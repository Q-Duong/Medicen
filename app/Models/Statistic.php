<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    public $timestamps = true; //set time to false
    protected $fillable = [
        'order_date', 'sales', 'profit', 'quantity', 'total_order', 'total_profit'
    ];
    protected $primaryKey = 'id_statistics';
    protected $table = 'statistics';
}
