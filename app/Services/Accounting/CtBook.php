<?php

namespace App\Services\Accounting;

use App\Models\Accounting\CtPartner;
use App\Models\Accounting\CtReceivable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Công nợ CT: phần Medicen thu về của từng bệnh viện, từng tháng; đã thu = tổng các lần thanh toán.
 * Còn phải thu = Medicen thu về − đã thu.
 */
class CtBook
{
    private const SUM_COLS = ['quantity', 'revenue', 'cost', 'hospital_share', 'medicen_share', 'hospital_collect', 'medicen_collect'];

    /** Số dòng có đơn vị, đã thu, còn phải thu (theo năm, đơn vị) */
    public function rows(int $year, ?int $partnerId = null)
    {
        return CtReceivable::with(['partner', 'payments'])
            ->where('year', $year)
            ->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId))
            ->orderBy('month')->orderBy(CtPartner::select('sort')->whereColumn('ct_partners.id', 'ct_receivables.partner_id'))
            ->orderBy('kind')->get();
    }

    /** Tổng theo đơn vị và theo tháng của một năm */
    public function summary(int $year): array
    {
        $rows = $this->rows($year);
        $agg = function ($group) {
            $out = [];
            foreach (self::SUM_COLS as $c) {
                $out[$c] = (int) $group->sum($c);
            }
            $out['profit'] = $out['revenue'] - $out['cost'];
            $out['paid'] = (int) $group->sum(fn ($r) => $r->paid);
            $out['outstanding'] = $out['medicen_collect'] - $out['paid'];

            return $out;
        };

        return [
            'by_partner' => $rows->groupBy('partner_id')->map(fn ($g) => ['partner' => $g->first()->partner] + $agg($g))
                ->sortBy(fn ($x) => $x['partner']->sort)->values(),
            'by_month' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => $agg($rows->where('month', $m))]),
            'total' => $agg($rows),
        ];
    }

    /**
     * Phần Medicen còn phải thu tính đến đầu tháng (các kỳ TRƯỚC tháng $start, trừ các lần đã trả trước ngày $start).
     * $kind null = mọi loại.
     */
    public function outstandingAsOf(Carbon $start, ?int $partnerId = null, ?string $kind = null): int
    {
        $period = $start->year * 100 + $start->month;
        $q = DB::table('ct_receivables as r')
            ->whereRaw('(r.year * 100 + r.month) < ?', [$period])
            ->when($partnerId, fn ($q) => $q->where('r.partner_id', $partnerId))
            ->when($kind, fn ($q) => $q->where('r.kind', $kind));
        $due = (int) (clone $q)->sum('r.medicen_collect');
        $paid = (int) (clone $q)->join('ct_payments as p', 'p.receivable_id', '=', 'r.id')
            ->where('p.paid_on', '<', $start->toDateString())->sum('p.amount');

        return $due - $paid;
    }

    /** Tiền đã nhận trong khoảng ngày cho các kỳ trước tháng của $from */
    public function paidBetween(Carbon $from, Carbon $to, ?int $partnerId = null, ?string $kind = null): int
    {
        $period = $from->year * 100 + $from->month;

        return (int) DB::table('ct_payments as p')->join('ct_receivables as r', 'r.id', '=', 'p.receivable_id')
            ->whereRaw('(r.year * 100 + r.month) < ?', [$period])
            ->whereBetween('p.paid_on', [$from->toDateString(), $to->toDateString()])
            ->when($partnerId, fn ($q) => $q->where('r.partner_id', $partnerId))
            ->when($kind, fn ($q) => $q->where('r.kind', $kind))
            ->sum('p.amount');
    }

    /** Tổng còn phải thu hiện tại (mọi kỳ) */
    public function totalOutstanding(?int $partnerId = null): int
    {
        $q = DB::table('ct_receivables')->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId));
        $due = (int) (clone $q)->sum('medicen_collect');
        $paid = (int) DB::table('ct_payments')->whereIn('receivable_id', (clone $q)->select('id'))->sum('amount');

        return $due - $paid;
    }
}
