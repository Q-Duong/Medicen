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
        if (Schema::hasTable('report_items')) {
            return;
        }

        Schema::create('report_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->enum('type', ['thu', 'chi'])->comment('Loại: thu hoặc chi');
            $table->string('summary_group', 255)->comment('Nhóm tổng hợp để map lên bảng trên (VD: Tiền hiện có, Chi DV)');
            $table->string('name', 255)->comment('Tên mục chi tiết');
            $table->integer('sort_order')->default(0);
            $table->date('expected_date')->nullable()->comment('Ngày dự kiến thu/chi');
            $table->decimal('estimated_amount', 15, 0)->default(0)->comment('Số phải thu / Dự chi');
            $table->decimal('actual_amount', 15, 0)->default(0)->comment('Đã thu / Đã chi');
            $table->dateTime('actual_date')->nullable()->comment('Ngày thu/chi thực tế');
            $table->timestamps();
            $table->index('report_id', 'report_items_report_id_foreign');
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
