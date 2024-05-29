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
                'accountant_status',
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
                'accountant_status',
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
                'accountant_status',
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
        if ($year != 'all') {
            $accountants->where(DB::raw('YEAR(ord_start_day)'), '=', $year);
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
                'accountant_status',
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
                'accountant_status',
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

        if ($year != 'all') {
            $query->where(DB::raw('YEAR(ord_start_day)'), '=', $year);
        }
        //Order Id
        if (isset($searchData['order_id']) && !empty($searchData['order_id']) && $searchData['order_id'] != 'all') {
            $query->where('accountants.order_id', $searchData['order_id']);
        }
        //Status Id
        if (isset($searchData['status_id']) && !empty($searchData['status_id']) && $searchData['status_id'] != 'all') {
            $query->where('status_id', (int)$searchData['status_id']);
        }
        //Car Name
        if (isset($searchData['car_name']) && !empty($searchData['car_name']) && $searchData['car_name'] != 'all') {
            $query->where('car_name', $searchData['car_name']);
        }
        //Unit Code
        if (isset($searchData['unit_code']) && !empty($searchData['unit_code']) && $searchData['unit_code'] != 'all') {
            $query->where('unit_code', $searchData['unit_code']);
        }
        //Unit Name
        if (isset($searchData['unit_name']) && !empty($searchData['unit_name']) && $searchData['unit_name'] != 'all') {
            $query->where('unit_name', 'LIKE', '%' . $searchData['unit_name'] . '%');
        }
        //Order Start Day
        if (isset($searchData['ord_start_day']) && !empty($searchData['ord_start_day']) && $searchData['ord_start_day'] != 'all') {
            $query->where('ord_start_day', $searchData['ord_start_day']);
        }
        //Cty Name
        if (isset($searchData['ord_cty_name']) && !empty($searchData['ord_cty_name']) && $searchData['ord_cty_name'] != 'all') {
            $query->where('ord_cty_name', 'LIKE', '%' . $searchData['ord_cty_name'] . '%');
        }
        //Order Form
        if (isset($searchData['ord_form']) && !empty($searchData['ord_form']) && $searchData['ord_form'] != 'all') {
            $query->where('ord_form', $searchData['ord_form']);
        }
        //Accountant Month
        if (isset($searchData['accountant_month']) && !empty($searchData['accountant_month']) && $searchData['accountant_month'] != 'all') {
            $query->where('accountant_month', (int)$searchData['accountant_month']);
        }
        //Accountant Distance
        if (isset($searchData['accountant_distance']) && !empty($searchData['accountant_distance']) && $searchData['accountant_distance'] != 'all') {
            $query->where('accountant_distance', $searchData['accountant_distance']);
        }
        //Accountant Deadline
        if (isset($searchData['accountant_deadline']) && !empty($searchData['accountant_deadline']) && $searchData['accountant_deadline'] != 'all') {
            if ($searchData['accountant_deadline'] == 'empty') {
                $query->whereNull('accountant_deadline');
            } else {
                $query->where('accountant_deadline', $searchData['accountant_deadline']);
            }
        }
        //Accountant Number
        if (isset($searchData['accountant_number']) && $searchData['accountant_number'] != 'all') {
            if ($searchData['accountant_number'] == 'empty') {
                $query->whereNull('accountant_number');
            } else {
                $query->where('accountant_number', $searchData['accountant_number']);
            }
        }
        //Accountant Date
        if (isset($searchData['accountant_date']) && !empty($searchData['accountant_date']) && $searchData['accountant_date'] != 'all') {
            if ($searchData['accountant_date'] == 'empty') {
                $query->whereNull('accountant_date');
            } else {
                $query->where('accountant_date', $searchData['accountant_date']);
            }
        }
        //Accountant Status
        if (isset($searchData['accountant_status'])  && $searchData['accountant_status'] != 'all') {
            $query->where('accountant_status', $searchData['accountant_status']);
        }
        //Accountant Day Payment
        if (isset($searchData['accountant_day_payment']) && !empty($searchData['accountant_day_payment']) && $searchData['accountant_day_payment'] != 'all') {
            if ($searchData['accountant_day_payment'] == 'empty') {
                $query->whereNull('accountant_day_payment');
            } else {
                $query->where('accountant_day_payment', $searchData['accountant_day_payment']);
            }
        }
        //Accountant Method
        if (isset($searchData['accountant_method']) && !empty($searchData['accountant_method']) && $searchData['accountant_method'] != 'all') {
            if ($searchData['accountant_method'] == 'empty') {
                $query->whereNull('accountant_method');
            } else {
                $query->where('accountant_method', 'LIKE', '%' . $searchData['accountant_method'] . '%');
            }
        }
        //Accountant Amount Paid
        if (isset($searchData['accountant_amount_paid']) && $searchData['accountant_amount_paid'] != 'all') {
            $query->where('accountant_amount_paid', $searchData['accountant_amount_paid']);
        }
        //Accountant Owe
        if (isset($searchData['accountant_owe']) && $searchData['accountant_owe'] != 'all') {
            $query->where('accountant_owe', $searchData['accountant_owe']);
        }
        //Accountant Discount Day
        if (isset($searchData['accountant_discount_day']) && !empty($searchData['accountant_discount_day']) && $searchData['accountant_discount_day'] != 'all') {
            if ($searchData['accountant_discount_day'] == 'empty') {
                $query->whereNull('accountant_discount_day');
            } else {
                $query->where('accountant_discount_day', $searchData['accountant_discount_day']);
            }
        }
        //Accountant Doctor Read
        if (isset($searchData['accountant_doctor_read']) && !empty($searchData['accountant_doctor_read']) && $searchData['accountant_doctor_read'] != 'all') {
            if ($searchData['accountant_doctor_read'] == 'empty') {
                $query->whereNull('accountant_doctor_read');
            } else {
                $query->where('accountant_doctor_read', 'LIKE', '%' . $searchData['accountant_doctor_read'] . '%');
            }
        }
        //Accountant Doctor Date Payment
        if (isset($searchData['accountant_doctor_date_payment']) && !empty($searchData['accountant_doctor_date_payment']) && $searchData['accountant_doctor_date_payment'] != 'all') {
            if ($searchData['accountant_doctor_date_payment'] == 'empty') {
                $query->whereNull('accountant_doctor_date_payment');
            } else {
                $query->where('accountant_doctor_date_payment', $searchData['accountant_doctor_date_payment']);
            }
        }
        //Accountant 35X43
        if (isset($searchData['accountant_35X43']) && $searchData['accountant_35X43'] != 'all') {
            if ($searchData['accountant_35X43'] == 'empty') {
                $query->whereNull('accountant_35X43');
            } else {
                $query->where('accountant_35X43', $searchData['accountant_35X43']);
            }
        }
        //Accountant Polime
        if (isset($searchData['accountant_polime']) && $searchData['accountant_polime'] != 'all') {
            if ($searchData['accountant_polime'] == 'empty') {
                $query->whereNull('accountant_polime');
            } else {
                $query->where('accountant_polime', $searchData['accountant_polime']);
            }
        }
        //Accountant 8X10
        if (isset($searchData['accountant_8X10']) && $searchData['accountant_8X10'] != 'all') {
            if ($searchData['accountant_8X10'] == 'empty') {
                $query->whereNull('accountant_8X10');
            } else {
                $query->where('accountant_8X10', $searchData['accountant_8X10']);
            }
        }
        //Accountant 10X12
        if (isset($searchData['accountant_10X12']) && $searchData['accountant_10X12'] != 'all') {
            if ($searchData['accountant_10X12'] == 'empty') {
                $query->whereNull('accountant_10X12');
            } else {
                $query->where('accountant_10X12', $searchData['accountant_10X12']);
            }
        }
        //Accountant Film Bag
        if (isset($searchData['accountant_film_bag']) && $searchData['accountant_film_bag'] != 'all') {
            if ($searchData['accountant_film_bag'] == 'empty') {
                $query->whereNull('accountant_film_bag');
            } else {
                $query->where('accountant_film_bag', $searchData['accountant_film_bag']);
            }
        }
        //Order VAT
        if (isset($searchData['order_vat']) && $searchData['order_vat'] != 'all') {
            if ($searchData['order_vat'] == 'empty') {
                $query->whereNull('order_vat');
            } else {
                $query->where('order_vat', 'LIKE', '%' . $searchData['order_vat'] . '%');
            }
        }
        //Order Quantity
        if (isset($searchData['order_quantity']) && $searchData['order_quantity'] != 'all') {
            $query->where('order_quantity', $searchData['order_quantity']);
        }
        //Order Cost
        if (isset($searchData['order_cost']) && $searchData['order_cost'] != 'all') {
            $query->where('order_cost', $searchData['order_cost']);
        }
        //Order Price
        if (isset($searchData['order_price']) && $searchData['order_price'] != 'all') {
            $query->where('order_price', $searchData['order_price']);
        }
        //Order Percent Discount
        if (isset($searchData['order_percent_discount']) && $searchData['order_percent_discount'] != 'all') {
            if ($searchData['order_percent_discount'] == 'empty') {
                $query->whereNull('order_percent_discount');
            } else {
                $query->where('order_percent_discount', 'LIKE', '%' . $searchData['order_percent_discount'] . '%');
            }
        }
        //Order Discount
        if (isset($searchData['order_discount']) && $searchData['order_discount'] != 'all') {
            $query->where('order_discount', $searchData['order_discount']);
        }
        //Order Profit
        if (isset($searchData['order_profit']) && $searchData['order_profit'] != 'all') {
            $query->where('order_profit', $searchData['order_profit']);
        }

        return $query;
    }

    public function renewFilterWhenUpdated($currentChange, $year)
    {
        $filters = Accountant::join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->where('car_active', 1)
            ->orderBy('accountants.id', 'ASC')
            ->select($currentChange);
        if ($year != 'all') {
            $filters->where(DB::raw('YEAR(ord_start_day)'), '=', $year);
        }
        $filters->get();
        $paramsNull = ['accountant_deadline', 'accountant_number', 'accountant_date', 'accountant_day_payment', 'order_percent_discount', 'accountant_discount_day', 'accountant_doctor_date_payment', 'accountant_35X43', 'accountant_polime', 'accountant_8X10', 'accountant_10X12', 'accountant_film_bag'];
        if($currentChange == 'order_vat'){
            $uniqueFilters = $filters->where($currentChange, '!=', null)->pluck($currentChange)->map(function ($item) {
                return mb_strtoupper($item, 'UTF-8');
            })->unique()->sort();
        }elseif(in_array($currentChange, $paramsNull)){
            $uniqueFilters = $filters->where($currentChange, '!=', null)->pluck($currentChange)->unique()->sort();
        }else{
            $uniqueFilters = $filters->pluck($currentChange)->unique()->sort();
        }
        
        return $uniqueFilters;
    }
}
