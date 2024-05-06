<?php

namespace App\Builders;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

final class OrderBuilder extends Builder
{
    public function  listOrder()
    {
        $getListOrder = Order::join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
            ->join('units', 'units.unit_id', '=', 'orders.unit_id')
            ->orderBy('order_id', 'DESC')->select('order_id', 'orders.created_at', 'order_quantity', 'order_price', 'order_status', 'unit_code', 'unit_name', 'ord_start_day', 'ord_end_day', 'ord_select', 'schedule_status')->get();
        return $getListOrder;
    }

    public function getOrder(Request $request)
    {
        $firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
        $lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
        $getOrder = Order::join('accountants', 'accountants.order_id', '=', 'orders.order_id')
            ->join('units', 'orders.unit_id', '=', 'units.unit_id')
            ->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
            ->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.order_id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->select(['order_status', 'ord_start_day', 'ord_end_day', 'order_warning', 'orders.order_id', 'car_ktv_id', 'car_ktv_name_1', 'car_ktv_name_2', 'unit_code', 'unit_name', 'ord_select', 'ord_cty_name', 'customer_address', 'customer_note', 'ord_list_file', 'ord_list_file_path', 'customer_name', 'customer_phone', 'ord_time', 'order_quantity', 'order_quantity_draft', 'order_note_ktv', 'ord_doctor_read', 'ord_film', 'ord_form', 'ord_print', 'ord_form_print', 'ord_print_result', 'ord_film_sheet', 'ord_note', 'ord_deadline', 'ord_deliver_results', 'ord_email', 'accountant_doctor_read', 'accountant_35X43', 'accountant_polime', 'accountant_8X10', 'accountant_10X12', 'accountant_film_bag', 'accountant_note', 'car_active', 'car_name', 'order_surcharge', 'order_child', 'ord_delivery_date', 'order_updated'])
            ->orderBy('order_details.ord_start_day', 'ASC')
            // ->orderBy('orders.created_at', 'DESC')
            ->orderBy('orders.order_child', 'DESC')
            ->get();
        return $getOrder;
    }
    public function getAccountant(Request $request)
    {
        $firstDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->firstOfMonth()->toDateString();
        $lastDayofThisMonth = Carbon::createFromFormat('M Y', $request->month . ' ' . $request->year)->endOfMonth()->toDateString();
        $getAccountant = Order::join('accountants', 'accountant.order_id', '=', 'orders.order_id')
            ->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->orderBy('order_details.ord_start_day', 'ASC')
            ->get();
        return $getAccountant;
    }
}