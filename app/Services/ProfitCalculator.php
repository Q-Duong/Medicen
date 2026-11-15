<?php

namespace App\Services;

/**
 * Công thức của "Bảng tính hiệu quả kinh doanh" (admin/profit/calculate),
 * chuyển từ public/assets/js/tool/calculation.js sang server để:
 *  - server tự tính lại khi lưu (không tin số tổng trình duyệt gửi lên),
 *  - trợ lý AI dùng đúng một công thức với trang web.
 *
 * Đầu vào dùng đúng khóa id của các ô trên trang (giống input_data đã lưu).
 * Ô nào không có thì lấy giá mặc định ở config/profit.php, số lượng mặc định = 0.
 */
class ProfitCalculator
{
    public function __construct(private ProfitSettings $settings)
    {
    }

    /** Đơn giá mặc định hiện hành (config/profit.php + giá đã cập nhật trong DB) */
    public function defaults(): array
    {
        return $this->settings->all();
    }

    /**
     * @return array{
     *   inputs: array<string, float>,
     *   revenue: array<string, float>, costs: array<string, array<string, float>>,
     *   total_revenue: float, total_cost: float, net_profit: float, profit_margin: float,
     *   verdict: string
     * }
     */
    public function calculate(array $input): array
    {
        $v = fn (string $key) => $this->number($input, $key);

        $days = $v('inp_so_ngay');
        $nights = $v('inp_so_dem');
        $km = $v('inp_so_km');
        $people = $v('inp_so_nguoi');

        $xq = $v('thu_xq_sl');
        $cstl = $v('thu_cstl_sl');
        // "Unit": CSTL chụp 2 tư thế nên tính gấp đôi
        $units = $xq + 2 * $cstl;

        // I. Doanh thu
        $revenue = [
            'xq_phoi' => $v('thu_xq_gia') * $xq,
            'cstl' => $v('thu_cstl_gia') * $cstl,
            'phu_thu' => $v('thu_phuthu_gia') * $v('thu_phuthu_sl'),
            'bs_doc_truc_tiep' => $v('thu_bs_gia') * $v('thu_bs_sl'),
        ];
        $totalRevenue = array_sum($revenue);

        // II. Chi phí
        $materials = [
            'bao_phim' => $v('chi_baophim_gia') * $xq,
            'phim_16' => $v('chi_phim16_gia') * $xq,
            'phim_8x10' => $v('chi_phim810_gia') * $cstl,
            'phim_10x12' => $v('chi_phim1012_gia') * $v('chi_phim1012_sl'),
            'chiet_khau' => $v('chi_chietkhau_gia') * 1,
        ];

        $staff = [
            'bs_doc_tu_xa' => $v('chi_bsdoc_gia') * $units,
            'bs_doc_truc_tiep' => $v('chi_bstructiep_gia') * $v('chi_bstructiep_sl'),
            'luong_tai_xe' => $v('chi_taixe_gia') * $days,
            'thue_tai_xe' => $v('chi_thue_tx_gia') * $v('chi_thue_tx_sl'),
            'luong_ktv' => $v('chi_ktv_gia_ca') * $units + $v('chi_ktv_gia_ngay') * $days,
            'thue_ktv' => $v('chi_thue_ktv_gia') * $v('chi_thue_ktv_sl'),
            'luong_bpkq' => $v('chi_bpkq_gia') * $units,
            'phu_cap_xa' => $v('chi_phucap_xa_gia') * $v('chi_phucap_xa_sl') * $people,
        ];

        $fuelNorm = $v('chi_dau_dinhmuc');
        $vatRate = $v('chi_vat_phantram') / 100;

        $operations = [
            'phi_quan_ly' => $totalRevenue * ($v('chi_quanly_phantram') / 100),
            'dau' => $fuelNorm > 0 ? ($km / $fuelNorm) * $v('chi_dau_gia') : 0,
            'dau_phat_sinh' => $v('chi_dau_phatsinh_gia') * $v('chi_dau_phatsinh_sl'),
            'cau_duong' => $v('chi_cauduong_gia') * $v('chi_cauduong_sl'),
            'dang_kiem' => $v('chi_dangkiem_gia') * $v('chi_dangkiem_sl'),
            'khach_san' => $v('chi_ks_gia') * $nights,
            'phu_cap_dem' => $v('chi_dem_gia') * $nights * $people,
            'vat_phai_nop' => (1 + $vatRate) > 0 ? $totalRevenue / (1 + $vatRate) * $vatRate : 0,
        ];

        $totalCost = array_sum($materials) + array_sum($staff) + array_sum($operations);
        $netProfit = $totalRevenue - $totalCost;
        $margin = $totalRevenue > 0 ? $netProfit / $totalRevenue * 100 : 0;

        return [
            'inputs' => compact('days', 'nights', 'km', 'people', 'xq', 'cstl', 'units'),
            'revenue' => $revenue,
            'costs' => ['vat_tu' => $materials, 'nhan_su' => $staff, 'van_hanh' => $operations],
            'total_revenue' => round($totalRevenue, 2),
            'total_cost' => round($totalCost, 2),
            'net_profit' => round($netProfit, 2),
            'profit_margin' => round($margin, 2),
            'verdict' => $this->verdict($netProfit),
        ];
    }

    /** Theo quy trình sale: lãi → tự chốt; hòa hoặc lỗ → báo KTT */
    public function verdict(float $netProfit): string
    {
        $threshold = (float) config('profit.margin_break_even', 0);

        return match (true) {
            $netProfit > $threshold => 'lai',
            abs($netProfit - $threshold) < 1 => 'hoa',
            default => 'lo',
        };
    }

    private function number(array $input, string $key): float
    {
        $raw = $input[$key] ?? null;

        if ($raw === null || $raw === '') {
            // Đơn giá trống → giá mặc định; số lượng trống → 0 (giống trang web khi mới mở)
            return (float) ($this->defaults()[$key] ?? 0);
        }

        // Trang web cho phép dấu phẩy phân cách nghìn
        $clean = str_replace(',', '', (string) $raw);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
