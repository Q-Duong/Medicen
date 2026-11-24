<?php

namespace App\Http\Controllers;

use App\Services\Activity\ActivityTimeline;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Nhật ký hoạt động — CHỈ Admin đầy đủ quyền (SubAdmin không vào được; trên trợ lý SubAdmin vẫn hỏi được nhân viên nhưng không hỏi được Admin).
 * Gộp cả trang "Lịch sử chỉnh sửa" cũ: ô Mã đơn hiện toàn bộ lịch sử một đơn (nhật ký mới + history_edits).
 * Dữ liệu: App\Services\Activity\ActivityTimeline (cùng nguồn với tool trợ lý hoat_dong_nhan_vien).
 */
class ActivityController extends Controller
{
    public function index(Request $request, ActivityTimeline $timeline)
    {
        abort_unless(\App\Services\Claude\AssistantAccess::isFullAdmin(Auth::user()), 403);
        $order = (int) ltrim((string) $request->query('don'), '#') ?: null;
        $parse = fn ($v) => $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v) : null;
        $from = ($parse($request->query('tu')) ?? Carbon::today())->startOfDay();
        $to = ($parse($request->query('den')) ?? $from->copy())->endOfDay();
        if ($to->lt($from)) {
            $to = $from->copy()->endOfDay();
        }
        if ($from->diffInDays($to) > 31) {
            $to = $from->copy()->addDays(31)->endOfDay();
        }
        // Xem theo mã đơn: toàn bộ lịch sử đơn, không giới hạn ngày
        if ($order) {
            $from = Carbon::create(2015, 1, 1);
            $to = Carbon::now()->endOfDay();
        }
        $people = $timeline->people(Auth::user());
        $userId = (int) $request->query('nguoi') ?: null;
        if ($userId && !$people->contains('id', $userId)) {
            $userId = null; // tài khoản bị ẩn với người xem
        }
        $result = $timeline->build(Auth::user(), $from, $to, [
            'user_id' => $userId, 'nguoi' => $request->query('q'), 'trang' => $request->query('trang'), 'loai' => $request->query('loai'), 'don' => $order,
        ], 400);
        // Theo mã đơn: một bảng theo thời gian, có cột người làm
        $orderRows = $order ? collect($result['nguoi'])->flatMap(fn ($p) => collect($p['dong_thoi_gian'] ?? [])->map(fn ($it) => $it + ['nguoi' => $p['nguoi']]))
            ->sortBy('ts')->values() : null;
        $areas = collect(config('activity.areas'))->mapWithKeys(fn ($a) => [$a[0] => $a[1]])->unique()
            ->merge(['dang_nhap' => 'Đăng nhập', 'bao_cao_thu_chi' => 'Báo cáo thu chi'])->sort();

        return view('pages.admin.activity.index', [
            'result' => $result, 'people' => $people, 'areas' => $areas, 'types' => ActivityTimeline::TYPES,
            'from' => $from, 'to' => $to, 'userId' => $userId, 'order' => $order, 'orderRows' => $orderRows,
        ]);
    }
}
