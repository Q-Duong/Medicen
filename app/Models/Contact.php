<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public $timestamps = true; //set time to false
    protected $fillable = [
        'info_contact', 'info_map', 'info_logo'
    ];
    // protected $primaryKey = 'info_id';
    protected $table = 'infomation';
}
