<?php

namespace App\Http\Controllers;

use App\Models\AssistantConversation;
use App\Models\Unit;
use App\Services\Claude\AssistantAccess;
use App\Services\Claude\DepartmentAgentRunner;
use App\Services\Claude\SalesSuggestions;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

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

        $conversation->messages()->create(['role' => 'user', 'content' => $data['message'], 'mode' => $modeKey]);
        $conversation->update(['last_message_at' => now()]);

        // Agent có thể cần vài chục giây khi phải tra cứu nhiều lần
        set_time_limit((int) config('claude.reply_timeout', 240) + 30);

        try {
            if (!$conversation->claude_session_id) {
                $conversation->update(['claude_session_id' => $this->runner->startSession(
                    $department,
                    "{$user->name} - {$conversation->title}",
                    ['user_id' => $user->id, 'user_name' => $user->name, 'conversation_id' => $conversation->id],
                )]);
            }

            $result = $this->runner->ask($department, $conversation->claude_session_id, $data['message'], $user, $modeNote);
        } catch (Throwable $e) {
            Log::error('Assistant ask failed', ['department' => $department, 'conversation' => $conversation->id, 'error' => $e->getMessage()]);
            $message = 'Trợ lý đang gặp lỗi: ' . $e->getMessage();
            $conversation->messages()->create(['role' => 'error', 'content' => $message]);

            return response()->json(['message' => $message, 'conversation' => $this->summary($conversation)], 502);
        }

        $tools = collect($result['tools'])->pluck('name')->unique()->values()->all();
        $conversation->messages()->create(['role' => 'assistant', 'content' => $result['reply'], 'tools' => $tools]);
        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'reply' => $result['reply'],
            'tools' => $tools,
            'conversation' => $this->summary($conversation),
        ]);
    }

    /** Danh sách cuộc trò chuyện gần đây. scope=all chỉ dành cho Admin. */
    public function conversations(Request $request, string $department)
    {
        $this->department($department);
        $user = Auth::user();
        $all = $request->query('scope') === 'all' && AssistantConversation::canViewAll($user);
        $search = trim((string) $request->query('q', ''));

        $items = AssistantConversation::query()
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
                'is_mine' => $c->isOwnedBy($user),
            ]),
        ]);
    }

    public function messages(string $department, int $conversation)
    {
        $this->department($department);
        $user = Auth::user();
        $conversation = $this->find($department, $conversation);
        abort_unless($conversation->canView($user), 403);

        $modes = config("claude.departments.{$department}.modes", []);

        return response()->json([
            'conversation' => $this->summary($conversation) + [
                'user_name' => $conversation->user?->display_name ?? 'Không rõ',
                'readonly' => !$conversation->isOwnedBy($user),
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
        $conversation = $this->find($department, $conversation);
        abort_unless($conversation->canView(Auth::user()), 403);

        $conversation->purge();

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

    private function find(string $department, int $id): AssistantConversation
    {
        return AssistantConversation::where('department', $department)->findOrFail($id);
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
        ];
    }
}
