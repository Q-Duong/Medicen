<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\CarKTV;
use App\Models\Accountant;
use App\Models\OrderDetail;
use App\Models\Staff;
use App\Models\TempFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
	//Client
	//Technologist
	public function showSchedule()
	{
		$months = [];
		$firstDayofThisMonth = Carbon::now()->startOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::now()->endOfMonth()->toDateString();
		$currentYear = Carbon::now()->format('Y');
		$currentMonth = Carbon::now()->format('F');
		$dayInMonth = Carbon::now()->daysInMonth;
		$orders = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('units', 'units.id', '=', 'orders.unit_id')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('car_ktvs.car_active', 1)
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
				'car_ktvs.order_id',
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
				'order_note_ktv'
			])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();

		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.client.schedule.technologist.index', compact('orders', 'months', 'dayInMonth', 'currentMonth', 'currentYear'));
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
		$orders = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('units', 'units.id', '=', 'orders.unit_id')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('car_ktvs.car_active', 1)
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
				'car_ktvs.order_id',
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
				'order_note_ktv'
			])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		$view = view('pages.client.schedule.technologist.render', compact('orders', 'dayInMonth'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function updateQuantityKTV(Request $request)
	{
		$data = $request->all();
		$order = Order::findOrFail($request->id);
		$order->order_quantity_draft = $data['order_quantity_draft'];
		$order->order_note_ktv = $data['order_note_ktv'];
		$order->save();
		return response()->json(array('success' => true));
	}
	//End Technologist

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

	//Details
	public function showScheduleDetails()
	{
		return view('pages.client.schedule.details.index');
	}

	public function getScheduleDetails(Request $request)
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

		$orders = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('units', 'units.id', '=', 'orders.unit_id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('car_ktvs.car_active', 1)
			->select([
				'status_id',
				'car_ktvs.order_id',
				'car_ktvs.id',
				'car_ktv_name_1',
				'car_ktv_name_2',
				'car_active',
				'car_name',
				'unit_code',
				'unit_name',
				'customer_address',
				'customer_note',
				'customer_name',
				'customer_phone',
				'orders.order_detail_id',
				'ord_select',
				'ord_cty_name',
				'ord_time',
				'ord_list_file',
				'ord_list_file_path',
				'ord_total_file_name',
				'ord_total_file_path',
				'ord_delivery_date',
				'ord_start_day',
				'ord_end_day',
				'ord_doctor_read',
				'ord_film',
				'ord_form',
				'ord_print',
				'ord_form_print',
				'ord_print_result',
				'ord_film_sheet',
				'ord_note',
				'ord_deadline',
				'ord_deliver_results',
				'ord_email',
				'accountant_doctor_read',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'order_surcharge',
				'order_child',
				'order_quantity',
				'order_quantity_draft',
				'order_note_ktv',
				'order_warning',
				'order_updated'
			])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		$accountant = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
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

		$html = view('pages.client.schedule.details.index_render')->with(compact('orders', 'month', 'currentMonth', 'currentYear', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function scheduleSearchSuggest(Request $request)
	{
		$data = $request->all();
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
		$ctyName = OrderDetail::whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('ord_cty_name', 'LIKE', '%' . $data['query'] . '%')
			->select(['ord_cty_name'])
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

	public function scheduleSearch(Request $request)
	{
		$data = $request->all();
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
		$dayInMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->daysInMonth;
		$orders = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('units', 'units.id', '=', 'orders.unit_id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('car_ktvs.car_active', 1)
			->where('ord_cty_name', $data['param'])
			->select([
				'status_id',
				'car_ktvs.order_id',
				'car_ktvs.id',
				'car_ktv_name_1',
				'car_ktv_name_2',
				'car_active',
				'car_name',
				'unit_code',
				'unit_name',
				'customer_address',
				'customer_note',
				'customer_name',
				'customer_phone',
				'orders.order_detail_id',
				'ord_select',
				'ord_cty_name',
				'ord_time',
				'ord_list_file',
				'ord_list_file_path',
				'ord_total_file_name',
				'ord_total_file_path',
				'ord_delivery_date',
				'ord_start_day',
				'ord_end_day',
				'ord_doctor_read',
				'ord_film',
				'ord_form',
				'ord_print',
				'ord_form_print',
				'ord_print_result',
				'ord_film_sheet',
				'ord_note',
				'ord_deadline',
				'ord_deliver_results',
				'ord_email',
				'accountant_doctor_read',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'order_surcharge',
				'order_child',
				'order_quantity',
				'order_quantity_draft',
				'order_note_ktv',
				'order_warning',
				'order_updated'
			])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();

		$html = view('pages.client.schedule.details.search_render')->with(compact('orders', 'dayInMonth'))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function selectMonthDetails(Request $request)
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
		$orders = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('units', 'units.id', '=', 'orders.unit_id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('car_ktvs.car_active', 1)
			->select([
				'status_id',
				'car_ktvs.order_id',
				'car_ktvs.id',
				'car_ktv_name_1',
				'car_ktv_name_2',
				'car_active',
				'car_name',
				'unit_code',
				'unit_name',
				'customer_address',
				'customer_note',
				'customer_name',
				'customer_phone',
				'orders.order_detail_id',
				'ord_select',
				'ord_cty_name',
				'ord_time',
				'ord_list_file',
				'ord_list_file_path',
				'ord_total_file_name',
				'ord_total_file_path',
				'ord_delivery_date',
				'ord_start_day',
				'ord_end_day',
				'ord_doctor_read',
				'ord_film',
				'ord_form',
				'ord_print',
				'ord_form_print',
				'ord_print_result',
				'ord_film_sheet',
				'ord_note',
				'ord_deadline',
				'ord_deliver_results',
				'ord_email',
				'accountant_doctor_read',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'order_surcharge',
				'order_child',
				'order_quantity',
				'order_quantity_draft',
				'order_note_ktv',
				'order_warning',
				'order_updated'
			])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();
		$accountant = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
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

		$view = view('pages.client.schedule.details.render')->with(compact('orders', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function updateQuantityDetails(Request $request)
	{
		$data = $request->all();
		$order = Order::findOrFail($request->id);
		$order->order_quantity = $data['order_quantity_details'];
		if ($order->status_id == 4) {
			$order->status_id = 4;
		} else {
			$order->status_id = 2;
		}
		$order->order_updated = 1;
		$order->save();
		$orderDetail = OrderDetail::findOrFail($order->order_detail_id);
		$orderDetail->ord_delivery_date = $data['ord_delivery_date'];
		if ($request->ord_total_file_name) {
			$tmp_file = TempFile::where('filename', $request->ord_total_file_name)->first();
			$orderDetail->ord_total_file_name = $tmp_file->filename;
			$orderDetail->ord_total_file_path = $tmp_file->folder;
			$tmp_file->delete();			
		}
		$orderDetail->save();

		// $accountant = Accountant::where('order_id', $request->id)->first();
		// $accountant->accountant_doctor_read = $data['accountant_doctor_read'];
		// $accountant->accountant_35X43 = $data['accountant_35X43'];
		// $accountant->accountant_polime = $data['accountant_polime'];
		// $accountant->accountant_8X10 = $data['accountant_8X10'];
		// $accountant->accountant_10X12 = $data['accountant_10X12'];
		// $accountant->accountant_note = $data['accountant_note'];
		// $accountant->save();
	}
	//End Details

	//Sales
	public function showScheduleSale()
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
		$orders = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('units', 'units.id', '=', 'orders.unit_id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('car_ktvs.car_active', 1)
			->select([
				'status_id',
				'car_ktvs.order_id',
				'car_ktvs.id',
				'car_ktv_name_1',
				'car_ktv_name_2',
				'car_active',
				'car_name',
				'unit_code',
				'unit_name',
				'customer_address',
				'customer_note',
				'customer_name',
				'customer_phone',
				'orders.order_detail_id',
				'ord_select',
				'ord_cty_name',
				'ord_time',
				'ord_list_file',
				'ord_list_file_path',
				'ord_total_file_name',
				'ord_total_file_path',
				'ord_delivery_date',
				'ord_start_day',
				'ord_end_day',
				'ord_doctor_read',
				'ord_film',
				'ord_form',
				'ord_print',
				'ord_form_print',
				'ord_print_result',
				'ord_film_sheet',
				'ord_note',
				'ord_deadline',
				'ord_deliver_results',
				'ord_email',
				'accountant_doctor_read',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'order_surcharge',
				'order_child',
				'order_quantity',
				'order_quantity_draft',
				'order_note_ktv',
				'order_warning',
				'order_updated'
			])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();

		$accountant = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
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

		return view('pages.client.schedule.sales.index')->with(compact('orders', 'currentMonth', 'currentYear', 'month', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'));
	}

	public function selectMonthSales(Request $request)
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
		$orders = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('units', 'units.id', '=', 'orders.unit_id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->join('customers', 'customers.id', '=', 'orders.customer_id')
			->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
			->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
			->where('car_ktvs.car_active', 1)
			->select([
				'status_id',
				'car_ktvs.order_id',
				'car_ktvs.id',
				'car_ktv_name_1',
				'car_ktv_name_2',
				'car_active',
				'car_name',
				'unit_code',
				'unit_name',
				'customer_address',
				'customer_note',
				'customer_name',
				'customer_phone',
				'orders.order_detail_id',
				'ord_select',
				'ord_cty_name',
				'ord_time',
				'ord_list_file',
				'ord_list_file_path',
				'ord_total_file_name',
				'ord_total_file_path',
				'ord_delivery_date',
				'ord_start_day',
				'ord_end_day',
				'ord_doctor_read',
				'ord_film',
				'ord_form',
				'ord_print',
				'ord_form_print',
				'ord_print_result',
				'ord_film_sheet',
				'ord_note',
				'ord_deadline',
				'ord_deliver_results',
				'ord_email',
				'accountant_doctor_read',
				'accountant_35X43',
				'accountant_polime',
				'accountant_8X10',
				'accountant_10X12',
				'accountant_film_bag',
				'accountant_note',
				'order_surcharge',
				'order_child',
				'order_quantity',
				'order_quantity_draft',
				'order_note_ktv',
				'order_warning',
				'order_updated'
			])
			->orderBy('order_details.ord_start_day', 'ASC')
			->orderBy('orders.order_child', 'DESC')
			->get();

		$accountant = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
			->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
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
		$view = view('pages.client.schedule.sales.render')->with(compact('orders', 'dayInMonth', 'accountant_total_complete', 'accountant_total_cas', 'accountant_total_35', 'accountant_total_8', 'accountant_total_10', 'accountant_total_N', 'accountant_total_T', 'accountant_total_G', 'accountant_total_K'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}
	//End Sales

	//Admin
	public function create($order_id)
	{
		$getAllStaff = Staff::orderBy('id', 'ASC')->get();
		$order = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->where('orders.id', $order_id)
			->select(['order_details.ord_start_day'])
			->first();
		return view('pages.admin.order.schedule.create', compact('getAllStaff', 'order', 'order_id'));
	}

	public function store(Request $request)
	{
		$message = 'Thêm lịch không gửi Zalo thành công';
		$data = $request->all();
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
		$order = Order::findOrFail($data['order_id']);
		$order->schedule_status = 1;
		$order->save();

		if (!empty($data['zalo'])) {
			$getOrderId = CarKTV::orderBy('updated_at', 'DESC')->first();
			$carActive = CarKTV::where('order_id', $getOrderId->order_id)->where('car_active', 1)->get();
			foreach ($carActive as $key => $car) {
				if ($car->car_driver_phone != null && $car->car_driver_name != null) {
					app(ZaloController::class)->notificationSchedule($car, 'drv');
				}
				if ($car->car_ktv_phone_1 != null && $car->car_ktv_name_1 != null) {
					app(ZaloController::class)->notificationSchedule($car, 'kt1');
				}
				if ($car->car_ktv_phone_2 != null && $car->car_ktv_name_2 != null) {
					app(ZaloController::class)->notificationSchedule($car, 'kt2');
				}
			}
			$message = 'Thêm lịch gửi Zalo thành công';
		}
		return Redirect::route('order.index')->with('success', $message);
	}

	public function edit($order_id)
	{
		$is_active = 0;
		$order = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->where('orders.id', $order_id)
			->select(['order_details.ord_start_day'])
			->first();
		$getAllStaff = Staff::orderBy('id', 'ASC')->get();
		$cars = CarKTV::where('order_id', $order_id)->get();
		if (in_array(1, $cars->pluck('car_active')->toArray())) {
			$is_active = 1;
		}
		return view('pages.admin.order.schedule.edit', compact('getAllStaff', 'order', 'order_id', 'cars', 'is_active'));
	}

	public function update(Request $request, $order_id)
	{
		$message = 'Cập nhật lịch xe không gửi Zalo thành công';
		$data = $request->all();
		foreach ($request->car_name as $key => $car_name) {
			$car_id = CarKTV::where('order_id', $order_id)->get();
			foreach ($car_id as $key => $car_i) {
				$car = CarKTV::find($car_i->id);

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
		if (!empty($data['zalo'])) {
			$getOrderId = CarKTV::orderBy('updated_at', 'DESC')->first();
			$carActive = CarKTV::where('order_id', $getOrderId->order_id)->where('car_active', 1)->get();
			foreach ($carActive as $key => $car) {
				if ($car->car_driver_phone != null && $car->car_driver_name != null) {
					app(ZaloController::class)->notificationSchedule($car, 'drv');
				}
				if ($car->car_ktv_phone_1 != null && $car->car_ktv_name_1 != null) {
					app(ZaloController::class)->notificationSchedule($car, 'kt1');
				}
				if ($car->car_ktv_phone_2 != null && $car->car_ktv_name_2 != null) {
					app(ZaloController::class)->notificationSchedule($car, 'kt2');
				}
			}
			$message = 'Cập nhật lịch xe gửi Zalo thành công';
		}
		return Redirect::route('order.index')->with('success', $message);
	}

	public function cancel(Request $request)
	{
		$data = $request->all();
		$car = CarKTV::where('order_id', $data['order_id'])->where('car_name', $data['car_name'])->first();
		if ($car->car_driver_phone != null && $car->car_driver_name != null) {
			app(ZaloController::class)->notificationScheduleCancel($car, 'drv');
		}
		if ($car->car_ktv_phone_1 != null && $car->car_ktv_name_1 != null) {
			app(ZaloController::class)->notificationScheduleCancel($car, 'kt1');
		}
		if ($car->car_ktv_phone_2 != null && $car->car_ktv_name_2 != null) {
			app(ZaloController::class)->notificationScheduleCancel($car, 'kt2');
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
