<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryPost;
use Illuminate\Support\Facades\Redirect;

class CategoryPostController extends Controller
{
    public function add(){
    	return view('admin.CategoryPost.add_category_post');
    }
    
    public function list(){
       $getAllCategoryPost = CategoryPost::orderBy('category_post_id','ASC')->get();
    	return view('admin.CategoryPost.list_category_post')->with(compact('getAllCategoryPost'));
    }
    public function save(Request $request){
    	$data = $request->all();
        $category_post = new CategoryPost();
    	$category_post->category_post_name = $data['category_post_name'];
        $category_post->category_post_slug = $data['category_post_slug'];
        $name = $category_post->category_post_name;

        $check = CategoryPost::where('category_post_name',$name)->exists();
        if($check)
        {
            return Redirect()->back()->with('error','Danh mục đã tồn tại, Vui lòng kiểm tra lại.');
        }
        $category_post->save();

    	return Redirect()->back()->with('success','Thêm danh mục bài viết thành công');
    }
    
    public function edit($category_post_id){
        $category_post = CategoryPost::find($category_post_id);
        return view('admin.CategoryPost.edit_category_post')->with(compact('category_post'));
    }
    public function update(Request $request,$category_post_id){
        $data = $request->all();
        $category_post = CategoryPost::find($category_post_id);
    	$category_post->category_post_name = $data['category_post_name'];
        $category_post->category_post_slug = $data['category_post_slug'];
        $category_post->save();

    	return Redirect::to('/list-category-post')->with('success','Cập nhật danh mục bài viết thành công');
    }
    public function delete($category_post_id){
        $category_post = CategoryPost::find($category_post_id);
        $category_post->delete();
        return Redirect()->back()->with('success','Xóa danh mục bài viết thành công');
    }
}
