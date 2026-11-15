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
        if (Schema::hasTable('profit_reports')) {
            return;
        }

        Schema::create('profit_reports', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->id();
            $table->unsignedInteger('unit_id');
            $table->text('comments')->nullable();
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->tinyInteger('status')->default(0);
            $table->decimal('profit_margin', 5, 2)->default(0);
            $table->longText('input_data')->nullable();
            $table->timestamps();
            $table->index('unit_id', 'unit_id');
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
