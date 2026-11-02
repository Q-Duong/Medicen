<?php

namespace App\Builders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

final class OrderBuilder extends Builder
{
    public function getAll($request = null)
    {
        // Khởi tạo query builder với các lệnh join và select như cũ
        $query = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->select(
                'orders.id',
                'orders.created_at',
                'orders.complete',
                'order_quantity',
                'order_price',
                'status_id',
                'unit_code',
                'unit_abbreviation',
                'unit_name',
                'ord_start_day',
                'ord_end_day',
                'ord_select',
                'ord_cty_name',
                'ord_type',
                'schedule_status'
            );

        // Xử lý nối thêm các điều kiện lọc (nếu có Request truyền vào)
        if ($request) {
            if ($request->filled('unit_abbreviation')) {
                $query->where('units.unit_abbreviation', $request->unit_abbreviation);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('ord_start_day', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('ord_start_day', '<=', $request->to_date);
            }

            if ($request->filled('status_id')) {
                if ($request->status_id === 'processing') {
                    $query->whereIn('orders.status_id', [1, 2]); // Đang xử lý
                } elseif ($request->status_id === 'completed') {
                    $query->whereIn('orders.status_id', [3, 4]); // Hoàn thành
                } else {
                    $query->where('orders.status_id', $request->status_id);
                }
            }

            if ($request->filled('schedule_status')) {
                if ($request->schedule_status === '0') {
                    // Chưa lên lịch
                    $query->where('orders.schedule_status', 0);
                } elseif ($request->schedule_status === '1') {
                    // Đã lên lịch (schedule_status != 0)
                    $query->where('orders.schedule_status', '!=', 0);
                }
            }

            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('orders.id', 'LIKE', "%{$keyword}%")
                        ->orWhere('order_details.ord_cty_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('units.unit_name', 'LIKE', "%{$keyword}%");
                });
            }
        }

        // Cuối cùng mới sắp xếp và phân trang
        return $query->orderBy('ord_start_day', 'DESC')
            ->orderBy('orders.created_at', 'DESC')
            ->paginate(10)
            ->withQueryString(); // withQueryString giúp giữ tham số lọc trên URL khi bấm sang trang 2, 3...
    }

    public function getOneOrder($order_id)
    {
        $getOneOder = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
            ->join('units', 'units.id',  '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.id', $order_id)
            ->select(
                'accountants.order_id',
                'orders.order_detail_id',
                'customer_name',
                'customer_phone',
                'customer_address',
                'customer_location',
                'customer_note',
                'orders.unit_id',
                'ord_cty_name',
                'ord_start_day',
                'ord_session',
                'ord_time',
                'ord_select',
                'ord_doctor_read',
                'ord_film',
                'ord_form',
                'ord_print',
                'ord_form_print',
                'ord_print_result',
                'ord_film_sheet',
                'ord_note',
                'order_warning',
                'ord_deadline',
                'ord_deliver_results',
                'ord_email',
                'ord_list_file',
                'ord_list_file_path',
                'accountant_distance',
                'order_vat',
                'order_child',
                'order_surcharge',
                'order_all_in_one',
                'order_quantity',
                'order_cost',
                'order_percent_discount',
                'order_price',
                'overnight',
                'status_id'
            )
            ->first();
        return $getOneOder;
    }

    public function getScheduleTechnologist($firstDayofThisMonth, $lastDayofThisMonth)
    {
        $getScheduleTechnologist = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->where('car_ktvs.car_active', 1)
            ->select(
                'car_name',
                'car_active',
                'status_id',
                'order_surcharge',
                'car_driver_name',
                'car_ktv_name_1',
                'car_ktv_name_2',
                'driver_assistance',
                'work_over_250',
                'ord_start_day',
                'ord_end_day',
                'order_child',
                'order_updated',
                'car_ktvs.order_id',
                'unit_name',
                'unit_abbreviation',
                'car_ktvs.id',
                'ord_select',
                'ord_cty_name',
                'customer_address',
                'customer_note',
                'ord_note',
                'ord_list_file',
                'ord_list_file_path',
                'customer_name',
                'customer_phone',
                'ord_time',
                'order_quantity',
                'order_quantity_draft',
                'order_note_ktv',
                'orders.created_at'
            )
            ->orderBy('order_details.ord_start_day', 'ASC')
            ->orderBy('orders.created_at', 'ASC')
            ->get();
        return $getScheduleTechnologist;
    }

    public function getScheduleTechnicianSearch($firstDayofThisMonth, $lastDayofThisMonth, $target)
    {
        $getScheduleTechnologist = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->where('car_ktvs.car_active', 1)
            ->where(function ($query) use ($target) {
                $query->where('car_ktvs.car_ktv_name_1', $target)
                    ->orWhere('car_ktvs.car_ktv_name_2', $target);
            })
            ->select(
                'car_name',
                'car_active',
                'status_id',
                'order_surcharge',
                'car_driver_name',
                'car_ktv_name_1',
                'car_ktv_name_2',
                'ord_start_day',
                'ord_end_day',
                'order_child',
                'order_updated',
                'car_ktvs.order_id',
                'unit_name',
                'unit_abbreviation',
                'car_ktvs.id',
                'ord_select',
                'ord_cty_name',
                'customer_address',
                'customer_note',
                'ord_note',
                'ord_list_file',
                'ord_list_file_path',
                'customer_name',
                'customer_phone',
                'ord_time',
                'order_quantity',
                'order_quantity_draft',
                'order_note_ktv',
                'orders.created_at'
            )
            ->orderBy('order_details.ord_start_day', 'ASC')
            ->orderBy('orders.created_at', 'ASC')
            ->get();
        return $getScheduleTechnologist;
    }

    public function getScheduleDriverSearch($firstDayofThisMonth, $lastDayofThisMonth, $target)
    {
        $getScheduleTechnologist = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->where('car_ktvs.car_active', 1)
            ->where(function ($query) use ($target) {
                $query->where('car_ktvs.car_driver_name', $target);
            })
            ->select(
                'car_name',
                'car_active',
                'status_id',
                'order_surcharge',
                'car_driver_name',
                'car_ktv_name_1',
                'car_ktv_name_2',
                'ord_start_day',
                'ord_end_day',
                'order_child',
                'order_updated',
                'car_ktvs.order_id',
                'unit_name',
                'unit_abbreviation',
                'car_ktvs.id',
                'ord_select',
                'ord_cty_name',
                'customer_address',
                'customer_note',
                'ord_note',
                'ord_list_file',
                'ord_list_file_path',
                'customer_name',
                'customer_phone',
                'ord_time',
                'order_quantity',
                'order_quantity_draft',
                'order_note_ktv',
                'orders.created_at'
            )
            ->orderBy('order_details.ord_start_day', 'ASC')
            ->orderBy('orders.created_at', 'ASC')
            ->get();
        return $getScheduleTechnologist;
    }

    public function getScheduleDetails($firstDayofThisMonth, $lastDayofThisMonth)
    {
        $getScheduleDetails = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->where('car_ktvs.car_active', 1)
            ->select(
                'status_id',
                'car_ktvs.order_id',
                'car_ktvs.id',
                'car_driver_name',
                'car_ktv_name_1',
                'car_ktv_name_2',
                'car_active',
                'car_name',
                'unit_code',
                'unit_abbreviation',
                'unit_name',
                'customer_address',
                'customer_note',
                'customer_name',
                'customer_phone',
                'orders.order_detail_id',
                'ord_select',
                'ord_cty_name',
                'ord_time',
                'ord_list_file',
                'ord_list_file_path',
                'ord_total_file_name',
                'ord_total_file_path',
                'ord_delivery_date',
                'ord_start_day',
                'ord_end_day',
                'ord_doctor_read',
                'ord_film',
                'ord_form',
                'ord_print',
                'ord_form_print',
                'ord_print_result',
                'ord_film_sheet',
                'ord_note',
                'ord_deadline',
                'ord_deliver_results',
                'ord_email',
                'accountant_doctor_read',
                'accountant_35X43',
                'accountant_polime',
                'accountant_8X10',
                'accountant_10X12',
                'accountant_film_bag',
                'accountant_note',
                'order_surcharge',
                'order_child',
                'order_quantity',
                'order_quantity_draft',
                'order_note_ktv',
                'order_warning',
                'order_updated',
                'order_send_result',
                'orders.created_at'
            )
            ->orderBy('order_details.ord_start_day', 'ASC')
            ->orderBy('orders.created_at', 'ASC')
            ->get();
        return $getScheduleDetails;
    }

    public function getScheduleDetailsForSearch($firstDayofThisMonth, $lastDayofThisMonth, $target)
    {
        $getScheduleDetails = Order::join('accountants', 'accountants.order_id', '=', 'orders.id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->where('car_ktvs.car_active', 1)
            ->where('ord_cty_name', $target)
            ->select(
                'status_id',
                'car_ktvs.order_id',
                'car_ktvs.id',
                'car_driver_name',
                'car_ktv_name_1',
                'car_ktv_name_2',
                'car_active',
                'car_name',
                'unit_code',
                'unit_abbreviation',
                'unit_name',
                'customer_address',
                'customer_note',
                'customer_name',
                'customer_phone',
                'orders.order_detail_id',
                'ord_select',
                'ord_cty_name',
                'ord_time',
                'ord_list_file',
                'ord_list_file_path',
                'ord_total_file_name',
                'ord_total_file_path',
                'ord_delivery_date',
                'ord_start_day',
                'ord_end_day',
                'ord_doctor_read',
                'ord_film',
                'ord_form',
                'ord_print',
                'ord_form_print',
                'ord_print_result',
                'ord_film_sheet',
                'ord_note',
                'ord_deadline',
                'ord_deliver_results',
                'ord_email',
                'accountant_doctor_read',
                'accountant_35X43',
                'accountant_polime',
                'accountant_8X10',
                'accountant_10X12',
                'accountant_film_bag',
                'accountant_note',
                'order_surcharge',
                'order_child',
                'order_quantity',
                'order_quantity_draft',
                'order_note_ktv',
                'order_warning',
                'order_updated',
                'order_send_result'
            )
            ->orderBy('order_details.ord_start_day', 'ASC')
            ->orderBy('orders.order_child', 'DESC')
            ->get();
        return $getScheduleDetails;
    }
}
