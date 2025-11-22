<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Statistic;
use Carbon\Carbon;


class StatisticsController extends Controller
{
    public function revenueByDate(Request $request)
    {
        $data = $request->all();
        $from_date = $data['from_date'];
        $to_date = $data['to_date'];
        $from_date_format = Date('d/m/Y', strtotime($data['from_date']));
        $to_date_format = Date('d/m/Y', strtotime($data['to_date']));
        $total = 0;
        $total_orders = 0;
        $getStatistics = Statistic::whereBetween('order_date', [$from_date, $to_date])->orderBy('order_date', 'ASC')->get();
        if (count($getStatistics) > 0) {
            foreach ($getStatistics as $key => $val) {
                $total += $val->sales;
                $total_orders += $val->total_order;
                $chart_data[] = array(
                    'period' =>  date('d/m/Y', strtotime($val->order_date)),
                    'order' => $val->total_order,
                    'sales' => $val->sales,
                    'quantity' => $val->quantity,
                );
            }
            $chart = $chart_data;
            return response()->json(array('success' => true, 'chart' => $chart, 'total' => $total, 'total_orders' => $total_orders, 'from_date' => $from_date_format, 'to_date' => $to_date_format));
        } else {
            return response()->json(array('success' => false));
        }
    }

    public function revenueByUnit(Request $request)
    {
        $data = $request->all();
        $unit_id = $data['unit_id'];
        $total = 0;
        $getStatistics = Order::where('unit_id', $unit_id)->orderBy('created_at', 'ASC')->get();
        if (count($getStatistics) > 0) {
            foreach ($getStatistics as $key => $val) {
                $total += $val->order_price;
                $chart_data[] = array(
                    'period' => $val->created_at->format('d/m/Y'),
                    'quantity' => $val->order_quantity,
                    'sales' => $val->order_price,
                    'total' => $total,
                );
            }
            $chart = $chart_data;
            return response()->json(array('success' => true, 'chart' => $chart, 'total' => $total, 'total_orders' => count($getStatistics)));
        } else {
            return response()->json(array('success' => false));
        }
    }

    public function optionalRevenue(Request $request)
    {
        $data = $request->all();
        $firstThisMonth = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();
        $earlyLastMonth = Carbon::now('Asia/Ho_Chi_Minh')->subMonth()->startOfMonth()->toDateString();
        $endOfLastMonth = Carbon::now('Asia/Ho_Chi_Minh')->subMonth()->endOfMonth()->toDateString();
        $sub7days = Carbon::now('Asia/Ho_Chi_Minh')->subDays(7)->toDateString();
        $sub365days = Carbon::now('Asia/Ho_Chi_Minh')->subDays(365)->toDateString();
        $total = 0;
        $total_orders = 0;
        $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();

        if ($data['optional_revenue'] == '7ngay') {
            $getStatistics = Statistic::whereBetween('order_date', [$sub7days, $now])->orderBy('order_date', 'ASC')->get();
            $option = '7 Ngày qua';
        } elseif ($data['optional_revenue'] == 'thangtruoc') {
            $getStatistics = Statistic::whereBetween('order_date', [$earlyLastMonth, $endOfLastMonth])->orderBy('order_date', 'ASC')->get();
            $option = 'Tháng trước';
        } elseif ($data['optional_revenue'] == 'thangnay') {
            $getStatistics = Statistic::whereBetween('order_date', [$firstThisMonth, $now])->orderBy('order_date', 'ASC')->get();
            $option = 'Tháng này';
        } else {
            $getStatistics = Statistic::whereBetween('order_date', [$sub365days, $now])->orderBy('order_date', 'ASC')->get();
            $option = 'Năm nay';
        }

        if (count($getStatistics) > 0) {
            foreach ($getStatistics as $key => $val) {
                $total += $val->sales;
                $total_orders += $val->total_order;
                $chart_data[] = array(
                    'period' =>  date('d/m/Y', strtotime($val->order_date)),
                    'order' => $val->total_order,
                    'sales' => $val->sales,
                    'quantity' => $val->quantity,
                );
            }
            $chart = $chart_data;
            return response()->json(array('success' => true, 'chart' => $chart, 'total' => $total, 'total_orders' => $total_orders, 'option' => $option));
        } else {
            return response()->json(array('success' => false));
        }
    }

    public function revenueForTheMonth()
    {
        $sub30days = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();
        $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
        $month = Carbon::now('Asia/Ho_Chi_Minh')->format('m');
        $total = 0;
        $total_orders = 0;
        $getStatistics = Statistic::whereBetween('order_date', [$sub30days, $now])->orderBy('order_date', 'ASC')->get();
        if (count($getStatistics) > 0) {
            foreach ($getStatistics as $key => $val) {
                $total += $val->sales;
                $total_orders += $val->total_order;
                $chart_data[] = array(
                    'period' => date('d/m/Y', strtotime($val->order_date)),
                    'order' => $val->total_order,
                    'sales' => $val->sales,
                    'quantity' => $val->quantity,
                );
            }
            $chart = $chart_data;
            return response()->json(array('success' => true, 'chart' => $chart, 'total' => $total, 'total_orders' => $total_orders, 'month' => $month));
        } else {
            return response()->json(array('success' => false));
        }
    }

    public function performanceFunction($part, $deadline, $delivery_date)
    {
        $now = Carbon::now()->setTime(0, 0, 0);
        $deadline = Carbon::parse($deadline);
        $dateEqual = $now;

        if ($delivery_date != null) {
            $deliveryDate = Carbon::parse($delivery_date);

            if ($deliveryDate->isAfter($now) || $deliveryDate->equalTo($now)) {
                $dateEqual = $deliveryDate;
            }
        } 

        if ($dateEqual->isBefore($deadline) || $dateEqual->equalTo($deadline)) {
            $performanceScore = 100;
            $description = 'Completed ahead of schedule';
            $status = 1;
        } else {
            $performanceScore = 0;
            $description = 'Missed deadline';
            $status = 0;
        }

        $response = ['score' => $performanceScore, 'description' => $description, 'status' => $status];
        return $response;
    }

    public function performanceAnalysis(Request $request)
    {


        $data = $request->all();
        $statistic_complete = 0;
        $statistic_cas = 0;

        // if ($request->month == 'April') {
        // 	$firstDayofThisMonth = $request->year . '-04-01';
        // 	$lastDayofThisMonth = $request->year . '-04-30';
        // 	$dayInMonth = 30;
        // } elseif ($request->month == 'June') {
        // 	$firstDayofThisMonth = $request->year . '-06-01';
        // 	$lastDayofThisMonth = $request->year . '-06-30';
        // 	$dayInMonth = 30;
        // } elseif ($request->month == 'September') {
        // 	$firstDayofThisMonth = $request->year . '-09-01';
        // 	$lastDayofThisMonth = $request->year . '-00-30';
        // 	$dayInMonth = 30;
        // } elseif ($request->month == 'November') {
        // 	$firstDayofThisMonth = $request->year . '-11-01';
        // 	$lastDayofThisMonth = $request->year . '-11-30';
        // 	$dayInMonth = 30;
        // } else {
        // 	$firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
        // 	$lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
        // 	$dayInMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->daysInMonth;
        // }
        $orders = Order::getScheduleDetails('2025-10-01', '2025-10-31');



        return view('pages.admin.statistics.index')->with(compact('orders'));
    }
}
