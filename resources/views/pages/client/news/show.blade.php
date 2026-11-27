@extends('layouts.default')
@section('title', ($item->seo_title ?: $item->title) . ' | Medicen')
@section('seo_description', $item->seo_description ?: $item->excerpt(160))
@if ($item->image)
    @section('seo_image', route('news.image', $item->image))
@endif
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/news.css') }}" type="text/css">
    @if ($preview)<meta name="robots" content="noindex, nofollow">@endif
@endpush

@section('content')
    @if ($preview)
        <div class="nc-preview">
            <div class="container"><i class="bi bi-eye"></i> <b>Xem trước</b> — bài {{ $item->status === 'published' ? 'hẹn giờ đăng lúc ' . $item->published_at->format('H:i d/m/Y') : 'đang là bản nháp' }}, khách chưa thấy.
                <a href="{{ route('news_admin.edit', $item->id) }}">Sửa bài</a></div>
        </div>
    @endif

    <article class="nc-article">
        <header class="nc-hero nc-hero--article">
            <div class="container nc-narrow">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3">
                        <li class="breadcrumb-item"><a href="{{ route('home.index') }}">@lang('master_pages.header.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('news.index') }}">@lang('master_pages.header.blog')</a></li>
                        @if ($item->category)<li class="breadcrumb-item"><a href="{{ route('news.index', ['category' => $item->category]) }}">{{ $item->category }}</a></li>@endif
                    </ol>
                </nav>
                <h1>{{ $item->title }}</h1>
                <div class="nc-meta nc-meta--light">
                    <span><i class="bi bi-calendar3"></i> {{ ($item->published_at ?? $item->updated_at)->format('d/m/Y') }}</span>
                    <span><i class="bi bi-clock"></i> {{ $item->readMinutes() }} phút đọc</span>
                    @if (!$preview && $item->views > 20)<span><i class="bi bi-eye"></i> {{ number_format($item->views, 0, ',', '.') }} lượt xem</span>@endif
                </div>
            </div>
        </header>

        <div class="container nc-narrow nc-article__body">
            @if ($item->image)
                <figure class="nc-cover"><img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}"></figure>
            @endif
            @if ($item->summary)
                <p class="nc-lead">{{ $item->summary }}</p>
            @endif
            <div class="nc-content">{!! $item->content !!}</div>

            <div class="nc-share">
                <span>Chia sẻ bài viết</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('news.show', $item->slug)) }}" target="_blank" rel="noopener" class="nc-share__btn nc-share__btn--fb" aria-label="Chia sẻ Facebook"><i class="bi bi-facebook"></i></a>
                <a href="https://sp.zalo.me/share_inline?url={{ urlencode(route('news.show', $item->slug)) }}" target="_blank" rel="noopener" class="nc-share__btn nc-share__btn--zalo" aria-label="Chia sẻ Zalo">Zalo</a>
                <button type="button" class="nc-share__btn" data-copy="{{ route('news.show', $item->slug) }}" aria-label="Sao chép liên kết"><i class="bi bi-link-45deg"></i></button>
            </div>

            @if ($prev || $next)
                <nav class="nc-prevnext" aria-label="Bài trước / sau">
                    @if ($prev)<a href="{{ route('news.show', $prev->slug) }}" class="nc-prevnext__item"><small><i class="bi bi-arrow-left"></i> Bài trước</small><span>{{ $prev->title }}</span></a>@else<span></span>@endif
                    @if ($next)<a href="{{ route('news.show', $next->slug) }}" class="nc-prevnext__item nc-prevnext__item--next"><small>Bài sau <i class="bi bi-arrow-right"></i></small><span>{{ $next->title }}</span></a>@endif
                </nav>
            @endif

            <aside class="nc-cta">
                <div>
                    <h3>Cần khám sức khỏe lưu động cho doanh nghiệp?</h3>
                    <p>Medicen có xe X-quang lưu động và đội kỹ thuật viên, đến tận nơi khám cho công ty, trường học, khu công nghiệp.</p>
                </div>
                <a href="{{ route('contact.index') }}" class="nc-cta__btn">Liên hệ tư vấn <i class="bi bi-arrow-right"></i></a>
            </aside>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="nc-section nc-section--soft">
            <div class="container">
                <div class="nc-section__head">
                    <h2>Bài viết liên quan</h2>
                    <a href="{{ route('news.index') }}" class="nc-more">Xem tất cả <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="nc-grid">
                    @foreach ($related as $n)
                        @include('pages.client.news._card', ['n' => $n])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@push('js')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => \Illuminate\Support\Str::limit($item->title, 110, ''),
            'description' => $item->seo_description ?: $item->excerpt(160),
            'image' => $item->image ? [route('news.image', $item->image)] : null,
            'datePublished' => $item->published_at?->toAtomString(),
            'dateModified' => $item->updated_at->toAtomString(),
            'mainEntityOfPage' => route('news.show', $item->slug),
            'publisher' => ['@type' => 'Organization', 'name' => 'Medicen', 'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/images/medicen-logo.png')]],
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
    <script>
        document.querySelectorAll('[data-copy]').forEach(function (b) {
            b.addEventListener('click', function () {
                navigator.clipboard && navigator.clipboard.writeText(b.dataset.copy).then(function () {
                    b.classList.add('is-done');
                    b.innerHTML = '<i class="bi bi-check2"></i>';
                    setTimeout(function () { b.classList.remove('is-done'); b.innerHTML = '<i class="bi bi-link-45deg"></i>'; }, 1800);
                });
            });
        });
    </script>
@endpush
