@extends('layouts.default')
@section('title', __('about_us.seo.title'))
@section('seo_description', __('about_us.seo.description'))
@section('content')
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

            </div>
        </div>
    </section>

    <section class="section-padding section-bg">
        <div class="container">

            <div class="row mb-5">
                <div class="col-12 text-center mb-5">
                    <h2 class="mb-2">@lang('about_us.core_fields.title')</h2>
                </div>
    
                <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-0">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-4 border-0">
                        <h5 class="mb-3 text-primary">@lang('about_us.core_fields.item1.title')</h5>
                        <p class="mb-0 text-muted">@lang('about_us.core_fields.item1.desc')</p>
                    </div>
                </div>
    
                <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-0">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-4 border-0">
                        <h5 class="mb-3 text-primary">@lang('about_us.core_fields.item2.title')</h5>
                        <p class="mb-0 text-muted">@lang('about_us.core_fields.item2.desc')</p>
                    </div>
                </div>
    
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-4 border-0">
                        <h5 class="mb-3 text-primary">@lang('about_us.core_fields.item3.title')</h5>
                        <p class="mb-0 text-muted">@lang('about_us.core_fields.item3.desc')</p>
                    </div>
                </div>
            </div>
    
            <div class="row mb-5">
                <div class="col-lg-6 col-12 mb-4 mb-lg-0">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-5 border-0">
                        <h4 class="mb-3 text-danger">@lang('about_us.vision.title')</h4>
                        <p class="mb-0">@lang('about_us.vision.desc')</p>
                    </div>
                </div>
    
                <div class="col-lg-6 col-12">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-5 border-0">
                        <h4 class="mb-3 text-danger">@lang('about_us.mission_detail.title')</h4>
                        <p class="mb-0 text-muted">@lang('about_us.mission_detail.desc')</p>
                    </div>
                </div>
            </div>
    
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="mb-2">@lang('about_us.why_choose.title')</h2>
                </div>
    
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-4 text-center border-0">
                        <h3 class="mb-3 text-danger font-weight-bold">15+</h3>
                        <h6 class="mb-3">@lang('about_us.why_choose.item1.title')</h6>
                        <p class="mb-0 text-muted small">@lang('about_us.why_choose.item1.desc')</p>
                    </div>
                </div>
    
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-4 text-center border-0">
                        <i class="bi-shield-check text-primary fs-1 mb-3 d-block"></i>
                        <h6 class="mb-3">@lang('about_us.why_choose.item2.title')</h6>
                        <p class="mb-0 text-muted small">@lang('about_us.why_choose.item2.desc')</p>
                    </div>
                </div>
    
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-4 text-center border-0">
                        <i class="bi-clock-history text-primary fs-1 mb-3 d-block"></i>
                        <h6 class="mb-3">@lang('about_us.why_choose.item3.title')</h6>
                        <p class="mb-0 text-muted small">@lang('about_us.why_choose.item3.desc')</p>
                    </div>
                </div>
    
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="custom-text-box h-100 rounded-4 shadow-sm bg-white p-4 text-center border-0">
                        <i class="bi-tools text-primary fs-1 mb-3 d-block"></i>
                        <h6 class="mb-3">@lang('about_us.why_choose.item4.title')</h6>
                        <p class="mb-0 text-muted small">@lang('about_us.why_choose.item4.desc')</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
