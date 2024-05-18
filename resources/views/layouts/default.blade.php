<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Medicen">
    <meta name="keywords" content="Medicen, Y tế, Thiết bị, Xe">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')Medicen</title>
    <!-- #FAVICONS -->
    <link rel='shortcut icon' href="{{ asset('frontend/img/new-logo.jpg') }}" type="image/x-icon">
    <link rel='icon' href="{{ asset('frontend/img/new-logo.jpg') }}" type="image/x-icon">
    <link rel='canonical' href="https://medicen.vn">
    <!-- Google Font -->
    <link rel="preload stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <!-- Css Styles -->
    <link rel="stylesheet" href="{{ versionResource('frontend/css/bootstrap.min.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/jquery-ui.min.css') }}" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/font-awesome.min.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/elegant-icons.min.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/slicknav.min.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/style.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/fontawesome-free-5.15.4-web/css/all.min.css') }}" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/prettify.css') }}" as="style">
    <link href="{{ versionResource('assets/css/overview.built.css') }}" rel='stylesheet' type='text/css' as="style"/>

    @stack('css')
</head>

<body>
    <!-- Offcanvas Menu Begin -->
    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="offcanvas__nav__option">
            <a href="javascript:;" class="search-switch"><img src="{{ asset('frontend/img/icon/search.png') }}"
                    class="img-search" alt="search"></a>
        </div>
        <div id="mobile-menu-wrap"></div>
        <div class="offcanvas__text">
            <p></p>
        </div>
    </div>
    <!-- Offcanvas Menu End -->

    <!-- Header Section Begin -->
    <header class="header">
        <div class="header__top">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="header__top__right">
                            <div class="header__top__links">
                                <a href="{{ route('order.clients.create') }}" class="primary-btn-top blue">Đăng ký</a>
                                <a href="tel:098 708 7230" class="primary-btn-top red">Tư vấn : 0987.087.230</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bodyContainer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-2 col-md-2">
                        <div class="header__logo">
                            <a href="{{ route('home.index') }}"><img src="{{ asset('frontend/img/new-logo.jpg') }}"
                                    class="img_logo" alt="Medicen"></a>
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8">
                        <nav class="header__menu mobile-menu">
                            <ul>
                                <li class="nav-item"><a href="{{ URL::to('/gioi-thieu') }}">Giới thiệu</a>
                                    <ul class="dropdown">
                                        <li>
                                            <a href="">Giới thiệu
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="javascript:;">Dịch vụ</a>
                                    <ul class="dropdown">
                                        @foreach ($getAllService as $key => $service)
                                            <li><a
                                                    href="{{ route('service.show',$service->service_slug) }}">{{ $service->service_title }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="{{ route('blog.category') }}">Tin tức</a>
                                    <ul class="dropdown">
                                        @foreach ($getAllPostCategory as $key => $category)
                                            <li><a
                                                    href="{{ route('blog.category_slug', $category->post_category_slug) }}">{{ $category->post_category_name }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="{{ route('contact.show') }}">Liên hệ</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="col-lg-2 col-md-2">
                        <div class="header__nav__option">
                            <a href="javascript:;" class="search-switch"><img
                                    src="{{ asset('frontend/img/icon/search.png') }}" class="img-search"
                                    alt="search"></a>
                        </div>
                    </div>
                </div>
                <div class="canvas__open"><i class="fa fa-bars"></i></div>
            </div>
        </div>
    </header>

    <div class="bodyContainer">
        @yield('content')
    </div>

    {{-- <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="footer__about">
                        <div class="footer__logo">
                            <a href="{{ URL::to('/') }}">
                                <img src="{{ asset('frontend/img/logo-footer.png') }}" class="logo1" alt="Medicen">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <p>Dịch vụ</p>
                        <ul>
                            @foreach ($getAllService as $key => $service)
                                <li><a href="{{ asset(URL::to('/dich-vu/' . $service->service_slug)) }}">
                                        {{ $service->service_title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <p>Tin tức</p>
                        <ul>
                            @foreach ($getAllPostCategory as $key => $cate_post)
                                <li><a
                                        href="{{ asset(URL::to('/blogs/' . $cate_post->post_category_slug)) }}">{{ $cate_post->post_category_name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 offset-lg-1 col-md-6 col-sm-6">
                    <div class="footer__widget">
                        <p>Liên hệ với chúng tôi</p>
                        <div class="footer__newslatter">
                            <p>Hãy là người đầu tiên biết về hàng mới xuất hiện, xem sách, bán hàng và quảng cáo!</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="footer__buttom">
                </div>
                <div class="col-7 col-lg-4 ">
                    <div class="footer__copyright__text">
                        <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                        <p>Copyright ©
                            <script>
                                document.write(new Date().getFullYear());
                            </script>
                            Quốc Dương. All rights reserved.
                        </p>
                        <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                    </div>
                </div>
            </div>
        </div>
    </footer> --}}

    <div class="container">
        <footer class="footer">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="footer__widget">
                        <p>Dịch vụ</p>
                        <ul>
                            @foreach ($getAllService as $key => $service)
                                <li><a href="{{ route('service.show', $service->service_slug) }}">
                                        {{ $service->service_title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="footer__widget">
                        <p>Tin tức</p>
                        <ul>
                            @foreach ($getAllPostCategory as $key => $category)
                                <li><a
                                        href="{{ route('blog.category_slug', $category->post_category_slug) }}">{{ $category->post_category_name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 col-sm-12">
                    <div class="footer__widget">
                        <p>Thông tin liên hệ </p>
                        <div class="footer__newslatter">
                            <p>Hotline:<a href="tel:0987087230"> 0987.087.230 (Ms.Quyên)</a></p>
                            <p>Email:<a href="mailto:medicen.company@gmail.com"> medicen.company@gmail.com</a></p>
                            <p>Địa chỉ:<a target="_blank" href="https://www.google.com/maps/dir//C%C3%94NG+TY+TNHH+%C4%90%E1%BA%A6U+T%C6%AF+V%C3%80+TRANG+THI%E1%BA%BET+B%E1%BB%8A+Y+T%E1%BA%BE+NAM+KH%C3%81NH+LINH+S%E1%BB%91+59+%C4%90%C6%B0%E1%BB%9Dng+s%E1%BB%91+9+B%C3%ACnh+H%C6%B0ng+B%C3%ACnh+Ch%C3%A1nh,+Th%C3%A0nh+ph%E1%BB%91+H%E1%BB%93+Ch%C3%AD+Minh/@10.719258,106.660787,16z/data=!4m8!4m7!1m0!1m5!1m1!1s0x31752e3bc7e767e5:0x169aca0c83b94fdb!2m2!1d106.660787!2d10.719258?entry=ttu">59 Đường số 9, KDC Nam Sài Gòn - Thế Kỷ 21, Xã Bình Hưng, Huyện Bình Chánh, TP.HCM <span class="link-maps"> ( Xem bản đồ )</span></a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="footer__buttom">
                </div>
                <div class="col-12">
                    <div class="footer__copyright__text">
                        <p>Copyright © 2023 Medicen. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <!-- Footer Section End -->

    <!-- Search Begin -->
    <div class="search-model">
        <div class="d-flex align-items-center justify-content-center">
            <div class="overlay"></div>
            <form action="{{ URL::to('/search') }}" method="POST" autocomplete="off" class="search-model-form">
                {{ csrf_field() }}
                <div class="input_container">
                    <img src="{{ asset('frontend/img/icon/search-icon.svg') }}" class="input_img" atl="search">
                    <input type="text" id="keywords" id="search-input" name="keywords_submit"
                        placeholder="Tìm kiếm sản phẩm" class="input">
                    <div class="search-close-switch">+</div>
                </div>
                <div class="search_seggest">
                    <p>Cụm từ tìm kiếm phổ biến</p>
                </div>

                <div id="search_ajax"></div>
            </form>
        </div>
    </div>
    <!-- Search End -->

    <!-- scrollUp -->
    <a id="button"></a>
    <!-- scrollUp End -->

    <!-- Noti Popup -->
    <div class="notifications-popup-success">
        <div class="notifications-content">
            <div class="notifications-icon">
            </div>
            <div class="notifications-message">
                <span class="message-title">Thông báo !</span>
                <span class="message-text"></span>
            </div>
        </div>
        <i class="fas fa-times notifications-close"></i>
    </div>
    <div class="notifications-popup-error">
        <div class="notifications-content">
            <div class="notifications-icon">
            </div>
            <div class="notifications-message">
                <span class="message-title">Thông báo !</span>
                <span class="message-text"></span>
            </div>
        </div>
        <i class="fas fa-times notifications-close"></i>
    </div>
    <!-- Noti Popup -->
    <div class="loader-over">
        <span class="loader"></span>
    </div>
    <a id="buttonZalo" href="https://zalo.me/4496283275293639849" target="_blank" rel="noopener nofollow"
        class="btn-zalo-chat"><img src="{{ asset('frontend/img/icon/icon-zalo.png') }}" class="img-zalo"
            alt="Medicen"></a>

   {{-- <script src="https://sp.zalo.me/plugins/sdk.js"></script> --}}
    <!-- Js Plugins -->
    <script src="{{ versionResource('frontend/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ versionResource('frontend/js/bootstrap.min.js') }}"></script>
    <script src="{{ versionResource('frontend/js/jquery-ui.min.js') }}" defer ></script>
    <script src="{{ versionResource('frontend/js/jquery.slicknav.min.js') }}" ></script>
    <script src="{{ versionResource('frontend/js/main.js') }}" ></script>
    <script src="{{ versionResource('frontend/js/jquery.nicescroll.min.js') }}" defer ></script>
    <script src="{{ versionResource('frontend/js/prettify.min.js') }}" defer ></script>
    @stack('js')
</body>
</html>
