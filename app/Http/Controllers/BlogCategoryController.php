<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Support\Facades\App;

class BlogCategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    private $locale;

    public function __construct()
    {
        $this->locale = App::getLocale();
    }

    public function index($blog_category_slug)
    {
        $blogCategory = BlogCategory::firstWhere('blog_category_slug', $blog_category_slug);
        $getAllBlog = Blog::where('blog_category_id', $blogCategory->id)->orderBy('id', 'DESC')->paginate(1);
        return view('pages.client.blog.category_index', compact('getAllBlog', 'blogCategory'));
    }
}
