<?php

namespace App\Services\Finance;

use App\Services\Accounting\CashForecast;
use App\Services\Accounting\Receivables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cảnh báo sớm tài chính (lệnh finance:alerts, email thứ Hai hằng tuần):
 * thiếu tiền trong 13 tuần tới, nợ quá hạn tăng, tiền kẹt chưa xuất hóa đơn, khách quen ngưng đặt lịch,
 * xe nằm không, hợp đồng sắp hết hạn. Ngưỡng ở config/finance.php.
 */
class EarlyWarnings
{
    private const SNAPSHOT = 'finance.alerts.snapshot';

    public function __construct(private CashForecast $forecast, private Receivables $receivables)
    {
    }

    /**
     * @return array{today: Carbon, alerts: array<int, array{level: string, title: string, detail: string, lines: array}>, overview: array}
     */
    public function check(bool $saveSnapshot = false): array
    {
        $cfg = config('finance.alerts');
        $today = Carbon::today();
        $alerts = [];
        $m = fn ($v) => number_format((int) round($v), 0, ',', '.') . ' đ';

        // 1. Dòng tiền 13 tuần
        $f = $this->forecast->build(['nguong' => $cfg['cash_threshold']]);
        if (str_starts_with($f['nguon_tien_dau_ky'], 'Chưa có')) {
            $alerts[] = ['level' => 'info', 'title' => 'Chưa có Báo cáo thu chi tháng ' . $today->month,
                'detail' => 'Dự báo dòng tiền đang tính từ số dư 0 nên chưa đáng tin. Kế toán cần lập Báo cáo thu chi tháng này (có dòng "Tiền hiện có").', 'lines' => []];
        } elseif ($f['so_tuan_duoi_nguong'] > 0) {
            $low = collect($f['tuan'])->filter(fn ($w) => $w['canh_bao']);
            $first = $low->first();
            $alerts[] = ['level' => 'bad', 'title' => 'Có ' . $low->count() . ' tuần tiền dưới ngưỡng ' . $m($cfg['cash_threshold']),
                'detail' => 'Sớm nhất tuần ' . $first['tuan'] . ' (từ ' . Carbon::parse($first['tu'])->format('d/m') . '), số dư dự kiến ' . $m($first['cuoi_ky'])
                    . '. Thấp nhất ' . $m($f['tuan_thap_nhat']['so_du']) . ' (tuần ' . $f['tuan_thap_nhat']['tuan'] . ').',
                'lines' => $low->take(5)->map(fn ($w) => 'Tuần ' . $w['tuan'] . ' (' . Carbon::parse($w['tu'])->format('d/m') . '–' . Carbon::parse($w['den'])->format('d/m') . '): '
                    . $m($w['cuoi_ky']) . ($w['khoan_lon'] ? ' · ' . collect($w['khoan_lon'])->where('dir', 'out')->take(2)->map(fn ($i) => $i['label'])->implode(', ') : ''))->values()->all()];
        }

        // 2. Nợ quá hạn tăng / 3. Tiền kẹt
        $r = $this->receivables->summary();
        $prev = Cache::get(self::SNAPSHOT);
        if ($prev && isset($prev['overdue']) && $r['overdue'] - $prev['overdue'] >= $cfg['overdue_increase']) {
            $alerts[] = ['level' => 'bad', 'title' => 'Nợ quá hạn tăng ' . $m($r['overdue'] - $prev['overdue']),
                'detail' => 'Từ ' . $m($prev['overdue']) . ' (' . Carbon::parse($prev['date'])->format('d/m') . ') lên ' . $m($r['overdue']) . ' (' . $r['overdue_pct'] . '% tổng phải thu).',
                'lines' => $r['overdue_rows']->take(5)->map(fn ($x) => $x->unit . ' · HĐ ' . $x->invoice_no . ' · quá hạn ' . $x->overdue_days . ' ngày · ' . $m($x->owe))->all()];
        }
        if ($r['stuck_total'] >= $cfg['stuck_threshold']) {
            $alerts[] = ['level' => 'warn', 'title' => 'Tiền kẹt chưa xuất hóa đơn: ' . $m($r['stuck_total']),
                'detail' => $r['stuck']->count() . ' đơn đã chụp xong từ ' . Receivables::STUCK_DAYS . ' ngày trở lên chưa xuất hóa đơn.',
                'lines' => $r['stuck']->take(5)->map(fn ($x) => $x->unit . ' · đơn #' . $x->id . ' · chụp ' . $x->shot?->format('d/m/Y') . ' (' . $x->days_since_shot . ' ngày) · ' . $m($x->owe))->all()];
        }

        // 3b. Khách vượt hạn mức tín dụng / quá hạn quá lâu
        $credit = app(\App\Services\Accounting\CreditPolicy::class)->all()->whereIn('status', ['vuot_han_muc', 'qua_han_lau'])->values();
        if ($credit->isNotEmpty()) {
            $alerts[] = ['level' => 'bad', 'title' => $credit->count() . ' khách vượt hạn mức tín dụng / quá hạn quá lâu',
                'detail' => 'Tổng nợ của nhóm này ' . $m($credit->sum('owe')) . '. Nên thu nợ, hoặc Kế toán duyệt trước khi nhận lịch mới.',
                'lines' => $credit->take(6)->map(fn ($c) => $c['unit'] . ' · nợ ' . $m($c['owe']) . ' / hạn mức ' . $m($c['limit'])
                    . ($c['max_overdue_days'] > $c['max_days'] ? ' · quá hạn ' . $c['max_overdue_days'] . ' ngày' : ''))->all()];
        }

        // 3c. Thu nợ: khách quá ngày hẹn trả, nợ chưa ai phụ trách
        $collect = app(\App\Services\Accounting\DebtCollection::class)->list();
        $late = $collect->where('status', 'qua_hen');
        if ($late->isNotEmpty()) {
            $alerts[] = ['level' => 'bad', 'title' => $late->count() . ' khách quá ngày hẹn trả nợ',
                'detail' => 'Đã hẹn nhưng chưa trả, người phụ trách cần nhắc lại.',
                'lines' => $late->take(6)->map(fn ($c) => $c['unit'] . ' · hẹn ' . $c['promise']['date']->format('d/m') . ($c['promise']['amount'] ? ' (' . $m($c['promise']['amount']) . ')' : '')
                    . ' · phụ trách: ' . ($c['assignee'] ?? 'chưa giao'))->values()->all()];
        }
        $noOwner = $collect->where('status', 'chua_phu_trach');
        if ($noOwner->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $noOwner->count() . ' khách nợ quá hạn / tiền kẹt chưa ai phụ trách thu',
                'detail' => 'Tổng ' . $m($noOwner->sum(fn ($c) => $c['overdue'] + $c['stuck'])) . '. Giao người phụ trách ở Phân tích tài chính → Thu nợ.',
                'lines' => $noOwner->sortByDesc(fn ($c) => $c['overdue'] + $c['stuck'])->take(5)->map(fn ($c) => $c['unit'] . ' · ' . $m($c['overdue'] + $c['stuck']))->values()->all()];
        }

        // 3d. Đơn giá thấp / chiết khấu cao chưa duyệt
        $prices = app(\App\Services\Accounting\PriceGuard::class)->list()->where('approved', false);
        if ($prices->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $prices->count() . ' đơn giá thấp / chiết khấu cao chờ kế toán duyệt',
                'detail' => 'Lỗ hoặc biên lãi ước tính dưới mức, hoặc chiết khấu cao. Duyệt ở Phân tích tài chính → Duyệt giá.',
                'lines' => $prices->take(6)->map(fn ($p) => '#' . $p['order_id'] . ' ' . $p['unit'] . ' · ' . Carbon::parse($p['date'])->format('d/m') . ' · ' . implode(', ', $p['flags']))->values()->all()];
        }

        // 4. Khách quen ngưng đặt lịch: ≥ 3 đơn trong 180 ngày, đơn gần nhất cách quá N ngày, chưa có lịch sắp tới
        $idle = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)->where('orders.order_surcharge', 0)
            ->where('order_details.ord_start_day', '>=', $today->copy()->subDays(180)->toDateString())
            ->groupBy('units.id', 'units.unit_abbreviation')
            ->selectRaw('units.unit_abbreviation u, COUNT(*) n, MAX(order_details.ord_start_day) last_day, SUM(COALESCE(orders.order_price,0)) rev')
            ->havingRaw('COUNT(*) >= 3')
            ->havingRaw('MAX(order_details.ord_start_day) <= ?', [$today->copy()->subDays($cfg['customer_idle_days'])->toDateString()])
            ->orderByDesc('rev')->limit(8)->get();
        if ($idle->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $idle->count() . ' khách quen ngưng đặt lịch trên ' . $cfg['customer_idle_days'] . ' ngày',
                'detail' => 'Đặt từ 3 lần trong 6 tháng qua nhưng chưa có lịch mới. Sales nên liên hệ lại.',
                'lines' => $idle->map(fn ($c) => $c->u . ' · ' . $c->n . ' đơn / 6 tháng · ' . $m($c->rev) . ' · đơn cuối ' . Carbon::parse($c->last_day)->format('d/m/Y'))->all()];
        }

        // 5. Xe nhà nằm không
        $cars = DB::table('car_ktvs')->join('orders', 'orders.id', '=', 'car_ktvs.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('car_ktvs.car_active', 1)->where('orders.status_id', '!=', 0)
            ->where('order_details.ord_start_day', '>=', $today->copy()->subDays(60)->toDateString())
            ->groupBy('car_ktvs.car_name')
            ->selectRaw('car_ktvs.car_name car, MAX(CASE WHEN order_details.ord_start_day < ? THEN order_details.ord_start_day END) last_day, '
                . 'SUM(CASE WHEN order_details.ord_start_day BETWEEN ? AND ? THEN 1 ELSE 0 END) upcoming',
                [$today->toDateString(), $today->toDateString(), $today->copy()->addDays(3)->toDateString()])
            ->get()
            // Chỉ xe nhà (1–5) và xe siêu âm; xe thuê / tăng cường chỉ dùng khi cần nên không cảnh báo nằm không
            ->filter(fn ($c) => in_array((string) $c->car, array_merge(config('schedule.own_cars', []), [(string) config('schedule.ultrasound_car')]), true) && !$c->upcoming && $c->last_day && Carbon::parse($c->last_day)->diffInDays($today) > $cfg['car_idle_days']);
        if ($cars->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $cars->count() . ' xe nằm không quá ' . $cfg['car_idle_days'] . ' ngày',
                'detail' => 'Không chạy từ lâu và chưa có lịch trong 3 ngày tới.',
                'lines' => $cars->sortBy('last_day')->map(fn ($c) => \App\Services\Accounting\CfoAnalytics::carLabel($c->car) . ' · chạy lần cuối ' . Carbon::parse($c->last_day)->format('d/m') . ' (' . Carbon::parse($c->last_day)->diffInDays($today) . ' ngày)')->values()->all()];
        }

        // 6. Hợp đồng sắp hết hạn: hợp đồng khách hàng (nguyên tắc, không tự gia hạn) + tài liệu loại hợp đồng ở Dữ liệu kế toán
        $until = $today->copy()->addDays($cfg['contract_days'])->toDateString();
        $contracts = \App\Models\CustomerContract::with('unit:id,unit_abbreviation')->where('type', 'nguyen_tac')->where('auto_renew', false)
            ->whereBetween('valid_to', [$today->toDateString(), $until])->orderBy('valid_to')->get()
            ->map(fn ($c) => 'HĐ ' . ($c->contract_no ?: '#' . $c->id) . ' · ' . ($c->unit->unit_abbreviation ?? $c->party_a) . ' · hết hạn ' . $c->valid_to->format('d/m/Y'))
            ->merge(DB::table('acc_documents')->where('type', 'hop_dong')->whereNotNull('valid_to')->whereBetween('valid_to', [$today->toDateString(), $until])
                ->orderBy('valid_to')->get(['title', 'parties', 'valid_to'])
                ->map(fn ($c) => $c->title . ($c->parties ? ' · ' . $c->parties : '') . ' · hết hạn ' . Carbon::parse($c->valid_to)->format('d/m/Y')));
        if ($contracts->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $contracts->count() . ' hợp đồng hết hạn trong ' . $cfg['contract_days'] . ' ngày',
                'detail' => 'Cần gia hạn hoặc đàm phán lại.', 'lines' => $contracts->values()->all()];
        }

        // 6b. Đơn thu thấp hơn giá hợp đồng khách hàng (90 ngày qua → 60 ngày tới)
        $under = app(\App\Services\Accounting\ContractPricing::class)->underBilled();
        if ($under->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $under->count() . ' đơn thu thấp hơn giá hợp đồng, thiếu ' . $m(-$under->sum('diff')),
                'detail' => 'Thành tiền trên đơn thấp hơn bảng giá đã ký. Kiểm tra số ca / đơn giá hoặc ghi lý do giảm giá. Xem Hành chính → Hợp đồng.',
                'lines' => $under->take(6)->map(fn ($u) => '#' . $u['order_id'] . ' ' . $u['unit'] . ' · ' . Carbon::parse($u['date'])->format('d/m') . ' · ' . $u['qty'] . ' ca · thiếu ' . $m(-$u['diff']))->all()];
        }

        if ($saveSnapshot) {
            Cache::forever(self::SNAPSHOT, ['date' => $today->toDateString(), 'overdue' => $r['overdue'], 'stuck' => $r['stuck_total']]);
        }

        return [
            'today' => $today,
            'alerts' => $alerts,
            'overview' => [
                'Tiền hiện có' => $m($f['tien_dau_ky']),
                'Số dư dự kiến sau 13 tuần' => $m($f['tien_cuoi_13_tuan']),
                'Tổng phải thu XQ' => $m($r['total']),
                'Nợ quá hạn' => $m($r['overdue']) . ' (' . $r['overdue_pct'] . '%)',
                'Tiền kẹt chưa xuất HĐ' => $m($r['stuck_total']),
                'Số ngày thu tiền bình quân' => $r['dso'] !== null ? $r['dso'] . ' ngày' : 'chưa đủ dữ liệu',
            ],
        ];
    }
}
