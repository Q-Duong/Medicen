{{-- Thanh trên cùng: logo + nút thu gọn menu + tài khoản. Giao diện ở assets/css/support/org-shell.css (bản nạp: shell.css).
     Đã bỏ các ô thông báo / thư / công việc mẫu (dữ liệu giả của giao diện gốc) và ô tìm kiếm không dùng. --}}
@php
    $me = Auth::user();
    $meName = $me ? $me->display_name : '';
    $meInitial = $me ? mb_strtoupper(mb_substr(trim(\Illuminate\Support\Str::afterLast(trim($meName), ' ')) ?: $meName, 0, 1)) : '';
    $meRole = $me ? ([0 => 'Admin', 1 => 'Quản lý', 2 => 'Nhân viên', 3 => 'Kế toán'][(int) $me->role] ?? 'Tài khoản') : '';
    $meDept = $me && $me->department ? (config('departments')[$me->department] ?? null) : null;
@endphp
<header class="header fixed-top clearfix app-header">
    <div class="brand">
        <a href="{{ route('dashboard.index') }}" class="logo">
            <span class="logo-mark">M</span><span class="logo-text">Medicen</span>
        </a>
        <div class="sidebar-toggle-box" title="Thu gọn / mở menu">
            <div class="fa fa-bars"></div>
        </div>
    </div>
    <div class="top-nav clearfix">
        <ul class="nav pull-right top-menu">
            <li>
                <div class="bmucDg"></div>
            </li>
            @if ($me)
                {{-- Chuông: việc cần làm theo bộ phận (assets/js/tool/org-shell.js gọi notifications.todo) --}}
                <li class="dropdown app-bell" id="app-bell" data-url="{{ route('notifications.todo') }}">
                    <a data-toggle="dropdown" class="dropdown-toggle app-bell__btn" href="javascript:;" title="Việc cần làm" aria-label="Việc cần làm">
                        <i class="far fa-bell"></i>
                        <span class="app-bell__badge" hidden></span>
                    </a>
                    <div class="dropdown-menu app-bell__menu">
                        <div class="app-bell__head">
                            <b>Việc cần làm</b>
                            <button type="button" class="app-bell__refresh" title="Cập nhật ngay"><i class="fas fa-sync-alt"></i></button>
                        </div>
                        <div class="app-bell__body"><div class="app-bell__empty">Đang tải…</div></div>
                        <div class="app-bell__foot"></div>
                    </div>
                </li>
                <li class="dropdown app-user">
                    <a data-toggle="dropdown" class="dropdown-toggle app-user__btn" href="javascript:;">
                        <span class="app-avatar">{{ $meInitial }}</span>
                        <span class="app-user__text">
                            <b class="username">{{ $meName }}</b>
                            <small>{{ $meRole }}{{ $meDept ? ' · ' . $meDept : '' }}</small>
                        </span>
                        <i class="fas fa-chevron-down app-user__caret"></i>
                    </a>
                    <ul class="dropdown-menu extended logout app-user__menu">
                        <li class="app-user__head">
                            <span class="app-avatar app-avatar--lg">{{ $meInitial }}</span>
                            <div>
                                <b>{{ $meName }}</b>
                                <small>{{ $me->email }}</small>
                            </div>
                        </li>
                        <li><a href="{{ route('information') }}"><i class="far fa-id-card"></i> Tài khoản của tôi</a></li>
                        <li><a href="#" class="app-user__logout"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </li>
            @endif
        </ul>
    </div>
</header>
