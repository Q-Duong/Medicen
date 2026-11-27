@extends('layouts.default_auth')
@section('title', 'Tin tức - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/news-admin.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $qs = fn ($extra) => array_filter(array_merge(request()->only(['q', 'chuyen_muc', 'tt']), $extra), fn ($x) => $x !== null && $x !== '');
        $tabs = ['' => ['Tất cả', 'tat_ca'], 'da_dang' => ['Đã đăng', 'da_dang'], 'hen_gio' => ['Hẹn giờ', 'hen_gio'], 'nhap' => ['Bản nháp', 'nhap'], 'da_xoa' => ['Đã xóa', 'da_xoa']];
        $stateTag = ['Đã đăng' => 'nw-tag--live', 'Hẹn giờ' => 'nw-tag--soon', 'Bản nháp' => 'nw-tag--draft', 'Đã xóa' => 'nw-tag--deleted'];
    @endphp
    <div class="sf nw">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Tin tức</h1>
                <p>Bài viết hiện ở mục <a href="{{ route('news.index') }}" target="_blank" rel="noopener">Tin tức trên website <i class="fas fa-external-link-alt"></i></a>. Bản nháp chỉ người quản lý xem trước được; đặt ngày đăng ở tương lai để hẹn giờ.</p>
            </div>
            <a href="{{ route('news_admin.create') }}" class="sf-btn sf-btn--primary"><i class="fas fa-plus"></i> Viết bài mới</a>
        </div>

        <nav class="nw-chips" aria-label="Lọc theo trạng thái">
            @foreach ($tabs as $k => [$label, $count])
                @if ($k === '' || ($counts[$count] ?? 0) > 0 || $state === $k)
                    <a href="{{ route('news_admin.index', $qs(['tt' => $k ?: null])) }}" class="{{ $state === $k ? 'is-active' : '' }}">{{ $label }} <b>{{ $counts[$count] ?? 0 }}</b></a>
                @endif
            @endforeach
        </nav>

        <form method="GET" action="{{ route('news_admin.index') }}" class="sf-filters">
            @if ($state)<input type="hidden" name="tt" value="{{ $state }}">@endif
            <label class="sf-field sf-field--grow"><span>Tìm kiếm</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Tiêu đề, mô tả…" autocomplete="off"></label>
            <label class="sf-field"><span>Chuyên mục</span>
                <select name="chuyen_muc" onchange="this.form.submit()">
                    <option value="">Tất cả</option>
                    @foreach ($categories as $c)<option value="{{ $c }}" @selected(request('chuyen_muc') === $c)>{{ $c }}</option>@endforeach
                </select></label>
            <div class="sf-filters__actions">
                <button type="submit" class="sf-btn sf-btn--dark">Lọc</button>
                @if (array_filter(request()->only(['q', 'chuyen_muc'])))
                    <a href="{{ route('news_admin.index', $qs(['q' => null, 'chuyen_muc' => null])) }}" class="sf-btn sf-btn--ghost"><i class="fas fa-times"></i> Bỏ lọc</a>
                @endif
            </div>
        </form>

        <div class="sf-table">
            <table>
                <thead><tr><th>Bài viết</th><th>Chuyên mục</th><th>Trạng thái</th><th>Ngày đăng</th><th>Lượt xem</th><th class="sf-actions-h">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($news as $n)
                        @php
                            $label = $n->trashed() ? 'Đã xóa' : $n->stateLabel();
                        @endphp
                        <tr>
                            <td data-label="Bài viết" class="nw-post">
                                <a href="{{ $n->trashed() ? '#' : route('news_admin.edit', $n->id) }}" class="nw-post__link">
                                    <span class="nw-thumb">@if ($n->image)<img src="{{ $n->imageUrl() }}" alt="" loading="lazy">@else<i class="far fa-image"></i>@endif</span>
                                    <span>
                                        <b>{{ $n->title }}</b>@if ($n->is_featured)<span class="nw-star" title="Bài nổi bật"><i class="fas fa-star"></i></span>@endif
                                        <span class="nw-sub">{{ $n->excerpt(110) }}</span>
                                        <span class="nw-sub">{{ $n->author->name ?? '—' }} · sửa {{ $n->updated_at->format('H:i d/m/Y') }}</span>
                                    </span>
                                </a>
                            </td>
                            <td data-label="Chuyên mục">{{ $n->category ?: '—' }}</td>
                            <td data-label="Trạng thái"><span class="nw-tag {{ $stateTag[$label] }}">{{ $label }}</span></td>
                            <td data-label="Ngày đăng" class="sf-nowrap">{{ $n->published_at ? $n->published_at->format('H:i d/m/Y') : '—' }}</td>
                            <td data-label="Lượt xem">{{ number_format($n->views, 0, ',', '.') }}</td>
                            <td class="sf-actions sf-nowrap">
                                @if ($n->trashed())
                                    <span class="nw-sub">xóa {{ $n->deleted_at->format('d/m/Y') }}</span>
                                    <form method="post" action="{{ route('news_admin.restore', $n->id) }}" class="nw-inline">
                                        @csrf
                                        <button type="submit" class="sf-btn sf-btn--ghost nw-restore"><i class="fas fa-undo"></i> Khôi phục</button>
                                    </form>
                                @else
                                <a href="{{ route('news.show', $n->slug) }}" target="_blank" rel="noopener" class="sf-icon-btn" title="{{ $n->isLive() ? 'Xem trên website' : 'Xem trước' }}"><i class="far fa-eye"></i></a>
                                <a href="{{ route('news_admin.edit', $n->id) }}" class="sf-icon-btn" title="Sửa"><i class="fas fa-pen"></i></a>
                                <form method="post" action="{{ route('news_admin.toggle', $n->id) }}" class="nw-inline">
                                    @csrf
                                    <button type="submit" class="sf-icon-btn" title="{{ $n->isLive() ? 'Gỡ xuống (về bản nháp)' : 'Đăng ngay' }}"><i class="fas fa-{{ $n->isLive() ? 'eye-slash' : 'paper-plane' }}"></i></button>
                                </form>
                                <form method="post" action="{{ route('news_admin.destroy', $n->id) }}" class="nw-inline" data-msg="Xóa bài “{{ $n->title }}”? Bài sẽ biến mất khỏi website." onsubmit="return confirm(this.dataset.msg)">
                                    @csrf @method('delete')
                                    <button type="submit" class="sf-icon-btn sf-icon-btn--danger" title="Xóa"><i class="far fa-trash-alt"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sf-empty">
                            {{ $counts['tat_ca'] ? 'Không có bài nào khớp bộ lọc.' : 'Chưa có bài viết nào. Bấm "Viết bài mới" để bắt đầu.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($news->hasPages())
            <div class="sf-pager">
                @include('layouts.section.admin.pagination_showing_current', ['items' => $news])
                {{ $news->links('pagination::custom') }}
            </div>
        @endif
    </div>
@endsection
