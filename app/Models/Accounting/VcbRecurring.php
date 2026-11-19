<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

/** Khoản trừ cố định hằng tháng trên TK VCB chú Lộc */
class VcbRecurring extends Model
{
    protected $table = 'vcb_recurrings';
    protected $fillable = ['description', 'chi', 'day', 'active'];
    protected $casts = ['active' => 'boolean'];
}
