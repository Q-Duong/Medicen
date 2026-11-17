@extends('layouts.default_auth')
@section('title', 'Trang chủ - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/home.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $now = now();
        $hour = (int) $now->format('G');
        $greet = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
        $weekdays = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
        $isAdmin = \App\Services\Claude\AssistantAccess::isAdmin($user);
        $who = $user->display_name ?: $user->name;
        $role = $isAdmin ? 'Quản trị' : ($departmentLabel ?: null);
        // Lối tắt: mọi mục trong menu trừ "Trang chủ"
        $shortcuts = collect($sections)->map(fn ($s) => array_merge($s, ['items' => array_values(array_filter($s['items'], fn ($i) => $i['label'] !== 'Trang chủ'))]))
            ->filter(fn ($s) => $s['items'])->values();
    @endphp
    <div class="hm">
        <section class="hm-hero">
            <div class="hm-hero__text">
                <p class="hm-date">{{ $weekdays[$now->dayOfWeek] }}, {{ $now->format('d/m/Y') }}</p>
                <h1>{{ $greet }}, {{ $who }}!</h1>
                <p class="hm-sub">
                    @if ($role)<span class="hm-badge">{{ $role }}</span>@endif
                    Chúc bạn một ngày làm việc hiệu quả cùng Medicen.
                </p>
            </div>
            <img class="hm-hero__logo" src="{{ asset('frontend/img/new-logo.jpg') }}" alt="" aria-hidden="true">
        </section>

        <div class="hm-grid">
            <section class="hm-card hm-todo">
                <header class="hm-card__head">
                    <h2><i class="fas fa-bell"></i> Việc cần làm</h2>
                    @if ($todo)<span class="hm-muted">Cập nhật {{ $todo['checked_at'] }}</span>@endif
                </header>
                @if ($todo && $todo['groups'])
                    @foreach ($todo['groups'] as $group)
                        <div class="hm-todo__group">
                            <h3><i class="{{ $group['icon'] }}"></i> {{ $group['label'] }}</h3>
                            <ul>
                                @foreach ($group['items'] as $t)
                                    <li>
                                        <a href="{{ $t['url'] }}" class="hm-todo__item {{ $t['level'] === 'urgent' ? 'is-urgent' : '' }}">
                                            <span class="hm-todo__count">{{ $t['count'] }}</span>
                                            <span class="hm-todo__title">{{ $t['title'] }}
                                                @if ($t['hint'])<small>{{ $t['hint'] }}</small>@endif
                                            </span>
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @else
                    <div class="hm-empty">
                        <i class="fas fa-check-circle"></i>
                        <p>Không có việc nào đang chờ. Mọi thứ đã gọn gàng!</p>
                    </div>
                @endif
            </section>

            <section class="hm-card hm-links">
                <header class="hm-card__head">
                    <h2><i class="fas fa-compass"></i> Truy cập nhanh</h2>
                </header>
                @forelse ($shortcuts as $section)
                    <div class="hm-links__group">
                        <h3>{{ $section['title'] }}</h3>
                        <div class="hm-tiles">
                            @foreach ($section['items'] as $i)
                                <a href="{{ $i['url'] }}" class="hm-tile">
                                    <span class="hm-tile__icon"><i class="{{ $i['icon'] }}"></i></span>
                                    <span class="hm-tile__text">
                                        <b>{{ $i['label'] }}</b>
                                        @if ($i['hint'])<small>{{ $i['hint'] }}</small>@endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="hm-muted">Tài khoản chưa được cấp quyền trang nào. Liên hệ Admin để được phân quyền phòng ban.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
