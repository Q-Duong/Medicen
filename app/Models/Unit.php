<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'unit_code', 'unit_name'
    ];
    // protected $primaryKey = 'unit_id';
    protected $table = 'units';

    public function order()
    {
        $this->hasMany('App\Models\Order');
    }
}
