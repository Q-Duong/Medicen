@extends('layout_not_slider')
@section('content')
@section('title', 'Blog - ')
<section>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Danh mục tin tức</h4>
                    <div class="breadcrumb__links">
                        <a href="{{URL::to('/')}}">Trang chủ</a>
                        <span>Danh mục tin tức</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="breadcrumb-blog set-bg" >
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h2>Danh Mục Tin Tức</h2>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->
<div class="container">
    <div class="border-style"></div>
</div>
<!-- Blog Section Begin -->
<section class="blog spad">
    <div class="container">
        <div class="row">
            @foreach($getAllCategoryPost as $key => $category_post)
            <div class="col-lg-4 col-md-6 col-sm-6">
                <div class="blog__item">
                    <!-- <div class="blog__item__pic set-bg"
                            data-setbg="{{asset('frontend/img/hero_homepod_lockup__4j6sxrq610y2_large.jpg')}}"></div> -->
                    <div class="blog__item__text">
                        <span><img src="img/icon/calendar.png" alt="">-------------------</span>
                        <h5>{{$category_post->category_post_name}}</h5>
                        <a href="{{asset(URL::to('/blogs/'.$category_post->category_post_slug))}}">Xem danh mục</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Blog Section End -->


@endsection