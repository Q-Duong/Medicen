<?php

namespace App\Services\Schedule;

use App\Models\HistoryEdit;
use Illuminate\Support\Facades\Auth;

/**
 * Ghi vào lịch sử chỉnh sửa đơn (history_edits, xem ở trang Lịch sử) ai xếp / sửa lịch xe, xe và người nào.
 * Cột history_action tối đa 100 ký tự nên viết gọn.
 */
class ScheduleHistory
{
    /** "Xe 2, TX Hùng, KTV Sơn, Dương" từ dòng car_ktvs (hoặc mảng car/driver/ktv1/ktv2) */
    public static function describe(object|array|null $car): string
    {
        if (!$car) {
            return '';
        }
        $c = (object) $car;
        $name = $c->car_name ?? $c->car ?? null;
        $driver = $c->car_driver_name ?? $c->driver ?? null;
        $ktv = array_filter([$c->car_ktv_name_1 ?? $c->ktv1 ?? null, $c->car_ktv_name_2 ?? $c->ktv2 ?? null]);

        return ScheduleBoard::carLabel((string) $name) . ', TX ' . self::short($driver ?: '—') . ($ktv ? ', KTV ' . implode(', ', array_map([self::class, 'short'], $ktv)) : '');
    }

    /** $verb: "Xếp lịch" | "Sửa lịch"; $where: trang thao tác; $before: mô tả lịch trước khi sửa */
    public static function record(int $orderId, string $verb, object|array $car, string $where, ?string $before = null): void
    {
        $now = self::describe($car);
        if ($before !== null && $before === $now) {
            return; // lưu lại y nguyên, không ghi
        }
        $text = "{$verb} ({$where}): {$now}" . ($before ? " · trước: {$before}" : '');
        HistoryEdit::create([
            'order_id' => $orderId,
            'user_name' => Auth::user()?->email ?? 'system',
            'history_action' => mb_substr($text, 0, 100),
        ]);
    }

    /** Tên gọn: 2 chữ cuối ("Nguyễn Chánh Sơn" -> "Chánh Sơn") cho vừa 100 ký tự */
    private static function short(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));

        return count($parts) > 2 ? implode(' ', array_slice($parts, -2)) : trim($name);
    }
}
