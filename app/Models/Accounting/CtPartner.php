<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Bệnh viện hợp tác đặt máy CT */
class CtPartner extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    protected $table = 'ct_partners';
    protected $fillable = ['code', 'name', 'active', 'sort'];
    protected $casts = ['active' => 'boolean'];

    public function receivables(): HasMany
    {
        return $this->hasMany(CtReceivable::class, 'partner_id');
    }

    public function activityArea(): string
    {
        return 'cong_no_ct';
    }

    public function activityTitle(): string
    {
        return 'bệnh viện ' . $this->name;
    }

    public function activityFields(): array
    {
        return ['code' => 'Mã', 'name' => 'Tên', 'active' => 'Đang theo dõi'];
    }
}
