<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Claude\DepartmentAgentRunner;
use Illuminate\Console\Command;
use Throwable;

/**
 * Hỏi thử agent phòng ban từ terminal (nhiều lượt trong cùng một session).
 *
 * php artisan claude:ask sales "Báo giá 300 cas phổi cho BV Bình Chánh"
 * php artisan claude:ask sales            (chế độ hỏi liên tục, gõ "exit" để thoát)
 */
class ClaudeAsk extends Command
{
    protected $signature = 'claude:ask {department} {message?} {--session= : Tiếp tục session có sẵn} {--user= : Email user để kiểm quyền (vd. tool đổi giá)}';

    protected $description = 'Hỏi thử trợ lý AI của một phòng ban';

    public function handle(DepartmentAgentRunner $runner): int
    {
        $department = $this->argument('department');
        $user = $this->option('user') ? User::where('email', $this->option('user'))->firstOrFail() : null;

        try {
            $sessionId = $this->option('session') ?: $runner->startSession($department, 'CLI test', ['source' => 'cli']);
            $this->comment("Session: {$sessionId}");

            $message = $this->argument('message');
            do {
                $message ??= $this->ask('Bạn');
                if ($message === null || in_array(trim($message), ['exit', 'quit'], true)) {
                    break;
                }

                $result = $runner->ask($department, $sessionId, $message, $user);
                foreach ($result['tools'] as $tool) {
                    $this->line(sprintf('  <fg=gray>[tool] %s %s%s</>', $tool['name'],
                        json_encode($tool['input'], JSON_UNESCAPED_UNICODE), $tool['ok'] ? '' : ' (lỗi)'));
                }
                $this->newLine();
                $this->line($result['reply']);
                $this->newLine();

                $message = null;
            } while (!$this->argument('message'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
