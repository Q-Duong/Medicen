<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Exports\ExcelExport;
use App\Exports\MisaExport;
use App\Http\Requests\OrderClientRequestForm;
use App\Http\Requests\OrderRequestForm;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Unit;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Customer;
use App\Models\Accountant;
use App\Models\ExcelUpload;
use App\Models\HistoryEdit;
use App\Services\Accountant\OrderMoney;
use App\Models\TempFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDF;

class OrderController extends Controller
{
	//Client
	public function createOrderClient()
	{
		return view('pages.client.order.index');
	}

	public function storeOrderClient(Request $request)
	{
		$this->checkOrder($request);
		DB::beginTransaction();
		try {
			$data = $request->all();
			$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];
			$customer = new Customer();
			$customer->customer_name = $data['customer_name'];
			$customer->customer_phone = $data['customer_phone'];
			$customer->customer_address = $data['customer_address'];
			$customer->customer_note = '';
			$customer->save();

			$orderDetail = new OrderDetail();
			$orderDetail->ord_start_day = $data['ord_start_day'];
			$orderDetail->ord_end_day = $data['ord_end_day'];
			$orderDetail->ord_select = $data['ord_select'];
			$orderDetail->ord_doctor_read = '';
			$orderDetail->ord_film = '';
			$orderDetail->ord_form = 'ko in';
			$orderDetail->ord_print = '';
			$orderDetail->ord_form_print = '';
			$orderDetail->ord_print_result = '';
			$orderDetail->ord_film_sheet = '';
			$orderDetail->ord_note = '';
			$orderDetail->ord_deadline = '';
			$orderDetail->ord_deliver_results = '';
			$orderDetail->ord_cty_name = '';
			$orderDetail->ord_time = '';
			$orderDetail->ord_list_file = '';
			$orderDetail->ord_list_file_path = '';
			$orderDetail->ord_email = '';
			if (in_array($request->ord_select, $ultraSound)) {
				$orderDetail->ord_type = 2;
			} else {
				$orderDetail->ord_type = 1;
			}
			$orderDetail->save();

			$order = new Order();
			$order->customer_id = $customer->id;
			$order->order_detail_id = $orderDetail->id;
			$order->unit_id = 1;
			$order->order_quantity = $data['order_quantity'];
			$order->order_cost = 0;
			$order->order_all_in_one = 0;
			$order->order_price = 0;
			$order->order_discount = 0;
			$order->order_profit = 0;
			$order->status_id = 0;
			$order->schedule_status = 0;
			$order->order_warning = 'Không';
			$order->accountant_updated = 0;
			$order->save();

			$format = explode("-", $data['ord_start_day']);

			$accountant = new Accountant();
			$accountant->order_id = $order->id;
			$accountant->accountant_owe = 0;
			$accountant->accountant_amount_paid = 0;

			if ($format[1] == 10) {
				$accountant->accountant_month = $format[1];
			} else {
				$month = explode("0", $format[1]);
				if (count($month) > 1) {
					$accountant->accountant_month = $month[1];
				} else {
					$accountant->accountant_month = $month[0];
				}
			}
			$accountant->save();

			DB::commit();
			return Redirect::route('order.clients.alert');
		} catch (\Exception $e) {
			DB::rollback();
			return Redirect()->back();
		}
	}

	public function createOrderDetailsClient()
	{
		return view('pages.client.order.index_details');
	}

	public function storeOrderDetailsClient(OrderClientRequestForm $request)
	{
		DB::beginTransaction();
		try {
			$data = $request->all();
			$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];
			$customer = new Customer();
			$customer->customer_name = $data['customer_name'];
			$customer->customer_phone = $data['customer_phone'];
			$customer->customer_address = $data['customer_address'];
			$customer->save();

			$orderDetail = new OrderDetail();
			$orderDetail->ord_start_day = $data['ord_start_day'];
			$orderDetail->ord_end_day = $data['ord_start_day'];
			$orderDetail->ord_select = 'Phổi (1 Tư thế)';
			$orderDetail->ord_film = $data['ord_film'];
			$orderDetail->ord_form = $data['ord_film'] == '' ? 'ko in' : $data['ord_form'];
			// $orderDetail->ord_note = $data['ord_note'];
			$orderDetail->ord_cty_name = $data['ord_cty_name'];
			$orderDetail->ord_time = $data['ord_time'];
			$orderDetail->ord_list_file = '';
			$orderDetail->ord_list_file_path = '';
			$orderDetail->ord_email = '';
			if (in_array($request->ord_select, $ultraSound)) {
				$orderDetail->ord_type = 2;
			} else {
				$orderDetail->ord_type = 1;
			}
			$orderDetail->save();

			$order = new Order();
			$order->customer_id = $customer->id;
			$order->order_detail_id = $orderDetail->id;
			$order->unit_id = 1;
			$order->order_quantity = $data['order_quantity'];
			$order->order_price = 0;
			$order->order_all_in_one = 0;
			$order->order_cost = 0;
			$order->order_discount = 0;
			$order->order_profit = 0;
			$order->status_id = 0;
			$order->schedule_status = 0;
			$order->order_warning = 'Không';
			$order->accountant_updated = 0;
			$order->save();

			// for($i=0 ;$i<=4; $i++){
			//     $car = new CarKTV();
			//     $car->order_id = $order->order_id;
			//     $car->car_name = $i+1;
			// 	$car->car_active = 0;
			// 	$car->car_driver_name = '';
			// 	$car->car_ktv_name_1 = '';
			// 	$car->car_ktv_name_2 = '';
			// 	$car->order_quantity_draft = 0;
			//     $car->save();
			// }

			$format = explode("-", $data['ord_start_day']);

			$accountant = new Accountant();
			$accountant->order_id = $order->id;
			$accountant->accountant_owe = 0;
			$accountant->accountant_amount_paid = 0;

			if ($format[1] == 10) {
				$accountant->accountant_month = $format[1];
			} else {
				$month = explode("0", $format[1]);
				if (count($month) > 1) {
					$accountant->accountant_month = $month[1];
				} else {
					$accountant->accountant_month = $month[0];
				}
			}
			$accountant->save();
			DB::commit();
			return Redirect::route('order.clients.alert');
		} catch (\Exception $e) {
			DB::rollback();
			return Redirect::route('home.index');
		}
	}

	public function successfulRegistration()
	{
		return view('pages.client.successful_medical_registration');
	}

	//Admin

	public function index(Request $request)
	{
		// Thẻ đếm theo trạng thái: theo các ô lọc đang chọn (trừ chính trạng thái)
		$baseQuery = \App\Services\OrderFilters::apply(\App\Services\OrderFilters::base(), $request, ['status_id', 'schedule_status']);

		// 2. Clone Query gốc để đếm động cho thẻ thống kê
		$summary = [
			'all' => (clone $baseQuery)->count(),
			'new' => (clone $baseQuery)->where('orders.status_id', 0)->count(),
			'processing' => (clone $baseQuery)->where('orders.status_id', 1)->count(),
			'schedule' => (clone $baseQuery)->where('orders.status_id', 5)->count(),
			'updated' => (clone $baseQuery)->where('orders.status_id', 2)->count(),
			'revenue_updated' => (clone $baseQuery)->where('orders.status_id', 4)->count(),
			'completed' => (clone $baseQuery)->where('orders.status_id', 3)->count(),
		];

		$getAll = Order::getAll($request);

		// Gợi ý các ô lọc (liên kết với nhau: mỗi ô chỉ còn giá trị có đơn theo các ô khác)
		$filterOptions = \App\Services\OrderFilters::options($request);
		return view('pages.admin.order.index', compact('getAll', 'summary', 'filterOptions'));
	}

	/** Gợi ý các ô lọc danh sách đơn theo các ô đang chọn (trang gọi lại mỗi khi đổi một ô lọc) */
	public function filterOptions(Request $request)
	{
		return response()->json(\App\Services\OrderFilters::options($request));
	}

	public function create()
	{
		$getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
		return view('pages.admin.order.create')->with(compact('getAllUnit'));
	}

	public function store(OrderRequestForm $request)
	{

		DB::beginTransaction();
		try {
			$data = $request->all();
			// Số ca có thể gõ kèm dấu chấm (1.200): chỉ lấy chữ số
			$data['order_quantity'] = preg_replace('/\D/', '', (string) ($data['order_quantity'] ?? ''));

			$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];

			$customer = new Customer();

			$customer->customer_name = $data['customer_name'];
			$customer->customer_phone = $data['customer_phone'];
			$customer->customer_address = $data['customer_address'];
			$customer->customer_location = $data['customer_location'];
			$customer->save();

			$orderDetail = new OrderDetail();
			$orderDetail->ord_start_day = $data['ord_start_day'];
			$orderDetail->ord_end_day = $data['ord_start_day'];
			$orderDetail->ord_session = $data['ord_session'];
			$orderDetail->ord_select = $data['ord_select'];
			$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
			$orderDetail->ord_film = $data['ord_film'];
			$orderDetail->ord_form = $data['ord_form'];
			$orderDetail->ord_print = $data['ord_print'];
			$orderDetail->ord_form_print = $data['ord_form_print'];
			$orderDetail->ord_print_result = $data['ord_print_result'];
			$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
			$orderDetail->ord_note = $data['ord_note'];
			$orderDetail->ord_deadline = $this->deadlineFunction($data['ord_start_day'], $data['unit_id'], $data['order_quantity']);
			$orderDetail->ord_deliver_results = $data['ord_deliver_results'];
			$orderDetail->ord_cty_name = $data['ord_cty_name'];
			$orderDetail->ord_time = $data['ord_time'];
			$orderDetail->ord_email = $data['ord_email'];
			$get_file = $request->ord_list_file;
			if ($get_file) {
				$name = '';
				$path = '';
				foreach ($get_file as $key => $file) {
					$tmp_file = TempFile::where('filename', $file)->first();
					$name .= $tmp_file->filename . ',';
					$path .= $tmp_file->folder . ',';
					$tmp_file->delete();
				}
				$orderDetail->ord_list_file = substr($name, 0, -1);
				$orderDetail->ord_list_file_path = substr($path, 0, -1);
			} else {
				$orderDetail->ord_list_file = '';
				$orderDetail->ord_list_file_path = '';
			}

			if (in_array($request->ord_select, $ultraSound)) {
				$orderDetail->ord_type = 2;
			} else {
				$orderDetail->ord_type = 1;
			}
			$orderDetail->save();

			$order = new Order();
			$order->customer_id = $customer->id;
			$order->order_detail_id = $orderDetail->id;
			$order->unit_id = $data['unit_id'];
			$order->status_id = 5;
			$order->order_quantity = $data['order_quantity'];
			$order->order_price = formatPrice($data['order_price']);
			$order->order_cost = $data['order_all_in_one'] == 0 ? formatPrice($data['order_cost']) : 0;
			$order->order_all_in_one = $data['order_all_in_one'];
			$order->order_percent_discount = $data['order_percent_discount'];
			$order->order_vat = $data['order_vat'];
			$order->order_discount = 0;
			$order->order_profit = 0;
			$order->schedule_status = 0;
			$order->accountant_updated = 0;
			$order->order_warning = $data['order_warning'];
			$order->order_surcharge = $data['order_surcharge'];
			$order->overnight = $data['overnight'];
			$order->complete = $data['complete'] ?? ($order->complete ?? 0); // nút Cập nhật / Chưa hoàn thành; thiếu thì giữ giá trị cũ
			$order->save();

			if ($request->filled('excel_upload_id')) {
				$upload = ExcelUpload::find($request->excel_upload_id);
				if ($upload && ! $upload->order_id) {
					$upload->syncWithOrder($order->id, (bool) $order->complete);
				}
			}

			$monthFormat = explode("-", $data['ord_start_day']);
			$accountant = new Accountant();
			$accountant->order_id = $order->id;
			$accountant->accountant_owe = formatPrice($data['order_price']);
			$accountant->accountant_amount_paid = 0;
			$accountant->accountant_distance = $data['accountant_distance'];
			if ($monthFormat[1] == 10) {
				$accountant->accountant_month = $monthFormat[1];
			} else {
				$month = explode("0", $monthFormat[1]);
				if (count($month) > 1) {
					$accountant->accountant_month = $month[1];
				} else {
					$accountant->accountant_month = $month[0];
				}
			}
			$accountant->save();

			// Đơn mới: tính Chiết khấu, Lợi nhuận, Còn nợ ngay (cùng công thức trang Công nợ)
			OrderMoney::sync($order, $accountant, OrderMoney::MONEY_FIELDS);

			$history = new HistoryEdit();
			$history->order_id = $order->id;
			$history->user_name = Auth::user()->email;
			$history->history_action = 'Thêm đơn hàng';
			$history->save();
			// Nhóm đi chung: Sale tích các đơn cùng đơn vị, cùng ngày ở form
			$group = $request->filled('group_touched') ? app(\App\Services\OrderGroups::class)->sync($order, (array) $request->input('group_with', [])) : null;
			DB::commit();
			return Redirect::route('order.index')->with('success', 'Thêm đơn hàng thành công' . ($group ? " · nhóm đi chung {$group->code}" : ''));
		} catch (\Exception $e) {
			DB::rollback();
			return Redirect()->back()->withInput()->with('error', 'Thêm đơn hàng thất bại');
		}
	}

	public function edit($order_id)
	{
		$order = Order::getOneOrder($order_id);
		$files = array_combine(explode(',', $order->ord_list_file), explode(',', $order->ord_list_file_path));
		$getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
		// Lịch xe hiện tại: nếu đổi ngày / buổi chụp thì lịch này sẽ bị hủy (form hỏi xác nhận trước)
		$schedule = \App\Models\CarKTV::where('order_id', $order_id)->where('car_active', 1)->first();
		// Đơn đã chụp xong: đổi ngày / buổi vẫn giữ lịch (form không hỏi hủy lịch)
		$scheduleKept = $schedule && \App\Services\Schedule\ScheduleCancel::keepsScheduleOnReschedule($order->status_id);
		// Đơn đã hoàn thành: form chỉ xem
		$orderClosed = (int) $order->status_id === Order::STATUS_CLOSED;
		// Đã có số ca thực tế (Kết quả đã cập nhật): khóa số lượng + hình thức in phim
		// Admin (role 0) sửa được mọi ô, trừ đơn đã hoàn thành
		$isAdmin = (int) Auth::user()->role === 0;
		$adminOverride = $isAdmin && !$orderClosed && in_array((int) $order->status_id, array_merge(Order::QTY_LOCKED_STATUSES, OrderMoney::LOCKED_STATUSES), true);
		// Đơn phụ thu ở trạng thái 2: không qua Kết quả nên số lượng do Sale nhập, chưa khóa (khóa khi kế toán cập nhật doanh thu)
		$qtyLocked = !$isAdmin && !((int) $order->order_surcharge === 1 && (int) $order->status_id === 2)
			&& in_array((int) $order->status_id, Order::QTY_LOCKED_STATUSES, true);
		// Đã cập nhật doanh thu / đã chốt: khóa các ô tiền (hiện nhãn "đã khóa" trên từng ô)
		$moneyLocked = $orderClosed || (!$isAdmin && in_array((int) $order->status_id, OrderMoney::LOCKED_STATUSES, true));
		// Nhóm đi chung hiện tại
		$groupId = Order::whereKey($order_id)->value('order_group_id');
		$groupCode = $groupId ? \App\Models\OrderGroup::whereKey($groupId)->value('code') : null;
		$groupMembers = $groupId ? Order::where('order_group_id', $groupId)->where('id', '!=', $order_id)->pluck('id')->all() : [];
		// Kiểm soát giá: đơn lỗ / biên thấp / chiết khấu cao cần kế toán duyệt (Phân tích tài chính → Duyệt giá)
		$priceCheck = app(\App\Services\Accounting\PriceGuard::class)->forOrder((int) $order_id);
		return view('pages.admin.order.edit', compact('order', 'getAllUnit', 'files', 'schedule', 'scheduleKept', 'orderClosed', 'qtyLocked', 'moneyLocked', 'adminOverride', 'groupCode', 'groupMembers', 'priceCheck'));
	}

	public function update(OrderRequestForm $request, $order_id)
	{
		$data = $request->all();
		// Số ca có thể gõ kèm dấu chấm (1.200): chỉ lấy chữ số
		$data['order_quantity'] = preg_replace('/\D/', '', (string) ($data['order_quantity'] ?? ''));
		$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];
		$order = Order::findOrFail($order_id);
		// Đơn đã hoàn thành: không sửa gì nữa
		if ($order->isClosed()) {
			return Redirect::route('order.edit', $order_id)->with('error', Order::closedMessage($order_id));
		}
		// Ngày / buổi trước khi sửa: đơn đã xếp xe mà đổi thì phải hủy lịch, đơn về "Chưa lên lịch"
		$oldDetail = OrderDetail::findOrFail($order->order_detail_id);
		$oldStart = (string) $oldDetail->ord_start_day;
		$oldSession = trim((string) $oldDetail->ord_session) ?: 'Cả ngày';
		$oldUnit = $order->unit_id;
		$oldStatus = (int) $order->status_id;
		// Đơn đã cập nhật doanh thu (4) / đã chốt (3) ở trang Công nợ: KHÔNG sửa các ô ảnh hưởng đến tiền,
		// giữ nguyên số trong database (kể cả khi form bị sửa tay gửi lên)
		// Admin (role 0) sửa được cả các ô bị khóa (tiền, số lượng, phần in phim…) — đơn đã hoàn thành đã chặn ở trên
		$isAdmin = (int) Auth::user()->role === 0;
		$moneyLocked = !$isAdmin && OrderMoney::isLocked($order);
		if ($moneyLocked) {
			$data = array_merge($data, OrderMoney::lockedFormValues($order));
		}
		// Đã có số ca thực tế (2: Kết quả đã cập nhật, 4: kế toán đã cập nhật doanh thu): giữ nguyên số lượng và cả phần
		// in phim / in phiếu / cảnh báo trong database (form khóa 2 ô này; gửi tay lên cũng không đổi được)
		if (!$isAdmin && !((int) $order->order_surcharge === 1 && (int) $order->status_id === 2)
			&& in_array((int) $order->status_id, Order::QTY_LOCKED_STATUSES, true)) {
			$data['order_quantity'] = $order->order_quantity;
			// Cả phần in phim / in phiếu / bác sĩ đọc / phim & phiếu / cảnh báo đơn: giữ như cũ
			foreach (Order::PRINT_LOCKED_FIELDS as $f) {
				$data[$f] = $oldDetail->{$f};
			}
			$data['order_warning'] = $order->order_warning;
		}
		$moneyBefore = OrderMoney::snapshot($order);
		$order->unit_id = $data['unit_id'];
		// Trước đây mọi đơn (trừ 5) bị đưa về 1 "Đang xử lý" mỗi lần sửa, kể cả đơn đã cập nhật số ca (2),
		// đã cập nhật doanh thu (4), đã chốt (3). Giờ chỉ đơn mới (0) chuyển sang 1, còn lại giữ nguyên.
		if ((int) $order->status_id === 0) {
			$order->status_id = 1;
		}
		$order->order_quantity = $data['order_quantity'];
		$order->order_price = formatPrice($data['order_price']);
		$order->order_cost = $data['order_all_in_one'] == 0 ? formatPrice($data['order_cost']) : 0;
		$order->order_all_in_one = $data['order_all_in_one'];
		$order->order_percent_discount = $data['order_percent_discount'];
		$order->order_vat = $data['order_vat'];
		$order->order_warning = $data['order_warning'];
		$order->order_surcharge = $data['order_surcharge'];
		$order->overnight = $data['overnight'];
		$order->complete = $data['complete'] ?? ($order->complete ?? 0); // nút Cập nhật / Chưa hoàn thành; thiếu thì giữ giá trị cũ
		$order->save();

		ExcelUpload::where('order_id', $order->id)->first()
			?->syncWithOrder($order->id, (bool) $order->complete);

		$monthFormat = explode("-", $data['ord_start_day']);
		$accountant = Accountant::where('order_id', $order_id)->first();
		$accountant->accountant_distance = $data['accountant_distance'];
		if ($monthFormat[1] == 10) {
			$accountant->accountant_month = $monthFormat[1];
		} else {
			$month = explode("0", $monthFormat[1]);
			if (count($month) > 1) {
				$accountant->accountant_month = $month[1];
			} else {
				$accountant->accountant_month = $month[0];
			}
		}
		$accountant->save();

		// Tiền: chỉ khi các ô tiền thật sự đổi mới tính lại Chiết khấu, Lợi nhuận, Còn nợ (cùng công thức trang Công nợ;
		// Còn nợ = Thành tiền − Đã thanh toán, không còn bị đưa về bằng Thành tiền mỗi lần sửa đơn)
		if (!$moneyLocked) {
			$moneyChanged = OrderMoney::changedFields($moneyBefore, $order);
			OrderMoney::sync($order, $accountant, $moneyChanged);
			// Admin sửa số ca / tiền ở đơn đang có cảnh báo "số ca đổi sau khi cập nhật doanh thu" = đã xử lý
			if ($moneyChanged && $order->qty_changed_from !== null) {
				OrderMoney::clearQtyFlag($order);
				$order->save();
			}
		}

		$customer_id = $order->customer_id;
		$order_detail_id = $order->order_detail_id;

		$customer = Customer::findOrFail($customer_id);
		$customer->customer_name = $data['customer_name'];
		$customer->customer_phone = $data['customer_phone'];
		$customer->customer_address = $data['customer_address'];
		$customer->customer_location = $data['customer_location'];
		$customer->save();

		$orderDetail = OrderDetail::findOrFail($order_detail_id);
		$orderDetail->ord_start_day = $data['ord_start_day'];
		$orderDetail->ord_end_day = $data['ord_start_day'];
		$orderDetail->ord_session = $data['ord_session'];
		$orderDetail->ord_select = $data['ord_select'];
		$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
		$orderDetail->ord_film = $data['ord_film'];
		$orderDetail->ord_form = $data['ord_form'];
		$orderDetail->ord_print = $data['ord_print'];
		$orderDetail->ord_form_print = $data['ord_form_print'];
		$orderDetail->ord_print_result = $data['ord_print_result'];
		$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
		$orderDetail->ord_note = $data['ord_note'];
		$orderDetail->ord_deadline = $this->deadlineFunction($data['ord_start_day'], $data['unit_id'], $data['order_quantity']);
		$orderDetail->ord_deliver_results = $data['ord_deliver_results'];
		$orderDetail->ord_cty_name = $data['ord_cty_name'];
		$orderDetail->ord_time = $data['ord_time'];
		$orderDetail->ord_email = $data['ord_email'];

		$get_file = $request->ord_list_file;
		if ($get_file) {
			$name = '';
			$path = '';
			foreach ($get_file as $key => $file) {
				$tmp_file = TempFile::where('filename', $file)->first();
				$name .= $tmp_file->filename . ',';
				$path .= $tmp_file->folder . ',';
				$tmp_file->delete();
			}
			$orderDetail->ord_list_file = $orderDetail->ord_list_file != '' ? $orderDetail->ord_list_file  . ',' . substr($name, 0, -1) : substr($name, 0, -1);
			$orderDetail->ord_list_file_path = $orderDetail->ord_list_file_path != '' ? $orderDetail->ord_list_file_path . ',' . substr($path, 0, -1) : substr($path, 0, -1);
		}
		if (in_array($request->ord_select, $ultraSound)) {
			$orderDetail->ord_type = 2;
		} else {
			$orderDetail->ord_type = 1;
		}
		$orderDetail->save();

		$history = new HistoryEdit();
		$history->order_id = $order_id;
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Sửa đơn hàng';
		$history->save();

		// Nhóm đi chung: theo lựa chọn trên form; form cũ không có khung nhóm mà đổi ngày / đơn vị thì rời nhóm
		$groups = app(\App\Services\OrderGroups::class);
		$group = null;
		if ($request->filled('group_touched')) {
			$group = $groups->sync($order, (array) $request->input('group_with', []));
		} elseif ($oldStart !== (string) $orderDetail->ord_start_day || (int) $oldUnit !== (int) $order->unit_id) {
			$groups->leave($order);
		}

		$newSession = trim((string) $orderDetail->ord_session) ?: 'Cả ngày';
		$cancelled = null;
		// Đơn đã chụp xong (2/3/4) thì giữ lịch xe (dữ liệu chấm công thật), chỉ đơn chưa chụp mới phải xếp lại
		if (($oldStart !== (string) $orderDetail->ord_start_day || $oldSession !== $newSession)
			&& !\App\Services\Schedule\ScheduleCancel::keepsScheduleOnReschedule($oldStatus)) {
			$cancelled = app(\App\Services\Schedule\ScheduleCancel::class)->cancel($order->fresh(),
				'đổi ngày chụp ' . date('d/m', strtotime($oldStart)) . " {$oldSession} → " . date('d/m', strtotime($orderDetail->ord_start_day)) . " {$newSession}");
		}

		return Redirect::route('order.index')->with('success', 'Cập nhật thông tin đơn hàng thành công'
			. ($group ? " · nhóm đi chung {$group->code}" : '')
			. ($cancelled ? '. Đơn đổi ngày / buổi chụp nên lịch xe cũ (' . \App\Services\Schedule\ScheduleBoard::carLabel($cancelled['car']) . ', TX ' . ($cancelled['driver'] ?: '—') . ') đã được hủy; đơn chuyển về Chưa lên lịch, cần xếp lại.' : ''));
	}

	/** Người liên hệ (họ tên + SĐT) từng dùng ở các đơn cũ của một đơn vị, mới nhất trước (ô chọn trên form lên đơn) */
	public function customerSuggestions(Request $request)
	{
		$unitId = (int) $request->validate(['unit_id' => ['required', 'integer']])['unit_id'];

		return response()->json(DB::table('orders')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->where('orders.unit_id', $unitId)
			->whereRaw("TRIM(COALESCE(customers.customer_name, '')) <> ''")
			->groupBy('customers.customer_name', 'customers.customer_phone')
			->selectRaw('TRIM(customers.customer_name) as name, TRIM(customers.customer_phone) as phone, MAX(order_details.ord_start_day) as last_day, COUNT(*) as orders')
			->orderByDesc('last_day')
			->limit(30)
			->get()
			->map(fn ($c) => ['name' => $c->name, 'phone' => $c->phone, 'last_day' => $c->last_day, 'orders' => (int) $c->orders])
			->unique(fn ($c) => mb_strtolower($c['name']) . '|' . preg_replace('/\D/', '', (string) $c['phone']))
			->values());
	}

	/** Các đơn cùng đơn vị, cùng ngày (khung "Đi chung với đơn khác" trên form lên đơn) */
	public function groupCandidates(Request $request)
	{
		$data = $request->validate([
			'unit_id' => ['required', 'integer'],
			'date' => ['required', 'date_format:Y-m-d'],
			'exclude' => ['nullable', 'integer'],
		]);

		return response()->json(app(\App\Services\OrderGroups::class)
			->candidates((int) $data['unit_id'], $data['date'], $data['exclude'] ?? null)
			->map(fn ($o) => [
				'id' => $o->id,
				'company' => $o->ord_cty_name,
				'session' => $o->ord_session ?: 'Cả ngày',
				'service' => $o->ord_select,
				'quantity' => (int) $o->order_quantity,
				'surcharge' => (int) $o->order_surcharge === 1,
				'group_id' => $o->order_group_id,
				'group_code' => $o->group_code,
				'car' => $o->car_name ? \App\Services\Schedule\ScheduleBoard::carLabel($o->car_name) . ($o->car_driver_name ? ' · TX ' . $o->car_driver_name : '') : null,
			])->values());
	}

	public function copy($order_id)
	{
		$order = Order::getOneOrder($order_id);
		$files = array_combine(explode(',', $order->ord_list_file), explode(',', $order->ord_list_file_path));
		$getAllUnit = Unit::orderBy('unit_code', 'ASC')->get();
		return view('pages.admin.order.copy', compact('order', 'getAllUnit', 'files'));
	}
	public function storeCopy(OrderRequestForm $request)
	{
		DB::beginTransaction();
		try {
			$data = $request->all();
			// Số ca có thể gõ kèm dấu chấm (1.200): chỉ lấy chữ số
			$data['order_quantity'] = preg_replace('/\D/', '', (string) ($data['order_quantity'] ?? ''));
			$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];
			$customer = new Customer();
			$customer->customer_name = $data['customer_name'];
			$customer->customer_phone = $data['customer_phone'];
			$customer->customer_address = $data['customer_address'];
			$customer->customer_location = $data['customer_location'];
			$customer->save();

			$orderDetail = new OrderDetail();
			$orderDetail->ord_start_day = $data['ord_start_day'];
			$orderDetail->ord_end_day = $data['ord_start_day'];
			$orderDetail->ord_session = $data['ord_session'];
			$orderDetail->ord_select = $data['ord_select'];
			$orderDetail->ord_doctor_read = $data['ord_doctor_read'];
			$orderDetail->ord_film = $data['ord_film'];
			$orderDetail->ord_form = $data['ord_form'];
			$orderDetail->ord_print = $data['ord_print'];
			$orderDetail->ord_form_print = $data['ord_form_print'];
			$orderDetail->ord_print_result = $data['ord_print_result'];
			$orderDetail->ord_film_sheet = $data['ord_film_sheet'];
			$orderDetail->ord_note = $data['ord_note'];
			$orderDetail->ord_deadline = $this->deadlineFunction($data['ord_start_day'], $data['unit_id'], $data['order_quantity']);
			$orderDetail->ord_deliver_results = $data['ord_deliver_results'];
			$orderDetail->ord_cty_name = $data['ord_cty_name'];
			$orderDetail->ord_time = $data['ord_time'];
			$orderDetail->ord_email = $data['ord_email'];
			$get_file = $request->ord_list_file;
			if ($get_file) {
				$name = '';
				$path = '';
				foreach ($get_file as $key => $file) {
					$tmp_file = TempFile::where('filename', $file)->first();
					$name .= $tmp_file->filename . ',';
					$path .= $tmp_file->folder . ',';
					$tmp_file->delete();
				}
				$orderDetail->ord_list_file = substr($name, 0, -1);
				$orderDetail->ord_list_file_path = substr($path, 0, -1);
			} else {
				$orderDetail->ord_list_file = '';
				$orderDetail->ord_list_file_path = '';
			}

			if (in_array($request->ord_select, $ultraSound)) {
				$orderDetail->ord_type = 2;
			} else {
				$orderDetail->ord_type = 1;
			}
			$orderDetail->save();

			$order = new Order();
			$order->customer_id = $customer->id;
			$order->order_detail_id = $orderDetail->id;
			$order->unit_id = $data['unit_id'];
			$order->status_id = 5;
			$order->order_quantity = $data['order_quantity'];
			$order->order_price = formatPrice($data['order_price']);
			$order->order_cost = $data['order_all_in_one'] == 0 ? formatPrice($data['order_cost']) : 0;
			$order->order_all_in_one = $data['order_all_in_one'];
			$order->order_percent_discount = $data['order_percent_discount'];
			$order->order_vat = $data['order_vat'];
			$order->order_discount = 0;
			$order->order_profit = 0;
			$order->schedule_status = 0;
			$order->accountant_updated = 0;
			$order->order_warning = $data['order_warning'];
			$order->order_surcharge = $data['order_surcharge'];
			$order->overnight = $data['overnight'];
			$order->complete = $data['complete'] ?? ($order->complete ?? 0); // nút Cập nhật / Chưa hoàn thành; thiếu thì giữ giá trị cũ
			$order->save();

			$monthFormat = explode("-", $data['ord_start_day']);
			$accountant = new Accountant();
			$accountant->order_id = $order->id;
			$accountant->accountant_owe = formatPrice($data['order_price']);
			$accountant->accountant_distance = $data['accountant_distance'];
			$accountant->accountant_amount_paid = 0;
			if ($monthFormat[1] == 10) {
				$accountant->accountant_month = $monthFormat[1];
			} else {
				$month = explode("0", $monthFormat[1]);
				if (count($month) > 1) {
					$accountant->accountant_month = $month[1];
				} else {
					$accountant->accountant_month = $month[0];
				}
			}
			$accountant->save();

			// Đơn mới: tính Chiết khấu, Lợi nhuận, Còn nợ ngay (cùng công thức trang Công nợ)
			OrderMoney::sync($order, $accountant, OrderMoney::MONEY_FIELDS);

			$history = new HistoryEdit();
			$history->order_id = $order->id;
			$history->user_name = Auth::user()->email;
			$history->history_action = 'Thêm đơn hàng';
			$history->save();
			// Nhóm đi chung: Sale tích các đơn cùng đơn vị, cùng ngày ở form
			$group = $request->filled('group_touched') ? app(\App\Services\OrderGroups::class)->sync($order, (array) $request->input('group_with', [])) : null;
			DB::commit();
			return Redirect::route('order.index')->with('success', 'Thêm đơn hàng thành công' . ($group ? " · nhóm đi chung {$group->code}" : ''));
		} catch (\Exception $e) {
			DB::rollback();
			return Redirect()->back()->withInput()->with('error', 'Thêm đơn hàng thất bại');
		}
	}

	public function destroy($order_id)
	{
		$order = Order::findOrFail($order_id);
		if ($order->isClosed()) {
			return Redirect::route('order.index')->with('error', Order::closedMessage($order_id));
		}
		app(\App\Services\OrderGroups::class)->leave($order);
		$order->delete();
		$customer = Customer::findOrFail($order->customer_id);
		$customer->delete();
		$orderDetail = OrderDetail::findOrFail($order->order_detail_id);
		if ($orderDetail->ord_list_file != '') {
			$files = explode(',', $orderDetail->ord_list_file);
			foreach ($files as $file) {
				deleteImageFileDrive($file);
			}
		}
		$orderDetail->delete();

		return Redirect()->back()->with('success', 'Xóa đơn hàng thành công');
	}

	public function exportExcel(Request $request)
	{
		$filters = $request->all();
		// $year=$request->year;
		// $month=$request->month;
		//$user = Customer::findOrFail($customer_id);
		// return Excel::download(new ExcelExport($customer_id), $user->customer_name.'.xlsx');

		// return Excel::download(new ExcelExport($date), $date.'.xlsx');
		return Excel::download(new ExcelExport($filters), 'Accountant.xlsx');
	}

	public function exportMisa(Request $request)
	{
		$filters = $request->all();

		return Excel::download(new MisaExport($filters), 'Misa.xlsx');
	}


	function weekendPlusDayFunction($dateString, $numberOfDaysAdded)
	{
		$startDay = Carbon::parse($dateString);
		$numberOfWeekends = 0;
		for ($i = 1; $i <= $numberOfDaysAdded; $i++) {
			$ngayKiemTra = $startDay->copy()->addDays($i);
			if ($ngayKiemTra->isWeekend()) {
				$numberOfWeekends++;
			}
		}
		$totalNumberOfDaysAdded = $numberOfDaysAdded + $numberOfWeekends;

		return $startDay->addDays($totalNumberOfDaysAdded)->toDateString();
	}

	public function deadlineFunction($ord_start_day, $unit, $order_quantity)
	{
		$ordStartDay = Carbon::parse($ord_start_day);
		if ($unit == 28 || $unit == 3 || $unit == 87) {
			return $this->weekendPlusDayFunction($ord_start_day, 7);
		}

		if ($ordStartDay->month == '04') {
			$firstDayofThisMonth = $ordStartDay->year . '-04-01';
			$lastDayofThisMonth = $ordStartDay->year . '-04-30';
		} elseif ($ordStartDay->month == '06') {
			$firstDayofThisMonth = $ordStartDay->year . '-06-01';
			$lastDayofThisMonth = $ordStartDay->year . '-06-30';
		} elseif ($ordStartDay->month == '09') {
			$firstDayofThisMonth = $ordStartDay->year . '-09-01';
			$lastDayofThisMonth = $ordStartDay->year . '-00-30';
		} elseif ($ordStartDay->month == '11') {
			$firstDayofThisMonth = $ordStartDay->year . '-11-01';
			$lastDayofThisMonth = $ordStartDay->year . '-11-30';
		} else {
			$firstDayofThisMonth = Carbon::createFromFormat('M Y', $ordStartDay->format('F') . ' ' . $ordStartDay->year)->firstOfMonth()->toDateString();
			$lastDayofThisMonth = Carbon::createFromFormat('M Y', $ordStartDay->format('F') . ' ' . $ordStartDay->year)->endOfMonth()->toDateString();
		}
		$countOrders = Order::getScheduleTechnologist($firstDayofThisMonth, $lastDayofThisMonth)->count();

		if ($order_quantity >= 150 && $countOrders > 40) {
			$deadline = $this->weekendPlusDayFunction($ord_start_day, 7);
		} else {
			$deadline = $this->weekendPlusDayFunction($ord_start_day, 5);
		}
		return $deadline;
	}
}
