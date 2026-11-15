<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Phòng ban của tài khoản (Admin cấp trên trang "Phân quyền phòng ban").
 * Dùng để quyết định ai được dùng trợ lý AI nào. Danh sách phòng ban: config/departments.php.
 * Trước đây web phân biệt phòng ban theo tên tài khoản, nên lần đầu điền sẵn theo tên.
 */
return new class extends Migration
{
    private const FROM_NAME = [
        'Sale' => 'sales',
        'SubAdmin' => 'sales',
        'Accountant' => 'ke_toan',
        'Office' => 'ket_qua',
        'Technician' => 'ky_thuat',
        'HR' => 'nhan_su',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('department', 30)->nullable()->after('role')->index();
            });
        }

        // Tài khoản Admin (role 0, 1) dùng được tất cả nên không cần phòng ban
        foreach (self::FROM_NAME as $name => $department) {
            DB::table('users')->where('name', $name)->whereNotIn('role', [0, 1])->whereNull('department')
                ->update(['department' => $department]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['department']);
                $table->dropColumn('department');
            });
        }
    }
};
