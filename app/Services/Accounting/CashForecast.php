<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dự báo dòng tiền 13 tuần (cuốn chiếu, tuần 1 bắt đầu hôm nay).
 *
 * Tiền đầu kỳ: Báo cáo thu chi tháng này = "Tiền hiện có (SDĐK)" + đã thu thực tế − đã chi thực tế trong tháng
 * (hoặc số người dùng nhập tay).
 *
 * THU
 *  - XQ hóa đơn đến hạn: từng hóa đơn còn nợ, vào tuần của hạn thanh toán.
 *  - XQ nợ quá hạn: thu được {qua_han_pct}% (mặc định 50%), rải đều tuần 1–4.
 *  - XQ chưa xuất hóa đơn: coi như xuất HĐ trong 7 ngày rồi thu sau "số ngày thu tiền bình quân" (DSO), thu {chua_hd_pct}%.
 *  - XQ đơn mới: doanh thu XQ bình quân 3 tháng gần nhất / tuần, bắt đầu vào tiền sau 7 ngày + DSO.
 *  - CT và các khoản thu khác có ngày: lấy từ Báo cáo thu chi (phần chưa thu); tháng chưa có báo cáo: CT bình quân 3 tháng gần nhất, ngày 12.
 *    Nhóm "XQ (số dự thu…)" của Báo cáo thu chi KHÔNG lấy (đã tính chi tiết theo hóa đơn ở trên, tránh tính hai lần).
 * CHI
 *  - Báo cáo thu chi: phần chưa chi của từng khoản, theo ngày dự kiến (quá hạn / không ngày → tuần gần nhất hoặc ngày 15).
 *  - Tháng chưa có báo cáo: lặp lại các khoản chi định kỳ (nhóm Chi cố định, Chi tiền vay Bank, Chi DV) của báo cáo gần nhất,
 *    trừ khoản "đáo hạn" (vay quay vòng) và khoản có chữ "bù" (trả bù, một lần); khoản ghi cho nhiều tháng (vd lương trả bù tháng 5 + tháng 6) chỉ lấy 1 lần (khoản lớn nhất).
 */
class CashForecast
{
    public const WEEKS = 13;

    private const RECURRING_GROUPS = ['Chi cố định', 'Chi tiền vay Bank', 'Chi DV'];

    public function __construct(private Receivables $receivables)
    {
    }

    public function build(array $opt = []): array
    {
        $today = Carbon::today();
        $end = $today->copy()->addDays(self::WEEKS * 7 - 1);
        $overduePct = max(0, min(100, (int) ($opt['qua_han_pct'] ?? 50)));
        $uninvoicedPct = max(0, min(100, (int) ($opt['chua_hd_pct'] ?? 80)));
        $withNew = (bool) ($opt['don_moi'] ?? true);
        $threshold = (int) ($opt['nguong'] ?? 100000000);

        $weeks = collect(range(0, self::WEEKS - 1))->map(fn ($i) => [
            'no' => $i + 1, 'from' => $today->copy()->addDays($i * 7), 'to' => $today->copy()->addDays($i * 7 + 6),
            'in' => [], 'out' => [], 'items' => [],
        ])->all();
        $weekOf = function (Carbon $d) use ($today, $end) {
            if ($d->gt($end)) {
                return null;
            }

            return $d->lt($today) ? 0 : intdiv($today->diffInDays($d), 7);
        };
        $add = function (string $dir, string $line, ?Carbon $date, float $amount, ?string $label = null) use (&$weeks, $weekOf) {
            if ($amount <= 0 || !$date) {
                return;
            }
            $w = $weekOf($date);
            if ($w === null) {
                return;
            }
            $weeks[$w][$dir][$line] = ($weeks[$w][$dir][$line] ?? 0) + $amount;
            if ($label && $amount >= 5000000) {
                $weeks[$w]['items'][] = ['dir' => $dir, 'label' => $label, 'amount' => (int) round($amount), 'date' => $date->format('d/m')];
            }
        };

        // ---------- Tiền đầu kỳ ----------
        [$opening, $openingNote] = $this->opening($today);
        if (isset($opt['so_du']) && $opt['so_du'] !== '' && $opt['so_du'] !== null) {
            $opening = (int) preg_replace('/[^\d-]/', '', (string) $opt['so_du']);
            $openingNote = 'Nhập tay';
        }

        // ---------- THU XQ theo hóa đơn ----------
        $dso = $this->receivables->dso() ?? 45;
        $rows = $this->receivables->rows();
        $overdueTotal = 0;
        foreach ($rows as $r) {
            if ($r->bucket === 'chua_xuat_hd') {
                $base = $r->shot && $r->shot->gt($today) ? $r->shot : $today;
                $add('in', 'XQ chưa xuất HĐ', $base->copy()->addDays(7 + $dso), $r->owe * $uninvoicedPct / 100);
            } elseif ($r->overdue_days > 0) {
                $overdueTotal += $r->owe;
            } else {
                $add('in', 'XQ hóa đơn đến hạn', $r->due ?? $today->copy()->addDays(30), $r->owe, $r->unit . ' HĐ ' . $r->invoice_no);
            }
        }
        for ($i = 0; $i < 4; $i++) {
            $add('in', 'XQ thu nợ quá hạn', $today->copy()->addDays($i * 7), $overdueTotal * $overduePct / 100 / 4);
        }

        // ---------- THU XQ đơn mới (bình quân 3 tháng) ----------
        $monthlyXq = $this->avgMonthlyXq($today);
        if ($withNew && $monthlyXq > 0) {
            $weekly = $monthlyXq * 12 / 52;
            $startCollect = $today->copy()->addDays(7 + $dso);
            foreach ($weeks as $i => $w) {
                if ($w['from']->gte($startCollect)) {
                    $add('in', 'XQ đơn mới (ước tính)', $w['from'], $weekly);
                }
            }
        }

        // ---------- Báo cáo thu chi (tháng này + các tháng tới) ----------
        $notes = [];
        $template = $this->recurringTemplate();
        $ctMonthly = $this->avgMonthlyCt($today);
        for ($m = $today->copy()->startOfMonth(); $m->lte($end); $m->addMonth()) {
            $items = $this->reportItems((int) $m->year, (int) $m->month);
            $isCurrent = $m->isSameMonth($today);
            if ($items->isNotEmpty()) {
                foreach ($items as $it) {
                    $group = (string) $it->summary_group;
                    if (str_starts_with($group, 'Tiền hiện có') || str_starts_with($group, 'XQ')) {
                        continue;
                    }
                    $remaining = (float) $it->estimated_amount - (float) $it->actual_amount;
                    if ($remaining <= 0) {
                        continue;
                    }
                    $date = $it->expected_date ? Carbon::parse($it->expected_date) : null;
                    if ($it->type === 'thu') {
                        if (!$date) {
                            $notes[] = 'Khoản thu "' . trim($it->name, '- ') . '" (' . number_format($remaining, 0, ',', '.') . ' đ, tháng ' . $m->month . ') chưa có ngày dự kiến nên chưa tính.';
                            continue;
                        }
                        $line = $it->auto_key && str_starts_with($it->auto_key, 'ct') ? 'CT' : 'Thu khác';
                        $add('in', $line, $date->lt($today) ? $today : $date, $remaining, trim($it->name, '- '));
                    } else {
                        $date ??= $m->copy()->day(15);
                        $add('out', $group ?: 'Chi khác', $date->lt($today) ? $today : $date, $remaining, trim($it->name, '- '));
                    }
                }
            } elseif (!$isCurrent) {
                foreach ($template['items'] as $it) {
                    $day = $it->expected_date ? min(Carbon::parse($it->expected_date)->day, $m->daysInMonth) : 15;
                    $add('out', $it->summary_group, $m->copy()->day($day), (float) $it->estimated_amount, trim($it->name, '- ') . ' (lặp lại)');
                }
                if ($ctMonthly > 0) {
                    $add('in', 'CT', $m->copy()->day(min(12, $m->daysInMonth)), $ctMonthly, 'CT bình quân 3 tháng');
                }
                if ($template['from']) {
                    $notes[] = 'Tháng ' . $m->month . '/' . $m->year . ' chưa có Báo cáo thu chi: chi lặp lại các khoản định kỳ của tháng ' . $template['from'] . '.';
                }
            }
        }

        // ---------- Cộng dồn ----------
        $balance = $opening;
        $lowest = null;
        $out = [];
        foreach ($weeks as $w) {
            $in = array_sum($w['in']);
            $outSum = array_sum($w['out']);
            $start = $balance;
            $balance = $balance + $in - $outSum;
            $row = [
                'tuan' => $w['no'],
                'tu' => $w['from']->toDateString(),
                'den' => $w['to']->toDateString(),
                'dau_ky' => (int) round($start),
                'thu' => array_map(fn ($v) => (int) round($v), $w['in']),
                'chi' => array_map(fn ($v) => (int) round($v), $w['out']),
                'tong_thu' => (int) round($in),
                'tong_chi' => (int) round($outSum),
                'cuoi_ky' => (int) round($balance),
                'canh_bao' => $balance < $threshold,
                'khoan_lon' => collect($w['items'])->sortByDesc('amount')->take(6)->values()->all(),
            ];
            $out[] = $row;
            if ($lowest === null || $row['cuoi_ky'] < $lowest['cuoi_ky']) {
                $lowest = $row;
            }
        }

        return [
            'ngay_lap' => $today->toDateString(),
            'tien_dau_ky' => (int) $opening,
            'nguon_tien_dau_ky' => $openingNote,
            'nguong_canh_bao' => $threshold,
            'tuan' => $out,
            'tong_thu' => array_sum(array_column($out, 'tong_thu')),
            'tong_chi' => array_sum(array_column($out, 'tong_chi')),
            'tien_cuoi_13_tuan' => end($out)['cuoi_ky'] ?? $opening,
            'tuan_thap_nhat' => $lowest ? ['tuan' => $lowest['tuan'], 'tu' => $lowest['tu'], 'so_du' => $lowest['cuoi_ky']] : null,
            'so_tuan_duoi_nguong' => count(array_filter($out, fn ($r) => $r['canh_bao'])),
            'gia_dinh' => [
                'so_ngay_thu_tien_binh_quan' => $dso,
                'thu_no_qua_han_pct' => $overduePct,
                'no_qua_han_hien_tai' => (int) $overdueTotal,
                'thu_chua_xuat_hd_pct' => $uninvoicedPct,
                'tinh_don_moi' => $withNew,
                'doanh_thu_xq_binh_quan_thang' => (int) round($monthlyXq),
                'ct_binh_quan_thang' => (int) round($ctMonthly),
            ],
            'ghi_chu' => array_values(array_unique($notes)),
        ];
    }

    /** [số tiền, nguồn] */
    private function opening(Carbon $today): array
    {
        $items = $this->reportItems((int) $today->year, (int) $today->month);
        if ($items->isEmpty()) {
            return [0, 'Chưa có Báo cáo thu chi tháng ' . $today->month . '/' . $today->year . ' — nhập số dư tay để dự báo đúng'];
        }
        $isOpening = fn ($it) => str_starts_with((string) $it->summary_group, 'Tiền hiện có');
        $sdk = $items->filter($isOpening)->sum(fn ($it) => (float) $it->estimated_amount);
        $thu = $items->where('type', 'thu')->reject($isOpening)->sum(fn ($it) => (float) $it->actual_amount);
        $chi = $items->where('type', 'chi')->sum(fn ($it) => (float) $it->actual_amount);

        return [(int) round($sdk + $thu - $chi), 'Báo cáo thu chi tháng ' . $today->month . ': số dư đầu tháng ' . number_format($sdk, 0, ',', '.')
            . ' + đã thu ' . number_format($thu, 0, ',', '.') . ' − đã chi ' . number_format($chi, 0, ',', '.')];
    }

    private function reportItems(int $year, int $month): Collection
    {
        return DB::table('report_items')->join('reports', 'reports.id', '=', 'report_items.report_id')
            ->where('reports.year', $year)->where('reports.month', (string) $month)
            ->get(['report_items.type', 'report_items.summary_group', 'report_items.name', 'report_items.auto_key', 'report_items.expected_date',
                'report_items.estimated_amount', 'report_items.actual_amount']);
    }

    /** Các khoản chi định kỳ của báo cáo tháng gần nhất có dữ liệu */
    private function recurringTemplate(): array
    {
        $r = DB::table('reports')->whereRaw('month REGEXP "^[0-9]+$"')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('report_items')->whereColumn('report_items.report_id', 'reports.id'))
            ->orderByDesc('year')->orderByRaw('CAST(month AS UNSIGNED) DESC')->first();
        if (!$r) {
            return ['from' => null, 'items' => collect()];
        }
        $items = DB::table('report_items')->where('report_id', $r->id)->where('type', 'chi')
            ->whereIn('summary_group', self::RECURRING_GROUPS)->where('estimated_amount', '>', 0)
            ->get(['summary_group', 'name', 'expected_date', 'estimated_amount'])
            // "Đáo hạn" là khoản vay quay vòng (trả rồi vay lại), không phải chi định kỳ hằng tháng
            ->reject(fn ($it) => str_contains(mb_strtolower((string) $it->name), 'đáo hạn'))
            // Khoản có chữ "bù" (vd "Trả bù lương tháng 10") là khoản một lần, không lặp lại sang tháng sau
            ->reject(fn ($it) => preg_match('/\bbù\b/u', mb_strtolower((string) $it->name)))
            // Cùng một khoản ghi cho nhiều tháng (vd "Chi lương … tháng 5" trả bù và "… tháng 6") chỉ lặp lại 1 lần mỗi tháng:
            // bỏ phần "tháng N" / ngày trong tên rồi giữ khoản lớn nhất
            ->groupBy(fn ($it) => trim(preg_replace('/\s+/u', ' ', preg_replace('/(tháng|t)\s*\d{1,2}(\s*\/\s*\d{2,4})?|\d{1,2}\s*\/\s*\d{1,2}(\s*\/\s*\d{2,4})?/iu', '', mb_strtolower((string) $it->name)))))
            ->map(fn ($g) => $g->sortByDesc(fn ($it) => (float) $it->estimated_amount)->first())
            ->values();

        return ['from' => $r->month . '/' . $r->year, 'items' => $items];
    }

    private function avgMonthlyXq(Carbon $today): float
    {
        $from = $today->copy()->subMonthsNoOverflow(3)->startOfMonth();
        $to = $today->copy()->startOfMonth()->subDay();
        $sum = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.status_id', '!=', 0)
            ->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()])
            ->sum(DB::raw('COALESCE(orders.order_price,0) - COALESCE(orders.order_discount,0)'));

        return (float) $sum / 3;
    }

    private function avgMonthlyCt(Carbon $today): float
    {
        $rows = DB::table('ct_receivables')->selectRaw('year, month, SUM(medicen_collect) s')->groupBy('year', 'month')
            ->orderByDesc('year')->orderByDesc('month')->limit(3)->get();

        return $rows->count() ? (float) $rows->avg('s') : 0;
    }
}
