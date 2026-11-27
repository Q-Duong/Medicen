<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tin tức trên website (App\Models\News): admin soạn ở Website → Tin tức, khách xem ở /news.
 * Ảnh lưu ở storage/app/news (không phụ thuộc thư mục public trên hosting), phục vụ qua route news.image.
 * Bảng blogs / blog_categories cũ (bài test) giữ nguyên, không dùng nữa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('title', 255);
                $table->string('slug', 255)->unique();
                $table->string('category', 80)->nullable();
                $table->text('summary')->nullable()->comment('Mô tả ngắn hiện ở danh sách và chia sẻ mạng xã hội');
                $table->longText('content');
                $table->string('image', 120)->nullable()->comment('Tên file ảnh đại diện trong storage/app/news');
                $table->string('status', 20)->default('draft')->comment('draft | published');
                $table->dateTime('published_at')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('views')->default(0);
                $table->string('seo_title', 255)->nullable();
                $table->string('seo_description', 300)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'published_at']);
                $table->index('category');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
