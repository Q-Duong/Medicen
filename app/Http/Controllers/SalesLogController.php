<?php

namespace App\Http\Controllers;

use App\Models\SalesDigest;
use App\Models\SalesFollowup;
use App\Models\SalesLog;
use App\Services\Sales\SalesDay;
use App\Services\Sales\SalesLogReader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kinh doanh → Nhật ký trao đổi khách: Sales dán đoạn chat Zalo / ảnh chụp màn hình / ghi chú cuộc gọi, AI tóm tắt và tách việc cần làm;
 * việc đến hạn nhắc trên chuông; 17:00 email nhắc việc (lệnh sales:digest); giám đốc hỏi Trợ lý AI (tool nhat_ky_sales). Quyền: config access.sales_log.
 */
class SalesLogController extends Controller
{
    public const IMG_DIR = 'sales-logs';

    public function index(Request $request)
    {
        $day = $this->day($request->query('ngay'));
        $userId = (int) $request->query('nguoi') ?: null;
        $logs = SalesLog::with(['followups', 'user:id,name', 'unit:id,unit_abbreviation,unit_name'])
            ->whereDate('happened_on', $day)->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('created_at')->get();
        $today = Carbon::today();
        $open = SalesFollowup::open()->with('user:id,name', 'log:id,happened_on')->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderByRaw('due_on IS NULL')->orderBy('due_on')->orderBy('id')->get();
        $groups = [
            'qua_han' => ['Quá hạn', $open->filter(fn ($f) => $f->due_on && $f->due_on->lt($today))],
            'hom_nay' => ['Hôm nay', $open->filter(fn ($f) => $f->due_on && $f->due_on->isSameDay($today))],
            'sap_toi' => ['7 ngày tới', $open->filter(fn ($f) => $f->due_on && $f->due_on->gt($today) && $f->due_on->lte($today->copy()->addDays(7)))],
            'sau_nua' => ['Sau đó / chưa đặt hạn', $open->filter(fn ($f) => !$f->due_on || $f->due_on->gt($today->copy()->addDays(7)))],
        ];
        $doneToday = SalesFollowup::whereNotNull('done_at')->where('done_at', '>=', $today)->when($userId, fn ($q) => $q->where('user_id', $userId))->latest('done_at')->limit(20)->get();
        $units = DB::table('units')->orderBy('unit_abbreviation')->get(['id', 'unit_abbreviation', 'unit_name']);

        return view('pages.admin.sales_logs.index', [
            'day' => $day, 'logs' => $logs, 'groups' => $groups, 'doneToday' => $doneToday, 'units' => $units,
            'users' => SalesDay::salesUsers(), 'userId' => $userId, 'digest' => SalesDigest::whereDate('day', $day)->first(),
            'canEdit' => \App\Http\Middleware\PageAccess::allows(Auth::user(), ['sales_log_edit']),
        ]);
    }

    public function store(Request $request, SalesLogReader $reader)
    {
        $data = $request->validate([
            'kind' => ['required', 'in:' . implode(',', array_keys(SalesLog::KINDS))],
            'raw_text' => ['nullable', 'string', 'max:30000'],
            'images' => ['nullable', 'array', 'max:' . SalesLogReader::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'happened_on' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'images.max' => 'Tối đa ' . SalesLogReader::MAX_IMAGES . ' ảnh mỗi lần.',
            'images.*.image' => 'File đính kèm phải là ảnh chụp màn hình.',
            'images.*.max' => 'Mỗi ảnh tối đa 8 MB.',
            'happened_on.before_or_equal' => 'Không ghi nhật ký cho ngày chưa tới.',
        ]);
        $text = trim((string) ($data['raw_text'] ?? ''));
        if ($text === '' && !$request->hasFile('images')) {
            return back()->withInput()->withErrors(['raw_text' => 'Dán đoạn chat, ghi chú cuộc gọi hoặc chọn ảnh chụp màn hình.']);
        }
        @set_time_limit(180);
        $day = isset($data['happened_on']) ? Carbon::parse($data['happened_on']) : Carbon::today();
        $images = [];
        foreach ((array) $request->file('images', []) as $file) {
            $name = date('Ym') . '-' . Str::lower(Str::random(16)) . '.' . strtolower($file->guessExtension() ?: 'jpg');
            Storage::disk('local')->putFileAs(self::IMG_DIR, $file, $name);
            $images[] = $name;
        }
        $log = new SalesLog(['user_id' => Auth::id(), 'happened_on' => $day, 'kind' => $data['kind'], 'unit_id' => $data['unit_id'] ?? null,
            'raw_text' => $text !== '' ? SalesLogReader::maskContacts($text) : null, 'images' => $images ?: null]);
        $ai = null;
        try {
            $ai = $reader->read($text, array_map(fn ($n) => Storage::disk('local')->path(self::IMG_DIR . '/' . $n), $images), $data['kind'], $day);
        } catch (Throwable $e) {
            Log::warning('AI đọc nhật ký trao đổi khách lỗi', ['error' => $e->getMessage()]);
            $log->ai_status = 'failed';
            $log->ai_error = mb_substr($e->getMessage(), 0, 250);
        }
        if ($ai) {
            $log->fill(['kind' => $ai['kind'], 'customer' => $ai['customer'], 'unit_id' => $log->unit_id ?: $ai['unit_id'], 'summary' => $ai['summary'],
                'important' => $ai['important'], 'ai_status' => $ai['readable'] ? 'done' : 'failed', 'ai_error' => $ai['readable'] ? null : 'AI không đọc rõ nội dung']);
            if ($ai['important'] && $ai['important_reason']) {
                $log->summary = trim($log->summary . "\n⚠ " . $ai['important_reason']);
            }
        }
        $log->save();
        foreach ($ai['followups'] ?? [] as $f) {
            $log->followups()->create(['user_id' => Auth::id(), 'unit_id' => $log->unit_id, 'customer' => $log->customer, 'title' => $f['title'], 'due_on' => $f['due_on']]);
        }
        $n = count($ai['followups'] ?? []);
        $msg = $log->ai_status === 'done' ? 'Đã lưu nhật ký' . ($n ? ", AI tách {$n} việc cần làm." : '.') : 'Đã lưu nhật ký, nhưng AI chưa đọc được: ' . e($log->ai_error) . '. Có thể tự ghi tóm tắt / thêm việc.';

        return redirect()->route('sales_logs.index', ['ngay' => $day->toDateString()])->with($log->ai_status === 'done' ? 'success' : 'error', $msg)->with('sl_new', $log->id);
    }

    public function update(Request $request, int $id)
    {
        $log = SalesLog::findOrFail($id);
        $data = $request->validate(['customer' => ['nullable', 'string', 'max:191'], 'summary' => ['nullable', 'string', 'max:3000'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'], 'kind' => ['required', 'in:' . implode(',', array_keys(SalesLog::KINDS))], 'important' => ['nullable', 'boolean']]);
        $log->update(['customer' => $data['customer'] ?? null, 'summary' => isset($data['summary']) ? SalesLogReader::maskContacts($data['summary']) : null,
            'unit_id' => $data['unit_id'] ?? null, 'kind' => $data['kind'], 'important' => (bool) ($data['important'] ?? false)]);

        return back()->with('success', 'Đã sửa nhật ký.');
    }

    public function destroy(int $id)
    {
        $log = SalesLog::findOrFail($id);
        foreach ((array) $log->images as $img) {
            Storage::disk('local')->delete(self::IMG_DIR . '/' . $img);
        }
        $log->followups()->whereNull('done_at')->delete();
        $log->delete();

        return back()->with('success', 'Đã xóa nhật ký.');
    }

    public function image(string $file)
    {
        abort_unless(preg_match('/^\d{6}-[a-z0-9]{16}\.(jpg|jpeg|png|webp)$/', $file), 404);
        $path = self::IMG_DIR . '/' . $file;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, max-age=86400']);
    }

    public function storeFollowup(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:500'], 'due_on' => ['nullable', 'date'], 'customer' => ['nullable', 'string', 'max:191'],
            'sales_log_id' => ['nullable', 'integer', 'exists:sales_logs,id']], ['title.required' => 'Nhập việc cần làm.']);
        $log = !empty($data['sales_log_id']) ? SalesLog::find($data['sales_log_id']) : null;
        SalesFollowup::create(['title' => trim($data['title']), 'due_on' => $data['due_on'] ?? null, 'user_id' => Auth::id(),
            'customer' => $data['customer'] ?? $log?->customer, 'unit_id' => $log?->unit_id, 'sales_log_id' => $log?->id]);

        return back()->with('success', 'Đã thêm việc cần làm.');
    }

    public function updateFollowup(Request $request, int $id)
    {
        $f = SalesFollowup::findOrFail($id);
        if ($request->has('toggle')) {
            $f->update($f->done_at ? ['done_at' => null, 'done_by' => null] : ['done_at' => now(), 'done_by' => Auth::id()]);

            return $request->expectsJson() ? response()->json(['done' => (bool) $f->done_at]) : back();
        }
        $data = $request->validate(['title' => ['required', 'string', 'max:500'], 'due_on' => ['nullable', 'date']]);
        $f->update(['title' => trim($data['title']), 'due_on' => $data['due_on'] ?? null]);

        return back()->with('success', 'Đã sửa việc.');
    }

    public function destroyFollowup(int $id)
    {
        SalesFollowup::findOrFail($id)->delete();

        return back()->with('success', 'Đã xóa việc.');
    }

    /** Tóm tắt ngày (AI) ngay trên trang */
    public function digest(Request $request, SalesDay $sales)
    {
        @set_time_limit(180);
        $day = $this->day($request->input('ngay'));
        try {
            $d = $sales->summarize($day);
        } catch (Throwable $e) {
            Log::warning('Tóm tắt ngày Sales lỗi', ['error' => $e->getMessage()]);

            return back()->with('error', 'AI chưa tóm tắt được, thử lại sau.');
        }

        return redirect()->route('sales_logs.index', ['ngay' => $day->toDateString()])->with($d ? 'success' : 'error', $d ? 'Đã tóm tắt ngày.' : 'Ngày này chưa có gì để tóm tắt.');
    }

    private function day($v): Carbon
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v)->min(Carbon::today()) : Carbon::today();
    }
}
