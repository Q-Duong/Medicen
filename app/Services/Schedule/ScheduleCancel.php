<?php

namespace App\Services\Schedule;

use App\Models\CarKTV;
use App\Models\HistoryEdit;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Hủy lịch xe của một đơn để đơn quay về "Chưa lên lịch" (giống đơn mới tạo):
 * dùng khi Sale đổi ngày / buổi chụp của đơn đã được xếp xe, vì xe + người đã xếp chỉ đúng cho ngày cũ.
 *
 * - Xóa dòng car_ktvs của đơn (đơn mới tạo cũng chưa có dòng này), để "Thêm lịch" hay trang Xếp lịch xe
 *   tạo lại sạch, không bị dòng cũ đã tắt chen vào.
 * - orders.schedule_status = 0, status_id = 5 (Chưa lên lịch) nếu đơn chưa chụp / chưa xử lý.
 * - Ghi lịch sử chỉnh sửa đơn và log (kèm xe / người cũ để tra lại).
 */
class ScheduleCancel
{
    /**
     * Đơn đã chụp xong: 2 = đã cập nhật số ca thực tế, 3 = đã chốt, 4 = đã cập nhật doanh thu.
     * Sửa ngày / buổi ở form đơn hàng thì KHÔNG hủy lịch xe của các đơn này (lịch là dữ liệu chấm công TX / KTV thật,
     * đổi ngày lúc này thường chỉ là sửa nhầm ngày).
     */
    public const SHOT_STATUSES = [2, 3, 4];

    public static function keepsScheduleOnReschedule($status): bool
    {
        return in_array((int) $status, self::SHOT_STATUSES, true);
    }

    /** @return array|null xe / người của lịch vừa hủy, null nếu đơn chưa có lịch */
    public function cancel(Order $order, string $reason): ?array
    {
        $car = CarKTV::where('order_id', $order->id)->where('car_active', 1)->first();
        if (!$car && (int) $order->schedule_status === 0) {
            return null;
        }

        $before = $car ? [
            'car' => $car->car_name,
            'driver' => $car->car_driver_name,
            'ktv' => array_values(array_filter([$car->car_ktv_name_1, $car->car_ktv_name_2])),
        ] : null;

        CarKTV::where('order_id', $order->id)->delete();
        $order->schedule_status = 0;
        if (in_array((int) $order->status_id, [0, 1, 5], true)) {
            $order->status_id = 5;
        }
        $order->save();

        $text = $before
            ? 'Hủy lịch ' . ScheduleBoard::carLabel($before['car']) . ', TX ' . ($before['driver'] ?: '—') . ($before['ktv'] ? ', KTV ' . implode(', ', $before['ktv']) : '')
            : 'Hủy lịch';
        HistoryEdit::create([
            'order_id' => $order->id,
            'user_name' => Auth::user()?->email ?? 'system',
            'history_action' => mb_substr("{$text} ({$reason})", 0, 100),
        ]);
        Log::info('Hủy lịch xe của đơn', ['order' => $order->id, 'reason' => $reason, 'before' => $before, 'by' => Auth::id()]);

        return $before;
    }
}
