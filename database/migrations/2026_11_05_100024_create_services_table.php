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
        if (Schema::hasTable('services')) {
            return;
        }

        Schema::create('services', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id', true);
            $table->string('service_title', 100);
            $table->string('service_slug', 100);
            $table->text('service_content');
            $table->string('service_image', 300);
            $table->timestamps();
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
