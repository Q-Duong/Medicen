<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Nhật ký thao tác (ai, lúc nào, ở trang nào, làm gì, đổi gì).
 * Ghi tự động qua trait Concerns\RecordsActivity (các model kế toán) hoặc gọi ActivityLog::record() ở controller.
 * Chỉ ghi khi có người đăng nhập (lệnh artisan / nhập file hàng loạt không ghi).
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const AREAS = [
        'bao_cao_thu_chi' => 'Báo cáo thu chi',
        'cong_no' => 'Công nợ',
        'cong_no_ct' => 'Công nợ CT',
        'so_vcb' => 'Sổ VCB chú Lộc',
        'du_lieu_ke_toan' => 'Dữ liệu kế toán',
        'tro_ly_ai' => 'Trợ lý AI',
        'dang_nhap' => 'Đăng nhập',
        // Các trang khác: tên lấy theo config('activity.areas')
    ];

    public const ACTIONS = ['them' => 'Thêm', 'sua' => 'Sửa', 'xoa' => 'Xóa', 'tai_len' => 'Tải lên', 'nhap' => 'Nhập file', 'tai_ve' => 'Tải / xuất file',
        'thao_tac' => 'Thao tác', 'dang_nhap' => 'Đăng nhập', 'dang_xuat' => 'Đăng xuất', 'dang_nhap_sai' => 'Đăng nhập sai'];

    protected $fillable = ['user_id', 'user_name', 'area', 'action', 'route', 'subject_type', 'subject_id', 'summary', 'changes', 'ip', 'user_agent'];

    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    private static int $muted = 0;

    /** Lưu JSON giữ nguyên tiếng Việt để tìm kiếm LIKE được */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Tạm tắt ghi nhật ký (thao tác hệ thống tự sinh kèm theo, vd đồng bộ dòng tự động) */
    public static function muted(callable $fn): mixed
    {
        self::$muted++;
        try {
            return $fn();
        } finally {
            self::$muted--;
        }
    }

    public static function record(string $area, string $action, string $summary, ?Model $subject = null, ?array $changes = null): void
    {
        $user = Auth::user();
        if (self::$muted > 0 || !$user) {
            return;
        }
        // Trang đã tự ghi nhật ký chi tiết: RecordActivity không ghi thêm dòng chung cho request này
        app(\App\Services\Activity\ActivityRecorder::class)->markExplicit();
        try {
            static::create([
                'route' => request()?->route()?->getName(),
                'user_id' => $user->id,
                'user_name' => $user->display_name ?? $user->name,
                'area' => $area,
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'summary' => mb_substr($summary, 0, 500),
                'changes' => $changes ?: null,
                'ip' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            // Ghi nhật ký lỗi không được chặn thao tác chính
            Log::warning('Không ghi được nhật ký thao tác', ['area' => $area, 'error' => $e->getMessage()]);
        }
    }

    public function areaLabel(): string
    {
        if (isset(self::AREAS[$this->area])) {
            return self::AREAS[$this->area];
        }
        foreach (config('activity.areas', []) as [$key, $label]) {
            if ($key === $this->area) {
                return $label;
            }
        }

        return $this->area === 'khac' ? 'Khác' : $this->area;
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /** Thay đổi dạng "Trường: cũ → mới" để hiện / đưa cho trợ lý */
    public function changeLines(): array
    {
        $lines = [];
        // getAttribute: trong model, $this->changes là thuộc tính nội bộ của Eloquent (các cột vừa đổi), không phải cột changes
        foreach ((array) $this->getAttribute('changes') as $field => $pair) {
            [$old, $new] = array_pad((array) $pair, 2, null);
            $lines[] = $field . ': ' . self::show($old) . ' → ' . self::show($new);
        }

        return $lines;
    }

    private static function show($v): string
    {
        if ($v === null || $v === '') {
            return '(trống)';
        }
        if (is_bool($v)) {
            return $v ? 'Có' : 'Không';
        }
        if (is_numeric($v) && abs((float) $v) >= 1000 && floor((float) $v) == (float) $v) {
            return number_format((float) $v, 0, ',', '.');
        }

        return mb_strimwidth((string) $v, 0, 120, '…');
    }
}
