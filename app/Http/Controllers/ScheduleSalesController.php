<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\CarKTV;
use App\Models\Staff;
use Carbon\Carbon;

class ScheduleSalesController extends Controller
{
	//Client
	public function index()
	{
		$date = Carbon::now()->startOfMonth();
		$currentYear     = $date->year;
		$currentMonth    = $date->format('F');
		$currentMonthNum = $date->month;
		$dayInMonth      = $date->daysInMonth;

		$firstDayOfThisMonth = $date->toDateString();
		$lastDayOfThisMonth  = $date->copy()->endOfMonth()->toDateString();

		$rawOrders = Order::getScheduleDetails($firstDayOfThisMonth, $lastDayOfThisMonth);
		$statistics = Accountant::getStatistics($firstDayOfThisMonth, $lastDayOfThisMonth);

		$scheduleData = $rawOrders
			->filter(function ($order) {
				return $order->status_id != 0;
			})
			->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				});
			});

		$statsArray = app(ScheduleController::class)->calculateStatistics($statistics);
		extract($statsArray);

		$months = [];
		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.client.schedule.sales.index')->with(compact(
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

		$rawOrders = Order::getScheduleDetails($firstDayOfThisMonth, $lastDayOfThisMonth);
		$statistics = Accountant::getStatistics($firstDayOfThisMonth, $lastDayOfThisMonth);

		$scheduleData = $rawOrders
			->filter(function ($order) {
				return $order->status_id != 0;
			})
			->groupBy('car_name')
			->map(function ($ordersByCar) {
				return $ordersByCar->groupBy(function ($item) {
					return Carbon::parse($item->ord_start_day)->format('Y-m-d');
				});
			});

		$statsArray = app(ScheduleController::class)->calculateStatistics($statistics);
		extract($statsArray);

		$view = view('pages.client.schedule.sales.render')->with(compact(
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
		// $data = $request->all();
		// foreach ($request->car_name as $key => $car_name) {
		// 	$car = new CarKTV();
		// 	$driver[$key] = explode("_", $request->car_driver_name[$key]);
		// 	$name_driver[$key] = array_shift($driver[$key]);
		// 	$phone_driver[$key] = array_pop($driver[$key]);

		// 	$ktv1[$key] = explode("_", $request->car_ktv_name_1[$key]);
		// 	$name_ktv1[$key] = array_shift($ktv1[$key]);
		// 	$phone_ktv1[$key] = array_pop($ktv1[$key]);

		// 	$ktv2[$key] = explode("_", $request->car_ktv_name_2[$key]);
		// 	$name_ktv2[$key] = array_shift($ktv2[$key]);
		// 	$phone_ktv2[$key] = array_pop($ktv2[$key]);

		// 	$car->order_id = $data['order_id'];
		// 	$car->car_name = $request->car_name[$key];
		// 	$car->car_active = $request->car_active[$key];
		// 	$car->car_driver_name = $name_driver[$key];
		// 	$car->car_driver_phone = $phone_driver[$key];
		// 	$car->car_ktv_name_1 = $name_ktv1[$key];
		// 	$car->car_ktv_phone_1 = $phone_ktv1[$key];
		// 	$car->car_ktv_name_2 = $name_ktv2[$key];
		// 	$car->car_ktv_phone_2 = $phone_ktv2[$key];
		// 	$car->save();
		// }

		$driver = explode("_", $request->car_driver_name);
		$ktv1 = explode("_", $request->car_ktv_name_1);
		$ktv2 = explode("_", $request->car_ktv_name_2);

		$car = new CarKTV();
		$car->order_id = $request->order_id;
		$car->car_name = $request->car_name;
		$car->car_active = 1;
		$car->car_driver_name = array_shift($driver);
		$car->car_driver_phone = array_pop($driver);
		$car->car_ktv_name_1 = empty($request->car_ktv_name_1) ? null : array_shift($ktv1);
		$car->car_ktv_phone_1 = empty($request->car_ktv_name_1) ? null : array_pop($ktv1);
		$car->car_ktv_name_2 = empty($request->car_ktv_name_2) ? null : array_shift($ktv2);
		$car->car_ktv_phone_2 = empty($request->car_ktv_name_2) ? null : array_pop($ktv2);
		$car->save();

		$order = Order::findOrFail($request->order_id);
		$order->schedule_status = 1;
		$order->status_id = 1;
		$order->save();

		if (!empty($request->zalo)) {

			if ($car->car_driver_phone) {
				app(ZaloController::class)->notificationSchedule($car, 'drv');
			}
			if ($car->car_ktv_phone_1) {
				app(ZaloController::class)->notificationSchedule($car, 'kt1');
			}
			if ($car->car_ktv_phone_2) {
				app(ZaloController::class)->notificationSchedule($car, 'kt2');
			}

			$message = 'Thêm lịch gửi Zalo thành công';
		}
		return Redirect::route('order.index')->with('success', $message);
	}

	public function edit($order_id)
	{
		$order = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->where('orders.id', $order_id)
			->select(['order_details.ord_start_day'])
			->first();
		$getAllStaff = Staff::orderBy('id', 'ASC')->get();
		$car = CarKTV::firstWhere('order_id', $order_id);
		return view('pages.admin.order.schedule.edit', compact('getAllStaff', 'order', 'order_id', 'car'));
	}

	public function update(Request $request, $order_id)
	{
		$message = 'Cập nhật lịch xe không gửi Zalo thành công';

		$driver = explode("_", $request->car_driver_name);
		$ktv1 = explode("_", $request->car_ktv_name_1);
		$ktv2 = explode("_", $request->car_ktv_name_2);

		$car = CarKTV::firstWhere('order_id', $order_id);
		$car->car_name = $request->car_name;
		$car->car_active = 1;
		$car->car_driver_name = array_shift($driver);
		$car->car_driver_phone = array_pop($driver);
		$car->car_ktv_name_1 = empty($request->car_ktv_name_1) ? null : array_shift($ktv1);
		$car->car_ktv_phone_1 = empty($request->car_ktv_name_1) ? null : array_pop($ktv1);
		$car->car_ktv_name_2 = empty($request->car_ktv_name_2) ? null : array_shift($ktv2);
		$car->car_ktv_phone_2 = empty($request->car_ktv_name_2) ? null : array_pop($ktv2);
		$car->save();

		if (!empty($request->zalo)) {
			if ($car->car_driver_phone) {
				app(ZaloController::class)->notificationSchedule($car, 'drv');
			}
			if ($car->car_ktv_phone_1) {
				app(ZaloController::class)->notificationSchedule($car, 'kt1');
			}
			if ($car->car_ktv_phone_2) {
				app(ZaloController::class)->notificationSchedule($car, 'kt2');
			}
			$message = 'Cập nhật lịch xe gửi Zalo thành công';
		}
		return Redirect::route('order.index')->with('success', $message);
	}

	public function cancel(Request $request)
	{
		$car = CarKTV::firstWhere('order_id', $request->order_id);
		if ($car->car_driver_phone) {
			app(ZaloController::class)->notificationScheduleCancel($car, 'drv');
		}
		if ($car->car_ktv_phone_1) {
			app(ZaloController::class)->notificationScheduleCancel($car, 'kt1');
		}
		if ($car->car_ktv_phone_2) {
			app(ZaloController::class)->notificationScheduleCancel($car, 'kt2');
		}
		$car->car_name = null;
		$car->car_active = 0;
		$car->car_driver_name = null;
		$car->car_driver_phone = null;
		$car->car_ktv_name_1 = null;
		$car->car_ktv_phone_1 = null;
		$car->car_ktv_name_2 = null;
		$car->car_ktv_phone_2 = null;
		$car->save();
		return response()->json(['success' => 'Huỷ lịch thành công.']);
	}
}
