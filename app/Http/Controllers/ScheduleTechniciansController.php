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

		return view('pages.client.schedule.technicians.index', compact('orders', 'months', 'dayInMonth', 'currentMonth', 'currentYear'));
	}

	public function select(Request $request)
	{
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
		$view = view('pages.client.schedule.technicians.render', compact('orders', 'dayInMonth'))->render();

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
