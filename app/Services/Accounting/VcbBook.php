<?php

namespace App\Services\Accounting;

use App\Models\Accounting\CtPayment;
use App\Models\Accounting\VcbEntry;
use App\Models\Accounting\VcbRecurring;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sổ TK VCB chú Lộc.
 * Số dư = số dư đầu kế toán chốt (dòng source = 'opening', tính đến hết ngày đó) + thu − chi các dòng SAU ngày đó.
 * Dòng trước ngày chốt chỉ để ghi nhớ (số cũ từ file Excel), không cộng vào số dư.
 * - Thanh toán CT hình thức L-VCB tự sinh / cập nhật / xóa dòng thu (source = 'ct').
 * - Khoản cố định hằng tháng (vcb_recurrings) tự sinh dòng chi mỗi tháng từ tháng chốt số dư đến tháng hiện tại.
 */
class VcbBook
{
    public function opening(): ?VcbEntry
    {
        return VcbEntry::where('source', 'opening')->orderByDesc('entry_date')->orderByDesc('id')->first();
    }

    /** Số dư cuối ngày $date; null nếu chưa chốt số dư đầu hoặc ngày trước ngày chốt */
    public function balanceAt(Carbon $date): ?int
    {
        $opening = $this->opening();
        if (!$opening || $date->copy()->startOfDay()->lt($opening->entry_date)) {
            return null;
        }
        $sum = VcbEntry::where('source', '!=', 'opening')
            ->where('entry_date', '>', $opening->entry_date->toDateString())
            ->where('entry_date', '<=', $date->toDateString())
            ->selectRaw('COALESCE(SUM(thu),0) - COALESCE(SUM(chi),0) as net')->value('net');

        return (int) $opening->thu - (int) $opening->chi + (int) $sum;
    }

    /** Dòng trong tháng + số dư đầu / cuối tháng */
    public function month(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $this->ensureRecurring();
        $entries = VcbEntry::whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('entry_date')->orderBy('id')->get();
        $opening = $this->opening();
        $counted = fn (VcbEntry $e) => $opening && $e->source !== 'opening' && $e->entry_date->gt($opening->entry_date);

        return [
            'entries' => $entries,
            'opening' => $opening,
            'start_balance' => $this->balanceAt($start->copy()->subDay()),
            'end_balance' => $this->balanceAt($end),
            'thu' => (int) $entries->filter($counted)->sum('thu'),
            'chi' => (int) $entries->filter($counted)->sum('chi'),
            'counted' => $counted,
        ];
    }

    /** Sinh dòng chi khoản cố định cho các tháng từ tháng chốt số dư đến tháng hiện tại (không tạo trùng) */
    public function ensureRecurring(): void
    {
        $opening = $this->opening();
        if (!$opening) {
            return;
        }
        $recs = VcbRecurring::where('active', true)->get();
        if ($recs->isEmpty()) {
            return;
        }
        $last = Carbon::now()->startOfMonth();
        for ($m = $opening->entry_date->copy()->startOfMonth(); $m->lte($last); $m->addMonth()) {
            foreach ($recs as $r) {
                $date = $m->copy()->day(min($r->day, $m->daysInMonth));
                if ($date->lte($opening->entry_date)) {
                    continue;
                }
                VcbEntry::firstOrCreate(
                    ['recurring_id' => $r->id, 'period' => $m->format('Y-m')],
                    ['entry_date' => $date->toDateString(), 'description' => $r->description, 'chi' => $r->chi, 'thu' => 0, 'source' => 'recurring'],
                );
            }
        }
    }

    /** Thanh toán CT qua VCB chú Lộc -> dòng thu tương ứng (tạo / sửa / xóa theo hình thức) */
    public function syncCtPayment(CtPayment $payment): void
    {
        $entry = VcbEntry::where('ct_payment_id', $payment->id)->first();
        if ($payment->method !== 'L-VCB') {
            $entry?->delete();

            return;
        }
        $payment->loadMissing('receivable.partner');
        $data = [
            'entry_date' => $payment->paid_on->toDateString(),
            'description' => 'Thu CT ' . $payment->receivable->title,
            'thu' => (int) $payment->amount,
            'chi' => 0,
            'source' => 'ct',
            'ct_payment_id' => $payment->id,
            'note' => $payment->note,
        ];
        $entry ? $entry->update($data) : VcbEntry::create($data);
    }

    public function deleteForPayment(int $paymentId): void
    {
        VcbEntry::where('ct_payment_id', $paymentId)->delete();
    }

    /** Số dư cuối từng tháng trong năm (cho bảng tổng hợp / trợ lý) */
    public function yearBalances(int $year): Collection
    {
        // Chỉ các tháng đã bắt đầu (tháng sau chưa có số)
        return collect(range(1, 12))
            ->filter(fn ($m) => Carbon::create($year, $m, 1)->lte(now()))
            ->mapWithKeys(fn ($m) => [$m => $this->balanceAt(Carbon::create($year, $m, 1)->endOfMonth())]);
    }
}
