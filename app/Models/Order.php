<?php

namespace App\Models;

use App\Builders\OrderBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'order_detail_id',
        'unit_id',
        'status_id',
        'order_vat',
        'order_quantity',
        'order_quantity_draft',
        'order_note_ktv',
        'order_cost',
        'order_price',
        'order_percent_discount',
        'order_discount',
        'order_profit',
        'schedule_status',
        'order_warning',
        'accountant_updated',
        'order_all_in_one',
        'order_child',
        'order_surcharge',
        'order_updated',
        'order_send_result'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function carKTV()
    {
        $this->hasMany(CarKTV::class);
    }

    public function accountant()
    {
        $this->hasMany(Accountant::class);
    }

    public function newEloquentBuilder($query): OrderBuilder
    {
        return new OrderBuilder($query);
    }

    
}
