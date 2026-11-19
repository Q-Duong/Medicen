<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Công nợ CT: một bệnh viện, một tháng (kind: thuong | bhyt) */
class CtReceivable extends Model
{
    public const KINDS = ['thuong' => 'Ca chụp', 'bhyt' => 'BHYT'];

    protected $table = 'ct_receivables';
    protected $fillable = ['partner_id', 'year', 'month', 'kind', 'label', 'quantity', 'revenue', 'cost', 'hospital_share',
        'medicen_share', 'hospital_collect', 'medicen_collect', 'note', 'user_id'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(CtPartner::class, 'partner_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CtPayment::class, 'receivable_id')->orderBy('paid_on')->orderBy('id');
    }

    public function getProfitAttribute(): int
    {
        return (int) $this->revenue - (int) $this->cost;
    }

    public function getPaidAttribute(): int
    {
        return (int) ($this->relationLoaded('payments') ? $this->payments->sum('amount') : $this->payments()->sum('amount'));
    }

    public function getOutstandingAttribute(): int
    {
        return (int) $this->medicen_collect - $this->paid;
    }

    /** "Á Châu T6/2026 (BHYT bhyt Q1+2)" */
    public function getTitleAttribute(): string
    {
        return ($this->partner->name ?? '?') . ' T' . $this->month . '/' . $this->year
            . ($this->kind === 'bhyt' ? ' (BHYT' . ($this->label ? ' ' . $this->label : '') . ')' : '');
    }
}
