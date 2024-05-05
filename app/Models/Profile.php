<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    public $timestamps = true; //set time to false
    protected $fillable = [
        'profile_firstname',
        'profile_lastname',
        'profile_phone',
        'profile_email',
        'profile_avatar',
        'date_of_birth',
        'profile_gender'
    ];
    // protected $primaryKey = 'profile_id';
    protected $table = 'profiles';

    public function user()
    {
        return $this->hasOne('App\Models\User');
    }
}