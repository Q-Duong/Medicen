<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thu nợ & kiểm soát giá (Phân tích tài chính):
 * - unit_collectors: người phụ trách thu nợ của từng đơn vị khách hàng.
 * - debt_contacts: mỗi lần nhắc nợ (ngày, kênh, nội dung, khách hẹn trả ngày nào / bao nhiêu).
 * - price_approvals: giám đốc duyệt đơn giá thấp / chiết khấu cao (App\Services\Accounting\PriceGuard).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('unit_collectors')) {
            Schema::create('unit_collectors', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('unit_id')->unique();
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('debt_contacts')) {
            Schema::create('debt_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('unit_id')->index();
                $table->date('contacted_on');
                $table->string('channel', 20)->nullable();
                $table->text('note')->nullable();
                $table->date('promised_date')->nullable();
                $table->bigInteger('promised_amount')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('price_approvals')) {
            Schema::create('price_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('order_id')->unique();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_collectors');
        Schema::dropIfExists('debt_contacts');
        Schema::dropIfExists('price_approvals');
    }
};
