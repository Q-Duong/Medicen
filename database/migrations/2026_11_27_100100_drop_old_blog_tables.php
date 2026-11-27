<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/*
 * Bỏ blog cũ (bảng blogs, blog_categories — chỉ có bài test, thay bằng Tin tức: bảng news).
 * Trước khi xóa, chép dữ liệu ra storage/app/backups/old-blogs-<ngày giờ>.json để còn tra lại nếu cần.
 */
return new class extends Migration
{
    public function up(): void
    {
        $backup = [];
        foreach (['blogs', 'blog_categories'] as $t) {
            if (Schema::hasTable($t)) {
                $backup[$t] = DB::table($t)->get()->map(fn ($r) => (array) $r)->all();
            }
        }
        if ($backup) {
            Storage::disk('local')->put('backups/old-blogs-' . date('Ymd-His') . '.json', json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('blogs');
        Schema::dropIfExists('blog_categories');
        Schema::enableForeignKeyConstraints();
    }

    /** Tạo lại bảng rỗng (dữ liệu cũ nằm trong file backup) */
    public function down(): void
    {
        if (!Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $table) {
                $table->id();
                $table->string('blog_category_name')->nullable();
                $table->string('blog_category_name_en')->nullable();
                $table->string('blog_category_slug')->nullable();
                $table->string('blog_category_image')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('blogs')) {
            Schema::create('blogs', function (Blueprint $table) {
                $table->id();
                $table->string('blog_title')->nullable();
                $table->string('blog_title_en')->nullable();
                $table->string('blog_slug')->nullable();
                $table->longText('blog_text')->nullable();
                $table->longText('blog_text_en')->nullable();
                $table->string('blog_image')->nullable();
                $table->unsignedBigInteger('blog_category_id')->nullable();
                $table->timestamps();
            });
        }
    }
};
