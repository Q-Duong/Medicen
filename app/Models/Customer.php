<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public $timestamps = true; //set time to false
    protected $fillable = [
    	'customer_name','customer_phone', 'customer_address', 'customer_note'
    ];
    protected $primaryKey = 'customer_id';
 	protected $table = 'tbl_customers';

    public function orders(){
       $this->hasMany('App\Models\Select');
    }
}
