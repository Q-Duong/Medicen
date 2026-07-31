<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'month',
        'year',
    ];

    // Khai báo mối quan hệ 1-Nhiều với bảng chi tiết
    public function items()
    {
        return $this->hasMany(ReportItem::class, 'report_id');
    }
}