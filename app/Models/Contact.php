<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public $timestamps = true; //set time to false
    protected $fillable = [
        'contact', 'contact_map'
    ];
    // protected $primaryKey = 'info_id';
    protected $table = 'contacts';
}
