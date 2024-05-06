@extends('layout_not_slider')
@section('content')
@section('title', '')
<!-- Blog Details Hero Begin -->
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Giới thiệu</h4>
                    <div class="breadcrumb__links">
                        <a href="{{URL::to('/')}}">Trang chủ</a>
                        <span>Giới thiệu</span>
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
                    <h2>Về chúng tôi</h2>
                    <ul>
                        <li>Với tâm huyết nâng cao chất lượng khám chữa bệnh và tạo điều kiện thuận lợi cho người dân ngày càng tiếp cận dễ dàng hơn các dịch vụ y tế chất lượng cao và hiện đại.</li>
                        
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
        
            <div class="col-lg-6">
                <div class="">
                    <img src="{{asset('frontend/img/logo-renew.jpg')}}" alt="">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="blog__details__content">
                    <div class="blog__details__text">
                        <p>Bạn cần tìm một nhà cung cấp dịch vụ xe Xquang kỹ thuật ? Thực hiện chỉ định XQ phục vụ cho nhu cầu khám ngoại? Hãy truy cập vào trang dịch vụ của chúng tôi. Chúng tôi hiện là công ty chính thức cung cấp dịch vụ XQ lưu động trên các Tỉnh Thành.
                            Từ giờ, khi cần thực hện các chỉ định về XQ cho công tác khám sức khỏe định kỳ ( bất kì tỉnh Thành nào), bạn hãy liên hệ với công ty chúng tôi bất cứ lúc nào..
                            SĐT tư vấn : 098 289 6642 ( Ms Mai)  
                            Từ năm 2009 đến nay, CÔNG TY TNHH ĐẦU TƯ TRANG THIẾT BỊ Y TẾ  NAM KHÁNH LINH đã triển khai cung cấp dịch vụ Xq lưu động trên khắp các Tỉnh Thành với tên gọi Xq Mobile. Và là công ty cung ứng chụp XQ lưu động chính thức trên toàn hệ thống các Tỉnh.
                            </p>
                    </div>
                    
                    
                </div>
            </div>
        
        </div>
    </div>
</section>
<!-- Blog Section End -->


@endsection