@extends('layouts.default')

@section('title', __('contact.seo.title'))
@section('seo_description', __('contact.seo.description'))

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
    <section class="page-header-section section-padding section-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-12 mx-auto text-center">
                    <h2 class="mb-3 fw-bold">@lang('contact.header.title')</h2>
                    <p class="text-muted" style="font-size: 1.1rem;">@lang('contact.header.subtitle')</p>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section section-padding">
        <div class="container">
            <div class="row justify-content-between">
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
@endsection
@push('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
    <script src="{{ versionResource('assets/js/support/contact.js') }}" defer></script>
@endpush
