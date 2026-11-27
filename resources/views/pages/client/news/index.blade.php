@extends('layouts.default')
@section('title', ($category ? $category . ' | ' : '') . __('blog.seo.title'))
@section('seo_description', __('blog.seo.description'))
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/news.css') }}" type="text/css">
@endpush

@section('content')
    <section class="nc-hero">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="{{ route('home.index') }}">@lang('master_pages.header.home')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">@lang('master_pages.header.blog')</li>
                </ol>
            </nav>
            <h1>@lang('blog.header.title')</h1>
            <p>@lang('blog.header.subtitle')</p>
        </div>
    </section>

    <section class="nc-section">
        <div class="container">
            <div class="nc-toolbar">
                <nav class="nc-chips" aria-label="Chuyên mục">
                    <a href="{{ route('news.index', array_filter(['q' => $q])) }}" class="{{ $category === '' ? 'is-active' : '' }}">Tất cả</a>
                    @foreach ($categories as $c => $n)
                        <a href="{{ route('news.index', array_filter(['category' => $c, 'q' => $q])) }}" class="{{ $category === $c ? 'is-active' : '' }}">{{ $c }} <span>{{ $n }}</span></a>
                    @endforeach
                </nav>
                <form method="GET" action="{{ route('news.index') }}" class="nc-search" role="search">
                    @if ($category !== '')<input type="hidden" name="category" value="{{ $category }}">@endif
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Tìm bài viết…" aria-label="Tìm bài viết">
                </form>
            </div>

            @if ($q !== '')
                <p class="nc-result">{{ $news->total() }} bài khớp “{{ $q }}”{{ $category !== '' ? ' trong ' . $category : '' }} · <a href="{{ route('news.index', array_filter(['category' => $category])) }}">Bỏ tìm kiếm</a></p>
            @endif

            @if ($featured)
                <article class="nc-featured">
                    <a href="{{ route('news.show', $featured->slug) }}" class="nc-featured__img" tabindex="-1" aria-hidden="true">
                        @if ($featured->image)
                            <img src="{{ $featured->imageUrl() }}" alt="">
                        @else
                            <span class="nc-noimg"><img src="{{ asset('assets/images/medicen-logo.png') }}" alt=""></span>
                        @endif
                    </a>
                    <div class="nc-featured__body">
                        <div class="nc-featured__tags">
                            <span class="nc-badge">Mới nhất</span>
                            @if ($featured->category)<a href="{{ route('news.index', ['category' => $featured->category]) }}" class="nc-cat">{{ $featured->category }}</a>@endif
                        </div>
                        <h2><a href="{{ route('news.show', $featured->slug) }}">{{ $featured->title }}</a></h2>
                        <p>{{ $featured->excerpt(260) }}</p>
                        <div class="nc-meta"><i class="bi bi-calendar3"></i> {{ $featured->published_at->format('d/m/Y') }} <span>·</span> {{ $featured->readMinutes() }} phút đọc</div>
                        <a href="{{ route('news.show', $featured->slug) }}" class="nc-more">@lang('blog.readMore') <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            @endif

            @if ($news->isNotEmpty())
                <div class="nc-grid">
                    @foreach ($news as $n)
                        @include('pages.client.news._card', ['n' => $n])
                    @endforeach
                </div>
                @if ($news->hasPages())
                    <div class="nc-pager">{{ $news->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
                @endif
            @elseif (!$featured)
                <div class="nc-empty">
                    <i class="bi bi-newspaper"></i>
                    <p>{{ $q !== '' || $category !== '' ? 'Không có bài viết nào khớp.' : 'Tin tức đang được cập nhật, mời bạn quay lại sau.' }}</p>
                    @if ($q !== '' || $category !== '')<a href="{{ route('news.index') }}" class="nc-more">Xem tất cả tin tức</a>@endif
                </div>
            @endif
        </div>
    </section>
@endsection
