<?php

namespace App\Http\Middleware;

use App\Services\Claude\AssistantAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Giới hạn tài khoản SubAdmin loại Admin (role 0/1, tên 'SubAdmin'): chỉ được vào các route trong
 * config('access.subadmin_routes') (Thống kê doanh thu, Công nợ Sales, Báo cáo thu chi chỉ xem, Trợ lý Kế toán…).
 * Chạy cho mọi route web; chỉ chặn route cần đăng nhập (auth / schedules.login), trang công khai không ảnh hưởng.
 */
class SubAdminScope
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $route = $request->route();
        if (!$user || !$route || !AssistantAccess::isSubAdmin($user)) {
            return $next($request);
        }
        $needsLogin = array_intersect(array_filter($route->gatherMiddleware(), 'is_string'), ['auth', 'schedules.login']);
        if (!$needsLogin || self::allowsRoute($route->getName())) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Tài khoản của bạn không có quyền dùng chức năng này.');
        }

        return redirect()->route('403');
    }

    public static function allowsRoute(?string $name): bool
    {
        return $name !== null && Str::is(config('access.subadmin_routes', []), $name);
    }
}
