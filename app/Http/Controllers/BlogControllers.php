<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequestForm;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Support\Facades\Redirect;

class BlogControllers extends Controller
{
    public function index()
    {
        $getAllPost = Post::join('post_categories', 'post_categories.id' , '=', 'posts.post_category_id')->orderBy('posts.id', 'DESC')->paginate(10);
        return view('pages.admin.post.index', compact('getAllPost'));
    }

    public function create()
    {
        $post_category = PostCategory::orderBy('id', 'ASC')->get();
        return view('pages.admin.post.create', compact('post_category'));
    }

    public function upload_image_ck(Request $request)
    {
        if ($request->hasFile('upload')) {
            $get_image = $request->file('upload');
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/content/'), $new_image);
            $url = asset('uploads/content/' . $new_image);
            return response()->json(['fileName' => $new_image, 'uploaded' => 1, 'url' => $url]);
        }
    }

    public function store(PostRequestForm $request)
    {
        $data = $request->all();
        $post = new Post();
        $post->post_title = $data['post_title'];
        $post->post_slug = $data['post_slug'];
        $post->post_desc = $data['post_desc'];
        $post->post_content = $data['post_content'];
        $post->post_category_id = $data['post_category_id'];
        $get_image = $request->file('post_image');
        $name = $post->post_title;

        $check = Post::where('post_title', $name)->exists();
        if ($check) {
            return Redirect()->back()->with('error', 'Bài đã tồn tại, Vui lòng kiểm tra lại.')->withInput();
        }

        if ($get_image) {
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/post/'), $new_image);
            $post->post_image = $new_image;
            $post->save();
            return Redirect::route('post.index')->with('success', 'Thêm bài viết thành công');
        } else {
            return Redirect()->back()->with('error', 'Vui lòng thêm hình ảnh');
        }
    }

    public function edit($id)
    {
        $postCategory = PostCategory::orderBy('id', 'ASC')->get();
        $post = Post::findOrFail($id);
        dd($post);
        return view('pages.admin.post.edit', compact('post', 'postCategory'));
    }

    public function update(PostRequestForm $request, $id)
    {
        $data = $request->all();
        $post = Post::findOrFail($id);
        $post->post_title = $data['post_title'];
        $post->post_slug = $data['post_slug'];
        $post->post_desc = $data['post_desc'];
        $post->post_content = $data['post_content'];
        $get_image = $request->file('post_image');

        if ($get_image) {
            $post_image_old = $post->post_image;
            $path = public_path('uploads/post/');
            unlink($path . $post_image_old);

            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.', $get_name_image));
            $new_image =  $name_image . rand(0, 99) . '.' . $get_image->getClientOriginalExtension();
            $get_image->move($path, $new_image);
            $post->post_image = $new_image;
        }
        $post->save();
        return Redirect::route('post.index')->with('success', 'Cập nhật bài viết thành công');
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post_image = $post->post_image;
        if ($post_image) {
            unlink(public_path('uploads/post/') . $post_image);
        }
        $post->delete();
        return Redirect()->back()->with('success', 'Xóa bài viết thành công');
    }

    //Client
    public function showPostCategories()
    {
        return view('pages.client.post.index');
    }

    public function showPostCategoriesSlug($post_category_slug)
    {
        $postCategory = PostCategory::where('post_category_slug', $post_category_slug)->first();
        $posts = Post::where('id', $postCategory->id)->get();
        return view('pages.client.post.category_blog', compact('postCategory', 'posts'));
    }

    public function showPostInCategories($post_category_slug, $post_slug)
    {
        $postCategory = PostCategory::where('post_category_slug', $post_category_slug)->first();
        $post = Post::where('post_slug', $post_slug)->first();
        $getAllRelatedPost = Post::where('id', $postCategory->id)->whereNotIn('post_slug', [$post_slug])->take(3)->get();
        return view('pages.client.post.post_details', compact('post', 'postCategory', 'getAllRelatedPost'));
    }
}
