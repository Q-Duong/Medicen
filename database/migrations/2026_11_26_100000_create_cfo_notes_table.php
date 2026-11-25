<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Ghi nhớ của CFO (App\Models\CfoNote): quyết định / tình hình giám đốc, kế toán nói với trợ lý ("tiền máy Bloc đã về 20/11",
 * "Á Châu dừng hợp đồng tháng 12") để các lần hỏi sau trợ lý tự dùng. Sửa ở Phân tích tài chính → Ghi nhớ hoặc qua tool ghi_nho.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cfo_notes')) {
            Schema::create('cfo_notes', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->text('content');
                $table->string('topic', 120)->nullable();
                $table->date('remind_on')->nullable()->comment('Ngày cần xem lại / nhắc');
                $table->boolean('done')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('source', 20)->default('web')->comment('web | tro_ly');
                $table->timestamps();
                $table->index(['done', 'remind_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cfo_notes');
    }
};
