<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Baseline theo DB thực tế (dump 2026-11-05).
 * DB đã có bảng này thì bỏ qua, để chạy được trên cả DB mới lẫn DB đang dùng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            return;
        }

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('uuid', 255);
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
            $table->unique('uuid', 'failed_jobs_uuid_unique');
        });
    }

    /**
     * Cố ý không drop: migration này cũng được "ghi nhận" trên DB đang có dữ liệu,
     * rollback mà drop sẽ mất dữ liệu thật. Dùng migrate:fresh khi cần dựng lại DB trống.
     */
    public function down(): void
    {
    }
};
