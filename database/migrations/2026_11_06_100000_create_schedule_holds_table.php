<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Lịch giữ chỗ: khách đã đặt trước ngày / xe nhưng chưa lên đơn.
 * Khi có đơn thì "xếp đơn vào lịch giữ chỗ" (status = used, order_id = đơn đó).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_holds')) {
            return;
        }
        Schema::create('schedule_holds', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->date('hold_start');
            $table->date('hold_end');
            $table->string('hold_session', 20)->default('Cả ngày')->comment('Sáng | Chiều | Cả ngày');
            $table->string('car', 5);
            $table->string('driver', 100)->nullable();
            $table->string('ktv1', 255)->nullable();
            $table->string('ktv2', 255)->nullable();
            $table->unsignedInteger('unit_id')->nullable()->index()->comment('Đơn vị nếu đã có trong hệ thống');
            $table->string('customer', 255)->comment('Tên khách / đơn vị đặt trước');
            $table->unsignedInteger('expected_cas')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('active')->index()->comment('active | used | cancelled');
            $table->unsignedInteger('order_id')->nullable()->index()->comment('Đơn đã xếp vào chỗ này');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['hold_start', 'hold_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_holds');
    }
};
