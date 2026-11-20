<?php

namespace App\Services\Accounting;

use App\Models\CustomerContract;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Giá theo hợp đồng khách hàng (bảng customer_contracts):
 *  - Thời vụ: giá trọn gói của đợt; gắn với đơn cùng đơn vị + cùng ngày chụp (hoặc đã chọn đơn khi lưu hợp đồng).
 *  - Nguyên tắc: bậc giá theo số ca / ngày (chụp nhiều ngày lấy số ca bình quân), "ngay" = trọn gói / ngày, "ca" = đồng / ca.
 *    Trọn gói theo đợt (hợp đồng không ghi số) → không có giá chuẩn, dùng gợi ý từ lịch sử / giá sàn.
 *  - So với Thành tiền trên đơn (đã gồm VAT như giá hợp đồng). Phần cộng thêm in phim và phụ thu khoảng cách không kiểm tra
 *    (chốt theo thực tế), nên thu NHIỀU hơn giá hợp đồng không coi là lỗi.
 * Chỉ để cảnh báo, không chặn gì.
 */
class ContractPricing
{
    /** Lệch quá tỷ lệ này mới báo thu thiếu (làm tròn, lẻ đồng…) */
    public const TOLERANCE = 0.02;

    private ?Collection $contracts = null;

    public function all(): Collection
    {
        return $this->contracts ??= CustomerContract::orderByDesc('signed_on')->orderByDesc('id')->get();
    }

    /**
     * Hợp đồng áp dụng cho một đơn:
     *  1. thời vụ đã gắn đúng đơn này;
     *  2. thời vụ cùng đơn vị + cùng ngày chụp (nhiều hợp đồng cùng ngày → chọn hợp đồng có "công ty được chụp" giống Tên Cty trên đơn nhất);
     *  3. nguyên tắc còn hiệu lực của đơn vị vào ngày chụp (hợp đồng ký sau được ưu tiên).
     */
    public function contractFor(int $orderId, ?int $unitId, ?string $date, ?string $company = null): ?CustomerContract
    {
        $all = $this->all();
        if ($orderId > 0 && ($c = $all->first(fn ($c) => (int) $c->order_id === $orderId))) {
            return $c;
        }
        if (!$unitId) {
            return null;
        }
        $d = $date ? Carbon::parse(substr($date, 0, 10)) : Carbon::today();
        // Hợp đồng thời vụ đã gắn với một đơn khác chỉ nhận thêm đơn cùng ngày của cùng công ty được chụp
        $seasonal = $all->filter(fn ($c) => $c->type === 'thoi_vu' && (int) $c->unit_id === $unitId && $c->service_date && $c->service_date->isSameDay($d)
            && (!$c->order_id || !$company || !$c->end_company || self::similar($c->end_company, $company) >= 60));
        if ($seasonal->count() === 1) {
            return $seasonal->first();
        }
        if ($seasonal->count() > 1) {
            return $seasonal->sortByDesc(fn ($c) => self::similar($c->end_company, $company) + ($c->order_id ? 0 : 1))->first();
        }

        return $all->first(fn ($c) => $c->type === 'nguyen_tac' && (int) $c->unit_id === $unitId && $c->activeOn($d));
    }

    /** Độ giống tên công ty (0–100), bỏ dấu và chữ "công ty / TNHH…" */
    private static function similar(?string $a, ?string $b): float
    {
        $n = fn ($s) => trim(preg_replace('/\s+/', ' ', preg_replace('/\b(cong ty|cty|tnhh|co phan|cp|chi nhanh|mtv)\b/', ' ', mb_strtolower(\Illuminate\Support\Str::ascii((string) $s)))));
        [$a, $b] = [$n($a), $n($b)];
        if ($a === '' || $b === '') {
            return 0;
        }
        similar_text($a, $b, $pct);

        return $pct;
    }

    /** Bậc giá cho số ca / ngày: bậc có mức tối thiểu lớn nhất ≤ số ca (bảng có khe như "dưới 100" / "từ 101" vẫn ra bậc gần nhất) */
    public function tier(CustomerContract $c, float $casesPerDay): ?array
    {
        $tiers = collect($c->price_tiers ?? [])->filter(fn ($t) => !empty($t['price']))->sortBy(fn ($t) => (int) ($t['min_cases'] ?? 0))->values();
        if ($tiers->isEmpty()) {
            return null;
        }

        return $tiers->filter(fn ($t) => (int) ($t['min_cases'] ?? 0) <= $casesPerDay)->last() ?? $tiers->first();
    }

    /** Thành tiền theo hợp đồng cho $qty ca trong $days ngày; null nếu hợp đồng không có giá chuẩn */
    public function expected(CustomerContract $c, int $qty, int $days = 1): ?array
    {
        $days = max(1, $days);
        if ($c->type === 'thoi_vu' || $c->price_mode === 'tron_goi_ngay') {
            if (!$c->package_price) {
                return null;
            }
            $amount = $c->type === 'thoi_vu' ? (int) $c->package_price : (int) $c->package_price * $days;

            return ['amount' => $amount, 'basis' => 'Trọn gói ' . number_format($c->package_price, 0, ',', '.') . ' đ' . ($c->type === 'thoi_vu' ? '/đợt' : '/ngày')];
        }
        if ($c->price_mode === 'tron_goi_dot' || $qty <= 0) {
            return null;
        }
        $perDay = $qty / $days;
        $t = $this->tier($c, $perDay);
        if (!$t) {
            return null;
        }
        $range = (($t['min_cases'] ?? null) ? 'từ ' . $t['min_cases'] : 'dưới ' . (($t['max_cases'] ?? 0) + 1)) . (($t['min_cases'] ?? null) && ($t['max_cases'] ?? null) ? '–' . $t['max_cases'] : '') . ' ca/ngày';

        return ($t['unit'] ?? 'ca') === 'ngay'
            ? ['amount' => (int) $t['price'] * $days, 'basis' => 'Bậc ' . $range . ': trọn gói ' . number_format($t['price'], 0, ',', '.') . ' đ/ngày × ' . $days . ' ngày']
            : ['amount' => (int) $t['price'] * $qty, 'basis' => 'Bậc ' . $range . ': ' . number_format($t['price'], 0, ',', '.') . ' đ/ca × ' . $qty . ' ca'];
    }

    /** Đơn cùng đơn vị, cùng ngày chụp (nhớ trong lần chạy) */
    private array $dayCache = [];

    private function sameDay(object $o): Collection
    {
        $key = (int) $o->unit_id . '|' . $o->ord_start_day;

        return $this->dayCache[$key] ??= $this->orders(fn ($q) => $q->where('orders.unit_id', (int) $o->unit_id)->where('order_details.ord_start_day', $o->ord_start_day));
    }

    /**
     * Đối chiếu một đơn với hợp đồng; null nếu đơn vị chưa có hợp đồng.
     * Một ngày chụp tách nhiều đơn (cùng đơn vị, cùng ngày, cùng hợp đồng): bậc giá theo TỔNG số ca trong ngày, giá trọn gói của đợt / ngày
     * chia cho các đơn theo tỷ lệ số ca; mỗi đơn so với phần của mình.
     */
    public function checkOrder(object $o): ?array
    {
        $c = $this->contractFor((int) $o->id, $o->unit_id ? (int) $o->unit_id : null, $o->ord_start_day ?? null, $o->ord_cty_name ?? null);
        if (!$c) {
            return null;
        }
        $start = $o->ord_start_day ? Carbon::parse(substr($o->ord_start_day, 0, 10)) : null;
        $end = !empty($o->ord_end_day) ? Carbon::parse(substr($o->ord_end_day, 0, 10)) : $start;
        $days = $start && $end ? max(1, $start->diffInDays($end) + 1) : 1;
        $qty = (int) $o->order_quantity;

        $group = collect([$o]);
        if ($o->unit_id && $o->ord_start_day) {
            // Các đơn khác cùng ngày + chính đơn này (dùng số đang nhập trên form nếu đang sửa)
            $group = $this->sameDay($o)->filter(fn ($x) => (int) $x->id !== (int) $o->id && (string) $x->ord_end_day === (string) ($o->ord_end_day ?? '')
                && optional($this->contractFor((int) $x->id, (int) $x->unit_id, $x->ord_start_day, $x->ord_cty_name))->id === $c->id)->values()->push($o);
        }
        $totalQty = max($qty, (int) $group->sum('order_quantity'));
        $n = $group->count();

        $exp = $this->expected($c, $totalQty, $days);
        $share = null;
        $basis = $exp['basis'] ?? null;
        if ($exp) {
            $share = $n > 1 && $totalQty > 0 ? (int) round($exp['amount'] * $qty / $totalQty) : $exp['amount'];
            if ($n > 1) {
                $basis = 'Cả ngày ' . $n . ' đơn, ' . $totalQty . ' ca → ' . $exp['basis'] . ' = ' . number_format($exp['amount'], 0, ',', '.') . ' đ; phần đơn này ' . $qty . '/' . $totalQty . ' ca';
            }
        }
        $actual = (int) $o->order_price;
        $status = 'khong_tinh';
        if ($share !== null && $actual > 0) {
            $status = $actual < $share * (1 - self::TOLERANCE) ? 'thieu' : ($actual > $share * (1 + self::TOLERANCE) ? 'du' : 'khop');
        }

        return [
            'contract_id' => $c->id,
            'contract_no' => $c->contract_no,
            'contract_type' => $c->type,
            'expected' => $share,
            'basis' => $basis ?? ($c->price_mode === 'tron_goi_dot' ? 'Hợp đồng trọn gói theo đợt, không ghi giá — dựa vào báo giá từng đợt' : null),
            'actual' => $actual,
            'diff' => $share !== null ? $actual - $share : null,
            'status' => $status,
            'group' => $n > 1 ? ['so_don' => $n, 'tong_ca' => $totalQty, 'ma_don' => $group->pluck('id')->map(fn ($i) => (int) $i)->sort()->values()->all()] : null,
        ];
    }

    /** Các dòng đơn cần cho đối chiếu (đơn không hủy, không phải dòng phụ thu / 0 ca) */
    public function orders(callable $scope): Collection
    {
        $q = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)->where('orders.order_surcharge', 0)->where('orders.order_quantity', '>', 0);
        $scope($q);

        return $q->get(['orders.id', 'orders.unit_id', 'orders.order_quantity', 'orders.order_price', 'order_details.ord_start_day', 'order_details.ord_end_day',
            'order_details.ord_cty_name', 'units.unit_abbreviation']);
    }

    /** Đơn thu thấp hơn giá hợp đồng (mặc định 90 ngày gần đây → 60 ngày tới) */
    public function underBilled(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= Carbon::today()->subDays(90);
        $to ??= Carbon::today()->addDays(60);

        return $this->orders(fn ($q) => $q->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()]))
            ->map(fn ($o) => ($r = $this->checkOrder($o)) ? $r + ['order_id' => $o->id, 'unit' => $o->unit_abbreviation, 'date' => $o->ord_start_day, 'company' => $o->ord_cty_name, 'qty' => (int) $o->order_quantity] : null)
            ->filter(fn ($r) => $r && $r['status'] === 'thieu')->sortBy('date')->values();
    }

    /** Thông tin hợp đồng gọn cho AI (không có số tài khoản / điện thoại — hợp đồng không lưu các trường đó) */
    public function describe(CustomerContract $c): array
    {
        return array_filter([
            'id' => $c->id,
            'loai' => CustomerContract::TYPES[$c->type] ?? $c->type,
            'so_hd' => $c->contract_no,
            'don_vi' => $c->unit->unit_abbreviation ?? null,
            'ben_a' => $c->party_a,
            'cong_ty_duoc_chup' => $c->end_company,
            'ngay_ky' => optional($c->signed_on)->toDateString(),
            'hieu_luc' => $c->type === 'thoi_vu' ? null : (optional($c->valid_from ?? $c->signed_on)->toDateString() . ' → ' . ($c->valid_to ? $c->valid_to->toDateString() : 'đến khi chấm dứt') . ($c->auto_renew ? ' (tự gia hạn)' : '')),
            'con_hieu_luc_hom_nay' => $c->type === 'nguyen_tac' ? $c->activeOn(Carbon::today()) : null,
            'ngay_chup' => optional($c->service_date)->toDateString(),
            'gan_don' => $c->order_id,
            'gia' => $c->priceSummary(),
            'gia_gom_vat' => $c->vat_included,
            'cach_tinh_nhieu_ngay' => $c->averaging_rule,
            'cong_them_theo_ca' => $c->addons,
            'phu_thu_khoang_cach' => $c->surcharges ? ['bac' => $c->surcharges, 'ghi_chu' => 'chốt theo thực tế khi khách cho thông tin'] : null,
            'ban_kinh_gia_goc_km' => $c->service_radius_km,
            'thanh_toan_trong_ngay' => $c->payment_days,
            'tra_ket_qua' => $c->result_delivery,
            'dieu_khoan' => $c->notable_terms,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** Đơn vị khớp từ khóa (mã, tên viết tắt, tên đầy đủ) */
    public function findUnits(string $kw, int $limit = 5): Collection
    {
        $kw = trim($kw);

        return DB::table('units')->where(fn ($q) => $q->where('unit_code', $kw)->orWhere('unit_abbreviation', 'like', "%{$kw}%")->orWhere('unit_name', 'like', "%{$kw}%"))
            ->orderByRaw('unit_code = ? DESC, unit_abbreviation = ? DESC', [$kw, $kw])->limit($limit)->get(['id', 'unit_code', 'unit_abbreviation', 'unit_name']);
    }

    /** Cho AI: hợp đồng của đơn vị + giá theo hợp đồng / lịch sử / giá sàn cho số ca dự kiến */
    public function forAssistant(?string $kw, int $qty = 0, int $days = 1, int $km = 0, ?string $company = null): array
    {
        if (!$kw) {
            $all = $this->all()->load('unit:id,unit_abbreviation');
            $today = Carbon::today();

            return [
                'so_hop_dong' => $all->count(),
                'hop_dong' => $all->map(fn ($c) => array_intersect_key($this->describe($c), array_flip(['id', 'loai', 'so_hd', 'don_vi', 'cong_ty_duoc_chup', 'hieu_luc', 'con_hieu_luc_hom_nay', 'ngay_chup', 'gia', 'thanh_toan_trong_ngay'])))->values()->all(),
                'sap_het_han_30_ngay' => $all->filter(fn ($c) => $c->type === 'nguyen_tac' && !$c->auto_renew && $c->valid_to && $c->valid_to->between($today, $today->copy()->addDays(30)))->pluck('contract_no')->values()->all(),
                'don_thu_thap_hon_hop_dong' => $this->underBilled()->map(fn ($u) => ['ma_don' => $u['order_id'], 'don_vi' => $u['unit'], 'ngay' => $u['date'], 'so_ca' => $u['qty'],
                    'thanh_tien' => $u['actual'], 'theo_hop_dong' => $u['expected'], 'cach_tinh' => $u['basis'], 'thieu' => -$u['diff']])->all(),
                'ghi_chu' => 'Giá hợp đồng so với Thành tiền trên đơn (đã gồm VAT). In phim / phụ thu khoảng cách không kiểm tra. Đơn vị chưa có trong danh sách = chưa tải hợp đồng lên (Hành chính → Hợp đồng).',
            ];
        }
        $units = $this->findUnits($kw, 3);
        if ($units->isEmpty()) {
            return ['tu_khoa' => $kw, 'ket_qua' => 'Không tìm thấy đơn vị — khách mới: báo giá theo giá sàn có lời.',
                'gia_san' => $qty ? $this->suggest(0, $qty, $days, $km)['floor'] : null];
        }

        return ['tu_khoa' => $kw, 'don_vi' => $units->map(function ($u) use ($qty, $days, $km, $company) {
            $contracts = $this->all()->where('unit_id', $u->id)->load('unit:id,unit_abbreviation');
            $out = ['don_vi' => $u->unit_abbreviation ?: $u->unit_name, 'ten_day_du' => $u->unit_name,
                'hop_dong' => $contracts->isEmpty() ? 'Chưa có hợp đồng trên web (chưa tải lên hoặc khách chưa ký).' : $contracts->map(fn ($c) => $this->describe($c))->values()->all()];
            if ($qty > 0) {
                $s = $this->suggest($u->id, $qty, $days, $km, $company);
                $out['bao_gia'] = ['so_ca' => $qty, 'so_ngay' => $days, 'theo_hop_dong' => $s['contract_price'], 'cac_dot_truoc' => $s['history'], 'gia_san' => $s['floor']];
            }

            return $out;
        })->values()->all()];
    }

    /**
     * Gợi ý giá cho một đơn vị (dùng khi báo giá, nhất là hợp đồng trọn gói theo đợt):
     * giá hợp đồng nếu có bậc / trọn gói → giá các đợt trước của đơn vị (cùng công ty nếu có) → khách mới: giá sàn theo chi phí ước tính.
     */
    public function suggest(int $unitId, int $qty, int $days = 1, int $km = 0, ?string $company = null): array
    {
        $out = ['contract' => null, 'contract_price' => null, 'history' => [], 'floor' => null];
        $c = $this->contractFor(0, $unitId, Carbon::today()->toDateString());
        if ($c) {
            $out['contract'] = ['so_hd' => $c->contract_no, 'loai' => CustomerContract::TYPES[$c->type] ?? $c->type, 'cach_tinh' => CustomerContract::PRICE_MODES[$c->price_mode] ?? $c->price_mode,
                'thanh_toan_ngay' => $c->payment_days, 'cong_them' => $c->addons, 'phu_thu_khoang_cach' => $c->surcharges, 'ban_kinh_km' => $c->service_radius_km];
            $exp = $this->expected($c, $qty, $days);
            $out['contract_price'] = $exp ? ['thanh_tien' => $exp['amount'], 'cach_tinh' => $exp['basis'], 'gia_mot_ca' => $qty ? (int) round($exp['amount'] / $qty) : null] : null;
        }
        $hist = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.unit_id', $unitId)->where('orders.status_id', '!=', 0)->where('orders.order_surcharge', 0)->where('orders.order_quantity', '>', 0)
            ->when($company, fn ($q) => $q->orderByRaw('order_details.ord_cty_name LIKE ? DESC', ['%' . addcslashes($company, '%_\\') . '%']))
            ->orderByDesc('order_details.ord_start_day')->limit(5)
            ->get(['orders.id', 'order_details.ord_start_day', 'order_details.ord_cty_name', 'orders.order_quantity', 'orders.order_price']);
        $out['history'] = $hist->map(fn ($h) => ['ma_don' => $h->id, 'ngay' => $h->ord_start_day, 'cong_ty' => $h->ord_cty_name, 'so_ca' => (int) $h->order_quantity,
            'thanh_tien' => (int) $h->order_price, 'gia_mot_ca' => (int) round($h->order_price / max(1, $h->order_quantity))])->all();

        // Giá sàn: chi phí chuyến ước tính / số ca + phí quản lý, VAT, biên tối thiểu (giống PriceGuard)
        $est = app(OrderCostEstimator::class)->estimate((object) ['order_price' => 0, 'order_quantity' => $qty, 'ord_start_day' => Carbon::today()->toDateString(),
            'ord_end_day' => Carbon::today()->addDays($days - 1)->toDateString(), 'accountant_distance' => $km, 'order_vat' => '8', 'ord_doctor_read' => 'Có']);
        $mgmt = (float) (app(\App\Services\ProfitSettings::class)->all()['chi_quanly_phantram'] ?? 0) / 100;
        $keep = 1 - $mgmt - 0.08 / 1.08 - config('finance.price.min_margin_pct', 10) / 100;
        if ($qty > 0 && $keep > 0) {
            $floorCase = (int) (ceil($est['chi_phi_truc_tiep'] / $keep / $qty / 100) * 100);
            $out['floor'] = ['gia_san_mot_ca' => $floorCase, 'thanh_tien_san' => $floorCase * $qty, 'chi_phi_chuyen_uoc_tinh' => $est['chi_phi_truc_tiep'],
                'gia_dinh' => $km ? "{$km} km khứ hồi, VAT 8%" : 'chưa có khoảng cách (chưa tính dầu), VAT 8%'];
        }

        return $out;
    }
}
