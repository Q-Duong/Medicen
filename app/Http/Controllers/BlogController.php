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
        $blogs = Blog::orderBy('blogs.id', 'DESC')
            ->paginate(10);
        return view('pages.client.blog.index', compact('blogs'));
    }
}
