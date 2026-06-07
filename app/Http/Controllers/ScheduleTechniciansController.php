<?php

namespace App\Http\Controllers;

use App\Models\CarKTV;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PerformanceAnalysis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleTechniciansController extends Controller
{
	public function index()
	{
		$date = Carbon::now()->startOfMonth();
		$currentYear     = $date->year;
		$currentMonth    = $date->format('F');
		$currentMonthNum = $date->month;
		$dayInMonth      = $date->daysInMonth;

		$firstDayOfThisMonth = $date->toDateString();
		$lastDayOfThisMonth  = $date->copy()->endOfMonth()->toDateString();

		$rawOrders = Order::getScheduleTechnologist($firstDayOfThisMonth, $lastDayOfThisMonth);

		$filteredOrders = $rawOrders->filter(function ($order) {
			return $order->status_id != 0 && $order->order_surcharge == 0;
		});

		$scheduleData = $filteredOrders->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				});
			});

		$technicianData = $filteredOrders
			->flatMap(function ($order) {
				return [$order->car_ktv_name_1, $order->car_ktv_name_2];
			})
			->filter()
			->unique()
			->values();

		$months = [];
		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.client.schedule.technicians.index', compact(
			'scheduleData',
			'technicianData',
			'months',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
		));
	}

	public function select(Request $request)
	{
		$monthNum = Carbon::parse('1 ' . $request->month)->month;
		$date = Carbon::createFromDate($request->year, $monthNum, 1);
		$currentYear     = $date->year;
		$currentMonth    = $date->format('F');
		$currentMonthNum = $date->month;
		$dayInMonth      = $date->daysInMonth;

		$firstDayOfThisMonth = $date->copy()->startOfMonth()->toDateString();
		$lastDayOfThisMonth  = $date->copy()->endOfMonth()->toDateString();

		$rawOrders = Order::getScheduleTechnologist($firstDayOfThisMonth, $lastDayOfThisMonth);

		$filteredOrders = $rawOrders->filter(function ($order) {
			return $order->status_id != 0 && $order->order_surcharge == 0;
		});

		$scheduleData = $filteredOrders->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				});
			});

		$technicianData = $filteredOrders
			->flatMap(function ($order) {
				return [$order->car_ktv_name_1, $order->car_ktv_name_2];
			})
			->filter()
			->unique()
			->values();
		$view = view('pages.client.schedule.technicians.render', compact(
			'scheduleData',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
		))->render();

		return response()->json(array('success' => true, 'html' => $view, 'technicianData' => $technicianData, 'day' => $dayInMonth));
	}

	public function selectTechnician(Request $request)
	{
		$monthNum = Carbon::parse('1 ' . $request->month)->month;
		$date = Carbon::createFromDate($request->year, $monthNum, 1);
		$currentYear     = $date->year;
		$currentMonth    = $date->format('F');
		$currentMonthNum = $date->month;
		$dayInMonth      = $date->daysInMonth;

		$firstDayOfThisMonth = $date->copy()->startOfMonth()->toDateString();
		$lastDayOfThisMonth  = $date->copy()->endOfMonth()->toDateString();
		if ($request->param == 'all') {
			$rawOrders = Order::getScheduleTechnologist($firstDayOfThisMonth, $lastDayOfThisMonth);
		} else {
			$rawOrders = Order::getScheduleTechnicianSearch($firstDayOfThisMonth, $lastDayOfThisMonth, $request->param);
		}

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

		$html = view('pages.client.schedule.technicians.render', compact(
			'scheduleData',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
		))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	public function update(Request $request)
	{
		$data = $request->all();

		// 1. Cập nhật Order
		$order = Order::findOrFail($request->id);
		$order->order_quantity_draft = $data['order_quantity_draft'];
		$order->order_note_ktv = $data['order_note_ktv'];
		$order->save();

		// 2. Cập nhật CarKTV
		$carKTV = CarKTV::where('order_id', $order->id)->first();
		if ($carKTV) {
			$carKTV->driver_assistance = $data['driver_assistance'];
			$carKTV->work_over_250 = $data['work_over_250'];
			$carKTV->save();
		}

		// 3. Kiểm tra xem user hiện tại đã có KPI cho order này (part 2) chưa
		if (Auth::user()->role != 0) {
			$userId = Auth::id();
			$kpiExists = PerformanceAnalysis::where('order_id', $order->id)
				->where('part', 2)
				->where('user_id', $userId) // Đã thêm check user_id
				->exists();

			if (!$kpiExists) {
				$ordStartDay = OrderDetail::where('id', $order->order_detail_id)->value('ord_start_day');

				$now = Carbon::now()->startOfDay();
				$dateEqual = Carbon::parse($ordStartDay)->startOfDay();

				if ($now->lte($dateEqual)) {
					$performanceScore = 100;
					$description = 'Completed ahead of schedule';
					$status = 1;
				} else {
					$performanceScore = 0;
					$description = 'Missed deadline';
					$status = 0;
				}

				$performanceAnalysis = new PerformanceAnalysis();
				$performanceAnalysis->order_id = $order->id;
				$performanceAnalysis->user_id = $userId;
				$performanceAnalysis->part = 2;
				$performanceAnalysis->performance = $performanceScore;
				$performanceAnalysis->description = $description;
				$performanceAnalysis->first_edit_time = $order->updated_at;
				$performanceAnalysis->status = $status;
				$performanceAnalysis->save();
			}
		}


		return response()->json(['success' => true]);
	}
}
