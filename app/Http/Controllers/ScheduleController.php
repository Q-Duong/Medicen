<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
	public function showLogin()
	{
		return view('pages.client.schedule.login');
	}

	public function login(Request $request)
	{
		$data = $request->all();
		if (Auth::attempt([
			'email' => $data['email'],
			'password' => $data['password'],
		], true)) {
			switch (Auth::user()->name) {
				case ('Admin'):
					$route = 'schedules.results.index';
					break;
				case ('Sale'):
					$route = 'schedules.sales.index';
					break;
				case ('Technician'):
					$route = 'schedules.technicians.index';
					break;
				case ('Office'):
					$route = 'schedules.results.index';
					break;
				default:
					$route = 'dashboard.index';
					break;
			}
			return Redirect::route($route);
		} else {
			return Redirect::route('schedules.login')->with('error', 'Mật khẩu hoặc tài khoản bị sai.Vui lòng nhập lại');
		}
	}

	//Drivers
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

		return view('pages.client.schedule.drivers.index', compact(
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
		$view = view('pages.client.schedule.drivers.render', compact(
			'scheduleData',
			'currentMonth',
			'currentMonthNum',
			'currentYear',
			'dayInMonth',
		))->render();

		return response()->json(array('success' => true, 'html' => $view, 'day' => $dayInMonth));
	}

	public function calculateStatistics($statistics)
	{
		$stats = [
			'statistic_complete' => 0,
			'statistic_cas'      => 0,
			'statistic_ultrasound' => 0,
			'statistic_bone'     => 0,
			'statistic_mammogram'=> 0,
			'statistic_35'       => 0,
			'statistic_8'        => 0,
			'statistic_10'       => 0,
			'statistic_N'        => 0,
			'statistic_T'        => 0,
			'statistic_G'        => 0,
			'statistic_A'        => 0,
			'statistic_K'        => 0,
		];

		$xray1Position = ['Phổi (1 Tư thế)', 'Cột sống thắt lưng (1 Tư thế)', 'Cột sống cổ (1 Tư thế)', 'Vai (1 Tư thế)', 'Gối (1 Tư thế)', 'Khác'];
		$xray2Position = ['Phổi (2 Tư thế)', 'Cột sống thắt lưng (2 Tư thế)', 'Cột sống cổ (2 Tư thế)', 'Vai (2 Tư thế)', 'Gối (2 Tư thế)'];
		$ultraSound    = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];

		foreach ($statistics as $statistic) {
			if (in_array($statistic->status_id, [2, 3, 4]) && !in_array($statistic->ord_select, $ultraSound)) {
				$stats['statistic_cas'] += $statistic->order_quantity;
				$stats['statistic_35']  += $statistic->accountant_35X43;
				$stats['statistic_8']   += $statistic->accountant_8X10;
				$stats['statistic_10']  += $statistic->accountant_10X12;

				$multiplier = 0;
				if (in_array($statistic->ord_select, $xray1Position)) {
					$multiplier = 1;
				} elseif (in_array($statistic->ord_select, $xray2Position)) {
					$multiplier = 2;
				}

				if ($multiplier > 0) {
					$quantityToAdd = $statistic->order_quantity * $multiplier;
					$stats['statistic_complete'] += $quantityToAdd;

					switch ($statistic->accountant_doctor_read) {
						case 'Nhân':
							$stats['statistic_N'] += $quantityToAdd;
							break;
						case 'Trung':
							$stats['statistic_T'] += $quantityToAdd;
							break;
						case 'Giang':
							$stats['statistic_G'] += $quantityToAdd;
							break;
						case 'Ân':
							$stats['statistic_A'] += $quantityToAdd;
							break;
						default:
							$stats['statistic_K'] += $quantityToAdd;
							break;
					}
				}
			}

			if ($statistic->schedule_status) {
				if (in_array($statistic->ord_select, $ultraSound)) {
					$stats['statistic_ultrasound'] += $statistic->order_quantity;
				}
				if ($statistic->ord_select == "Đo loãng xương") {
					$stats['statistic_bone'] += $statistic->order_quantity;
				}
				if ($statistic->ord_select == "Nhũ Ảnh") {
					$stats['statistic_mammogram'] += $statistic->order_quantity;
				}
			}
		}
		return $stats;
	}
}
