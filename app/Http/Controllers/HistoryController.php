<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HistoryEdit;
use App\Models\User;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /** Lịch sử chỉnh sửa: lọc theo mã đơn, người sửa, nội dung, khoảng ngày */
    public function index(Request $request)
    {
        if ($request->query('tab') === 'ke-toan') {
            return $this->activity($request);
        }
        $query = HistoryEdit::query();
        if ($request->filled('order')) {
            $query->where('order_id', (int) $request->order);
        }
        if ($request->filled('user')) {
            $query->where('user_name', $request->user);
        }
        if ($request->filled('q')) {
            $query->where('history_action', 'LIKE', '%' . addcslashes(trim((string) $request->q), '%_\\') . '%');
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        $getAllHistory = $query->orderBy('id', 'DESC')->paginate(20)->withQueryString();

        // Người sửa: lịch sử lưu email, hiện kèm họ tên (profile) cho dễ đọc
        $users = User::with('profile:id,profile_firstname,profile_lastname')->get(['id', 'name', 'email', 'profile_id']);
        $people = $users->mapWithKeys(fn ($u) => [$u->email => $u->display_name]);
        $editors = HistoryEdit::query()->select('user_name')->distinct()->orderBy('user_name')->pluck('user_name')
            ->map(fn ($e) => ['email' => $e, 'name' => $people[$e] ?? $e]);
        $today = HistoryEdit::whereDate('created_at', now()->toDateString())->count();

        return view('pages.admin.history.index', compact('getAllHistory', 'people', 'editors', 'today'));
    }

    /** Tab "Kế toán & trợ lý": nhật ký thao tác (activity_logs) ở Báo cáo thu chi, Công nợ, Công nợ CT, Sổ VCB, Dữ liệu kế toán, Trợ lý AI */
    private function activity(Request $request)
    {
        $query = ActivityLog::query()
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', (int) $request->user))
            ->when($request->filled('area'), fn ($q) => $q->where('area', $request->area))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('summary', 'LIKE', '%' . addcslashes(trim((string) $request->q), '%_\\') . '%')
                ->orWhere('changes', 'LIKE', '%' . addcslashes(trim((string) $request->q), '%_\\') . '%')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));
        $logs = $query->orderByDesc('id')->paginate(30)->withQueryString();
        $actors = ActivityLog::query()->select('user_id', 'user_name')->whereNotNull('user_id')->distinct()->orderBy('user_name')->get();
        $today = ActivityLog::whereDate('created_at', now()->toDateString())->count();

        return view('pages.admin.history.activity', compact('logs', 'actors', 'today'));
    }
}
