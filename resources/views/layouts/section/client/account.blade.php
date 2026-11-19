{{-- Nút tài khoản (tên + phòng ban, menu Đăng xuất) cho header website và header nội bộ trang lịch --}}
@auth
    @php
        $me = Auth::user();
        $meName = $me->display_name ?: $me->name;
        $meDept = $me->department ? config('departments.' . $me->department) : null;
    @endphp
    {{-- Tài khoản đang đăng nhập (trang lịch): tên + phòng ban, nút đăng xuất --}}
    <li class="nav-item dropdown ms-lg-3 nav-account">
        <button type="button" class="nav-account__btn" id="nav-account-btn" aria-haspopup="true" aria-expanded="false" aria-label="Tài khoản">
            <span class="nav-account__avatar">{{ mb_strtoupper(mb_substr($meName, 0, 1)) }}</span>
            <span class="nav-account__text">
                <b>{{ $meName }}</b>
                @if ($meDept)<small>{{ $meDept }}</small>@endif
            </span>
            <i class="fas fa-chevron-down nav-account__caret" aria-hidden="true"></i>
        </button>
        <div class="dropdown-menu nav-account__menu" id="nav-account-menu">
            <div class="nav-account__who">
                <span class="nav-account__avatar">{{ mb_strtoupper(mb_substr($meName, 0, 1)) }}</span>
                <span class="nav-account__text"><b>{{ $meName }}</b><small>{{ $me->email }}</small></span>
            </div>
            <form action="{{ route('schedules.logout') }}" method="POST">
                @csrf
                <button type="submit" class="nav-account__logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button>
            </form>
        </div>
    </li>
    <script>
        // Mở / đóng menu tài khoản (Bootstrap ở trang này không kèm Popper nên không dùng data-bs-toggle)
        (function () {
            const btn = document.getElementById('nav-account-btn');
            const menu = document.getElementById('nav-account-menu');
            if (!btn || !menu) return;
            const set = (open) => { menu.classList.toggle('show', open); btn.setAttribute('aria-expanded', open); };
            btn.addEventListener('click', (e) => { e.stopPropagation(); set(!menu.classList.contains('show')); });
            document.addEventListener('click', (e) => { if (!menu.contains(e.target)) set(false); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') set(false); });
        })();
    </script>
@endauth
