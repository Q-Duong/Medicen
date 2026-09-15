<nav class="navbar navbar-expand-lg bg-light shadow-lg">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home.index') }}">
            <img src="{{ asset('assets/images/medicen-logo.png') }}" class="logo img-fluid"
                alt="Medicen Logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('about.index') ? 'active' : '' }}"
                        href="{{ route('about.index') }}">@lang('master_pages.header.aboutUs')</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}"
                        href="{{ route('services.index') }}">@lang('master_pages.header.service')</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('blogs.index') ? 'active' : '' }}"
                        href="{{ route('blogs.index') }}">@lang('master_pages.header.blog')</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('contact.index') ? 'active' : '' }}"
                        href="{{ route('contact.index') }}">@lang('master_pages.header.contact')</a>
                </li>

                {{-- <li class="nav-item ms-3">
                    <div class="globalnav-languages-item">
                        <button class="button-languages" dropdown="false">
                            <div class="globalnav__languages">
                                <span class="globalnav__languages-icon">
                                    <img src="{{ App::getLocale() == 'en' ? asset('assets/images/icon/united-states.png') : asset('assets/images/icon/vietnam.png') }}"
                                        alt="{{ App::getLocale() == 'en' ? 'en_GB' : 'vn' }}"
                                        class="globalnav__language-flag">
                                </span>
                            </div>
                        </button>
                        <div class="language-dropdown-menu languages-desktop">
                            <div class="globalnav__languages-list">
                                <div class="language {{ App::getLocale() == 'vn' ? 'selection' : '' }}">
                                    <a href="{{ route('locale', 'vn') }}" class="language__link"
                                        aria-label="@lang('master_pages.header.langVN')" data-analytics-title="@lang('master_pages.header.langVN')"></a>
                                    <span class="language__flag">
                                        <img src="{{ asset('assets/images/icon/vietnam.png') }}" alt="Tiếng Việt">
                                    </span>
                                    <span class="language__title">@lang('master_pages.header.vietnamese')</span>
                                </div>
                                <div class="language {{ App::getLocale() == 'en' ? 'selection' : '' }}">
                                    <a href="{{ route('locale', 'en') }}" class="language__link"
                                        aria-label="@lang('master_pages.header.langEN')" data-analytics-title="@lang('master_pages.header.langEN')"></a>
                                    <span class="language__flag">
                                        <img src="{{ asset('assets/images/icon/united-states.png') }}" alt="English">
                                    </span>
                                    <span class="language__title">@lang('master_pages.header.english')</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </li> --}}
            </ul>
        </div>
    </div>
</nav>
