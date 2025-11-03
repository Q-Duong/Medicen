<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceAnalysis extends Model
{
    use HasFactory;

    protected $table = 'performance_analysis';

    public $timestamps = true;
    
    protected $fillable = [
        'order_id',
        'user_id',
        'part',
        'performance',
        'description',
        'first_edit_time',
        'status'
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function profile(){
        return $this->belongsTo(Profile::class);
    }
}
