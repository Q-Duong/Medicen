<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostCategoryRequestForm;
use Illuminate\Http\Request;
use App\Models\PostCategory;
use Illuminate\Support\Facades\Redirect;

class PostCategoryController extends Controller
{
    public function index()
    {
        return view('pages.admin.postCategory.index');
    }

    public function create()
    {
        return view('pages.admin.postCategory.create');
    }

    public function store(PostCategoryRequestForm $request)
    {
        $data = $request->all();
        $postCategory = new PostCategory();
        $postCategory->post_category_name = $data['post_category_name'];
        $postCategory->post_category_slug = $data['post_category_slug'];
        $name = $postCategory->post_category_name;
        $check = PostCategory::where('post_category_name', $name)->exists();
        if ($check) {
            return Redirect()->back()->with('error', 'Danh mục đã tồn tại, Vui lòng kiểm tra lại.');
        }
        $postCategory->save();

        return Redirect::route('post_category.index')->with('success', 'Thêm danh mục bài viết thành công');
    }

    public function edit($id)
    {
        $postCategory = PostCategory::findOrFail($id);
        return view('pages.admin.postCategory.edit', compact('postCategory'));
    }
    
    public function update(PostCategoryRequestForm $request, $id)
    {
        $data = $request->all();
        $postCategory = PostCategory::findOrFail($id);
        $postCategory->post_category_name = $data['post_category_name'];
        $postCategory->post_category_slug = $data['post_category_slug'];
        $postCategory->save();

        return Redirect::route('post_category.index')->with('success', 'Cập nhật danh mục bài viết thành công');
    }

    public function destroy($id)
    {
        $postCategory = PostCategory::findOrFail($id);
        $postCategory->delete();
        return Redirect()->back()->with('success', 'Xóa danh mục bài viết thành công');
    }
}
