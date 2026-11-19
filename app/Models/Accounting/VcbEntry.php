<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

/** Một dòng sổ TK VCB chú Lộc */
class VcbEntry extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    public const SOURCES = ['opening' => 'Số dư đầu', 'manual' => 'Nhập tay', 'ct' => 'CT tự động', 'recurring' => 'Khoản cố định', 'import' => 'Từ file cũ'];

    protected $table = 'vcb_entries';
    protected $fillable = ['entry_date', 'description', 'thu', 'chi', 'source', 'ct_payment_id', 'recurring_id', 'period', 'note', 'user_id'];
    protected $casts = ['entry_date' => 'date'];

    /** Dòng do hệ thống sinh (CT, khoản cố định): sửa ở nơi gốc */
    public function isLinked(): bool
    {
        return $this->source === 'ct';
    }

    public function activityArea(): string
    {
        return 'so_vcb';
    }

    public function activityTitle(): string
    {
        return 'dòng sổ VCB "' . $this->description . '"';
    }

    public function activityFields(): array
    {
        return ['entry_date' => 'Ngày', 'description' => 'Nội dung', 'thu' => 'Thu', 'chi' => 'Chi', 'note' => 'Ghi chú'];
    }

    /** Dòng tự sinh (thu CT, khoản cố định) đã được ghi ở thao tác gốc */
    public function shouldRecordActivity(string $action): bool
    {
        return !in_array($this->source, ['ct', 'recurring'], true);
    }
}
