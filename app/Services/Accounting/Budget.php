<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Kế hoạch năm theo tháng (bảng budgets) và so với thực tế.
 *
 * Thực tế lấy từ:
 *  - Doanh thu XQ, số ca: đơn hàng (Thành tiền − chiết khấu; ngày chụp; bỏ đơn hủy; số ca không tính đơn phụ thu).
 *  - Medicen thu về từ CT: Công nợ CT (medicen_collect theo tháng của kỳ).
 *  - Các khoản chi: số ĐÃ CHI THỰC TẾ trong Báo cáo thu chi của tháng đó, chia theo nhóm / tên khoản.
 */
class Budget
{
    /** line => [nhãn, loại thu|chi|so] */
    public const LINES = [
        'dt_xq' => ['Doanh thu XQ', 'thu'],
        'dt_ct' => ['Medicen thu về từ CT', 'thu'],
        'so_ca' => ['Số ca XQ', 'so'],
        'chi_luong' => ['Chi lương & BHXH', 'chi'],
        'chi_vay' => ['Trả nợ vay ngân hàng', 'chi'],
        'chi_ncc' => ['Trả nhà cung cấp (film, PACS…)', 'chi'],
        'chi_thue' => ['Nộp thuế', 'chi'],
        'chi_co_dinh' => ['Chi cố định khác (thuê nhà, điện nước, bảo trì…)', 'chi'],
        'chi_khac' => ['Chi khác', 'chi'],
    ];

    public function plan(int $year): array
    {
        $out = $this->empty();
        foreach (DB::table('budgets')->where('year', $year)->get() as $r) {
            if (isset($out[$r->line])) {
                $out[$r->line][(int) $r->month] = (int) $r->amount;
            }
        }

        return $out;
    }

    public function hasPlan(int $year): bool
    {
        return DB::table('budgets')->where('year', $year)->where('amount', '!=', 0)->exists();
    }

    public function actual(int $year): array
    {
        $out = $this->empty();
        $orders = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.status_id', '!=', 0)->whereYear('order_details.ord_start_day', $year)
            ->groupByRaw('MONTH(order_details.ord_start_day)')
            ->selectRaw('MONTH(order_details.ord_start_day) m, SUM(COALESCE(orders.order_price,0) - COALESCE(orders.order_discount,0)) rev, '
                . 'SUM(CASE WHEN orders.order_surcharge = 0 THEN COALESCE(orders.order_quantity,0) ELSE 0 END) qty')
            ->get();
        foreach ($orders as $r) {
            $out['dt_xq'][(int) $r->m] = (int) $r->rev;
            $out['so_ca'][(int) $r->m] = (int) $r->qty;
        }
        foreach (DB::table('ct_receivables')->where('year', $year)->groupBy('month')->selectRaw('month m, SUM(medicen_collect) s')->get() as $r) {
            $out['dt_ct'][(int) $r->m] = (int) $r->s;
        }
        $items = DB::table('report_items')->join('reports', 'reports.id', '=', 'report_items.report_id')
            ->where('reports.year', $year)->whereRaw('reports.month REGEXP "^[0-9]+$"')->where('report_items.type', 'chi')
            ->where('report_items.actual_amount', '>', 0)
            ->get(['reports.month', 'report_items.summary_group', 'report_items.name', 'report_items.actual_amount']);
        foreach ($items as $it) {
            $out[self::chiLine($it->summary_group, $it->name)][(int) $it->month] += (int) $it->actual_amount;
        }

        return $out;
    }

    /** Khoản chi của Báo cáo thu chi thuộc dòng kế hoạch nào */
    public static function chiLine(?string $group, ?string $name): string
    {
        $g = mb_strtolower((string) $group);
        $n = mb_strtolower((string) $name);

        return match (true) {
            str_contains($n, 'lương') || str_contains($n, 'bhxh') || str_contains($n, 'bảo hiểm xã hội') => 'chi_luong',
            str_contains($g, 'vay') => 'chi_vay',
            str_contains($g, 'ncc') || str_contains($g, 'nhà cung cấp') => 'chi_ncc',
            str_contains($g, 'thuế') => 'chi_thue',
            str_contains($g, 'cố định') || $g === 'chi dv' || str_contains($g, 'dịch vụ') => 'chi_co_dinh',
            default => 'chi_khac',
        };
    }

    /**
     * So kế hoạch với thực tế. Lũy kế tính đến hết tháng trước (năm hiện tại) hoặc cả năm (năm đã qua).
     */
    public function compare(int $year): array
    {
        $plan = $this->plan($year);
        $actual = $this->actual($year);
        $today = Carbon::today();
        $upTo = $year < $today->year ? 12 : ($year > $today->year ? 0 : $today->month - 1);
        $lines = [];
        foreach (self::LINES as $key => [$label, $type]) {
            $planYear = array_sum($plan[$key]);
            $planYtd = array_sum(array_slice($plan[$key], 0, $upTo, true));
            $actYtd = array_sum(array_slice($actual[$key], 0, $upTo, true));
            // Ước cả năm = thực tế lũy kế + kế hoạch các tháng còn lại
            $forecast = $actYtd + array_sum(array_slice($plan[$key], $upTo, null, true));
            $lines[$key] = [
                'label' => $label,
                'type' => $type,
                'plan_year' => $planYear,
                'plan_ytd' => $planYtd,
                'actual_ytd' => $actYtd,
                'diff_ytd' => $actYtd - $planYtd,
                'pct_ytd' => $planYtd ? round($actYtd / $planYtd * 100, 1) : null,
                'forecast_year' => $forecast,
                'plan' => $plan[$key],
                'actual' => $actual[$key],
            ];
        }
        $sum = fn (string $type, string $field) => array_sum(array_column(array_filter($lines, fn ($l) => $l['type'] === $type), $field));

        return [
            'year' => $year,
            'up_to_month' => $upTo,
            'lines' => $lines,
            'totals' => [
                'thu_plan_ytd' => $sum('thu', 'plan_ytd'), 'thu_actual_ytd' => $sum('thu', 'actual_ytd'),
                'chi_plan_ytd' => $sum('chi', 'plan_ytd'), 'chi_actual_ytd' => $sum('chi', 'actual_ytd'),
                'thu_plan_year' => $sum('thu', 'plan_year'), 'chi_plan_year' => $sum('chi', 'plan_year'),
            ],
            'has_plan' => $this->hasPlan($year),
        ];
    }

    /** Lưu kế hoạch (ô trống = 0) */
    public function save(int $year, array $grid, ?int $userId): void
    {
        DB::transaction(function () use ($year, $grid, $userId) {
            foreach (self::LINES as $key => $_) {
                for ($m = 1; $m <= 12; $m++) {
                    $v = (int) preg_replace('/[^\d-]/', '', (string) ($grid[$key][$m] ?? '0'));
                    DB::table('budgets')->updateOrInsert(['year' => $year, 'month' => $m, 'line' => $key],
                        ['amount' => $v, 'updated_by' => $userId, 'updated_at' => now(), 'created_at' => now()]);
                }
            }
        });
    }

    /** Gợi ý: thực tế năm trước từng tháng × (1 + tăng trưởng %) — tháng năm trước không có số thì lấy bình quân các tháng có số */
    public function suggest(int $year, float $growthPct): array
    {
        $base = $this->actual($year - 1);
        $out = $this->empty();
        foreach ($base as $key => $months) {
            $nonZero = array_filter($months);
            $avg = $nonZero ? array_sum($nonZero) / count($nonZero) : 0;
            foreach ($months as $m => $v) {
                $out[$key][$m] = (int) (round(($v ?: $avg) * (1 + $growthPct / 100) / ($key === 'so_ca' ? 1 : 100000)) * ($key === 'so_ca' ? 1 : 100000));
            }
        }

        return $out;
    }

    private function empty(): array
    {
        return array_map(fn () => array_fill(1, 12, 0), self::LINES);
    }
}
