<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ghi nhớ của CFO: quyết định / tình hình để trợ lý dùng ở các lần hỏi sau (migration 2026_11_26_100000) */
class CfoNote extends Model
{
    use Concerns\RecordsActivity;

    protected $fillable = ['content', 'topic', 'remind_on', 'done', 'created_by', 'source'];

    protected $casts = ['remind_on' => 'date', 'done' => 'boolean'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activityArea(): string
    {
        return 'tai_chinh';
    }

    public function activityTitle(): string
    {
        return 'ghi nhớ CFO "' . mb_strimwidth((string) $this->content, 0, 60, '…') . '"';
    }

    public function activityFields(): array
    {
        return ['content' => 'Nội dung', 'topic' => 'Chủ đề', 'remind_on' => 'Ngày nhắc', 'done' => 'Đã xong'];
    }
}
