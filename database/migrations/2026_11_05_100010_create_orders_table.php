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
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('order_detail_id');
            $table->unsignedInteger('unit_id')->default(1);
            $table->integer('order_quantity')->nullable();
            $table->integer('order_quantity_draft')->nullable()->default(0);
            $table->string('order_note_ktv', 255)->nullable();
            $table->string('order_vat', 50)->nullable();
            $table->integer('order_cost')->nullable();
            $table->integer('order_price')->nullable();
            $table->string('order_percent_discount', 20)->nullable();
            $table->integer('order_discount')->nullable()->default(0);
            $table->integer('order_profit')->nullable();
            $table->unsignedInteger('status_id');
            $table->integer('schedule_status')->nullable();
            $table->string('order_warning', 10)->nullable();
            $table->integer('order_all_in_one')->nullable()->default(0);
            $table->integer('accountant_updated')->nullable();
            $table->integer('order_child')->default(1);
            $table->integer('order_surcharge')->default(0);
            $table->integer('order_updated')->nullable()->default(0);
            $table->string('order_send_result', 255)->nullable();
            $table->integer('overnight')->default(0);
            $table->integer('complete')->nullable()->default(1);
            $table->timestamps();
            $table->index('customer_id', 'customer_id');
            $table->index('order_detail_id', 'order_detail_id');
            $table->index('unit_id', 'unit_id');
            $table->index('status_id', 'order_status');
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
