<?php

namespace App\Services\Schedule;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Chấm công tài xế / KTV theo tháng. Đây là cách tính của trang Chấm công (admin/employee-timekeeping,
 * file Excel "Bao_cao_nhan_vien.xlsx"), tách ra để trang Xếp lịch xe dùng chung, đảm bảo cùng một con số.
 *
 * Đơn tính: ngày bắt đầu trong tháng, xe đang hoạt động (car_active = 1), không phải đơn phụ thu.
 * - Tài xế, "Tổng số ca": order_quantity × hệ số bộ phận (1 tư thế ×1, 2 tư thế ×2; siêu âm và bộ phận
 *   ngoài danh sách = 0), chỉ khi đơn ở trạng thái 2, 3, 4 (đã cập nhật số ca / đã xử lý / đã có doanh thu).
 * - Tài xế, "Số ca TX phụ KTV": khi driver_assistance = 1, lấy order_quantity_draft (trống thì order_quantity).
 * - KTV 1, KTV 2, "Số ca KTV": order_quantity_draft (số ca thực tế KTV nhập).
 * - Cả TX và KTV: "Ở lại đêm" cộng orders.overnight, "C.tác trên 250km" cộng car_ktvs.work_over_250.
 *
 * Thêm phần "dự kiến" (không có trong file Excel) để chia đều việc khi xếp lịch: đơn chưa đủ điều kiện tính
 * thì tạm tính tài xế theo order_quantity × hệ số, KTV theo order_quantity.
 */
class Timekeeping
{
    public const XRAY_1 = ['Phổi (1 Tư thế)', 'Cột sống thắt lưng (1 Tư thế)', 'Cột sống cổ (1 Tư thế)', 'Vai (1 Tư thế)', 'Gối (1 Tư thế)', 'Nhũ Ảnh', 'Khác'];

    public const XRAY_2 = ['Phổi (2 Tư thế)', 'Cột sống thắt lưng (2 Tư thế)', 'Cột sống cổ (2 Tư thế)', 'Vai (2 Tư thế)', 'Gối (2 Tư thế)'];

    public const ULTRASOUND = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];

    public const DONE_STATUSES = [2, 3, 4];

    /** Hệ số ca của tài xế theo bộ phận chụp: 1, 2, hoặc 0 (siêu âm / ngoài danh sách) */
    public static function multiplier(?string $service): int
    {
        if (in_array($service, self::ULTRASOUND, true)) {
            return 0;
        }
        if (in_array($service, self::XRAY_1, true)) {
            return 1;
        }

        return in_array($service, self::XRAY_2, true) ? 2 : 0;
    }

    /** Các đơn tính công trong tháng (cùng điều kiện với file Excel) */
    public function orders(Carbon $month): Collection
    {
        return Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('car_ktvs', 'car_ktvs.order_id', '=', 'orders.id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->whereBetween('order_details.ord_start_day', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->where('car_ktvs.car_active', 1)
            ->where('orders.order_surcharge', 0)
            ->select('orders.id', 'car_ktvs.car_name', 'car_ktvs.car_ktv_name_1', 'car_ktvs.car_ktv_name_2', 'car_ktvs.car_driver_name',
                'car_ktvs.driver_assistance', 'car_ktvs.work_over_250', 'orders.overnight', 'orders.order_quantity_draft',
                'orders.status_id', 'orders.order_quantity', 'order_details.ord_select', 'order_details.ord_start_day',
                'units.unit_abbreviation as unit')
            ->orderBy('order_details.ord_start_day')
            ->get();
    }

    /**
     * Bảng chấm công của tháng: tài xế trước, rồi theo tên (giống file Excel).
     *
     * @return Collection<int, array{nhan_vien: string, is_driver: bool, tong_so_ca: float, so_ca_ktv: float,
     *     so_ca_tx_phu_ktv: float, o_lai_dem: float, cong_tac_tren_250: float, du_kien: float}>
     */
    public function month(Carbon $month, ?Collection $orders = null): Collection
    {
        $summary = [];
        $init = function (string $name, bool $isDriver) use (&$summary) {
            $summary[$name] ??= [
                'nhan_vien' => $name,
                'is_driver' => false,
                'tong_so_ca' => 0,
                'so_ca_ktv' => 0,
                'so_ca_tx_phu_ktv' => 0,
                'o_lai_dem' => 0,
                'cong_tac_tren_250' => 0,
                'du_kien' => 0,
            ];
            if ($isDriver) {
                $summary[$name]['is_driver'] = true;
            }
        };

        foreach ($orders ?? $this->orders($month) as $order) {
            $driver = trim($order->car_driver_name ?? '');
            $ktvs = array_filter([trim($order->car_ktv_name_1 ?? ''), trim($order->car_ktv_name_2 ?? '')]);
            $draft = (float) $order->order_quantity_draft;
            $mult = self::multiplier($order->ord_select);
            $done = in_array((int) $order->status_id, self::DONE_STATUSES, true);

            if ($driver !== '') {
                $init($driver, true);
                if ($done) {
                    $summary[$driver]['tong_so_ca'] += $order->order_quantity * $mult;
                } else {
                    $summary[$driver]['du_kien'] += $order->order_quantity * $mult;
                }
                if ((int) $order->driver_assistance === 1) {
                    $summary[$driver]['so_ca_tx_phu_ktv'] += !empty($order->order_quantity_draft) ? $draft : (float) $order->order_quantity;
                }
                $summary[$driver]['o_lai_dem'] += (float) $order->overnight;
                $summary[$driver]['cong_tac_tren_250'] += (float) $order->work_over_250;
            }

            foreach ($ktvs as $ktv) {
                $init($ktv, false);
                $summary[$ktv]['so_ca_ktv'] += $draft;
                if (empty($order->order_quantity_draft)) {
                    $summary[$ktv]['du_kien'] += (float) $order->order_quantity;
                }
                $summary[$ktv]['o_lai_dem'] += (float) $order->overnight;
                $summary[$ktv]['cong_tac_tren_250'] += (float) $order->work_over_250;
            }
        }

        return collect($summary)->sort(function ($a, $b) {
            if ($a['is_driver'] !== $b['is_driver']) {
                return $a['is_driver'] ? -1 : 1;
            }

            return strnatcmp($a['nhan_vien'], $b['nhan_vien']);
        })->values();
    }

    /** Số ca chính của mỗi người (TX: tổng số ca, KTV: số ca KTV) + dự kiến, để chia đều khi xếp lịch */
    public function workload(Carbon $month): array
    {
        return $this->month($month)->mapWithKeys(fn ($r) => [$r['nhan_vien'] => [
            'done' => $r['is_driver'] ? $r['tong_so_ca'] : $r['so_ca_ktv'],
            'planned' => $r['du_kien'],
        ]])->all();
    }

    /** Đơn đã qua ngày chụp nhưng KTV chưa nhập số ca thực tế (chấm công sẽ thiếu) */
    public function missingActual(Carbon $month, ?Collection $orders = null): Collection
    {
        $today = Carbon::today()->toDateString();
        // Đơn chụp trước schedule.timekeeping_from không nhắc nữa (đã đối chiếu với công nợ kế toán)
        $from = (string) config('schedule.timekeeping_from', '2000-01-01');

        return ($orders ?? $this->orders($month))
            // Đơn chỉ có tiền, không có số ca (in thêm phim / giấy, phí khác…): không cần chấm công
            ->filter(fn ($o) => $o->ord_start_day < $today && $o->ord_start_day >= $from && (int) $o->order_quantity > 0
                && empty($o->order_quantity_draft) && trim((string) $o->car_ktv_name_1) !== '')
            ->values();
    }
}
