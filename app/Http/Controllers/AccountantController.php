<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\Statistic;
use App\Models\Accountant;
use App\Models\CarKTV;
use App\Services\Accountant\RowCalculator;
use App\Models\HistoryEdit;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AccountantController extends Controller
{
	/** SubAdmin xem trang Công nợ kế toán ở chế độ chỉ xem: khóa mọi ô nhập / nút trong bảng (route lưu cũng bị SubAdminScope chặn) */
	private static function readOnlyHtml(string $html): string
	{
		if (!\App\Services\Claude\AssistantAccess::isSubAdmin(\Illuminate\Support\Facades\Auth::user())) {
			return $html;
		}

		return preg_replace('/<(input|select|textarea|button)\b/i', '<$1 disabled', $html);
	}

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
			SUM(orders.order_quantity_draft) as total_quantity_draft,
			SUM(orders.order_discount) as total_discount,
			SUM(accountants.accountant_35X43) as total_35,
			SUM(accountants.accountant_polime) as total_polime,
			SUM(accountants.accountant_8X10) as total_8,
			SUM(accountants.accountant_10X12) as total_10,
			SUM(accountants.accountant_film_bag) as total_pack,
			SUM(CASE WHEN car_ktvs.driver_assistance = 1 THEN orders.order_quantity_draft ELSE 0 END) as total_driver_assistance
			
		')->first();

		$perPage = 30;
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
				'order_quantity_draft',
				'order_cost',
				'order_price',
				'order_percent_discount',
				'order_discount',
				'order_profit',
				'orders.status_id',
				'car_name',
				'car_ktv_name_1',
				'car_ktv_name_2',
				'car_driver_name',
				'driver_assistance',
				'unit_name',
				'unit_abbreviation',
				'order_groups.code as order_group',
				'orders.qty_changed_from',
				'orders.order_surcharge'
			)
			->cursorPaginate($perPage);
		$sttStart = $request->input('current_count', 0);

		$response = [
			'success'       => true,
			'html'          => self::readOnlyHtml(view('pages.admin.accountant.data_render', ['accountantData' => $data, 'sttStart' => $sttStart])->render()),
			'next_page_url' => $data->nextPageUrl(),
		];

		if ($totals) {
			$response['totals'] = [
				'totalRows'             => $totals->total_rows ?? 0,
				'totalPrice'      		=> $totals->total_price ?? 0,
				'totalOwe'        		=> $totals->total_owe ?? 0,
				'totalAmountPaid' 		=> $totals->total_amount_paid ?? 0,
				'totalQuantity'   		=> $totals->total_quantity ?? 0,
				'totalQuantityDraft'    => $totals->total_quantity_draft ?? 0,
				'totalDriverAssistance' => $totals->total_driver_assistance ?? 0,
				'totalDiscount'   		=> $totals->total_discount ?? 0,
				'total35'         		=> $totals->total_35 ?? 0,
				'totalPolime'     		=> $totals->total_polime ?? 0,
				'total8'          		=> $totals->total_8 ?? 0,
				'total10'         		=> $totals->total_10 ?? 0,
				'totalPack'       		=> $totals->total_pack ?? 0,
			];
			// Số đơn có số ca đổi sau khi cập nhật doanh thu (nút lọc "Số ca thay đổi")
			$response['qty_changed_count'] = self::qtyChangedCount();
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
			'car_ktv_name_1'                    => 'car_ktv_name_1',
			'car_ktv_name_2'                    => 'car_ktv_name_2',
			'car_driver_name'                   => 'car_driver_name',
			'driver_assistance'                 => 'driver_assistance',
			'unit_abbreviation'                 => 'unit_abbreviation',
			'order_group'                       => 'order_groups.code',
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
			'order_quantity_draft'				=> 'order_quantity_draft',
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

	/** Số đơn đang có số ca đổi sau khi cập nhật doanh thu (chờ kế toán), cho nút lọc trên trang Công nợ */
	public static function qtyChangedCount(): int
	{
		return Order::whereNotNull('qty_changed_from')->where('status_id', 4)->count();
	}

	/**
	 * Kế toán xử lý đơn có số ca đổi sau khi cập nhật doanh thu:
	 * - action=apply: tính lại Thành tiền, CK, Lợi nhuận, Còn nợ theo số ca mới (OrderMoney::recalcForQuantity)
	 * - action=keep: giữ tiền cũ
	 * Cả hai đều bỏ đánh dấu và ghi lịch sử.
	 */
	public function qtyChange(Request $request)
	{
		$data = $request->validate(['id' => ['required', 'integer'], 'action' => ['required', 'in:apply,keep']]);
		$accountant = Accountant::find($data['id']);
		$order = $accountant?->order;
		if (!$order) {
			return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn.']);
		}
		if ((int) $order->status_id === 3) {
			return response()->json(['success' => false, 'message' => 'Đơn #' . $order->id . ' đã chốt, không sửa được nữa.']);
		}
		if ($order->qty_changed_from === null) {
			return response()->json(['success' => false, 'cleared' => true, 'message' => 'Đơn #' . $order->id . ' không còn cảnh báo số ca (có thể người khác vừa xử lý).']);
		}

		$from = (int) $order->qty_changed_from;
		$to = (int) $order->order_quantity;
		$fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
		DB::beginTransaction();
		try {
			if ($data['action'] === 'apply') {
				$res = \App\Services\Accountant\OrderMoney::recalcForQuantity($order, $accountant);
				$text = 'Kế toán tính lại tiền theo số ca ' . $from . ' → ' . $to . ': thành tiền ' . $fmt($res['price_before']) . ' → ' . $fmt($res['price_after']);
			} else {
				\App\Services\Accountant\OrderMoney::clearQtyFlag($order);
				$order->save();
				$text = 'Kế toán giữ tiền cũ khi số ca đổi ' . $from . ' → ' . $to;
			}
			$history = new HistoryEdit();
			$history->order_id = $order->id;
			$history->user_name = Auth::user()->email;
			$history->history_action = mb_substr($text, 0, 100);
			$history->save();
			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'message' => 'Lỗi xử lý đơn #' . $order->id . ': ' . $e->getMessage()]);
		}

		$order->refresh();
		$accountant->refresh();
		$response = [
			'success' => true,
			'action' => $data['action'],
			'message' => $text,
			'values' => [
				'order_quantity' => $fmt($order->order_quantity),
				'order_price' => $fmt($order->order_price),
				'order_discount' => $fmt($order->order_discount),
				'order_profit' => $fmt($order->order_profit),
				'accountant_owe' => $fmt($accountant->accountant_owe),
			],
			'qty_changed_count' => self::qtyChangedCount(),
		];
		$totals = $this->totalsForFilter($request->input('filter_data'));
		if ($totals) {
			$response['totals'] = $totals;
		}

		return response()->json($response);
	}

	public function updateRow(Request $request)
	{
		// Trang công nợ Kế toán: lưu xong thì đơn chuyển "Đã cập nhật doanh thu"
		return $this->saveAccountantRow($request, true);
	}

	/**
	 * Lưu một dòng công nợ (tự lưu khi nhập từng ô). Dùng chung cho trang Kế toán và trang Sales.
	 *
	 * - Trang chỉ gửi các ô ĐÃ ĐỔI (row_data), kèm giá trị cũ của chúng (original) và tên các ô người dùng
	 *   tự sửa (edited). Không gửi cả dòng nữa, để người đang mở trang cũ không ghi đè dữ liệu người khác vừa nhập.
	 * - Nếu một ô người dùng sửa đã bị người khác đổi trong lúc đó (giá trị trong DB khác giá trị cũ trang đang có),
	 *   không lưu dòng đó, trả về "conflicts" kèm giá trị mới nhất để trang hiện lại và báo người dùng nhập lại.
	 * - Tổng tiền tính SAU khi đã lưu xong (lỗi khi tính tổng không làm mất dữ liệu vừa lưu).
	 */
	private function saveAccountantRow(Request $request, bool $setRevenueStatus)
	{
		$response = $this->saveRowData($request->all(), $setRevenueStatus);
		if (!empty($response['success'])) {
			$totals = $this->totalsForFilter($request->filter_data);
			if ($totals) {
				$response['totals'] = $totals;
			}
		}

		return response()->json($response);
	}

	/**
	 * Lưu nhiều dòng công nợ một lần (dán từ Excel, xóa cả vùng ở trang Kế toán). Mỗi dòng lưu và kiểm tra
	 * trùng sửa riêng như khi lưu từng dòng (một dòng lỗi không làm hỏng các dòng khác); tổng tiền tính một lần.
	 * Trang gửi JSON: rows = [{id, row_data, original, edited}], client = 2, filter_data.
	 */
	public function updateRows(Request $request)
	{
		$rows = is_array($request->input('rows')) ? $request->input('rows') : [];
		if (count($rows) > 300) {
			return response()->json(['success' => false, 'message' => 'Mỗi lần lưu tối đa 300 dòng.'], 422);
		}

		$results = [];
		foreach ($rows as $row) {
			$id = (string) ($row['id'] ?? '');
			if ($id === '') {
				continue;
			}
			$results[$id] = $this->saveRowData(array_merge($row, ['client' => 2]), true);
		}

		$response = ['success' => true, 'results' => $results];
		if (collect($results)->contains(fn ($r) => !empty($r['success']))) {
			$totals = $this->totalsForFilter($request->input('filter_data'));
			if ($totals) {
				$response['totals'] = $totals;
			}
		}

		return response()->json($response);
	}

	/**
	 * Trợ lý AI sửa giá (App\Services\Accounting\ContractPriceAudit): lưu các ô như kế toán tự sửa trên trang Công nợ
	 * (cùng kiểm tra đơn đã chốt, tính lại các ô liên kết, chuyển "Đã cập nhật doanh thu", ghi nhật ký).
	 */
	public function saveRowValues(Accountant $accountant, array $values): array
	{
		$order = $accountant->order;
		$current = fn ($c) => in_array($c, $accountant->getFillable()) ? $accountant->getRawOriginal($c) : ($order ? $order->getRawOriginal($c) : null);
		$rows = [];
		$original = [];
		foreach ($values as $name => $value) {
			$rows[] = ['name' => $name, 'value' => (string) $value];
			$original[] = ['name' => $name, 'value' => (string) $current($name)];
		}

		return $this->saveRowData(['id' => $accountant->id, 'row_data' => $rows, 'original' => $original, 'edited' => array_keys($values), 'client' => 2], true);
	}

	/** Nhãn cột trang Công nợ cho nhật ký thao tác */
	private const DEBT_LABELS = [
		'order_quantity' => 'Số ca', 'order_cost' => 'Đơn giá', 'order_price' => 'Thành tiền', 'order_vat' => 'VAT',
		'order_percent_discount' => '% chiết khấu', 'order_discount' => 'Chiết khấu', 'order_profit' => 'Lợi nhuận',
		'accountant_amount_paid' => 'Đã thanh toán', 'accountant_owe' => 'Còn nợ', 'accountant_day_payment' => 'Ngày thanh toán',
		'accountant_payment' => 'Trạng thái thanh toán', 'accountant_method' => 'Hình thức TT', 'accountant_number' => 'Số HĐ',
		'accountant_date' => 'Ngày HĐ', 'accountant_deadline' => 'Hạn thanh toán', 'accountant_distance' => 'Khoảng cách',
		'accountant_note' => 'Ghi chú', 'accountant_doctor_read' => 'Bác sĩ đọc', 'accountant_doctor_date_payment' => 'Ngày TT bác sĩ',
		'accountant_35X43' => '35x43', 'accountant_polime' => 'Polime', 'accountant_8X10' => '8x10', 'accountant_10X12' => '10x12',
		'accountant_film_bag' => 'Túi phim', 'accountant_month' => 'Tháng', 'accountant_discount_day' => 'Ngày CK',
	];

	private function logDebtChange(Accountant $accountant, array $before, array $after): void
	{
		$changes = [];
		foreach ($after as $column => $new) {
			$old = $before[$column] ?? null;
			if ((string) $old === (string) $new || (is_numeric($old) && is_numeric($new) && (float) $old == (float) $new)) {
				continue;
			}
			$changes[self::DEBT_LABELS[$column] ?? $column] = [$old, $new];
		}
		if ($changes) {
			$unit = optional(optional($accountant->order)->unit)->unit_abbreviation;
			\App\Models\ActivityLog::record('cong_no', 'sua', 'Sửa công nợ đơn #' . $accountant->order_id . ($unit ? ' (' . $unit . ')' : ''), $accountant, $changes);
		}
	}

	private function saveRowData(array $in, bool $setRevenueStatus): array
	{
		$accountant = Accountant::find($in['id'] ?? null);
		if (!$accountant) {
			return ['success' => false, 'message' => 'Không tìm thấy dòng công nợ này (có thể đã bị xóa).'];
		}

		$order = $accountant->order;
		if ($order && $order->status_id == 3) {
			return [
				'success' => false,
				'message' => 'Đơn #' . $accountant->order_id . ' đã chốt, không thể chỉnh sửa!'
			];
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

		// Làm sạch giá trị từ ô nhập: tiền bỏ dấu chấm, ngày d/m/Y -> Y-m-d
		$clean = function (string $column, $value) use ($moneyFields, $dateFields) {
			if (in_array($column, $moneyFields)) {
				$value = preg_replace('/[^0-9]/', '', (string) $value);
				return $value === '' ? 0 : $value;
			}
			if (in_array($column, $dateFields) && $value && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
				try {
					return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
				} catch (\Exception $e) {
					return null;
				}
			}
			return $value;
		};
		// So sánh giá trị: tiền theo số, ngày theo Y-m-d, chữ bỏ khoảng trắng; null = rỗng
		$same = function (string $column, $a, $b) use ($moneyFields, $dateFields) {
			if (in_array($column, $moneyFields)) {
				return (int) preg_replace('/[^0-9]/', '', (string) $a) === (int) preg_replace('/[^0-9]/', '', (string) $b);
			}
			if (in_array($column, $dateFields)) {
				return substr(trim((string) $a), 0, 10) === substr(trim((string) $b), 0, 10);
			}
			return trim((string) $a) === trim((string) $b);
		};
		// Giá trị hiển thị lại trên ô (khi bị trùng sửa)
		$display = function (string $column, $value) use ($moneyFields, $dateFields) {
			if ($value === null || $value === '') {
				return '';
			}
			if (in_array($column, $moneyFields)) {
				return number_format((float) $value, 0, ',', '.');
			}
			if (in_array($column, $dateFields) && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $value)) {
				return Carbon::parse($value)->format('d/m/Y');
			}
			return (string) $value;
		};

		$fillableAccountant = $accountant->getFillable();
		$fillableOrder      = $order ? $order->getFillable() : [];
		$current = fn (string $column) => in_array($column, $fillableAccountant)
			? $accountant->getRawOriginal($column)
			: ($order ? $order->getRawOriginal($column) : null);

		$formData = is_array($in['row_data'] ?? null) ? $in['row_data'] : [];
		$original = collect(is_array($in['original'] ?? null) ? $in['original'] : [])
			->mapWithKeys(fn ($i) => [$i['name'] ?? '' => $i['value'] ?? null]);
		$edited = array_flip(is_array($in['edited'] ?? null) ? $in['edited'] : []);
		$client = (int) ($in['client'] ?? 0);

		$dataForAccountant = [];
		$dataForOrder      = [];
		$conflicts         = [];

		foreach ($formData as $item) {
			$column = $item['name'] ?? '';
			if ($column === '' || $column == '_token') {
				continue;
			}
			$value = $clean($column, $item['value'] ?? null);

			// Ô người dùng tự sửa nhưng người khác đã đổi trước đó: không ghi đè
			if (isset($edited[$column]) && $original->has($column)
				&& !$same($column, $clean($column, $original[$column]), $current($column))) {
				$conflicts[] = ['name' => $column, 'value' => $display($column, $current($column))];
				continue;
			}

			if (in_array($column, $fillableAccountant)) {
				$dataForAccountant[$column] = $value;
			}
			if ($order && in_array($column, $fillableOrder)) {
				$dataForOrder[$column] = $value;
			}
		}

		// Ngày nhập sai định dạng: báo lỗi, không lưu ngày rác vào database
		foreach ($formData as $item) {
			$column = $item['name'] ?? '';
			$raw = trim((string) ($item['value'] ?? ''));
			// Chỉ chặn ô có chữ cái, hoặc trông như MỘT ngày nhưng không có thật (vd 32/13/2026).
			// Cách ghi gộp nhiều lần thanh toán đang dùng (vd "2025-04-05+25", "20+21/11/2025") vẫn cho lưu.
			$looksSingleDate = preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$|^\d{4}-\d{2}-\d{2}$/', $raw);
			if (in_array($column, $dateFields) && $raw !== ''
				&& (preg_match('/\p{L}/u', $raw) || ($looksSingleDate && !RowCalculator::parseDate($raw)))) {
				return [
					'success' => false,
					'message' => 'Ngày "' . $raw . '" ở đơn #' . $accountant->order_id . ' không đúng định dạng ngày/tháng/năm (vd 05/11/2026), chưa lưu.',
				];
			}
		}

		if ($conflicts) {
			// Trả về giá trị mới nhất của mọi ô trang gửi lên để hiện lại đúng dữ liệu đang có
			$fresh = collect($formData)->map(fn ($i) => ['name' => $i['name'], 'value' => $display($i['name'], $current($i['name']))])->values();
			Log::info('Công nợ: trùng sửa, không lưu', ['accountant' => $accountant->id, 'conflicts' => array_column($conflicts, 'name'), 'by' => Auth::id()]);

			return [
				'success'   => false,
				'conflict'  => true,
				'conflicts' => $conflicts,
				'fresh'     => $fresh,
				'message'   => 'Dòng đơn #' . $accountant->order_id . ' vừa được người khác sửa (' . implode(', ', array_column($conflicts, 'name')) . '). Đã hiện lại dữ liệu mới nhất, vui lòng nhập lại.',
			];
		}

		// Trang mới (client=2): máy chủ tự tính lại các ô liên kết (Thành tiền, CK, Lợi nhuận, Còn nợ, Ngày TT,
		// Trạng thái TT) từ số mới nhất trong database + các ô người dùng vừa sửa, không dùng số trang tự tính
		// (trang mở lâu có thể đang giữ số cũ). Trang cũ (chưa tải lại JS) thì lưu như trước.
		$computed = null;
		if ($client === 2) {
			$editedNames = array_keys($edited);
			foreach (RowCalculator::DERIVED as $column) {
				if (!isset($edited[$column])) {
					unset($dataForAccountant[$column], $dataForOrder[$column]);
				}
			}
			$values = [];
			foreach (array_merge($fillableAccountant, $fillableOrder) as $column) {
				$values[$column] = $current($column);
			}
			$values = array_merge($values, $dataForAccountant, $dataForOrder);
			$computed = RowCalculator::compute($values, $editedNames);
			foreach ($computed as $column => $value) {
				if ((string) $value === (string) $current($column) || ($value === null && ($current($column) === null || $current($column) === ''))) {
					continue;
				}
				if (in_array($column, $fillableAccountant)) {
					$dataForAccountant[$column] = $value;
				} elseif ($order && in_array($column, $fillableOrder)) {
					$dataForOrder[$column] = $value;
				}
			}
		}

		$revenueFields = ['order_quantity', 'order_cost', 'order_price', 'order_vat', 'order_percent_discount', 'order_discount', 'accountant_amount_paid'];
		$touchedNames = $client === 2
			? array_keys($edited)
			: array_column($formData, 'name');
		$revenueTouched = (bool) array_intersect($revenueFields, $touchedNames)
			&& (bool) array_intersect(array_keys($dataForAccountant + $dataForOrder), array_merge($revenueFields, ['order_profit', 'accountant_owe']));

		// Giá trị trước khi lưu, để ghi nhật ký thao tác (Lịch sử chỉnh sửa → tab Kế toán)
		$before = [];
		foreach (array_keys($dataForAccountant + $dataForOrder) as $column) {
			$before[$column] = $current($column);
		}

		DB::beginTransaction();
		try {
			if (!empty($dataForAccountant)) {
				$accountant->update($dataForAccountant);
			}

			if ($order) {
				// Kế toán tự sửa số ca / thành tiền ở trang Công nợ = đã xử lý cảnh báo "số ca đổi sau khi cập nhật doanh thu"
				// (cột đánh dấu không nằm trong $fillable nên trang không gửi lên sửa được; ở đây gán thẳng)
				$clearQtyFlag = $order->qty_changed_from !== null && array_intersect(array_keys($dataForOrder), ['order_quantity', 'order_price', 'order_cost']);
				if (!empty($dataForOrder)) {
					$order->update($dataForOrder);
				}
				if ($clearQtyFlag) {
					$order->forceFill(['qty_changed_from' => null, 'qty_changed_at' => null, 'qty_changed_by' => null])->save();
				}
				// Chỉ chuyển "Đã cập nhật doanh thu" khi người dùng sửa ô doanh thu (SL, đơn giá, thành tiền, VAT,
				// chiết khấu, đã thanh toán). Sửa Số HĐ, ghi chú, ngày… thì giữ trạng thái đơn
				// (trạng thái 2/3/4 được tính công tài xế ở Chấm công, không được đổi oan).
				if ($setRevenueStatus && $revenueTouched && $order->status_id != 3 && $order->status_id != 4) {
					$order->status_id = 4;
					$order->save();
				}
			}
			DB::commit();
			$this->logDebtChange($accountant, $before, $dataForAccountant + $dataForOrder);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::warning('Công nợ: lưu dòng lỗi', ['accountant' => $accountant->id, 'data' => $dataForAccountant + $dataForOrder, 'error' => $e->getMessage()]);

			return [
				'success' => false,
				'message' => 'Lỗi lưu dữ liệu đơn #' . $accountant->order_id . ': ' . $e->getMessage()
			];
		}

		$response = ['success' => true, 'saved' => array_keys($dataForAccountant + $dataForOrder),
			// Trang chỉ đổi nhãn trạng thái đơn khi đơn thật sự ở "Đã cập nhật doanh thu"
			'order_status' => $order ? (int) $order->status_id : null];
		if ($computed !== null) {
			// Giá trị các ô liên kết sau khi máy chủ tính, để trang hiện đúng
			$response['computed'] = collect($computed)->map(fn ($v, $k) => $k === 'accountant_status' ? (string) (int) $v : $display($k, $v))->all();
		}

		return $response;
	}

	/** Tổng theo bộ lọc đang dùng, tính SAU khi đã lưu (lỗi ở đây không ảnh hưởng dữ liệu vừa lưu) */
	private function totalsForFilter($filterData): ?array
	{
		try {
			$filters = [];
			if (is_array($filterData)) {
				foreach ($filterData as $item) {
					$filters[$item['name']] = $item['value'];
				}
			}

			$totals = Accountant::getAccountantByFilter($filters)->selectRaw('
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
				return [
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
		} catch (\Exception $e) {
			Log::warning('Công nợ: tính tổng sau khi lưu lỗi', ['error' => $e->getMessage()]);
		}

		return null;
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
			// 1. Tìm đơn hàng, KHÓA dòng tới hết giao dịch: bấm 2 lần / 2 tab cùng chốt thì lần sau phải đợi lần
			//    trước xong và thấy đơn đã chốt (trước đây cả hai cùng đọc "chưa chốt" nên doanh thu bị cộng 2 lần)
			$orderId = $request->order_id;
			$order = Order::lockForUpdate()->find($orderId);

			if (!$order) {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng!']);
			}

			// Kiểm tra nếu đơn đã chốt rồi thì thôi (tránh cộng dồn doanh thu nhiều lần)
			if ($order->status_id == 3) {
				DB::rollBack();
				return response()->json(['success' => false, 'already' => true, 'message' => 'Đơn #' . $order->id . ' đã được chốt trước đó rồi!']);
			}

			// 2. CẬP NHẬT TRẠNG THÁI
			$order->status_id = 3;

			// 3. CẬP NHẬT THỐNG KÊ (LOGIC BẠN YÊU CẦU)
			// LƯU Ý: $order->ord_start_day không có trên bảng orders nên luôn lấy NGÀY CHỐT (hôm nay).
			// Kế toán yêu cầu giữ nguyên cách này (thống kê doanh thu theo ngày chốt), không sửa.
			// Chỉ cộng dồn nếu chưa được cộng (accountant_updated != 1)
			if ($order->accountant_updated != 1) {

				// Lấy ngày đơn hàng (Giả sử cột ord_start_day là ngày tính doanh thu)
				// Nếu không có ord_start_day thì dùng created_at hoặc today()
				$order_date = $order->ord_start_day ? \Carbon\Carbon::parse($order->ord_start_day)->format('Y-m-d') : date('Y-m-d');

				// Khóa dòng thống kê của ngày đó: hai đơn cùng ngày chốt cùng lúc không ghi đè số của nhau
				$statistic = Statistic::where('order_date', $order_date)->lockForUpdate()->first();

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
		if ($msg = Order::closedMessage($order_id)) {
			return Redirect::route('order.index')->with('error', $msg);
		}
		$accountant = Accountant::getAccountantForUpdateOrder($order_id);
		return view('pages.admin.accountant.sales.edit', compact('accountant'));
	}

	public function storeOrder(Request $request, $order_id)
	{
		if ($msg = Order::closedMessage($order_id)) {
			return Redirect::route('order.index')->with('error', $msg);
		}
		$order = Order::findOrFail($order_id);
		$orderDetail = OrderDetail::findOrFail($order->order_detail_id);
		$carKTV = CarKTV::firstWhere('order_id', $order_id);
		$carKTV->driver_assistance =  $request->driver_assistance;
		$carKTV->save();

		if ($order->order_status == 4) {
			// if (Carbon::parse($orderDetail->ord_start_day)->month == 4) {
			// $orderDetail->ord_cty_name = empty($request->ord_cty_name) ? $orderDetail->ord_cty_name : $request->ord_cty_name;
			// $orderDetail->save();
			// }
		} else {
			$order->order_all_in_one = $request->order_all_in_one;
			$order->overnight =  $request->overnight;
			$order->save();
			// if (Carbon::parse($orderDetail->ord_start_day)->month == 4) {
			// 	$orderDetail->ord_cty_name = empty($request->ord_cty_name) ? $orderDetail->ord_cty_name : $request->ord_cty_name;
			// 	$orderDetail->save();
			// }
			// $orderDetail->ord_cty_name = empty($request->ord_cty_name) ? $orderDetail->ord_cty_name : $request->ord_cty_name;
			// $orderDetail->save();
			$accountant = Accountant::where('order_id', $order_id)->first();
			$accountant->accountant_distance = $request->accountant_distance;
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
			SUM(orders.order_quantity_draft) as total_quantity_draft,
			SUM(orders.order_discount) as total_discount,
			SUM(accountants.accountant_35X43) as total_35,
			SUM(accountants.accountant_polime) as total_polime,
			SUM(accountants.accountant_8X10) as total_8,
			SUM(accountants.accountant_10X12) as total_10,
			SUM(accountants.accountant_film_bag) as total_pack,
			SUM(CASE WHEN car_ktvs.driver_assistance = 1 THEN orders.order_quantity_draft ELSE 0 END) as total_driver_assistance
			
		')->first();

		$perPage = 30;
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
				'order_quantity_draft',
				'order_cost',
				'order_price',
				'order_percent_discount',
				'order_discount',
				'order_profit',
				'orders.status_id',
				'car_name',
				'car_ktv_name_1',
				'car_ktv_name_2',
				'car_driver_name',
				'driver_assistance',
				'unit_name',
				'unit_abbreviation',
				'order_groups.code as order_group',
				'orders.qty_changed_from',
				'orders.order_surcharge'
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
				'totalRows'             => $totals->total_rows ?? 0,
				'totalPrice'      		=> $totals->total_price ?? 0,
				'totalOwe'        		=> $totals->total_owe ?? 0,
				'totalAmountPaid' 		=> $totals->total_amount_paid ?? 0,
				'totalQuantity'   		=> $totals->total_quantity ?? 0,
				'totalQuantityDraft'    => $totals->total_quantity_draft ?? 0,
				'totalDriverAssistance' => $totals->total_driver_assistance ?? 0,
				'totalDiscount'   		=> $totals->total_discount ?? 0,
				'total35'         		=> $totals->total_35 ?? 0,
				'totalPolime'     		=> $totals->total_polime ?? 0,
				'total8'          		=> $totals->total_8 ?? 0,
				'total10'         		=> $totals->total_10 ?? 0,
				'totalPack'       		=> $totals->total_pack ?? 0,
			];
		}
		return response()->json($response);
	}

	public function updateRowSales(Request $request)
	{
		// Trang công nợ Sales chỉ để xem và lọc, không cho sửa (Kế toán nhập ở trang công nợ Kế toán).
		// Giữ đường dẫn để trang Sales đang mở bản cũ nhận thông báo rõ ràng thay vì lỗi.
		return response()->json([
			'success' => false,
			'message' => 'Trang công nợ Sales chỉ để xem, không sửa được. Vui lòng báo Kế toán cập nhật.',
		], 403);
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

		// Bộ phận Kết quả không xử lý đơn phụ thu chưa xếp xe (không có phim / kết quả)
		$params['only_scheduled'] = 1;
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

		$perPage = 30;
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
