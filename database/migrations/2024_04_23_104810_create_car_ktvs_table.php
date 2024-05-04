<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarKtvsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_ktvs', function (Blueprint $table) {
            $table->increments('id');
            $table->char('car_name', 5);
            $table->integer('car_active');
            $table->string('car_driver_name', 100);
            $table->string('car_driver_phone', 12);
            $table->string('car_ktv_name_1');
            $table->string('car_ktv_phone_1', 12);
            $table->string('car_ktv_name_2');
            $table->string('car_ktv_phone_2', 12);
            $table->integer('order_id')->unsigned()->nullable(false);
            $table->timestamps();

            //foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('car_ktvs');
    }
}
