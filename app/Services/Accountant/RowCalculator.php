<?php

namespace App\Services\Accountant;

use Carbon\Carbon;

/**
 * Tính các ô liên kết của một dòng công nợ, GIỐNG HỆT hàm calculateRow trong
 * public/assets/js/tool/org-accountant.js (trang tính trước để hiện ngay, máy chủ tính lại khi lưu
 * dựa trên số mới nhất trong database, nên người mở trang cũ không làm sai các ô này).
 *
 * Ô liên kết:
 * - order_price (Thành tiền): sửa Số lượng / Đơn giá thì = SL × ĐG; sửa Thành tiền thì giữ số nhập;
 *   còn lại giữ nguyên, riêng đang 0 mà SL × ĐG > 0 thì = SL × ĐG.
 * - order_discount (Thành tiền CK): sửa % CK / VAT / SL / ĐG / Thành tiền thì tính lại từ "% CK":
 *   "x%" = Thành tiền / 1,1 (VAT "không" thì / 1) × x%; "y/ca" = y × SL; số thường = số đó. Làm tròn.
 *   Sửa thẳng Thành tiền CK thì giữ số nhập.
 * - order_profit (Lợi nhuận) = Thành tiền − Thành tiền CK.
 * - accountant_owe (Còn nợ) = Thành tiền − Đã thanh toán (không trừ chiết khấu).
 * - accountant_day_payment (Ngày TT) là ngày khách thanh toán thật, chỉ đổi khi người dùng nhập. (Trước đây JS định
 *   "Ngày TT = Ngày HĐ + THCN" nhưng ô Ngày HĐ là ô chọn ngày dạng Y-m-d nên phép tính chưa bao giờ chạy, chỉ còn
 *   tác dụng phụ là XÓA Ngày TT khi Ngày HĐ trống; đã bỏ cả ở JS lẫn ở đây.)
 * - accountant_status: người dùng tự chọn thì giữ lựa chọn; sửa Ngày TT / Hình thức thì = 1 (đã TT) nếu có cả hai, ngược lại 0.
 */
class RowCalculator
{
    public const DERIVED = ['order_price', 'order_discount', 'order_profit', 'accountant_owe', 'accountant_day_payment', 'accountant_status'];

    /**
     * @param array $values giá trị hiện tại của dòng (DB) đã ghép các ô người dùng vừa sửa (đã làm sạch: tiền là số, ngày Y-m-d)
     * @param array $edited tên các ô người dùng tự sửa
     * @return array giá trị mới của các ô liên kết
     */
    public static function compute(array $values, array $edited): array
    {
        $e = array_flip($edited);
        $has = fn (string ...$keys) => (bool) array_intersect_key($e, array_flip($keys));
        // getNumber của JS: bỏ dấu chấm / phẩy rồi parseFloat (không phải số thì 0)
        $num = fn ($v) => self::jsParseFloat(str_replace(['.', ','], '', (string) $v)) ?? 0.0;

        $qty = $num($values['order_quantity'] ?? 0);
        $cost = $num($values['order_cost'] ?? 0);
        $paid = $num($values['accountant_amount_paid'] ?? 0);

        // B. Thành tiền
        if ($has('order_quantity', 'order_cost') && !$has('order_price')) {
            $price = $qty * $cost;
        } else {
            $price = $num($values['order_price'] ?? 0);
            if (!$has('order_price') && $price == 0 && $qty * $cost > 0) {
                $price = $qty * $cost;
            }
        }

        // C. Chiết khấu
        if ($has('order_percent_discount', 'order_vat', 'order_quantity', 'order_cost', 'order_price') && !$has('order_discount')) {
            $discount = self::discountFrom((string) ($values['order_percent_discount'] ?? ''), $price, $qty, (string) ($values['order_vat'] ?? ''));
        } else {
            $discount = $num($values['order_discount'] ?? 0);
        }

        $out = [
            'order_price' => (int) round($price),
            'order_discount' => (int) round($discount),
            'order_profit' => (int) round($price - $discount),
            'accountant_owe' => (int) round($price - $paid),
            'accountant_day_payment' => $values['accountant_day_payment'] ?? null,
            'accountant_status' => $values['accountant_status'] ?? 0,
        ];

        // Trạng thái thanh toán: người dùng tự chọn thì giữ đúng lựa chọn; sửa Ngày TT / Hình thức thì
        // đủ cả hai = 1 (đã TT), thiếu một trong hai = 0
        if ($has('accountant_status')) {
            $out['accountant_status'] = (int) ($values['accountant_status'] ?? 0);
        } elseif ($has('accountant_day_payment', 'accountant_method')) {
            $dp = trim((string) ($out['accountant_day_payment'] ?? ''));
            $method = trim((string) ($values['accountant_method'] ?? ''));
            $out['accountant_status'] = ($dp !== '' && $method !== '') ? 1 : 0;
        }

        return $out;
    }

    /** Chiết khấu từ chuỗi "% CK": "5%", "2,5%", "10.000/ca", "500.000" */
    public static function discountFrom(string $raw, float $price, float $qty, string $vat): float
    {
        $lower = mb_strtolower($raw, 'UTF-8');
        $clean = str_replace(',', '.', str_replace('.', '', $raw));
        $vatClean = trim(mb_strtolower($vat, 'UTF-8'));
        if (str_contains($lower, '%')) {
            $rate = self::jsParseFloat(str_replace('%', '', $clean)) ?? 0;
            $divisor = (str_contains($vatClean, 'không') || str_contains($vatClean, 'khong')) ? 1 : 1.1;
            $amount = ($price / $divisor) * ($rate / 100);
        } elseif (str_contains($lower, '/ca')) {
            $amount = (self::jsParseFloat(str_replace('/ca', '', mb_strtolower($clean, 'UTF-8'))) ?? 0) * $qty;
        } elseif (trim($clean) !== '' && self::jsParseFloat($clean) !== null) {
            $amount = self::jsParseFloat($clean);
        } else {
            $amount = 0;
        }

        // Math.round của JS: làm tròn .5 lên
        return floor($amount + 0.5);
    }

    /** parseFloat của JS: lấy số ở đầu chuỗi, không có thì null (NaN) */
    public static function jsParseFloat(string $s): ?float
    {
        return preg_match('/^\s*([-+]?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?)/', $s, $m) ? (float) $m[1] : null;
    }

    /** parseInt của JS (NaN thì 0, như "|| 0" trong JS) */
    public static function jsParseInt($s): int
    {
        return preg_match('/^\s*([-+]?\d+)/', (string) $s, $m) ? (int) $m[1] : 0;
    }

    /** Ngày dạng Y-m-d hoặc d/m/Y, sai định dạng / không có thật (vd 32/13/2026) thì null */
    public static function parseDate($v): ?Carbon
    {
        $v = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        return checkdate($mo, $d, $y) ? Carbon::create($y, $mo, $d)->startOfDay() : null;
    }
}
