<?php

namespace App\Services\Accounting;

use App\Models\Accounting\CtPartner;
use App\Models\Report;
use App\Models\ReportItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Báo cáo thu chi tháng X lấy số tự động (report_items.auto_key):
 * - 'vcb'            : Tiền hiện có (VCB chú Lộc) = số dư sổ VCB cuối ngày trước tháng X.
 * - 'ct:CODE[:bhyt]' : Dự thu = phần Medicen còn phải thu của bệnh viện tính đến đầu tháng X (mọi kỳ trước),
 *                      Đã thu = tiền bệnh viện trả trong tháng X cho các kỳ đó (cả HDB và VCB chú).
 * Bệnh viện còn nợ mà báo cáo chưa có dòng thì tự thêm dòng.
 */
class ReportAutoSync
{
    /** Tên dòng mẫu -> khóa tự động (dùng khi tạo khung báo cáo) */
    public const DEFAULT_KEYS = [
        '- Tiền hiện có (VCB chú Lộc)' => 'vcb',
        '- CT Á Châu' => 'ct:AC',
        '- CT Á Châu (bhyt)' => 'ct:AC:bhyt',
        '- CT Bảo Lộc' => 'ct:BL',
        '- CT Cà Mau' => 'ct:CM',
        '- CT Gò Công' => 'ct:GC',
        '- CT Lagi' => 'ct:LG',
    ];

    public function __construct(private CtBook $ct, private VcbBook $vcb)
    {
    }

    public function tagDefaults(Report $report): void
    {
        foreach (self::DEFAULT_KEYS as $name => $key) {
            ReportItem::where('report_id', $report->id)->where('name', $name)->whereNull('auto_key')->update(['auto_key' => $key]);
        }
    }

    public function sync(Report $report): void
    {
        if (!is_numeric($report->month) || !$report->id || !$report->items()->exists()) {
            return;
        }
        $start = Carbon::create((int) $report->year, (int) $report->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $hasCtData = DB::table('ct_receivables')->exists();

        DB::transaction(function () use ($report, $start, $end, $hasCtData) {
            $items = ReportItem::where('report_id', $report->id)->whereNotNull('auto_key')->get()->keyBy('auto_key');

            // Số dư VCB chú Lộc
            if ($items->has('vcb')) {
                $bal = $this->vcb->balanceAt($start->copy()->subDay());
                if ($bal !== null) {
                    $items['vcb']->update(['estimated_amount' => $bal]);
                }
            }

            if (!$hasCtData) {
                return;
            }
            // Thêm dòng cho bệnh viện còn nợ mà chưa có dòng
            foreach (CtPartner::orderBy('sort')->get() as $p) {
                foreach (['thuong' => '', 'bhyt' => ':bhyt'] as $kind => $suffix) {
                    $key = 'ct:' . $p->code . $suffix;
                    if (!$items->has($key) && $this->ct->outstandingAsOf($start, $p->id, $kind) > 0) {
                        $items[$key] = ReportItem::create([
                            'report_id' => $report->id, 'type' => 'thu', 'summary_group' => 'CT', 'auto_key' => $key,
                            'name' => '- CT ' . $p->name . ($kind === 'bhyt' ? ' (bhyt)' : ''),
                            'estimated_amount' => 0, 'actual_amount' => 0,
                            'sort_order' => (int) ReportItem::where('report_id', $report->id)->where('summary_group', 'CT')->max('sort_order') + 1,
                        ]);
                    }
                }
            }
            // Cập nhật số các dòng CT
            foreach ($items as $key => $item) {
                if (!str_starts_with($key, 'ct:')) {
                    continue;
                }
                [, $code, $kind] = array_pad(explode(':', $key), 3, 'thuong');
                $partner = CtPartner::where('code', $code)->first();
                if (!$partner) {
                    continue;
                }
                $paid = $this->ct->paidBetween($start, $end, $partner->id, $kind);
                $lastPaid = $paid > 0 ? DB::table('ct_payments as p')->join('ct_receivables as r', 'r.id', '=', 'p.receivable_id')
                    ->where('r.partner_id', $partner->id)->where('r.kind', $kind)
                    ->whereBetween('p.paid_on', [$start->toDateString(), $end->toDateString()])->max('p.paid_on') : null;
                $item->update([
                    'estimated_amount' => max(0, $this->ct->outstandingAsOf($start, $partner->id, $kind)),
                    'actual_amount' => $paid,
                    'actual_date' => $lastPaid ? Carbon::parse($lastPaid)->setTime(9, 0) : null,
                ]);
            }
        });
    }
}
