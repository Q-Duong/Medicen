<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\Statistic;
use App\Models\Accountant;
use App\Models\HistoryEdit;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AccountantController extends Controller
{
	public function index()
	{
		if (!Session::has('year')) {
			Session::put('year', Carbon::now()->year);
		}
		return view('pages.admin.accountant.index');
	}

	public function getAccountant(Request $request)
	{
		$year = $request->input('year', session('year', Carbon::now()->year));
		$type = $request->input('type', session('type', 'all'));

		session(['year' => $year, 'type' => $type]);

		$params = array_merge($request->all(), [
			'year' => $year,
			'type' => $type
		]);

		$baseQuery = Accountant::getAccountantByFilter($params);

		// $totals = null;
		// if (!$request->has('cursor')) {
		// 	$cacheKey = 'acc_totals_' . $year . '_' . $type;
		// 	$totals = Cache::remember($cacheKey, 3600, function () use ($baseQuery) {
		// 		return $baseQuery->clone()->selectRaw('
		// 			SUM(order_price) as totalPrice,
		// 			SUM(accountant_owe) as totalOwe,
		// 			SUM(accountant_amount_paid) as totalAmountPaid,
		// 			SUM(order_quantity) as totalQuantity,
		// 			SUM(order_discount) as totalDiscount,
		// 			SUM(accountant_35X43) as total35,
		// 			SUM(accountant_polime) as totalPolime,
		// 			SUM(accountant_8X10) as total8,
		// 			SUM(accountant_10X12) as total10,
		// 			SUM(accountant_film_bag) as totalPack
		// 		')->first();
		// 	});
		// }

		$totals = $baseQuery->clone()
			->selectRaw('
			COUNT(*) as total_rows,
			SUM(orders.order_price) as total_price,
			SUM(accountants.accountant_owe) as total_owe,
			SUM(accountants.accountant_amount_paid) as total_amount_paid,
			SUM(orders.order_quantity) as total_quantity,
			SUM(orders.order_discount) as total_discount,
			SUM(accountants.accountant_35X43) as total_35,
			SUM(accountants.accountant_polime) as total_polime,
			SUM(accountants.accountant_8X10) as total_8,
			SUM(accountants.accountant_10X12) as total_10,
			SUM(accountants.accountant_film_bag) as total_pack
		')->first();

		$perPage = 20;
		$data = $baseQuery
			->orderBy('ord_start_day', 'ASC')
			->select(
				'accountants.id',
				'accountants.order_id',
				'accountants.accountant_month',
				'accountant_distance',
				'accountant_deadline',
				'accountant_number',
				'accountant_date',
				'accountant_payment',
				'accountant_day_payment',
				'accountant_method',
				'accountant_amount_paid',
				'accountant_owe',
				'accountant_discount_day',
				'accountant_doctor_read',
				'accountant_doctor_date_payment',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'accountant_status',
				'ord_type',
				'ord_start_day',
				'ord_form',
				'ord_note',
				'ord_cty_name',
				'order_vat',
				'order_quantity',
				'order_cost',
				'order_price',
				'order_percent_discount',
				'order_discount',
				'order_profit',
				'orders.status_id',
				'car_name',
				'unit_name'
			)
			->cursorPaginate($perPage);
		$sttStart = $request->input('current_count', 0);

		$response = [
			'success'       => true,
			'html'          => view('pages.admin.accountant.data_render', ['accountantData' => $data, 'sttStart' => $sttStart])->render(),
			'next_page_url' => $data->nextPageUrl(),
		];

		if ($totals) {
			$response['totals'] = [
				'totalPrice'      => $totals->total_price ?? 0,
				'totalOwe'        => $totals->total_owe ?? 0,
				'totalAmountPaid' => $totals->total_amount_paid ?? 0,
				'totalQuantity'   => $totals->total_quantity ?? 0,
				'totalDiscount'   => $totals->total_discount ?? 0,
				'total35'         => $totals->total_35 ?? 0,
				'totalPolime'     => $totals->total_polime ?? 0,
				'total8'          => $totals->total_8 ?? 0,
				'total10'         => $totals->total_10 ?? 0,
				'totalPack'       => $totals->total_pack ?? 0,
			];
		}
		return response()->json($response);
	}

	public function getFilterOptions(Request $request)
	{
		// 1. Lấy field đang cần load options
		$currentField = $request->input('field');

		// 2. Lấy toàn bộ tham số gửi lên (bao gồm year, type và các filter khác)
		$params = $request->all();
		// 3. LOGIC EXCEL: "SELF-EXCLUSION"
		// Khi đang lấy danh sách options cho cột "car_name", ta KHÔNG ĐƯỢC lọc theo "car_name"
		// Nếu lọc theo chính nó, danh sách trả về sẽ chỉ còn lại đúng cái đang chọn, user không chọn cái khác được.
		if (isset($params[$currentField])) {
			unset($params[$currentField]);
		}

		// 2. Map tên field sang tên cột DB chính xác
		// LƯU Ý: Phải khớp 100% với data-field bên View và cột trong Database
		$fieldMapping = [
			'order_id'                          => 'accountants.order_id',
			'status_id'                         => 'orders.status_id',
			'car_name'                          => 'car_name',
			'unit_name'                         => 'unit_name',
			'accountant_month'                  => 'accountants.accountant_month',
			'accountant_distance'               => 'accountant_distance',
			'accountant_deadline'               => 'accountant_deadline',
			'accountant_number'                 => 'accountant_number',
			'accountant_date'                   => 'accountant_date',
			'accountant_payment'                => 'accountant_payment',
			'accountant_day_payment'            => 'accountant_day_payment',
			'accountant_method'                 => 'accountant_method',
			'accountant_amount_paid'            => 'accountant_amount_paid',
			'accountant_owe'                    => 'accountant_owe',
			'accountant_discount_day'           => 'accountant_discount_day',
			'accountant_doctor_read'            => 'accountant_doctor_read',
			'accountant_doctor_date_payment'    => 'accountant_doctor_date_payment',
			'accountant_35X43'                  => 'accountant_35X43',
			'accountant_polime'                 => 'accountant_polime',
			'accountant_8X10'                   => 'accountant_8X10',
			'accountant_10X12'                  => 'accountant_10X12',
			'accountant_film_bag'               => 'accountant_film_bag',
			'accountant_note'                   => 'accountant_note',
			'accountant_status'                 => 'accountant_status',
			'ord_type'                          => 'ord_type',
			'ord_start_day'                     => 'ord_start_day',
			'ord_form'                          => 'ord_form',
			'ord_note'                          => 'ord_note',
			'ord_cty_name'                      => 'ord_cty_name',
			'order_vat' 						=> 'order_vat',
			'order_quantity'                    => 'order_quantity',
			'order_cost'                        => 'order_cost',
			'order_price'                       => 'order_price',
			'order_percent_discount'            => 'order_percent_discount',
			'order_discount'                    => 'order_discount',
			'order_profit'                      => 'order_profit',
		];

		if (!array_key_exists($currentField, $fieldMapping)) {
			return response()->json([]);
		}

		$dbColumn = $fieldMapping[$currentField];

		// 5. Gọi Model với bộ params đã loại trừ chính mình
		// Lúc này Model sẽ lọc theo Year + Type + Các cột khác (trừ cột hiện tại)
		$query = Accountant::getAccountantByFilter($params);

		// 6. Lấy dữ liệu Unique (Giữ nguyên logic)
		if ($currentField == 'order_vat') {
			$data = $query->pluck($dbColumn)
				->map(fn($item) => mb_strtoupper($item, 'UTF-8'))
				->unique()->sort()->values();
		} else {
			$data = $query->select($dbColumn)
				->distinct()
				->orderBy($dbColumn, 'asc')
				->pluck($dbColumn);
			$data = $data->values();
		}

		return response()->json($data);
	}

	public function updateRow(Request $request)
	{
		$id = $request->id;
		$formData = $request->row_data;

		$accountant = Accountant::findOrFail($id);
		if (!$accountant) {
			return response()->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu!']);
		}

		$order = $accountant->order;
		if ($order && $order->status_id == 3) {
			return response()->json([
				'success' => false,
				'message' => 'Đơn hàng này đã chốt, không thể chỉnh sửa!'
			]);
		}

		$moneyFields = [
			'order_price',
			'order_cost',
			'order_quantity',
			'order_discount',
			'accountant_amount_paid',
			'accountant_owe',
			'order_profit',
		];

		$dateFields = [
			'accountant_date',
			'accountant_day_payment',
			'accountant_discount_day',
			'accountant_doctor_date_payment'
		];

		// 3. Chuẩn bị các thùng chứa dữ liệu riêng biệt
		$dataForAccountant = [];
		$dataForOrder      = [];

		$fillableAccountant = $accountant->getFillable();
		$fillableOrder      = $order ? $order->getFillable() : [];

		// 4. Xử lý dữ liệu
		if ($formData && is_array($formData)) {
			foreach ($formData as $item) {
				$column = $item['name'];
				$value  = $item['value'];

				if ($column == '_token') continue;

				// --- CLEAN DATA (Giữ nguyên logic cũ) ---
				if (in_array($column, $moneyFields)) {
					$value = preg_replace('/[^0-9]/', '', $value);
					if ($value === '') $value = 0;
				}

				if (in_array($column, $dateFields)) {
					if ($value && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
						try {
							$value = Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
						} catch (\Exception $e) {
							$value = null;
						}
					}
				}

				// --- PHÂN LOẠI DỮ LIỆU (THE "SORTING HAT") ---
				if (in_array($column, $fillableAccountant)) {
					$dataForAccountant[$column] = $value;
				}
				if ($order && in_array($column, $fillableOrder)) {
					$dataForOrder[$column] = $value;
				}
			}
		}

		DB::beginTransaction();
		try {
			if (!empty($dataForAccountant)) {
				$accountant->update($dataForAccountant);
			}

			if ($order) {
				if (!empty($dataForOrder)) {
					$order->update($dataForOrder);
				}
				if ($order->status_id != 3) {
					$order->status_id = 4;
					$order->save();
				}
			}

			$response = [
				'success'       => true,
			];

			$filters = [];
			if ($request->has('filter_data') && is_array($request->filter_data)) {
				foreach ($request->filter_data as $item) {
					$filters[$item['name']] = $item['value'];
				}
			}

			$baseQuery = Accountant::getAccountantByFilter($filters);

			$totals = $baseQuery->selectRaw('
				COUNT(*) as total_rows,
				SUM(orders.order_price) as total_price,
				SUM(accountants.accountant_owe) as total_owe,
				SUM(accountants.accountant_amount_paid) as total_amount_paid,
				SUM(orders.order_quantity) as total_quantity,
				SUM(orders.order_discount) as total_discount,
				SUM(accountants.accountant_35X43) as total_35,
				SUM(accountants.accountant_polime) as total_polime,
				SUM(accountants.accountant_8X10) as total_8,
				SUM(accountants.accountant_10X12) as total_10,
				SUM(accountants.accountant_film_bag) as total_pack
				')->first();

			if ($totals) {
				$response['totals'] = [
					'totalPrice'      => $totals->total_price ?? 0,
					'totalOwe'        => $totals->total_owe ?? 0,
					'totalAmountPaid' => $totals->total_amount_paid ?? 0,
					'totalQuantity'   => $totals->total_quantity ?? 0,
					'totalDiscount'   => $totals->total_discount ?? 0,
					'total35'         => $totals->total_35 ?? 0,
					'totalPolime'     => $totals->total_polime ?? 0,
					'total8'          => $totals->total_8 ?? 0,
					'total10'         => $totals->total_10 ?? 0,
					'totalPack'       => $totals->total_pack ?? 0,
				];
			}

			DB::commit();

			return response()->json($response);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'message' => 'Lỗi lưu dữ liệu: ' . $e->getMessage()
			]);
		}
	}

	// public function complete(Request $request)
	// {
	// 	$data = $request->all();
	// 	$order = Order::find($data['order_id']);
	// 	$order->order_quantity = $data['order_quantity'];
	// 	$order->order_price = formatPrice($data['order_price']);
	// 	$order->order_cost = empty($data['order_cost']) ? 0 : formatPrice($data['order_cost']);
	// 	$order->order_vat = empty($data['order_vat']) ? null : $data['order_vat'];
	// 	$order->order_percent_discount = empty($data['order_percent_discount']) ? null : $data['order_percent_discount'];
	// 	$order->order_discount = formatPrice($data['order_discount']);
	// 	$order->order_profit = formatPrice($data['order_profit']);
	// 	$order->status_id = 3;
	// 	$order->save();

	// 	$order_date = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
	// 	$statistic = Statistic::where('order_date', $order_date)->get();

	// 	if ($statistic) {
	// 		$statistic_count = $statistic->count();
	// 	} else {
	// 		$statistic_count = 0;
	// 	}
	// 	if ($order->status_id == 3 && $order->accountant_updated != 1) {
	// 		$total_order = 1;
	// 		if ($statistic_count > 0) {
	// 			$statistic_update = Statistic::where('order_date', $order_date)->first();
	// 			$statistic_update->sales = (int)($statistic_update->sales + $order->order_price);
	// 			$statistic_update->quantity =  $statistic_update->quantity + $data['order_quantity'];
	// 			$statistic_update->total_order = $statistic_update->total_order + $total_order;
	// 			$statistic_update->total_profit = (int)($statistic_update->total_profit + $order->order_profit);
	// 			$statistic_update->save();
	// 		} else {
	// 			$statistic_new = new Statistic();
	// 			$statistic_new->order_date = $order_date;
	// 			$statistic_new->sales = $order->order_price;
	// 			$statistic_new->quantity =  (int)$data['order_quantity'];
	// 			$statistic_new->total_order = $total_order;
	// 			$statistic_new->total_profit = (int)$order->order_profit;
	// 			$statistic_new->save();
	// 		}
	// 	}
	// 	$order->accountant_updated = 1;
	// 	$order->save();

	// 	$accountant = Accountant::findOrFail($data['accountant_id']);
	// 	$accountant->accountant_deadline = empty($data['accountant_deadline']) ? null : $data['accountant_deadline'];
	// 	$accountant->accountant_number = empty($data['accountant_number']) ? null : $data['accountant_number'];
	// 	$accountant->accountant_date = empty($data['accountant_date']) ? null : formatDate($data['accountant_date']);
	// 	$accountant->accountant_day_payment = empty($data['accountant_day_payment']) ? null : formatDate($data['accountant_day_payment']);
	// 	$accountant->accountant_method = empty($data['accountant_method']) ? null : $data['accountant_method'];
	// 	$accountant->accountant_amount_paid = formatPrice($data['accountant_amount_paid']);
	// 	$accountant->accountant_owe = formatPrice($data['accountant_owe']);
	// 	$accountant->accountant_discount_day = empty($data['accountant_discount_day']) ? null : formatDate($data['accountant_discount_day']);
	// 	$accountant->accountant_doctor_read = empty($data['accountant_doctor_read']) ? null : $data['accountant_doctor_read'];
	// 	$accountant->accountant_doctor_date_payment = empty($data['accountant_doctor_date_payment']) ? null : formatDate($data['accountant_doctor_date_payment']);
	// 	$accountant->accountant_35X43 = empty($data['accountant_35X43']) ? null : $data['accountant_35X43'];
	// 	$accountant->accountant_polime = empty($data['accountant_polime']) ? null : $data['accountant_polime'];
	// 	$accountant->accountant_8X10 = empty($data['accountant_8X10']) ? null : $data['accountant_8X10'];
	// 	$accountant->accountant_10X12 = empty($data['accountant_10X12']) ? null : $data['accountant_10X12'];
	// 	$accountant->accountant_film_bag = empty($data['accountant_film_bag']) ? null : $data['accountant_film_bag'];
	// 	$accountant->accountant_note = empty($data['accountant_note']) ? null : $data['accountant_note'];
	// 	$accountant->accountant_status = $data['accountant_status'];
	// 	$accountant->save();

	// 	return response()->json(['success' => 'Đơn hàng đã được hoàn tất.']);
	// }

	// public function complete(Request $request)
	// {
	// 	try {
	// 		$orderId = $request->order_id;
	// 		$order = \App\Models\Order::find($orderId);

	// 		if (!$order) {
	// 			return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng!']);
	// 		}

	// 		// 2. Cập nhật trạng thái thành 3 (Đã hoàn thành / Đã xử lý)
	// 		$order->status_id = 3;
	// 		$order->save();

	// 		// 3. Xóa Cache (Quan trọng: Để các thống kê tính lại)
	// 		$year = session('year', \Carbon\Carbon::now()->year);
	// 		$type = session('type', 'all');
	// 		\Illuminate\Support\Facades\Cache::forget('acc_totals_' . $year . '_' . $type);

	// 		return response()->json([
	// 			'success' => true,
	// 			'message' => 'Đã cập nhật trạng thái thành công!'
	// 		]);
	// 	} catch (\Exception $e) {
	// 		return response()->json([
	// 			'success' => false,
	// 			'message' => 'Lỗi: ' . $e->getMessage()
	// 		]);
	// 	}
	// }

	public function complete(Request $request)
	{
		DB::beginTransaction(); // Dùng Transaction cho an toàn
		try {
			// 1. Tìm đơn hàng
			$orderId = $request->order_id;
			$order = Order::find($orderId);

			if (!$order) {
				return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng!']);
			}

			// Kiểm tra nếu đơn đã chốt rồi thì thôi (tránh cộng dồn doanh thu nhiều lần)
			if ($order->status_id == 3) {
				return response()->json(['success' => false, 'message' => 'Đơn hàng này đã được chốt trước đó rồi!']);
			}

			// 2. CẬP NHẬT TRẠNG THÁI
			$order->status_id = 3;

			// 3. CẬP NHẬT THỐNG KÊ (LOGIC BẠN YÊU CẦU)
			// Chỉ cộng dồn nếu chưa được cộng (accountant_updated != 1)
			if ($order->accountant_updated != 1) {

				// Lấy ngày đơn hàng (Giả sử cột ord_start_day là ngày tính doanh thu)
				// Nếu không có ord_start_day thì dùng created_at hoặc today()
				$order_date = $order->ord_start_day ? \Carbon\Carbon::parse($order->ord_start_day)->format('Y-m-d') : date('Y-m-d');

				$statistic = Statistic::where('order_date', $order_date)->first();

				// Biến đếm số đơn (mỗi lần chốt là 1 đơn)
				$total_order_count = 1;

				if ($statistic) {
					// TRƯỜNG HỢP 1: Đã có thống kê ngày này -> CỘNG DỒN
					$statistic->sales        = (int)($statistic->sales + $order->order_price);
					// Lưu ý: Ở đây lấy $order->order_quantity trực tiếp từ DB
					$statistic->quantity     = $statistic->quantity + $order->order_quantity;
					$statistic->total_order  = $statistic->total_order + $total_order_count;
					$statistic->total_profit = (int)($statistic->total_profit + $order->order_profit);
					$statistic->save();
				} else {
					// TRƯỜNG HỢP 2: Chưa có thống kê ngày này -> TẠO MỚI
					$statistic_new = new Statistic();
					$statistic_new->order_date   = $order_date;
					$statistic_new->sales        = (int)$order->order_price;
					$statistic_new->quantity     = (int)$order->order_quantity;
					$statistic_new->total_order  = $total_order_count;
					$statistic_new->total_profit = (int)$order->order_profit;
					$statistic_new->save();
				}

				// Đánh dấu là đã cập nhật doanh thu để không bao giờ cộng lại lần 2
				$order->accountant_updated = 1;
			}

			// Lưu thay đổi vào bảng Order
			$order->save();

			DB::commit(); // Xác nhận Transaction

			// 4. Xóa Cache (Quan trọng)
			$year = session('year', \Carbon\Carbon::now()->year);
			$type = session('type', 'all');
			Cache::forget('acc_totals_' . $year . '_' . $type);

			return response()->json([
				'success' => true,
				'message' => 'Đã chốt đơn và cập nhật thống kê thành công!'
			]);
		} catch (\Exception $e) {
			DB::rollBack(); // Có lỗi thì hoàn tác
			return response()->json([
				'success' => false,
				'message' => 'Lỗi: ' . $e->getMessage()
			]);
		}
	}

	//Sales
	public function updateOrder($order_id)
	{
		$accountant = Accountant::getAccountantForUpdateOrder($order_id);
		return view('pages.admin.accountant.sales.edit', compact('accountant'));
	}

	public function storeOrder(Request $request, $order_id)
	{
		$order = Order::findOrFail($order_id);
		$orderDetail = OrderDetail::findOrFail($order->order_detail_id);
		if ($order->order_status == 4) {
			if (Carbon::parse($orderDetail->ord_start_day)->month < 12) {
				$orderDetail->ord_cty_name = empty($request->ord_cty_name) ? $orderDetail->ord_cty_name : $request->ord_cty_name;
				$orderDetail->save();
			}
		} else {
			$order->order_cost = $request->order_all_in_one == 0 ? formatPrice($request->order_cost) : 0;
			$order->order_all_in_one = $request->order_all_in_one;
			$order->order_percent_discount =  $request->order_percent_discount;
			$order->order_vat =  $request->order_vat;
			$order->order_price = formatPrice($request->order_price);
			$order->order_quantity =  empty($request->order_quantity) ? $order->order_quantity : $request->order_quantity;
			$order->save();
			if (Carbon::parse($orderDetail->ord_start_day)->month < 12) {
				$orderDetail->ord_cty_name = empty($request->ord_cty_name) ? $orderDetail->ord_cty_name : $request->ord_cty_name;
				$orderDetail->save();
			}
			// $orderDetail->ord_cty_name = empty($request->ord_cty_name) ? $orderDetail->ord_cty_name : $request->ord_cty_name;
			// $orderDetail->save();
			$accountant = Accountant::where('order_id', $order_id)->first();
			$accountant->accountant_owe = $order->order_price;
			$accountant->save();
		}

		$history = new HistoryEdit();
		$history->order_id = $order_id;
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Sửa đơn hàng';
		$history->save();
		return Redirect::route('order.index')->with('success', 'Cập nhật thông tin báo cáo thành công');
	}

	public function indexSales()
	{
		if (!Session::has('year')) {
			Session::put('year', Carbon::now()->year);
		}
		return view('pages.admin.accountant.sales.index');
	}

	public function getAccountantSales(Request $request)
	{
		$year = $request->input('year', session('year', Carbon::now()->year));
		$type = $request->input('type', session('type', 'all'));

		session(['year' => $year, 'type' => $type]);

		$params = array_merge($request->all(), [
			'year' => $year,
			'type' => $type
		]);

		$baseQuery = Accountant::getAccountantByFilter($params);

		$totals = $baseQuery->clone()
			->selectRaw('
			COUNT(*) as total_rows,
			SUM(orders.order_price) as total_price,
			SUM(accountants.accountant_owe) as total_owe,
			SUM(accountants.accountant_amount_paid) as total_amount_paid,
			SUM(orders.order_quantity) as total_quantity,
			SUM(orders.order_discount) as total_discount,
			SUM(accountants.accountant_35X43) as total_35,
			SUM(accountants.accountant_polime) as total_polime,
			SUM(accountants.accountant_8X10) as total_8,
			SUM(accountants.accountant_10X12) as total_10,
			SUM(accountants.accountant_film_bag) as total_pack
		')->first();

		$perPage = 20;
		$data = $baseQuery
			->orderBy('ord_start_day', 'ASC')
			->select(
				'accountants.id',
				'accountants.order_id',
				'accountants.accountant_month',
				'accountant_distance',
				'accountant_deadline',
				'accountant_number',
				'accountant_date',
				'accountant_payment',
				'accountant_day_payment',
				'accountant_method',
				'accountant_amount_paid',
				'accountant_owe',
				'accountant_discount_day',
				'accountant_doctor_read',
				'accountant_doctor_date_payment',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'accountant_status',
				'ord_type',
				'ord_start_day',
				'ord_form',
				'ord_note',
				'ord_cty_name',
				'order_vat',
				'order_quantity',
				'order_cost',
				'order_price',
				'order_percent_discount',
				'order_discount',
				'order_profit',
				'orders.status_id',
				'car_name',
				'unit_name'
			)
			->cursorPaginate($perPage);
		$sttStart = $request->input('current_count', 0);

		$response = [
			'success'       => true,
			'html'          => view('pages.admin.accountant.sales.data_render', ['accountantData' => $data, 'sttStart' => $sttStart])->render(),
			'next_page_url' => $data->nextPageUrl(),
		];

		if ($totals) {
			$response['totals'] = [
				'totalPrice'      => $totals->total_price ?? 0,
				'totalOwe'        => $totals->total_owe ?? 0,
				'totalAmountPaid' => $totals->total_amount_paid ?? 0,
				'totalQuantity'   => $totals->total_quantity ?? 0,
				'totalDiscount'   => $totals->total_discount ?? 0,
				'total35'         => $totals->total_35 ?? 0,
				'totalPolime'     => $totals->total_polime ?? 0,
				'total8'          => $totals->total_8 ?? 0,
				'total10'         => $totals->total_10 ?? 0,
				'totalPack'       => $totals->total_pack ?? 0,
			];
		}
		return response()->json($response);
	}

	//Result
	public function indexResult()
	{
		if (!Session::has('year')) {
			Session::put('year', Carbon::now()->year);
		}
		return view('pages.admin.accountant.result.index');
	}

	public function getAccountantResult(Request $request)
	{
		$year = $request->input('year', session('year', Carbon::now()->year));
		$type = $request->input('type', session('type', 'all'));

		session(['year' => $year, 'type' => $type]);

		$params = array_merge($request->all(), [
			'year' => $year,
			'type' => $type
		]);

		$baseQuery = Accountant::getAccountantByFilter($params);

		$totals = $baseQuery->clone()
			->selectRaw('
			COUNT(*) as total_rows,
			SUM(orders.order_price) as total_price,
			SUM(accountants.accountant_owe) as total_owe,
			SUM(accountants.accountant_amount_paid) as total_amount_paid,
			SUM(orders.order_quantity) as total_quantity,
			SUM(orders.order_discount) as total_discount,
			SUM(accountants.accountant_35X43) as total_35,
			SUM(accountants.accountant_polime) as total_polime,
			SUM(accountants.accountant_8X10) as total_8,
			SUM(accountants.accountant_10X12) as total_10,
			SUM(accountants.accountant_film_bag) as total_pack
		')->first();

		$perPage = 20;
		$data = $baseQuery
			->orderBy('ord_start_day', 'ASC')
			->select(
				'accountants.id',
				'accountants.order_id',
				'accountants.accountant_month',
				'accountant_distance',
				'accountant_deadline',
				'accountant_number',
				'accountant_date',
				'accountant_payment',
				'accountant_day_payment',
				'accountant_method',
				'accountant_amount_paid',
				'accountant_owe',
				'accountant_discount_day',
				'accountant_doctor_read',
				'accountant_doctor_date_payment',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'accountant_status',
				'ord_type',
				'ord_start_day',
				'ord_form',
				'ord_note',
				'ord_cty_name',
				'order_vat',
				'order_quantity',
				'order_cost',
				'order_price',
				'order_percent_discount',
				'order_discount',
				'order_profit',
				'orders.status_id',
				'car_name',
				'unit_name'
			)
			->cursorPaginate($perPage);
		$sttStart = $request->input('current_count', 0);

		$response = [
			'success'       => true,
			'html'          => view('pages.admin.accountant.result.data_render', ['accountantData' => $data, 'sttStart' => $sttStart])->render(),
			'next_page_url' => $data->nextPageUrl(),
		];

		if ($totals) {
			$response['totals'] = [
				'totalPrice'      => $totals->total_price ?? 0,
				'totalOwe'        => $totals->total_owe ?? 0,
				'totalAmountPaid' => $totals->total_amount_paid ?? 0,
				'totalQuantity'   => $totals->total_quantity ?? 0,
				'totalDiscount'   => $totals->total_discount ?? 0,
				'total35'         => $totals->total_35 ?? 0,
				'totalPolime'     => $totals->total_polime ?? 0,
				'total8'          => $totals->total_8 ?? 0,
				'total10'         => $totals->total_10 ?? 0,
				'totalPack'       => $totals->total_pack ?? 0,
			];
		}
		return response()->json($response);
	}
}
