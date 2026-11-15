<?php

namespace App\Console\Commands;

use App\Models\AssistantConversation;
use Illuminate\Console\Command;

/**
 * Xóa lịch sử chat với trợ lý AI cũ hơn số ngày cấu hình (mặc định 90 ngày),
 * kể cả session tương ứng trên Claude.
 */
class PruneAssistantConversations extends Command
{
    protected $signature = 'assistant:prune {--days= : Số ngày giữ lại (mặc định theo config claude.history_days)}';

    protected $description = 'Xóa các cuộc trò chuyện với trợ lý AI không hoạt động quá N ngày';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('claude.history_days', 90));
        $cutoff = now()->subDays($days);
        $count = 0;

        AssistantConversation::where(fn ($q) => $q->where('last_message_at', '<', $cutoff)
                ->orWhere(fn ($q) => $q->whereNull('last_message_at')->where('created_at', '<', $cutoff)))
            ->orderBy('id')
            ->chunkById(100, function ($conversations) use (&$count) {
                foreach ($conversations as $conversation) {
                    $conversation->purge();
                    $count++;
                }
            });

        $this->info("Đã xóa {$count} cuộc trò chuyện không hoạt động quá {$days} ngày.");

        return self::SUCCESS;
    }
}
