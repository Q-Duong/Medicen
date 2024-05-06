<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    public $timestamps = true;
    
    protected $fillable = [
        'post_category_id',
        'post_title',
        'post_slug',
        'post_desc',
        'post_content',
        'post_meta_desc',
        'post_meta_keywords',
        'post_image'
    ];
   
    public function post_category()
    {
        return $this->belongsTo(PostCategory::class);
    }
}
