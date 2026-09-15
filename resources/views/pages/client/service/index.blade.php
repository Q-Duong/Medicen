@extends('layouts.default')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/services.css') }}" type="text/css">
@endpush
@section('title', __('services.combined.seo_title'))
@section('seo_description', __('services.combined.seo_description'))

@section('content')
    <section class="hero-section position-relative d-flex align-items-center hero-banner-bg"
        style="background-image: url('{{ asset('assets/images/services/x-ray-banner-bg.jpg') }}');">
        <div class="position-absolute top-0 start-0 w-100 h-100 hero-gradient-overlay"></div>
        <div class="container position-relative text-white hero-content-wrapper">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="{{ route('home.index') }}"
                            class="text-white-50 text-decoration-none">@lang('master_pages.header.home')</a></li>
                    <li class="breadcrumb-item active text-white fw-bold" aria-current="page">@lang('master_pages.header.service')</li>
                </ol>
            </nav>

            <h1 class="display-4 fw-bold mb-3">@lang('services.combined.hero.title')</h1>
            <p class="lead text-white-50 mb-0 hero-desc-text">
                @lang('services.combined.hero.desc')
            </p>
        </div>
    </section>

    <section class="section-padding bg-white mt-5" id="xe-x-quang">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 col-12">
                    <div class="position-relative">
                        <img src="{{ asset('assets/images/services/cho-thue-xe-x-quang-luu-dong.jpg') }}"
                            alt="@lang('services.combined.x_ray.title')" class="img-fluid rounded-4 shadow-lg w-100 service-img-featured">
                        <div class="position-absolute bottom-0 start-0 bg-white p-3 shadow-sm service-tag">
                            <h4 class="text-danger fw-bold mb-0">Lưu động</h4>
                            <span class="text-muted small">Nhanh chóng & An toàn</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-12">
                    <span class="badge bg-danger-subtle mb-3 px-3 py-2 rounded-pill"><i
                            class="bi bi-truck-front-fill me-1"></i> Dịch vụ mũi nhọn</span>
                    <h2 class="fw-bold mb-4 text-dark">@lang('services.combined.x_ray.title')</h2>

                    <p class="text-muted mb-4 service-desc-text">
                        @lang('services.combined.x_ray.desc_1')
                    </p>
                    <p class="text-muted mb-4 service-desc-text">
                        @lang('services.combined.x_ray.desc_2')
                    </p>

                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-3"></i>
                            <span class="text-dark">@lang('services.combined.x_ray.benefit_1')</span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-3"></i>
                            <span class="text-dark">@lang('services.combined.x_ray.benefit_2')</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding section-bg-light process-section" id="phan-mem-ai">
        <div class="container">
            <div class="row align-items-center g-5 flex-lg-row-reverse">
                <div class="col-lg-6 col-12">
                    <div class="position-relative">
                        <img src="{{ asset('assets/images/services/aim-giai-phap-chinh-hinh-ai.png') }}"
                            alt="@lang('services.combined.aim.title')" class="img-fluid rounded-4 shadow-lg w-100 service-img-featured-top">
                        <div class="position-absolute bottom-0 end-0 bg-white p-3 shadow-sm text-end service-right-tag">
                            <h4 class="text-primary fw-bold mb-0">AI Tech</h4>
                            <span class="text-muted small">Độ chính xác cao</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-12">
                    <span class="badge bg-primary-subtle mb-3 px-3 py-2 rounded-pill"><i
                            class="bi bi-cpu-fill me-1"></i> Công nghệ số</span>
                    <h2 class="fw-bold mb-4 text-dark">@lang('services.combined.aim.title')</h2>

                    <p class="text-muted mb-4 service-desc-text">
                        @lang('services.combined.aim.desc_1')
                    </p>
                    <p class="text-muted mb-4 service-desc-text">
                        @lang('services.combined.aim.desc_2')
                    </p>

                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-primary fs-5 me-3"></i>
                            <span class="text-dark">@lang('services.combined.aim.benefit_1')</span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-primary fs-5 me-3"></i>
                            <span class="text-dark">@lang('services.combined.aim.benefit_2')</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding bg-white process-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">@lang('services.combined.process.title')</h2>
                <p class="text-muted">@lang('services.combined.process.subtitle')</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="h-100 rounded-4 bg-light p-4 text-center border-0 shadow-sm transition-hover">
                        <div
                            class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow process-step-icon">
                            1</div>
                        <h5 class="fw-bold mb-3">@lang('services.combined.process.step1.title')</h5>
                        <p class="text-muted small mb-0">@lang('services.combined.process.step1.desc')</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="h-100 rounded-4 bg-light p-4 text-center border-0 shadow-sm transition-hover">
                        <div
                            class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow process-step-icon">
                            2</div>
                        <h5 class="fw-bold mb-3">@lang('services.combined.process.step2.title')</h5>
                        <p class="text-muted small mb-0">@lang('services.combined.process.step2.desc')</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="h-100 rounded-4 bg-light p-4 text-center border-0 shadow-sm transition-hover">
                        <div
                            class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow process-step-icon">
                            3</div>
                        <h5 class="fw-bold mb-3">@lang('services.combined.process.step3.title')</h5>
                        <p class="text-muted small mb-0">@lang('services.combined.process.step3.desc')</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12">
                    <div class="h-100 rounded-4 bg-light p-4 text-center border-0 shadow-sm transition-hover">
                        <div
                            class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow process-step-icon">
                            4</div>
                        <h5 class="fw-bold mb-3">@lang('services.combined.process.step4.title')</h5>
                        <p class="text-muted small mb-0">@lang('services.combined.process.step4.desc')</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding text-center section-bg cta-section">
        <div class="container">
            <h2 class="fw-bold mb-3">@lang('services.combined.cta.title')</h2>
            <p class="mb-4 cta-desc-text">@lang('services.combined.cta.desc')</p>
            <a href="{{ route('contact.index') }}"
                class="btn btn-outline-primary rounded-pill px-5 py-2 fw-bold mb-5">
                <i class="bi bi-telephone-fill me-2"></i> @lang('services.combined.cta.button')
            </a>
        </div>
    </section>

@endsection
