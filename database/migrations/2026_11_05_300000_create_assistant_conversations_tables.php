<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Lịch sử chat với trợ lý AI các phòng ban.
 * Mỗi cuộc trò chuyện ứng với một session trên Claude Managed Agents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('department', 30);
            $table->string('title', 200);
            $table->string('claude_session_id', 100)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['department', 'user_id', 'last_message_at'], 'assistant_conv_owner_idx');
            $table->index('last_message_at');
        });

        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->string('role', 20)->comment('user | assistant | error');
            $table->longText('content');
            $table->string('mode', 30)->nullable()->comment('Chức năng đang chọn khi hỏi');
            $table->json('tools')->nullable()->comment('Tool trợ lý đã dùng để trả lời');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
        Schema::dropIfExists('assistant_conversations');
    }
};
