<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Service;
use App\Models\Slider;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(){
        $getAllSlider = Slider::orderBy('id','ASC')->select(['slider_image',])->get();
        $getAllService = Service::orderBy('id','ASC')->get();
        $getAllPost = Post::inRandomOrder('id')->limit(4)->get();
    	return view('pages.home')->with(compact('getAllSlider','getAllService','getAllPost'));
    }

    public function blog_list(){
    	return view('pages.blog.blog_list');
    }
}
