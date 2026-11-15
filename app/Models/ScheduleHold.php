<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lịch giữ chỗ (khách đặt trước, chưa lên đơn) */
class ScheduleHold extends Model
{
    protected $fillable = ['hold_start', 'hold_end', 'hold_session', 'car', 'driver', 'ktv1', 'ktv2', 'unit_id',
        'customer', 'expected_cas', 'note', 'status', 'order_id', 'created_by'];

    protected $casts = ['hold_start' => 'date', 'hold_end' => 'date'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
