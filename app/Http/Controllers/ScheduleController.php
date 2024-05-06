<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\CarKTV;
use App\Models\Accountant;
use App\Models\OrderDetail;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
	//Client
	public function showSchedule()
	{
		$months = [];
		$firstDayofThisMonth = Carbon::now()->startOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::now()->endOfMonth()->toDateString();
		$currentYear = Carbon::now()->format('Y');
		$currentMonth = Carbon::now()->format('F');
		$dayInMonth = Carbon::now()->daysInMonth;
		$orders = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('units', 'units.id', '=', 'orders.unit_id' )
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->select([
				'car_name',
				'car_active',
				'status_id', 
				'order_surcharge',
				'car_ktv_name_1', 
				'car_ktv_name_2', 
				'ord_start_day', 
				'ord_end_day', 
				'order_child', 
				'order_updated', 
				'orders.id', 
				'unit_name', 
				'car_ktvs.id', 
				'ord_select', 
				'ord_cty_name', 
				'customer_address', 
				'ord_note', 
				'ord_list_file', 
				'ord_list_file_path', 
				'customer_name', 
				'customer_phone', 
				'ord_time', 
				'order_quantity', 
				'order_quantity_draft', 
				'order_note_ktv'])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();
			// dd($orders);
		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}
		return view('pages.client.schedule.ktv.index')->with(compact('orders', 'months', 'dayInMonth', 'currentMonth', 'currentYear'));
	}

	public function selectMonth(Request $request)
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
		$order = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('units', 'units.id', '=', 'orders.unit_id' )
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		$view = view('pages.client.schedule.ktv.render')->with(compact('order', 'dayInMonth'))->render();

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

	//Admin
	public function create($order_id)
	{
		$order = Order::find($order_id);
		$staff = Staff::orderBy('staff_id', 'ASC')->get();
		return view('admin.Order.add_schedule')->with(compact('staff', 'order'));
	}

	public function store(Request $request)
	{
		$data = $request->all();
		if (!empty($data['zalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car = new CarKTV();
				$driver[$key] = explode("_", $request->car_driver_name[$key]);
				$name_driver[$key] = array_shift($driver[$key]);
				$phone_driver[$key] = array_pop($driver[$key]);

				$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
				$name_ktv1[$key] = array_shift($ktv1[$key]);
				$phone_ktv1[$key] = array_pop($ktv1[$key]);

				$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
				$name_ktv2[$key] = array_shift($ktv2[$key]);
				$phone_ktv2[$key] = array_pop($ktv2[$key]);

				$car->order_id = $data['order_id'];
				$car->car_name = $request->car_name[$key];
				$car->car_active = $request->car_active[$key];
				$car->car_driver_name = $name_driver[$key];
				$car->car_driver_phone = $phone_driver[$key];
				$car->car_ktv_name_1 = $name_ktv1[$key];
				$car->car_ktv_phone_1 = $phone_ktv1[$key];
				$car->car_ktv_name_2 = $name_ktv2[$key];
				$car->car_ktv_phone_2 = $phone_ktv2[$key];
				$car->save();
			}
			$order = Order::find($data['order_id']);
			$order->schedule_status = 1;
			$order->save();

			$getOrderId = CarKTV::orderBy('updated_at', 'DESC')->first();
			$carActive = CarKTV::orderBy('updated_at', 'DESC')->where('order_id', $getOrderId->order_id)->where('car_active', 1)->get();

			foreach ($carActive as $key => $value) {
				if ($value->car_driver_phone != null && $value->car_driver_name != null) {
					$this->zaloNotificationServiceD($value);
				}
				if ($value->car_ktv_phone_1 != null && $value->car_ktv_name_1 != null) {
					$this->zaloNotificationServiceK1($value);
				}
				if ($value->car_ktv_phone_2 != null && $value->car_ktv_name_2 != null) {
					$this->zaloNotificationServiceK2($value);
				}
			}

			return Redirect()->back()->with('success', 'Thêm lịch gửi Zalo thành công');
		}
		if (!empty($data['notZalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car = new CarKTV();
				$driver[$key] = explode("_", $request->car_driver_name[$key]);
				$name_driver[$key] = array_shift($driver[$key]);
				$phone_driver[$key] = array_pop($driver[$key]);

				$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
				$name_ktv1[$key] = array_shift($ktv1[$key]);
				$phone_ktv1[$key] = array_pop($ktv1[$key]);

				$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
				$name_ktv2[$key] = array_shift($ktv2[$key]);
				$phone_ktv2[$key] = array_pop($ktv2[$key]);

				$car->order_id = $data['order_id'];
				$car->car_name = $request->car_name[$key];
				$car->car_active = $request->car_active[$key];
				$car->car_driver_name = $name_driver[$key];
				$car->car_driver_phone = $phone_driver[$key];
				$car->car_ktv_name_1 = $name_ktv1[$key];
				$car->car_ktv_phone_1 = $phone_ktv1[$key];
				$car->car_ktv_name_2 = $name_ktv2[$key];
				$car->car_ktv_phone_2 = $phone_ktv2[$key];
				$car->save();
			}
			$order = Order::find($data['order_id']);
			$order->schedule_status = 1;
			$order->save();

			return Redirect()->back()->with('success', 'Thêm lịch không gửi Zalo thành công');
		}
	}

	public function edit($order_id)
	{
		$order = $order_id;
		$staff = Staff::orderBy('staff_id', 'ASC')->get();
		$car = CarKTV::where('order_id', $order_id)->get();
		return view('admin.Order.edit_schedule')->with(compact('staff', 'order', 'car'));
	}

	public function update(Request $request, $order_id)
	{
		$data = $request->all();

		if (!empty($data['zalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car_id = CarKTV::where('order_id', $order_id)->get();
				foreach ($car_id as $key => $car_i) {
					$car = CarKTV::find($car_i->car_ktv_id);

					$driver[$key] = explode("_", $request->car_driver_name[$key]);
					$name_driver[$key] = array_shift($driver[$key]);
					$phone_driver[$key] = array_pop($driver[$key]);

					$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
					$name_ktv1[$key] = array_shift($ktv1[$key]);
					$phone_ktv1[$key] = array_pop($ktv1[$key]);

					$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
					$name_ktv2[$key] = array_shift($ktv2[$key]);
					$phone_ktv2[$key] = array_pop($ktv2[$key]);

					$car->order_id = $order_id;
					$car->car_name = $request->car_name[$key];
					$car->car_active = $request->car_active[$key];
					$car->car_driver_name = $name_driver[$key];
					$car->car_driver_phone = $phone_driver[$key];
					$car->car_ktv_name_1 = $name_ktv1[$key];
					$car->car_ktv_phone_1 = $phone_ktv1[$key];
					$car->car_ktv_name_2 = $name_ktv2[$key];
					$car->car_ktv_phone_2 = $phone_ktv2[$key];
					$car->save();
				}
			}

			$getOrderId = CarKTV::orderBy('updated_at', 'DESC')->first();
			$carActive = CarKTV::orderBy('updated_at', 'DESC')->where('order_id', $getOrderId->order_id)->where('car_active', 1)->get();

			foreach ($carActive as $key => $value) {
				if ($value->car_driver_phone != null && $value->car_driver_name != null) {
					$this->zaloNotificationServiceD($value);
				}
				if ($value->car_ktv_phone_1 != null && $value->car_ktv_name_1 != null) {
					$this->zaloNotificationServiceK1($value);
				}
				if ($value->car_ktv_phone_2 != null && $value->car_ktv_name_2 != null) {
					$this->zaloNotificationServiceK2($value);
				}
			}
			return Redirect()->back()->with('success', 'Cập nhật lịch xe gửi Zalo thành công');
		}
		if (!empty($data['notZalo'])) {
			foreach ($request->car_name as $key => $car_name) {
				$car_id = CarKTV::where('order_id', $order_id)->get();
				foreach ($car_id as $key => $car_i) {
					$car = CarKTV::find($car_i->car_ktv_id);

					$driver[$key] = explode("_", $request->car_driver_name[$key]);
					$name_driver[$key] = array_shift($driver[$key]);
					$phone_driver[$key] = array_pop($driver[$key]);

					$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
					$name_ktv1[$key] = array_shift($ktv1[$key]);
					$phone_ktv1[$key] = array_pop($ktv1[$key]);

					$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
					$name_ktv2[$key] = array_shift($ktv2[$key]);
					$phone_ktv2[$key] = array_pop($ktv2[$key]);

					$car->order_id = $order_id;
					$car->car_name = $request->car_name[$key];
					$car->car_active = $request->car_active[$key];
					$car->car_driver_name = $name_driver[$key];
					$car->car_driver_phone = $phone_driver[$key];
					$car->car_ktv_name_1 = $name_ktv1[$key];
					$car->car_ktv_phone_1 = $phone_ktv1[$key];
					$car->car_ktv_name_2 = $name_ktv2[$key];
					$car->car_ktv_phone_2 = $phone_ktv2[$key];
					$car->save();
				}
			}
			return Redirect()->back()->with('success', 'Cập nhật lịch xe không gửi Zalo thành công');
		}
	}

	public function cancel(Request $request)
	{
		$data = $request->all();
		$car = CarKTV::where('order_id', $data['order_id'])->where('car_name', $data['car_name'])->first();
		if ($car->car_driver_phone != null && $car->car_driver_name != null) {
			$this->zaloNotificationCancleD($car);
		}
		if ($car->car_ktv_phone_1 != null && $car->car_ktv_name_1 != null) {
			$this->zaloNotificationCancleK1($car);
		}
		if ($car->car_ktv_phone_2 != null && $car->car_ktv_name_2 != null) {
			$this->zaloNotificationCancleK2($car);
		}

		$car->car_active = 0;
		$car->car_driver_name = '';
		$car->car_driver_phone = '';
		$car->car_ktv_name_1 = '';
		$car->car_ktv_phone_1 = '';
		$car->car_ktv_name_2 = '';
		$car->car_ktv_phone_2 = '';
		$car->save();

		return response()->json(['success' => 'Huỷ lịch thành công.']);
	}
}
