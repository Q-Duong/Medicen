<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PostCategory;
use Illuminate\Support\Facades\Redirect;

class PostCategoryController extends Controller
{
    public function add()
    {
        return view('admin.CategoryPost.add_post_category');
    }

    public function list()
    {
        $getAllCategoryPost = PostCategory::orderBy('post_category_id', 'ASC')->get();
        return view('admin.PostCategory.list_post_category')->with(compact('getAllPostCategory'));
    }
    public function save(Request $request)
    {
        $data = $request->all();
        $post_category = new PostCategory();
        $post_category->post_category_name = $data['post_category_name'];
        $post_category->post_category_slug = $data['post_category_slug'];
        $name = $post_category->post_category_name;
        $check = PostCategory::where('post_category_name', $name)->exists();
        if ($check) {
            return Redirect()->back()->with('error', 'Danh mục đã tồn tại, Vui lòng kiểm tra lại.');
        }
        $post_category->save();

        return Redirect()->back()->with('success', 'Thêm danh mục bài viết thành công');
    }

    public function edit($post_category_id)
    {
        $post_category = PostCategory::find($post_category_id);
        return view('admin.PostCategory.edit_post_category')->with(compact('post_category'));
    }
    public function update(Request $request, $post_category_id)
    {
        $data = $request->all();
        $post_category = PostCategory::find($post_category_id);
        $post_category->post_category_name = $data['post_category_name'];
        $post_category->post_category_slug = $data['post_category_slug'];
        $post_category->save();

        return Redirect::to('/list-category-post')->with('success', 'Cập nhật danh mục bài viết thành công');
    }
    public function delete($post_category_id)
    {
        $post_category = PostCategory::find($post_category_id);
        $post_category->delete();
        return Redirect()->back()->with('success', 'Xóa danh mục bài viết thành công');
    }
}
