@extends('layouts.default')
@section('content')
@section('title', $postCategory->post_category_name . ' - ')
<section>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Tin tức</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home.index') }}">Trang chủ</a>
                        <a href="{{ route('blog.category') }}">Danh mục tin tức</a>
                        <span>{{ $postCategory->post_category_name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="breadcrumb-blog set-bg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h2>Tin tức</h2>
            </div>
        </div>
    </div>
</section>
<div class="container">
    <div class="border-style"></div>
</div>
<section class="blog spad">
    <div class="container">
        <div class="row">
            @foreach ($posts as $key => $post)
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="blog__item">
                        <div class="blog__item__pic set-bg" data-setbg="{{ asset('uploads/post/' . $post->post_image) }}">
                        </div>
                        <div class="blog__item__text">
                            <span><img src="{{ asset('frontend/img/icon/calendar.png') }}" alt=""> 16 February
                                2020</span>
                            <h5>{{ $post->post_title }}</h5>
                            <a href="{{ route('blog.post_in_category', [$postCategory->post_category_slug, $post->post_slug]) }}">Xem tin</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
