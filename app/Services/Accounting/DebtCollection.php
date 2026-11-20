<?php

namespace App\Services\Accounting;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Thu nợ: đơn vị đang có nợ quá hạn hoặc tiền kẹt (chưa xuất HĐ ≥ 7 ngày), người phụ trách, lần nhắc gần nhất, khách hẹn trả.
 * Dữ liệu: unit_collectors (phụ trách), debt_contacts (lịch sử nhắc nợ). Trang: Phân tích tài chính → Thu nợ.
 */
class DebtCollection
{
    public const CHANNELS = ['goi' => 'Gọi điện', 'zalo' => 'Zalo', 'email' => 'Email', 'gap' => 'Gặp trực tiếp', 'cong_van' => 'Công văn'];

    /** Không nhắc quá số ngày này = cần nhắc lại */
    public const REMIND_DAYS = 14;

    public const STATUS = [
        'qua_hen' => 'Quá ngày hẹn trả',
        'chua_phu_trach' => 'Chưa ai phụ trách',
        'can_nhac' => 'Lâu chưa nhắc',
        'co_hen' => 'Đã hẹn trả',
        'dang_theo_doi' => 'Đang theo dõi',
    ];

    public function __construct(private Receivables $receivables)
    {
    }

    public function list(): Collection
    {
        $today = Carbon::today();
        $rows = $this->receivables->rows()->groupBy('unit_id');
        $collectors = DB::table('unit_collectors')->get()->keyBy('unit_id');
        $people = $this->people()->keyBy('id');
        $contacts = DB::table('debt_contacts')->orderByDesc('contacted_on')->orderByDesc('id')->get()->groupBy('unit_id');

        return $rows->map(function (Collection $g, $unitId) use ($today, $collectors, $people, $contacts) {
            $overdue = (int) $g->where('overdue_days', '>', 0)->sum('owe');
            $stuck = (int) $g->filter(fn ($r) => $r->bucket === 'chua_xuat_hd' && ($r->days_since_shot ?? 0) >= Receivables::STUCK_DAYS)->sum('owe');
            if ($overdue <= 0 && $stuck <= 0) {
                return null;
            }
            $assigneeId = $collectors[$unitId]->assignee_id ?? null;
            $history = $contacts[$unitId] ?? collect();
            $last = $history->first();
            $promise = $history->first(fn ($c) => $c->promised_date);
            $promiseDate = $promise ? Carbon::parse($promise->promised_date) : null;
            $lastDate = $last ? Carbon::parse($last->contacted_on) : null;
            $status = match (true) {
                $promiseDate && $promiseDate->lt($today) && (!$lastDate || $lastDate->lte($promiseDate)) => 'qua_hen',
                !$assigneeId => 'chua_phu_trach',
                $promiseDate && $promiseDate->gte($today) => 'co_hen',
                !$lastDate || $lastDate->diffInDays($today) > self::REMIND_DAYS => 'can_nhac',
                default => 'dang_theo_doi',
            };

            return [
                'unit_id' => (int) $unitId,
                'unit' => $g->first()->unit,
                'owe' => (int) $g->sum('owe'),
                'overdue' => $overdue,
                'stuck' => $stuck,
                'max_overdue_days' => (int) $g->max('overdue_days'),
                'assignee_id' => $assigneeId,
                'assignee' => $assigneeId ? ($people[$assigneeId]->name ?? 'Không rõ') : null,
                'last' => $last ? [
                    'date' => $lastDate, 'days_ago' => $lastDate->diffInDays($today), 'channel' => self::CHANNELS[$last->channel] ?? $last->channel,
                    'note' => $last->note, 'by' => $people[$last->created_by]->name ?? null,
                ] : null,
                'promise' => $promise ? ['date' => $promiseDate, 'amount' => $promise->promised_amount !== null ? (int) $promise->promised_amount : null] : null,
                'contacts' => $history->take(6)->map(fn ($c) => [
                    'date' => Carbon::parse($c->contacted_on), 'channel' => self::CHANNELS[$c->channel] ?? $c->channel, 'note' => $c->note,
                    'promised_date' => $c->promised_date ? Carbon::parse($c->promised_date) : null, 'promised_amount' => $c->promised_amount,
                    'by' => $people[$c->created_by]->name ?? null,
                ])->values()->all(),
                'contact_count' => $history->count(),
                'status' => $status,
                'status_label' => self::STATUS[$status],
            ];
        })->filter()->sortBy(fn ($r) => [array_search($r['status'], array_keys(self::STATUS)), -($r['overdue'] + $r['stuck'])])->values();
    }

    /** Người có thể phụ trách thu nợ: Admin, phòng Kế toán, phòng Sales (tên hiển thị theo hồ sơ) */
    public function people(): Collection
    {
        return User::with('profile:id,profile_firstname,profile_lastname')
            ->where(fn ($q) => $q->whereIn('department', ['ke_toan', 'sales'])->orWhereIn('role', [0, 1, 3]))
            ->where('name', '!=', 'SubAdmin')
            ->get(['id', 'name', 'email', 'department', 'role', 'profile_id'])
            ->map(fn ($u) => (object) ['id' => $u->id, 'name' => $u->display_name ?? $u->name,
                'group' => $u->department === 'sales' ? 'Sales' : ($u->department === 'ke_toan' || (int) $u->role === 3 ? 'Kế toán' : 'Quản lý')])
            ->sortBy('name')->values();
    }
}
