<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

/** Một dòng sổ TK VCB chú Lộc */
class VcbEntry extends Model
{
    public const SOURCES = ['opening' => 'Số dư đầu', 'manual' => 'Nhập tay', 'ct' => 'CT tự động', 'recurring' => 'Khoản cố định', 'import' => 'Từ file cũ'];

    protected $table = 'vcb_entries';
    protected $fillable = ['entry_date', 'description', 'thu', 'chi', 'source', 'ct_payment_id', 'recurring_id', 'period', 'note', 'user_id'];
    protected $casts = ['entry_date' => 'date'];

    /** Dòng do hệ thống sinh (CT, khoản cố định): sửa ở nơi gốc */
    public function isLinked(): bool
    {
        return $this->source === 'ct';
    }
}
