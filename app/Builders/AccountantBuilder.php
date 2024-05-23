<?php

namespace App\Builders;

use App\Models\Accountant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class AccountantBuilder extends Builder
{
    public function getAllAccountant()
    {
        $accountants = Accountant::join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->where('car_active', 1)
            ->orderBy('accountants.id', 'ASC')
            ->select(
                'accountants.id',
                'accountants.order_id',
                'accountant_month',
                'accountant_distance',
                'accountant_deadline',
                'accountant_number',
                'accountant_date',
                'accountant_payment',
                'accountant_day_payment',
                'accountant_method',
                'accountant_amount_paid',
                'accountant_owe',
                'accountant_discount_day',
                'accountant_doctor_read',
                'accountant_doctor_date_payment',
                'accountant_35X43',
                'accountant_polime',
                'accountant_8X10',
                'accountant_10X12',
                'accountant_film_bag',
                'accountant_note',
                'ord_start_day',
                'ord_form',
                'ord_note',
                'ord_cty_name',
                'order_vat',
                'order_quantity',
                'order_cost',
                'order_price',
                'order_percent_discount',
                'order_discount',
                'order_profit',
                'status_id',
                'car_name',
                'unit_code',
                'unit_name',
            )->get();
        return $accountants;
    }

    public function getAccountantForUpdateOrder($order_id)
    {
        $accountant = Accountant::join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('accountants.order_id', $order_id)
            ->select(
                'accountants.order_id',
                'order_quantity',
                'order_vat',
                'order_cost',
                'order_percent_discount',
                'order_price',
                'unit_code',
                'unit_name',
                'ord_cty_name',
                'ord_start_day',
                'ord_form',
                'status_id',
                'accountant_distance',
                'accountant_doctor_read',
                'accountant_35X43',
                'accountant_polime',
                'accountant_8X10',
                'accountant_10X12',
                'accountant_film_bag',
                'accountant_note',
            )->first();
            
        return $accountant;
    }

    public function getAccountantByYear($year)
    {
        $accountants = Accountant::join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->where('car_active', 1)
            ->orderBy('accountants.id', 'ASC')
            ->select(
                'accountants.id',
                'accountants.order_id',
                'accountant_month',
                'accountant_distance',
                'accountant_deadline',
                'accountant_number',
                'accountant_date',
                'accountant_payment',
                'accountant_day_payment',
                'accountant_method',
                'accountant_amount_paid',
                'accountant_owe',
                'accountant_discount_day',
                'accountant_doctor_read',
                'accountant_doctor_date_payment',
                'accountant_35X43',
                'accountant_polime',
                'accountant_8X10',
                'accountant_10X12',
                'accountant_film_bag',
                'accountant_note',
                'ord_start_day',
                'ord_form',
                'ord_note',
                'ord_cty_name',
                'order_vat',
                'order_quantity',
                'order_cost',
                'order_price',
                'order_percent_discount',
                'order_discount',
                'order_profit',
                'status_id',
                'car_name',
                'unit_code',
                'unit_name',
            );
        if($year != 'all'){
            $accountants->where( DB::raw('YEAR(ord_start_day)'), '=', $year );
        }
        return $accountants->get();
    }

    public function getStatistics($firstDayofThisMonth, $lastDayofThisMonth)
    {
        $statistics = Accountant::join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->select(
                'status_id',
                'order_quantity',
                'accountant_35X43',
                'accountant_8X10',
                'accountant_10X12',
                'ord_select',
                'accountant_doctor_read'
            )
            ->orderBy('order_details.ord_start_day', 'ASC')
            ->get();
        return $statistics;
    }

    public function exportAccountant($firstDayofThisMonth, $lastDayofThisMonth)
    {
        $accountants = Accountant::join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->join('status', 'status.id', '=', 'orders.status_id')
            ->where('car_active', 1)
            ->orderBy('accountants.id', 'ASC')
            ->select(
                'accountants.id',
                'accountants.order_id',
                'accountant_month',
                'accountant_distance',
                'accountant_deadline',
                'accountant_number',
                'accountant_date',
                'accountant_payment',
                'accountant_day_payment',
                'accountant_method',
                'accountant_amount_paid',
                'accountant_owe',
                'accountant_discount_day',
                'accountant_doctor_read',
                'accountant_doctor_date_payment',
                'accountant_35X43',
                'accountant_polime',
                'accountant_8X10',
                'accountant_10X12',
                'accountant_film_bag',
                'accountant_note',
                'ord_start_day',
                'ord_form',
                'ord_note',
                'ord_cty_name',
                'order_vat',
                'order_quantity',
                'order_cost',
                'order_price',
                'order_percent_discount',
                'order_discount',
                'order_profit',
                'status_id',
                'car_name',
                'unit_code',
                'unit_name',
            )
            ->whereBetween('order_details.ord_start_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->whereBetween('order_details.ord_end_day', [$firstDayofThisMonth, $lastDayofThisMonth])
            ->orderBy('order_details.ord_start_day', 'ASC');
        return $accountants;
    }

    public function getQueryBuilderBySearchData($searchData, $year)
    {

        $query = Accountant::join('orders', 'orders.id', '=', 'accountants.order_id')
        ->join('units', 'units.id', '=', 'orders.unit_id')
        ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
        ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
        ->where('car_active', 1)
        ->orderBy('accountants.id', 'ASC')
        ->select(
            'order_price',
            'accountant_owe',
            'accountant_amount_paid',
            'order_quantity',
            'order_discount'
        );

        if($year != 'all'){
            $query->where( DB::raw('YEAR(ord_start_day)'), '=', $year );
        }

        //Month
        if (isset($searchData['month']) && ! empty($searchData['month'])) {
            $query->where('accountant_month', 'LIKE', '%' . $searchData['month'] . '%');
        }
        //Unit Code
        if (isset($searchData['unitCode']) && ! empty($searchData['unitCode'])) {
            $query->where('unit_code', 'LIKE', '%' . $searchData['unitCode'] . '%');
        }
        //Unit Name
        if (isset($searchData['unitName']) && ! empty($searchData['unitName'])) {
            $query->where('unit_name', 'LIKE', '%' . $searchData['unitName'] . '%');
        }
        //Cty Name
        if (isset($searchData['ctyName']) && ! empty($searchData['ctyName'])) {
            $query->where('order_details.ord_cty_name', 'LIKE', '%' . $searchData['ctyName'] . '%');
        }

        return $query;
    }
}
