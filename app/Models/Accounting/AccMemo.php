<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

/** Ghi nhớ số cũ / công nợ cũ (không tính vào báo cáo) */
class AccMemo extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    protected $table = 'acc_memos';
    protected $fillable = ['scope', 'title', 'period', 'amount', 'note', 'sort'];

    public function activityArea(): string
    {
        return $this->scope === 'vcb' ? 'so_vcb' : 'cong_no_ct';
    }

    public function activityTitle(): string
    {
        return 'ghi nhớ "' . $this->title . '"';
    }

    public function activityFields(): array
    {
        return ['title' => 'Nội dung', 'period' => 'Kỳ', 'amount' => 'Số tiền', 'note' => 'Ghi chú'];
    }
}
