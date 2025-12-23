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
		// --- 1. XỬ LÝ THỜI GIAN
		if ($request->filled('currentTime')) {
			$date = Carbon::createFromDate($request->currentTime['year'], Carbon::parse($request->currentTime['month'])->month, 1);
		} else {
			$date = Carbon::now();
		}

		$currentYear  = $date->year;
		$currentMonth = $date->format('F');
		$currentMonthNum = $date->month;
		$dayInMonth   = $date->daysInMonth;

		$firstDayOfThisMonth = $date->copy()->startOfMonth()->toDateString();
		$lastDayOfThisMonth  = $date->copy()->endOfMonth()->toDateString();

		// --- 2. LẤY DỮ LIỆU ---
		$rawOrders = Order::getScheduleDetails($firstDayOfThisMonth, $lastDayOfThisMonth);
		$statistics = Accountant::getStatistics($firstDayOfThisMonth, $lastDayOfThisMonth);

		// --- 3. XỬ LÝ GOM NHÓM DỮ LIỆU CHO BLADE (QUAN TRỌNG) ---
		// Cấu trúc đích: $scheduleData[ID_XE][NGÀY_Y-m-d] = Collection các đơn
		$scheduleData = $rawOrders
			->filter(function ($order) {
				return $order->status_id != 0
					&& $order->order_surcharge == 0;
			})
			->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				});
			});

		// --- 4. TÍNH TOÁN THỐNG KÊ ---
		$statsArray = app(ScheduleController::class)->calculateStatistics($statistics);
		extract($statsArray);

		// --- 5. CHUẨN BỊ MẢNG THÁNG ---
		$months = [];
		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		$html = view('pages.client.schedule.results.index_render')->with(compact(
			'scheduleData',
			'months',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
			'statistic_complete',
			'statistic_cas',
			'statistic_ultrasound',
			'statistic_bone',
			'statistic_35',
			'statistic_8',
			'statistic_10',
			'statistic_N',
			'statistic_T',
			'statistic_G',
			'statistic_A',
			'statistic_K'
		))->render();
		return response()->json(['success' => true, 'html' => $html, 'day' => $dayInMonth]);
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
		if ($request->filled('currentTime')) {
			$date = Carbon::createFromDate($request->currentTime['year'], Carbon::parse($request->currentTime['month'])->month, 1);
		} else {
			$date = Carbon::now();
		}

		$currentYear  = $date->year;
		$currentMonth = $date->format('F');
		$currentMonthNum = $date->month;
		$dayInMonth   = $date->daysInMonth;

		$firstDayOfThisMonth = $date->copy()->startOfMonth()->toDateString();
		$lastDayOfThisMonth  = $date->copy()->endOfMonth()->toDateString();

		$rawOrders = Order::getScheduleDetailsForSearch($firstDayOfThisMonth, $lastDayOfThisMonth, $request->param);
		
		$scheduleData = $rawOrders
			->filter(function ($order) {
				return $order->status_id != 0
					&& $order->order_surcharge == 0;
			})
			->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				});
			});

		$html = view('pages.client.schedule.results.search_render', compact(
			'scheduleData',
			'dayInMonth',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
		))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function select(Request $request)
	{
		$date = Carbon::createFromDate($request->year, Carbon::parse($request->month)->month, 1);
		$currentYear  = $date->year;
		$currentMonth = $date->format('F');
		$currentMonthNum = $date->month;
		$dayInMonth   = $date->daysInMonth;

		$firstDayOfThisMonth = $date->copy()->startOfMonth()->toDateString();
		$lastDayOfThisMonth  = $date->copy()->endOfMonth()->toDateString();

		$rawOrders = Order::getScheduleDetails($firstDayOfThisMonth, $lastDayOfThisMonth);
		$statistics = Accountant::getStatistics($firstDayOfThisMonth, $lastDayOfThisMonth);

		$scheduleData = $rawOrders
			->filter(function ($order) {
				return $order->status_id != 0
					&& $order->order_surcharge == 0;
			})
			->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				});
			});

		$statsArray = app(ScheduleController::class)->calculateStatistics($statistics);
		extract($statsArray);

		$view = view('pages.client.schedule.results.render')->with(compact(
			'scheduleData',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
			'statistic_complete',
			'statistic_cas',
			'statistic_ultrasound',
			'statistic_bone',
			'statistic_35',
			'statistic_8',
			'statistic_10',
			'statistic_N',
			'statistic_T',
			'statistic_G',
			'statistic_A',
			'statistic_K'
		))->render();

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
