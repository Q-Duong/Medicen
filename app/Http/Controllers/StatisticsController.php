<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Profile;
use App\Models\Statistic;
use App\Models\User;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function index()
    {
        return view('pages.admin.statistics.index');
    }

    public function performanceAnalysis(Request $request)
    {
        if ($request->month == 'April') {
            $firstDayOfThisMonth = $request->year . '-04-01';
            $lastDayOfThisMonth = $request->year . '-04-30';
        } elseif ($request->month == 'June') {
            $firstDayOfThisMonth = $request->year . '-06-01';
            $lastDayOfThisMonth = $request->year . '-06-30';
        } elseif ($request->month == 'September') {
            $firstDayOfThisMonth = $request->year . '-09-01';
            $lastDayOfThisMonth = $request->year . '-00-30';
        } elseif ($request->month == 'November') {
            $firstDayOfThisMonth = $request->year . '-11-01';
            $lastDayOfThisMonth = $request->year . '-11-30';
        } else {
            $firstDayOfThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
            $lastDayOfThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
        }
        $date = Carbon::parse($firstDayOfThisMonth)->format('m/Y');
        $orders = Order::getScheduleDetails($firstDayOfThisMonth, $lastDayOfThisMonth);
        $getPerformances = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('performance_analysis', 'performance_analysis.order_id', '=', 'orders.id')
            ->select(
                'orders.id',
                'unit_abbreviation',
                'unit_name',
                'ord_start_day',
                'ord_end_day',
                'user_id',
                'part',
                'performance',
                'description',
                'first_edit_time',
                'status'
            )
            ->whereBetween('ord_start_day', [$firstDayOfThisMonth, $lastDayOfThisMonth])
            ->whereBetween('ord_end_day', [$firstDayOfThisMonth, $lastDayOfThisMonth])
            ->orderBy('ord_start_day', 'ASC')
            ->orderBy('unit_abbreviation', 'DESC')
            ->get();

        if ($getPerformances->count() > 0) {
            $totalPerformance =
                [
                    'total' => $getPerformances->count(),
                    'missed' => $getPerformances->where('performance', 0)->count(),
                    'kpi' => number_format((($getPerformances->count() - $getPerformances->where('performance', 0)->count()) / $getPerformances->count() * 100), 0)
                ];

            $data = [
                'date' => $date,
                'orders' => $orders,
                'totalPerformance' => $totalPerformance,
            ];

            $getPerformanceSales = $getPerformances->where('part', 1);
            if ($getPerformanceSales->count() > 0) {
                $totalPerformanceSales =
                    [
                        'missed' => $getPerformanceSales->where('performance', 0)->count(),
                        'data' => $getPerformanceSales->where('performance', 0),
                        'kpi' => number_format((($getPerformances->count() - $getPerformanceSales->where('performance', 0)->count()) / $getPerformances->count() * 100), 0)
                    ];
                $data['totalPerformanceSales'] = $totalPerformanceSales;
            }

            $getPerformanceTechnicians = $getPerformances->where('part', 2);
            if ($getPerformanceTechnicians->count() > 0) {
                $total = $getPerformances->where('part', 2)->count();
                $userId = $getPerformances->where('part', 2)->pluck('user_id')
                    ->unique()
                    ->values();
                foreach ($userId as $key => $id) {
                    $profile = Profile::findOrFail(User::findOrFail($id)->profile_id);
                    $totalPerformanceTechnicians[$key] =
                        [
                            'name' => $profile->profile_firstname . ' ' . $profile->profile_lastname,
                            'total' => $getPerformanceTechnicians->where('user_id', $id)->count(),
                            'missed' => $getPerformanceTechnicians->where('user_id', $id)->where('performance', 0)->count(),
                            'data' => $getPerformanceTechnicians->where('user_id', $id)->where('performance', 0),
                            'kpi' => number_format((($getPerformanceTechnicians->where('user_id', $id)->count() - $getPerformanceTechnicians->where('user_id', $id)->where('performance', 0)->count()) / $getPerformanceTechnicians->where('user_id', $id)->count() * 100), 0)
                        ];
                }
                $data = array_merge($data, [
                    'total' => $total,
                    'totalPerformanceTechnicians' => $totalPerformanceTechnicians,
                ]);
            }

            $getPerformanceResults = $getPerformances->where('part', 3);
            if ($getPerformanceResults->count() > 0) {
                $totalPerformanceResults =
                    [
                        'total' => $getPerformanceResults->count(),
                        'missed' => $getPerformanceResults->where('performance', 0)->count(),
                        'data' => $getPerformanceResults->where('performance', 0),
                        'kpi' => number_format((($getPerformanceResults->count() - $getPerformanceResults->where('performance', 0)->count()) / $getPerformanceResults->count() * 100), 0)
                    ];
                $data['totalPerformanceResults'] = $totalPerformanceResults;
            }

            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true])->loadView('pages.admin.statistics.performance', $data);

            return $pdf->stream('Performance-Analysis.pdf');
        }

        return Redirect()->back()->with('success', 'Không có dữ liệu');
    }
}
