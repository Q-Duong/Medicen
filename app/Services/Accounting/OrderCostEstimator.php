<?php

namespace App\Services\Accounting;

use App\Services\ProfitSettings;
use Carbon\Carbon;

/**
 * Giá vốn ƯỚC TÍNH cho từng đơn XQ, theo đúng công thức và đơn giá mặc định của "Bảng tính hiệu quả kinh doanh"
 * (config/profit.php + giá đã cập nhật trong DB, xem App\Services\ProfitCalculator).
 *
 * Lấy từ đơn: số ca (XQ phổi hay CSTL theo bộ phận chụp), số ngày (ngày chụp đầu → cuối), khoảng cách (ô Khoảng cách trang
 * Công nợ đã là km KHỨ HỒI), tài xế / KTV (tên có chữ "Thuê" = thuê ngoài), công tác trên 250 km (ô KTV chọn, hoặc
 * khoảng cách khứ hồi > 500 km), bác sĩ đọc, phim 10x12, VAT. Không có trong đơn: BOT,
 * dầu phát sinh, BS đọc trực tiếp → bỏ qua. Doanh thu dùng số thật của đơn (Thành tiền − chiết khấu).
 * Đơn phụ thu: không có chi phí riêng (toàn bộ là lãi của chuyến đi kèm).
 */
class OrderCostEstimator
{
    private ?array $p = null;

    public function __construct(private ProfitSettings $settings)
    {
    }

    /** @param object|array $o dòng đơn (orders + order_details + accountants + car_ktvs) */
    public function estimate($o): array
    {
        $o = (object) $o;
        $p = $this->p ??= $this->settings->all();
        $g = fn (string $k) => (float) ($p[$k] ?? 0);

        $revenue = max(0, (int) ($o->order_price ?? 0) - (int) ($o->order_discount ?? 0));
        if (!empty($o->order_surcharge)) {
            return ['doanh_thu' => $revenue, 'chi_phi_truc_tiep' => 0, 'phi_quan_ly' => 0, 'vat_phai_nop' => 0, 'tong_chi_phi' => 0,
                'lai_uoc_tinh' => $revenue, 'bien_lai_pct' => $revenue ? 100.0 : null, 'chi_tiet' => []];
        }

        $qty = max(0, (int) ($o->order_quantity ?? 0));
        $select = mb_strtolower((string) ($o->ord_select ?? ''));
        $isCstl = str_contains($select, 'cột sống') || str_contains($select, 'cstl');
        $xq = $isCstl ? 0 : $qty;
        $cstl = $isCstl ? $qty : 0;
        $units = $xq + 2 * $cstl;

        $start = $this->date($o->ord_start_day ?? null);
        $end = $this->date($o->ord_end_day ?? null) ?? $start;
        $days = $start && $end ? max(1, $start->diffInDays($end) + 1) : 1;
        $nights = $days - 1;
        // Ô Khoảng cách đã là km khứ hồi (cả đi lẫn về)
        $km = (float) preg_replace('/[^\d.]/', '', (string) ($o->accountant_distance ?? ''));
        // Phụ cấp xa: KTV đã chọn "Công tác trên 250km" hoặc đường đi một chiều > 250 km
        $far = !empty($o->work_over_250) || $km / 2 > 250;

        $hired = fn ($name) => $name !== null && trim((string) $name) !== '' && str_contains(mb_strtolower((string) $name), 'thuê');
        $ktvs = array_values(array_filter([$o->car_ktv_name_1 ?? null, $o->car_ktv_name_2 ?? null], fn ($n) => trim((string) $n) !== ''));
        if (!$ktvs) {
            $ktvs = ['(chưa xếp)'];
        }
        $people = 1 + count($ktvs);
        $doctor = in_array(mb_strtolower(trim((string) ($o->ord_doctor_read ?? ''))), ['có', 'co', '1', 'yes'], true);

        $ktvCost = $g('chi_ktv_gia_ca') * $units;
        foreach ($ktvs as $name) {
            $ktvCost += $hired($name) ? $g('chi_thue_ktv_gia') * $days : $g('chi_ktv_gia_ngay') * $days;
        }
        $fuelNorm = $g('chi_dau_dinhmuc');

        $items = [
            'vat_tu_phim' => $g('chi_baophim_gia') * $xq + $g('chi_phim16_gia') * $xq + $g('chi_phim810_gia') * $cstl
                + $g('chi_phim1012_gia') * (int) ($o->accountant_10X12 ?? 0),
            'bac_si_doc' => $doctor ? $g('chi_bsdoc_gia') * $units : 0,
            'tai_xe' => $hired($o->car_driver_name ?? null) ? $g('chi_thue_tx_gia') * $days : $g('chi_taixe_gia') * $days,
            'ktv' => $ktvCost,
            'bo_phan_ket_qua' => $g('chi_bpkq_gia') * $units,
            'phu_cap_xa' => $far ? $g('chi_phucap_xa_gia') * $people : 0,
            'dau' => $fuelNorm > 0 ? $km / $fuelNorm * $g('chi_dau_gia') : 0,
            'dang_kiem' => $g('chi_dangkiem_gia') * $days,
            'khach_san' => $g('chi_ks_gia') * $nights,
            'phu_cap_dem' => $g('chi_dem_gia') * $nights * $people,
        ];
        $direct = array_sum($items);
        $mgmt = $revenue * $g('chi_quanly_phantram') / 100;
        $vatRate = (float) preg_replace('/[^\d.]/', '', (string) ($o->order_vat ?? '')) / 100;
        $vat = $vatRate > 0 ? $revenue / (1 + $vatRate) * $vatRate : 0;
        $total = $direct + $mgmt + $vat;
        $profit = $revenue - $total;

        return [
            'doanh_thu' => $revenue,
            'chi_phi_truc_tiep' => (int) round($direct),
            'phi_quan_ly' => (int) round($mgmt),
            'vat_phai_nop' => (int) round($vat),
            'tong_chi_phi' => (int) round($total),
            'lai_uoc_tinh' => (int) round($profit),
            'bien_lai_pct' => $revenue ? round($profit / $revenue * 100, 1) : null,
            'chi_tiet' => array_map(fn ($v) => (int) round($v), array_filter($items)),
        ];
    }

    private function date($v): ?Carbon
    {
        if (!$v) {
            return null;
        }
        try {
            return Carbon::parse(substr((string) $v, 0, 10))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
