@if ($paginator->hasPages())
<nav class="pager" aria-label="Phân trang">
    <span class="pager__info">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} trong {{ $paginator->total() }} file</span>
    <div class="pager__links">
        @if ($paginator->onFirstPage())
            <span class="pager__btn is-disabled">Trước</span>
        @else
            <a class="pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Trước</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pager__btn is-disabled">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pager__btn is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pager__btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau</a>
        @else
            <span class="pager__btn is-disabled">Sau</span>
        @endif
    </div>
</nav>
@endif
