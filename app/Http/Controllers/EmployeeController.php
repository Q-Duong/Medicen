<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Exports\TechnicianWorkloadExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('pages.admin.employee.index');
    }

    public function export(Request $request)
    {
        if ($request->month == 'April') {
            $startDate = $request->year . '-04-01';
            $endDate = $request->year . '-04-30';
        } elseif ($request->month == 'June') {
            $startDate = $request->year . '-06-01';
            $endDate = $request->year . '-06-30';
        } elseif ($request->month == 'September') {
            $startDate = $request->year . '-09-01';
            $endDate = $request->year . '-00-30';
        } elseif ($request->month == 'November') {
            $startDate = $request->year . '-11-01';
            $endDate = $request->year . '-11-30';
        } else {
            $startDate = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
            $endDate = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
        }

        // 2. Lấy dữ liệu thô từ Database
        $orders = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->whereBetween('order_details.ord_start_day', [$startDate, $endDate])
            ->where('car_ktvs.car_active', 1)
            ->where('orders.order_surcharge', 0)
            ->select(
                'car_ktvs.car_ktv_name_1', 
                'car_ktvs.car_ktv_name_2', 
                'car_ktvs.car_driver_name', 
                'orders.order_quantity'
            )
            ->get();

        // 3. Xử lý logic gộp và cộng dồn
        $summary = [];

        foreach ($orders as $order) {
            // Lấy danh sách tên không trống từ 3 cột
            $names = array_filter([
                $order->car_ktv_name_1, 
                $order->car_ktv_name_2, 
                $order->car_driver_name
            ]);

            // Mỗi nhân viên xuất hiện trong đơn này đều được cộng quantity
            foreach ($names as $name) {
                $name = trim($name);
                if (!isset($summary[$name])) {
                    $summary[$name] = 0;
                }
                $summary[$name] += $order->order_quantity;
            }
        }

        // 4. Chuyển đổi mảng summary thành Collection để Export
        $finalData = collect($summary)->map(function ($quantity, $name) {
            return [
                'name' => $name,
                'quantity' => $quantity
            ];
        })->values();

        // 5. Xuất File
        $fileName = "Bao_cao_nhan_vien.xlsx";
        return Excel::download(new TechnicianWorkloadExport($finalData), $fileName);
    }
}
