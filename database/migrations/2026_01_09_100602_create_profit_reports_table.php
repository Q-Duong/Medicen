<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profit_reports', function (Blueprint $table) {
            $table->id();
            $table->text('comments')->nullable(); // Ý kiến / Ghi chú
            
            // Các chỉ số tài chính quan trọng (để sort, filter sau này)
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->decimal('profit_margin', 5, 2)->default(0);
            // Lưu toàn bộ trạng thái các ô input (giá, số lượng...) vào 1 cục JSON
            // Để khi cần sửa lại, chỉ cần đổ JSON này ra form
            $table->json('input_data'); 
            $table->tinyInteger('status')->default(0);
            $table->integer('unit_id')->unsigned()->nullable(false);
            $table->timestamps();
            
            //foreign keys
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_reports');
    }
};
