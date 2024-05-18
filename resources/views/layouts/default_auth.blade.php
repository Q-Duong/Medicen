<!DOCTYPE html>

<head>
    <title>Medicen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="Medicen, Y tế, Thiết bị, Xe">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel='shortcut icon' href="{{ asset('frontend/img/new-logo.jpg') }}" />
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
    <link href="{{ versionResource('assets/css/overview.built.css') }}" rel='stylesheet' type='text/css' as="style"/>
    <!-- font CSS -->
    {{-- <link
        href='//fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic'
        rel='stylesheet' type='text/css'> --}}
    <!-- font-awesome icons -->
    <link rel="stylesheet" href="{{ versionResource('backend/css/font.css') }}" type="text/css" as="style" />
    <link href="{{ versionResource('backend/css/font-awesome.css') }}" rel="stylesheet" as="style" />
    {{-- <link rel="stylesheet" href="{{ versionResource('backend/css/morris.css') }}" type="text/css" />  --}}
    <!-- calendar -->
    <link rel="stylesheet" href="{{ versionResource('backend/css/monthly.css') }}" as="style" />
    <!-- //calendar -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css" as="style" />
    <!-- //font-awesome icons -->
    <link rel="stylesheet" href="{{ versionResource('backend/fontawesome-free-5.15.4-web/css/all.css') }}"
        as="style" />
    <!-- //select2 -->
    <link href="{{ versionResource('backend/css/select2.min.css') }}" rel="stylesheet" as="style"/>
    @stack('css')
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
                                        <div class="extended-name">Chào, {{ Auth::user()->profile->profile_lastname }}
                                        </div>
                                    </div>
                                </li>
                                <li><a href="{{ route('information') }}"><i class=" fa fa-suitcase"></i>Thông tin</a>
                                </li>
                                <li><a href="{{ route('settings') }}"><i class="fa fa-cog"></i> Cài đặt</a></li>
                                <li><a href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </a>
                                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST"
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
                            <a class="{{ request()->routeIs('dashboard.index') ? 'active' : '' }}"
                                href="{{ route('dashboard.index') }}">
                                <i class="far fa-chart-bar"></i>
                                <span>Thống kê doanh thu</span>
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->routeIs('contact.edit') ? 'active' : '' }}"
                                href="{{ route('contact.edit') }}">
                                <i class="fa fa-info-circle"></i>
                                <span>Thông tin Web NKL</span>
                            </a>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('order.index') || request()->routeIs('order.create') || request()->routeIs('order.edit') || request()->routeIs('order.copy') || request()->routeIs('schedule.create') || request()->routeIs('schedule.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý đơn hàng</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('order.create') ? 'active' : '' }}"
                                        href="{{ route('order.create') }}">
                                        <i class="fas fa-user-plus"></i> Thêm đơn hàng
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('order.index') || request()->routeIs('schedule.create') || request()->routeIs('schedule.edit') ? 'active' : '' }}"
                                        href="{{ route('order.index') }}">
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
                            <a class="{{ request()->routeIs('unit.index') || request()->routeIs('unit.create') || request()->routeIs('unit.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý đơn vị</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('unit.create') ? 'active' : '' }}"
                                        href="{{ route('unit.create') }}">
                                        <i class="fas fa-user-plus"></i> Thêm đơn vị
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('unit.index') ? 'active' : '' }}"
                                        href="{{ route('unit.index') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách đơn vị
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('customer.index') ? 'active' : '' }}"
                                href="{{ route('customer.index') }}">
                                <i class="fas fa-users"></i>
                                <span>Quản lý khách hàng</span>
                            </a>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('staff.index') || request()->routeIs('staff.create') || request()->routeIs('staff.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý nhân viên</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('staff.create') ? 'active' : '' }}"
                                        href="{{ route('staff.create') }}">
                                        <i class="fas fa-user-plus"></i> Thêm nhân viên
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('staff.index') ? 'active' : '' }}"
                                        href="{{ route('staff.index') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách nhân viên
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('service.index') || request()->routeIs('service.create') || request()->routeIs('service.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý dịch vụ</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('service.create') ? 'active' : '' }}"
                                        href="{{ route('service.create') }}">
                                        <i class="fas fa-user-plus"></i> Thêm dịch vụ
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('service.index') ? 'active' : '' }}"
                                        href="{{ route('service.index') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách dịch vụ
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('about.index') || request()->routeIs('about.create') || request()->routeIs('about.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-file-alt"></i>
                                <span>Quản lý giới thiệu</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('about.create') ? 'active' : '' }}"
                                        href="{{ route('about.create') }}">
                                        <i class="fas fa-user-plus"></i> Thêm giới thiệu
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('about.index') ? 'active' : '' }}"
                                        href="{{ route('about.index') }}">
                                        <i class="fas fa-list-ol"></i> Danh sách giới thiệu
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('post_category.index') || request()->routeIs('post_category.create') || request()->routeIs('post_category.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fas fa-th"></i>
                                <span>Quản lý danh mục bài viết</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('post_category.create') ? 'active' : '' }}"
                                        href="{{ route('post_category.create') }}">
                                        <i class="far fa-plus-square"></i> Thêm danh mục bài viết
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('post_category.index') ? 'active' : '' }}"
                                        href="{{ route('post_category.index') }}">
                                        <i class="far fa-list-alt"></i> Danh mục bài viết
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('post.index') || request()->routeIs('post.create') || request()->routeIs('post.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fab fa-blogger-b"></i>
                                <span>Quản lý bài viết</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('post.create') ? 'active' : '' }}"
                                        href="{{ route('post.create') }}">
                                        <i class="far fa-plus-square"></i> Thêm bài viết
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('post.index') ? 'active' : '' }}"
                                        href="{{ route('post.index') }}">
                                        <i class="far fa-list-alt"></i> Danh sách bài viết
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('slider.index') || request()->routeIs('slider.create') || request()->routeIs('slider.edit') ? 'active' : '' }}"
                                href="javascript:;">
                                <i class="fa fa-picture-o"></i>
                                <span>Slider</span>
                            </a>
                            <ul class="sub">
                                <li>
                                    <a class="{{ request()->routeIs('slider.create') ? 'active' : '' }}"
                                        href="{{ route('slider.create') }}">
                                        <i class="far fa-plus-square"></i> Thêm slider
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('slider.index') ? 'active' : '' }}"
                                        href="{{ route('slider.index') }}">
                                        <i class="far fa-list-alt"></i> Quản lý slider
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('accountant.index') ? 'active' : '' }}"
                                href="{{ route('accountant.index') }}">
                                <i class="fa fa-picture-o"></i>
                                <span>Quản lý công nợ</span>
                            </a>
                        </li>
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('history.index') ? 'active' : '' }}"
                                href="{{ route('history.list_history') }}">
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

    <div class="loader-over">
        <span class="loader"></span>
    </div>
    <script src="{{ versionResource('backend/js/jquery2.0.3.min.js') }}"></script>
    <script src="{{ versionResource('backend/js/bootstrap.js') }}"></script>
    <!-- Ux Ui -->
    <script src="{{ versionResource('backend/js/ux-ui/jquery.dcjqaccordion.2.7.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery.slimscroll.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery.nicescroll.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/left-side.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery-ui.min.js') }}"></script>
    {{-- <script src="{{ versionResource('backend/js/responsive.jqueryui.min.js') }}"></script> --}}
    <script src="{{ versionResource('backend/js/tool/select2.min.js') }}" ></script>
    <script src="{{ versionResource('backend/js/tool/main.min.js') }}" ></script>
    @stack('js')
    {{-- [if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif] --}}
    <script src="{{ asset('backend/js/jquery.scrollTo.js') }}"></script>
    <!-- calendar -->
    <script type="text/javascript" src="{{ asset('backend/js/monthly.js') }}"></script>
</body>

</html>
