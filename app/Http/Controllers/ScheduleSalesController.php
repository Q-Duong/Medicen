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

		$view = view('pages.client.schedule.sales.render')->with(compact('orders', 'dayInMonth', 'statistic_complete', 'statistic_cas', 'statistic_ultrasound', 'statistic_bone', 'statistic_35', 'statistic_8', 'statistic_10', 'statistic_N', 'statistic_T', 'statistic_G', 'statistic_A', 'statistic_K'))->render();

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
