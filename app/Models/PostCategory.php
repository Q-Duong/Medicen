<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    use HasFactory;
    
    protected $table = 'post_categories';

    public $timestamps = true; 

    protected $fillable = [
        'post_category_name',
        'post_category_slug'
    ];
    
    public function post()
    {
        $this->hasMany(Post::class);
    }
}
