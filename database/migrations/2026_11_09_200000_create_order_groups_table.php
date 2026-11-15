<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Nhóm đơn đi chung: các đơn cùng đơn vị, cùng ngày chụp mà Sale chọn đi chung một chuyến (một xe, một đội).
 * Giữ nguyên quan hệ 1 đơn - 1 order_detail; nhóm chỉ là lớp gắn thêm (orders.order_group_id).
 * Mã nhóm dạng {mã đơn vị}-{ddmmyy}, trùng thì thêm -2, -3…
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_groups')) {
            Schema::create('order_groups', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->string('code', 40)->unique();
                $table->unsignedInteger('unit_id')->index();
                $table->date('group_date')->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasColumn('orders', 'order_group_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('order_group_id')->nullable()->index()->comment('Nhóm đơn đi chung (order_groups)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'order_group_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['order_group_id']);
                $table->dropColumn('order_group_id');
            });
        }
        Schema::dropIfExists('order_groups');
    }
};
