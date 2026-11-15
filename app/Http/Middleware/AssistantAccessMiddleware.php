<?php

namespace App\Http\Middleware;

use App\Services\Claude\AssistantAccess;
use Closure;
use Illuminate\Http\Request;

/** Chặn truy cập trợ lý AI của phòng ban khác (kiểm tra cả trang, câu hỏi và lịch sử). */
class AssistantAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $assistant = (string) $request->route('department');
        abort_unless(AssistantAccess::exists($assistant), 404);

        if (!AssistantAccess::can($request->user(), $assistant)) {
            if ($request->expectsJson()) {
                abort(403, 'Tài khoản của bạn không được dùng trợ lý này. Liên hệ Admin để được cấp quyền.');
            }

            return redirect()->route('403');
        }

        return $next($request);
    }
}
