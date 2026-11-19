{{-- Header nội bộ cho các trang lịch (schedules.*): logo, các trang lịch người này được vào, Trang quản trị, nút tài khoản.
     Thay menu website (Về Medicen, Dịch vụ…). Lịch Sales / Kết quả chỉ hiện với đúng tên tài khoản vì middleware sẽ đăng xuất người khác. --}}
@php
    $me = Auth::user();
    $nav = [
        ['schedules.technicians.index', 'Lịch KTV', 'fas fa-user-md', (bool) $me],
        ['schedules.sales.index', 'Lịch Sales', 'far fa-calendar-alt', $me && in_array($me->name, ['Admin', 'Sale'], true)],
        ['schedules.results.index', 'Lịch kết quả', 'fas fa-file-medical', $me && in_array($me->name, ['Admin', 'Office'], true)],
        ['schedules.drivers.index', 'Lịch tài xế', 'fas fa-shuttle-van', true],
    ];
@endphp
<nav class="sch-head">
    <div class="sch-head__inner">
        <a class="sch-head__brand" href="{{ $me ? route('admin.home') : route('home.index') }}" title="{{ $me ? 'Trang quản trị' : 'Trang chủ' }}">
            <img src="{{ asset('assets/images/medicen-logo.png') }}" alt="Medicen">
        </a>
        <button type="button" class="sch-head__toggle" id="sch-head-toggle" aria-expanded="false" aria-controls="sch-head-nav" aria-label="Mở menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sch-head__nav" id="sch-head-nav">
            <ul class="sch-head__links">
                @foreach ($nav as [$route, $label, $icon, $show])
                    @if ($show)
                        <li><a href="{{ route($route) }}" class="{{ request()->routeIs(str_replace('.index', '.*', $route)) ? 'is-active' : '' }}"><i class="{{ $icon }}"></i> {{ $label }}</a></li>
                    @endif
                @endforeach
                @if ($me)
                    <li><a href="{{ route('admin.home') }}"><i class="fas fa-th-large"></i> Trang quản trị</a></li>
                @endif
            </ul>
            <ul class="sch-head__account navbar-nav">
                @include('layouts.section.client.account')
            </ul>
        </div>
    </div>
</nav>
<script>
    (function () {
        const btn = document.getElementById('sch-head-toggle');
        const nav = document.getElementById('sch-head-nav');
        btn && btn.addEventListener('click', () => {
            const open = !nav.classList.contains('is-open');
            nav.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open);
        });
    })();
</script>
