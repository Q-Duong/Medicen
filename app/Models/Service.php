<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public $timestamps = true;
    protected $fillable = [
    	'service_title','service_slug','service_content','service_image'
    ];
    protected $primaryKey = 'service_id';
 	protected $table = 'tbl_service';
}
