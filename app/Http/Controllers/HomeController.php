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
        // Tin tức mới nhất (3 bài) cuối trang chủ; chưa có bài đăng thì không hiện khối này
        $latestNews = \App\Models\News::published()->orderByDesc('published_at')->limit(3)->get();
    	return view('pages.client.home')->with(compact('getAllSlider','getAllService','latestNews'));
    }
}
