<?php

namespace App\Services\Finance;

use App\Services\Accounting\CashForecast;
use App\Services\Accounting\Receivables;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trang "Tổng quan điều hành" cho giám đốc: một màn hình trả lời
 *  - tiền đang có bao nhiêu (Báo cáo thu chi tháng này, như Dòng tiền 13 tuần),
 *  - sắp phải chi gì (các khoản chi trên Báo cáo thu chi tháng này + tháng sau chưa chi hết),
 *  - tháng này X-quang chụp được bao nhiêu, có thể thu về bao nhiêu (công nợ theo hạn hóa đơn),
 *  - số dư dự kiến 4 tuần / 13 tuần tới, khách nợ nhiều nhất, lịch 7 ngày tới, các cảnh báo cần xử lý.
 * Chỉ đọc; số liệu dùng chung với Phân tích tài chính và Trợ lý Kế toán để mọi nơi ra cùng một số.
 */
class DirectorOverview
{
    /** Giả định thu nợ quá hạn trong tháng (giống mặc định Dòng tiền 13 tuần) */
    public const OVERDUE_COLLECT_PCT = 50;

    public function __construct(private CashForecast $forecast, private Receivables $receivables, private EarlyWarnings $warnings)
    {
    }

    public function build(): array
    {
        $today = Carbon::today();
        $f = $this->forecast->build(['nguong' => (int) config('finance.alerts.cash_threshold', 100000000)]);
        $weeks = collect($f['tuan']);
        $four = $weeks->take(4);
        $r = $this->receivables->summary();

        return [
            'today' => $today,
            'cash' => [
                'amount' => $f['tien_dau_ky'],
                'source' => $f['nguon_tien_dau_ky'],
                'estimated' => !str_starts_with($f['nguon_tien_dau_ky'], 'Báo cáo thu chi') && $f['nguon_tien_dau_ky'] !== 'Nhập tay',
                'in_4w' => (int) $four->sum('tong_thu'),
                'out_4w' => (int) $four->sum('tong_chi'),
                'balance_4w' => (int) ($four->last()['cuoi_ky'] ?? $f['tien_dau_ky']),
                'balance_13w' => (int) $f['tien_cuoi_13_tuan'],
                'lowest' => $f['tuan_thap_nhat'],
                'threshold' => $f['nguong_canh_bao'],
                'weeks' => $weeks->map(fn ($w) => ['no' => $w['tuan'], 'from' => Carbon::parse($w['tu']), 'to' => Carbon::parse($w['den']),
                    'in' => $w['tong_thu'], 'out' => $w['tong_chi'], 'end' => $w['cuoi_ky'], 'low' => $w['canh_bao']])->all(),
            ],
            'payables' => $this->payables($today),
            'xq' => $this->xq($today, $r),
            'debtors' => collect($r['by_unit'])->filter(fn ($u) => $u['total'] > 0)->sortByDesc(fn ($u) => $u['total'] - $u['chua_den_han'])->take(6)->values()->all(),
            'receivables' => ['total' => $r['total'], 'overdue' => $r['overdue'], 'overdue_pct' => $r['overdue_pct'], 'uninvoiced' => $r['uninvoiced'],
                'stuck' => $r['stuck_total'], 'dso' => $r['dso']],
            'week' => $this->nextDays($today, 7),
            // Cảnh báo đỏ lên đầu, rồi vàng, rồi thông tin
            'alerts' => collect($this->warnings->check(false)['alerts'])->map(fn ($a) => $a + ['link' => $this->linkFor($a['title'])])
                ->sortBy(fn ($a) => ['bad' => 0, 'warn' => 1][$a['level']] ?? 2)->values()->all(),
        ];
    }

    /**
     * Khoản phải chi trên Báo cáo thu chi tháng này + tháng sau, còn chưa chi hết (dự kiến − đã chi):
     * quá ngày dự kiến mà chưa ghi đã chi / trong 30 ngày tới / chưa có ngày.
     */
    private function payables(Carbon $today): array
    {
        $until = $today->copy()->addDays(30);
        $rows = collect();
        foreach ([$today->copy()->startOfMonth(), $today->copy()->startOfMonth()->addMonthNoOverflow()] as $m) {
            $rows = $rows->merge(DB::table('report_items')->join('reports', 'reports.id', '=', 'report_items.report_id')
                ->where('reports.year', $m->year)->where('reports.month', (string) $m->month)->where('report_items.type', 'chi')
                ->get(['report_items.name', 'report_items.summary_group', 'report_items.expected_date', 'report_items.estimated_amount', 'report_items.actual_amount'])
                ->map(fn ($it) => (object) ['name' => trim((string) $it->name, '- '), 'group' => (string) $it->summary_group,
                    'date' => $it->expected_date ? Carbon::parse($it->expected_date) : null, 'month' => $m->month . '/' . $m->year,
                    'left' => (int) round((float) $it->estimated_amount - (float) $it->actual_amount), 'paid' => (int) $it->actual_amount]));
        }
        $rows = $rows->filter(fn ($x) => $x->left > 0);
        $late = $rows->filter(fn ($x) => $x->date && $x->date->lt($today))->sortBy('date')->values();
        $soon = $rows->filter(fn ($x) => $x->date && $x->date->gte($today) && $x->date->lte($until))->sortBy('date')->values();
        $noDate = $rows->filter(fn ($x) => !$x->date)->sortByDesc('left')->values();

        return [
            'late' => $late, 'soon' => $soon, 'no_date' => $noDate,
            'late_total' => $late->sum('left'), 'soon_total' => $soon->sum('left'), 'no_date_total' => $noDate->sum('left'),
            'week_total' => $soon->filter(fn ($x) => $x->date->lte($today->copy()->addDays(7)))->sum('left'),
            'has_next_report' => DB::table('reports')->where('year', $today->copy()->addMonthNoOverflow()->year)
                ->where('month', (string) $today->copy()->addMonthNoOverflow()->month)
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('report_items')->whereColumn('report_items.report_id', 'reports.id'))->exists(),
        ];
    }

    /** X-quang tháng này: đã chụp, sắp chụp, cùng kỳ tháng trước; thu tiền theo hạn hóa đơn; số kế toán dự thu trên Báo cáo thu chi */
    private function xq(Carbon $today, array $r): array
    {
        $start = $today->copy()->startOfMonth();
        $end = $today->copy()->endOfMonth();
        $sum = function (Carbon $from, Carbon $to) {
            $x = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
                ->where('orders.status_id', '!=', 0)
                ->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('COUNT(*) n, SUM(CASE WHEN orders.order_surcharge = 0 THEN orders.order_quantity ELSE 0 END) ca, SUM(COALESCE(orders.order_price, 0)) tien')->first();

            return ['orders' => (int) $x->n, 'cases' => (int) $x->ca, 'amount' => (int) $x->tien];
        };
        $zero = ['orders' => 0, 'cases' => 0, 'amount' => 0];
        // Đã chụp xong = ngày chụp trước hôm nay; đơn hôm nay để riêng (có thể đang chụp); lịch còn lại = từ mai đến cuối tháng
        $done = $today->day > 1 ? $sum($start, $today->copy()->subDay()) : $zero;
        $todayRow = $sum($today, $today);
        $upcoming = $today->lt($end) ? $sum($today->copy()->addDay(), $end) : $zero;
        $prevStart = $start->copy()->subMonthNoOverflow();
        // Cùng kỳ tháng trước: cùng số ngày đã qua (1 → hôm qua)
        $prevSame = $today->day > 1 ? $sum($prevStart, $prevStart->copy()->day(min($today->day - 1, $prevStart->daysInMonth))) : $zero;
        $prevFull = $sum($prevStart, $prevStart->copy()->endOfMonth());

        $rows = $this->receivables->rows();
        $dueThisMonth = $rows->filter(fn ($x) => $x->is_debt && $x->overdue_days <= 0 && $x->due && $x->due->lte($end));
        $due30 = $rows->filter(fn ($x) => $x->is_debt && $x->overdue_days <= 0 && $x->due && $x->due->lte($today->copy()->addDays(30)));
        $dueLater = $rows->filter(fn ($x) => $x->is_debt && $x->overdue_days <= 0 && (!$x->due || $x->due->gt($end)));
        $overdueCollect = (int) round($r['overdue'] * self::OVERDUE_COLLECT_PCT / 100);

        // Kế toán tự ghi dự thu XQ trên Báo cáo thu chi tháng này (dòng nhóm "XQ…")
        $plan = DB::table('report_items')->join('reports', 'reports.id', '=', 'report_items.report_id')
            ->where('reports.year', $today->year)->where('reports.month', (string) $today->month)
            ->where('report_items.type', 'thu')->where('report_items.summary_group', 'like', 'XQ%')
            ->selectRaw('SUM(report_items.estimated_amount) e, SUM(report_items.actual_amount) a')->first();

        return [
            'done' => $done,
            'today' => $todayRow,
            'upcoming' => $upcoming,
            'month_total' => $done['amount'] + $todayRow['amount'] + $upcoming['amount'],
            'prev_same' => $prevSame,
            'prev_full' => $prevFull,
            'change_pct' => $prevSame['amount'] > 0 ? round(($done['amount'] - $prevSame['amount']) / $prevSame['amount'] * 100, 1) : null,
            'due_this_month' => (int) $dueThisMonth->sum('owe'),
            'due_this_month_count' => $dueThisMonth->count(),
            'due_30' => (int) $due30->sum('owe'),
            'due_30_count' => $due30->count(),
            'no_due_date' => (int) $rows->filter(fn ($x) => $x->is_debt && !$x->due)->sum('owe'),
            'due_later' => (int) $dueLater->sum('owe'),
            'overdue' => (int) $r['overdue'],
            'overdue_collect' => $overdueCollect,
            'collectible' => (int) $dueThisMonth->sum('owe') + $overdueCollect,
            'uninvoiced' => (int) $r['uninvoiced'],
            'stuck' => (int) $r['stuck_total'],
            'stuck_count' => $r['stuck']->count(),
            'plan' => $plan && $plan->e !== null ? ['estimated' => (int) $plan->e, 'actual' => (int) $plan->a] : null,
        ];
    }

    /** Lịch chụp N ngày tới: số đơn, số ca, tiền mỗi ngày */
    private function nextDays(Carbon $today, int $n): Collection
    {
        $rows = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)->where('orders.order_surcharge', 0)
            ->whereBetween('order_details.ord_start_day', [$today->toDateString(), $today->copy()->addDays($n - 1)->toDateString()])
            ->get(['order_details.ord_start_day', 'orders.order_quantity', 'orders.order_price', 'units.unit_abbreviation'])
            ->groupBy(fn ($o) => substr((string) $o->ord_start_day, 0, 10));

        return collect(range(0, $n - 1))->map(function ($i) use ($today, $rows) {
            $d = $today->copy()->addDays($i);
            $g = $rows->get($d->toDateString(), collect());

            return ['date' => $d, 'orders' => $g->count(), 'cases' => (int) $g->sum('order_quantity'), 'amount' => (int) $g->sum('order_price'),
                'units' => $g->pluck('unit_abbreviation')->filter()->unique()->take(4)->values()->all(), 'more' => max(0, $g->pluck('unit_abbreviation')->filter()->unique()->count() - 4)];
        });
    }

    /** Link tới trang xử lý cảnh báo (các trang giám đốc / kế toán đều mở được) */
    private function linkFor(string $title): ?array
    {
        $t = mb_strtolower($title);
        $tab = fn ($k, $label) => ['url' => route('finance.index', ['tab' => $k]), 'label' => $label];

        return match (true) {
            str_contains($t, 'báo cáo thu chi') => ['url' => route('reports.index'), 'label' => 'Báo cáo thu chi'],
            str_contains($t, 'ngưỡng') => $tab('dong-tien', 'Dòng tiền 13 tuần'),
            str_contains($t, 'hẹn trả') || str_contains($t, 'phụ trách') || str_contains($t, 'quá hạn tăng') => $tab('thu-no', 'Thu nợ'),
            str_contains($t, 'tiền kẹt') => $tab('tien-ket', 'Tiền kẹt & tuổi nợ'),
            str_contains($t, 'hạn mức') => $tab('han-muc', 'Hạn mức tín dụng'),
            str_contains($t, 'duyệt') => $tab('duyet-gia', 'Duyệt giá'),
            str_contains($t, 'hợp đồng') => ['url' => route('contracts.index'), 'label' => 'Hợp đồng'],
            str_contains($t, 'misa') => ['url' => route('accounting.index'), 'label' => 'Dữ liệu kế toán'],
            default => null,
        };
    }
}
