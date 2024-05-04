<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    public $timestamps = true; 
    protected $fillable = [
        'post_category_name', 'post_category_slug'
    ];
    // protected $primaryKey = 'post_category_id';
    protected $table = 'post_categories';

    public function post()
    {
        $this->hasMany('App\Models\Post');
    }
}
