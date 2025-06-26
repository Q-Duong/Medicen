<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\CarKTV;
use App\Models\HistoryEdit;
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
		$orders = Order::getScheduleTechnologist($firstDayofThisMonth, $lastDayofThisMonth);

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
		if ($request->month == 'April') {
			$firstDayofThisMonth = $request->year . '-04-01';
			$lastDayofThisMonth = $request->year . '-04-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'June') {
			$firstDayofThisMonth = $request->year . '-06-01';
			$lastDayofThisMonth = $request->year . '-06-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'September') {
			$firstDayofThisMonth = $request->year . '-09-01';
			$lastDayofThisMonth = $request->year . '-00-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'November') {
			$firstDayofThisMonth = $request->year . '-11-01';
			$lastDayofThisMonth = $request->year . '-11-30';
			$dayInMonth = 30;
		} else {
			$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
			$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
			$dayInMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->daysInMonth;
		}
		$orders = Order::getScheduleTechnologist($firstDayofThisMonth, $lastDayofThisMonth);
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

	public function loginScheduleDetails()
	{
		return view('pages.client.schedule.login_schedule_details');
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
		$months = [];
		$statistic_complete = 0;
		$statistic_cas = 0;
		$statistic_ultrasound = 0;
		$statistic_bone = 0;
		$statistic_35 = 0;
		$statistic_8 = 0;
		$statistic_10 = 0;
		$statistic_4 = 0;
		$statistic_N = 0;
		$statistic_T = 0;
		$statistic_G = 0;
		$statistic_A = 0;
		$statistic_K = 0;
		$xray1Position = ['Phổi (1 Tư thế)', 'Cột sống thắt lưng (1 Tư thế)', 'Cột sống cổ (1 Tư thế)', 'Vai (1 Tư thế)', 'Gối (1 Tư thế)', 'Khác'];
		$xray2Position = ['Phổi (2 Tư thế)', 'Cột sống thắt lưng (2 Tư thế)', 'Cột sống cổ (2 Tư thế)', 'Vai (2 Tư thế)', 'Gối (2 Tư thế)'];
		$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];

		if (empty($request->currentTime)) {
			$firstDayofThisMonth = Carbon::now()->startOfMonth()->toDateString();
			$lastDayofThisMonth = Carbon::now()->endOfMonth()->toDateString();
			$currentYear = Carbon::now()->format('Y');
			$currentMonth = Carbon::now()->format('F');
			$dayInMonth = Carbon::now()->daysInMonth;
		} else {
			$currentYear = $request->currentTime['year'];
			$currentMonth = $request->currentTime['month'];
			if ($currentMonth == 'April') {
				$firstDayofThisMonth = $currentYear . '-04-01';
				$lastDayofThisMonth = $currentYear . '-04-30';
				$dayInMonth = 30;
			} elseif ($currentMonth == 'June') {
				$firstDayofThisMonth = $currentYear . '-06-01';
				$lastDayofThisMonth = $currentYear . '-06-30';
				$dayInMonth = 30;
			} elseif ($currentMonth == 'September') {
				$firstDayofThisMonth = $currentYear . '-09-01';
				$lastDayofThisMonth = $currentYear . '-00-30';
				$dayInMonth = 30;
			} elseif ($currentMonth == 'November') {
				$firstDayofThisMonth = $currentYear . '-11-01';
				$lastDayofThisMonth = $currentYear . '-11-30';
				$dayInMonth = 30;
			} else {
				$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
				$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
				$dayInMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->daysInMonth;
			}
		}

		$orders = Order::getScheduleDetails($firstDayofThisMonth, $lastDayofThisMonth);
		$statistics = Accountant::getStatistics($firstDayofThisMonth, $lastDayofThisMonth);

		foreach ($statistics as $key => $statistic) {
			if ($statistic->status_id == 2 || $statistic->status_id == 3 || $statistic->status_id == 4) {
				if (!in_array($statistic->ord_select, $ultraSound)) {
					$statistic_cas += $statistic->order_quantity;
					$statistic_35 += $statistic->accountant_35X43;
					$statistic_8 += $statistic->accountant_8X10;
					$statistic_10 += $statistic->accountant_10X12;
					if (in_array($statistic->ord_select, $xray1Position)) {
						$statistic_complete += $statistic->order_quantity;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += $statistic->order_quantity;
						} else {
							$statistic_K += $statistic->order_quantity;
						}
					} elseif (in_array($statistic->ord_select, $xray2Position)) {
						$statistic_complete += ($statistic->order_quantity) * 2;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += ($statistic->order_quantity) * 2;
						} else {
							$statistic_K += ($statistic->order_quantity) * 2;
						}
					}
				}
			}
			if ($statistic->schedule_status) {
				if (in_array($statistic->ord_select, $ultraSound)) {
					$statistic_ultrasound += ($statistic->order_quantity);
				}
				if ($statistic->ord_select == "Đo loãng xương") {
					$statistic_bone += ($statistic->order_quantity);
				}
			}
		}

		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		$html = view('pages.client.schedule.details.index_render')->with(compact('orders', 'months', 'currentMonth', 'currentYear', 'dayInMonth', 'statistic_complete', 'statistic_cas', 'statistic_ultrasound', 'statistic_bone', 'statistic_35', 'statistic_8', 'statistic_10', 'statistic_N', 'statistic_T', 'statistic_G', 'statistic_A', 'statistic_K'))->render();

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
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
		$dayInMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->daysInMonth;
		$orders = Order::getScheduleDetailsForSearch($firstDayofThisMonth, $lastDayofThisMonth, $request->param);

		$html = view('pages.client.schedule.details.search_render', compact('orders', 'dayInMonth'))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function selectMonthDetails(Request $request)
	{
		$data = $request->all();
		$statistic_complete = 0;
		$statistic_cas = 0;
		$statistic_ultrasound = 0;
		$statistic_bone = 0;
		$statistic_35 = 0;
		$statistic_8 = 0;
		$statistic_10 = 0;
		$statistic_4 = 0;
		$statistic_N = 0;
		$statistic_T = 0;
		$statistic_G = 0;
		$statistic_A = 0;
		$statistic_K = 0;
		$xray1Position = ['Phổi (1 Tư thế)', 'Cột sống thắt lưng (1 Tư thế)', 'Cột sống cổ (1 Tư thế)', 'Vai (1 Tư thế)', 'Gối (1 Tư thế)', 'Khác'];
		$xray2Position = ['Phổi (2 Tư thế)', 'Cột sống thắt lưng (2 Tư thế)', 'Cột sống cổ (2 Tư thế)', 'Vai (2 Tư thế)', 'Gối (2 Tư thế)'];
		$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];
		if ($request->month == 'April') {
			$firstDayofThisMonth = $request->year . '-04-01';
			$lastDayofThisMonth = $request->year . '-04-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'June') {
			$firstDayofThisMonth = $request->year . '-06-01';
			$lastDayofThisMonth = $request->year . '-06-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'September') {
			$firstDayofThisMonth = $request->year . '-09-01';
			$lastDayofThisMonth = $request->year . '-00-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'November') {
			$firstDayofThisMonth = $request->year . '-11-01';
			$lastDayofThisMonth = $request->year . '-11-30';
			$dayInMonth = 30;
		} else {
			$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
			$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
			$dayInMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->daysInMonth;
		}
		$orders = Order::getScheduleDetails($firstDayofThisMonth, $lastDayofThisMonth);
		$statistics = Accountant::getStatistics($firstDayofThisMonth, $lastDayofThisMonth);

		foreach ($statistics as $key => $statistic) {
			if ($statistic->status_id == 2 || $statistic->status_id == 3 || $statistic->status_id == 4) {
				if (!in_array($statistic->ord_select, $ultraSound)) {
					$statistic_cas += $statistic->order_quantity;
					$statistic_35 += $statistic->accountant_35X43;
					$statistic_8 += $statistic->accountant_8X10;
					$statistic_10 += $statistic->accountant_10X12;
					if (in_array($statistic->ord_select, $xray1Position)) {
						$statistic_complete += $statistic->order_quantity;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += $statistic->order_quantity;
						} else {
							$statistic_K += $statistic->order_quantity;
						}
					} elseif (in_array($statistic->ord_select, $xray2Position)) {
						$statistic_complete += ($statistic->order_quantity) * 2;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += ($statistic->order_quantity) * 2;
						} else {
							$statistic_K += ($statistic->order_quantity) * 2;
						}
					}
				}
			}
			if ($statistic->schedule_status) {
				if (in_array($statistic->ord_select, $ultraSound)) {
					$statistic_ultrasound += ($statistic->order_quantity);
				}
				if ($statistic->ord_select == "Đo loãng xương") {
					$statistic_bone += ($statistic->order_quantity);
				}
			}
		}

		$view = view('pages.client.schedule.details.render')->with(compact('orders', 'dayInMonth', 'statistic_complete', 'statistic_cas', 'statistic_ultrasound', 'statistic_bone', 'statistic_35', 'statistic_8', 'statistic_10', 'statistic_N', 'statistic_T', 'statistic_G', 'statistic_A', 'statistic_K'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function updateQuantityDetails(Request $request)
	{
		$data = $request->all();
		$order = Order::findOrFail($request->id);
		$order->order_quantity = $request->order_quantity_details;
		$order->order_send_result = $request->order_send_result;
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

		$accountant = Accountant::where('order_id', $request->id)->first();
		$accountant->accountant_doctor_read = $data['accountant_doctor_read'];
		$accountant->accountant_35X43 = $data['accountant_35X43'];
		$accountant->accountant_polime = $data['accountant_polime'];
		$accountant->accountant_8X10 = $data['accountant_8X10'];
		$accountant->accountant_10X12 = $data['accountant_10X12'];
		$accountant->accountant_note = $data['accountant_note'];
		$accountant->save();

		$history = new HistoryEdit();
		$history->order_id = $request->id;
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Chỉnh sửa lịch chi tiết';
		$history->save();
	}
	//End Details

	//Sales
	public function showScheduleSale()
	{
		$months = [];
		$statistic_complete = 0;
		$statistic_cas = 0;
		$statistic_ultrasound = 0;
		$statistic_bone = 0;
		$statistic_35 = 0;
		$statistic_8 = 0;
		$statistic_10 = 0;
		$statistic_4 = 0;
		$statistic_N = 0;
		$statistic_T = 0;
		$statistic_G = 0;
		$statistic_A = 0;
		$statistic_K = 0;
		$xray1Position = ['Phổi (1 Tư thế)', 'Cột sống thắt lưng (1 Tư thế)', 'Cột sống cổ (1 Tư thế)', 'Vai (1 Tư thế)', 'Gối (1 Tư thế)', 'Khác'];
		$xray2Position = ['Phổi (2 Tư thế)', 'Cột sống thắt lưng (2 Tư thế)', 'Cột sống cổ (2 Tư thế)', 'Vai (2 Tư thế)', 'Gối (2 Tư thế)'];
		$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];
		$firstDayofThisMonth = Carbon::now()->startOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::now()->endOfMonth()->toDateString();
		$currentYear = Carbon::now()->format('Y');
		$currentMonth = Carbon::now()->format('F');
		$dayInMonth = Carbon::now()->daysInMonth;

		$orders = Order::getScheduleDetails($firstDayofThisMonth, $lastDayofThisMonth);
		$statistics = Accountant::getStatistics($firstDayofThisMonth, $lastDayofThisMonth);

		foreach ($statistics as $key => $statistic) {
			if ($statistic->status_id == 2 || $statistic->status_id == 3 || $statistic->status_id == 4) {
				if (!in_array($statistic->ord_select, $ultraSound)) {
					$statistic_cas += $statistic->order_quantity;
					$statistic_35 += $statistic->accountant_35X43;
					$statistic_8 += $statistic->accountant_8X10;
					$statistic_10 += $statistic->accountant_10X12;
					if (in_array($statistic->ord_select, $xray1Position)) {
						$statistic_complete += $statistic->order_quantity;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += $statistic->order_quantity;
						} else {
							$statistic_K += $statistic->order_quantity;
						}
					} elseif (in_array($statistic->ord_select, $xray2Position)) {
						$statistic_complete += ($statistic->order_quantity) * 2;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += ($statistic->order_quantity) * 2;
						} else {
							$statistic_K += ($statistic->order_quantity) * 2;
						}
					}
				}
			}
			if ($statistic->schedule_status) {
				if (in_array($statistic->ord_select, $ultraSound)) {
					$statistic_ultrasound += ($statistic->order_quantity);
				}
				if ($statistic->ord_select == "Đo loãng xương") {
					$statistic_bone += ($statistic->order_quantity);
				}
			}
		}

		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.client.schedule.sales.index')->with(compact('orders', 'currentMonth', 'currentYear', 'months', 'dayInMonth', 'statistic_complete', 'statistic_cas', 'statistic_ultrasound', 'statistic_bone', 'statistic_35', 'statistic_8', 'statistic_10', 'statistic_N', 'statistic_T', 'statistic_G', 'statistic_A', 'statistic_K'));
	}

	public function selectMonthSales(Request $request)
	{
		$data = $request->all();
		$statistic_complete = 0;
		$statistic_cas = 0;
		$statistic_ultrasound = 0;
		$statistic_bone = 0;
		$statistic_35 = 0;
		$statistic_8 = 0;
		$statistic_10 = 0;
		$statistic_4 = 0;
		$statistic_N = 0;
		$statistic_T = 0;
		$statistic_G = 0;
		$statistic_A = 0;
		$statistic_K = 0;
		$xray1Position = ['Phổi (1 Tư thế)', 'Cột sống thắt lưng (1 Tư thế)', 'Cột sống cổ (1 Tư thế)', 'Vai (1 Tư thế)', 'Gối (1 Tư thế)', 'Khác'];
		$xray2Position = ['Phổi (2 Tư thế)', 'Cột sống thắt lưng (2 Tư thế)', 'Cột sống cổ (2 Tư thế)', 'Vai (2 Tư thế)', 'Gối (2 Tư thế)'];
		$ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];

		if ($request->month == 'April') {
			$firstDayofThisMonth = $request->year . '-04-01';
			$lastDayofThisMonth = $request->year . '-04-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'June') {
			$firstDayofThisMonth = $request->year . '-06-01';
			$lastDayofThisMonth = $request->year . '-06-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'September') {
			$firstDayofThisMonth = $request->year . '-09-01';
			$lastDayofThisMonth = $request->year . '-00-30';
			$dayInMonth = 30;
		} elseif ($request->month == 'November') {
			$firstDayofThisMonth = $request->year . '-11-01';
			$lastDayofThisMonth = $request->year . '-11-30';
			$dayInMonth = 30;
		} else {
			$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
			$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
			$dayInMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->daysInMonth;
		}

		$orders = Order::getScheduleDetails($firstDayofThisMonth, $lastDayofThisMonth);
		$statistics = Accountant::getStatistics($firstDayofThisMonth, $lastDayofThisMonth);

		foreach ($statistics as $key => $statistic) {
			if ($statistic->status_id == 2 || $statistic->status_id == 3 || $statistic->status_id == 4) {
				if (!in_array($statistic->ord_select, $ultraSound)) {
					$statistic_cas += $statistic->order_quantity;
					$statistic_35 += $statistic->accountant_35X43;
					$statistic_8 += $statistic->accountant_8X10;
					$statistic_10 += $statistic->accountant_10X12;
					if (in_array($statistic->ord_select, $xray1Position)) {
						$statistic_complete += $statistic->order_quantity;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += $statistic->order_quantity;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += $statistic->order_quantity;
						} else {
							$statistic_K += $statistic->order_quantity;
						}
					} elseif (in_array($statistic->ord_select, $xray2Position)) {
						$statistic_complete += ($statistic->order_quantity) * 2;
						if ($statistic->accountant_doctor_read == 'Nhân') {
							$statistic_N += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Trung') {
							$statistic_T += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Giang') {
							$statistic_G += ($statistic->order_quantity) * 2;
						} elseif ($statistic->accountant_doctor_read == 'Ân') {
							$statistic_A += ($statistic->order_quantity) * 2;
						} else {
							$statistic_K += ($statistic->order_quantity) * 2;
						}
					}
				}
			}
			if ($statistic->schedule_status) {
				if (in_array($statistic->ord_select, $ultraSound)) {
					$statistic_ultrasound += ($statistic->order_quantity);
				}
				if ($statistic->ord_select == "Đo loãng xương") {
					$statistic_bone += ($statistic->order_quantity);
				}
			}
		}

		$view = view('pages.client.schedule.sales.render')->with(compact('orders', 'dayInMonth', 'statistic_complete', 'statistic_cas', 'statistic_ultrasound', 'statistic_bone', 'statistic_35', 'statistic_8', 'statistic_10', 'statistic_N', 'statistic_T', 'statistic_G', 'statistic_A', 'statistic_K'))->render();

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
		$order->status_id = 1;
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
