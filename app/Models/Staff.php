<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Staff extends Model
{
  use HasFactory;

  protected $table = 'staffs';

  public $timestamps = true;
  
  protected $fillable = [
    'staff_name',
    'staff_phone',
    'staff_gender',
    'staff_birthday',
    'staff_role'
  ];

  public function carKTV()
  {
    return $this->belongsTo(CarKTV::class);
  }
}
