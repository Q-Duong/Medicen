<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryEdit extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'order_id',  'user_name', 'history_action'
    ];
    // protected $primaryKey = 'history_id';
    protected $table = 'history_edits';
}
