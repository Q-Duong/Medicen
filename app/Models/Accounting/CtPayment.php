<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một lần bệnh viện thanh toán công nợ CT */
class CtPayment extends Model
{
    /** Hình thức đang dùng. M-VCB chỉ còn ở dữ liệu cũ. */
    public const METHODS = ['HDB' => 'HDB (TK công ty)', 'L-VCB' => 'VCB chú Lộc'];
    public const LEGACY_METHODS = ['M-VCB' => 'M-VCB (không dùng nữa)'];

    protected $table = 'ct_payments';
    protected $fillable = ['receivable_id', 'paid_on', 'method', 'amount', 'note', 'user_id'];
    protected $casts = ['paid_on' => 'date'];

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(CtReceivable::class, 'receivable_id');
    }

    public static function methodLabel(?string $m): string
    {
        return (self::METHODS + self::LEGACY_METHODS)[$m] ?? (string) $m;
    }
}
