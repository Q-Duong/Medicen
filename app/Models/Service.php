<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    
    protected $table = 'services';

    public $timestamps = true;
    
    protected $fillable = [
        'service_title',
        'service_slug',
        'service_content',
        'service_image'
    ];
    
}
