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
                        <a href="javascript:;">Dịch vụ</a>
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
                        <li>Nam Khánh Linh</li>
                        <li>{{$created_at}}</li>
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
        @foreach($service as $key => $ser)
            <div class="col-lg-12">
                <div class="blog__details__pic">
                    <img src="{{asset('uploads/service/'.$ser->service_image)}}" alt="">
                </div>
            </div>
            <div class="col-lg-8">
                <div class="blog__details__content">
                    
                    <div class="blog__details__text">
                        <p>{!! $ser->service_content !!}</p>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    </div>
</section>
<!-- Blog Section End -->

@endsection