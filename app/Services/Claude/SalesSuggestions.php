<?php

namespace App\Services\Claude;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Câu hỏi gợi ý của Trợ lý Sales, sinh từ dữ liệu thật trong ngày (không gọi AI, không tốn token).
 * Lưu cache 1 giờ. Chức năng nào không có dữ liệu phù hợp thì giữ câu mẫu cố định trong config/claude.php.
 */
class SalesSuggestions
{
    private const PER_MODE = 3;

    /**
     * @param array<string, array> $modes config claude.departments.sales.modes
     * @return array<string, array> $modes với 'suggestions' đã thay bằng gợi ý trong ngày
     */
    public function apply(array $modes): array
    {
        try {
            $dynamic = Cache::remember('assistant.sales.suggestions.' . now()->format('Y-m-d-H'), 3600, fn () => $this->build());
        } catch (Throwable $e) {
            Log::warning('Không tạo được gợi ý trợ lý Sales', ['error' => $e->getMessage()]);
            $dynamic = [];
        }

        foreach ($modes as $key => &$mode) {
            $fixed = $mode['suggestions'] ?? [];
            $mode['suggestions'] = array_slice(array_values(array_unique(array_merge($dynamic[$key] ?? [], $fixed))), 0, self::PER_MODE);
        }

        return $modes;
    }

    /** @return array<string, list<string>> */
    public function build(): array
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $nextMonday = $today->copy()->next(Carbon::MONDAY);

        $schedule = [
            "Ngày mai {$this->day($tomorrow)} xe nào còn trống?",
            "Tuần sau ({$nextMonday->format('d/m')}–{$nextMonday->copy()->addDays(6)->format('d/m')}) xe nào còn trống?",
        ];
        $unscheduled = $this->orders()->where('orders.status_id', 5)
            ->where('order_details.ord_start_day', '>=', $today->toDateString())->count();
        if ($unscheduled > 0) {
            $schedule[] = "Liệt kê {$unscheduled} đơn sắp tới chưa xếp xe";
        }

        $messages = [];
        foreach ($this->ordersOn($tomorrow->copy()->addDay(), 2) as $o) {
            $messages[] = "Soạn tin confirm lịch chụp {$this->date($o->ord_start_day)} cho {$o->unit}";
        }
        foreach ($this->ordersOn($tomorrow, 2) as $o) {
            $messages[] = "Soạn tin nhắc lịch chụp ngày mai cho {$o->unit}";
        }
        $done = $this->orders()->where('order_details.ord_end_day', $today->copy()->subDay()->toDateString())->first();
        if ($done) {
            $messages[] = "Soạn tin cảm ơn và báo ngày trả kết quả cho {$done->unit}";
        }

        $profit = [];
        $upcoming = $this->orders()
            ->whereBetween('order_details.ord_start_day', [$tomorrow->toDateString(), $today->copy()->addDays(7)->toDateString()])
            ->orderBy('order_details.ord_start_day')->first();
        if ($upcoming) {
            $profit[] = "Đơn {$upcoming->unit} ngày {$this->date($upcoming->ord_start_day)} ({$upcoming->order_quantity} cas) lãi bao nhiêu?";
        }

        $debts = array_map(fn ($d) => "Kiểm tra công nợ {$d['unit']} (quá hạn {$d['days']} ngày)", $this->overdue(2));
        if ($debts) {
            $debts[] = 'Soạn tin nhắc nợ cho ' . $this->overdue(1)[0]['unit'];
        }

        // Khám sức khỏe thường định kỳ hằng năm: khách chụp khoảng 1 năm trước mà chưa đặt lại
        $quotes = array_map(
            fn ($u) => "{$u->unit} chụp {$u->order_quantity} cas tháng {$this->monthYear($u->ord_start_day)}, soạn báo giá mời chụp lại năm nay",
            $this->dueForRepeat(2),
        );

        return [
            'auto' => array_values(array_filter([$schedule[0], $debts[0] ?? null, $messages[0] ?? null])),
            'bao_gia' => $quotes,
            'loi_nhuan' => $profit,
            'lich_xe' => $schedule,
            'cong_no' => $debts,
            'tin_nhan' => array_slice($messages, 0, self::PER_MODE),
        ];
    }

    /** Đơn đang hoạt động (bỏ đơn mới chưa xác nhận), kèm tên đơn vị */
    private function orders()
    {
        return DB::table('orders')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)
            ->select('orders.id', 'orders.order_quantity', 'order_details.ord_start_day', 'order_details.ord_end_day',
                DB::raw("COALESCE(NULLIF(units.unit_abbreviation, ''), order_details.ord_cty_name) as unit"));
    }

    private function ordersOn(Carbon $day, int $limit)
    {
        return $this->orders()->where('order_details.ord_start_day', $day->toDateString())
            ->orderBy('orders.id')->limit($limit)->get()->unique('unit');
    }

    /** @return list<array{unit: string, days: int}> đơn vị nợ quá hạn lâu nhất */
    private function overdue(int $limit): array
    {
        $today = Carbon::today();

        return DB::table('accountants')
            ->join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->where('accountants.accountant_status', 0)
            ->where('orders.status_id', '!=', 0)
            ->whereNotNull('accountants.accountant_date')
            ->whereRaw('accountants.accountant_owe > COALESCE(accountants.accountant_amount_paid, 0)')
            ->select('accountants.accountant_date', 'accountants.accountant_deadline',
                DB::raw("COALESCE(NULLIF(units.unit_abbreviation, ''), order_details.ord_cty_name) as unit"))
            ->get()
            ->map(function ($r) use ($today) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->accountant_date)) {
                    return null;
                }
                $due = Carbon::createFromFormat('!Y-m-d', $r->accountant_date)->addDays((int) $r->accountant_deadline);

                return $due->lt($today) ? ['unit' => $r->unit, 'days' => (int) $due->diffInDays($today)] : null;
            })
            ->filter()
            ->sortByDesc('days')
            ->unique('unit')
            ->take($limit)
            ->values()
            ->all();
    }

    private function dueForRepeat(int $limit)
    {
        $today = Carbon::today();
        $from = $today->copy()->subMonths(13)->toDateString();
        $to = $today->copy()->subMonths(11)->toDateString();

        return $this->orders()
            ->whereBetween('order_details.ord_start_day', [$from, $to])
            ->whereNotExists(function ($q) use ($to) {
                $q->from('orders as later')
                    ->join('order_details as later_d', 'later_d.id', '=', 'later.order_detail_id')
                    ->whereColumn('later.unit_id', 'orders.unit_id')
                    ->where('later_d.ord_start_day', '>', $to);
            })
            ->orderByDesc('orders.order_quantity')
            ->limit($limit * 3)
            ->get()
            ->unique('unit')
            ->take($limit)
            ->values()
            ->all();
    }

    private function day(Carbon $d): string
    {
        return $d->format('d/m') . ' (' . ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][$d->dayOfWeek] . ')';
    }

    private function date(string $ymd): string
    {
        return Carbon::parse($ymd)->format('d/m');
    }

    private function monthYear(string $ymd): string
    {
        return Carbon::parse($ymd)->format('m/Y');
    }
}
