<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đánh dấu đơn "số ca đổi sau khi kế toán đã cập nhật doanh thu" (bộ phận Kết quả nhập số ca thực tế khác):
 * qty_changed_from = số ca lúc kế toán cập nhật doanh thu; trang Công nợ hiện cảnh báo để kế toán
 * "Tính lại tiền theo số ca mới" hoặc "Giữ tiền cũ" (cả hai đều xóa đánh dấu).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'qty_changed_from')) {
            return;
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('qty_changed_from')->nullable()->after('order_quantity');
            $table->timestamp('qty_changed_at')->nullable()->after('qty_changed_from');
            $table->string('qty_changed_by', 191)->nullable()->after('qty_changed_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('orders', 'qty_changed_from')) {
            return;
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['qty_changed_from', 'qty_changed_at', 'qty_changed_by']);
        });
    }
};
