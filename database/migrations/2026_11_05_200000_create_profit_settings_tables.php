<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Đơn giá mặc định của bảng tính lợi nhuận có thể đổi khi đang chạy
 * (giá gốc vẫn ở config/profit.php; bảng này chỉ lưu giá đã ghi đè).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('key', 64)->primary();
            $table->decimal('value', 15, 4);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('profit_setting_changes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('key', 64)->index();
            $table->decimal('old_value', 15, 4)->nullable();
            $table->decimal('new_value', 15, 4);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('source', 30)->default('web')->comment('web | assistant');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_setting_changes');
        Schema::dropIfExists('profit_settings');
    }
};
