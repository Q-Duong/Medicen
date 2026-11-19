<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

/** Ghi nhớ số cũ / công nợ cũ (không tính vào báo cáo) */
class AccMemo extends Model
{
    protected $table = 'acc_memos';
    protected $fillable = ['scope', 'title', 'period', 'amount', 'note', 'sort'];
}
