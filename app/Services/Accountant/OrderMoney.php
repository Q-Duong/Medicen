<?php

namespace App\Services\Accountant;

use App\Models\Accountant;
use App\Models\Order;

/**
 * Tiền của đơn khi lên / sửa đơn ở trang Đơn hàng, dùng CÙNG công thức với trang Công nợ (RowCalculator):
 * Thành tiền CK theo "% CK" (x% / y/ca / số tiền), Lợi nhuận = Thành tiền − CK, Còn nợ = Thành tiền − Đã thanh toán.
 *
 * - Đơn mới: tính ngay khi lưu.
 * - Sửa đơn: chỉ tính lại khi các ô tiền thật sự đổi (để không xóa chiết khấu kế toán đã nhập tay ở trang Công nợ).
 * - Đơn đã cập nhật doanh thu (4) hoặc đã chốt (3): khóa, không sửa được các ô tiền.
 */
class OrderMoney
{
    /** Các ô ảnh hưởng đến tiền trên form đơn hàng */
    public const MONEY_FIELDS = ['order_quantity', 'order_cost', 'order_price', 'order_percent_discount', 'order_vat', 'order_all_in_one'];

    /** Trạng thái khóa tiền: 3 = Hoàn thành (đã chốt), 4 = Đã cập nhật doanh thu */
    public const LOCKED_STATUSES = [3, 4];

    public static function isLocked(Order $order): bool
    {
        return in_array((int) $order->status_id, self::LOCKED_STATUSES, true);
    }

    /** Giá trị trong database của các ô tiền, dạng form gửi lên (để ghi đè dữ liệu form khi đơn bị khóa) */
    public static function lockedFormValues(Order $order): array
    {
        return [
            'order_quantity' => $order->order_quantity,
            'order_cost' => (string) (int) $order->order_cost,
            'order_price' => (string) (int) $order->order_price,
            'order_percent_discount' => $order->order_percent_discount,
            'order_vat' => $order->order_vat,
            'order_all_in_one' => (int) $order->order_all_in_one,
        ];
    }

    public static function snapshot(Order $order): array
    {
        return collect(self::MONEY_FIELDS)->mapWithKeys(fn ($f) => [$f => (string) $order->getRawOriginal($f)])->all();
    }

    /** Các ô tiền đã đổi so với lúc trước khi sửa */
    public static function changedFields(array $before, Order $order): array
    {
        return array_values(array_filter(self::MONEY_FIELDS, fn ($f) => trim((string) ($before[$f] ?? '')) !== trim((string) $order->{$f})));
    }

    /**
     * Bộ phận Kết quả lưu số ca thực tế (ScheduleResultsController::update): số ca đổi thì tính lại tiền giống
     * như Sale sửa số lượng trên form đơn hàng — Thành tiền = SL × ĐG (đơn trọn gói giữ Thành tiền), rồi
     * Thành tiền CK, Lợi nhuận, Còn nợ. Đơn đã khóa tiền (3, 4) thì chỉ lưu số ca, không đụng tiền.
     *
     * Đơn đã cập nhật doanh thu (4): số ca vẫn lưu nhưng tiền giữ nguyên, và đơn được ĐÁNH DẤU "số ca đổi sau khi cập nhật
     * doanh thu" (qty_changed_from = số ca lúc kế toán cập nhật) để trang Công nợ báo kế toán xử lý.
     *
     * @return array{changed: bool, locked: bool, price_before: int, price_after: int, flag: ?array{from:int,to:int}}
     */
    public static function applyActualQuantity(Order $order, ?Accountant $accountant, $quantity, ?string $by = null): array
    {
        $before = self::snapshot($order);
        $priceBefore = (int) $order->getRawOriginal('order_price');
        $qtyBefore = (int) $order->getRawOriginal('order_quantity');
        $locked = self::isLocked($order);

        $order->order_quantity = $quantity;
        if (!$locked && !(int) $order->order_all_in_one) {
            $order->order_price = (int) round(self::qty($quantity) * (float) $order->order_cost);
        }

        $flag = null;
        if ((int) $order->status_id === 4) {
            $from = $order->qty_changed_from !== null ? (int) $order->qty_changed_from : $qtyBefore;
            $to = (int) self::qty($quantity);
            if ($to === $from) {
                // Đổi về đúng số ca kế toán đã tính tiền: hết lệch, bỏ đánh dấu
                self::clearQtyFlag($order);
            } elseif ($to !== $qtyBefore || $order->qty_changed_from === null) {
                $order->qty_changed_from = $from;
                $order->qty_changed_at = now();
                $order->qty_changed_by = $by;
                $flag = ['from' => $from, 'to' => $to];
            }
        }
        $order->save();

        $changed = self::changedFields($before, $order);
        if (!$locked) {
            self::sync($order, $accountant, $changed);
        }

        return [
            'changed' => (bool) $changed,
            'locked' => $locked,
            'price_before' => $priceBefore,
            'price_after' => (int) $order->order_price,
            'flag' => $flag,
        ];
    }

    private static function qty($v): float
    {
        return RowCalculator::jsParseFloat(str_replace(['.', ','], '', (string) $v)) ?? 0;
    }

    public static function clearQtyFlag(Order $order): void
    {
        $order->qty_changed_from = null;
        $order->qty_changed_at = null;
        $order->qty_changed_by = null;
    }

    /**
     * Kế toán bấm "Tính lại tiền theo số ca mới": Thành tiền = SL × ĐG (đơn trọn gói giữ Thành tiền), tính lại
     * Thành tiền CK, Lợi nhuận, Còn nợ theo công thức trang Công nợ; bỏ đánh dấu. Trả về thành tiền trước / sau.
     */
    public static function recalcForQuantity(Order $order, ?Accountant $accountant): array
    {
        $priceBefore = (int) $order->order_price;
        if (!(int) $order->order_all_in_one) {
            $order->order_price = (int) round(self::qty($order->order_quantity) * (float) $order->order_cost);
        }
        self::clearQtyFlag($order);
        $order->save();
        self::sync($order, $accountant, ['order_quantity']);

        return ['price_before' => $priceBefore, 'price_after' => (int) $order->order_price];
    }

    /**
     * Tính lại Thành tiền CK, Lợi nhuận, Còn nợ khi các ô tiền ($changed) đổi. Thành tiền giữ số trên form
     * (form đã tính SL × ĐG, trọn gói thì nhập tay).
     */
    public static function sync(Order $order, ?Accountant $accountant, array $changed): void
    {
        if (!$changed) {
            return;
        }
        $values = [
            'order_quantity' => $order->order_quantity,
            'order_cost' => $order->order_cost,
            'order_price' => $order->order_price,
            'order_percent_discount' => $order->order_percent_discount,
            'order_vat' => $order->order_vat,
            'order_discount' => $order->order_discount,
            'accountant_amount_paid' => $accountant?->accountant_amount_paid ?? 0,
        ];
        // Thành tiền luôn theo số trên form; chiết khấu tính lại vì ô tiền đã đổi
        $edited = array_values(array_unique(array_merge(array_diff($changed, ['order_all_in_one']), ['order_price'])));
        $calc = RowCalculator::compute($values, $edited);

        $order->order_discount = $calc['order_discount'];
        $order->order_profit = $calc['order_profit'];
        $order->save();

        if ($accountant) {
            $accountant->accountant_owe = $calc['accountant_owe'];
            $accountant->save();
        }
    }
}
