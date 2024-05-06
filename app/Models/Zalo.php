<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zalo extends Model
{
    use HasFactory;
    
    protected $table = 'zalo';
    
    public $timestamps = true;

    protected $fillable = [
    	'access_token',
        'refresh_token'
    ];
}
