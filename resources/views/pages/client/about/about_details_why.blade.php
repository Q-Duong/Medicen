@extends('layout_not_slider')
@section('content')
@section('title', '')
<!-- Blog Details Hero Begin -->
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Tại sao chọn chúng tôi</h4>
                    <div class="breadcrumb__links">
                        <a href="{{URL::to('/')}}">Trang chủ</a>
                        <a href="{{URL::to('/gioi-thieu')}}">Giới thiệu</a>
                        <span>Tại sao chọn chúng tôi</span>
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
                    <h2>Tại sao chọn chúng tôi</h2>
                    {{-- <ul>
                        <li>Với tâm huyết nâng cao chất lượng khám chữa bệnh và tạo điều kiện thuận lợi cho người dân ngày càng tiếp cận dễ dàng hơn các dịch vụ y tế chất lượng cao và hiện đại.</li>
                        
                    </ul> --}}
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Blog Details Hero End -->

<section class="blog-details spad">
    <div class="container">
        <div class="row d-flex justify-content-center">
        
            <div class="col-lg-6">
                <div class="">
                    <img src="{{asset('frontend/img/logo-renew.jpg')}}" alt="">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="blog__details__content">
                    <div class="blog__details__text">
                        <p>Hãy đến và trải nghiệm dịch vụ của công ty chúng tôi, bạn sẽ cảm nhận được tính chuyên nghiệp từ chất lượng dịch vụ, vận hành cho đến trả kết quả chính xác. Điều quan trọng tiếp là giá luôn ưu đãi.
                            Chúng tôi luôn đảm bảo cân bằng lợi ích giữa hai bên.
                        </p>
                    </div>
                    
                    
                </div>
            </div>
        
        </div>
    </div>
</section>
<!-- Blog Section End -->


@endsection