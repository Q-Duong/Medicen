<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
   use HasFactory;
   
   protected $table = 'customers';

   public $timestamps = true;
   
   protected $fillable = [
      'customer_name',
      'customer_phone',
      'customer_address',
      'customer_note'
   ];
  
   public function order()
   {
      $this->hasMany(Order::class);
   }
}
