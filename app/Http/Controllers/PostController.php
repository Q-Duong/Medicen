<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Support\Facades\Redirect;

class PostController extends Controller
{
    public function add_post(){
        $post_category = PostCategory::orderBy('post_category_id','ASC')->get();
    	return view('admin.Post.add_post')->with(compact('post_category'));
    }

    public function list_post(){
        $all_post = Post::with('post_category')->orderBy('post_id','DESC')->get();
    	return view('admin.Post.list_post')->with(compact('all_post'));
    }

    public function upload_image_ck(Request $request){
        if($request->hasFile('upload')){
            $get_image = $request->file('upload');
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.',$get_name_image));
            $new_image =  $name_image.rand(0,99).'.'.$get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/content/'), $new_image);
            $url = asset('uploads/content/'. $new_image);
            return response()->json(['fileName' => $new_image,'uploaded' => 1,'url' => $url]);
        }
    }

    public function save_post(Request $request){
        $this->checkPostAdd($request);
    	$data = $request->all();
        $post = new Post();
    	$post->post_title = $data['post_title'];
        $post->post_slug = $data['post_slug'];
        $post->post_desc = $data['post_desc'];
        $post->post_content = $data['post_content'];
        $post->post_category_id = $data['post_category_id'];

        $get_image = $request->file('post_image');
        $name = $post->post_title;

        $check = Post::where('post_title',$name)->exists();
        if($check)
        {
            return Redirect()->back()->with('error','Bài đã tồn tại, Vui lòng kiểm tra lại.')->withInput();
        }
      
        if($get_image){
            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.',$get_name_image));
            $new_image =  $name_image.rand(0,99).'.'.$get_image->getClientOriginalExtension();
            $get_image->move(public_path('uploads/post/'), $new_image);
            $post->post_image=$new_image;
            $post->save();
            return Redirect()->back()->with('success','Thêm bài viết thành công');
        }else{
            return Redirect()->back()->with('error','Vui lòng thêm hình ảnh');
        }
    }
    
    public function edit_post($post_id){
        $post_category = PostCategory::orderBy('post_category_id','ASC')->get();
        $post = Post::find($post_id);
        return view('admin.Post.edit_post')->with(compact('post'))->with(compact('post_category'));
    }

    public function update_post(Request $request,$post_id){
        $this->checkPostUpdate($request);
        $data = $request->all();
        $post = Post::find($post_id);
    	$post->post_title = $data['post_title'];
        $post->post_slug = $data['post_slug'];
        $post->post_desc = $data['post_desc'];
        $post->post_content = $data['post_content'];
        $post->post_category_id = $data['post_category_id'];
        $get_image = $request->file('post_image');
      
        if($get_image){
            $post_image_old = $post->post_image;
            $path = public_path('uploads/post/');
            unlink($path.$post_image_old);

            $get_name_image = $get_image->getClientOriginalName();
            $name_image = current(explode('.',$get_name_image));
            $new_image =  $name_image.rand(0,99).'.'.$get_image->getClientOriginalExtension();
            $get_image->move($path,$new_image);
            $post->post_image=$new_image;
            
        }
        $post->save();
        return Redirect::to('admin/post/list')->with('success','Cập nhật bài viết thành công');
    }

    public function delete_post($post_id){
        $post = Post::find($post_id);
        $post_image = $post->post_image;
        if($post_image){
            unlink(public_path('uploads/post/').$post_image);
        }
        $post->delete();
        return Redirect()->back()->with('success','Xóa bài viết thành công');
    }
    //Front End

    public function show_post_category_home(Request $request,$post_slug){
        $catepost = PostCategory::where('post_category_slug',$post_slug)->take(1)->get();
        foreach($catepost as $key =>$cate){
            $cate_id = $cate->post_category_id;
        }
        $post = Post::with('post_category')->where('post_category_id',$cate_id)->paginate(12);

        return view('pages.blog.category_blog')->with(compact('post'));
    }

    public function show_post_home(Request $request,$post_slug){
        $post = Post::with('post_category')->where('post_slug',$post_slug)->take(1)->get();

        foreach($post as $key =>$pst){
            $cate_id = $pst->post_category_id;
            $title = $pst->post_title;
            $post_category_id = $pst->post_category_id;
        }

        $related = Post::with('post_category')->where('post_category_id',$post_category_id)->whereNotIn('post_slug',[$post_slug])->take(3)->get();

        return view('pages.blog.blog_details')->with(compact('post','title','related'));
    }

    //Validation
    public function checkPostUpdate(Request $request){
        $this-> validate($request,
        [
            'post_title' => 'required',
            'post_slug' => 'required',
            //'post_desc' => 'required',
            'post_content' => 'required',
        ],
        [
            'post_title.required' =>'Vui lòng điền thông tin',
            'post_slug.required' =>'Vui lòng điền thông tin',
            'post_desc.required' =>'Vui lòng điền thông tin',
            'post_content.required' =>'Vui lòng điền thông tin',
        ]);
    }

    public function checkPostAdd(Request $request){
        $this-> validate($request,
        [
            'post_title' => 'required',
            'post_slug' => 'required',
            'post_image' => 'required',
            // 'post_desc' => 'required',
            'post_content' => 'required',
        ],
        [
            'post_title.required' =>'Vui lòng điền thông tin',
            'post_slug.required' =>'Vui lòng điền thông tin',
            'post_image.required' =>'Vui lòng theem hình ảnh',
            'post_desc.required' =>'Vui lòng điền thông tin',
            'post_content.required' =>'Vui lòng điền thông tin',
        ]);
    }
}
