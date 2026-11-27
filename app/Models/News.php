<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** Tin tức trên website (migration 2026_11_27_100000). Đã đăng = status published và published_at ≤ bây giờ (hẹn giờ đăng được). */
class News extends Model
{
    use SoftDeletes;
    use Concerns\RecordsActivity;

    protected $table = 'news';

    public const STATUSES = ['draft' => 'Bản nháp', 'published' => 'Đăng'];

    /** Gợi ý chuyên mục (ô chuyên mục vẫn gõ tự do được) */
    public const CATEGORIES = ['Tin công ty', 'Hoạt động khám sức khỏe', 'Kiến thức sức khỏe', 'Công nghệ y tế', 'Tuyển dụng'];

    protected $fillable = ['title', 'slug', 'category', 'summary', 'content', 'image', 'status', 'published_at', 'is_featured',
        'seo_title', 'seo_description', 'created_by', 'updated_by'];

    protected $casts = ['published_at' => 'datetime', 'is_featured' => 'boolean', 'views' => 'integer'];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->lte(now());
    }

    /** Trạng thái hiển thị: Bản nháp / Hẹn giờ / Đã đăng */
    public function stateLabel(): string
    {
        return $this->status !== 'published' ? 'Bản nháp' : ($this->isLive() ? 'Đã đăng' : 'Hẹn giờ');
    }

    public function imageUrl(): ?string
    {
        return $this->image ? route('news.image', $this->image) : null;
    }

    /** Mô tả ngắn: summary, không có thì cắt từ nội dung */
    public function excerpt(int $len = 180): string
    {
        $text = $this->summary ?: html_entity_decode(strip_tags((string) $this->content), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), $len);
    }

    /** Thời gian đọc ước tính (phút) */
    public function readMinutes(): int
    {
        $words = count(preg_split('/\s+/u', trim(html_entity_decode(strip_tags((string) $this->content), ENT_QUOTES | ENT_HTML5, 'UTF-8')), -1, PREG_SPLIT_NO_EMPTY));

        return max(1, (int) ceil($words / 220));
    }

    /** Slug không trùng (kể cả bài đã xóa mềm) */
    public static function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($text), 200, '') ?: 'tin-tuc'; // slug theo tiêu đề tiếng Việt (không dấu)
        $slug = $base;
        for ($i = 2; static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    public function activityArea(): string
    {
        return 'website';
    }

    public function activityTitle(): string
    {
        return 'tin tức "' . mb_strimwidth((string) $this->title, 0, 60, '…') . '"';
    }

    public function activityFields(): array
    {
        return ['title' => 'Tiêu đề', 'slug' => 'Đường dẫn', 'category' => 'Chuyên mục', 'summary' => 'Mô tả ngắn', 'image' => 'Ảnh đại diện',
            'status' => 'Trạng thái', 'published_at' => 'Ngày đăng', 'is_featured' => 'Nổi bật', 'seo_title' => 'Tiêu đề SEO', 'seo_description' => 'Mô tả SEO'];
    }
}
