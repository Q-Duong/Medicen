<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Thông tin để gợi ý xếp lịch: chứng chỉ hành nghề của nhân viên và lịch nghỉ.
 * Bảng staffs là MyISAM/latin1 nên cột chữ mới khai báo utf8mb4 riêng để lưu được tiếng Việt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            if (!Schema::hasColumn('staffs', 'staff_certificate')) {
                $table->boolean('staff_certificate')->default(false)->after('staff_role')
                    ->comment('Có chứng chỉ hành nghề (CCHN)');
            }
            if (!Schema::hasColumn('staffs', 'staff_certificate_note')) {
                $table->string('staff_certificate_note', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')
                    ->nullable()->after('staff_certificate')->comment('Số CCHN, phạm vi (X-quang, siêu âm...)');
            }
        });

        if (!Schema::hasTable('staff_leaves')) {
            Schema::create('staff_leaves', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->unsignedInteger('staff_id')->index();
                $table->date('leave_start');
                $table->date('leave_end');
                $table->string('leave_session', 20)->default('Cả ngày')->comment('Sáng | Chiều | Cả ngày');
                $table->string('leave_note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['leave_start', 'leave_end']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_leaves');
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn(['staff_certificate', 'staff_certificate_note']);
        });
    }
};
