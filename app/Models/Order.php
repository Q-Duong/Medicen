<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = true;
    protected $fillable = [
    	'customer_id', 'order_detail_id', 'unit_id','order_vat', 'order_quantity', 'order_quantity_draft', 'order_note_ktv','order_cost','order_price', 'order_percent_discount', 'order_discount', 'order_profit', 'order_status', 'schedule_status', 'order_warning', 'accountant_updated', 'order_all_in_one', 'order_child', 'order_surcharge', 'order_updated'
    ];
    protected $primaryKey = 'order_id';
 	protected $table = 'tbl_orders';

    public function unit(){
        return $this->belongsTo('App\Models\Unit','unit_id');
    }

    public function customer(){
        return $this->belongsTo('App\Models\Customer','customer_id');
    }

    public function orderdetail(){
        return $this->belongsTo('App\Models\OrderDetail','order_detail_id');
    }

    public function carktv(){
        $this->hasMany('App\Models\CarKTV');
    }

    public function accountant(){
        $this->hasMany('App\Models\Accountant');
    }

    public function status(){
        return $this->belongsTo('App\Models\Status','order_status');
    }
}
