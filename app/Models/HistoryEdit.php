<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryEdit extends Model
{
    use HasFactory;
    
    protected $table = 'history_edits';

    public $timestamps = true;

    protected $fillable = [
        'order_id',
        'user_name',
        'history_action'
    ];
}
