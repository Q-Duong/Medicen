<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Claude\AssistantAccess;
use Closure;
use Illuminate\Http\Request;

/**
 * Chặn ở server theo nhóm trang (config/access.php), để dán link trực tiếp cũng không vào được trang
 * mà sidebar không hiện cho tài khoản đó. Dùng: ->middleware('access:orders') hoặc 'access:debt_sales,debt_accountant'
 * (khớp một nhóm là được). Admin luôn qua.
 */
class PageAccess
{
    public function handle(Request $request, Closure $next, string ...$groups)
    {
        if (self::allows($request->user(), $groups)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Tài khoản của bạn không có quyền dùng chức năng này.');
        }

        return redirect()->route('403');
    }

    public static function allows(?User $user, array $groups): bool
    {
        if (!$user) {
            return false;
        }
        if (AssistantAccess::isFullAdmin($user)) {
            return true;
        }
        if (AssistantAccess::isSubAdmin($user)) {
            // SubAdmin loại Admin: route nào được vào do SubAdminScope (config access.subadmin_routes) quyết định
            return true;
        }
        foreach ($groups as $group) {
            $rule = config("access.{$group}");
            if (!$rule) {
                continue;
            }
            if (in_array($user->department, $rule['departments'] ?? [], true)
                || in_array($user->name, $rule['names'] ?? [], true)
                || in_array((int) $user->role, $rule['roles'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }
}
