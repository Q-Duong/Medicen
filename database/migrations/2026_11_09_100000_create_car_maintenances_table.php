<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Lịch xe nghỉ (bảo trì, đăng kiểm, sửa chữa): xe không được xếp đơn / giữ chỗ trong thời gian này.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('car_maintenances')) {
            return;
        }
        Schema::create('car_maintenances', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('car', 5)->index();
            $table->date('maintenance_start');
            $table->date('maintenance_end');
            $table->string('maintenance_session', 20)->default('Cả ngày')->comment('Sáng | Chiều | Cả ngày');
            $table->string('maintenance_note', 255)->nullable()->comment('Bảo trì, đăng kiểm, sửa chữa…');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['maintenance_start', 'maintenance_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_maintenances');
    }
};
