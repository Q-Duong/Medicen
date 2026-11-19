<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phải thu XQ (trang Công nợ): phân tuổi nợ theo hạn thanh toán và "tiền kẹt" (đã chụp xong nhưng chưa xuất hóa đơn).
 * Hạn thanh toán = ngày xuất hóa đơn + số ngày hạn. Đơn đã hủy (status 0) và đơn hết nợ không tính.
 */
class Receivables
{
    public const BUCKETS = [
        'chua_den_han' => 'Chưa đến hạn',
        'qh_1_30' => 'Quá hạn 1–30 ngày',
        'qh_31_60' => 'Quá hạn 31–60 ngày',
        'qh_61_90' => 'Quá hạn 61–90 ngày',
        'qh_tren_90' => 'Quá hạn trên 90 ngày',
        'chua_xuat_hd' => 'Chưa xuất hóa đơn',
    ];

    /** Số ngày sau ngày chụp mà chưa xuất hóa đơn thì coi là "kẹt" */
    public const STUCK_DAYS = 7;

    public function rows(): Collection
    {
        $today = Carbon::today();

        return DB::table('accountants')
            ->join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)
            ->where('accountants.accountant_owe', '>', 0)
            ->get(['orders.id', 'orders.unit_id', 'units.unit_abbreviation', 'order_details.ord_cty_name', 'order_details.ord_start_day', 'order_details.ord_end_day',
                'accountants.accountant_number', 'accountants.accountant_date', 'accountants.accountant_deadline', 'accountants.accountant_owe'])
            ->map(function ($r) use ($today) {
                $issued = self::date($r->accountant_date);
                $due = $issued && is_numeric($r->accountant_deadline) ? $issued->copy()->addDays((int) $r->accountant_deadline) : null;
                $shot = self::date($r->ord_end_day) ?? self::date($r->ord_start_day);
                $overdue = $due && $due->lt($today) ? $due->diffInDays($today) : 0;
                $bucket = !$issued ? 'chua_xuat_hd' : match (true) {
                    $overdue <= 0 => 'chua_den_han',
                    $overdue <= 30 => 'qh_1_30',
                    $overdue <= 60 => 'qh_31_60',
                    $overdue <= 90 => 'qh_61_90',
                    default => 'qh_tren_90',
                };

                return (object) [
                    'id' => $r->id,
                    'unit_id' => $r->unit_id,
                    'unit' => $r->unit_abbreviation ?: '(chưa có đơn vị)',
                    'company' => $r->ord_cty_name,
                    'shot' => $shot,
                    'invoice_no' => $r->accountant_number,
                    'issued' => $issued,
                    'due' => $due,
                    'owe' => (int) $r->accountant_owe,
                    'overdue_days' => $overdue,
                    'days_since_shot' => $shot ? $shot->diffInDays($today, false) : null,
                    'bucket' => $bucket,
                ];
            });
    }

    public function summary(): array
    {
        $rows = $this->rows();
        $buckets = collect(self::BUCKETS)->map(fn ($label, $k) => [
            'key' => $k, 'label' => $label, 'amount' => $rows->where('bucket', $k)->sum('owe'), 'count' => $rows->where('bucket', $k)->count(),
        ])->values();
        $byUnit = $rows->groupBy('unit')->map(function ($g, $unit) {
            $row = ['unit' => $unit, 'total' => $g->sum('owe'), 'count' => $g->count(), 'max_overdue' => (int) $g->max('overdue_days')];
            foreach (array_keys(self::BUCKETS) as $k) {
                $row[$k] = $g->where('bucket', $k)->sum('owe');
            }

            return $row;
        })->sortByDesc(fn ($r) => $r['total'] - $r['chua_den_han'])->values();
        $stuck = $rows->filter(fn ($r) => $r->bucket === 'chua_xuat_hd' && ($r->days_since_shot ?? 0) >= self::STUCK_DAYS)
            ->sortByDesc('owe')->values();
        $total = $rows->sum('owe');
        $overdue = $rows->filter(fn ($r) => $r->overdue_days > 0)->sum('owe');

        return [
            'today' => Carbon::today(),
            'total' => $total,
            'overdue' => $overdue,
            'overdue_pct' => $total ? round($overdue / $total * 100, 1) : 0,
            'uninvoiced' => $rows->where('bucket', 'chua_xuat_hd')->sum('owe'),
            'stuck' => $stuck,
            'stuck_total' => $stuck->sum('owe'),
            'buckets' => $buckets,
            'by_unit' => $byUnit,
            'overdue_rows' => $rows->filter(fn ($r) => $r->overdue_days > 0)->sortByDesc('overdue_days')->values(),
            'dso' => $this->dso(),
        ];
    }

    /** Số ngày thu tiền bình quân 6 tháng gần nhất: từ ngày xuất HĐ đến ngày khách thanh toán, có trọng số theo số tiền */
    public function dso(): ?int
    {
        $rows = DB::table('accountants')
            ->join('orders', 'orders.id', '=', 'accountants.order_id')
            ->where('orders.status_id', '!=', 0)
            ->whereNotNull('accountants.accountant_date')->whereNotNull('accountants.accountant_day_payment')
            ->where('accountants.accountant_day_payment', '>=', Carbon::today()->subMonths(6)->toDateString())
            ->get(['accountants.accountant_date', 'accountants.accountant_day_payment', 'accountants.accountant_amount_paid']);
        $sum = 0;
        $w = 0;
        foreach ($rows as $r) {
            $a = self::date($r->accountant_date);
            $b = self::date($r->accountant_day_payment);
            $amt = max(0, (int) $r->accountant_amount_paid);
            if ($a && $b && $b->gte($a) && $amt > 0) {
                $sum += $a->diffInDays($b) * $amt;
                $w += $amt;
            }
        }

        return $w ? (int) round($sum / $w) : null;
    }

    public static function date($v): ?Carbon
    {
        if (!$v) {
            return null;
        }
        try {
            $s = (string) $v;
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
                return Carbon::parse(substr($s, 0, 10))->startOfDay();
            }
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $s)) {
                return Carbon::createFromFormat('d/m/Y', $s)->startOfDay();
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
