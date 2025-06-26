<div id="globalheader">
    <header id="globalnav" class="header" lang="vi-VN" dir="ltr" aria-label="Global">
        <div class="globalnav-content">
            <ul class="globalnav-list">
                <li class="globalnav-logo">
                    <div class="globalnav-link-logo">
                        <a href="{{ Route('home.index') }}" aria-label="Medicen" data-analytics-title="Medicen"
                            previewlistener="true">
                            <img src="{{ asset('assets/images/logo.png') }}" class="globalnav-link-image"
                                alt="Medicen">
                            <span class="globalnav-link-text">Medicen</span>
                        </a>
                    </div>
                </li>
                <li class="globalnav">
                    <a href="{{ Route('about.index') }}" aria-label="Giới thiệu" data-analytics-title="Giới thiệu"
                        class="globalnav-link-text" previewlistener="true">Giới thiệu</a>
                </li>
                <li class="globalnav">
                    <a href="" aria-label="Dịch vụ" data-analytics-title="Dịch vụ" class="globalnav-link-text"
                        previewlistener="true">Dịch vụ</a>
                    <div class="globalnav-submenu-link">
                        <div class="globalnav-submenu-group">
                            <ul class="submenu-list">
                                <h4 class="submenu-header">@lang('masterpages.header.exploreVart')</h4>
                                @foreach ($getAllService as $key => $service)
                                    <li class="submenu-list-item">
                                        <a href="{{ Route('service.details', $service->service_slug) }}"
                                            class="submenu-link" aria-label="{{ $service->service_title }}"
                                            data-analytics-title="{{ $service->service_title }}" previewlistener="true">
                                            {{ $service->service_title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </li>

                <li class="globalnav">
                    <a href="{{ Route('home.index') }}" aria-label="Tin tức" data-analytics-title="Tin tức"
                        class="globalnav-link-text" previewlistener="true">Tin tức</a>
                </li>
                <li class="globalnav">
                    <a href="{{ Route('contact.index') }}" aria-label="Liên hệ" data-analytics-title="Liên hệ"
                        class="globalnav-link-text" previewlistener="true">Liên hệ</a>
                </li>
                <li data-topnav-flyout-label="Tìm kiếm trên medicen.vn" class="globalnav-item globalnav-search "
                    data-globalnav-iconflyout-enabled="true" data-analytics-region="search">
                    <a role="button" id="globalnav-menubutton-link-search" href="#"
                        data-topnav-flyout-trigger-regular="true" data-topnav-flyout-trigger-compact="true"
                        aria-label="Tìm kiếm trên medicen.vn" class="globalnav-link-text globalnav-link-search"
                        aria-expanded="false" data-analytiregulacs-title="open - search field">
                        <span class="globalnav-image-r">
                            <svg height="48" viewBox="0 0 17 48" width="17" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="m16.2294 29.9556-4.1755-4.0821a6.4711 6.4711 0 1 0 -1.2839 1.2625l4.2005 4.1066a.9.9 0 1 0 1.2588-1.287zm-14.5294-8.0017a5.2455 5.2455 0 1 1 5.2455 5.2527 5.2549 5.2549 0 0 1 -5.2455-5.2527z">
                                </path>
                            </svg>
                        </span>
                    </a>
                    <div class="globalnav-flyout globalnav-submenu">
                        <div class="globalnav-submenu-group">
                            <ul class="submenu-list">
                                <h4 class="submenu-header">Tin tức</h4>
                                @foreach ($getAllBlogCategory as $key => $blogCategory)
                                    <li class="submenu-list-item">
                                        <a href="{{ Route('blog_category.index', $blogCategory->blog_category_slug) }}"
                                            class="submenu-link" aria-label="{{ $blogCategory->blog_category_name }}"
                                            data-analytics-title="{{ $blogCategory->blog_category_name }}"
                                            previewlistener="true">{{ $blogCategory->blog_category_name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </li>
                <li class="globalnav-item globalnav-account">
                    <a href="" data-analytics-title="account"
                        class="globalnav-link-text globalnav-link-account">
                        <div class="ac-gn-bagview-nav-image-container">
                            <svg id="Outlined" xmlns="http://www.w3.org/2000/svg"
                                width="18" height="50" viewBox="0 0 16 25">
                                <g id="person.crop.circle_compact">
                                    <rect id="box_" width="18" height="50" fill="none"></rect>
                                    <path id="art_"
                                        d="M15.09,12.5a7.1,7.1,0,1,1-7.1-7.1A7.1077,7.1077,0,0,1,15.09,12.5ZM7.99,6.6a5.89,5.89,0,0,0-4.4609,9.7471c.6069-.9658,2.48-1.6787,4.4609-1.6787s3.8545.7129,4.4615,1.6787A5.89,5.89,0,0,0,7.99,6.6ZM7.99,8.4A2.5425,2.5425,0,0,0,5.5151,11,2.5425,2.5425,0,0,0,7.99,13.6,2.5424,2.5424,0,0,0,10.4653,11,2.5424,2.5424,0,0,0,7.99,8.4Z"
                                        fill="6E6E73">
                                    </path>
                                </g>
                            </svg>
                        </div>
                    </a>
                    <div class="globalnav-flyout globalnav-submenu">
                        <div class="globalnav-submenu-group">
                            <ul class="submenu-list">
                                <h4 class="submenu-header">Tin tức</h4>
                                @foreach ($getAllBlogCategory as $key => $blogCategory)
                                    <li class="submenu-list-item">
                                        <a href="{{ Route('blog_category.index', $blogCategory->blog_category_slug) }}"
                                            class="submenu-link" aria-label="{{ $blogCategory->blog_category_name }}"
                                            data-analytics-title="{{ $blogCategory->blog_category_name }}"
                                            previewlistener="true">{{ $blogCategory->blog_category_name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </li>
            </ul>
            <div class="globalnav-menutrigger">
                <button id="globalnav-menutrigger-button" aria-controls="globalnav-list" aria-label="Menu"
                    data-topnav-menu-label-open="Menu" data-topnav-menu-label-close="Close"
                    data-topnav-flyout-trigger-compact="menu" class="globalnav-menutrigger-button"
                    aria-expanded="false">
                    <svg width="18" height="18" viewBox="0 0 18 18">
                        <polyline id="globalnav-menutrigger-bread-bottom" fill="none" stroke="currentColor"
                            stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" points="2 12, 16 12"
                            class="globalnav-menutrigger-bread globalnav-menutrigger-bread-bottom">
                            <animate id="globalnav-anim-menutrigger-bread-bottom-open" attributeName="points"
                                keyTimes="0;0.5;1" dur="0.24s" begin="indefinite" fill="freeze"
                                calcMode="spline" keySplines="0.42, 0, 1, 1;0, 0, 0.58, 1"
                                values=" 2 12, 16 12; 2 9, 16 9; 3.5 15, 15 3.5"></animate>
                            <animate id="globalnav-anim-menutrigger-bread-bottom-close" attributeName="points"
                                keyTimes="0;0.5;1" dur="0.24s" begin="indefinite" fill="freeze"
                                calcMode="spline" keySplines="0.42, 0, 1, 1;0, 0, 0.58, 1"
                                values=" 3.5 15, 15 3.5; 2 9, 16 9; 2 12, 16 12"></animate>
                        </polyline>
                        <polyline id="globalnav-menutrigger-bread-top" fill="none" stroke="currentColor"
                            stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" points="2 5, 16 5"
                            class="globalnav-menutrigger-bread globalnav-menutrigger-bread-top">
                            <animate id="globalnav-anim-menutrigger-bread-top-open" attributeName="points"
                                keyTimes="0;0.5;1" dur="0.24s" begin="indefinite" fill="freeze"
                                calcMode="spline" keySplines="0.42, 0, 1, 1;0, 0, 0.58, 1"
                                values=" 2 5, 16 5; 2 9, 16 9; 3.5 3.5, 15 15"></animate>
                            <animate id="globalnav-anim-menutrigger-bread-top-close" attributeName="points"
                                keyTimes="0;0.5;1" dur="0.24s" begin="indefinite" fill="freeze"
                                calcMode="spline" keySplines="0.42, 0, 1, 1;0, 0, 0.58, 1"
                                values=" 3.5 3.5, 15 15; 2 9, 16 9; 2 5, 16 5"></animate>
                        </polyline>
                    </svg>
                </button>
            </div>
        </div>
    </header>
    <div class="offcanvas-menu-wrapper">
        <div id="mobile-menu-wrap"></div>
        <div class="mobile-menu">
            <nav class="mobile-menu-nav">
                <ul>
                    <li class="globalnav">
                        <a href="{{ Route('about.index') }}" aria-label="Giới thiệu"
                            data-analytics-title="Giới thiệu" previewlistener="true">
                            <span class="globalnav-link-text">Giới thiệu</span>
                        </a>
                    </li>
                    <li class="globalnav">
                        <span class="globalnav-link-text" aria-label="Dịch vụ" data-analytics-title="Dịch vụ">Dịch vụ
                        </span>
                        <ul class="submenu-list">
                            <li class="submenu-list-item">
                                <a href="" class="submenu-link" data-analytics-title="@lang('masterpages.header.exploreVart')"
                                    previewlistener="true">
                                    @lang('masterpages.header.exploreVart')
                                </a>
                            </li>
                            @foreach ($getAllService as $key => $service)
                                <li class="submenu-list-item">
                                    <a href="{{ Route('service.details', $service->service_slug) }}"
                                        class="submenu-link" data-analytics-title="{{ $service->service_title }}"
                                        previewlistener="true">
                                        {{ $service->service_title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                    <li class="globalnav">
                        <span class="globalnav-link-text" aria-label="Tin tức" data-analytics-title="Tin tức"
                            previewlistener="true">Tin tức
                        </span>
                    </li>
                    <li class="globalnav">
                        <a href="{{ Route('contact.index') }}" aria-label="Liên hệ" data-analytics-title="Liên hệ"
                            previewlistener="true">
                            <span class="globalnav-link-text">Liên hệ</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>
