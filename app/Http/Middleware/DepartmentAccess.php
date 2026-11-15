<?php

namespace App\Http\Middleware;

use App\Services\Claude\AssistantAccess;
use Closure;
use Illuminate\Http\Request;

/**
 * Cho phép Admin và các tài khoản thuộc phòng ban được liệt kê.
 * Dùng: ->middleware('department:ky_thuat,sales')
 */
class DepartmentAccess
{
    public function handle(Request $request, Closure $next, string ...$departments)
    {
        $user = $request->user();
        if ($user && (AssistantAccess::isAdmin($user) || in_array($user->department, $departments, true))) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Tài khoản của bạn không có quyền dùng chức năng này.');
        }

        return redirect()->route('403');
    }
}
