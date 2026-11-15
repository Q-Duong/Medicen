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
        if (Schema::hasTable('excel_uploads')) {
            return;
        }

        Schema::create('excel_uploads', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->id();
            $table->unsignedInteger('unit_id');
            $table->date('ngay');
            $table->string('ten_file', 255);
            $table->string('duong_dan', 255);
            $table->unsignedBigInteger('kich_thuoc')->default(0);
            $table->text('ghi_chu')->nullable();
            $table->string('trang_thai', 30)->default('cho_tiep_nhan');
            $table->unsignedInteger('order_id')->nullable();
            $table->text('ly_do_tu_choi')->nullable();
            $table->unsignedBigInteger('nguoi_tao_id')->nullable();
            $table->unsignedBigInteger('nguoi_tiep_nhan_id')->nullable();
            $table->timestamp('tiep_nhan_luc')->nullable();
            $table->timestamp('hoan_thanh_luc')->nullable();
            $table->timestamps();
            $table->index('nguoi_tao_id', 'excel_uploads_nguoi_tao_id_foreign');
            $table->index('nguoi_tiep_nhan_id', 'excel_uploads_nguoi_tiep_nhan_id_foreign');
            $table->index('trang_thai', 'excel_uploads_trang_thai_index');
            $table->index(['unit_id', 'ngay'], 'excel_uploads_unit_id_ngay_index');
            $table->index('created_at', 'excel_uploads_created_at_index');
            $table->index('order_id', 'excel_uploads_order_id_foreign');
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
