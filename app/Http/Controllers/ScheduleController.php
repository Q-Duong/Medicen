<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\CarKTV;
use App\Models\Accountant;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
	public function show_schedule()
	{
		$month = [];
		$firstDayofThisMonth = Carbon::now()->startOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::now()->endOfMonth()->toDateString();
		$currentYear = Carbon::now()->format('Y');
		$currentMonth = Carbon::now()->format('F');
		$dayInMonth = Carbon::now()->daysInMonth;
		$order = Order::join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->join('unit', 'orders.unit_id', '=', 'unit.unit_id')
			->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
			->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			// ->orderBy('orders.created_at', 'DESC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		for ($m = 1; $m <= 12; $m++) {
			$month[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.schedule.schedule.view_schedule')->with(compact('order', 'month', 'dayInMonth', 'currentMonth', 'currentYear'));
	}

	public function select_month(Request $request)
	{
		// if($data['month']!= 1 && $data['month']!= 8){
		// 	$firstDayofThisMonth = Carbon::now()->month($data['month'])->subMonth()->startOfMonth()->toDateString();
		// 	$lastDayofThisMonth = Carbon::now()->month($data['month'])->subMonth()->endOfMonth()->toDateString();
		// 	$dayInMonth = Carbon::now()->month($data['month'])->subMonth()->daysInMonth;
		// }else{
		// 	$firstDayofThisMonth = Carbon::now()->month($data['month'])->startOfMonth()->toDateString();
		// 	$lastDayofThisMonth = Carbon::now()->month($data['month'])->endOfMonth()->toDateString();
		// 	$dayInMonth = Carbon::now()->month($data['month'])->subMonth()->daysInMonth;
		// }
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
		$dayInMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->daysInMonth;
		$order = Order::join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->join('unit', 'orders.unit_id', '=', 'unit.unit_id')
			->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
			->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			// ->orderBy('orders.created_at', 'DESC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		$view = view('pages.schedule.schedule.view_schedule_render')->with(compact('order', 'dayInMonth'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function login_schedule_details()
	{
		return view('pages.schedule.login_schedule_details');
	}

	public function login_schedule(Request $request)
	{
		$data = $request->all();
		if (Auth::attempt([
			'email' => $data['email'],
			'password' => $data['password'],
		], true)) {
			if (Auth::user()->name == 'Admin' || Auth::user()->name == 'Office') {
				return Redirect::to('show-schedule-details');
			} else {
				return Redirect::to('lichxechitiet');
			}
		} else {
			return Redirect::to('lichchitiet')->with('error', 'Mật khẩu hoặc tài khoản bị sai.Vui lòng nhập lại');
		}
	}

	public function show_schedule_details()
	{
		return view('pages.schedule.schedule_details.view_schedule_details');
	}

	public function call_schedule_details(Request $request)
	{
		$month = [];
		$accountant_total_complete = 0;
		$accountant_total_cas = 0;
		$accountant_total_35 = 0;
		$accountant_total_8 = 0;
		$accountant_total_10 = 0;
		$accountant_total_4 = 0;
		$accountant_total_N = 0;
		$accountant_total_T = 0;
		$accountant_total_G = 0;
		$accountant_total_K = 0;

		if (empty($request->currentTime)) {
			$firstDayofThisMonth = Carbon::now()->startOfMonth()->toDateString();
			$lastDayofThisMonth = Carbon::now()->endOfMonth()->toDateString();
			$currentYear = Carbon::now()->format('Y');
			$currentMonth = Carbon::now()->format('F');
			$dayInMonth = Carbon::now()->daysInMonth;
		} else {
			$currentYear = $request->currentTime['year'];
			$currentMonth = $request->currentTime['month'];
			$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
			$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
			$dayInMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->daysInMonth;
		}

		$order = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('unit', 'orders.unit_id', '=', 'unit.unit_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
			->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			// ->orderBy('orders.created_at', 'DESC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		$accountant = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			->get();

		foreach ($accountant as $key => $accountant_t) {
			if ($accountant_t->order_status == 2 || $accountant_t->order_status == 3 || $accountant_t->order_status == 4) {
				$accountant_total_cas += $accountant_t->order_quantity;
				$accountant_total_35 += $accountant_t->accountant_35X43;
				$accountant_total_8 += $accountant_t->accountant_8X10;
				$accountant_total_10 += $accountant_t->accountant_10X12;
				if ($accountant_t->ord_select == 'Phổi (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống thắt lưng (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống cổ (1 Tư thế)' || $accountant_t->ord_select == 'Vai (1 Tư thế)' || $accountant_t->ord_select == 'Gối (1 Tư thế)' || $accountant_t->ord_select == 'Khác') {
					$accountant_total_complete += $accountant_t->order_quantity;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += $accountant_t->order_quantity;
					} else {
						$accountant_total_K += $accountant_t->order_quantity;
					}
				} else {
					$accountant_total_complete += ($accountant_t->order_quantity) * 2;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += ($accountant_t->order_quantity) * 2;
					} else {
						$accountant_total_K += ($accountant_t->order_quantity) * 2;
					}
				}
			}
		}
		for ($m = 1; $m <= 12; $m++) {
			$month[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}


		$html = view('pages.schedule.schedule_details.schedule_details_render')->with(compact('order', 'month', 'currentMonth', 'currentYear', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function suggest_schedule_search(Request $request)
	{
		$data = $request->all();
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
		$ctyName = OrderDetail::whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('ord_cty_name', 'LIKE', '%' . $data['query'] . '%')
			->orderBy('ord_cty_name', 'ASC')
			->get();
		$uniqueCtyName = $ctyName->unique('ord_cty_name');
		$html = '<ul  class="dropdown-menu">
            <div class="search-suggest">Gợi ý tìm kiếm</div>';
		foreach ($uniqueCtyName as $key => $name) {
			$html .= '<li class="li_search"><i class="fas fa-search"></i>' . $name->ord_cty_name . '</li>';
		}
		$html .= '</ul>';
		return response()->json(array('result' => true, 'html' => $html));
	}

	public function schedule_search(Request $request)
	{
		$data = $request->all();
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
		$dayInMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->daysInMonth;

		$order = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('unit', 'orders.unit_id', '=', 'unit.unit_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
			->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('ord_cty_name', $data['value'])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();

		$html = view('pages.schedule.schedule_details.view_schedule_details_search')->with(compact('order', 'dayInMonth'))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function show_schedule_details_clone()
	{
		$month = [];
		$accountant_total_complete = 0;
		$accountant_total_cas = 0;
		$accountant_total_35 = 0;
		$accountant_total_8 = 0;
		$accountant_total_10 = 0;
		$accountant_total_4 = 0;
		$accountant_total_N = 0;
		$accountant_total_T = 0;
		$accountant_total_G = 0;
		$accountant_total_K = 0;
		$firstDayofThisMonth = Carbon::now()->startOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::now()->endOfMonth()->toDateString();
		$currentYear = Carbon::now()->format('Y');
		$currentMonth = Carbon::now()->format('F');
		$dayInMonth = Carbon::now()->daysInMonth;
		$order = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('unit', 'orders.unit_id', '=', 'unit.unit_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
			->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->select(['order_status', 'ord_start_day', 'ord_end_day', 'order_warning', 'orders.order_id', 'car_ktv_id', 'car_ktv_name_1', 'car_ktv_name_2', 'unit_code', 'unit_name', 'ord_select', 'ord_cty_name', 'customer_address', 'customer_note', 'ord_list_file', 'ord_list_file_path', 'customer_name', 'customer_phone', 'ord_time', 'order_quantity', 'order_quantity_draft', 'order_note_ktv', 'ord_doctor_read', 'ord_film', 'ord_form', 'ord_print', 'ord_form_print', 'ord_print_result', 'ord_film_sheet', 'ord_note', 'ord_deadline', 'ord_deliver_results', 'ord_email', 'accountant_doctor_read', 'accountant_35X43', 'accountant_polime', 'accountant_8X10', 'accountant_10X12', 'accountant_film_bag', 'accountant_note', 'car_active', 'car_name', 'order_surcharge', 'order_child', 'ord_delivery_date', 'order_updated'])
			->orderBy('order_details.ord_start_day', 'ASC')
			// ->orderBy('orders.created_at', 'DESC')
			->orderBy('orders.order_child', 'DESC')
			->get();

		$accountant = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			->get();

		foreach ($accountant as $key => $accountant_t) {
			if ($accountant_t->order_status == 2 || $accountant_t->order_status == 3 || $accountant_t->order_status == 4) {
				$accountant_total_cas += $accountant_t->order_quantity;
				$accountant_total_35 += $accountant_t->accountant_35X43;
				$accountant_total_8 += $accountant_t->accountant_8X10;
				$accountant_total_10 += $accountant_t->accountant_10X12;
				if ($accountant_t->ord_select == 'Phổi (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống thắt lưng (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống cổ (1 Tư thế)' || $accountant_t->ord_select == 'Vai (1 Tư thế)' || $accountant_t->ord_select == 'Gối (1 Tư thế)' || $accountant_t->ord_select == 'Khác') {
					$accountant_total_complete += $accountant_t->order_quantity;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += $accountant_t->order_quantity;
					} else {
						$accountant_total_K += $accountant_t->order_quantity;
					}
				} else {
					$accountant_total_complete += ($accountant_t->order_quantity) * 2;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += ($accountant_t->order_quantity) * 2;
					} else {
						$accountant_total_K += ($accountant_t->order_quantity) * 2;
					}
				}
			}
		}
		for ($m = 1; $m <= 12; $m++) {
			$month[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.schedule.schedule_details_clone.view_schedule_details_clone')->with(compact('order', 'currentMonth', 'currentYear', 'month', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'));
	}

	public function select_month_details(Request $request)
	{
		$data = $request->all();
		$accountant_total_complete = 0;
		$accountant_total_cas = 0;
		$accountant_total_35 = 0;
		$accountant_total_8 = 0;
		$accountant_total_10 = 0;
		$accountant_total_4 = 0;
		$accountant_total_N = 0;
		$accountant_total_T = 0;
		$accountant_total_G = 0;
		$accountant_total_K = 0;

		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
		$dayInMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->daysInMonth;
		$order = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('unit', 'orders.unit_id', '=', 'unit.unit_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
			->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			// ->orderBy('orders.created_at', 'DESC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		$accountant = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			->get();

		foreach ($accountant as $key => $accountant_t) {
			if ($accountant_t->order_status == 2 || $accountant_t->order_status == 3 || $accountant_t->order_status == 4) {
				$accountant_total_cas += $accountant_t->order_quantity;
				$accountant_total_35 += $accountant_t->accountant_35X43;
				$accountant_total_8 += $accountant_t->accountant_8X10;
				$accountant_total_10 += $accountant_t->accountant_10X12;
				if ($accountant_t->ord_select == 'Phổi (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống thắt lưng (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống cổ (1 Tư thế)' || $accountant_t->ord_select == 'Vai (1 Tư thế)' || $accountant_t->ord_select == 'Gối (1 Tư thế)' || $accountant_t->ord_select == 'Khác') {
					$accountant_total_complete += $accountant_t->order_quantity;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += $accountant_t->order_quantity;
					} else {
						$accountant_total_K += $accountant_t->order_quantity;
					}
				} else {
					$accountant_total_complete += ($accountant_t->order_quantity) * 2;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += ($accountant_t->order_quantity) * 2;
					} else {
						$accountant_total_K += ($accountant_t->order_quantity) * 2;
					}
				}
			}
		}

		$view = view('pages.schedule.schedule_details.view_schedule_details_render')->with(compact('order', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function select_month_details_clone(Request $request)
	{
		$data = $request->all();
		$accountant_total_complete = 0;
		$accountant_total_cas = 0;
		$accountant_total_35 = 0;
		$accountant_total_8 = 0;
		$accountant_total_10 = 0;
		$accountant_total_4 = 0;
		$accountant_total_N = 0;
		$accountant_total_T = 0;
		$accountant_total_G = 0;
		$accountant_total_K = 0;
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
		$dayInMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->daysInMonth;
		$order = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('unit', 'orders.unit_id', '=', 'unit.unit_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
			->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->select(['order_status', 'ord_start_day', 'ord_end_day', 'order_warning', 'orders.order_id', 'car_ktv_id', 'car_ktv_name_1', 'car_ktv_name_2', 'unit_code', 'unit_name', 'ord_select', 'ord_cty_name', 'customer_address', 'customer_note', 'ord_list_file', 'ord_list_file_path', 'customer_name', 'customer_phone', 'ord_time', 'order_quantity', 'order_quantity_draft', 'order_note_ktv', 'ord_doctor_read', 'ord_film', 'ord_form', 'ord_print', 'ord_form_print', 'ord_print_result', 'ord_film_sheet', 'ord_note', 'ord_deadline', 'ord_deliver_results', 'ord_email', 'accountant_doctor_read', 'accountant_35X43', 'accountant_polime', 'accountant_8X10', 'accountant_10X12', 'accountant_film_bag', 'accountant_note', 'car_active', 'car_name', 'order_surcharge', 'order_child', 'ord_delivery_date', 'order_updated'])
			->orderBy('order_details.ord_start_day', 'ASC')
			// ->orderBy('orders.created_at', 'DESC')
			->orderBy('orders.order_child', 'DESC')
			->get();

		$accountant = Order::join('accountant', 'accountant.order_id', '=', 'orders.order_id')
			->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			->get();

		foreach ($accountant as $key => $accountant_t) {
			if ($accountant_t->order_status == 2 || $accountant_t->order_status == 3 || $accountant_t->order_status == 4) {
				$accountant_total_cas += $accountant_t->order_quantity;
				$accountant_total_35 += $accountant_t->accountant_35X43;
				$accountant_total_8 += $accountant_t->accountant_8X10;
				$accountant_total_10 += $accountant_t->accountant_10X12;
				if ($accountant_t->ord_select == 'Phổi (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống thắt lưng (1 Tư thế)' || $accountant_t->ord_select == 'Cột sống cổ (1 Tư thế)' || $accountant_t->ord_select == 'Vai (1 Tư thế)' || $accountant_t->ord_select == 'Gối (1 Tư thế)' || $accountant_t->ord_select == 'Khác') {
					$accountant_total_complete += $accountant_t->order_quantity;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += $accountant_t->order_quantity;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += $accountant_t->order_quantity;
					} else {
						$accountant_total_K += $accountant_t->order_quantity;
					}
				} else {
					$accountant_total_complete += ($accountant_t->order_quantity) * 2;
					if ($accountant_t->accountant_doctor_read == 'Nhân') {
						$accountant_total_N += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Trung') {
						$accountant_total_T += ($accountant_t->order_quantity) * 2;
					} elseif ($accountant_t->accountant_doctor_read == 'Giang') {
						$accountant_total_G += ($accountant_t->order_quantity) * 2;
					} else {
						$accountant_total_K += ($accountant_t->order_quantity) * 2;
					}
				}
			}
		}
		$view = view('pages.schedule.schedule_details_clone.view_schedule_details_clone_render')->with(compact('order', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function update_order_quantity_draft(Request $request, $id)
	{
		$data = $request->all();
		$order = Order::find($id);
		$order->order_quantity_draft = $data['order_quantity_draft'];
		$order->order_note_ktv = $data['order_note_ktv'];
		$order->save();
	}

	public function update_order_quantity_details(Request $request, $id)
	{
		$data = $request->all();
		$order = Order::find($id);
		$order->order_quantity = $data['order_quantity_details'];
		if ($order->order_status == 4) {
			$order->order_status = 4;
		} else {
			$order->order_status = 2;
		}
		$order->order_updated = 1;
		$order->save();
		$order_detail = OrderDetail::find($order->order_detail_id);
		$order_detail->ord_delivery_date = $data['ord_delivery_date'];
		$order_detail->save();

		$accountant = Accountant::where('order_id', $id)->first();
		$accountant->accountant_doctor_read = $data['accountant_doctor_read'];
		$accountant->accountant_35X43 = $data['accountant_35X43'];
		$accountant->accountant_polime = $data['accountant_polime'];
		$accountant->accountant_8X10 = $data['accountant_8X10'];
		$accountant->accountant_10X12 = $data['accountant_10X12'];
		$accountant->accountant_note = $data['accountant_note'];
		$accountant->save();
	}

	public function update_order_warning(Request $request, $id)
	{
		$data = $request->all();
		$order = Order::find($id);
		$order->order_warning = $data['order_warning'];
		$order->save();
	}
}
