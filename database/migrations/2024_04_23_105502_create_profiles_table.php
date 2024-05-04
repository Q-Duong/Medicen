<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('profile_firstname', 70)->nullable(false);
            $table->string('profile_lastname', 70)->nullable(false);
            $table->string('profile_phone', 70)->nullable(false);
            $table->string('profile_email', 100)->nullable(false);
            $table->string('profile_avatar', 10);
            $table->tinyInteger('profile_gender')->nullable(false);
            $table->date('day_of_birth');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profiles');
    }
}
