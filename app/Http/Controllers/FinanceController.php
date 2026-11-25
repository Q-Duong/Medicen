<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\Accounting\CashForecast;
use App\Services\Accounting\CreditPolicy;
use App\Services\Claude\AssistantAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Accounting\CfoAnalytics;
use App\Services\Accounting\Receivables;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Phân tích tài chính (Admin, Kế toán, SubAdmin chỉ xem):
 *  - Tiền kẹt & tuổi nợ: phải thu XQ theo hạn thanh toán, đơn đã chụp chưa xuất hóa đơn.
 *  - Lãi ước tính: doanh thu − giá vốn ước tính từng đơn, theo tháng / khách / xe.
 *  - Dòng tiền 13 tuần: dự báo số dư tiền từng tuần từ hóa đơn, Báo cáo thu chi, CT, đơn mới.
 * Cùng số liệu với các tool của Trợ lý Kế toán (tuoi_no_tien_ket, phan_tich_van_hanh, du_bao_dong_tien).
 */
class FinanceController extends Controller
{
    public const TABS = ['tien-ket' => 'Tiền kẹt & tuổi nợ', 'thu-no' => 'Thu nợ', 'han-muc' => 'Hạn mức tín dụng', 'lai-uoc-tinh' => 'Lãi ước tính', 'duyet-gia' => 'Duyệt giá', 'dong-tien' => 'Dòng tiền 13 tuần', 'ke-hoach' => 'Kế hoạch năm',
        'quan-tri' => 'Báo cáo quản trị', 'ghi-nho' => 'Ghi nhớ CFO'];

    /** Sửa hạn mức / kế hoạch: Admin (trừ SubAdmin) và Kế toán */
    public static function canEdit(): bool
    {
        $u = Auth::user();

        return AssistantAccess::isFullAdmin($u) || (int) ($u->role ?? -1) === 3 || ($u->department ?? null) === 'ke_toan';
    }

    /** Ghi nhớ CFO: thêm cả SubAdmin (tài khoản giám đốc) — ghi nhớ là quyết định / tình hình, không sửa số liệu kế toán */
    public static function canEditNotes(): bool
    {
        return self::canEdit() || AssistantAccess::isSubAdmin(Auth::user());
    }

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'tien-ket';
        $data = ['tab' => $tab, 'tabs' => self::TABS, 'canEdit' => self::canEdit(), 'canEditNotes' => self::canEditNotes()];

        if ($tab === 'tien-ket') {
            $data['r'] = app(Receivables::class)->summary();
        } elseif ($tab === 'thu-no') {
            $dc = app(\App\Services\Accounting\DebtCollection::class);
            $all = $dc->list();
            $filter = (string) $request->query('loc', '');
            $data['collect'] = match ($filter) {
                'cua-toi' => $all->where('assignee_id', Auth::id())->values(),
                'qua-hen' => $all->where('status', 'qua_hen')->values(),
                'chua-phu-trach' => $all->where('status', 'chua_phu_trach')->values(),
                'can-nhac' => $all->where('status', 'can_nhac')->values(),
                default => $all,
            };
            $data['collectAll'] = $all;
            $data['filter'] = $filter;
            $data['people'] = $dc->people();
        } elseif ($tab === 'duyet-gia') {
            $data['prices'] = app(\App\Services\Accounting\PriceGuard::class)->list();
            $data['canApprove'] = self::canEdit();
        } elseif ($tab === 'han-muc') {
            $data['credits'] = app(CreditPolicy::class)->all();
        } elseif ($tab === 'quan-tri') {
            // Báo cáo quản trị lãi / lỗ theo tháng (cùng công thức tool trợ lý bao_cao_quan_tri)
            $to = $this->date($request->query('den')) ?? Carbon::today();
            $from = $this->date($request->query('tu')) ?? $to->copy()->startOfYear();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
            $data['mr'] = app(\App\Services\Accounting\ManagementReport::class)->build($from, $to);
            $data['mrFrom'] = $from;
            $data['mrTo'] = $to;
        } elseif ($tab === 'ghi-nho') {
            $data['notes'] = \App\Models\CfoNote::with('author:id,name')->orderBy('done')->orderByRaw('remind_on IS NULL')->orderBy('remind_on')->orderByDesc('id')->limit(200)->get();
        } elseif ($tab === 'ke-hoach') {
            $budget = app(\App\Services\Accounting\Budget::class);
            $year = (int) $request->query('nam', now()->month >= 11 ? now()->year + 1 : now()->year);
            $year = max(2023, min(now()->year + 2, $year));
            $data['year'] = $year;
            $data['cmp'] = $budget->compare($year);
            // "Gợi ý từ năm trước": điền sẵn vào ô nhập (chưa lưu) để xem lại rồi bấm Lưu
            $data['suggestPct'] = $request->has('goi_y') ? (float) $request->query('goi_y') : null;
            $data['grid'] = $data['suggestPct'] !== null ? $budget->suggest($year, $data['suggestPct']) : array_map(fn ($l) => $l['plan'], $data['cmp']['lines']);
        } elseif ($tab === 'lai-uoc-tinh') {
            $to = $this->date($request->query('den')) ?? Carbon::today();
            $from = $this->date($request->query('tu')) ?? $to->copy()->subMonthsNoOverflow(3)->addDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
            $data += ['from' => $from, 'to' => $to, 'a' => app(CfoAnalytics::class)->analyze($from, $to, true)];
        } else {
            $opt = [
                'so_du' => $request->query('so_du'),
                'qua_han_pct' => $request->query('qua_han', 50),
                'chua_hd_pct' => $request->query('chua_hd', 80),
                'don_moi' => $request->query('don_moi', '1') === '1',
                'nguong' => (int) preg_replace('/\D/', '', (string) $request->query('nguong', '100000000')) ?: 100000000,
            ];
            $data += ['f' => app(CashForecast::class)->build($opt), 'opt' => $opt];
        }

        return view('pages.admin.finance.index', $data);
    }

    private function date($v): ?Carbon
    {
        try {
            return $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Lưu hạn mức tín dụng một đơn vị (để trống = dùng gợi ý) */
    public function saveCredit(Request $request)
    {
        abort_unless(self::canEdit(), 403);
        $data = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'limit_amount' => ['nullable', 'string', 'max:20'],
            'max_overdue_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $limit = preg_replace('/\D/', '', (string) ($data['limit_amount'] ?? ''));
        $old = DB::table('unit_credit_limits')->where('unit_id', $data['unit_id'])->first();
        $new = ['limit_amount' => $limit === '' ? null : (int) $limit, 'max_overdue_days' => $data['max_overdue_days'] ?? null, 'note' => $data['note'] ?? null];
        DB::table('unit_credit_limits')->updateOrInsert(['unit_id' => $data['unit_id']], $new + ['updated_by' => Auth::id(), 'updated_at' => now(), 'created_at' => $old->created_at ?? now()]);

        $unit = DB::table('units')->where('id', $data['unit_id'])->value('unit_abbreviation');
        $changes = [];
        foreach (['limit_amount' => 'Hạn mức nợ', 'max_overdue_days' => 'Quá hạn tối đa (ngày)', 'note' => 'Ghi chú'] as $k => $label) {
            if ((string) ($old->{$k} ?? '') !== (string) ($new[$k] ?? '')) {
                $changes[$label] = [$old->{$k} ?? null, $new[$k]];
            }
        }
        if ($changes) {
            ActivityLog::record('cong_no', 'sua', 'Sửa hạn mức tín dụng ' . $unit, null, $changes);
        }

        return redirect()->route('finance.index', ['tab' => 'han-muc'])->with('bk_success', 'Đã lưu hạn mức của ' . $unit . '.');
    }

    /** Lưu kế hoạch năm (bảng 9 dòng × 12 tháng) */
    public function saveBudget(Request $request)
    {
        abort_unless(self::canEdit(), 403);
        $data = $request->validate(['year' => ['required', 'integer', 'min:2023', 'max:2100'], 'plan' => ['required', 'array']]);
        $budget = app(\App\Services\Accounting\Budget::class);
        $before = $budget->plan((int) $data['year']);
        $budget->save((int) $data['year'], $data['plan'], Auth::id());
        $after = $budget->plan((int) $data['year']);
        $changes = [];
        foreach (\App\Services\Accounting\Budget::LINES as $k => [$label]) {
            if (array_sum($before[$k]) !== array_sum($after[$k])) {
                $changes[$label . ' (cả năm)'] = [array_sum($before[$k]), array_sum($after[$k])];
            }
        }
        ActivityLog::record('bao_cao_thu_chi', 'sua', 'Sửa kế hoạch năm ' . $data['year'], null, $changes ?: null);

        return redirect()->route('finance.index', ['tab' => 'ke-hoach', 'nam' => $data['year']])->with('bk_success', 'Đã lưu kế hoạch năm ' . $data['year'] . '.');
    }

    /** Đổi người phụ trách thu nợ của một đơn vị */
    public function assignCollector(Request $request)
    {
        abort_unless(self::canEdit(), 403);
        $data = $request->validate(['unit_id' => ['required', 'integer', 'exists:units,id'], 'assignee_id' => ['nullable', 'integer', 'exists:users,id']]);
        $old = DB::table('unit_collectors')->where('unit_id', $data['unit_id'])->value('assignee_id');
        DB::table('unit_collectors')->updateOrInsert(['unit_id' => $data['unit_id']],
            ['assignee_id' => $data['assignee_id'] ?? null, 'updated_by' => Auth::id(), 'updated_at' => now(), 'created_at' => now()]);
        $names = app(\App\Services\Accounting\DebtCollection::class)->people()->pluck('name', 'id');
        $unit = DB::table('units')->where('id', $data['unit_id'])->value('unit_abbreviation');
        ActivityLog::record('cong_no', 'sua', 'Giao thu nợ ' . $unit, null, ['Người phụ trách' => [$names[$old] ?? null, $names[$data['assignee_id'] ?? 0] ?? null]]);

        return redirect()->route('finance.index', ['tab' => 'thu-no', 'loc' => $request->input('loc')])->with('bk_success', 'Đã giao thu nợ ' . $unit . '.');
    }

    /** Ghi một lần nhắc nợ (kèm hẹn trả nếu có) */
    public function saveContact(Request $request)
    {
        abort_unless(self::canEdit(), 403);
        $data = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'contacted_on' => ['required', 'date', 'before_or_equal:today'],
            'channel' => ['nullable', 'in:' . implode(',', array_keys(\App\Services\Accounting\DebtCollection::CHANNELS))],
            'note' => ['nullable', 'string', 'max:2000'],
            'promised_date' => ['nullable', 'date'],
            'promised_amount' => ['nullable', 'string', 'max:20'],
        ], ['contacted_on.before_or_equal' => 'Ngày nhắc không được sau hôm nay.']);
        $amount = preg_replace('/\D/', '', (string) ($data['promised_amount'] ?? ''));
        DB::table('debt_contacts')->insert([
            'unit_id' => $data['unit_id'], 'contacted_on' => $data['contacted_on'], 'channel' => $data['channel'] ?? null, 'note' => $data['note'] ?? null,
            'promised_date' => $data['promised_date'] ?? null, 'promised_amount' => $amount === '' ? null : (int) $amount,
            'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        // Chưa có người phụ trách thì người ghi nhận lần nhắc đầu tiên nhận luôn
        if (!DB::table('unit_collectors')->where('unit_id', $data['unit_id'])->whereNotNull('assignee_id')->exists()) {
            DB::table('unit_collectors')->updateOrInsert(['unit_id' => $data['unit_id']], ['assignee_id' => Auth::id(), 'updated_by' => Auth::id(), 'updated_at' => now(), 'created_at' => now()]);
        }
        $unit = DB::table('units')->where('id', $data['unit_id'])->value('unit_abbreviation');
        ActivityLog::record('cong_no', 'them', 'Nhắc nợ ' . $unit . (!empty($data['promised_date']) ? ', khách hẹn trả ' . \Carbon\Carbon::parse($data['promised_date'])->format('d/m/Y') : ''), null,
            array_filter(['Kênh' => [null, \App\Services\Accounting\DebtCollection::CHANNELS[$data['channel'] ?? ''] ?? null], 'Nội dung' => [null, $data['note'] ?? null],
                'Số tiền hẹn' => [null, $amount === '' ? null : (int) $amount]], fn ($p) => $p[1] !== null));

        return redirect()->route('finance.index', ['tab' => 'thu-no', 'loc' => $request->input('loc')])->with('bk_success', 'Đã ghi lần nhắc nợ ' . $unit . '.');
    }

    /** Kế toán (và Admin, không gồm SubAdmin) duyệt / bỏ duyệt đơn giá thấp / chiết khấu cao */
    /** Ghi nhớ CFO: thêm / sửa / đánh dấu xong / xóa (Admin, Kế toán) */
    public function saveNote(Request $request, ?int $id = null)
    {
        abort_unless(self::canEditNotes(), 403, 'Chỉ Kế toán / Admin / giám đốc được sửa ghi nhớ.');
        $note = $id ? \App\Models\CfoNote::findOrFail($id) : new \App\Models\CfoNote(['created_by' => Auth::id(), 'source' => 'web']);
        if ($request->has('toggle_done')) {
            $note->update(['done' => !$note->done]);

            return back()->with('bk_success', $note->done ? 'Đã đánh dấu xong.' : 'Đã mở lại ghi nhớ.');
        }
        $data = $request->validate(['content' => ['required', 'string', 'max:2000'], 'topic' => ['nullable', 'string', 'max:120'], 'remind_on' => ['nullable', 'date']],
            ['content.required' => 'Nhập nội dung ghi nhớ.']);
        $note->fill($data)->save();

        return redirect()->route('finance.index', ['tab' => 'ghi-nho'])->with('bk_success', $id ? 'Đã sửa ghi nhớ.' : 'Đã thêm ghi nhớ.');
    }

    public function deleteNote(int $id)
    {
        abort_unless(self::canEditNotes(), 403);
        \App\Models\CfoNote::findOrFail($id)->delete();

        return back()->with('bk_success', 'Đã xóa ghi nhớ.');
    }

    public function approvePrice(Request $request)
    {
        abort_unless(self::canEdit(), 403, 'Chỉ Kế toán / Admin được duyệt giá.');
        $data = $request->validate(['order_id' => ['required', 'integer', 'exists:orders,id'], 'note' => ['nullable', 'string', 'max:255'], 'undo' => ['nullable', 'boolean']]);
        if (!empty($data['undo'])) {
            DB::table('price_approvals')->where('order_id', $data['order_id'])->delete();
            ActivityLog::record('cong_no', 'xoa', 'Bỏ duyệt giá đơn #' . $data['order_id']);
            $msg = 'Đã bỏ duyệt giá đơn #' . $data['order_id'] . '.';
        } else {
            DB::table('price_approvals')->updateOrInsert(['order_id' => $data['order_id']],
                ['approved_by' => Auth::id(), 'approved_at' => now(), 'note' => $data['note'] ?? null, 'updated_at' => now(), 'created_at' => now()]);
            ActivityLog::record('cong_no', 'them', 'Duyệt giá đơn #' . $data['order_id'] . (!empty($data['note']) ? ': ' . $data['note'] : ''));
            $msg = 'Đã duyệt giá đơn #' . $data['order_id'] . '.';
        }

        return back()->with('bk_success', $msg);
    }
}
