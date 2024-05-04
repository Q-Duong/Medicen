<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_quantity');
            $table->integer('order_quantity_draft');
            $table->string('order_note_ktv');
            $table->integer('order_cost');
            $table->string('order_vat', 30);
            $table->integer('order_price');
            $table->string('order_percent_discount', 20);
            $table->integer('order_discount');
            $table->integer('order_profit');
            $table->integer('schedule_status');
            $table->string('order_warning', 10);
            $table->integer('accountant_updated');
            $table->integer('order_all_in_one');
            $table->integer('order_child')->nullable(false);
            $table->integer('order_surcharge')->nullable(false);
            $table->integer('order_updated');
            $table->integer('status_id')->unsigned()->nullable(false);
            $table->integer('customer_id')->unsigned()->nullable(false);
            $table->integer('order_detail_id')->unsigned()->nullable(false);
            $table->integer('unit_id')->unsigned()->nullable(false);
            $table->timestamps();

            //foreign keys
            $table->foreign('status_id')->references('id')->on('status')->onDelete('cascade')->onUpdate('cascade');

            //foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');

            //foreign keys
            $table->foreign('order_detail_id')->references('id')->on('order_details')->onDelete('cascade')->onUpdate('cascade');

            //foreign keys
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
