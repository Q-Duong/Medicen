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
        if (Schema::hasTable('accountants')) {
            return;
        }

        Schema::create('accountants', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id', true);
            $table->integer('accountant_month')->nullable();
            $table->unsignedInteger('order_id');
            $table->string('accountant_distance', 20)->nullable();
            $table->string('accountant_deadline', 15)->nullable();
            $table->string('accountant_number', 20)->nullable();
            $table->string('accountant_date', 20)->nullable();
            $table->string('accountant_payment', 15)->nullable();
            $table->string('accountant_day_payment', 20)->nullable();
            $table->string('accountant_method', 30)->nullable();
            $table->integer('accountant_amount_paid')->nullable()->default(0);
            $table->integer('accountant_owe')->nullable();
            $table->string('accountant_discount_day', 20)->nullable();
            $table->string('accountant_doctor_read', 30)->nullable();
            $table->string('accountant_doctor_date_payment', 20)->nullable();
            $table->integer('accountant_35X43')->nullable();
            $table->string('accountant_polime', 5)->nullable();
            $table->integer('accountant_8X10')->nullable();
            $table->integer('accountant_10X12')->nullable();
            $table->integer('accountant_film_bag')->nullable();
            $table->text('accountant_note')->nullable();
            $table->integer('accountant_status')->nullable()->default(0);
            $table->string('liquidation_number', 100)->nullable();
            $table->string('contract_type', 100)->nullable();
            $table->string('contract_date', 100)->nullable();
            $table->integer('contract_status')->nullable()->default(1);
            $table->timestamps();
            $table->index('order_id', 'order_id');
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
