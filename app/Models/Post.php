<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public $timestamps = false; //set time to false
    protected $fillable = [
    	'post_title','post_slug','post_desc','post_content','post_meta_desc','post_meta_keywords','post_image','category_post_id'
    ];
    protected $primaryKey = 'post_id';
 	protected $table = 'tbl_posts';

     public function category_post(){
        return $this->belongsTo('App\Models\CategoryPost','category_post_id');
    }
}
