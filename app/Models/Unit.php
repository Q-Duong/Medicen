<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    
    protected $table = 'units';

    public $timestamps = true;

    protected $fillable = [
        'unit_code',
        'unit_name'
    ];
    
    public function order()
    {
        $this->hasMany(Order::class);
    }
}
