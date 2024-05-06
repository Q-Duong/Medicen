<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Medicen">
    <meta name="keywords" content="Medicen, Y tế, Thiết bị, Xe">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Medicen</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel='shortcut icon' href="{{asset('frontend/img/new-logo.jpg')}}" />
    <!-- Css Styles -->
    <link rel="stylesheet" href="{{asset('frontend/css/bootstrap.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/font-awesome.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/elegant-icons.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/magnific-popup.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/nice-select.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/owl.carousel.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/slicknav.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/style.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('frontend/css/sweetalert.css')}}">
    <link rel="stylesheet" href="{{asset('frontend/fontawesome-free-5.15.4-web/css/all.css')}}">
    <link rel="stylesheet" href="{{asset('frontend/css/lightgallery.min.css')}}">
    <link rel="stylesheet" href="{{asset('frontend/css/lightslider.css')}}">
    <link rel="stylesheet" href="{{asset('frontend/css/prettify.css')}}">
    <link rel="stylesheet" href="{{asset('frontend/css/jquery-ui.css')}}">
</head>

<body>
    <!-- Page Preloder -->
    <div id="preloder">
        <div class="loader"></div>
    </div>

    <!-- Offcanvas Menu Begin -->
    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="offcanvas__option">
            <div class="offcanvas__links">
                <a href="#">Sign in</a>
                <a href="#">FAQs</a>
            </div>
            {{-- <div class="offcanvas__top__hover">
                <span><i class="far fa-user-circle"></i> Đăng ký
                    <i class="arrow_carrot-down"></i>
                </span>
                <ul>
                    <a href="{{URL::to('/create-order')}}">
                        <li><i class="fas fa-user-plus"></i> Đăng ký</li>
                    </a>
                </ul>
            </div> --}}
        </div>
        <div class="offcanvas__nav__option">
            <a href="#" class="search-switch"><img src="{{asset('frontend/img/icon/search.png')}}" alt=""></a>
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
                    <div class="col-lg-6 col-md-7">
                        <div class="header__top__left">
                            <p></p>
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="header__top__right">
                            <div class="header__top__links">
                                <a href="{{URL::to('/create-order')}}" class="primary-btn-top blue">Đăng ký</a>
                                <a href="tel:098 289 6642" class="primary-btn-top red">Tư vấn : 098 289 6642</a>
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
                            <a href="{{URL::to('/')}}"><img src="{{asset('frontend/img/new-logo.jpg')}}" class="img_logo" alt=""></a>
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8">
                        <nav class="header__menu mobile-menu">
                            <ul>
                                <li class="nav-item"><a href="{{URL::to('/gioi-thieu')}}">Giới thiệu</a>
                                    <ul class="dropdown">
                                        <li>
                                            <a href="{{asset(URL::to('/gioi-thieu'))}}">Giới thiệu
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{asset(URL::to('/gioi-thieu/tai-sao-chon-chung-toi'))}}">Tại sao chọn chúng tôi
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{asset(URL::to('/gioi-thieu/co-so-vat-chat'))}}">Cơ sở vật chất
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="javascript:;">Dịch vụ</a>
                                    <ul class="dropdown">
                                        @foreach($getAllService as $key => $service)
                                        <li><a
                                                href="{{asset(URL::to('/dich-vu/'.$service->service_slug))}}">{{$service->service_title}}</a>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="{{URL::to('/blog-list')}}">Tin tức</a>
                                    <ul class="dropdown">
                                        @foreach($getAllPostCategory as $key => $cate_post)
                                            <li><a href="{{asset(URL::to('/blogs/'.$cate_post->post_category_slug))}}">{{$cate_post->post_category_name}}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="{{URL::to('/lien-he')}}">Liên hệ</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="col-lg-2 col-md-2">
                        <div class="header__nav__option">
                            <a href="#" class="search-switch"><img src="{{asset('frontend/img/icon/search.png')}}" alt=""></a>
                        </div>
                    </div>
                </div>
                <div class="canvas__open"><i class="fa fa-bars"></i></div>
            </div>
        </div>
    </header>
    <!-- Header Section End -->

    <!-- Hero Section Begin -->
    <div class="bodyContainer">
        
            <div class="slider">
                <div class="slides">
                <!--radio buttons start-->
                <input type="radio" name="radio-btn" id="radio1">
                <input type="radio" name="radio-btn" id="radio2">
                <input type="radio" name="radio-btn" id="radio3">
                <input type="radio" name="radio-btn" id="radio4">
                <!--radio buttons end-->
                <!--slide images start-->
                <div class="slide first">
                    <img src="{{asset('uploads/slider/slide-1139.jpg')}}" alt="">
                </div>
                <div class="slide">
                    <img src="{{asset('uploads/slider/slide-2136.jpg')}}" alt="">
                </div>
                <div class="slide">
                    <img src="{{asset('uploads/slider/slide-3132.jpg')}}" alt="">
                </div>
                <div class="slide">
                    <img src="{{asset('uploads/slider/slide-3132.jpg')}}" alt="">
                </div>
                <!--slide images end-->
                <!--automatic navigation start-->
                <div class="navigation-auto">
                    <div class="auto-btn1"></div>
                    <div class="auto-btn2"></div>
                    <div class="auto-btn3"></div>
                    <div class="auto-btn4"></div>
                </div>
                <!--automatic navigation end-->
                </div>
                <!--manual navigation start-->
                <div class="navigation-manual">
                <label for="radio1" class="manual-btn"></label>
                <label for="radio2" class="manual-btn"></label>
                <label for="radio3" class="manual-btn"></label>
                <label for="radio4" class="manual-btn"></label>
                </div>
                <!--manual navigation end-->
            </div>
        
    
        <section class="product spad">
            <div class="container">
                @yield('content')
            </div>
        </section>
        <!-- Product Section End -->
    </div>

    <!-- Footer Section Begin -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="footer__about">
                        <div class="footer__logo">
                            <a href="{{URL::to('/')}}">
                                <img src="{{asset('frontend/img/new-logo.jpg')}}" class="logo1" alt="">  
                            </a>
                        </div>
                        <p>Khách hàng là trọng tâm của mô hình kinh doanh độc đáo của chúng tôi, bao gồm cả thiết kế.</p>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <h6>Dịch vụ</h6>
                        <ul>
                        @foreach($getAllService as $key => $service)
                                <li><a href="{{asset(URL::to('/dich-vu/'.$service->service_slug))}}">
                                    {{$service->service_title}}</a>
                                </li>
                        @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <h6>Tin tức</h6>
                        <ul>
                        @foreach($getAllPostCategory as $key => $cate_post)
                            <li><a href="{{asset(URL::to('/blogs/'.$cate_post->post_category_slug))}}">{{$cate_post->post_category_name}}</a>
                            </li>
                        @endforeach
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 offset-lg-1 col-md-6 col-sm-6">
                    <div class="footer__widget">
                        <h6>Liện với chúng tôi</h6>
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
                <div class="col-lg-6 d-none d-lg-block footer__img"></div>
            </div>    
        </div>
    </footer>
    <!-- Footer Section End -->

    <!-- Search Begin -->
    <div class="search-model">
        <div class="d-flex align-items-center justify-content-center">
            <div class="overlay"></div>
            <form action="{{URL::to('/search')}}" method="POST" autocomplete="off" class="search-model-form">
                {{csrf_field()}}
                <div class="input_container">
                    <img src="{{asset('frontend/img/icon/search-icon.svg')}}" class="input_img">
                    <input type="text" id="keywords" id="search-input" name="keywords_submit" placeholder="Tìm kiếm sản phẩm" class="input">
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
    <a id="buttonZalo" href="https://zalo.me/4496283275293639849" target="_blank" rel="noopener nofollow" class="btn-zalo-chat"><img src="{{asset('frontend/img/icon/icon-zalo.png')}}" alt="Zalo Button"></a>

    <script src="https://sp.zalo.me/plugins/sdk.js"></script>

    <!-- Js Plugins -->
    <script src="{{asset('frontend/js/jquery-3.3.1.min.js')}}"></script>
    <script src="{{asset('frontend/js/bootstrap.min.js')}}"></script>
    {{-- <script src="{{asset('public/frontend/js/jquery.nice-select.min.js')}}"></script> --}}
    <script src="{{asset('frontend/js/jquery.nicescroll.min.js')}}"></script>
    <script src="{{asset('frontend/js/jquery.magnific-popup.min.js')}}"></script>
    <script src="{{asset('frontend/js/jquery.countdown.min.js')}}"></script>
    <script src="{{asset('frontend/js/jquery.slicknav.js')}}"></script>
    <script src="{{asset('frontend/js/mixitup.min.js')}}"></script>
    <script src="{{asset('frontend/js/owl.carousel.min.js')}}"></script>
    <script src="{{asset('frontend/js/main.js')}}"></script>
    <script src="{{asset('frontend/js/sweetalert.min.js')}}"></script>
    <script src="{{asset('frontend/js/lightgallery-all.min.js')}}"></script>
    <script src="{{asset('frontend/js/lightslider.js')}}"></script>
    <script src="{{asset('frontend/js/prettify.js')}}"></script>
    <script src="{{asset('frontend/js/jquery.scrollUp.min.js')}}"></script>
    <script src="{{asset('frontend/js/delete_wistlists.min.js')}}"></script>
    <script src="{{asset('frontend/js/add_wistlists.min.js')}}"></script>
    <script src="{{asset('frontend/js/apple.min.js')}}"></script>
    <script src="{{asset('frontend/js/jquery-ui.js')}}"></script>

    <script type="text/javascript">
        $('.nav-item').on('click',function(){

        //Remove any previous active classes
        $('.nav-item').removeClass('active');

        //Add active class to the clicked item
        $(this).addClass('active');
        });
    </script>

    {{-- <script>

        let slideIndex = 0;
        showSlides();

        function showSlides() {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";  
            }
            slideIndex++;
            if (slideIndex > slides.length) {slideIndex = 1}    
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            slides[slideIndex-1].style.display = "block";  
            dots[slideIndex-1].className += " active";
            setTimeout(showSlides, 2000); // Change image every 2 seconds
        }


        // let slideIndex = 1;
        // showSlides(slideIndex);
        
        // function plusSlides(n) {
        // showSlides(slideIndex += n);
        // }
        
        // function currentSlide(n) {
        // showSlides(slideIndex = n);
        // }
        
        // function showSlides(n) {
        //     let i;
        //     let slides = document.getElementsByClassName("mySlides");
        //     let dots = document.getElementsByClassName("dot");
        //     if (n > slides.length) {slideIndex = 1}    
        //     if (n < 1) {slideIndex = slides.length}
        //     for (i = 0; i < slides.length; i++) {
        //         slides[i].style.display = "none";  
        //     }
        //     for (i = 0; i < dots.length; i++) {
        //         dots[i].className = dots[i].className.replace(" active", "");
        //     }
        //     slides[slideIndex-1].style.display = "block";  
        //     dots[slideIndex-1].className += " active";
        // }
        
    </script> --}}

    <script type="text/javascript">
        var counter = 1;
        setInterval(function(){
          document.getElementById('radio' + counter).checked = true;
          counter++;
          if(counter > 4){
            counter = 1;
          }
        }, 4000);
    </script>

    <script type="text/javascript">
        $( function() {
            $('#datepicker').datepicker({
                prevText:"Tháng trước",
                nextText:"Tháng sau",
                dateFormat:"dd/mm/yy",
                dayNamesMin: [ "T2", "T3", "T4", "T5", "T6", "T7", "CN" ],
                duration: "slow",
                minDate:1,
            });
            $('#datepicker2').datepicker({
                prevText:"Tháng trước",
                nextText:"Tháng sau",
                dateFormat:"dd/mm/yy",
                dayNamesMin: [ "T2", "T3", "T4", "T5", "T6", "T7", "CN" ],
                duration: "slow",
                minDate:1,
            });
            $('.date-icon').on('click', function() {
                $('#datepicker').focus();
            });
            $('.date-icon2').on('click', function() {
                $('#datepicker2').focus();
            })
        });
    </script>
    

    <script type="text/javascript">
        $('#keywords').keyup(function(){
            var query = $(this).val();

            if(query != '')
                {
                var _token = $('input[name="_token"]').val();

                $.ajax({
                    url:"{{url('/autocomplete-ajax')}}",
                    method:"POST",
                    data:{query:query, _token:_token},
                    success:function(data){
                    $('#search_ajax').fadeIn();  
                        $('#search_ajax').html(data);
                    }
                });

                }else{

                    $('#search_ajax').fadeOut();  
                }
        });

        $(document).on('click', '.li_search_ajax', function(){  
            $('#keywords').val($(this).text());  
            $('#search_ajax').fadeOut();  
        }); 
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert-success').fadeOut('fast');
            }, 3000);
        });
    </script>
</body>

</html>