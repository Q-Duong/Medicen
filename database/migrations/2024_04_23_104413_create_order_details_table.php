<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ord_doctor_read',10);
            $table->string('ord_film',100);
            $table->string('ord_form',100);
            $table->string('ord_print',100);
            $table->string('ord_form_print',100);
            $table->string('ord_print_result',100);
            $table->string('ord_film_sheet',100);
            $table->string('ord_note');
            $table->string('ord_select')->nullable(false);
            $table->string('ord_deadline',100)->nullable(false);
            $table->string('ord_delivery_date');
            $table->string('ord_deliver_results')->nullable(false);
            $table->string('ord_email');
            $table->string('ord_start_day',20)->nullable(false);
            $table->string('ord_end_day',20)->nullable(false);
            $table->string('ord_cty_name')->nullable(false);
            $table->char('ord_time', 15);
            $table->string('ord_list_file');
            $table->string('ord_list_file_path');
            $table->string('ord_total_file_name',100);
            $table->string('ord_total_file_path',200);
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
        Schema::dropIfExists('order_details');
    }
}
