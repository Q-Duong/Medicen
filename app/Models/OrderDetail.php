<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'ord_doctor_read', 'ord_film', 'ord_form', 'ord_print', 'ord_form_print', 'ord_print_result', 'ord_film_sheet', 'ord_deadline', 'ord_delivery_date', 'ord_deliver_results', 'ord_note', 'ord_start_day', 'ord_end_day', 'ord_cty_name', 'ord_time', 'ord_select', 'ord_list_file', 'ord_list_file_path', 'ord_email', 'ord_total_file_name', 'ord_total_file_path'
    ];
    // protected $primaryKey = 'order_detail_id';
    protected $table = 'order_details';

    public function order()
    {
        $this->hasMany('App\Models\Order');
    }
}
