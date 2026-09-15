@extends('layouts.default')
@section('title', __('blog.seo.title'))
@section('seo_description', __('blog.seo.description'))

@section('content')
    <section class="page-header-section section-padding section-bg">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h2 class="mb-0">@lang('blog.header.title')</h2>
                    <p class="text-muted" style="font-size: 1.1rem;">@lang('blog.header.subtitle')</p>
                </div>
            </div>
        </div>
    </section>

    <section class="news-section section-padding" id="section_news_list">
        <div class="container">
            <div class="row">
            </div>
    </section>
@endsection
