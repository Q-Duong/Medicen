<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'comments',
        'total_revenue',
        'total_cost',
        'net_profit',
        'status',
        'profit_margin',
        'input_data'
    ];

    // Cast cột input_data về dạng mảng để dễ dùng
    protected $casts = [
        'input_data' => 'array',
    ];

    public function unit()
    {
        // Tham số 1: Tên Model bảng đơn vị (Unit::class)
        // Tham số 2: Tên cột khóa ngoại trong bảng profit_reports (bạn đang đặt là 'unit_name')
        // Tham số 3: Tên cột khóa chính trong bảng units (thường là 'id')
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }
}
