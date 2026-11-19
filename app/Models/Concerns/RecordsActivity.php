<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

/**
 * Tự ghi nhật ký khi model được thêm / sửa / xóa qua Eloquent (update() hàng loạt bằng query builder không ghi).
 *
 * Model dùng trait khai báo:
 *  - activityArea(): string           khóa trong ActivityLog::AREAS
 *  - activityTitle(): string          mô tả ngắn đối tượng, vd "Thu · CT Lagi tháng 9/2026"
 *  - activityFields(): array          [cột => nhãn] các cột cần theo dõi khi sửa (cột khác bỏ qua)
 *  - shouldRecordActivity(string $action): bool   (tùy chọn) bỏ qua dòng tự sinh
 */
trait RecordsActivity
{
    public static function bootRecordsActivity(): void
    {
        static::created(fn ($m) => $m->logActivity('them'));
        static::updated(fn ($m) => $m->logActivity('sua'));
        static::deleted(fn ($m) => $m->logActivity('xoa'));
    }

    public function shouldRecordActivity(string $action): bool
    {
        return true;
    }

    protected function logActivity(string $action): void
    {
        if (!$this->shouldRecordActivity($action)) {
            return;
        }
        $fields = $this->activityFields();
        $changes = [];
        if ($action === 'sua') {
            foreach (array_keys($this->getChanges()) as $col) {
                if (!isset($fields[$col])) {
                    continue;
                }
                // Cả hai giá trị đều qua cast của model (ngày → Carbon, tiền → số) để so và hiện cùng dạng
                $new = $this->getAttribute($col);
                $old = $this->getOriginal($col);
                if ($old instanceof \DateTimeInterface) {
                    $old = $old->format('d/m/Y');
                }
                if ($new instanceof \DateTimeInterface) {
                    $new = $new->format('d/m/Y');
                }
                if ((string) $old === (string) $new || (is_numeric($old) && is_numeric($new) && (float) $old == (float) $new)) {
                    continue;
                }
                $changes[$fields[$col]] = [$old, $new];
            }
            if (!$changes) {
                return;
            }
        } elseif ($action === 'them' || $action === 'xoa') {
            // Lưu giá trị chính lúc thêm / trước khi xóa để còn tra lại
            foreach ($fields as $col => $label) {
                $v = $this->getAttribute($col);
                if ($v instanceof \DateTimeInterface) {
                    $v = $v->format('d/m/Y');
                }
                if ($v !== null && $v !== '') {
                    $changes[$label] = $action === 'them' ? [null, $v] : [$v, null];
                }
            }
        }

        $verb = ActivityLog::ACTIONS[$action] ?? $action;
        ActivityLog::record($this->activityArea(), $action, $verb . ' ' . $this->activityTitle(), $this, $changes ?: null);
    }
}
