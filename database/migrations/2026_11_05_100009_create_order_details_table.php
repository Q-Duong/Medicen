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
        if (Schema::hasTable('order_details')) {
            return;
        }

        Schema::create('order_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('ord_doctor_read', 100)->nullable();
            $table->string('ord_film', 100)->nullable();
            $table->string('ord_form', 100)->nullable();
            $table->string('ord_print', 100)->nullable();
            $table->string('ord_form_print', 100)->nullable();
            $table->string('ord_print_result', 100)->nullable();
            $table->string('ord_film_sheet', 100)->nullable();
            $table->text('ord_note')->nullable();
            $table->string('ord_select', 255);
            $table->integer('ord_type')->nullable();
            $table->string('ord_deadline', 100)->nullable();
            $table->string('ord_delivery_date', 255)->nullable();
            $table->string('ord_deliver_results', 255)->nullable();
            $table->string('ord_email', 255)->nullable();
            $table->string('ord_start_day', 20)->nullable();
            $table->string('ord_end_day', 20)->nullable();
            $table->string('ord_session', 20)->nullable()->default('Cả ngày');
            $table->string('ord_cty_name', 255)->nullable();
            $table->char('ord_time', 15)->nullable();
            $table->string('ord_list_file', 255)->nullable();
            $table->string('ord_list_file_path', 255)->nullable();
            $table->string('ord_total_file_name', 255)->nullable();
            $table->string('ord_total_file_path', 255)->nullable();
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
