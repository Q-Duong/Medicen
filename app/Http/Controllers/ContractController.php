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
use Illuminate\Support\Facades\Session;

class ContractController extends Controller
{
	public function index()
	{
		if (!Session::has('year')) {
			Session::put('year', Carbon::now()->year);
		}
		return view('pages.admin.contract.index');
	}

	public function getContract(Request $request)
	{
		if (isset($request->year) && !empty($request->year)) {
			$year = $request->year;
		} else {
			$year = Session::has('year') ? Session::get('year') : Carbon::now()->year;
		}
		Session::put('year', $year);
		$getAllContract = Accountant::getAllContractByFilter(2024);
		// $orderId = $getAllContract->pluck('order_id')->unique()->sort();
		// $unitNames = $getAllAccountant->pluck('unit_name')->unique()->sort();
		// $accNumbers = $getAllAccountant->where('accountant_number', '!=', null)->pluck('accountant_number')->unique()->sort();
		// $accDates = $getAllAccountant->where('accountant_date', '!=', null)->pluck('accountant_date')->unique()->sort();
		// ->pluck('accountant_number')->unique()->sort()
// dd($getAllContract);
		$html = view('pages.admin.contract.render_renew')->with(compact('getAllContract'))->render();
		return response()->json(array('success' => true, 'html' => $html));
	}

	public function update(Request $request)
	{
		$data = $request->all();
		$paramsDeny = ['accountant_status', 'accountant_method', 'accountant_doctor_read'];
		$subParams = ['order_quantity', 'order_cost', 'accountant_amount_paid', 'order_discount'];
		$currentChange = $data['currentChange'];

		$order = Order::findOrFail($data['order_id']);
		if ($order->status_id != 3) {
			$order->order_quantity = $data['order_quantity'];
			$order->order_price = formatPrice($data['order_price']);
			$order->order_cost = empty($data['order_cost']) ? 0 : formatPrice($data['order_cost']);
			$order->order_vat = empty($data['order_vat']) ? null : $data['order_vat'];
			$order->order_percent_discount = empty($data['order_percent_discount']) ? null : $data['order_percent_discount'];
			$order->order_discount = formatPrice($data['order_discount']);
			$order->order_profit = formatPrice($data['order_profit']);
			$order->status_id = 4;
			$order->save();

			$accountant = Accountant::findOrFail($data['accountant_id']);
			$accountant->accountant_deadline = empty($data['accountant_deadline']) ? null : $data['accountant_deadline'];
			$accountant->accountant_number = empty($data['accountant_number']) ? null : $data['accountant_number'];
			$accountant->accountant_date = empty($data['accountant_date']) ? null : formatDate($data['accountant_date']);
			$accountant->accountant_day_payment = empty($data['accountant_day_payment']) ? null : formatDate($data['accountant_day_payment']);
			$accountant->accountant_method = empty($data['accountant_method']) ? null : $data['accountant_method'];
			$accountant->accountant_amount_paid = formatPrice($data['accountant_amount_paid']);
			$accountant->accountant_owe = formatPrice($data['accountant_owe']);
			$accountant->accountant_discount_day = empty($data['accountant_discount_day']) ? null : formatDate($data['accountant_discount_day']);
			$accountant->accountant_doctor_read = empty($data['accountant_doctor_read']) ? null : $data['accountant_doctor_read'];
			$accountant->accountant_doctor_date_payment = empty($data['accountant_doctor_date_payment']) ? null : formatDate($data['accountant_doctor_date_payment']);
			$accountant->accountant_35X43 = empty($data['accountant_35X43']) ? null : $data['accountant_35X43'];
			$accountant->accountant_polime = empty($data['accountant_polime']) ? null : $data['accountant_polime'];
			$accountant->accountant_8X10 = empty($data['accountant_8X10']) ? null : $data['accountant_8X10'];
			$accountant->accountant_10X12 = empty($data['accountant_10X12']) ? null : $data['accountant_10X12'];
			$accountant->accountant_film_bag = empty($data['accountant_film_bag']) ? null : $data['accountant_film_bag'];
			$accountant->accountant_note = empty($data['accountant_note']) ? null : $data['accountant_note'];
			$accountant->accountant_status = $data['accountant_status'];
			$accountant->save();

			if (!in_array($currentChange, $paramsDeny)) {
				$year = Session::get('year');
				if (in_array($currentChange, $subParams)) {
					switch ($currentChange) {
						case 'order_quantity':
							$subFilters = Accountant::renewFilterWhenUpdated('order_price', $year);
							$subCurrentChange = 'order_price';
							break;
						case 'order_cost':
							$subFilters = Accountant::renewFilterWhenUpdated('order_price', $year);
							$subCurrentChange = 'order_price';
							break;
						case 'accountant_amount_paid':
							$subFilters = Accountant::renewFilterWhenUpdated('accountant_owe', $year);
							$subCurrentChange = 'accountant_owe';
							break;
						case 'order_discount':
							$subFilters = Accountant::renewFilterWhenUpdated('order_profit', $year);
							$subCurrentChange = 'order_profit';
							break;
					}
					$filters = Accountant::renewFilterWhenUpdated($currentChange, $year);

					$html = view('pages.admin.accountant.filter_render')->with(compact('filters', 'currentChange'))->render();
					$subHtml = view('pages.admin.accountant.sub_filter_render')->with(compact('subFilters', 'subCurrentChange'))->render();
					$className = str_replace('_', '-', $currentChange);
					$subClassName = str_replace('_', '-', $subCurrentChange);
					return response()->json(array('success' => true, 'multi' => true, 'html' => $html, 'className' => $className, 'subHtml' => $subHtml, 'subClassName' => $subClassName));
				}
				$filters = Accountant::renewFilterWhenUpdated($currentChange, $year);
				$className = str_replace('_', '-', $currentChange);
				$html = view('pages.admin.accountant.filter_render')->with(compact('filters', 'currentChange'))->render();
				return response()->json(array('success' => true, 'multi' => false, 'html' => $html, 'className' => $className));
			}
			return response()->json(array('success' => true));
		}
	}

	public function complete(Request $request)
	{
		$data = $request->all();
		$order = Order::find($data['order_id']);
		$order->order_quantity = $data['order_quantity'];
		$order->order_price = formatPrice($data['order_price']);
		$order->order_cost = empty($data['order_cost']) ? 0 : formatPrice($data['order_cost']);
		$order->order_vat = empty($data['order_vat']) ? null : $data['order_vat'];
		$order->order_percent_discount = empty($data['order_percent_discount']) ? null : $data['order_percent_discount'];
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

		$accountant = Accountant::findOrFail($data['accountant_id']);
		$accountant->accountant_deadline = empty($data['accountant_deadline']) ? null : $data['accountant_deadline'];
		$accountant->accountant_number = empty($data['accountant_number']) ? null : $data['accountant_number'];
		$accountant->accountant_date = empty($data['accountant_date']) ? null : formatDate($data['accountant_date']);
		$accountant->accountant_day_payment = empty($data['accountant_day_payment']) ? null : formatDate($data['accountant_day_payment']);
		$accountant->accountant_method = empty($data['accountant_method']) ? null : $data['accountant_method'];
		$accountant->accountant_amount_paid = formatPrice($data['accountant_amount_paid']);
		$accountant->accountant_owe = formatPrice($data['accountant_owe']);
		$accountant->accountant_discount_day = empty($data['accountant_discount_day']) ? null : formatDate($data['accountant_discount_day']);
		$accountant->accountant_doctor_read = empty($data['accountant_doctor_read']) ? null : $data['accountant_doctor_read'];
		$accountant->accountant_doctor_date_payment = empty($data['accountant_doctor_date_payment']) ? null : formatDate($data['accountant_doctor_date_payment']);
		$accountant->accountant_35X43 = empty($data['accountant_35X43']) ? null : $data['accountant_35X43'];
		$accountant->accountant_polime = empty($data['accountant_polime']) ? null : $data['accountant_polime'];
		$accountant->accountant_8X10 = empty($data['accountant_8X10']) ? null : $data['accountant_8X10'];
		$accountant->accountant_10X12 = empty($data['accountant_10X12']) ? null : $data['accountant_10X12'];
		$accountant->accountant_film_bag = empty($data['accountant_film_bag']) ? null : $data['accountant_film_bag'];
		$accountant->accountant_note = empty($data['accountant_note']) ? null : $data['accountant_note'];
		$accountant->accountant_status = $data['accountant_status'];
		$accountant->save();

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
		$total35 = 0;
		$totalPolime = 0;
		$total8 = 0;
		$total10 = 0;
		$totalPack = 0;
		$flagEmpty = false;
		$year = Session::get('year');
		$type = Session::get('type');
		$qb = Accountant::getQueryBuilderBySearchData($searchData, $year, $type);
		$getAllAccountant = $qb->get();
		
		foreach ($getAllAccountant as $key => $val) {
			$totalPrice += $val->order_price;
			$totalOwe += $val->accountant_owe;
			$totalAmountPaid += $val->accountant_amount_paid;
			$totalQuantity += $val->order_quantity;
			$totalDiscount += $val->order_discount;
			$total35 += $val->accountant_35X43;
			$totalPolime += $val->accountant_polime;
			$total8 += $val->accountant_8X10;
			$total10 += $val->accountant_10X12;
			$totalPack += $val->accountant_film_bag;
		}

		if (empty(array_filter($searchData))) {
			$flagEmpty = true;
		} 
		$html = view('pages.admin.accountant.filter_index')->with(compact('getAllAccountant'))->render();
		return response()->json(array('html' => $html, 'totalPrice' => $totalPrice, 'totalOwe' => $totalOwe, 'totalAmountPaid' => $totalAmountPaid, 'totalQuantity' => $totalQuantity, 'totalDiscount' => $totalDiscount, 'total35' => $total35, 'totalPolime' => $totalPolime, 'total8' => $total8, 'total10' => $total10, 'totalPack' => $totalPack, 'flagEmpty' => $flagEmpty));
	}
}
