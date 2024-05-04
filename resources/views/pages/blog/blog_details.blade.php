@extends('layout_not_slider')
@section('content')
@section('title', '')
<!-- Blog Details Hero Begin -->
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>{{$title}}</h4>
                    <div class="breadcrumb__links">
                        <a href="{{URL::to('/')}}">Trang chủ</a>
                        <a href="{{URL::to('/blog-list')}}">Danh mục tin tức</a>
                        <span>{{$title}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="blog-hero spad">
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-9 text-center">
                <div class="blog__hero__text">
                    <h2>{{$title}}</h2>
                    <ul>
                        <li>By Deercreative</li>
                        <li>February 21, 2019</li>
                        <li>8 Comments</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Blog Details Hero End -->

<section class="blog-details spad">
    <div class="container">
        <div class="row d-flex justify-content-center">
        @foreach($post as $key => $pst)
            <div class="col-lg-12">
                <div class="blog__details__pic">
                    <img src="{{asset('uploads/post/'.$pst->post_image)}}" alt="">
                </div>
            </div>
            <div class="col-lg-8">
                <div class="blog__details__content">
                    <div class="blog__details__share">
                        <span>share</span>
                        <ul>
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#" class="twitter"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#" class="youtube"><i class="fa fa-youtube-play"></i></a></li>
                            <li><a href="#" class="linkedin"><i class="fa fa-linkedin"></i></a></li>
                        </ul>
                    </div>
                    <div class="blog__details__text">
                        <p>{!! $pst->post_content !!}</p>
                    </div>
                    
                    <div class="blog__details__comment">
                        <h4>Leave A Comment</h4>
                        <form action="#">
                            <div class="row">
                                <div class="col-lg-4 col-md-4">
                                    <input type="text" placeholder="Name">
                                </div>
                                <div class="col-lg-4 col-md-4">
                                    <input type="text" placeholder="Email">
                                </div>
                                <div class="col-lg-4 col-md-4">
                                    <input type="text" placeholder="Phone">
                                </div>
                                <div class="col-lg-12 text-center">
                                    <textarea placeholder="Comment"></textarea>
                                    <button type="submit" class="site-btn">Post Comment</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    </div>
</section>
<!-- Blog Section End -->

<section class="breadcrumb-blog set-bg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h2 style="color:#111111;font-size:40px;">Bài viết liên quan</h2>
            </div>
        </div>
    </div>
</section>
<section class="blog spad">
    <div class="container">
        <div class="row">
            @foreach($related as $key => $post_ralated)
            <div class="col-lg-4 col-md-6 col-sm-6">
                <div class="blog__item">
                    <a href="{{URL::to('/blog/'.$post_ralated->post_slug)}}">
                        <div class="blog__item__pic set-bg"
                            data-setbg="{{asset('uploads/post/'.$post_ralated->post_image)}}"></div>
                        <div class="blog__item__text">
                            <span><img src="img/icon/calendar.png" alt=""> 16 February 2020</span>
                            <h5>{{$post_ralated->post_title}}</h5>
                            <a href="#">Read More</a>
                        </div>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


@endsection