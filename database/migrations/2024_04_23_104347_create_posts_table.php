<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('post_title')->nullable(false);
            $table->string('post_slug')->nullable(false);
            $table->text('post_desc')->nullable(true);
            $table->text('post_content')->nullable(false);
            $table->string('post_image')->nullable(false);
            $table->integer('post_category_id')->unsigned()->nullable(false);
            $table->timestamps();

            //foreign keys
            $table->foreign('post_category_id')->references('id')->on('post_categories')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
