@extends('layouts.default_auth')
@section('title', ($item->exists ? 'Sửa bài: ' . $item->title : 'Viết bài mới') . ' - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/news-admin.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $publishedAt = old('published_at', $item->published_at?->format('Y-m-d\TH:i'));
        $status = old('status', $item->status ?: 'draft');
    @endphp
    <div class="sf nw">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>{{ $item->exists ? 'Sửa bài viết' : 'Viết bài mới' }}</h1>
                <p>
                    @if ($item->exists)
                        <span class="nw-tag {{ ['Đã đăng' => 'nw-tag--live', 'Hẹn giờ' => 'nw-tag--soon', 'Bản nháp' => 'nw-tag--draft'][$item->stateLabel()] }}">{{ $item->stateLabel() }}</span>
                        {{ number_format($item->views, 0, ',', '.') }} lượt xem · sửa lần cuối {{ $item->updated_at->format('H:i d/m/Y') }}
                    @else
                        Viết xong có thể lưu nháp để xem trước, rồi bấm Đăng khi sẵn sàng.
                    @endif
                </p>
            </div>
            <div class="nw-head-actions">
                @if ($item->exists)
                    <a href="{{ route('news.show', $item->slug) }}" target="_blank" rel="noopener" class="sf-btn sf-btn--ghost"><i class="far fa-eye"></i> {{ $item->isLive() ? 'Xem trên website' : 'Xem trước' }}</a>
                @endif
                <a href="{{ route('news_admin.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="sf-alert nw-errors"><i class="fas fa-exclamation-circle"></i>
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ $item->exists ? route('news_admin.update', $item->id) : route('news_admin.store') }}" enctype="multipart/form-data" class="nw-form" id="nw-form">
            @csrf
            @if ($item->exists) @method('patch') @endif
            <div class="nw-main">
                <section class="sf-card nw-card">
                    <label class="nw-f">
                        <span>Tiêu đề <em>*</em></span>
                        <input type="text" name="title" value="{{ old('title', $item->title) }}" maxlength="255" required class="nw-title" placeholder="Vd: Medicen khám sức khỏe cho 2.000 công nhân KCN Tân Tạo" data-nw-title>
                    </label>
                    <label class="nw-f">
                        <span>Đường dẫn <small>(để trống: tự tạo theo tiêu đề)</small></span>
                        <span class="nw-slug"><span class="nw-slug__base">{{ url('news') }}/</span><input type="text" name="slug" value="{{ old('slug', $item->slug) }}" maxlength="255" placeholder="tu-dong-theo-tieu-de" data-nw-slug></span>
                    </label>
                    <label class="nw-f">
                        <span>Mô tả ngắn <small>(hiện ở danh sách tin và khi chia sẻ Facebook / Zalo; để trống thì lấy đầu bài)</small></span>
                        <textarea name="summary" rows="3" maxlength="500" data-nw-count>{{ old('summary', $item->summary) }}</textarea>
                        <small class="nw-count"></small>
                    </label>
                    <div class="nw-f">
                        <div class="nw-f__row">
                            <span>Nội dung <em>*</em></span>
                            <button type="button" class="sf-btn sf-btn--ghost nw-video-btn" data-nw-video title="Tải video MP4 từ máy lên, chèn vào chỗ con trỏ"><i class="fas fa-video"></i> Chèn video</button>
                            <input type="file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v" hidden data-nw-video-file>
                        </div>
                        <div class="nw-video-progress" hidden data-nw-video-progress>
                            <div class="nw-video-progress__bar"><i></i></div>
                            <span></span>
                        </div>
                        <textarea name="content" id="nw-editor">{{ old('content', $item->content) }}</textarea>
                    </div>
                </section>

                <details class="sf-card nw-card nw-seo" @if (old('seo_title', $item->seo_title) || old('seo_description', $item->seo_description)) open @endif>
                    <summary><i class="fas fa-search"></i> Tối ưu Google (SEO) <small>— tùy chọn, để trống dùng tiêu đề và mô tả ngắn</small></summary>
                    <label class="nw-f">
                        <span>Tiêu đề trên Google</span>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $item->seo_title) }}" maxlength="255" data-nw-count="60">
                        <small class="nw-count"></small>
                    </label>
                    <label class="nw-f">
                        <span>Mô tả trên Google</span>
                        <textarea name="seo_description" rows="2" maxlength="300" data-nw-count="160">{{ old('seo_description', $item->seo_description) }}</textarea>
                        <small class="nw-count"></small>
                    </label>
                </details>
            </div>

            <aside class="nw-side">
                <section class="sf-card nw-card">
                    <h3>Đăng bài</h3>
                    <div class="nw-status">
                        @foreach (\App\Models\News::STATUSES as $k => $label)
                            <label class="nw-radio"><input type="radio" name="status" value="{{ $k }}" @checked($status === $k)> {{ $k === 'draft' ? 'Bản nháp (khách chưa thấy)' : 'Đăng lên website' }}</label>
                        @endforeach
                    </div>
                    <label class="nw-f">
                        <span>Ngày giờ đăng <small>(để trống = ngay khi lưu; đặt tương lai = hẹn giờ)</small></span>
                        <input type="datetime-local" name="published_at" value="{{ $publishedAt }}">
                    </label>
                    <label class="nw-check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured))> Bài nổi bật <small>(hiện lớn đầu trang Tin tức, chỉ 1 bài)</small></label>
                    <div class="nw-actions">
                        <button type="submit" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Lưu</button>
                        <button type="submit" name="after" value="xem" class="sf-btn sf-btn--ghost"><i class="far fa-eye"></i> Lưu &amp; xem</button>
                    </div>
                </section>

                <section class="sf-card nw-card">
                    <h3>Ảnh đại diện</h3>
                    <label class="nw-image" data-nw-image>
                        <img src="{{ $item->imageUrl() }}" alt="" @if (!$item->image) hidden @endif data-nw-preview>
                        <span class="nw-image__empty" @if ($item->image) hidden @endif><i class="far fa-image"></i> Chọn ảnh<br><small>JPG, PNG, WEBP · ngang 16:9 đẹp nhất</small></span>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-nw-file>
                    </label>
                    @if ($item->image)
                        <label class="nw-check"><input type="checkbox" name="remove_image" value="1" data-nw-remove> Bỏ ảnh này</label>
                    @endif
                </section>

                <section class="sf-card nw-card">
                    <h3>Chuyên mục</h3>
                    <input type="text" name="category" value="{{ old('category', $item->category) }}" maxlength="80" list="nw-categories" placeholder="Chọn hoặc gõ chuyên mục mới" class="nw-input">
                    <datalist id="nw-categories">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
                </section>
            </aside>
        </form>

        @if ($item->exists)
            <form method="post" action="{{ route('news_admin.destroy', $item->id) }}" class="nw-delete" onsubmit="return confirm('Xóa bài này? Bài sẽ biến mất khỏi website.')">
                @csrf @method('delete')
                <button type="submit" class="sf-btn sf-btn--danger"><i class="far fa-trash-alt"></i> Xóa bài</button>
            </form>
        @endif
    </div>
@endsection
@push('js')
    <script src="{{ asset('backend/js/ckeditor/ckeditor.min.js') }}"></script>
    <script>
        window.NEWS_EDITOR = {
            uploadUrl: @json(route('news_admin.upload')), csrf: @json(csrf_token()),
            videoUrl: @json(route('news_admin.upload_video')), videoChunk: {{ \App\Services\News\NewsVideos::CHUNK }}, videoMaxMb: {{ \App\Services\News\NewsVideos::MAX_MB }},
        };
    </script>
    <script src="{{ versionResource('assets/js/tool/news-admin.js') }}"></script>
@endpush
