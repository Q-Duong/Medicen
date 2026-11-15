<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile(){
        return $this->belongsTo(Profile::class);
    }

    /**
     * Tên hiển thị: họ tên ở bảng profiles (vd "Nguyễn Thị Thảo Quyên"), chưa có thì tên tài khoản (vd "Sale").
     * Dữ liệu đang lưu: profile_firstname = họ + tên đệm ("Nguyễn Thị Thảo"), profile_lastname = tên ("Quyên").
     * Dùng ở giao diện (trang chat AI…); phân quyền vẫn theo tên tài khoản ($user->name).
     */
    public function getDisplayNameAttribute(): string
    {
        $p = $this->relationLoaded('profile') ? $this->profile : ($this->profile_id ? $this->profile()->first() : null);
        $full = trim(preg_replace('/\s+/u', ' ', trim(($p->profile_firstname ?? '') . ' ' . ($p->profile_lastname ?? ''))));

        return $full !== '' ? $full : (string) $this->name;
    }

    public function task(){
        return $this->hasMany(Task::class);
    }
}
