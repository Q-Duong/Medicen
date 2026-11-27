{{-- Thẻ một bài tin tức. Biến: $n (App\Models\News) --}}
<article class="nc-card">
    <a href="{{ route('news.show', $n->slug) }}" class="nc-card__img" tabindex="-1" aria-hidden="true">
        @if ($n->image)
            <img src="{{ $n->imageUrl() }}" alt="" loading="lazy">
        @else
            <span class="nc-noimg"><img src="{{ asset('assets/images/medicen-logo.png') }}" alt=""></span>
        @endif
    </a>
    <div class="nc-card__body">
        @if ($n->category)<a href="{{ route('news.index', ['category' => $n->category]) }}" class="nc-cat">{{ $n->category }}</a>@endif
        <h3 class="nc-card__title"><a href="{{ route('news.show', $n->slug) }}">{{ $n->title }}</a></h3>
        <p class="nc-card__excerpt">{{ $n->excerpt(140) }}</p>
        <div class="nc-meta"><i class="bi bi-calendar3"></i> {{ $n->published_at?->format('d/m/Y') }} <span>·</span> {{ $n->readMinutes() }} phút đọc</div>
    </div>
</article>
