<!DOCTYPE html>

<head>
    <title>Medicen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="Medicen, Y tế, Thiết bị, Xe">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel='shortcut icon' href="{{asset('frontend/img/new-logo.jpg')}}" />
    <script type="application/x-javascript">
    addEventListener("load", function() {
        setTimeout(hideURLbar, 0);
    }, false);

    function hideURLbar() {
        window.scrollTo(0, 1);
    }
    </script>
    <!-- bootstrap-css -->
    <link rel="stylesheet" href="{{ versionResource('backend/css/bootstrap.min.css') }} " as="style">
    <!-- //bootstrap-css -->
    <!-- Custom CSS -->
    <link href="{{ versionResource('backend/css/style.css') }}" rel='stylesheet' type='text/css' as="style"/>
    <link href="{{ versionResource('backend/css/style-responsive.css') }}" rel="stylesheet" as="style"/>
    <link href="{{ versionResource('backend/css/jquery.dataTables.min.css') }}" rel="stylesheet" as="style"/>
    <link href="{{ versionResource('backend/css/responsive-jqueryui.min.css') }}" rel="stylesheet" as="style"/>
    <link href="{{ versionResource('backend/css/themes-base-jquery-ui.css') }}" rel="stylesheet" as="style"/>
    
    <!-- font CSS -->
    {{-- <link
        href='//fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic'
        rel='stylesheet' type='text/css'> --}}
    <!-- font-awesome icons -->
    <link rel="stylesheet" href="{{ versionResource('backend/css/font.css') }}" type="text/css" as="style"/>
    <link href="{{ versionResource('backend/css/font-awesome.css') }}" rel="stylesheet" as="style" />
    {{-- <link rel="stylesheet" href="{{ versionResource('backend/css/morris.css') }}" type="text/css" />  --}}
    <!-- calendar -->
    <link rel="stylesheet" href="{{ versionResource('backend/css/monthly.css') }}" as="style" />
    <!-- //calendar -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css" as="style" />
    <!-- //font-awesome icons -->
    <link rel="stylesheet" href="{{ versionResource('backend/fontawesome-free-5.15.4-web/css/all.css') }}" as="style" />
    <!-- //select2 -->
    <link href="{{ versionResource('backend/css/select2.min.css') }}" rel="stylesheet" as="style"/>
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet" />
</head>

<body>
    <section id="container">
        <!--header start-->
        <header class="header fixed-top clearfix">
            <!--logo start-->
            <div class="brand">
                <a href="{{ route('dashboard.index') }}" class="logo">
                    Medicen
                </a>
                <div class="sidebar-toggle-box">
                    <div class="fa fa-bars"></div>
                </div>
            </div>
            <!--logo end-->
            <div class="nav notify-row" id="top_menu">
                <!--  notification start -->
                <ul class="nav top-menu">
                    <!-- settings start -->
                    <li class="dropdown">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <i class="fa fa-tasks"></i>
                            <span class="badge bg-success">8</span>
                        </a>
                        <ul class="dropdown-menu extended tasks-bar">
                            <li>
                                <p class="">You have 8 pending tasks</p>
                            </li>
                            <li>
                                <a href="#">
                                    <div class="task-info clearfix">
                                        <div class="desc pull-left">
                                            <h5>Target Sell</h5>
                                            <p>25% , Deadline 12 June’13</p>
                                        </div>
                                        <span class="notification-pie-chart pull-right" data-percent="45">
                                            <span class="percent"></span>
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li class="external">
                                <a href="#">See All Tasks</a>
                            </li>
                        </ul>
                    </li>
                    <!-- settings end -->
                    <!-- inbox dropdown start-->
                    <li id="header_inbox_bar" class="dropdown">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <i class="far fa-envelope"></i>
                            <span class="badge bg-important">4</span>
                        </a>
                        <ul class="dropdown-menu extended inbox">
                            <li>
                                <p class="red">You have 4 Mails</p>
                            </li>
                            <li>
                                <a href="#">
                                    <span class="photo"><img alt="avatar"></span>
                                    <span class="subject">
                                        <span class="from">Jonathan Smith</span>
                                        <span class="time">Just now</span>
                                    </span>
                                    <span class="message">
                                        Hello, this is an example msg.
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a href="#">See all messages</a>
                            </li>
                        </ul>
                    </li>
                    <!-- inbox dropdown end -->
                    <!-- notification dropdown start-->
                    <li id="header_notification_bar" class="dropdown">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <i class="far fa-bell"></i>
                            <span class="badge bg-warning">3</span>
                        </a>
                        <ul class="dropdown-menu extended notification">
                            <li>
                                <p>Notifications</p>
                            </li>
                            <li>
                                <div class="alert alert-info clearfix">
                                    <span class="alert-icon"><i class="fa fa-bolt"></i></span>
                                    <div class="noti-info">
                                        <a href="#"> Server #1 overloaded.</a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <!-- notification dropdown end -->
                </ul>
                <!--  notification end -->
            </div>
            <div class="top-nav clearfix">
                <!--search & user info start-->
                <ul class="nav pull-right top-menu">
                    <li>
                        <input type="text" class="form-control search" placeholder=" Search">
                    </li>
                    <!-- user login dropdown start-->
                    <li>
                        <div class="bmucDg"></div>
                    </li>
                    @if (Auth::check())
                        <li class="dropdown">
                            <a data-toggle="dropdown" class="dropdown-toggle" href="javascript:;">
                                <img alt="" src="{{ asset('frontend/img/new-logo.jpg') }}">
                                <span class="username">
                                    {{ Auth::user()->profile->profile_lastname }}
                                </span>
                                <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu extended logout">
                                <li>
                                    <div class="extended-info">
                                        <div class="extended-img">
                                            <img src="{{ asset('frontend/img/new-logo.jpg') }}">
                                        </div>
                                        <div class="extended-cty">Medicen</div>
                                        <div class="extended-name">Chào, {{ Auth::user()->profile->profile_lastname }}</div>
                                    </div>
                                </li>
                                <li><a href="{{ route('information') }}"><i class=" fa fa-suitcase"></i>Thông tin</a>
                                </li>
                                <li><a href="{{ route('settings') }}"><i class="fa fa-cog"></i> Cài đặt</a></li>
                                <li><a href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </a>
                                    <form id="logout-form" action="{{ route('admin-logout') }}" method="POST"
                                        class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- user login dropdown end -->
                </ul>
                <!--search & user info end-->
            </div>
        </header>
        <!--header end-->
        <!--sidebar start-->
        <aside>
            <div id="sidebar" class="nav-collapse">
                <!-- sidebar menu start-->
                <div class="leftside-navigation">
                    <ul class="sidebar-menu" id="nav-accordion">
                        <li>
                            @php
                                $route = Route::current();
                            @endphp
                            <a class="{{ $route->uri == 'admin/dashboard' ? 'active' : '' }}"
                                href="{{ route('dashboard.index') }}">
                                <i class="far fa-chart-bar"></i>
                                <span>Thống kê doanh thu</span>
                            </a>
                        </li>
                        <li>
                            <a class="{{ $route->uri == 'admin/contact/edit' ? 'active' : '' }}"
                                href="{{ route('edit-contact') }}">
                                <i class="fa fa-info-circle"></i>
                                <span>Thông tin Web NKL</span>
                            </a>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/order/add' || $route->uri == 'admin/order/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý đơn hàng</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/order/add' ? 'active' : '' }}"
                                        href="{{ route('add-order') }}">
                                        <i class="fas fa-user-plus"></i> Thêm đơn hàng
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ $route->uri == 'admin/order/list' ? 'active' : '' }}"
                                        href="{{ route('list-order') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách đơn hàng
                                    </a>
                                </li>

                            </ul>
                        </li>
                        <li>
                            <a href="https://medicen.vn/lichxe" target="_blank">
                                <i class="far fa-calendar-alt"></i>
                                <span>Lịch KTV và Xe</span>
                            </a>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/unit/add' || $route->uri == 'admin/unit/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý đơn vị</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/unit/add' ? 'active' : '' }}"
                                        href="{{ route('add-unit') }}">
                                        <i class="fas fa-user-plus"></i> Thêm đơn vị
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ $route->uri == 'admin/unit/list' ? 'active' : '' }}"
                                        href="{{ route('list-unit') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách đơn vị
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/customer/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-users"></i>
                                <span>Quản lý khách hàng</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/customer/list' ? 'active' : '' }}"
                                        href="{{ route('list-customer') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách khách hàng
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/staff/add' || $route->uri == 'admin/staff/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý nhân viên</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/staff/add' ? 'active' : '' }}"
                                        href="{{ route('add-staff') }}">
                                        <i class="fas fa-user-plus"></i> Thêm nhân viên
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ $route->uri == 'admin/staff/list' ? 'active' : '' }}"
                                        href="{{ route('list-staff') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách nhân viên
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/service/add' || $route->uri == 'admin/service/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý dịch vụ</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/service/add' ? 'active' : '' }}"
                                        href="{{ route('add-service') }}">
                                        <i class="fas fa-user-plus"></i> Thêm dịch vụ
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ $route->uri == 'admin/service/list' ? 'active' : '' }}"
                                        href="{{ route('list-service') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách dịch vụ
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/category-post/add' || $route->uri == 'admin/category-post/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-th"></i>
                                <span>Quản lý danh mục bài viết</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/category-post/add' ? 'active' : '' }}"
                                        href="{{ route('add-category-post') }}">
                                        <i class="far fa-plus-square"></i> Thêm danh mục bài viết
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ $route->uri == 'admin/category-post/list' ? 'active' : '' }}"
                                        href="{{ route('list-category-post') }}">
                                        <i class="far fa-list-alt"></i> Danh mục bài viết
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/post/add' || $route->uri == 'admin/post/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fab fa-blogger-b"></i>
                                <span>Quản lý bài viết</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/post/add' ? 'active' : '' }}"
                                        href="{{ route('add-post') }}">
                                        <i class="far fa-plus-square"></i> Thêm bài viết
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ $route->uri == 'admin/post/list' ? 'active' : '' }}"
                                        href="{{ route('list-post') }}">
                                        <i class="far fa-list-alt"></i> Danh sách bài viết
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/slider/add' || $route->uri == 'admin/slider/list' ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fa fa-picture-o"></i>
                                <span>Slider</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ $route->uri == 'admin/slider/add' ? 'active' : '' }}"
                                        href="{{ route('add-slider') }}">
                                        <i class="far fa-plus-square"></i> Thêm slider
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ $route->uri == 'admin/post/list' ? 'active' : '' }}"
                                        href="{{ route('list-slider') }}">
                                        <i class="far fa-list-alt"></i> Quản lý slider
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/accountant/list-order' ? 'active' : '' }}"
                                href="{{ route('list-order-accountant') }}">
                                <i class="fa fa-picture-o"></i>
                                <span>Quản lý công nợ</span>
                            </a>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ $route->uri == 'admin/history/list' ? 'active' : '' }}"
                                href="{{ route('list-history') }}">
                                <i class="fa fa-picture-o"></i>
                                <span>Quản lý chỉnh sửa đơn hàng</span>
                            </a>
                        </li>
                    </ul>
                    <!-- sidebar menu end-->
                </div>
            </div>
        </aside>
        <!--sidebar end-->
        <!--main content start-->
        <section id="main-content">
            <section class="wrapper">
                <div class="container-fluid">
                    @yield('admin_content')
                </div>
            </section>
            <!-- footer -->
            {{-- <div class="footer">
                <div class="wthree-copyright">
                    <p>© 2017 Visitors. All rights reserved | Design by <a href="http://w3layouts.com">W3layouts</a>
                    </p>
                </div>
            </div> --}}
            <!-- / footer -->
        </section>
        <!--main content end-->
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

        @if (session('success'))
            <div class="notifications-popup-success active">
                <div class="notifications-content">
                    <div class="notifications-icon">
                        <i class="fas fa-solid fa-check notifications-success"></i>
                    </div>
                    <div class="notifications-message">
                        <span class="message-title">Thông báo !</span>
                        <span class="message-text">{!! session('success') !!}</span>
                    </div>
                </div>
                <i class="fas fa-times notifications-close"></i>
            </div>
        @elseif(session('error'))
            <div class="notifications-popup-error active">
                <div class="notifications-content">
                    <div class="notifications-icon">
                        <i class="fas fa-times notifications-error"></i>
                    </div>
                    <div class="notifications-message">
                        <span class="message-title">Thông báo !</span>
                        <span class="message-text">{!! session('error') !!}</span>
                    </div>
                </div>
                <i class="fas fa-times notifications-close"></i>
            </div>
        @endif
        <!--End Noti Popup -->
    </section>
    <!-- Page Preloder -->
    <div id="preloder">
        <div class="loader"></div>
    </div>
    <script src="{{ versionResource('backend/js/jquery2.0.3.min.js') }}"></script>
    <script src="{{ versionResource('backend/js/bootstrap.js') }}"></script>
    <!-- Ux Ui -->
    <script src="{{ versionResource('backend/js/ux-ui/jquery.dcjqaccordion.2.7.min.js') }}" defer ></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery.slimscroll.min.js') }}" defer ></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery.nicescroll.min.js') }}" defer ></script>
    <script src="{{ versionResource('backend/js/ux-ui/left-side.min.js') }}" defer ></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery-ui.min.js') }}"  ></script>
    {{-- <script src="{{ versionResource('backend/js/responsive.jqueryui.min.js') }}"></script> --}}
    <script src="{{ versionResource('backend/js/tool/select2.min.js') }}" ></script>
    <script src="{{ versionResource('backend/js/tool/main.min.js') }}" ></script>
    <script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>
 
    @stack('js')

    <script type="text/javascript">
        //Handle sales
        var url_cancle_schedule = "{{route('cancle-schedule')}}";
        var url_upload_image_ck = "{{ route('upload-image-ck',['_token'=>csrf_token()]) }}";
        var url_delete_file_order = "{{route('url-delete-file-order',':path')}}";
        // Revenue Statistics Url
        var url_revenue_statistics_for_the_month = "{{route('url-revenue-statistics-for-the-month')}}";
        var url_optional_revenue_statistics = "{{route('url-optional-revenue-statistics')}}";
        var url_revenue_statistics_by_unit = "{{route('url-revenue-statistics-by-unit')}}";
        var url_revenue_statistics_by_date = "{{route('url-revenue-statistics-by-date')}}";
         // Accountant Url
        var urlGetAccountant = "{{route('url-get-list-accountant')}}";
        var urlUpdateAccountant = "{{route('url-update-accountant',':id')}}";
        var urlCompleteAccountant = "{{route('url-complete-accountant',':id')}}";
        var urlFilterAccountant = "{{route('url-filter-accountant')}}";
        var _token = "{{ csrf_token() }}";
    </script>

    {{-- [if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif] --}}
    <script src="{{ asset('backend/js/jquery.scrollTo.js') }}"></script>
    <!-- calendar -->
    <script type="text/javascript" src="{{ asset('backend/js/monthly.js') }}"></script>
</body>

</html>
