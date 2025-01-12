<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\Redirect;

class BlogController extends Controller
{
    public function index()
    {
        $getBlogs = Blog::join('blog_categories', 'blog_categories.id', 'blogs.blog_category_id')
            ->select(
                'blog_category_name',
                'blog_category_name_en',
                'blog_category_slug',
                'blog_title',
                'blog_slug',
                'blog_image',
                'blog_category_id'
            )
            ->orderBy('blogs.id', 'DESC')
            ->get();
        return view('pages.client.blog.index', compact('getBlogs'));
    }

    public function detail($blog_category_slug, $blog_slug)
    {
        $blogCategory = BlogCategory::firstWhere('blog_category_slug', $blog_category_slug);
        $blog = Blog::firstWhere('blog_slug', $blog_slug);
        $Comment = Comment::where('blog_id', $blog->id);
        $totalComment = count($Comment->get());
        $getPaginateComment = $Comment->paginate(2);
        $remaining = $totalComment - count($getPaginateComment);
        $getAllRelatedBlog = Blog::where('blog_category_id', $blogCategory->id)->whereNotIn('blog_slug', [$blog_slug]);
        $totalBlog = count($getAllRelatedBlog->get());
        $relatedBlog = $getAllRelatedBlog->take($totalBlog % 2 == 0 || $totalBlog > 6 ? 6 : $totalBlog - 1)->orderBy('id', 'DESC')->get();
        return view('pages.client.blog.details', compact('blogCategory', 'blog', 'getPaginateComment', 'remaining', 'totalComment', 'relatedBlog'));
    }
}
