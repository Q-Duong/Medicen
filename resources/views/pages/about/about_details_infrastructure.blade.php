@extends('layout_not_slider')
@section('content')
@section('title', '')
<!-- Blog Details Hero Begin -->
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Cơ sở vật chất</h4>
                    <div class="breadcrumb__links">
                        <a href="{{URL::to('/')}}">Trang chủ</a>
                        <a href="{{URL::to('/gioi-thieu')}}">Giới thiệu</a>
                        <span>Cơ sở vật chất</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="blog-hero-simple spad">
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-9 text-center">
                <div class="blog__hero__text">
                    <h2>Cơ sở vật chất</h2>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Blog Details Hero End -->
<div class="container">
    <div class="row product__filter">
        @foreach($all_post as $key => $all_pst)
        <div class="col-lg-3 col-md-6 col-sm-6 mix img_pst ">
            <a  href="{{URL::to('/blog/'.$all_pst->post_slug)}}">
                <img src="{{URL::to('uploads/post/'.$all_pst->post_image)}}" class="img_post" alt="">
                <div class="post__item__text">
                    <h5>{{$all_pst->post_title}}</h5>
                </div>
            </a>
        
        </div>
        @endforeach
    </div>
</div>


@endsection