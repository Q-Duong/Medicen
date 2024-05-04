<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public $timestamps = true; //set time to false
    protected $fillable = [
        'post_title', 'post_slug', 'post_desc', 'post_content', 'post_meta_desc', 'post_meta_keywords', 'post_image', 'post_category_id'
    ];
    // protected $primaryKey = 'post_id';
    protected $table = 'posts';

    public function post_categories()
    {
        return $this->belongsTo('App\Models\CategoryPost', 'post_category_id');
    }
}
