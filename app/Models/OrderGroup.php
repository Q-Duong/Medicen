<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Nhóm đơn đi chung (cùng đơn vị, cùng ngày, một chuyến xe) */
class OrderGroup extends Model
{
    protected $fillable = ['code', 'unit_id', 'group_date', 'created_by'];

    protected $casts = ['group_date' => 'date'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'order_group_id');
    }
}
