<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accountants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('accountant_month');
            $table->string('accountant_distance', 3);
            $table->string('accountant_deadline', 10);
            $table->string('accountant_number', 20);
            $table->string('accountant_date', 20);
            $table->string('accountant_payment', 10);
            $table->integer('accountant_day');
            $table->string('accountant_day_payment', 20);
            $table->string('accountant_method', 10);
            $table->integer('accountant_amount_paid');
            $table->integer('accountant_owe');
            $table->string('accountant_discount_day', 20);
            $table->string('accountant_doctor_read', 30);
            $table->string('accountant_doctor_date_payment', 20);
            $table->integer('accountant_35X43');
            $table->string('accountant_polime', 5);
            $table->integer('accountant_8X10');
            $table->integer('accountant_10X12');
            $table->integer('accountant_film_bag');
            $table->text('accountant_note');
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
        Schema::dropIfExists('accountants');
    }
}
