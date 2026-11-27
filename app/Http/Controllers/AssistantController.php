<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AssistantConversation;
use App\Models\Unit;
use App\Services\Claude\AssistantAccess;
use App\Services\Claude\DepartmentAgentRunner;
use App\Services\Claude\SalesSuggestions;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Trang chat với trợ lý AI của phòng ban (Claude Managed Agents).
 * Mỗi cuộc trò chuyện được lưu trong DB và ứng với một session trên Claude.
 * Quyền dùng trợ lý: middleware assistant.access (theo users.department, Admin dùng tất cả).
 * Nhân viên chỉ thấy cuộc trò chuyện của mình; Admin xem được của mọi người (chỉ xem).
 */
class AssistantController extends Controller
{
    public function __construct(private DepartmentAgentRunner $runner)
    {
    }

    public function show(Request $request, string $department, ProfitCalculator $calculator)
    {
        $this->department($department);

        return view('pages.admin.assistant.index', [
            'department' => $department,
            'title' => config("claude.departments.{$department}.title"),
            // Các trợ lý người này được dùng (Admin thấy tất cả, hiện ô chuyển phòng khi có nhiều hơn 1)
            'assistants' => AssistantAccess::assistantsFor(Auth::user()),
            'modes' => $this->modes($department),
            // Gợi ý tên đơn vị + giá mặc định cho form báo giá / tính lợi nhuận
            'units' => Unit::orderBy('unit_abbreviation')->pluck('unit_abbreviation')->filter()->unique()->values(),
            'profitDefaults' => $calculator->defaults(),
            'contractUnits' => $department === 'sales' ? \App\Models\CustomerContract::join('units', 'units.id', '=', 'customer_contracts.unit_id')
                ->orderBy('units.unit_abbreviation')->pluck('units.unit_abbreviation')->filter()->unique()->values() : collect(),
            'canViewAll' => AssistantConversation::canViewAll(Auth::user()),
            'initialConversation' => $request->integer('c') ?: null,
        ]);
    }

    public function ask(Request $request, string $department)
    {
        $this->department($department);
        $user = Auth::user();

        $modes = config("claude.departments.{$department}.modes", []);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:6000'],
            'mode' => ['nullable', 'string', Rule::in(array_keys($modes))],
            'conversation_id' => ['nullable', 'integer'],
            'page_url' => ['nullable', 'string', 'max:1000'],
            'page_title' => ['nullable', 'string', 'max:300'],
            'page_filters' => ['nullable', 'array'],
        ]);
        // Khung chat nổi gửi kèm trang đang xem; server tự nhận trang từ đường dẫn
        $pageNote = !empty($data['page_url']) ? \App\Services\Claude\PageContext::describe($data['page_url'], $data['page_title'] ?? null) : null;
        // Trang Công nợ kế toán: bộ lọc đang chọn → nhớ đúng các đơn đang hiện để trợ lý đối chiếu giá hợp đồng
        if ($department === 'ke_toan' && isset($data['page_filters']) && \App\Services\Claude\PageContext::routeName($data['page_url'] ?? null) === 'accountant.index') {
            $filterNote = app(\App\Services\Accounting\ContractPriceAudit::class)->rememberFilter($user, $data['page_filters']);
            $pageNote = $filterNote ? trim(($pageNote ?? '') . ' — ' . $filterNote, ' —') : $pageNote;
        }

        if (!empty($data['conversation_id'])) {
            $conversation = $this->find($department, $data['conversation_id']);
            abort_unless($conversation->isOwnedBy($user), 403, 'Chỉ người tạo cuộc trò chuyện mới hỏi tiếp được.');
        } else {
            $conversation = AssistantConversation::create([
                'user_id' => $user->id,
                'department' => $department,
                'title' => $this->titleFrom($data['message']),
                'last_message_at' => now(),
            ]);
        }

        $modeKey = $data['mode'] ?? 'auto';
        $mode = $modes[$modeKey] ?? null;
        $modeNote = $mode && $mode['instruction'] ? "{$mode['label']}. {$mode['instruction']}" : null;

        // Câu trước còn đang trả lời: không gửi chồng (session chỉ xử lý từng lượt)
        if (Cache::has(self::turnKey($conversation))) {
            return response()->json(['message' => 'Trợ lý đang trả lời câu trước, vui lòng chờ.', 'conversation' => $this->summary($conversation)], 409);
        }

        $conversation->messages()->create(['role' => 'user', 'content' => $data['message'], 'mode' => $modeKey]);
        $conversation->update(['last_message_at' => now()]);

        try {
            if (!$conversation->claude_session_id) {
                $conversation->update(['claude_session_id' => $this->runner->startSession(
                    $department,
                    "{$user->name} - {$conversation->title}",
                    ['user_id' => $user->id, 'user_name' => $user->name, 'conversation_id' => $conversation->id],
                )]);
            }

            // Chỉ gửi câu hỏi rồi trả về ngay; trình duyệt gọi poll() tới khi có câu trả lời
            // (hosting cắt request giữ kết nối lâu, nên không chờ trả lời trong cùng request)
            $state = $this->runner->begin($conversation->claude_session_id, $data['message'], $modeNote, $pageNote);
        } catch (Throwable $e) {
            return $this->failTurn($department, $conversation, $e->getMessage());
        }

        Cache::put(self::turnKey($conversation), $state, now()->addSeconds((int) config('claude.reply_timeout', 240) + 300));

        return response()->json(['pending' => true, 'conversation' => $this->summary($conversation)], 202);
    }

    /**
     * Trình duyệt hỏi lại câu trả lời của lượt đang chờ (1–2 giây / lần). Mỗi lần đọc event mới trên Claude,
     * chạy tool agent đang chờ (tra cứu DB). Xong thì lưu câu trả lời và trả về như ask() cũ.
     */
    public function poll(string $department, int $conversation)
    {
        $this->department($department);
        $user = Auth::user();
        $conversation = $this->find($department, $conversation);
        abort_unless($conversation->isOwnedBy($user), 403);

        $key = self::turnKey($conversation);
        // Hai lần poll chồng nhau (mạng chậm) không được chạy tool 2 lần
        $lock = Cache::lock($key . ':lock', 60);
        if (!$lock->get()) {
            return response()->json(['pending' => true]);
        }

        try {
            $state = Cache::get($key);
            if (!$state) {
                // Không còn lượt nào đang chờ (đã xong ở lần poll khác / hết hạn): trình duyệt tải lại tin nhắn
                return response()->json(['done' => true, 'reply' => null, 'conversation' => $this->summary($conversation)]);
            }

            set_time_limit(90);
            try {
                $result = $this->runner->poll($department, $conversation->claude_session_id, $state, $user);
            } catch (Throwable $e) {
                // Lỗi mạng tạm thời: giữ lượt, lần poll sau thử lại (quá giờ thì runner tự dừng)
                Log::warning('Assistant poll failed', ['conversation' => $conversation->id, 'error' => $e->getMessage()]);
                Cache::put($key, $state, now()->addSeconds((int) config('claude.reply_timeout', 240) + 300));

                return response()->json(['pending' => true]);
            }

            if (!$result['done']) {
                Cache::put($key, $state, now()->addSeconds((int) config('claude.reply_timeout', 240) + 300));

                return response()->json(['pending' => true]);
            }

            Cache::forget($key);
            if (isset($result['error'])) {
                return $this->failTurn($department, $conversation, $result['error']);
            }

            $tools = collect($state['tools'])->pluck('name')->unique()->values()->all();
            $conversation->messages()->create(['role' => 'assistant', 'content' => $result['reply'], 'tools' => $tools]);
            $conversation->update(['last_message_at' => now()]);

            return response()->json([
                'done' => true,
                'reply' => $result['reply'],
                'tools' => $tools,
                'conversation' => $this->summary($conversation),
            ]);
        } finally {
            $lock->release();
        }
    }

    private function failTurn(string $department, AssistantConversation $conversation, string $error)
    {
        Log::error('Assistant ask failed', ['department' => $department, 'conversation' => $conversation->id, 'error' => $error]);
        $message = 'Trợ lý đang gặp lỗi: ' . $error;
        $conversation->messages()->create(['role' => 'error', 'content' => $message]);

        return response()->json(['message' => $message, 'conversation' => $this->summary($conversation)], 502);
    }

    private static function turnKey(AssistantConversation $c): string
    {
        return "assistant:turn:{$c->id}";
    }

    /** Danh sách cuộc trò chuyện gần đây. scope=all chỉ dành cho Admin. */
    public function conversations(Request $request, string $department)
    {
        $this->department($department);
        $user = Auth::user();
        $all = $request->query('scope') === 'all' && AssistantConversation::canViewAll($user);
        $search = trim((string) $request->query('q', ''));

        $items = AssistantConversation::query()
            // Admin xem "Tất cả nhân viên" thấy cả cuộc trò chuyện nhân viên đã xóa (đánh dấu Đã xóa)
            ->when($all, fn ($q) => $q->withTrashed()->with('deleter:id,name,profile_id', 'deleter.profile:id,profile_firstname,profile_lastname'))
            ->where('department', $department)
            ->when(!$all, fn ($q) => $q->where('user_id', $user->id))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%'))
            ->with('user:id,name,profile_id', 'user.profile:id,profile_firstname,profile_lastname')
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();

        return response()->json([
            'items' => $items->map(fn ($c) => $this->summary($c) + [
                // Họ tên trong profile (thay cho tên tài khoản Admin / Sale…)
                'user_name' => $c->user?->display_name ?? 'Không rõ',
                'user_id' => $c->user_id,
                'is_mine' => $c->isOwnedBy($user),
            ]),
            // Admin xóa là xóa hẳn; nhân viên xóa chỉ ẩn khỏi danh sách của mình
            'delete_mode' => AssistantConversation::canViewAll($user) ? 'hard' : 'soft',
        ]);
    }

    public function messages(string $department, int $conversation)
    {
        $this->department($department);
        $user = Auth::user();
        $conversation = $this->find($department, $conversation, AssistantConversation::canViewAll($user));
        abort_unless($conversation->canView($user), 403);

        $modes = config("claude.departments.{$department}.modes", []);

        return response()->json([
            'conversation' => $this->summary($conversation) + [
                'user_name' => $conversation->user?->display_name ?? 'Không rõ',
                'readonly' => !$conversation->isOwnedBy($user) || $conversation->trashed(),
                // Câu hỏi cuối còn đang chờ trả lời (tải lại trang giữa chừng): trình duyệt hỏi lại tiếp
                'pending' => $conversation->isOwnedBy($user) && Cache::has(self::turnKey($conversation)),
            ],
            'messages' => $conversation->messages->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
                'mode' => $m->mode,
                'mode_label' => $m->mode && $m->mode !== 'auto' ? ($modes[$m->mode]['label'] ?? $m->mode) : null,
                'tools' => $m->tools ?? [],
                'created_at' => $m->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function rename(Request $request, string $department, int $conversation)
    {
        $this->department($department);
        $conversation = $this->find($department, $conversation);
        abort_unless($conversation->isOwnedBy(Auth::user()), 403);

        $data = $request->validate(['title' => ['required', 'string', 'max:200']]);
        $conversation->update(['title' => trim($data['title'])]);

        return response()->json(['conversation' => $this->summary($conversation)]);
    }

    public function destroy(string $department, int $conversation)
    {
        $this->department($department);
        $user = Auth::user();
        $isAdmin = AssistantConversation::canViewAll($user);
        $conversation = $this->find($department, $conversation, $isAdmin);
        abort_unless($conversation->canView($user), 403);

        $owner = $conversation->user?->display_name ?? 'Không rõ';
        if ($isAdmin) {
            $conversation->purge();
            ActivityLog::record('tro_ly_ai', 'xoa', 'Xóa hẳn cuộc trò chuyện "' . $conversation->title . '" của ' . $owner . ' (trợ lý ' . $department . ')');
        } else {
            $conversation->hide($user);
            ActivityLog::record('tro_ly_ai', 'xoa', 'Xóa cuộc trò chuyện "' . $conversation->title . '" (trợ lý ' . $department . ', Admin vẫn xem được)', $conversation);
        }

        return response()->json(['success' => true]);
    }

    /** Chức năng của khung chat; trợ lý Sales có gợi ý câu hỏi sinh từ dữ liệu trong ngày */
    private function modes(string $department): array
    {
        $modes = config("claude.departments.{$department}.modes", []);

        return $department === 'sales' ? app(SalesSuggestions::class)->apply($modes) : $modes;
    }

    private function department(string $department): void
    {
        abort_unless(AssistantAccess::exists($department), 404);
    }

    private function find(string $department, int $id, bool $withTrashed = false): AssistantConversation
    {
        return AssistantConversation::where('department', $department)->when($withTrashed, fn ($q) => $q->withTrashed())->findOrFail($id);
    }

    private function titleFrom(string $message): string
    {
        $firstLine = trim(strtok(trim($message), "\n") ?: $message);

        return Str::limit($firstLine, 80, '…');
    }

    private function summary(AssistantConversation $c): array
    {
        return [
            'id' => $c->id,
            'title' => $c->title,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'deleted_at' => $c->deleted_at?->toIso8601String(),
            'deleted_by' => $c->deleted_at ? ($c->deleter?->display_name ?? null) : null,
        ];
    }
}
