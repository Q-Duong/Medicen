<!DOCTYPE html>
<html lang="vi-VN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')Medicen</title>
    <meta name="description" content="@yield('title')">
    <meta name="keywords" content="Medicen, Y tế, Thiết bị, Xe, @yield('title')">
    <meta name="author" content="Medicen">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- #FAVICONS -->
    <link rel='shortcut icon' href="{{ asset('assets/images/logo.png') }}" type="image/x-icon">
    <link rel='icon' href="{{ asset('assets/images/logo.png') }}" type="image/x-icon">
    <link rel='canonical' href="{{ url()->current() }}">
    <!-- Open Graph Metadata for Social Media -->
    <meta property="og:title" content="@yield('title')">
    <meta property="og:description" content="@yield('title')">
    <meta property="og:image" content="https://madicen.vn/assets/images/logo.png">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="vi-VN">
    <meta property="og:site_name" content="Medicen">
    <meta property="og:type" content="website">
    <!-- Google Font -->
    <link rel="preload stylesheet"
        href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <!-- Css Styles -->
    <link rel="stylesheet" href="{{ versionResource('frontend/css/bootstrap.min.css') }}" type="text/css"
        as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/jquery-ui.min.css') }}" as="style">
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/site.built.css') }}" type="text/css"
        as="style" />
    <link rel="stylesheet" href="{{ versionResource('frontend/css/slicknav.min.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('frontend/css/style.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/ac-header/slicknav.css') }}" type="text/css"
        as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/ac-header/ac-header.built.css') }}"
        type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/overview.built.css') }}" rel='stylesheet'
        type='text/css' as="style" />
    @stack('css')
</head>

<body>
    <h1 class="visuallyhidden">
        Medicen
    </h1>
    @include('layouts.section.client.header')
    <main class="main" role="main">
        @yield('content')
    </main>
    {{-- <div class="bodyContainer">
        @yield('content')
    </div> --}}
    @include('layouts.section.client.footer')
    {{-- @include('layouts.section.client.chat_box') --}}
    {{-- @include('layouts.section.essential.zalo') --}}
    @include('layouts.section.essential.search_model')
    <div id="portal-notification">
        @include('layouts.section.essential.session_notification')
    </div>
    @include('layouts.section.essential.notification')

    <script src="{{ versionResource('frontend/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ versionResource('frontend/js/bootstrap.min.js') }}"></script>
    <script src="{{ versionResource('frontend/js/jquery-ui.min.js') }}" defer></script>
    <script src="https://kit.fontawesome.com/4b68e3663c.js" crossorigin="anonymous" defer></script>
    <script src="{{ versionResource('assets/js/built/ac-header/slicknav.js') }}"></script>
    <script src="{{ versionResource('frontend/js/main.js') }}"></script>
    <script src="{{ versionResource('frontend/js/jquery.nicescroll.min.js') }}" defer></script>
    <script src="{{ versionResource('frontend/js/prettify.min.js') }}" defer></script>
    @stack('js')
</body>

</html>
