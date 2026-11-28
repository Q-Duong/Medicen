<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tóm tắt một ngày làm việc của Sales (nút "Tóm tắt bằng AI" hoặc lệnh sales:digest gửi email nhắc việc 17:00) */
class SalesDigest extends Model
{
    protected $fillable = ['day', 'content', 'stats', 'emailed_at'];

    protected $casts = ['day' => 'date', 'stats' => 'array', 'emailed_at' => 'datetime'];

    /** Bản tóm tắt cũ (trước 28/11/2026) có mục "Trên web" (thao tác của Sales): bỏ đi, mục này chỉ trợ lý trả lời giám đốc */
    public function getContentAttribute($value): ?string
    {
        return $value === null ? null : trim(preg_replace('/^#{1,3}\s*Trên web.*?(?=^#{1,3}\s|\z)/msu', '', $value));
    }
}
