<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Nhật ký Sales (Kinh doanh → Nhật ký Sales): Sales dán đoạn chat Zalo / ảnh chụp màn hình / ghi chú cuộc gọi,
 * AI tóm tắt và tách việc cần làm (sales_followups) để nhắc lại; tóm tắt ngày (sales_digests) gửi email nhắc việc 17:00, giám đốc hỏi Trợ lý AI.
 * Zalo công ty là tài khoản cá nhân (không có API) nên dữ liệu do Sales đưa vào, không đọc tự động.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_logs')) {
            Schema::create('sales_logs', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->date('happened_on');
                $table->string('kind', 20)->default('chat')->comment('chat | call | meeting | note');
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->string('customer', 191)->nullable()->comment('Khách / người liên hệ (AI điền hoặc Sales sửa)');
                $table->mediumText('raw_text')->nullable()->comment('Đoạn chat / ghi chú Sales dán vào');
                $table->json('images')->nullable()->comment('Tên file ảnh chụp màn hình trong storage/app/sales-logs');
                $table->text('summary')->nullable();
                $table->boolean('important')->default(false)->comment('Khiếu nại, hủy, việc gấp');
                $table->string('ai_status', 20)->default('done')->comment('done | failed | skipped');
                $table->string('ai_error', 255)->nullable();
                $table->timestamps();
                $table->index(['happened_on', 'user_id']);
                $table->index('unit_id');
            });
        }
        if (!Schema::hasTable('sales_followups')) {
            Schema::create('sales_followups', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('sales_log_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->comment('Sales phụ trách');
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->string('customer', 191)->nullable();
                $table->string('title', 500);
                $table->date('due_on')->nullable();
                $table->dateTime('done_at')->nullable();
                $table->unsignedBigInteger('done_by')->nullable();
                $table->timestamps();
                $table->index(['done_at', 'due_on']);
                $table->index('sales_log_id');
            });
        }
        if (!Schema::hasTable('sales_digests')) {
            Schema::create('sales_digests', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->date('day')->unique();
                $table->mediumText('content')->comment('Tóm tắt ngày (markdown) do AI viết');
                $table->json('stats')->nullable();
                $table->dateTime('emailed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_digests');
        Schema::dropIfExists('sales_followups');
        Schema::dropIfExists('sales_logs');
    }
};
