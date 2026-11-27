<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Việc Sales cần làm với khách (AI tách từ nhật ký hoặc Sales tự thêm) — nhắc trên chuông khi đến hạn */
class SalesFollowup extends Model
{
    protected $fillable = ['sales_log_id', 'user_id', 'unit_id', 'customer', 'title', 'due_on', 'done_at', 'done_by'];

    protected $casts = ['due_on' => 'date', 'done_at' => 'datetime'];

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNull('done_at');
    }

    /** Chưa xong và đã tới hạn (hôm nay hoặc quá hạn) */
    public function scopeDue(Builder $q, ?Carbon $day = null): Builder
    {
        return $q->whereNull('done_at')->whereNotNull('due_on')->where('due_on', '<=', ($day ?? Carbon::today())->toDateString());
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(SalesLog::class, 'sales_log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function isOverdue(): bool
    {
        return !$this->done_at && $this->due_on && $this->due_on->lt(Carbon::today());
    }
}
