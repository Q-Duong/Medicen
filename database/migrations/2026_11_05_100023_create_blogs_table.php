<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Baseline theo DB thực tế (dump 2026-11-05).
 * DB đã có bảng này thì bỏ qua, để chạy được trên cả DB mới lẫn DB đang dùng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blogs')) {
            return;
        }

        Schema::create('blogs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->integer('id', true);
            $table->tinyText('blog_title');
            $table->tinyText('blog_title_en')->nullable();
            $table->string('blog_slug', 255);
            $table->longText('blog_text')->nullable();
            $table->longText('blog_text_en')->nullable();
            $table->string('blog_image', 255)->nullable();
            $table->integer('blog_category_id');
            $table->timestamps();
            $table->index('blog_category_id', 'blog_category_id');
        });
    }

    /**
     * Cố ý không drop: migration này cũng được "ghi nhận" trên DB đang có dữ liệu,
     * rollback mà drop sẽ mất dữ liệu thật. Dùng migrate:fresh khi cần dựng lại DB trống.
     */
    public function down(): void
    {
    }
};
