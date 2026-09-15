@extends('layouts.default')
@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush
@section('content')
    <section class="hero-section hero-section-full-height">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-12 p-0">
                    <div id="hero-slide" class="carousel carousel-fade slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="{{ asset('uploads/slider/slide-3132.jpg') }}" class="carousel-image img-fluid"
                                    alt="@lang('master_pages.hero.img_alt')">

                                <div class="carousel-caption d-flex flex-column justify-content-end">
                                    <h1>@lang('master_pages.hero.title')</h1>
                                    <p>@lang('master_pages.hero.subtitle')</p>
                                </div>
                            </div>

                            <div class="carousel-item">
                                <img src="{{ asset('uploads/slider/aim-giai-phap-chinh-hinh-ai.png') }}"
                                    class="carousel-image img-fluid" alt="@lang('master_pages.hero.img_alt')">

                                <div class="carousel-caption d-flex flex-column justify-content-end">
                                    <h1>@lang('master_pages.hero.title')</h1>
                                    <p>@lang('master_pages.hero.subtitle')</p>
                                </div>
                            </div>
                        </div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#hero-slide"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">@lang('master_pages.hero.previous')</span>
                        </button>

                        <button class="carousel-control-next" type="button" data-bs-target="#hero-slide"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">@lang('master_pages.hero.next')</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding section-bg">
        <div class="container">
            <div class="row">

                <div class="col-lg-6 col-12 mb-5 mb-lg-0">
                    <img src="{{ asset('assets/images/about/about-us-medicen.jpg') }}" alt="Đau cấp"
                        class="custom-text-box-image img-fluid">
                </div>

                <div class="col-lg-6 col-12">
                    <div class="custom-text-box">
                        <h2 class="mb-2">@lang('about_us.title')</h2>

                        <h5 class="mb-3">@lang('about_us.intro.company_name')</h5>

                        <p class="mb-0">@lang('about_us.intro.desc')</p>
                    </div>

                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 col-md-7 col-12 mt-4">
                    <div class="custom-text-box mb-lg-0">
                        <h5 class="mb-3">@lang('about_us.mission.title')</h5>

                        {{-- <p>@lang('about_us.mission.desc')</p> --}}

                        <ul class="custom-list mt-2">
                            <li class="custom-list-item d-flex">
                                <i class="bi-check custom-text-box-icon me-2"></i>
                                @lang('about_us.mission.items.1')
                            </li>

                            <li class="custom-list-item d-flex">
                                <i class="bi-check custom-text-box-icon me-2"></i>
                                @lang('about_us.mission.items.2')
                            </li>

                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-5 col-12 mt-4">
                    <div class="custom-text-box d-flex flex-wrap d-lg-block mb-lg-0">
                        <div class="counter-thumb">
                            <div class="d-flex">
                                <span class="counter-number" data-from="1" data-to="15" data-speed="1000"></span>
                                <span class="counter-number-text">+ năm</span>
                            </div>

                            <span class="counter-text mt-4">@lang('about_us.stats.establish')</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-5 col-12 mt-4">
                    <div class="custom-text-box d-flex flex-wrap d-lg-block mb-lg-0">
                        <div class="counter-thumb">
                            <div class="d-flex">
                                <span class="counter-number" data-from="1" data-to="100" data-speed="1000"></span>
                                <span class="counter-number-text">+ Đối tác</span>
                            </div>

                            <span class="counter-text mt-4">@lang('about_us.stats.patient')</span>
                        </div>
                    </div>
                </div>

                {{-- <div class="col-lg-3 col-md-5 col-12">
                    <div class="custom-text-box d-flex flex-wrap d-lg-block mb-lg-0">
                        <div class="counter-thumb">
                            <div class="d-flex">
                                <span class="counter-number" data-from="1" data-to="2009" data-speed="1000"></span>
                                <span class="counter-number-text"></span>
                            </div>

                            <span class="counter-text">@lang('about_us.stats.establish')</span>
                        </div>

                        <div class="counter-thumb mt-4">
                            <div class="d-flex">
                                <span class="counter-number" data-from="1" data-to="30" data-speed="1000"></span>
                                <span class="counter-number-text">+</span>
                            </div>

                            <span class="counter-text">@lang('about_us.stats.staff')</span>
                        </div>

                        <div class="counter-thumb mt-4">
                            <div class="d-flex">
                                <span class="counter-number" data-from="1" data-to="100"
                                    data-speed="1000"></span>
                                <span class="counter-number-text">+</span>
                            </div>

                            <span class="counter-text">@lang('about_us.stats.patient')</span>
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>
    </section>

    <section class="cta-section section-padding section-bg">
        <div class="container">
            <div class="col-lg-8 col-12 mx-auto text-center">
                <h2 class="mb-0">@lang('master_pages.header.service')</h2>
                <p class="text-muted">@lang('master_pages.services.intro_desc')</p>
            </div>
        </div>
    </section>

    <section class="section-padding" style="background-color: #fbfbfd;">
        <div class="container">
    
            <div class="row g-4">
                <div class="col-lg-6 d-flex">
                    <div class="apple-bento-card w-100 bg-white rounded-4 shadow-sm border-0 d-flex flex-column overflow-hidden">
                        <div class="bento-img-wrapper position-relative" style="height: 290px;">
                            <img src="{{ asset('assets/images/services/cho-thue-xe-x-quang-luu-dong.jpg') }}" 
                                 alt="@lang('services.bento.x-ray.title')"
                                 class="bento-img w-100 h-100" style="object-fit: cover; object-position: top;">
                        </div>
    
                        <div class="bento-content d-flex flex-column" style="padding: 35px 30px; flex-grow: 1;">
                            <h3 class="fw-bold mb-3" style="font-size: 1.4rem; color: #e30000;">
                                <i class="bi bi-truck-front-fill me-2"></i>
                                @lang('services.bento.x-ray.title')
                            </h3>
                            <p class="text-muted mb-4 flex-grow-1" style="font-size: 0.95rem; line-height: 1.6;">
                                @lang('services.bento.x-ray.desc')
                            </p>
                            
                            <ul class="list-unstyled mb-4 small text-dark">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> @lang('services.bento.x-ray.feature1')</li>
                                <li class="mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i> @lang('services.bento.x-ray.feature2')</li>
                            </ul>

                        </div>
                    </div>
                </div>
    
                <div class="col-lg-6 d-flex">
                    <div class="apple-bento-card w-100 bg-white rounded-4 shadow-sm border-0 d-flex flex-column overflow-hidden">
                        <div class="bento-img-wrapper position-relative" style="height: 290px;">
                            <img src="{{ asset('assets/images/services/aim-giai-phap-chinh-hinh-ai.png') }}"
                                 alt="@lang('services.bento.aim.title')" 
                                 class="bento-img w-100 h-100" style="object-fit: cover; object-position: top;">
                        </div>
                        
                        <div class="bento-content d-flex flex-column" style="padding: 35px 40px; flex-grow: 1;">
                            <h3 class="fw-bold mb-3" style="font-size: 1.4rem; color: #1d1d1f;">
                                <i class="bi bi-cpu-fill text-primary me-2"></i>
                                @lang('services.bento.aim.title')
                            </h3>
                            <p class="text-muted mb-4 flex-grow-1" style="font-size: 0.95rem; line-height: 1.6;">
                                @lang('services.bento.aim.desc')
                            </p>
    
                            <ul class="list-unstyled mb-4 small text-dark">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> @lang('services.bento.aim.feature1')</li>
                                <li class="mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i> @lang('services.bento.aim.feature2')</li>
                            </ul>
    
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <a href="{{ route('services.index') }}"
                        class="btn btn-outline-primary rounded-pill px-5 py-2 fw-bold">
                        @lang('services.bento.learn_more')
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section section-padding">
        <div class="container">
            <div class="row justify-content-between">

                <div class="col-lg-12 col-12 mb-5">
                    <h2>@lang('master_pages.header.contact')</h2>
                </div>

                <div class="col-lg-5 col-12 mb-5 mb-lg-0">

                    <div class="apple-contact-card">
                        <div class="apple-contact-icon"><i class="bi bi-building"></i></div>
                        <div class="apple-contact-info">
                            <h4 class="text-primary">@lang('contact.info.branch_1.name')</h4>
                            <p><i class="bi bi-pin-map text-muted me-2"></i>@lang('contact.info.branch_1.address')</p>
                            <p><i class="bi bi-phone text-muted me-2"></i><a href="tel:0812348381"
                                    class="text-decoration-none text-dark fw-bold">@lang('contact.info.branch_1.phone')</a></p>
                        </div>
                    </div>

                    <div>
                        <div class="apple-map-container">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d48796.22673189699!2d106.68077420346707!3d10.753730538290302!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752e3bc7e767e5%3A0x169aca0c83b94fdb!2zQ8OUTkcgVFkgVE5ISCDEkOG6plUgVMavIFbDgCBUUkFORyBUSEnhur5UIELhu4ogWSBU4bq-IE5BTSBLSMOBTkggTElOSA!5e0!3m2!1svi!2s!4v1789097506489!5m2!1svi!2s"
                                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-12">
                    <div class="apple-form-box">
                        <h3 class="fw-bold mb-4 text-center">@lang('contact.form.heading')</h3>
                        <div id="ajaxMessage" class="alert d-none mb-4" style="border-radius: 16px;" role="alert">
                        </div>
                        <form id="appointmentForm" action="{{ route('contact.submit') }}" method="POST">
                            @csrf
                            <div class="mb-4 position-relative">
                                <input type="text" class="apple-form-control w-100" name="name"
                                    placeholder="@lang('contact.form.name')">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-4 position-relative">
                                    <input type="tel" class="apple-form-control w-100" name="phone"
                                        placeholder="@lang('contact.form.phone')">
                                </div>
                                <div class="col-md-6 mb-4 position-relative">
                                    <input type="text" id="appointmentDate" class="apple-form-control w-100"
                                        name="date" placeholder="Chọn ngày khám" required>
                                </div>
                            </div>
                            <div class="mb-4 position-relative">
                                <textarea class="apple-form-control w-100" name="message" rows="3" placeholder="@lang('contact.form.message')"></textarea>
                            </div>

                            <button type="submit" id="submitBtn"
                                class="apple-btn-submit d-flex justify-content-center align-items-center gap-2">
                                <span>@lang('contact.form.submit_btn')</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="section-padding" style="background-color: #ffffff;">
        <div class="container">

            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="fw-bold" style="font-size: 2.2rem; color: #1d1d1f; letter-spacing: -0.5px;">
                        @lang('master_pages.social.title')
                    </h2>
                    <p class="text-muted mt-2" style="font-size: 1.1rem;">
                        @lang('master_pages.social.subtitle')
                    </p>
                </div>
            </div>

            <div class="row justify-content-center gap-lg-4">

                <div class="col-lg-5 col-md-6 col-12 mb-4 d-flex">
                    <div class="apple-social-card w-100">
                        <div class="social-header px-4 pt-4 pb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="social-icon fb-icon"><i class="bi bi-facebook"></i></div>
                                <div>
                                    <h4 class="mb-0 fw-bold fs-5">@lang('master_pages.social.fb_title')</h4>
                                    <span class="text-muted" style="font-size: 0.85rem;">@lang('master_pages.social.fb_desc')</span>
                                </div>
                            </div>
                            <a href="https://www.facebook.com/profile.php?id=100090839746348" target="_blank"
                                class="btn-social-follow fb-btn">
                                @lang('master_pages.social.follow_fb')
                            </a>
                        </div>

                    </div>
                </div>

                <div class="col-lg-5 col-md-6 col-12 mb-4 d-flex">
                    <div class="apple-social-card w-100">
                        <div class="social-header px-4 pt-4 pb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="social-icon"><img src="{{ asset('assets/images/icon/zalo.webp') }}" class="zalo-icon" alt="Medicen"></div>
                                <div>
                                    <h4 class="mb-0 fw-bold fs-5">@lang('master_pages.social.tiktok_title')</h4>
                                    <span class="text-muted" style="font-size: 0.85rem;">@lang('master_pages.social.tiktok_desc')</span>
                                </div>
                            </div>
                            <a href="https://zalo.me/4496283275293639849" target="_blank"
                                class="btn-social-follow zalo-btn">
                                @lang('master_pages.social.follow_zalo')
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection
@push('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
    <script src="{{ versionResource('assets/js/support/contact.js') }}" defer></script>
@endpush
