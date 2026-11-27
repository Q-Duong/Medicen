<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tóm tắt một ngày làm việc của Sales (nút "Tóm tắt bằng AI" hoặc lệnh sales:digest gửi email nhắc việc 17:00) */
class SalesDigest extends Model
{
    protected $fillable = ['day', 'content', 'stats', 'emailed_at'];

    protected $casts = ['day' => 'date', 'stats' => 'array', 'emailed_at' => 'datetime'];
}
