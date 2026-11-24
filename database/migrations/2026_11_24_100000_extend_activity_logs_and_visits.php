<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Nhật ký hoạt động chi tiết (App\Http\Middleware\RecordActivity, App\Services\Activity\*):
 *  - activity_logs thêm route (thao tác nào trên web) và user_agent (thiết bị / trình duyệt khi đăng nhập).
 *  - activity_visits: lượt mở các trang chính (để biết giờ vào / ra, thời gian làm trên web). Giữ 12 tháng (activity:prune).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'route')) {
                $table->string('route', 80)->nullable()->after('action');
            }
            if (!Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->string('user_agent', 190)->nullable()->after('ip');
            }
        });

        if (!Schema::hasTable('activity_visits')) {
            Schema::create('activity_visits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('route', 80);
                $table->string('path', 190)->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['user_id', 'created_at']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_visits');
        Schema::table('activity_logs', function (Blueprint $table) {
            foreach (['route', 'user_agent'] as $c) {
                if (Schema::hasColumn('activity_logs', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
