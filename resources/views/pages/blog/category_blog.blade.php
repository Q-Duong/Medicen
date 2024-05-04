@extends('layout_not_slider')
@section('content')
@section('title', 'Blog List - ')
<section>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Tin tức</h4>
                    <div class="breadcrumb__links">
                        <a href="{{URL::to('/')}}">Trang chủ</a>
                        <a href="{{URL::to('/blog-list')}}">Danh mục tin tức</a>
                        <span>Tin tức</span>
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
                <h2>Tin tức</h2>
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
            @foreach($post as $key => $pst)
            <div class="col-lg-4 col-md-6 col-sm-6">
                <div class="blog__item">
                    
                        <div class="blog__item__pic set-bg"
                            data-setbg="{{asset('uploads/post/'.$pst->post_image)}}"></div>
                        <div class="blog__item__text">
                            <span><img src="{{asset('frontend/img/icon/calendar.png')}}" alt=""> 16 February 2020</span>
                            <h5>{{$pst->post_title}}</h5>
                            <a href="{{URL::to('/blog/'.$pst->post_slug)}}">Xem tin</a>
                        </div>
                    
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Blog Section End -->


@endsection