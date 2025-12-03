<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\HistoryEdit;
use App\Models\OrderDetail;
use App\Models\PerformanceAnalysis;
use App\Models\TempFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleResultsController extends Controller
{
	public function index()
	{
		return view('pages.client.schedule.results.index');
	}

	public function get(Request $request)
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

		$html = view('pages.client.schedule.results.index_render')->with(compact('orders', 'months', 'currentMonth', 'currentYear', 'dayInMonth', 'statistic_complete', 'statistic_cas', 'statistic_ultrasound', 'statistic_bone', 'statistic_35', 'statistic_8', 'statistic_10', 'statistic_N', 'statistic_T', 'statistic_G', 'statistic_A', 'statistic_K'))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function suggest(Request $request)
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

	public function search(Request $request)
	{
		$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->firstOfMonth()->toDateString();
		$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->endOfMonth()->toDateString();
		$dayInMonth = Carbon::createFromFormat('M Y', $request->currentTime['month'] . ' ' . $request->currentTime['year'])->daysInMonth;
		$orders = Order::getScheduleDetailsForSearch($firstDayofThisMonth, $lastDayofThisMonth, $request->param);

		$html = view('pages.client.schedule.results.search_render', compact('orders', 'dayInMonth'))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function select(Request $request)
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

		$view = view('pages.client.schedule.results.render')->with(compact('orders', 'dayInMonth', 'statistic_complete', 'statistic_cas', 'statistic_ultrasound', 'statistic_bone', 'statistic_35', 'statistic_8', 'statistic_10', 'statistic_N', 'statistic_T', 'statistic_G', 'statistic_A', 'statistic_K'))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function update(Request $request)
	{
		$data = $request->all();
		$order = Order::findOrFail($request->id);
		$orderUpdatedBeforeUpdate = $order->order_updated;
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

		if ($orderUpdatedBeforeUpdate == 0) {
			$performance = app(StatisticsController::class)->performanceFunction(3, $orderDetail->ord_deadline, $request->ord_delivery_date);
			$performanceAnalysis = new PerformanceAnalysis();
			$performanceAnalysis->order_id = $request->id;
			$performanceAnalysis->user_id = Auth::user()->id;
			$performanceAnalysis->part = 3;
			$performanceAnalysis->performance = $performance['score'];
			$performanceAnalysis->description = $performance['description'];
			$performanceAnalysis->first_edit_time = $order->updated_at;
			$performanceAnalysis->status = $performance['status'];
			$performanceAnalysis->save();
		}

		$history = new HistoryEdit();
		$history->order_id = $request->id;
		$history->user_name = Auth::user()->email;
		$history->history_action = 'Chỉnh sửa lịch chi tiết';
		$history->save();
	}
}
