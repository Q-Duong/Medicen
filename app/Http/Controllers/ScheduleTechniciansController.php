<?php

namespace App\Http\Controllers;

use App\Models\CarKTV;
use App\Services\Schedule\ActualCases;
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
				})->map(function ($ordersByDate) {
					return $ordersByDate->values()->map(function ($order, $index) {
						$order->stt = $index + 1;
						return $order;
					});
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
				})->map(function ($ordersByDate) {
					return $ordersByDate->values()->map(function ($order, $index) {
						$order->stt = $index + 1;
						return $order;
					});
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

		$filteredOrders = $rawOrders->filter(function ($order) {
			return $order->status_id != 0 && $order->order_surcharge == 0;
		});

		$scheduleData = $filteredOrders->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				})->map(function ($ordersByDate) {
					return $ordersByDate->values()->map(function ($order, $index) {
						$order->stt = $index + 1;
						return $order;
					});
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
		// Ghi số ca thực tế + KPI phần 2: logic nằm ở App\Services\Schedule\ActualCases (trang Xếp lịch xe dùng chung)
		$order = Order::findOrFail($request->id);
		if ($order->isClosed()) {
			return response()->json(['success' => false, 'message' => Order::closedMessage($order->id)], 422);
		}
		app(ActualCases::class)->save($order, $request->all(), Auth::user());

		return response()->json(['success' => true]);
	}
}
