<footer class="site-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-2 col-12 mb-4">
                <img src="{{ asset('assets/images/medicen-logo.png') }}" class="logo img-fluid"
                    alt="@lang('master_pages.footer.logo_alt')">
            </div>
            <div class="col-lg-4 col-md-6 col-12 mb-4">
                <h5 class="site-footer-title mb-3">@lang('master_pages.footer.quickLinks')</h5>
                <ul class="footer-menu">
                    <li class="footer-menu-item">
                        <a class="footer-menu-link" href="{{ route('about.index') }}">@lang('master_pages.header.aboutUs')</a>
                    </li>

                    <li class="footer-menu-item">
                        <a class="footer-menu-link" href="{{ route('services.index') }}">@lang('master_pages.header.service')</a>
                    </li>

                    <li class="footer-menu-item">
                        <a class="footer-menu-link" href="{{ route('blogs.index') }}">@lang('master_pages.header.blog')</a>
                    </li>

                    <li class="footer-menu-item">
                        <a class="footer-menu-link" href="{{ route('contact.index') }}">@lang('master_pages.header.contact')</a>
                    </li>

                </ul>
            </div>

            <div class="col-lg-6 col-md-6 col-12 mx-auto">
                <h5 class="site-footer-title mb-3">@lang('master_pages.header.contact')</h5>

                <p class=" mb-2">
                    <a target="_blank" href="https://maps.app.goo.gl/TYeztLZHSpeH7hHd9" class="site-footer-link">
                        <i class="bi-geo-alt me-2"></i>@lang('master_pages.footer.address')
                        @lang('master_pages.footer.addHCM')
                    </a>
                </p>

                <p class=" mb-2">
                    <i class="bi bi-phone me-2 site-footer-link"></i>
                    <a href="tel:0812348381" class="site-footer-link">@lang('master_pages.footer.phone') @lang('contact.info.branch_1.phone')</a>
                </p>

                <p class=" mb-4 site-footer-link">
                    <i class="bi bi-clock me-2"></i>
                    @lang('master_pages.footer.workingHours')
                </p>

                
                <div class="d-flex mb-1">
                    <h5>@lang('master_pages.footer.connectWithUs')</h5>
                </div>
                <ul class="social-icon-home">
                    <li class="social-icon-item-home">
                        <a target="_blank" href="https://zalo.me/4496283275293639849"
                            class="social-icon-link-home">Z</a>
                    </li>

                    <li class="social-icon-item-home">
                        <a target="_blank" href="https://www.facebook.com/profile.php?id=100090839746348"
                            class="social-icon-link-home bi-facebook"></a>
                    </li>

                    <li class="social-icon-item-home">
                        <a href="mailto:medicen.company@gmail.com" class="social-icon-link-home bi-envelope"></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="site-footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-7 col-12">
                    <p class="copyright-text mb-0">@lang('master_pages.footer.copy')</p>
                </div>
                <div class="col-lg-6 col-md-5 col-12 ">
                    <p class="copyright-text mb-0">
                        @lang('master_pages.footer.ctyName')
                    </p>
                    <p class="copyright-text mb-0">
                        @lang('master_pages.footer.businessLicense')
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
