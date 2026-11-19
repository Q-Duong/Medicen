<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

/** Khoản trừ cố định hằng tháng trên TK VCB chú Lộc */
class VcbRecurring extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    protected $table = 'vcb_recurrings';
    protected $fillable = ['description', 'chi', 'day', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function activityArea(): string
    {
        return 'so_vcb';
    }

    public function activityTitle(): string
    {
        return 'khoản chi cố định "' . $this->description . '"';
    }

    public function activityFields(): array
    {
        return ['description' => 'Nội dung', 'chi' => 'Số tiền', 'day' => 'Ngày trong tháng', 'active' => 'Đang dùng'];
    }
}
