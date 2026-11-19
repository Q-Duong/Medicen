<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportItem extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    use HasFactory;

    protected $table = 'report_items';

    protected $fillable = [
        'report_id',
        'type',
        'summary_group',
        'auto_key',
        'name',
        'sort_order',
        'expected_date',
        'estimated_amount',
        'actual_amount',
        'actual_date',
    ];

    // Ép kiểu dữ liệu để dễ dàng tính toán hoặc hiển thị ở frontend
    protected $casts = [
        'estimated_amount' => 'decimal:0',
        'actual_amount'    => 'decimal:0',
        'actual_date'      => 'datetime',
        'expected_date'    => 'date',
    ];

    // Khai báo mối quan hệ ngược lại: 1 mục chi tiết thuộc về 1 báo cáo
    public function report()
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    // ---- Nhật ký thao tác (Concerns\RecordsActivity) ----
    public function activityArea(): string
    {
        return 'bao_cao_thu_chi';
    }

    public function activityTitle(): string
    {
        $r = $this->report;
        $period = $r ? (is_numeric($r->month) ? 'T' . $r->month . '/' . $r->year : 'năm ' . $r->year) : '';

        return 'dòng ' . ($this->type === 'chi' ? 'chi' : 'thu') . ' "' . $this->name . '" ' . $period;
    }

    public function activityFields(): array
    {
        return ['name' => 'Tên khoản', 'estimated_amount' => 'Dự kiến', 'actual_amount' => 'Thực tế',
            'expected_date' => 'Ngày dự kiến', 'actual_date' => 'Ngày thực tế'];
    }

    /** Dòng tự động (VCB chú Lộc, CT): số tiền do hệ thống đồng bộ, chỉ ghi khi người dùng đổi ngày dự kiến */
    public function shouldRecordActivity(string $action): bool
    {
        return !$this->auto_key || ($action === 'sua' && $this->wasChanged('expected_date'));
    }
}
