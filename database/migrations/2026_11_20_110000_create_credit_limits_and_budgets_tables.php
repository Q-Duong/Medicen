<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - unit_credit_limits: hạn mức tín dụng từng đơn vị khách hàng (nợ tối đa, số ngày quá hạn tối đa) do kế toán đặt.
 *   Đơn vị chưa đặt thì dùng hạn mức gợi ý tính từ lịch sử (App\Services\Accounting\CreditPolicy).
 * - budgets: kế hoạch năm theo tháng (App\Services\Accounting\Budget::LINES), so với thực tế ở trang Phân tích tài chính.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('unit_credit_limits')) {
            Schema::create('unit_credit_limits', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('unit_id')->unique();
                $table->bigInteger('limit_amount')->nullable();
                $table->unsignedSmallInteger('max_overdue_days')->nullable();
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->string('line', 40);
                $table->bigInteger('amount')->default(0);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['year', 'month', 'line']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_credit_limits');
        Schema::dropIfExists('budgets');
    }
};
