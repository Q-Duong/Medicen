<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accountant extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'accountant_month', 'order_id', 'accountant_distance', 'accountant_deadline', 'accountant_number', 'accountant_date', 'accountant_payment', 'accountant_day', 'accountant_day_payment', 'accountant_method', 'accountant_amount_paid', 'accountant_owe', 'accountant_discount_day', 'accountant_doctor_read', 'accountant_doctor_date_payment', 'accountant_35X43', 'accountant_polime', 'accountant_8X10', 'accountant_10X12', 'accountant_film_bag', 'accountant_note'
    ];
    protected $table = 'accountants';

    public function order()
    {
        return $this->belongsTo('App\Models\Order', 'order_id');
    }
}
