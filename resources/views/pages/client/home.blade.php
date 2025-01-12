@extends('layouts.default')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('frontend/css/owl.carousel.min.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/banner.min.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('assets/css/main.built.css') }}" type="text/css" as="style" />
@endpush
@section('content')
    <div class="hero__slider owl-carousel">
        @foreach ($getAllSlider as $key => $slider)
            <div class="hero__items set-bg active" data-setbg="{{ asset('uploads/slider/' . $slider->slider_image) }}">
            </div>
        @endforeach
    </div>
    <section class="main-content">
        <div class="container">
            <div class="form-block">
                <div class="title-block">
                    <p class="title">Đăng ký</p>
                    <div class="border_bottom"></div>
                </div>
                <div class="form-section transition-hover">
                    <div class="form-content-title blue">
                        <i class="fas fa-x-ray"></i> Thông tin đặt xe X-Quang
                    </div>
                    <div class="form-content">
                        <form action="{{ route('order.clients.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-lg-7 col-md-7 centered">
                                    <div class="form-group @error('customer_name') has-error @enderror">
                                        <p>Thông tin liên hệ<span>*</span></p>
                                        <input type="text" class="input-control" name="customer_name"
                                            placeholder="Điền họ và tên" value="{{ old('customer_name') }}">
                                        @error('customer_name')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-5 col-md-5 centered">
                                    <div class="form-group @error('customer_phone') has-error @enderror">
                                        <p>&nbsp;</p>
                                        <input type="text" class="input-control" name="customer_phone"
                                            placeholder="Điền số điện thoại" value="{{ old('customer_phone') }}">
                                        @error('customer_phone')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('customer_address') has-error @enderror">
                                <p>Địa chỉ chụp<span>*</span></p>
                                <input type="text" class="input-control" name="customer_address"
                                    placeholder="Điền địa chỉ" value="{{ old('customer_address') }}">
                                @error('customer_address')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group @error('ord_start_day') has-error @enderror">
                                        <p>Ngày chụp<span>*</span></p>
                                        <input type="date" class="input-control" name="ord_start_day"
                                            placeholder="Từ ngày" value="{{ old('ord_start_day') }}">
                                        @error('ord_start_day')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group @error('ord_end_day') has-error @enderror">
                                        <p>&nbsp;</p>
                                        <input type="date" class="input-control" name="ord_end_day"
                                            placeholder="Đến ngày" value="{{ old('ord_end_day') }}">
                                        @error('ord_end_day')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('order_quantity') has-error @enderror">
                                <p>Số lượng<span>*</span></p>
                                <input type="text" class="input-control" name="order_quantity"
                                    placeholder="Điền số lượng chụp" value="{{ old('order_quantity') }}">
                                @error('order_quantity')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="label">Bộ phận chụp<span>*</span></label>
                                <select id="label" name="ord_select" class="input-control">
                                    <option value="Phổi (1 Tư thế)">Phổi</option>
                                    <option value="Cột sống thắt lưng (1 Tư thế)">Cột sống thắt lưng
                                    </option>
                                    <option value="Cột sống cổ (1 Tư thế)">Cột sống cổ</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                            <button type="submit" class="primary-btn-submit">Đăng ký</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="service-block">
            <div class="title-block">
                <p class="title">Dịch vụ</p>
                <div class="border_bottom"></div>
            </div>
            <section class="rs-cardsshelf-section">
                <div class="rs-cardsshelf-container">
                    <div class="service owl-carousel">
                        @foreach ($getAllService as $key => $service)
                            <div class="card-content transition-hover">
                                <a href="{{ URL::to('/dich-vu/' . $service->service_slug) }}">
                                    <img src="{{ asset('uploads/service/' . $service->service_image) }}"
                                        class="service-img-full"
                                        alt="{{ asset('uploads/service/' . $service->service_image) }}">
                                    <div class="card-content-info">
                                        <div class="card-content-header">
                                            {{ $service->service_title }}
                                        </div>
                                        <div class="card-content-desc">
                                            {{-- Nhận thêm AirPods khi mua Mac và Apple Pencil khi mua iPad, giảm 20% AppleCare+,
                                            và
                                            còn
                                            nhiều nữa. --}}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
        <div class="news-block">
            <div class="title-block">
                <p class="title">Tin tức</p>
                <div class="border_bottom"></div>
            </div>
            
        </div>
        {{-- <section class="wrap-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="image-wrap">
                        <img src="{{ URL::to('frontend/img/212.jpg') }}" class="img-wrap" alt="">
                    </div>
                    <div class="banner-content">
                        <div class="banner-logo">
                            <a href="{{ URL::to('/') }}" class="banner-logo-a"><img
                                    src="{{ asset('frontend/img/new-logo.jpg') }}" alt="Medicen"></a>
                        </div>
                        <h2 class="name">CTY TNHH ĐẦU TƯ TRANG THIẾT BỊ Y TẾ <br> NAM KHÁNH LINH</h2>

                        <div class="phone-wrap">
                            <span>(028) 6265 4188</span>
                        </div>
                        <div class="text">Thời gian làm việc</div>
                        <div class="time-wrap">
                            <div class="item"><span>Từ thứ hai - Thứ bảy </span></div>
                            <div class="item"><em class="mdi mdi-clock-outline"></em><span>07: 30 - 16: 30</span>
                            </div>
                        </div>
                        <div class="btn-booking">
                            <a href="{{ URL::to('/create-order') }}" value="submit" class="primary-btn-wistlist"><i
                                    class="fas fa-user-plus"></i> Đăng ký</a>
                        </div>
                    </div>
                </div>
            </div>
        </section> --}}
    </section>
@endsection
@push('js')
    <script src="{{ versionResource('frontend/js/owl.carousel.min.js') }}" defer></script>
    <script src="{{ versionResource('frontend/js/home.min.js') }}" defer></script>
@endpush
