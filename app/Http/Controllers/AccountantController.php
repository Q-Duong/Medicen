<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\Statistic;
use App\Models\HistoryEdit;
use App\Models\Accountant;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AccountantController extends Controller
{
	public function update_order_accountant($order_id)
	{
		$order_accountant = Accountant::where('order_id', $order_id)->first();
		return view('admin.Accountant.update_order_accountant')->with(compact('order_accountant'));
	}

	public function save_order_accountant(Request $request, $order_id)
	{
		$data = $request->all();
		$order = Order::find($order_id);
		if ($order->order_status == 4) {
			$orderDetail = OrderDetail::find($order->order_detail_id);
			$orderDetail->ord_cty_name = $data['ord_cty_name'];
			$orderDetail->save();
		} else {
			$order->order_cost = formatPrice($data['order_cost']);
			$order->order_percent_discount =  $data['order_percent_discount'];
			$order->order_vat =  $data['order_vat'];
			$order->order_price = formatPrice($data['order_price']);
			$order->save();
			$orderDetail = OrderDetail::find($order->order_detail_id);
			$orderDetail->ord_cty_name = $data['ord_cty_name'];
			$orderDetail->save();
			$accountant = Accountant::where('order_id', $order_id)->first();
			$accountant->accountant_owe = $order->order_price;
			$accountant->save();
		}
		return Redirect::to('admin/order/list')->with('success', 'Cập nhật thông tin báo cáo thành công');
	}

	public function index()
	{
		if(!Session::has('year')){
			Session::put('year', Carbon::now()->year);
		}
		return view('pages.admin.accountant.index');
	}

	public function getAccountant(Request $request)
	{
		if(isset($request->year) && ! empty($request->year)){
			$year = $request->year;
			Session::put('year', Carbon::now()->year);
		}else{
			$year = Session::get('year');
		}
		Session::put('year', $year);
		$getAllAccountant = Accountant::getAccountantByYear($year);
		$html = view('pages.admin.accountant.render')->with(compact('getAllAccountant'))->render();
		return response()->json(array('success' => true, 'html' => $html));
	}

	public function update(Request $request)
	{
		$data = $request->all();
		$order = Order::findOrFail($data['order_id']);
		$order->order_quantity = $data['order_quantity'];
		$order->order_price = formatPrice($data['order_price']);
		$order->order_cost = $data['order_cost'] != '' ? formatPrice($data['order_cost']) : '';
		$order->order_vat = $data['order_vat'];
		$order->order_percent_discount = $data['order_percent_discount'];
		$order->order_discount = formatPrice($data['order_discount']);
		$order->order_profit = formatPrice($data['order_profit']);
		$order->status_id = 4;
		$order->save();

		$accountant = Accountant::findOrFail($data['accountant_id']);
		$accountant->accountant_deadline = $data['accountant_deadline'];
		$accountant->accountant_number = $data['accountant_number'];
		$accountant->accountant_date = $data['accountant_date'] != '' ? formatDate($data['accountant_date']) : '';
		$accountant->accountant_payment = $data['accountant_payment'] != '' ? formatDate($data['accountant_payment']) : '';
		$accountant->accountant_day_payment = $data['accountant_day_payment'] != '' ? formatDate($data['accountant_day_payment']) : '';
		$accountant->accountant_method = $data['accountant_method'];
		$accountant->accountant_amount_paid = formatPrice($data['accountant_amount_paid']);
		$accountant->accountant_owe = formatPrice($data['accountant_owe']);
		$accountant->accountant_discount_day = $data['accountant_discount_day'] != '' ? formatDate($data['accountant_discount_day']) : '';
		$accountant->accountant_doctor_read = $data['accountant_doctor_read'];
		$accountant->accountant_doctor_date_payment = $data['accountant_doctor_date_payment'] != '' ? formatDate($data['accountant_doctor_date_payment']) : '';
		$accountant->accountant_35X43 = $data['accountant_35X43'];
		$accountant->accountant_polime = $data['accountant_polime'];
		$accountant->accountant_8X10 = $data['accountant_8X10'];
		$accountant->accountant_10X12 = $data['accountant_10X12'];
		$accountant->accountant_film_bag = $data['accountant_film_bag'];
		$accountant->accountant_note = $data['accountant_note'];
		$accountant->save();

		$history = new HistoryEdit();
		$history->order_id = $data['order_id'];
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Cập nhật doanh thu';
		$history->save();

		return response()->json(['success' => 'Đã cập nhật doanh thu thành công.']);
	}

	public function complete(Request $request)
	{
		$data = $request->all();
		$order = Order::find($data['order_id']);
		$order->order_quantity = $data['order_quantity'];
		$order->order_price = formatPrice($data['order_price']);
		$order->order_cost = $data['order_cost'] != '' ? formatPrice($data['order_cost']) : '';
		$order->order_vat = $data['order_vat'];
		$order->order_percent_discount = $data['order_percent_discount'];
		$order->order_discount = formatPrice($data['order_discount']);
		$order->order_profit = formatPrice($data['order_profit']);
		$order->status_id = 3;
		$order->save();

		$order_date = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
		$statistic = Statistic::where('order_date', $order_date)->get();

		if ($statistic) {
			$statistic_count = $statistic->count();
		} else {
			$statistic_count = 0;
		}
		if ($order->status_id == 3 && $order->accountant_updated != 1) {
			$total_order = 1;
			if ($statistic_count > 0) {
				$statistic_update = Statistic::where('order_date', $order_date)->first();
				$statistic_update->sales = (int)($statistic_update->sales + $order->order_price);
				$statistic_update->quantity =  $statistic_update->quantity + $data['order_quantity'];
				$statistic_update->total_order = $statistic_update->total_order + $total_order;
				$statistic_update->total_profit = (int)($statistic_update->total_profit + $order->order_profit);
				$statistic_update->save();
			} else {
				$statistic_new = new Statistic();
				$statistic_new->order_date = $order_date;
				$statistic_new->sales = $order->order_price;
				$statistic_new->quantity =  (int)$data['order_quantity'];
				$statistic_new->total_order = $total_order;
				$statistic_new->total_profit = (int)$order->order_profit;
				$statistic_new->save();
			}
		}
		$order->accountant_updated = 1;
		$order->save();

		$accountant = Accountant::find($data['accountant_id']);
		$accountant->accountant_deadline = $data['accountant_deadline'];
		$accountant->accountant_number = $data['accountant_number'];
		$accountant->accountant_date = $data['accountant_date'] != '' ? formatDate($data['accountant_date']) : '';
		$accountant->accountant_payment = $data['accountant_payment'] != '' ? formatDate($data['accountant_payment']) : '';
		$accountant->accountant_day_payment = $data['accountant_day_payment'] != '' ? formatDate($data['accountant_day_payment']) : '';
		$accountant->accountant_method = $data['accountant_method'];
		$accountant->accountant_amount_paid = formatPrice($data['accountant_amount_paid']);
		$accountant->accountant_owe = formatPrice($data['accountant_owe']);
		$accountant->accountant_discount_day = $data['accountant_discount_day'] != '' ? formatDate($data['accountant_discount_day']) : '';
		$accountant->accountant_doctor_read = $data['accountant_doctor_read'];
		$accountant->accountant_doctor_date_payment = $data['accountant_doctor_date_payment'] != '' ? formatDate($data['accountant_doctor_date_payment']) : '';
		$accountant->accountant_35X43 = $data['accountant_35X43'];
		$accountant->accountant_polime = $data['accountant_polime'];
		$accountant->accountant_8X10 = $data['accountant_8X10'];
		$accountant->accountant_10X12 = $data['accountant_10X12'];
		$accountant->accountant_film_bag = $data['accountant_film_bag'];
		$accountant->accountant_note = $data['accountant_note'];
		$accountant->save();

		$history = new HistoryEdit();
		$history->order_id = $data['order_id'];
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Cập nhật doanh thu';
		$history->save();

		return response()->json(['success' => 'Đơn hàng đã được hoàn tất.']);
	}

	public function filter(Request $request)
	{
		$searchData = $request->all();
		$totalPrice = 0;
		$totalOwe = 0;
		$totalAmountPaid = 0;
		$totalQuantity = 0;
		$totalDiscount = 0;
		$qb = Accountant::getQueryBuilderBySearchData($searchData);
		$result = $qb->get();
		foreach ($result as $key => $val) {
			$totalPrice += $val->order_price;
			$totalOwe += $val->accountant_owe;
			$totalAmountPaid += $val->accountant_amount_paid;
			$totalQuantity += $val->order_quantity;
			$totalDiscount += $val->order_discount;
		}

		return response()->json(array('total_price' => $totalPrice, 'total_owe' => $totalOwe, 'total_amount_paid' => $totalAmountPaid, 'total_quantity' => $totalQuantity, 'total_discount' => $totalDiscount));
	}
	//Validation

	public function checkOrderAdmin(Request $request)
	{
		$this->validate(
			$request,
			[
				'customer_name' => 'required',
				'customer_phone' => 'required|numeric|digits_between:10,10',
				'customer_address' => 'required',
				'ord_cty_name' => 'required',
				'ord_start_day' => 'required',
				'ord_end_day' => 'required',
				'ord_deadline' => 'required',
				'ord_deliver_results' => 'required',
				'ord_time' => 'required',
				'order_quantity' => 'required',
				'order_cost' => 'required',
				'order_price' => 'required',
				'code_unit' => 'required',
			],
			[
				'customer_name.required' => 'Vui lòng điền họ và tên',
				'customer_phone.required' => 'Vui lòng điền số điện thoại',
				'customer_phone.digits_between' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_phone.numeric' => 'Vui lòng kiểm tra lại số điện thoại',
				'customer_address.required' => 'Vui lòng điền địa chỉ',
				'ord_cty_name.required' => 'Vui lòng điền tên công ty',
				'ord_start_day.required' => 'Vui lòng điền ngày bắt đầu',
				'ord_end_day.required' => 'Vui lòng điền ngày kết thúc',
				'ord_deadline.required' => 'Vui lòng điền ngày trả kết quả',
				'ord_deliver_results.required' => 'Vui lòng điền thông tin nhận kết quả',
				'ord_time.required' => 'Vui lòng điền giờ khám',
				'order_quantity.required' => 'Vui lòng điền số lượng chụp',
				'order_cost.required' => 'Vui lòng điền đơn giá',
				'order_price.required' => 'Vui lòng điền tổng tiền',
				'code_unit.required' => 'Vui lòng điền mã đơn vị',
			]
		);
	}
}
