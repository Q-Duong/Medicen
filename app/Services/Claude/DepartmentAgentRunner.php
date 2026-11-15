<?php

namespace App\Services\Claude;

use Anthropic\Beta\Sessions\BetaManagedAgentsAgentParams;
use Anthropic\Beta\Sessions\Events\ManagedAgentsUserCustomToolResultEventParams;
use Anthropic\Beta\Sessions\Events\ManagedAgentsUserMessageEventParams;
use Anthropic\Beta\Sessions\Events\ManagedAgentsUserToolConfirmationEventParams;
use Anthropic\Client;
use App\Models\User;
use App\Services\Claude\Tools\DepartmentTools;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Gửi câu hỏi tới agent của phòng ban, tự xử lý các lần agent gọi tool
 * (chạy trên DB Medicen), trả về câu trả lời cuối cùng.
 */
class DepartmentAgentRunner
{
    public function __construct(private Client $client, private AgentRegistry $registry)
    {
    }

    /**
     * Mỗi cuộc trò chuyện là một session trên Claude (giữ ngữ cảnh nhiều lượt).
     */
    public function startSession(string $department, string $title, array $metadata = []): string
    {
        $ids = $this->registry->agentFor($department);

        $session = $this->client->beta->sessions->create(
            agent: BetaManagedAgentsAgentParams::with(id: $ids['agent_id'], version: $ids['agent_version']),
            environmentID: $ids['environment_id'],
            title: mb_substr($title, 0, 200),
            metadata: array_map('strval', $metadata + ['department' => $department]),
        );

        return $session->id;
    }

    /** Xóa session trên Claude (khi xóa cuộc trò chuyện hoặc hết hạn lưu). */
    public function deleteSession(string $sessionId): void
    {
        $this->client->beta->sessions->delete($sessionId);
    }

    /**
     * @param string|null $modeNote Chức năng người dùng đang chọn trong khung chat (gửi kèm để trợ lý biết đang làm việc gì)
     * @return array{reply: string, tools: array<int, array{name: string, input: array, ok: bool}>}
     */
    public function ask(string $department, string $sessionId, string $text, ?User $user = null, ?string $modeNote = null): array
    {
        /** @var DepartmentTools $tools */
        $tools = app(config("claude.departments.{$department}.tools"));
        $deadline = microtime(true) + config('claude.reply_timeout', 240);

        // Mở stream TRƯỚC khi gửi tin nhắn để không lỡ event nào
        $stream = $this->client->beta->sessions->events->streamStream(
            $sessionId,
            requestOptions: ['transporter' => new GuzzleClient(['stream' => true, 'read_timeout' => 120])],
        );

        $today = now()->locale('vi')->isoFormat('dddd, DD/MM/YYYY');
        $this->client->beta->sessions->events->send($sessionId, events: [
            ManagedAgentsUserMessageEventParams::with(content: [
                ['type' => 'text', 'text' => "[Hôm nay: {$today}]" . ($modeNote ? "\n[Chức năng đang chọn: {$modeNote}]" : '') . "\n\n{$text}"],
            ]),
        ]);

        $replyParts = [];
        $toolLog = [];

        try {
            foreach ($stream as $event) {
                switch ($event->type) {
                    case 'agent.message':
                        // Chỉ giữ tin nhắn cuối cùng của lượt (các tin trước thường là "để em kiểm tra…")
                        $parts = [];
                        foreach ($event->content as $block) {
                            if ($block->type === 'text') {
                                $parts[] = $block->text;
                            }
                        }
                        if ($parts) {
                            $replyParts[] = implode('', $parts);
                        }
                        break;

                    case 'agent.custom_tool_use':
                        $toolLog[] = $this->runTool($tools, $sessionId, $event->id, $event->name, (array) $event->input, $user);
                        break;

                    case 'agent.tool_use':
                    case 'agent.mcp_tool_use':
                        // Toolset chỉ bật read/glob/grep với always_allow; nếu vẫn bị hỏi thì từ chối
                        if (($event->evaluatedPermission ?? null) === 'ask') {
                            $this->client->beta->sessions->events->send($sessionId, events: [
                                ManagedAgentsUserToolConfirmationEventParams::with(result: 'deny', toolUseID: $event->id),
                            ]);
                        }
                        break;

                    case 'session.status_idle':
                        // requires_action = đang chờ kết quả tool → tiếp tục đọc stream
                        if ($event->stopReason->type !== 'requires_action') {
                            break 2;
                        }
                        break;

                    case 'session.status_terminated':
                        break 2;

                    case 'session.error':
                        throw new RuntimeException('Agent báo lỗi: ' . ($event->error->message ?? 'không rõ'));
                }

                if (microtime(true) > $deadline) {
                    $this->client->beta->sessions->events->send($sessionId, events: [['type' => 'user.interrupt']]);
                    throw new RuntimeException('Trợ lý trả lời quá lâu, đã dừng. Vui lòng hỏi lại ngắn gọn hơn.');
                }
            }
        } finally {
            $stream->close();
        }

        $reply = trim((string) end($replyParts));

        return ['reply' => $reply !== '' ? $reply : '(Trợ lý không trả lời.)', 'tools' => $toolLog];
    }

    private function runTool(DepartmentTools $tools, string $sessionId, string $toolUseId, string $name, array $input, ?User $user): array
    {
        try {
            $result = $tools->call($name, $input, $user);
            $content = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $ok = true;
        } catch (\InvalidArgumentException $e) {
            $content = $e->getMessage();
            $ok = false;
        } catch (Throwable $e) {
            Log::error('Claude tool failed', ['tool' => $name, 'input' => $input, 'error' => $e->getMessage()]);
            $content = 'Lỗi hệ thống khi tra cứu dữ liệu.';
            $ok = false;
        }

        $this->client->beta->sessions->events->send($sessionId, events: [
            ManagedAgentsUserCustomToolResultEventParams::with(
                customToolUseID: $toolUseId,
                content: [['type' => 'text', 'text' => $content]],
                isError: !$ok,
            ),
        ]);

        return ['name' => $name, 'input' => $input, 'ok' => $ok];
    }
}
