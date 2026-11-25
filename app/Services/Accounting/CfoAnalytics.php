<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Số liệu vận hành mảng XQ (đơn hàng trên web) cho trợ lý Kế toán ở vai CFO: doanh thu / số ca / giá bình quân theo tháng,
 * khách hàng (tỷ trọng, tập trung, khách mới, khách bỏ đi), hiệu suất từng xe, tốc độ thu tiền; kèm kỳ trước để so sánh.
 * Chi phí, lãi lỗ thật lấy từ sổ MISA (tool ket_qua_kinh_doanh); ở đây không có chi phí từng đơn.
 *
 * Quy ước: đơn hủy (status 0) không tính; ngày của đơn = ngày chụp (ord_start_day);
 * doanh thu = Thành tiền (order_price), doanh thu thuần = Thành tiền − chiết khấu (cột Lợi nhuận trên trang Công nợ).
 */
class CfoAnalytics
{
    public function __construct(private OrderCostEstimator $cost)
    {
    }

    /** Tên xe hiển thị: 1–5 là "Xe 1"…; 6, 7, 8 là Xe Thuê / Xe Tăng Cường / Xe Siêu Âm (config/schedule.php car_labels) */
    public static function carLabel($car): ?string
    {
        if ($car === null || $car === '') {
            return null;
        }

        return config('schedule.car_labels')[(string) $car] ?? 'Xe ' . $car;
    }

    public function analyze(Carbon $from, Carbon $to, bool $compare = true): array
    {
        $cur = $this->orders($from, $to);
        $days = $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);
        $prev = $compare ? $this->orders($prevFrom, $prevTo) : collect();

        $result = [
            'ky' => $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y') . " ({$days} ngày)",
            'nguon' => 'Đơn hàng XQ trên web Medicen (trang Công nợ). Chi phí / lãi từng đơn là ƯỚC TÍNH theo đơn giá mặc định của Bảng tính hiệu quả kinh doanh '
                . '(vật tư, KTV, tài xế, dầu theo km khứ hồi, khách sạn, phụ cấp, phí quản lý, VAT); chưa gồm khấu hao xe, lãi vay, lương văn phòng — lãi lỗ thật toàn công ty xem ket_qua_kinh_doanh (sổ MISA)',
            'tong' => $this->totals($cur),
            'theo_thang' => $this->byMonth($cur),
            'khach_hang' => $this->customers($cur, $prev),
            'theo_xe' => $this->cars($cur),
            'thu_tien' => $this->collection($cur),
            // Đơn lãi ước tính thấp nhất (kể cả lỗ) — để xem lại giá / điều kiện nhận đơn
            'don_lai_thap' => $cur->where('order_surcharge', 0)->where('net', '>', 0)->sortBy('profit')->take(15)->map(fn ($r) => [
                'ma_don' => $r->id, 'don_vi' => $r->unit_abbreviation, 'ngay' => $r->ord_start_day, 'xe' => self::carLabel($r->car_name), 'so_ca' => $r->qty,
                'km_khu_hoi' => (int) preg_replace('/\D/', '', (string) $r->accountant_distance), 'doanh_thu' => $r->net, 'chi_phi_uoc_tinh' => $r->cost,
                'lai_uoc_tinh' => $r->profit, 'bien_lai_pct' => $r->net ? round($r->profit / $r->net * 100, 1) : null,
            ])->values()->all(),
        ];
        $result['thieu_du_lieu'] = $this->gaps($cur, $from, $to, $result['thu_tien']);
        if ($compare) {
            $result['ky_truoc'] = ['ky' => $prevFrom->format('d/m/Y') . ' – ' . $prevTo->format('d/m/Y'), 'tong' => $this->totals($prev)];
            $result['thay_doi_so_voi_ky_truoc'] = $this->delta($result['tong'], $result['ky_truoc']['tong']);
        }

        return $result;
    }

    /** Dữ liệu còn thiếu làm số phân tích chưa chính xác — trợ lý nêu ra để người dùng bổ sung */
    private function gaps(Collection $o, Carbon $from, Carbon $to, array $collection): array
    {
        $main = $o->where('order_surcharge', 0);
        $gaps = [];
        $noKm = $main->filter(fn ($r) => trim((string) $r->accountant_distance) === '' || (float) $r->accountant_distance <= 0);
        if ($noKm->count()) {
            $gaps[] = ['thieu' => $noKm->count() . ' đơn chưa có Khoảng cách (km khứ hồi)', 'anh_huong' => 'chưa tính tiền dầu, lãi ước tính các đơn này cao hơn thực tế',
                'bo_sung' => 'Kế toán nhập ô Khoảng cách ở trang Công nợ', 'vi_du_ma_don' => $noKm->pluck('id')->take(10)->values()->all()];
        }
        $noQty = $main->filter(fn ($r) => (int) $r->order_quantity <= 0);
        if ($noQty->count()) {
            $gaps[] = ['thieu' => $noQty->count() . ' đơn có số ca = 0', 'anh_huong' => 'giá bình quân / ca và chi phí theo ca bị sai',
                'bo_sung' => 'Cập nhật số ca thực tế (trang Công nợ / Lịch kết quả)', 'vi_du_ma_don' => $noQty->pluck('id')->take(10)->values()->all()];
        }
        $noCar = $main->filter(fn ($r) => !$r->car_name);
        if ($noCar->count()) {
            $gaps[] = ['thieu' => $noCar->count() . ' đơn chưa xếp xe', 'anh_huong' => 'không tính vào hiệu quả xe nào; chi phí KTV / tài xế ước theo mặc định',
                'bo_sung' => 'Điều phối xếp xe cho đơn (trang Xếp lịch xe)', 'vi_du_ma_don' => $noCar->pluck('id')->take(10)->values()->all()];
        }
        $hired = $o->filter(fn ($r) => in_array((string) $r->car_name, config('schedule.hired_cars', ['6', '7']), true));
        if ($hired->count()) {
            $gaps[] = ['thieu' => 'Tiền thuê Xe Thuê / Xe Tăng Cường (' . $hired->count() . ' đơn) chưa có trong lãi ước tính',
                'anh_huong' => 'lãi của 2 xe này cao hơn thực tế',
                'bo_sung' => 'Trợ lý tìm chi phí thuê xe trong sổ MISA (tim_but_toan, tài khoản 632 / 642x, từ khóa "thuê xe", tên nhà xe) rồi trừ; nếu sổ chưa có kỳ này thì kế toán tải sổ MISA mới'];
        }
        if ($collection['ngay_thu_tien_binh_quan'] === null) {
            $gaps[] = ['thieu' => 'Không có đơn nào đủ ngày xuất hóa đơn + ngày thanh toán trong kỳ', 'anh_huong' => 'không tính được số ngày thu tiền bình quân',
                'bo_sung' => 'Kế toán nhập Ngày HĐ và Ngày TT khi khách trả tiền (trang Công nợ)'];
        }
        $misaTo = DB::table('acc_journal_entries')->max('posted_on');
        if (!$misaTo || $misaTo < $to->toDateString()) {
            $gaps[] = ['thieu' => 'Sổ MISA ' . ($misaTo ? 'chỉ có đến ' . Carbon::parse($misaTo)->format('d/m/Y') : 'chưa được tải lên'),
                'anh_huong' => 'chi phí / lãi lỗ thật (khấu hao, lãi vay, lương văn phòng, thuê xe) chưa có cho phần sau ngày đó',
                'bo_sung' => 'Kế toán xuất sổ từ MISA và tải lên trang Dữ liệu kế toán'];
        }

        return $gaps;
    }

    /** Đơn kèm doanh thu, chi phí và lãi ước tính (dùng chung cho WhatIf) */
    public function orderRows(Carbon $from, Carbon $to): Collection
    {
        return $this->orders($from, $to);
    }

    private function orders(Carbon $from, Carbon $to): Collection
    {
        return DB::table('orders')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('accountants', 'accountants.order_id', '=', 'orders.id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->leftJoin('car_ktvs', function ($j) {
                $j->on('car_ktvs.order_id', '=', 'orders.id')->where('car_ktvs.car_active', 1);
            })
            ->where('orders.status_id', '!=', 0)
            ->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()])
            ->get([
                'orders.id', 'orders.order_quantity', 'orders.order_price', 'orders.order_discount', 'orders.order_profit', 'orders.order_surcharge',
                'order_details.ord_start_day', 'units.unit_abbreviation', 'units.id as unit_id', 'car_ktvs.car_name',
                'accountants.accountant_amount_paid', 'accountants.accountant_owe', 'accountants.accountant_date', 'accountants.accountant_day_payment', 'accountants.accountant_number',
                // cho giá vốn ước tính (OrderCostEstimator)
                'order_details.ord_select', 'order_details.ord_end_day', 'order_details.ord_doctor_read', 'orders.order_vat',
                'accountants.accountant_distance', 'accountants.accountant_10X12', 'car_ktvs.car_driver_name', 'car_ktvs.car_ktv_name_1', 'car_ktvs.car_ktv_name_2', 'car_ktvs.work_over_250',
            ])
            ->unique('id')
            ->map(function ($r) {
                $r->price = (int) $r->order_price;
                // Tính lại theo công thức trang Công nợ (Thành tiền − chiết khấu): cột order_profit ở đơn cũ có thể chưa được tính
                $r->net = $r->price - (int) $r->order_discount;
                $r->qty = $r->order_surcharge ? 0 : (int) $r->order_quantity; // đơn phụ thu không tính số ca
                $r->month = substr((string) $r->ord_start_day, 0, 7);
                $est = $this->cost->estimate($r);
                $r->cost = $est['tong_chi_phi'];
                $r->profit = $est['lai_uoc_tinh'];

                return $r;
            })
            ->values();
    }

    private function totals(Collection $o): array
    {
        $main = $o->where('order_surcharge', 0);
        $price = $o->sum('price');
        $qty = $o->sum('qty');

        return [
            'so_don' => $main->count(),
            'so_don_phu_thu' => $o->count() - $main->count(),
            'so_ca' => $qty,
            'doanh_thu' => $price,
            'trong_do_phu_thu' => $o->where('order_surcharge', '!=', 0)->sum('price'),
            'chiet_khau' => $o->sum(fn ($r) => (int) $r->order_discount),
            'doanh_thu_thuan' => $o->sum('net'),
            'chi_phi_uoc_tinh' => $o->sum('cost'),
            'lai_uoc_tinh' => $o->sum('profit'),
            'bien_lai_uoc_tinh_pct' => $o->sum('net') ? round($o->sum('profit') / $o->sum('net') * 100, 1) : null,
            'so_don_lo_uoc_tinh' => $main->where('profit', '<', 0)->count(),
            'gia_binh_quan_ca' => $qty ? (int) round($main->sum('price') / $qty) : null,
            'ca_binh_quan_don' => $main->count() ? round($qty / $main->count(), 1) : null,
            'so_khach' => $o->pluck('unit_id')->filter()->unique()->count(),
            'da_thu' => $o->sum(fn ($r) => (int) $r->accountant_amount_paid),
            // Nợ = đã có số hóa đơn; chưa có số = chưa xuất hóa đơn (không phải nợ)
            'con_no' => $o->filter(fn ($r) => trim((string) $r->accountant_number) !== '')->sum(fn ($r) => max(0, (int) $r->accountant_owe)),
            'chua_xuat_hd' => $o->filter(fn ($r) => trim((string) $r->accountant_number) === '')->sum(fn ($r) => max(0, (int) $r->accountant_owe)),
        ];
    }

    private function byMonth(Collection $o): array
    {
        return $o->groupBy('month')->sortKeys()->map(function ($g, $m) {
            $main = $g->where('order_surcharge', 0);
            $qty = $g->sum('qty');

            return [
                'thang' => $m,
                'so_don' => $main->count(),
                'so_ca' => $qty,
                'doanh_thu' => $g->sum('price'),
                'doanh_thu_thuan' => $g->sum('net'),
                'lai_uoc_tinh' => $g->sum('profit'),
                'bien_lai_pct' => $g->sum('net') ? round($g->sum('profit') / $g->sum('net') * 100, 1) : null,
                'gia_binh_quan_ca' => $qty ? (int) round($main->sum('price') / $qty) : null,
                'so_khach' => $g->pluck('unit_id')->filter()->unique()->count(),
            ];
        })->values()->all();
    }

    private function customers(Collection $cur, Collection $prev): array
    {
        $total = max(1, $cur->sum('price'));
        $rows = $cur->groupBy(fn ($r) => $r->unit_abbreviation ?: '(chưa có đơn vị)')->map(function ($g, $u) use ($total) {
            $qty = $g->sum('qty');
            $main = $g->where('order_surcharge', 0);

            return [
                'don_vi' => $u,
                'doanh_thu' => $g->sum('price'),
                'ty_trong' => round($g->sum('price') / $total * 100, 1),
                'so_don' => $main->count(),
                'so_ca' => $qty,
                'gia_binh_quan_ca' => $qty ? (int) round($main->sum('price') / $qty) : null,
                'lai_uoc_tinh' => $g->sum('profit'),
                'bien_lai_pct' => $g->sum('net') ? round($g->sum('profit') / $g->sum('net') * 100, 1) : null,
                'con_no' => $g->filter(fn ($r) => trim((string) $r->accountant_number) !== '')->sum(fn ($r) => max(0, (int) $r->accountant_owe)),
                'chua_xuat_hd' => $g->filter(fn ($r) => trim((string) $r->accountant_number) === '')->sum(fn ($r) => max(0, (int) $r->accountant_owe)),
                'don_gan_nhat' => $g->max('ord_start_day'),
            ];
        })->sortByDesc('doanh_thu')->values();

        $share = fn (int $n) => round($rows->take($n)->sum('doanh_thu') / $total * 100, 1);
        $curUnits = $cur->pluck('unit_abbreviation')->filter()->unique();
        $prevByUnit = $prev->groupBy('unit_abbreviation');
        $lost = $prevByUnit->keys()->filter()->diff($curUnits)
            ->map(fn ($u) => ['don_vi' => $u, 'doanh_thu_ky_truoc' => $prevByUnit[$u]->sum('price'), 'don_cuoi' => $prevByUnit[$u]->max('ord_start_day')])
            ->sortByDesc('doanh_thu_ky_truoc')->values();
        $new = $prev->isEmpty() ? collect() : $curUnits->diff($prevByUnit->keys())->values();

        $thin = $rows->filter(fn ($r) => $r['so_don'] > 0 && $r['bien_lai_pct'] !== null)->sortBy('bien_lai_pct')->take(8)->values();

        return [
            'bien_lai_thap_nhat' => $thin->all(),
            'tap_trung' => ['top1_pct' => $share(1), 'top5_pct' => $share(5), 'top10_pct' => $share(10), 'so_khach' => $rows->count()],
            'top' => $rows->take(15)->all(),
            'khach_moi_so_voi_ky_truoc' => $new->take(20)->all(),
            'khach_ky_truoc_khong_quay_lai' => $lost->take(15)->all(),
        ];
    }

    private function cars(Collection $o): array
    {
        return $o->groupBy(fn ($r) => self::carLabel($r->car_name) ?? '(chưa xếp xe)')->map(function ($g, $car) {
            $days = $g->pluck('ord_start_day')->unique()->count();

            return [
                'xe' => $car,
                'so_ngay_chay' => $days,
                'so_don' => $g->where('order_surcharge', 0)->count(),
                'so_ca' => $g->sum('qty'),
                'doanh_thu' => $g->sum('price'),
                'lai_uoc_tinh' => $g->sum('profit'),
                'lai_binh_quan_ngay' => $days ? (int) round($g->sum('profit') / $days) : null,
                'doanh_thu_binh_quan_ngay' => $days ? (int) round($g->sum('price') / $days) : null,
                'ca_binh_quan_ngay' => $days ? round($g->sum('qty') / $days, 1) : null,
            ];
        })->sortByDesc('doanh_thu')->values()->all();
    }

    /** Tốc độ thu tiền: số ngày từ xuất hóa đơn đến khi khách thanh toán (đơn đã thu đủ) */
    private function collection(Collection $o): array
    {
        $days = $o->filter(fn ($r) => $r->accountant_date && $r->accountant_day_payment && (int) $r->accountant_owe <= 0)
            ->map(function ($r) {
                try {
                    return Carbon::parse(substr($r->accountant_date, 0, 10))->diffInDays(Carbon::parse(substr($r->accountant_day_payment, 0, 10)), false);
                } catch (\Throwable) {
                    return null;
                }
            })->filter(fn ($d) => $d !== null && $d >= 0)->values();
        $price = max(1, $o->sum('price'));

        return [
            'ty_le_da_thu_pct' => round($o->sum(fn ($r) => (int) $r->accountant_amount_paid) / $price * 100, 1),
            'so_don_chua_xuat_hd' => $o->filter(fn ($r) => trim((string) $r->accountant_number) === '' && (int) $r->accountant_owe > 0)->count(),
            'ngay_thu_tien_binh_quan' => $days->count() ? (int) round($days->avg()) : null,
            'ngay_thu_tien_trung_vi' => $days->count() ? (int) $days->sort()->values()[intdiv($days->count(), 2)] : null,
            'so_don_tinh' => $days->count(),
        ];
    }

    private function delta(array $cur, array $prev): array
    {
        $out = [];
        foreach (['so_don', 'so_ca', 'doanh_thu', 'doanh_thu_thuan', 'gia_binh_quan_ca', 'so_khach'] as $k) {
            $a = (float) ($cur[$k] ?? 0);
            $b = (float) ($prev[$k] ?? 0);
            $out[$k] = $b ? round(($a - $b) / $b * 100, 1) . '%' : null;
        }

        return $out;
    }
}
