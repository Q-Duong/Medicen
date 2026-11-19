<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hạn mức tín dụng từng đơn vị khách hàng (mảng XQ).
 *
 * - Hạn mức nợ: kế toán đặt ở trang Phân tích tài chính → Hạn mức tín dụng (bảng unit_credit_limits).
 *   Chưa đặt thì dùng GỢI Ý = 2,5 tháng doanh thu bình quân 6 tháng gần nhất (làm tròn triệu, tối thiểu 20 triệu).
 * - Số ngày quá hạn tối đa: mặc định 30 ngày.
 * Khi có hợp đồng từng đơn vị (xem memory contracts-upload-plan) thì lấy theo điều khoản đã ký.
 */
class CreditPolicy
{
    public const DEFAULT_DAYS = 30;
    public const MIN_LIMIT = 20000000;
    public const MONTHS_OF_REVENUE = 2.5;

    public const STATUS = [
        'vuot_han_muc' => 'Vượt hạn mức',
        'qua_han_lau' => 'Quá hạn quá lâu',
        'sap_vuot' => 'Sắp chạm hạn mức (≥ 80%)',
        'ok' => 'Trong hạn mức',
    ];

    public function __construct(private Receivables $receivables)
    {
    }

    /** Mọi đơn vị đang nợ hoặc có đơn trong 6 tháng, kèm hạn mức và trạng thái; vi phạm nặng nhất lên đầu */
    public function all(): Collection
    {
        $debts = $this->receivables->rows()->groupBy('unit_id');
        $revenue = $this->revenue6m();
        $manual = DB::table('unit_credit_limits')->get()->keyBy('unit_id');
        $ids = $debts->keys()->merge($revenue->keys())->filter()->unique();
        $units = DB::table('units')->whereIn('id', $ids)->get(['id', 'unit_abbreviation', 'unit_name'])->keyBy('id');

        return $ids->map(fn ($id) => $this->build((int) $id, $units[$id] ?? null, $debts[$id] ?? collect(), (float) ($revenue[$id] ?? 0), $manual[$id] ?? null))
            ->sortBy(fn ($r) => [array_search($r['status'], array_keys(self::STATUS)), -$r['owe']])
            ->values();
    }

    public function forUnit(int $unitId): array
    {
        $debts = $this->receivables->rows()->where('unit_id', $unitId);
        $unit = DB::table('units')->where('id', $unitId)->first(['id', 'unit_abbreviation', 'unit_name']);

        return $this->build($unitId, $unit, $debts, (float) ($this->revenue6m($unitId)[$unitId] ?? 0), DB::table('unit_credit_limits')->where('unit_id', $unitId)->first());
    }

    private function build(int $id, $unit, Collection $debts, float $rev6m, $manual): array
    {
        $avgMonth = $rev6m / 6;
        $suggested = (int) max(self::MIN_LIMIT, round($avgMonth * self::MONTHS_OF_REVENUE / 1000000) * 1000000);
        $limit = $manual && $manual->limit_amount !== null ? (int) $manual->limit_amount : $suggested;
        $days = $manual && $manual->max_overdue_days !== null ? (int) $manual->max_overdue_days : self::DEFAULT_DAYS;
        $owe = (int) $debts->sum('owe');
        $maxOverdue = (int) $debts->max('overdue_days');
        $used = $limit > 0 ? round($owe / $limit * 100, 1) : null;
        $status = match (true) {
            $owe > $limit => 'vuot_han_muc',
            $maxOverdue > $days => 'qua_han_lau',
            $used !== null && $used >= 80 => 'sap_vuot',
            default => 'ok',
        };

        return [
            'unit_id' => $id,
            'unit' => $unit->unit_abbreviation ?? '(đơn vị #' . $id . ')',
            'unit_name' => $unit->unit_name ?? null,
            'owe' => $owe,
            'overdue' => (int) $debts->where('overdue_days', '>', 0)->sum('owe'),
            'max_overdue_days' => $maxOverdue,
            'uninvoiced' => (int) $debts->where('bucket', 'chua_xuat_hd')->sum('owe'),
            'avg_month_revenue' => (int) round($avgMonth),
            'suggested_limit' => $suggested,
            'limit' => $limit,
            'max_days' => $days,
            'is_manual' => (bool) ($manual && ($manual->limit_amount !== null || $manual->max_overdue_days !== null)),
            'note' => $manual->note ?? null,
            'used_pct' => $used,
            'status' => $status,
            'status_label' => self::STATUS[$status],
        ];
    }

    /** Doanh thu thuần 6 tháng gần nhất theo đơn vị */
    private function revenue6m(?int $unitId = null): Collection
    {
        return DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.status_id', '!=', 0)
            ->where('order_details.ord_start_day', '>=', Carbon::today()->subMonthsNoOverflow(6)->toDateString())
            ->when($unitId, fn ($q) => $q->where('orders.unit_id', $unitId))
            ->groupBy('orders.unit_id')
            ->selectRaw('orders.unit_id uid, SUM(COALESCE(orders.order_price,0) - COALESCE(orders.order_discount,0)) rev')
            ->pluck('rev', 'uid');
    }
}
