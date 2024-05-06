@extends('layouts.default')
@section('content')
@section('title', $service->service_title . ' - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>{{ $service->service_title }}</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home.index') }}">Trang chủ</a>
                        <a href="javascript:;">Dịch vụ</a>
                        <span>{{ $service->service_title }}</span>
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
                    <h2>{{ $service->service_title }}</h2>
                    <ul>
                        <li>Nam Khánh Linh</li>
                        <li>{{ $service->created_at }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="blog-details spad">
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-12">
                <div class="blog__details__pic">
                    <img src="{{ asset('uploads/service/' . $service->service_image) }}" alt="">
                </div>
            </div>
            <div class="col-lg-8">
                <div class="blog__details__content">

                    <div class="blog__details__text">
                        <p>{!! $service->service_content !!}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
