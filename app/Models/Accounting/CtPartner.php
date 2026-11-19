<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Bệnh viện hợp tác đặt máy CT */
class CtPartner extends Model
{
    protected $table = 'ct_partners';
    protected $fillable = ['code', 'name', 'active', 'sort'];
    protected $casts = ['active' => 'boolean'];

    public function receivables(): HasMany
    {
        return $this->hasMany(CtReceivable::class, 'partner_id');
    }
}
