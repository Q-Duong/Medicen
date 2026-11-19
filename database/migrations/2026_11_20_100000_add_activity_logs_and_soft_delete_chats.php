<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - activity_logs: nhật ký thao tác ở các trang kế toán (Báo cáo thu chi, Công nợ, Công nợ CT, Sổ VCB chú Lộc,
 *   Dữ liệu kế toán) và các thao tác khác cần lưu vết (xóa cuộc trò chuyện trợ lý). Xem App\Models\ActivityLog.
 * - assistant_conversations: xóa mềm. Nhân viên xóa thì chỉ ẩn khỏi danh sách của họ, Admin vẫn xem được.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 150)->nullable();
                $table->string('area', 40);
                $table->string('action', 20);
                $table->string('subject_type', 120)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('summary', 500);
                $table->json('changes')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable()->index();
                $table->index(['area', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasColumn('assistant_conversations', 'deleted_at')) {
            Schema::table('assistant_conversations', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        if (Schema::hasColumn('assistant_conversations', 'deleted_at')) {
            Schema::table('assistant_conversations', function (Blueprint $table) {
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }
    }
};
