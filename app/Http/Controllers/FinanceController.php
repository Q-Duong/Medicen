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
    public const TABS = ['tien-ket' => 'Tiền kẹt & tuổi nợ', 'han-muc' => 'Hạn mức tín dụng', 'lai-uoc-tinh' => 'Lãi ước tính', 'dong-tien' => 'Dòng tiền 13 tuần', 'ke-hoach' => 'Kế hoạch năm'];

    /** Sửa hạn mức / kế hoạch: Admin (trừ SubAdmin) và Kế toán */
    public static function canEdit(): bool
    {
        $u = Auth::user();

        return AssistantAccess::isFullAdmin($u) || (int) ($u->role ?? -1) === 3 || ($u->department ?? null) === 'ke_toan';
    }

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'tien-ket';
        $data = ['tab' => $tab, 'tabs' => self::TABS, 'canEdit' => self::canEdit()];

        if ($tab === 'tien-ket') {
            $data['r'] = app(Receivables::class)->summary();
        } elseif ($tab === 'han-muc') {
            $data['credits'] = app(CreditPolicy::class)->all();
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
}
