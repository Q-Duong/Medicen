<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $table = 'blog_categories';
    
    public $timestamps = true;

    protected $fillable = [
        'blog_category_name',
        'blog_category_name_en',
        'blog_category_slug',
        'blog_category_image'
    ];

    public function blog()
    {
        $this->hasMany(Blog::class);
    }
}
