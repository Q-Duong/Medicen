<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Giả định "nếu… thì…" cho CFO, tính từ số liệu thật (đơn hàng + chi phí ước tính theo đơn giá mặc định, dòng tiền 13 tuần):
 *  - tang_gia:  tăng X đ/ca (theo giá trên đơn) cho các đơn vị → doanh thu / lãi tăng mỗi tháng (chi phí không đổi).
 *  - bo_khach:  không nhận đơn của đơn vị → mất doanh thu và lãi đóng góp (lãi ước tính sau chi phí chuyến, phí quản lý, VAT).
 *  - bo_xe:     thôi chạy một xe → mất lãi đóng góp các đơn xe đó chạy, cộng phần chi phí cố định tiết kiệm được (người dùng nhập).
 *  - dong_tien: thêm khoản thu / chi (vay thêm, bán tài sản, chi lớn) → so dòng tiền 13 tuần có / không có khoản đó.
 * Kỳ tham chiếu: N tháng gần nhất (mặc định 3), quy ra bình quân / tháng.
 */
class WhatIf
{
    public function __construct(private CfoAnalytics $analytics, private CashForecast $forecast)
    {
    }

    public function run(array $in): array
    {
        $type = (string) ($in['loai'] ?? '');
        $months = max(1, min(12, (int) ($in['so_thang_tham_chieu'] ?? 3)));
        $to = Carbon::yesterday();
        $from = $to->copy()->subMonthsNoOverflow($months)->addDay();
        $base = ['ky_tham_chieu' => $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y') . " ({$months} tháng)",
            'luu_y' => 'Ước tính từ đơn hàng thật trong kỳ tham chiếu; chi phí chuyến theo đơn giá mặc định (OrderCostEstimator). Chi phí cố định (khấu hao, văn phòng, lãi vay) không đổi trừ khi ghi rõ.'];

        return match ($type) {
            'tang_gia' => $base + $this->raisePrice($this->orders($from, $to, $in), (int) ($in['them_moi_ca'] ?? 0), $months),
            'bo_khach' => $base + $this->dropCustomer($this->orders($from, $to, $in), $months),
            'bo_xe' => $base + $this->dropCar($from, $to, (string) ($in['xe'] ?? ''), (int) ($in['tiet_kiem_co_dinh_thang'] ?? 0), $months),
            'dong_tien' => $this->cash((array) ($in['khoan'] ?? [])),
            default => throw new InvalidArgumentException('loai phải là tang_gia, bo_khach, bo_xe hoặc dong_tien.'),
        };
    }

    /** Đơn chính (không phụ thu) của các đơn vị được chọn ('' / 'tat_ca' = tất cả) */
    private function orders(Carbon $from, Carbon $to, array $in): Collection
    {
        $rows = $this->analytics->orderRows($from, $to)->where('order_surcharge', 0);
        $kw = trim((string) ($in['don_vi'] ?? ''));
        if ($kw === '' || in_array(mb_strtolower($kw), ['tat_ca', 'tất cả', 'all'], true)) {
            return $rows;
        }
        $keys = array_filter(array_map(fn ($k) => mb_strtolower(trim($k)), explode(',', $kw)));

        return $rows->filter(fn ($r) => collect($keys)->contains(fn ($k) => str_contains(mb_strtolower((string) $r->unit_abbreviation), $k)));
    }

    private function raisePrice(Collection $o, int $extra, int $months): array
    {
        if ($extra === 0) {
            throw new InvalidArgumentException('Cần them_moi_ca (đ/ca theo giá trên đơn; âm nếu giảm giá).');
        }
        $byUnit = $o->groupBy('unit_abbreviation')->map(fn ($g, $u) => ['don_vi' => $u ?: '(chưa có)', 'so_ca_thang' => (int) round($g->sum('qty') / $months),
            'doanh_thu_tang_thang' => (int) round($g->sum('qty') * $extra / $months),
            'gia_binh_quan_hien_tai' => $g->sum('qty') ? (int) round($g->sum('net') / $g->sum('qty')) : null])
            ->sortByDesc('doanh_thu_tang_thang')->values();
        $qty = $o->sum('qty');
        $gain = $qty * $extra / $months;

        return [
            'gia_dinh' => ($extra > 0 ? 'Tăng ' : 'Giảm ') . number_format(abs($extra), 0, ',', '.') . ' đ/ca cho ' . $byUnit->count() . ' đơn vị',
            'so_ca_binh_quan_thang' => (int) round($qty / $months),
            'doanh_thu_va_lai_tang_moi_thang' => (int) round($gain),
            'doanh_thu_va_lai_tang_moi_nam' => (int) round($gain * 12),
            'giai_thich' => 'Chi phí chuyến không đổi nên phần tăng giá cộng thẳng vào lãi (cùng quy ước với báo cáo quản trị: giá trên đơn coi như doanh thu, vì cột VAT của đơn chỉ ghi có / không, không có %). Chưa tính rủi ro khách giảm số ca / chuyển nhà cung cấp.',
            'theo_don_vi' => $byUnit->take(15)->all(),
        ];
    }

    private function dropCustomer(Collection $o, int $months): array
    {
        if ($o->isEmpty()) {
            throw new InvalidArgumentException('Không có đơn của đơn vị này trong kỳ tham chiếu (kiểm tra tên viết tắt, don_vi).');
        }
        $units = $o->groupBy('unit_abbreviation')->map(fn ($g, $u) => ['don_vi' => $u, 'so_don' => $g->count(), 'so_ca' => (int) $g->sum('qty'),
            'doanh_thu_thang' => (int) round($g->sum('net') / $months), 'lai_dong_gop_thang' => (int) round($g->sum('profit') / $months),
            'bien_lai_pct' => $g->sum('net') ? round($g->sum('profit') / $g->sum('net') * 100, 1) : null])->values();
        $profit = $o->sum('profit') / $months;

        return [
            'gia_dinh' => 'Không nhận đơn của: ' . $units->pluck('don_vi')->implode(', '),
            'doanh_thu_mat_moi_thang' => (int) round($o->sum('net') / $months),
            'lai_dong_gop_mat_moi_thang' => (int) round($profit),
            'ket_luan' => $profit < 0 ? 'Các đơn vị này đang LỖ đóng góp: bỏ đi thì lãi tăng khoảng ' . number_format(-$profit, 0, ',', '.') . ' đ/tháng (nếu xe / người không bị bỏ trống).'
                : 'Bỏ đi thì lãi giảm khoảng ' . number_format($profit, 0, ',', '.') . ' đ/tháng; chi phí cố định không giảm.',
            'theo_don_vi' => $units->all(),
        ];
    }

    private function dropCar(Carbon $from, Carbon $to, string $car, int $saving, int $months): array
    {
        if ($car === '') {
            throw new InvalidArgumentException('Cần xe (vd "3", "Xe 3", "Siêu âm").');
        }
        $all = $this->analytics->orderRows($from, $to);
        $key = mb_strtolower(trim(preg_replace('/^xe\s*/iu', '', $car)));
        $o = $all->filter(fn ($r) => mb_strtolower(trim(preg_replace('/^xe\s*/iu', '', (string) $r->car_name))) === $key
            || str_contains(mb_strtolower((string) CfoAnalytics::carLabel((string) $r->car_name)), mb_strtolower($car)));
        if ($o->isEmpty()) {
            throw new InvalidArgumentException('Không có đơn nào của xe "' . $car . '" trong kỳ tham chiếu.');
        }
        $days = $o->pluck('ord_start_day')->unique()->count();
        $profit = $o->sum('profit') / $months;
        $delta = -$profit + $saving;

        return [
            'gia_dinh' => 'Thôi chạy ' . CfoAnalytics::carLabel((string) $o->first()->car_name),
            'ngay_chay_thang' => round($days / $months, 1),
            'doanh_thu_thang' => (int) round($o->sum('net') / $months),
            'lai_dong_gop_thang' => (int) round($profit),
            'lai_moi_ngay_chay' => $days ? (int) round($o->sum('profit') / $days) : null,
            'tiet_kiem_co_dinh_thang_nguoi_dung_nhap' => $saving ?: null,
            'thay_doi_lai_moi_thang' => (int) round($delta),
            'ket_luan' => ($delta >= 0 ? 'Lãi tăng' : 'Lãi giảm') . ' khoảng ' . number_format(abs($delta), 0, ',', '.') . ' đ/tháng'
                . ($saving ? '' : ' (chưa tính chi phí cố định tiết kiệm được: bảo hiểm, đăng kiểm, khấu hao / tiền bán xe — hỏi người dùng để tính lại với tiet_kiem_co_dinh_thang)')
                . '. Giả định các đơn của xe này bị mất; nếu dồn được sang xe khác thì giữ được phần lớn lãi.',
        ];
    }

    private function cash(array $items): array
    {
        if (!$items) {
            throw new InvalidArgumentException('Cần khoan: [{loai: thu|chi, ngay: YYYY-MM-DD, so_tien, ten}].');
        }
        $baseF = $this->forecast->build([]);
        $newF = $this->forecast->build(['khoan_them' => $items]);
        $low = fn ($f) => collect($f['tuan'])->sortBy('cuoi_ky')->first();
        $weeks = collect($baseF['tuan'])->map(fn ($w, $i) => ['tuan' => $w['tuan'], 'tu' => $w['tu'], 'so_du_hien_tai' => $w['cuoi_ky'],
            'so_du_gia_dinh' => $newF['tuan'][$i]['cuoi_ky'], 'duoi_nguong' => $newF['tuan'][$i]['canh_bao']])->all();

        return [
            'gia_dinh' => collect($items)->map(fn ($k) => (($k['loai'] ?? 'chi') === 'thu' ? 'Thu ' : 'Chi ') . number_format((float) ($k['so_tien'] ?? 0), 0, ',', '.')
                . ' đ' . (!empty($k['ngay']) ? ' ngày ' . Carbon::parse($k['ngay'])->format('d/m/Y') : ' ngay tuần này') . (!empty($k['ten']) ? ' (' . $k['ten'] . ')' : ''))->all(),
            'tien_dau_ky' => $baseF['tien_dau_ky'], 'nguon_tien_dau_ky' => $baseF['nguon_tien_dau_ky'],
            'cuoi_13_tuan_hien_tai' => $baseF['tien_cuoi_13_tuan'], 'cuoi_13_tuan_gia_dinh' => $newF['tien_cuoi_13_tuan'],
            'tuan_thap_nhat_hien_tai' => $low($baseF), 'tuan_thap_nhat_gia_dinh' => $low($newF),
            'so_tuan_duoi_nguong_hien_tai' => $baseF['so_tuan_duoi_nguong'], 'so_tuan_duoi_nguong_gia_dinh' => $newF['so_tuan_duoi_nguong'],
            'nguong' => $baseF['nguong_canh_bao'],
            'theo_tuan' => $weeks,
        ];
    }
}
