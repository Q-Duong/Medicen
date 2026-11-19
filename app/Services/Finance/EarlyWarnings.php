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

        // 5. Xe công ty nằm không (tên xe là số)
        $cars = DB::table('car_ktvs')->join('orders', 'orders.id', '=', 'car_ktvs.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('car_ktvs.car_active', 1)->where('orders.status_id', '!=', 0)
            ->where('order_details.ord_start_day', '>=', $today->copy()->subDays(60)->toDateString())
            ->groupBy('car_ktvs.car_name')
            ->selectRaw('car_ktvs.car_name car, MAX(CASE WHEN order_details.ord_start_day < ? THEN order_details.ord_start_day END) last_day, '
                . 'SUM(CASE WHEN order_details.ord_start_day BETWEEN ? AND ? THEN 1 ELSE 0 END) upcoming',
                [$today->toDateString(), $today->toDateString(), $today->copy()->addDays(3)->toDateString()])
            ->get()
            ->filter(fn ($c) => ctype_digit((string) $c->car) && !$c->upcoming && $c->last_day && Carbon::parse($c->last_day)->diffInDays($today) > $cfg['car_idle_days']);
        if ($cars->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $cars->count() . ' xe nằm không quá ' . $cfg['car_idle_days'] . ' ngày',
                'detail' => 'Không chạy từ lâu và chưa có lịch trong 3 ngày tới.',
                'lines' => $cars->sortBy('last_day')->map(fn ($c) => 'Xe ' . $c->car . ' · chạy lần cuối ' . Carbon::parse($c->last_day)->format('d/m') . ' (' . Carbon::parse($c->last_day)->diffInDays($today) . ' ngày)')->values()->all()];
        }

        // 6. Hợp đồng sắp hết hạn (tài liệu loại hợp đồng ở Dữ liệu kế toán)
        $contracts = DB::table('acc_documents')->where('type', 'hop_dong')->whereNotNull('valid_to')
            ->whereBetween('valid_to', [$today->toDateString(), $today->copy()->addDays($cfg['contract_days'])->toDateString()])
            ->orderBy('valid_to')->get(['title', 'parties', 'valid_to']);
        if ($contracts->isNotEmpty()) {
            $alerts[] = ['level' => 'warn', 'title' => $contracts->count() . ' hợp đồng hết hạn trong ' . $cfg['contract_days'] . ' ngày',
                'detail' => 'Cần gia hạn hoặc đàm phán lại.',
                'lines' => $contracts->map(fn ($c) => $c->title . ($c->parties ? ' · ' . $c->parties : '') . ' · hết hạn ' . Carbon::parse($c->valid_to)->format('d/m/Y'))->all()];
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
