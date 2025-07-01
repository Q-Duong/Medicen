<?php

namespace App\Models;

use App\Builders\AccountantBuilder;
use Illuminate\Database\Eloquent\Model;

class Accountant extends Model
{

    protected $table = 'accountants';

    public $timestamps = true;

    protected $fillable = [
        'order_id',
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
        'liquidation_number',
        'contract_type',
        'contract_date',
        'contract_status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function newEloquentBuilder($query): AccountantBuilder
    {
        return new AccountantBuilder($query);
    }
}
