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
        if (isset($searchData['order_id']) && !empty($searchData['order_id']) && $searchData['order_id'][0] != 'all') {
            $orderIds = explode(',', $searchData['order_id']);
            $query->whereIn('accountants.order_id', $orderIds);
        }
        //Status Id
        if (isset($searchData['status_id']) && !empty($searchData['status_id']) && $searchData['status_id'] != 'all') {
            $statusId = explode(',', $searchData['status_id']);
            $query->whereIn('status_id', (int)$statusId);
        }
        //Car Name
        if (isset($searchData['car_name']) && !empty($searchData['car_name']) && $searchData['car_name'] != 'all') {
            $carName = explode(',', $searchData['car_name']);
            $query->whereIn('car_name', $carName);
        }
        //Unit Code
        if (isset($searchData['unit_code']) && !empty($searchData['unit_code']) && $searchData['unit_code'] != 'all') {
            $unitCode = explode(',', $searchData['unit_code']);
            $query->whereIn('unit_code', $unitCode);
        }
        //Unit Name
        if (isset($searchData['unit_name']) && !empty($searchData['unit_name']) && $searchData['unit_name'] != 'all') {
            $unitName = explode(',', $searchData['unit_name']);
            $query->whereIn('unit_name', 'LIKE', '%' . $unitName . '%');
        }
        //Order Start Day
        if (isset($searchData['ord_start_day']) && !empty($searchData['ord_start_day']) && $searchData['ord_start_day'] != 'all') {
            $ordStartDay = explode(',', $searchData['ord_start_day']);
            $query->whereIn('ord_start_day', $ordStartDay);
        }
        //Cty Name
        if (isset($searchData['ord_cty_name']) && !empty($searchData['ord_cty_name']) && $searchData['ord_cty_name'] != 'all') {
            $ordCtyName = explode(',', $searchData['ord_cty_name']);
            $query->whereIn('ord_cty_name', 'LIKE', '%' . $ordCtyName . '%');
        }
        //Order Form
        if (isset($searchData['ord_form']) && !empty($searchData['ord_form']) && $searchData['ord_form'] != 'all') {
            $ordForm = explode(',', $searchData['ord_form']);
            $query->whereIn('ord_form', $ordForm);
        }
        //Accountant Month
        if (isset($searchData['accountant_month']) && !empty($searchData['accountant_month']) && $searchData['accountant_month'] != 'all') {
            $accountantMonth = explode(',', $searchData['accountant_month']);
            $query->whereIn('accountant_month', (int)$accountantMonth);
        }
        //Accountant Distance
        if (isset($searchData['accountant_distance']) && !empty($searchData['accountant_distance']) && $searchData['accountant_distance'] != 'all') {
            $accountantDistance = explode(',', $searchData['accountant_distance']);
            $query->whereIn('accountant_distance', $accountantDistance);
        }
        //Accountant Deadline
        if (isset($searchData['accountant_deadline']) && !empty($searchData['accountant_deadline']) && $searchData['accountant_deadline'] != 'all') {
            $accountantDeadline = explode(',', $searchData['accountant_deadline']);
            if ($accountantDeadline == 'empty') {
                $query->whereNull('accountant_deadline');
            } else {
                $query->whereIn('accountant_deadline', $accountantDeadline);
            }
        }
        //Accountant Number
        if (isset($searchData['accountant_number']) && $searchData['accountant_number'] != 'all') {
            $accountantNumber = explode(',', $searchData['accountant_number']);
            if ($accountantNumber == 'empty') {
                $query->whereNull('accountant_number');
            } else {
                $query->whereIn('accountant_number', $accountantNumber);
            }
        }
        //Accountant Date
        if (isset($searchData['accountant_date']) && !empty($searchData['accountant_date']) && $searchData['accountant_date'] != 'all') {
            $accountantDate = explode(',', $searchData['accountant_date']);
            if ($accountantDate == 'empty') {
                $query->whereNull('accountant_date');
            } else {
                $query->whereIn('accountant_date', $accountantDate);
            }
        }
        //Accountant Status
        if (isset($searchData['accountant_status'])  && $searchData['accountant_status'] != 'all') {
            $accountantStatus = explode(',', $searchData['accountant_status']);
            $query->whereIn('accountant_status', $accountantStatus);
        }
        //Accountant Day Payment
        if (isset($searchData['accountant_day_payment']) && !empty($searchData['accountant_day_payment']) && $searchData['accountant_day_payment'] != 'all') {
            $accountantDayPayment = explode(',', $searchData['accountant_day_payment']);
            if ($accountantDayPayment == 'empty') {
                $query->whereNull('accountant_day_payment');
            } else {
                $query->whereIn('accountant_day_payment', $accountantDayPayment);
            }
        }
        //Accountant Method
        if (isset($searchData['accountant_method']) && !empty($searchData['accountant_method']) && $searchData['accountant_method'] != 'all') {
            if ($searchData['accountant_method'] == 'empty') {
                $query->whereNull('accountant_method');
            } else {
                $accountantMethod = explode(',', $searchData['accountant_method']);
                $query->whereIn('accountant_method', 'LIKE', '%' . $accountantMethod . '%');
            }
        }
        //Accountant Amount Paid
        if (isset($searchData['accountant_amount_paid']) && $searchData['accountant_amount_paid'] != 'all') {
            $accountantAmountPaid = explode(',', $searchData['accountant_amount_paid']);
            $query->whereIn('accountant_amount_paid', $accountantAmountPaid);
        }
        //Accountant Owe
        if (isset($searchData['accountant_owe']) && $searchData['accountant_owe'] != 'all') {
            $accountantOwe = explode(',', $searchData['accountant_owe']);
            $query->whereIn('accountant_owe', $accountantOwe);
        }
        //Accountant Discount Day
        if (isset($searchData['accountant_discount_day']) && !empty($searchData['accountant_discount_day']) && $searchData['accountant_discount_day'] != 'all') {
            $accountantDiscountDay = explode(',', $searchData['accountant_discount_day']);
            if ($accountantDiscountDay == 'empty') {
                $query->whereNull('accountant_discount_day');
            } else {
                $query->whereIn('accountant_discount_day', $accountantDiscountDay);
            }
        }
        //Accountant Doctor Read
        if (isset($searchData['accountant_doctor_read']) && !empty($searchData['accountant_doctor_read']) && $searchData['accountant_doctor_read'] != 'all') {
            $accountantDoctorRead = explode(',', $searchData['accountant_doctor_read']);
            if ($accountantDoctorRead == 'empty') {
                $query->whereNull('accountant_doctor_read');
            } else {
                $query->whereIn('accountant_doctor_read', 'LIKE', '%' . $accountantDoctorRead . '%');
            }
        }
        //Accountant Doctor Date Payment
        if (isset($searchData['accountant_doctor_date_payment']) && !empty($searchData['accountant_doctor_date_payment']) && $searchData['accountant_doctor_date_payment'] != 'all') {
            $accountantDoctorDatePayment = explode(',', $searchData['accountant_doctor_date_payment']);
            if ($accountantDoctorDatePayment == 'empty') {
                $query->whereNull('accountant_doctor_date_payment');
            } else {
                $query->whereIn('accountant_doctor_date_payment', $accountantDoctorDatePayment);
            }
        }
        //Accountant 35X43
        if (isset($searchData['accountant_35X43']) && $searchData['accountant_35X43'] != 'all') {
            $accountant35X43 = explode(',', $searchData['accountant_35X43']);
            if ($accountant35X43 == 'empty') {
                $query->whereNull('accountant_35X43');
            } else {
                $query->whereIn('accountant_35X43', $accountant35X43);
            }
        }
        //Accountant Polime
        if (isset($searchData['accountant_polime']) && $searchData['accountant_polime'] != 'all') {
            $accountantPolime = explode(',', $searchData['accountant_polime']);
            if ($accountantPolime == 'empty') {
                $query->whereNull('accountant_polime');
            } else {
                $query->whereIn('accountant_polime', $accountantPolime);
            }
        }
        //Accountant 8X10
        if (isset($searchData['accountant_8X10']) && $searchData['accountant_8X10'] != 'all') {
            $accountant8X10 = explode(',', $searchData['accountant_8X10']);
            if ($accountant8X10 == 'empty') {
                $query->whereNull('accountant_8X10');
            } else {
                $query->whereIn('accountant_8X10', $accountant8X10);
            }
        }
        //Accountant 10X12
        if (isset($searchData['accountant_10X12']) && $searchData['accountant_10X12'] != 'all') {
            $accountant10X12 = explode(',', $searchData['accountant_10X12']);
            if ($accountant10X12 == 'empty') {
                $query->whereNull('accountant_10X12');
            } else {
                $query->whereIn('accountant_10X12', $accountant10X12);
            }
        }
        //Accountant Film Bag
        if (isset($searchData['accountant_film_bag']) && $searchData['accountant_film_bag'] != 'all') {
            $accountantFilmBag = explode(',', $searchData['accountant_film_bag']);
            if ($accountantFilmBag == 'empty') {
                $query->whereNull('accountant_film_bag');
            } else {
                $query->whereIn('accountant_film_bag', $accountantFilmBag);
            }
        }
        //Order VAT
        if (isset($searchData['order_vat']) && $searchData['order_vat'] != 'all') {
            $orderVat = explode(',', $searchData['order_vat']);
            if ($orderVat == 'empty') {
                $query->whereNull('order_vat');
            } else {
                $query->whereIn('order_vat', 'LIKE', '%' . $orderVat . '%');
            }
        }
        //Order Quantity
        if (isset($searchData['order_quantity']) && $searchData['order_quantity'] != 'all') {
            $orderQuantity = explode(',', $searchData['order_quantity']);
            $query->whereIn('order_quantity', $orderQuantity);
        }
        //Order Cost
        if (isset($searchData['order_cost']) && $searchData['order_cost'] != 'all') {
            $orderCost = explode(',', $searchData['order_cost']);
            $query->whereIn('order_cost', $orderCost);
        }
        //Order Price
        if (isset($searchData['order_price']) && $searchData['order_price'] != 'all') {
            $orderPrice = explode(',', $searchData['order_price']);
            $query->whereIn('order_price', $orderPrice);
        }
        //Order Percent Discount
        if (isset($searchData['order_percent_discount']) && $searchData['order_percent_discount'] != 'all') {
            $orderPercentDiscount = explode(',', $searchData['order_percent_discount']);
            if ($orderPercentDiscount == 'empty') {
                $query->whereNull('order_percent_discount');
            } else {
                $query->whereIn('order_percent_discount', 'LIKE', '%' . $orderPercentDiscount . '%');
            }
        }
        //Order Discount
        if (isset($searchData['order_discount']) && $searchData['order_discount'] != 'all') {
            $orderDiscount = explode(',', $searchData['order_discount']);
            $query->whereIn('order_discount', $orderDiscount);
        }
        //Order Profit
        if (isset($searchData['order_profit']) && $searchData['order_profit'] != 'all') {
            $orderProfit = explode(',', $searchData['order_profit']);
            $query->whereIn('order_profit', $orderProfit);
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
        if ($currentChange == 'order_vat') {
            $uniqueFilters = $filters->where($currentChange, '!=', null)->pluck($currentChange)->map(function ($item) {
                return mb_strtoupper($item, 'UTF-8');
            })->unique()->sort();
        } elseif (in_array($currentChange, $paramsNull)) {
            $uniqueFilters = $filters->where($currentChange, '!=', null)->pluck($currentChange)->unique()->sort();
        } else {
            $uniqueFilters = $filters->pluck($currentChange)->unique()->sort();
        }

        return $uniqueFilters;
    }
}
