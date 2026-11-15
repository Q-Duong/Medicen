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
        if (Schema::hasTable('staffs')) {
            return;
        }

        Schema::create('staffs', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->integer('id', true);
            $table->string('staff_name', 255)->charset('utf8')->collation('utf8_general_ci');
            $table->char('staff_phone', 12)->charset('utf8')->collation('utf8_general_ci');
            $table->integer('staff_gender');
            $table->string('staff_birthday', 10)->charset('utf8')->collation('utf8_general_ci');
            $table->string('staff_role', 10)->charset('utf8')->collation('utf8_general_ci')->nullable();
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
