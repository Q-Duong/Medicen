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

    // public function export(Request $request)
    // {
    //     if ($request->month == 'April') {
    //         $startDate = $request->year . '-04-01';
    //         $endDate = $request->year . '-04-30';
    //     } elseif ($request->month == 'June') {
    //         $startDate = $request->year . '-06-01';
    //         $endDate = $request->year . '-06-30';
    //     } elseif ($request->month == 'September') {
    //         $startDate = $request->year . '-09-01';
    //         $endDate = $request->year . '-00-30';
    //     } elseif ($request->month == 'November') {
    //         $startDate = $request->year . '-11-01';
    //         $endDate = $request->year . '-11-30';
    //     } else {
    //         $startDate = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
    //         $endDate = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
    //     }

    //     // 2. Lấy dữ liệu thô từ Database
    //     $orders = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
    //         ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
    //         ->whereBetween('order_details.ord_start_day', [$startDate, $endDate])
    //         ->where('car_ktvs.car_active', 1)
    //         ->where('orders.order_surcharge', 0)
    //         ->select(
    //             'car_ktvs.car_ktv_name_1', 
    //             'car_ktvs.car_ktv_name_2', 
    //             'car_ktvs.car_driver_name',
    //             'car_ktvs.driver_assistance',
    //             'car_ktvs.overnight',
    //             'car_ktvs.work_over_250', 
    //             'orders.order_quantity_draft'
    //         )
    //         ->get();

    //     // 3. Xử lý logic gộp và cộng dồn
    //     $summary = [];

    //     foreach ($orders as $order) {
    //         // Lấy danh sách tên không trống từ 3 cột
    //         $names = array_filter([
    //             $order->car_ktv_name_1, 
    //             $order->car_ktv_name_2, 
    //             $order->car_driver_name,
    //             $order->driver_assistance,
    //             $order->overnight,
    //             $order->work_over_250,
    //         ]);

    //         // Mỗi nhân viên xuất hiện trong đơn này đều được cộng quantity
    //         foreach ($names as $name) {
    //             $name = trim($name);
    //             if (!isset($summary[$name])) {
    //                 $summary[$name] = 0;
    //             }
    //             $summary[$name] += $order->order_quantity_draft;
    //         }
    //     }

    //     // 4. Chuyển đổi mảng summary thành Collection để Export
    //     $finalData = collect($summary)->map(function ($quantity, $name) {
    //         return [
    //             'name' => $name,
    //             'quantity' => $quantity
    //         ];
    //     })->values();

    //     // 5. Xuất File
    //     $fileName = "Bao_cao_nhan_vien.xlsx";
    //     return Excel::download(new TechnicianWorkloadExport($finalData), $fileName);
    // }

    public function export(Request $request)
    {
        // 1. Tối ưu xử lý ngày tháng với Carbon (Xóa bỏ if/else và fix lỗi tháng 9)
        $date = Carbon::parse($request->month . ' ' . $request->year);
        $startDate = $date->copy()->startOfMonth()->toDateString();
        $endDate = $date->copy()->endOfMonth()->toDateString();

        // 2. Lấy dữ liệu thô từ Database
        $orders = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->whereBetween('order_details.ord_start_day', [$startDate, $endDate])
            ->where('car_ktvs.car_active', 1)
            ->where('orders.order_surcharge', 0)
            ->select(
                'car_ktvs.car_name',
                'car_ktvs.car_ktv_name_1',
                'car_ktvs.car_ktv_name_2',
                'car_ktvs.car_driver_name',
                'car_ktvs.driver_assistance',
                'car_ktvs.work_over_250',
                'orders.overnight',
                'orders.order_quantity_draft',
                'orders.status_id',
                'orders.order_quantity',
                'order_details.ord_select',
            )
            ->get();

        // 3. Xử lý logic gộp và tính toán
        $xray1Position = ['Phổi (1 Tư thế)', 'Cột sống thắt lưng (1 Tư thế)', 'Cột sống cổ (1 Tư thế)', 'Vai (1 Tư thế)', 'Gối (1 Tư thế)', 'Nhũ Ảnh', 'Khác'];
        $xray2Position = ['Phổi (2 Tư thế)', 'Cột sống thắt lưng (2 Tư thế)', 'Cột sống cổ (2 Tư thế)', 'Vai (2 Tư thế)', 'Gối (2 Tư thế)'];
        $ultraSound    = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];

        $summary = [];

        // Hàm khởi tạo nhân viên kèm cờ nhận diện Tài Xế
        $initEmployee = function ($name, $isDriver = false) use (&$summary) {
            if (!isset($summary[$name])) {
                $summary[$name] = [
                    'nhan_vien'         => $name,
                    'is_driver'         => false, // Mặc định là false
                    'tong_so_ca'        => 0,
                    'so_ca_ktv'         => 0,
                    'so_ca_tx_phu_ktv'  => 0,
                    'o_lai_dem'         => 0,
                    'cong_tac_tren_250' => 0,
                ];
            }
            // Nếu bất kỳ lần nào người này xuất hiện với vai trò tài xế, gán cờ true
            if ($isDriver) {
                $summary[$name]['is_driver'] = true;
            }
        };

        foreach ($orders as $order) {
            $driver  = trim($order->car_driver_name ?? '');
            $ktv1    = trim($order->car_ktv_name_1 ?? '');
            $ktv2    = trim($order->car_ktv_name_2 ?? '');

            $quantityDraft = (float) $order->order_quantity_draft;

            // --- BƯỚC TÍNH TỔNG SỐ CA DỰA TRÊN RULE ---
            $soCaTinhToan = 0;
            if (in_array($order->status_id, [2, 3, 4]) && !in_array($order->ord_select, $ultraSound)) {
                $multiplier = 0;
                if (in_array($order->ord_select, $xray1Position)) {
                    $multiplier = 1;
                } elseif (in_array($order->ord_select, $xray2Position)) {
                    $multiplier = 2;
                }

                if ($multiplier > 0) {
                    // Áp dụng tính toán hệ số tư thế
                    $soCaTinhToan = $order->order_quantity * $multiplier;
                }
            }

            // A. Xử lý cho Tài xế (Được gán cờ is_driver = true)
            if (!empty($driver)) {
                $initEmployee($driver, true);

                // Tổng số ca (đã nhân hệ số) gán cho Tài xế
                $summary[$driver]['tong_so_ca'] += $soCaTinhToan;

                // Số ca phụ KTV
                if ($order->driver_assistance == 1) {
                    $summary[$driver]['so_ca_tx_phu_ktv'] += $quantityDraft;
                }
                $summary[$driver]['o_lai_dem']         += (float) $order->overnight;
                $summary[$driver]['cong_tac_tren_250'] += (float) $order->work_over_250;
            }

            // B. Xử lý cho Kỹ thuật viên 1 (Không phải tài xế -> is_driver = false)
            if (!empty($ktv1)) {
                $initEmployee($ktv1, false);
                $summary[$ktv1]['so_ca_ktv']         += $quantityDraft;
                $summary[$ktv1]['o_lai_dem']         += (float) $order->overnight;
                $summary[$ktv1]['cong_tac_tren_250'] += (float) $order->work_over_250;
            }

            // C. Xử lý cho Kỹ thuật viên 2
            if (!empty($ktv2)) {
                $initEmployee($ktv2, false);
                $summary[$ktv2]['so_ca_ktv']         += $quantityDraft;
                $summary[$ktv2]['o_lai_dem']         += (float) $order->overnight;
                $summary[$ktv2]['cong_tac_tren_250'] += (float) $order->work_over_250;
            }
        }

        // 4. Sắp xếp: Tài xế nằm đầu, sau đó xếp theo tên
        $finalData = collect($summary)->sort(function ($a, $b) {
            // Nếu một người là TX, một người là KTV -> Đưa TX lên trước (-1)
            if ($a['is_driver'] !== $b['is_driver']) {
                return $a['is_driver'] ? -1 : 1;
            }
            // Nếu cùng là TX hoặc cùng là KTV -> Xếp theo tên
            return strnatcmp($a['nhan_vien'], $b['nhan_vien']);
        })->values();

        // 5. Xuất File
        $fileName = "Bao_cao_nhan_vien.xlsx";
        return Excel::download(new TechnicianWorkloadExport($finalData), $fileName);
    }
}
