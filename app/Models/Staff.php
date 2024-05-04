<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    public $timestamps = true; //set time to false
    protected $fillable = [
      'staff_name','staff_phone', 'staff_gender', 'staff_birthday', 'staff_role'
    ];
    protected $primaryKey = 'staff_id';
    protected $table = 'tbl_staff';

    public function carKTV()
    {
        return $this->belongsTo('App\Models\CarKTV');
    }
}
