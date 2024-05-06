<?php

namespace App\Builders;

use App\Models\Accountant;
use Illuminate\Database\Eloquent\Builder;

final class AccountantBuilder extends Builder
{
    public function getListOrderAccountant()
    {
        $listOrderAccountant = Accountant::join('orders', 'orders.order_id', '=', 'accountant.order_id')
            ->join('units', 'orders.unit_id', '=', 'units.unit_id')
            ->join('order_details', 'order_details.order_detail_id', '=', 'orders.order_detail_id')
            ->join('car_ktv', 'car_ktv.order_id', '=', 'orders.order_id')
            ->where('car_ktv.car_active', 1)
            ->orderBy('accountant.accountant_id', 'ASC')
            ->select(
                'accountant.accountant_id',
                'orders.order_id',
                'accountant_month',
                'ord_start_day',
                'car_name',
                'accountant_distance',
                'unit_code',
                'unit_name',
                'ord_cty_name',
                'accountant_deadline',
                'accountant_number',
                'accountant_date',
                'order_vat',
                'order_quantity',
                'order_cost',
                'order_price',
                'accountant_payment',
                'accountant_day_payment',
                'accountant_method',
                'accountant_amount_paid',
                'accountant_owe',
                'order_percent_discount',
                'order_discount',
                'accountant_discount_day',
                'order_profit',
                'accountant_doctor_read',
                'accountant_doctor_date_payment',
                'ord_form',
                'accountant_35X43',
                'accountant_polime',
                'accountant_8X10',
                'accountant_10X12',
                'accountant_film_bag',
                'accountant_note',
                'ord_note',
                'order_status'
            )->get();
        return $listOrderAccountant;
    }
}