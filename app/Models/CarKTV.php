<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarKTV extends Model
{
    use HasFactory;
    
    protected $table = 'car_ktvs';

    public $timestamps = true;
    
    protected $fillable = [
        'order_id',
        'car_name',
        'car_active',
        'car_driver_name',
        'car_driver_phone',
        'car_ktv_name_1',
        'car_ktv_phone_1',
        'car_ktv_name_2',
        'car_ktv_phone_2'
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }
}
