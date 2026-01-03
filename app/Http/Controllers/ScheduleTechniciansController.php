<?php

namespace App\Http\Controllers;

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

		$months = [];
		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.client.schedule.technicians.index', compact(
			'scheduleData',
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
		$view = view('pages.client.schedule.technicians.render', compact(
			'scheduleData',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
		))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function update(Request $request)
	{
		$data = $request->all();
		$order = Order::findOrFail($request->id);
		$order->order_quantity_draft = $data['order_quantity_draft'];
		$order->order_note_ktv = $data['order_note_ktv'];
		$order->save();

		$ordStartDay = OrderDetail::firstWhere('id', $order->order_detail_id)->ord_start_day;

		$now = Carbon::now()->setTime(0, 0, 0);
		$dateEqual = Carbon::parse($ordStartDay);

		if ($now->isBefore($dateEqual) || $now->equalTo($dateEqual)) {
			$performanceScore = 100;
			$description = 'Completed ahead of schedule';
			$status = 1;
		} else {
			$performanceScore = 0;
			$description = 'Missed deadline';
			$status = 0;
		}
		$performanceAnalysis = new PerformanceAnalysis();
		$performanceAnalysis->order_id = $request->id;
		$performanceAnalysis->user_id = Auth::user()->id;
		$performanceAnalysis->part = 2;
		$performanceAnalysis->performance = $performanceScore;
		$performanceAnalysis->description = $description;
		$performanceAnalysis->first_edit_time = $order->updated_at;
		$performanceAnalysis->status = $status;
		$performanceAnalysis->save();

		return response()->json(array('success' => true));
	}
}
