@extends('layouts.default_auth')
@section('title', 'Quản lý tài khoản - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    {{-- Dùng chung giao diện trang quản lý (assets/css/support/org-staff.css → staff.css) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $role = request('role');
        $tab = fn ($r) => route('accounts.index', array_filter(['role' => $r, 'q' => request('q')], fn ($v) => $v !== null && $v !== ''));
        $roleClass = [0 => 'admin', 2 => 'staff', 3 => 'acc'];
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Quản lý tài khoản</h1>
                <p>Tài khoản đăng nhập trang quản trị: tên, email, mật khẩu, loại tài khoản, phòng ban, thông tin cá nhân.</p>
            </div>
            <a href="{{ route('accounts.create') }}" class="sf-btn sf-btn--primary"><i class="fas fa-plus"></i> Thêm tài khoản</a>
        </div>

        <nav class="sf-strip sf-strip--4" aria-label="Lọc theo loại tài khoản">
            <a href="{{ $tab(null) }}" class="sf-strip__seg {{ $role === null || $role === '' ? 'is-active' : '' }}"><b>{{ $counts['all'] }}</b><span>Tất cả</span></a>
            @foreach ($roles as $r => $label)
                <a href="{{ $tab((string) $r) }}" class="sf-strip__seg sf-strip__seg--{{ $roleClass[$r] ?? 'staff' }} {{ $role === (string) $r ? 'is-active' : '' }}"><b>{{ $counts[$r] ?? 0 }}</b><span>{{ $label }}</span></a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('accounts.index') }}" class="sf-filters">
            @if ($role !== null && $role !== '')<input type="hidden" name="role" value="{{ $role }}">@endif
            <label class="sf-field sf-field--grow">
                <span>Tìm kiếm</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Tên tài khoản, email, họ tên hoặc SĐT..." autocomplete="off">
            </label>
            <div class="sf-filters__actions">
                <button type="submit" class="sf-btn sf-btn--dark">Lọc</button>
                @if (request()->anyFilled(['q', 'role']))
                    <a href="{{ route('accounts.index') }}" class="sf-btn sf-btn--ghost">Bỏ lọc</a>
                @endif
            </div>
        </form>

        <div class="sf-table">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tài khoản</th>
                        <th>Email đăng nhập</th>
                        <th>Họ tên</th>
                        <th>Loại</th>
                        <th>Phòng ban</th>
                        <th class="sf-actions-h">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $a)
                        @php($isMe = auth()->id() === $a->id)
                        @php($fullName = trim(($a->profile_firstname ?? '') . ' ' . ($a->profile_lastname ?? '')))
                        <tr>
                            <td data-label="Mã" class="sf-muted">#{{ $a->id }}</td>
                            <td data-label="Tài khoản">
                                <div class="sf-person">
                                    <span class="sf-avatar sf-avatar--{{ $roleClass[$a->role] ?? 'staff' }}">{{ mb_strtoupper(mb_substr($a->name, 0, 1)) }}</span>
                                    <div><b>{{ $a->name }}</b>@if ($isMe)<span class="sf-tag sf-tag--hired">Bạn</span>@endif</div>
                                </div>
                            </td>
                            <td data-label="Email đăng nhập">{{ $a->email }}</td>
                            <td data-label="Họ tên">
                                @if ($fullName !== '')
                                    {{ $fullName }}@if ($a->profile_phone)<div class="sf-note">{{ $a->profile_phone }}</div>@endif
                                @else
                                    <span class="sf-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Loại"><span class="sf-tag sf-tag--{{ $roleClass[$a->role] ?? 'staff' }}">{{ $roles[$a->role] ?? 'Role ' . $a->role }}</span></td>
                            <td data-label="Phòng ban">
                                @if ((int) $a->role === 0)
                                    <span class="sf-muted">Tất cả</span>
                                @else
                                    {{ $departments[$a->department] ?? '' }}@if (!$a->department)<span class="sf-muted">Chưa cấp</span>@endif
                                @endif
                            </td>
                            <td data-label="Thao tác" class="sf-actions">
                                <a href="{{ route('accounts.edit', $a->id) }}" class="sf-icon-btn" title="Sửa tài khoản, đổi mật khẩu"><i class="fas fa-pen"></i></a>
                                @if ($isMe)
                                    <span class="sf-icon-btn is-disabled" title="Không xóa được chính tài khoản đang đăng nhập"><i class="fas fa-lock"></i></span>
                                @else
                                    <form action="{{ route('accounts.destroy', $a->id) }}" method="POST"
                                        data-confirm="Xóa tài khoản {{ $a->name }} ({{ $a->email }})? Người dùng này sẽ không đăng nhập được nữa. Không hoàn tác được."
                                        data-confirm-title="Xóa tài khoản" data-confirm-ok="Xóa" data-confirm-danger>
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="sf-icon-btn sf-icon-btn--danger" title="Xóa tài khoản"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="sf-empty">Không có tài khoản nào khớp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sf-pager">
            @include('layouts.section.admin.pagination_showing_current', ['items' => $accounts])
            {{ $accounts->links('pagination::custom') }}
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
@endpush
