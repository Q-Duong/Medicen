@extends('layouts.default_auth')
@section('title', $title . ' - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/assistant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        // Cấu hình cho assets/js/tool/assistant/org-assistant.js
        $assistantConfig = [
            'askUrl' => route('assistant.ask', $department),
            'listUrl' => route('assistant.conversations', $department),
            'pageUrl' => route('assistant.show', $department),
            'department' => $department,
            'modes' => $modes,
            'initialConversation' => $initialConversation,
        ];
    @endphp
    <div class="assistant" id="assistant" data-config='@json($assistantConfig)'>
        <aside class="side" id="side" aria-label="Lịch sử trò chuyện">
            <div class="side__top">
                <button type="button" class="ghost-btn side__new" id="new-chat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Cuộc trò chuyện mới
                </button>
                <input type="search" class="side__search" id="conv-search" placeholder="Tìm theo tiêu đề…" aria-label="Tìm cuộc trò chuyện">
                @if ($canViewAll)
                    <div class="side__tabs" role="group" aria-label="Phạm vi">
                        <button type="button" data-scope="mine" aria-pressed="true">Của tôi</button>
                        <button type="button" data-scope="all" aria-pressed="false">Tất cả nhân viên</button>
                    </div>
                @endif
            </div>
            <div class="side__label">Gần đây</div>
            <ul class="side__list" id="conv-list"></ul>
        </aside>
        <div class="side-backdrop" id="side-backdrop"></div>

        <div class="assistant__main">
        <div class="assistant__head">
            <button type="button" class="icon-btn" id="side-toggle" title="Ẩn/hiện lịch sử" aria-label="Ẩn/hiện lịch sử" aria-controls="side">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16"/></svg>
            </button>
            <div class="grow">
            <h3 class="assistant__title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 6.1L20 10l-6.1 1.9L12 18l-1.9-6.1L4 10l6.1-1.9z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z" opacity=".6"/></svg>
                {{ $title }}
            </h3>
            <span class="assistant__sub" id="conv-heading"></span>
            </div>
            @if (count($assistants) > 1)
                {{-- Admin dùng được nhiều trợ lý: chuyển nhanh giữa các phòng --}}
                <label class="dept-switch">
                    <span class="sr-only">Chọn trợ lý</span>
                    <select id="dept-switch" aria-label="Chọn trợ lý phòng ban">
                        @foreach ($assistants as $key => $label)
                            <option value="{{ route('assistant.show', $key) }}" @selected($key === $department)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <button type="button" class="ghost-btn" id="assistant-reset" title="Cuộc trò chuyện mới">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
                <span>Cuộc trò chuyện mới</span>
            </button>
        </div>

        <div class="assistant__log" id="assistant-log" aria-live="polite">
            <div class="thread" id="thread">
                <div class="empty" id="empty">
                    <span class="empty__mark"><svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 6.1L20 10l-6.1 1.9L12 18l-1.9-6.1L4 10l6.1-1.9z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z" opacity=".6"/></svg></span>
                    <h2>Chào {{ Auth::user()->display_name }}</h2>
                    <p>Em là {{ mb_strtolower(mb_substr($title, 0, 1)) . mb_substr($title, 1) }}. Anh/chị chọn chức năng bên dưới hoặc hỏi trực tiếp ạ.</p>
                </div>
            </div>
        </div>

        <div class="assistant__composer">
            <div class="readonly-bar" id="readonly-bar" hidden>
                <i class="far fa-eye"></i><span id="readonly-text"></span>
            </div>
            <div class="composer-wrap" id="composer-wrap">
                <div class="chips" id="chips"></div>

                <form class="composer" id="assistant-form" novalidate>
                    @include('pages.admin.assistant.forms.quote')
                    @include('pages.admin.assistant.forms.profit')

                    <textarea id="assistant-input" rows="1" maxlength="4000" aria-label="Tin nhắn"></textarea>

                    <div class="composer__bar">
                        <div class="mode" id="mode">
                            <button type="button" class="pill-btn" id="mode-btn" aria-haspopup="menu" aria-expanded="false">
                                <i id="mode-icon"></i><span id="mode-label"></span><i class="fas fa-chevron-down"></i>
                            </button>
                            <ul class="mode__menu" id="mode-menu" role="menu" hidden>
                                @foreach ($modes as $key => $mode)
                                    <li role="none">
                                        <button type="button" class="mode__item" role="menuitemradio" data-mode="{{ $key }}" aria-checked="false">
                                            <i class="{{ $mode['icon'] }}"></i>{{ $mode['label'] }}<i class="fas fa-check tick"></i>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="pill-btn form-toggle" id="form-toggle" hidden aria-expanded="true"></button>
                        <span class="spacer"></span>
                        <button type="submit" class="send-btn" id="assistant-send" aria-label="Gửi">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
                            <span class="spin"></span>
                        </button>
                    </div>
                </form>
                <p class="hint">Enter để gửi · Shift+Enter xuống dòng · Trợ lý có thể nhầm, hãy kiểm tra lại số liệu quan trọng.</p>
            </div>
        </div>
        </div>
    </div>

    <datalist id="assistant-units">
        @foreach ($units as $unit)
            <option value="{{ $unit }}"></option>
        @endforeach
    </datalist>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
    <script src="{{ versionResource('assets/js/tool/assistant/assistant.js') }}" defer></script>
@endpush
