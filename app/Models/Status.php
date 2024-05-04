<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'status_name'
    ];
    protected $primaryKey = 'status_id';
    protected $table = 'status';

    public function order()
    {
        $this->hasMany('App\Models\Order');
    }
}
