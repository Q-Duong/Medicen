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

  protected static function boot()
  {
    parent::boot();

    static::saving(function ($staff) {
      // Kiểm tra xem số điện thoại bắt đầu bằng '0' hay không
      if (strpos($staff->staff_phone, '0') === 0) {
        // Chuyển đổi '0' thành '84'
        $staff->staff_phone = '84' . substr($staff->staff_phone, 1);
      }
    });
  }

  public function   getStaffPhoneAttribute($value)
  {
    // Chỉ thêm '0' vào đầu số điện thoại nếu nó không bắt đầu bằng '0'
    if (strpos($value, '0') !== 0) {
      return '0' . substr($value, 2);
    }

    return $value;
  }
}
