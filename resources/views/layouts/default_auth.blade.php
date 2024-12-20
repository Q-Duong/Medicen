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
    <link href="{{ versionResource('backend/css/style.css') }}" rel='stylesheet' type='text/css' as="style" />
    <link href="{{ versionResource('backend/css/style-responsive.css') }}" rel="stylesheet" as="style" />
    <link href="{{ versionResource('backend/css/jquery.dataTables.min.css') }}" rel="stylesheet" as="style" />
    <link href="{{ versionResource('backend/css/responsive-jqueryui.min.css') }}" rel="stylesheet" as="style" />
    <link href="{{ versionResource('backend/css/themes-base-jquery-ui.css') }}" rel="stylesheet" as="style" />
    <link href="{{ versionResource('assets/css/overview.built.css') }}" rel='stylesheet' type='text/css'
        as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/unified.css') }}" type="text/css" as="style">
    <!-- font CSS -->
    {{-- <link
        href='//fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic'
        rel='stylesheet' type='text/css'> --}}
    <!-- font-awesome icons -->
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
    <link href="{{ versionResource('backend/css/select2.min.css') }}" rel="stylesheet" as="style" />
    @stack('css')
</head>

<body>
    <section id="container">
        @include('layouts.section.admin.header')
        @if (Auth::user()->role == 0)
            @include('layouts.section.admin.side_bar_super_admin')
        @else
            @include('layouts.section.admin.side_bar')
        @endif
        <section id="main-content">
            <section class="wrapper">
                <div class="container-fluid">
                    @yield('admin_content')
                </div>
            </section>
            @include('layouts.section.admin.footer')
        </section>
        @include('layouts.section.admin.notification')
        <div id="portal-notification">
            @include('layouts.section.essential.session_notification')
        </div>
    </section>

    <script src="{{ versionResource('backend/js/jquery2.0.3.min.js') }}"></script>
    <script src="{{ versionResource('backend/js/bootstrap.js') }}"></script>
    <!-- Ux Ui -->
    <script src="{{ versionResource('backend/js/ux-ui/jquery.dcjqaccordion.2.7.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery.slimscroll.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery.nicescroll.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/left-side.min.js') }}" defer></script>
    <script src="https://kit.fontawesome.com/4b68e3663c.js" crossorigin="anonymous" defer></script>
    <script src="{{ versionResource('backend/js/ux-ui/jquery-ui.min.js') }}"></script>
    {{-- <script src="{{ versionResource('backend/js/responsive.jqueryui.min.js') }}"></script> --}}
    <script src="{{ versionResource('backend/js/tool/select2.min.js') }}"></script>
    <script src="{{ versionResource('backend/js/tool/main.min.js') }}"></script>
    @stack('js')
    {{-- [if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif] --}}
    <script src="{{ asset('backend/js/jquery.scrollTo.js') }}"></script>
    <!-- calendar -->
    <script type="text/javascript" src="{{ asset('backend/js/monthly.js') }}"></script>
</body>

</html>
