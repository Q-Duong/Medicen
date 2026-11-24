<?php

namespace App\Http\Middleware;

use App\Services\Activity\ActivityRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Nhật ký hoạt động cho mọi trang (config/activity.php):
 *  - POST / PUT / PATCH / DELETE thành công → một dòng activity_logs: trang, việc, đối tượng, các thay đổi cũ → mới (ActivityRecorder).
 *    Trang đã tự ghi nhật ký chi tiết (ActivityLog::record) thì không ghi thêm. Route chỉ đọc (lọc, tìm, gợi ý…) bỏ qua.
 *  - GET tải / xuất file → một dòng "Tải …".
 *  - GET mở trang chính → activity_visits (để tính giờ vào / ra, thời gian làm trên web).
 * Lỗi khi ghi không bao giờ chặn thao tác của người dùng.
 */
class RecordActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $recorder = app(ActivityRecorder::class);
        $recorder->reset();
        $response = $next($request);

        try {
            $user = Auth::user();
            $route = $request->route()?->getName();
            if ($user && $route) {
                $method = $request->method();
                if ($method !== 'GET' && $method !== 'HEAD') {
                    $this->recordWrite($request, $response, $route, $recorder);
                } elseif ($this->matches($route, config('activity.download_routes', []))) {
                    if ($response->getStatusCode() < 400) {
                        $this->recordWrite($request, $response, $route, $recorder, 'tai_ve');
                    }
                } else {
                    $this->recordVisit($request, $response, $route, $user->id);
                }
            }
        } catch (Throwable $e) {
            Log::warning('Không ghi được nhật ký hoạt động', ['error' => $e->getMessage()]);
        }

        return $response;
    }

    private function recordWrite(Request $request, Response $response, string $route, ActivityRecorder $recorder, ?string $action = null): void
    {
        if ($this->matches($route, config('activity.read_routes', [])) || $recorder->hasExplicit()) {
            return;
        }
        $status = $response->getStatusCode();
        // Lỗi / dữ liệu không hợp lệ (422, hoặc quay lại form kèm lỗi) thì không ghi
        // (chỉ tính lỗi vừa flash trong request này, không tính lỗi còn lại từ lần trước)
        if ($status >= 400 || ($status >= 300 && $status < 400 && $request->hasSession()
            && in_array('errors', (array) $request->session()->get('_flash.new', []), true))) {
            return;
        }
        [$area, $areaLabel] = self::area($route);
        $verb = $action ?? $this->verb($route, $request->method());
        // (tên route có dấu chấm nên không dùng config('activity.labels.' . $route))
        $label = config('activity.labels', [])[$route] ?? (self::VERBS[$verb] ?? 'Thao tác') . ' · ' . $areaLabel;

        // Đối tượng: từ thay đổi đã gom (vd đơn vừa thêm) hoặc tham số route ({id}, {contract}…)
        $subjectMap = $recorder->subjects();
        $subjects = array_keys($subjectMap);
        if (!$subjects) {
            foreach ((array) $request->route()?->parameters() as $k => $v) {
                $id = is_object($v) && method_exists($v, 'getKey') ? $v->getKey() : (is_scalar($v) ? $v : null);
                if ($id !== null && $id !== '') {
                    $subjects[] = '#' . $id;
                }
            }
        }
        $names = array_values(array_unique(array_map(fn ($s) => preg_replace('/ \(.*\)$/u', '', $s), $subjects)));
        // "Sửa đơn" + "Đơn #2350" → "Sửa đơn #2350"
        if ($names && Str::contains(mb_strtolower($label), 'đơn') && collect($names)->every(fn ($n) => str_starts_with($n, 'Đơn #'))) {
            $names = array_map(fn ($n) => mb_substr($n, 4), $names);
            $summary = $label . ' ' . implode(', ', array_slice($names, 0, 6));
        } else {
            // "Sửa đơn vị" + "Đơn vị PK 115" → "Sửa đơn vị PK 115"
            $noun = mb_strtolower(trim(Str::after($label, ' ')));
            if ($names && $noun !== '' && collect($names)->every(fn ($n) => str_starts_with(mb_strtolower($n), $noun . ' '))) {
                $summary = $label . ' ' . implode(', ', array_map(fn ($n) => mb_substr($n, mb_strlen($noun) + 1), array_slice($names, 0, 6)));
            } else {
                $summary = $label . ($names ? ': ' . implode(', ', array_slice($names, 0, 6)) : '');
            }
        }

        ActivityRecorder::write([
            'area' => $area,
            'action' => $verb,
            'route' => mb_substr($route, 0, 80),
            'summary' => mb_substr($summary, 0, 500),
            'changes' => $recorder->changes() ?: null,
        ]);
    }

    private function recordVisit(Request $request, Response $response, string $route, int $userId): void
    {
        if ($request->ajax() || $request->expectsJson() || $response->getStatusCode() !== 200
            || $this->matches($route, config('activity.skip_visit_routes', []))
            || !str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return;
        }
        if (!Cache::add('activity:visit:' . $userId . ':' . $route, 1, (int) config('activity.visit_dedupe_seconds', 120))) {
            return;
        }
        DB::table('activity_visits')->insert([
            'user_id' => $userId,
            'route' => mb_substr($route, 0, 80),
            'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 190),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    public const VERBS = ['them' => 'Thêm', 'sua' => 'Sửa', 'xoa' => 'Xóa', 'tai_ve' => 'Tải', 'thao_tac' => 'Thao tác'];

    private function verb(string $route, string $method): string
    {
        $last = Str::afterLast($route, '.');

        return match (true) {
            $method === 'DELETE' || Str::contains($last, ['destroy', 'delete']) => 'xoa',
            Str::contains($last, ['store', 'create']) => 'them',
            $method === 'PATCH' || $method === 'PUT' || Str::contains($last, ['update', 'save']) => 'sua',
            default => 'thao_tac',
        };
    }

    /** [mã trang, tên trang] theo tiền tố route dài nhất khớp */
    public static function area(string $route): array
    {
        $best = null;
        foreach (config('activity.areas', []) as $prefix => $area) {
            if ((str_ends_with($prefix, '.') ? str_starts_with($route, $prefix) : $route === $prefix) && strlen($prefix) > strlen((string) $best)) {
                $best = $prefix;
            }
        }

        return $best ? config('activity.areas')[$best] : ['khac', 'Khác'];
    }

    private function matches(string $route, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if (Str::is($p, $route)) {
                return true;
            }
        }

        return false;
    }
}
