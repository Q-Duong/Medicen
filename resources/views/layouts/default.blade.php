<!DOCTYPE html>
<html lang="{{ App::getLocale() == 'vn' ? 'vi-VN' : 'en-US' }}" class="enhanced">

<head>
    <!-- Google tag (gtag.js) -->
    {{-- <script async src="https://www.googletagmanager.com/gtag/js?id=G-LL54RPCH92"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-LL54RPCH92');
    </script> --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="aPcMw-PBphViCDbTq79T4-WeqSUzk3H1C7M5qjDTBMY" />

    <title>@yield('title', 'Medicen | Cho Thuê Xe X-Quang & Phần Mềm Đo Loãng Xương')</title>

    <meta name="description" content="@yield('seo_description', 'Medicen (Nam Khánh Linh) chuyên cho thuê xe X-quang khám sức khỏe lưu động, cung cấp phần mềm đo loãng xương và trang thiết bị y tế chuyên nghiệp.')">
    <meta name="keywords" content="@yield('seo_keywords', 'Cho thuê xe X-quang, phần mềm đo loãng xương, trang thiết bị y tế, khám sức khỏe, định kỳ, loãng xương')">
    <meta name="author" content="Medicen">

    <link rel='shortcut icon' href="{{ asset('assets/images/medicen-logo.png') }}" type="image/x-icon">
    <link rel='icon' href="{{ asset('assets/images/medicen-logo.png') }}" type="image/x-icon">
    <link rel='canonical' href="{{ url()->current() }}">

    <meta property="og:site_name" content="Medicen">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ App::getLocale() == 'vn' ? 'vi-VN' : 'en-US' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Medicen | Cho Thuê Xe X-Quang & Phần Mềm Đo Loãng Xương')">
    <meta property="og:description" content="@yield('seo_description', 'Medicen (Nam Khánh Linh) chuyên cho thuê xe X-quang khám sức khỏe lưu động, cung cấp phần mềm đo loãng xương và trang thiết bị y tế chuyên nghiệp.')">
    <meta property="og:image" content="@yield('seo_image', asset('assets/images/medicen-logo.png'))">

    <link rel="stylesheet" href="{{ versionResource('frontend/css/style.css') }}" type="text/css" as="style">
    <link rel="stylesheet" href="{{ versionResource('assets/css/bootstrap.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ versionResource('assets/css/bootstrap-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/home.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/site.built.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ versionResource('assets/css/overview.built.css') }}" rel='stylesheet'>
    @stack('css')
</head>

<body>
    @include('layouts.section.client.header')

    <main class="main" role="main">
        @yield('content')
    </main>

    @include('layouts.section.client.footer')
    <div id="portal-notification">
        @include('layouts.section.essential.session_notification')
    </div>
    @include('layouts.section.essential.notification')


    <script src="{{ versionResource('assets/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ versionResource('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ versionResource('assets/js/built/sticky.js') }}"></script>
    <script src="{{ versionResource('assets/js/built/counter.js') }}"></script>
    <script src="{{ versionResource('assets/js/built/custom.js') }}"></script>
    <script src="https://kit.fontawesome.com/4b68e3663c.js" crossorigin="anonymous" defer></script>
    <script src="{{ versionResource('frontend/js/main.js') }}"></script>
    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "WebSite",
          "name": "Medicen",
          "alternateName": "Công Ty TNHH Đầu Tư Trang Thiết Bị Y Tế Nam Khánh Linh Medicen",
          "url": "https://www.medicen.tech/"
        }
        </script>
    @stack('js')
</body>

</html>
