<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Một lần Sales ghi lại trao đổi với khách (chat Zalo, cuộc gọi, gặp mặt, ghi chú) — migration 2026_11_28_100000 */
class SalesLog extends Model
{
    use Concerns\RecordsActivity;

    public const KINDS = ['chat' => 'Chat Zalo', 'call' => 'Cuộc gọi', 'meeting' => 'Gặp khách', 'note' => 'Ghi chú'];

    public const KIND_ICONS = ['chat' => 'far fa-comment-dots', 'call' => 'fas fa-phone-alt', 'meeting' => 'fas fa-handshake', 'note' => 'far fa-sticky-note'];

    protected $fillable = ['user_id', 'happened_on', 'kind', 'unit_id', 'customer', 'raw_text', 'images', 'summary', 'important', 'ai_status', 'ai_error'];

    protected $casts = ['happened_on' => 'date', 'images' => 'array', 'important' => 'boolean'];

    /**
     * Nhật ký thêm / sửa / xóa thì bản tóm tắt của ngày đó (sales_digests) không còn đúng: xóa đi để trang hiện "Chưa có" và
     * lần tóm tắt sau (nút trên trang / email 17:00) viết lại theo nhật ký hiện tại.
     */
    protected static function booted(): void
    {
        $clear = function (SalesLog $l) {
            $days = array_filter([$l->happened_on?->toDateString(), $l->getOriginal('happened_on') ? \Carbon\Carbon::parse($l->getOriginal('happened_on'))->toDateString() : null]);
            foreach (array_unique($days) as $d) {
                SalesDigest::whereDate('day', $d)->delete();
            }
        };
        static::saved($clear);
        static::deleted($clear);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(SalesFollowup::class);
    }

    public function activityArea(): string
    {
        return 'nhat_ky_sales';
    }

    public function activityTitle(): string
    {
        return 'nhật ký trao đổi khách ' . (self::KINDS[$this->kind] ?? '') . ($this->customer ? ' · ' . $this->customer : '');
    }

    public function activityFields(): array
    {
        return ['customer' => 'Khách', 'unit_id' => 'Đơn vị', 'kind' => 'Loại', 'summary' => 'Tóm tắt', 'happened_on' => 'Ngày'];
    }
}
