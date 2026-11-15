<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lịch nghỉ của tài xế / KTV, dùng khi xếp lịch và gợi ý AI */
class StaffLeave extends Model
{
    public const SESSIONS = ['Cả ngày', 'Sáng', 'Chiều'];

    protected $fillable = ['staff_id', 'leave_start', 'leave_end', 'leave_session', 'leave_note', 'created_by'];

    protected $casts = ['leave_start' => 'date', 'leave_end' => 'date'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
