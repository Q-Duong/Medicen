<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarKTV extends Model
{
    public $timestamps = true; 
    protected $fillable = [
    	'order_id','car_name', 'car_active', 'car_driver_name', 'car_driver_phone', 'car_ktv_name_1', 'car_ktv_phone_1', 'car_ktv_name_2', 'car_ktv_phone_2'
    ];
    protected $primaryKey = 'car_ktv_id';
 	protected $table = 'tbl_car_ktv';

    public function order(){
        return $this->belongsTo('App\Models\Order','order_id');
    }

    public function staff()
    {
        return $this->hasOne('App\Models\Staff');
    }
 }

