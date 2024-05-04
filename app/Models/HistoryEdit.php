<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryEdit extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'order_id',  'user_name', 'history_action', 'created_at'
    ];
    protected $primaryKey = 'history_id';
 	protected $table = 'tbl_history_edit';
}
