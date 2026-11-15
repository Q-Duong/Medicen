<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\CarKTV;
use App\Services\Schedule\ScheduleCancel;
use App\Services\Schedule\ScheduleHistory;
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

		$driverData = $filteredOrders
			->flatMap(function ($order) {
				return [$order->car_driver_name];
			})
			->filter()
			->unique()
			->values();

		$technicianData = $filteredOrders
			->flatMap(function ($order) {
				return [$order->car_ktv_name_1, $order->car_ktv_name_2];
			})
			->filter()
			->unique()
			->values();

		$statsArray = app(ScheduleController::class)->calculateStatistics($statistics);
		extract($statsArray);

		$months = [];
		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}

		return view('pages.client.schedule.sales.index')->with(compact(
			'scheduleData',
			'driverData',
			'technicianData',
			'months',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
			'statistic_complete',
			'statistic_cas',
			'statistic_ultrasound',
			'statistic_bone',
			'statistic_mammogram',
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

		$driverData = $filteredOrders
			->flatMap(function ($order) {
				return [$order->car_driver_name];
			})
			->filter()
			->unique()
			->values();

		$technicianData = $filteredOrders
			->flatMap(function ($order) {
				return [$order->car_ktv_name_1, $order->car_ktv_name_2];
			})
			->filter()
			->unique()
			->values();

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
			'statistic_mammogram',
			'statistic_35',
			'statistic_8',
			'statistic_10',
			'statistic_N',
			'statistic_T',
			'statistic_G',
			'statistic_A',
			'statistic_K'
		))->render();

		return response()->json(array('success' => true, 'html' => $view, 'driverData' => $driverData, 'technicianData' => $technicianData, 'day' => $dayInMonth));
	}

	public function selectDriver(Request $request)
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
			$rawOrders = Order::getScheduleDriverSearch($firstDayOfThisMonth, $lastDayOfThisMonth, $request->param);
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

		$html = view('pages.client.schedule.sales.render', compact(
			'scheduleData',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
		))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
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

		$html = view('pages.client.schedule.sales.render', compact(
			'scheduleData',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
		))->render();

		return response()->json(array('success' => true, 'html' => $html, 'day' => $dayInMonth));
	}

	//Admin
	public function create($order_id)
	{
		if ($msg = Order::closedMessage($order_id)) {
			return Redirect::route('order.index')->with('error', $msg);
		}
		$getAllStaff = Staff::orderBy('id', 'ASC')->get();
		$order = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->where('orders.id', $order_id)
			->select(['order_details.ord_start_day'])
			->first();
		return view('pages.admin.order.schedule.create', compact('getAllStaff', 'order', 'order_id'));
	}

	public function store(Request $request)
	{
		$message = 'Thêm lịch thành công';
		if ($msg = Order::closedMessage($request->order_id)) {
			return Redirect::route('order.index')->with('error', $msg);
		}
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

		// Đơn từng bị hủy lịch kiểu cũ có thể còn dòng car_ktvs đã tắt: dùng lại, không tạo trùng
		$car = CarKTV::firstOrNew(['order_id' => $request->order_id]);
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
		ScheduleHistory::record((int) $request->order_id, 'Xếp lịch', $car, 'trang đơn hàng');

		return Redirect::route('order.index')->with('success', $message);
	}

	public function edit($order_id)
	{
		if ($msg = Order::closedMessage($order_id)) {
			return Redirect::route('order.index')->with('error', $msg);
		}
		$order = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
			->where('orders.id', $order_id)
			->select(['order_details.ord_start_day'])
			->first();
		$getAllStaff = Staff::orderBy('id', 'ASC')->get();
		$car = CarKTV::where('order_id', $order_id)->first();
		// Đã hủy lịch (không còn dòng lịch): chuyển sang thêm lịch mới
		if (!$car) {
			return Redirect::route('schedule.create', $order_id);
		}
		return view('pages.admin.order.schedule.edit', compact('getAllStaff', 'order', 'order_id', 'car'));
	}

	public function update(Request $request, $order_id)
	{
		if ($msg = Order::closedMessage($order_id)) {
			return Redirect::route('order.index')->with('error', $msg);
		}
		$message = 'Cập nhật lịch xe thành công';

		$driver = explode("_", $request->car_driver_name);
		$ktv1 = explode("_", $request->car_ktv_name_1);
		$ktv2 = explode("_", $request->car_ktv_name_2);

		$car = CarKTV::firstWhere('order_id', $order_id);
		$before = $car->car_active ? ScheduleHistory::describe($car) : null;
		$car->car_name = $request->car_name;
		$car->car_active = 1;
		$car->car_driver_name = array_shift($driver);
		$car->car_driver_phone = array_pop($driver);
		$car->car_ktv_name_1 = empty($request->car_ktv_name_1) ? null : array_shift($ktv1);
		$car->car_ktv_phone_1 = empty($request->car_ktv_name_1) ? null : array_pop($ktv1);
		$car->car_ktv_name_2 = empty($request->car_ktv_name_2) ? null : array_shift($ktv2);
		$car->car_ktv_phone_2 = empty($request->car_ktv_name_2) ? null : array_pop($ktv2);
		$car->save();

		Order::whereKey($order_id)->update(['schedule_status' => 1]);
		ScheduleHistory::record((int) $order_id, $before ? 'Sửa lịch' : 'Xếp lịch', $car, 'trang đơn hàng', $before);
		return Redirect::route('order.index')->with('success', $message);
	}

	/** Hủy lịch (nút Huỷ Lịch ở trang sửa lịch): đơn về "Chưa lên lịch", không gửi Zalo */
	public function cancel(Request $request)
	{
		$order = Order::findOrFail($request->order_id);
		if ($order->isClosed()) {
			return response()->json(['success' => false, 'message' => Order::closedMessage($order->id)], 422);
		}
		app(ScheduleCancel::class)->cancel($order, 'nút Huỷ lịch, trang đơn hàng');

		return response()->json(['success' => 'Huỷ lịch thành công, đơn chuyển về Chưa lên lịch.']);
	}
}
