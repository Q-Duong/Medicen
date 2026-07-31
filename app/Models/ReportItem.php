<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportItem extends Model
{
    use HasFactory;

    protected $table = 'report_items';

    protected $fillable = [
        'report_id',
        'type',
        'summary_group',
        'name',
        'expected_date',
        'estimated_amount',
        'actual_amount',
        'actual_date',
    ];

    // Ép kiểu dữ liệu để dễ dàng tính toán hoặc hiển thị ở frontend
    protected $casts = [
        'estimated_amount' => 'decimal:0',
        'actual_amount'    => 'decimal:0',
        'actual_date'      => 'date',
        'expected_date'    => 'date',
    ];

    // Khai báo mối quan hệ ngược lại: 1 mục chi tiết thuộc về 1 báo cáo
    public function report()
    {
        return $this->belongsTo(Report::class, 'report_id');
    }
}