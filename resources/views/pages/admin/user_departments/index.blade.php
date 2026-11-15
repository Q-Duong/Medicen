@extends('layouts.default_auth')
@section('title', 'Phân quyền phòng ban - Medicen')
@push('css')
    {{-- Giao diện ở assets/css/support/org-departments.css (bản nạp: departments.css) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/departments.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $byDept = $users->groupBy(fn ($u) => \App\Services\Claude\AssistantAccess::isAdmin($u) ? '__admin' : ($u->department ?: '__none'));
    @endphp
    <div class="ud">
        {{-- Đầu trang: cùng kiểu với Đơn hàng / Công nợ / Xếp lịch (thẻ trắng, vạch cam) --}}
        <div class="ud-head">
            <div class="ud-head__title">
                <h1>Phân quyền phòng ban</h1>
                <p>Phòng ban quyết định tài khoản được dùng trợ lý AI nào. Admin (role 0) dùng được tất cả trợ lý và xem được
                    lịch sử chat của mọi người; tài khoản khác chỉ xem lịch sử của chính mình.</p>
            </div>
        </div>

        {{-- Trợ lý nào dành cho phòng ban nào --}}
        <div class="ud-cards">
            @foreach ($assistants as $assistant)
                <section class="ud-card">
                    <div class="ud-card__icon"><i class="fas fa-robot"></i></div>
                    <div>
                        <h2>{{ $assistant['title'] }}</h2>
                        <p>{{ collect($assistant['user_departments'])->map(fn ($d) => $departments[$d] ?? $d)->join(', ') ?: 'Chỉ Admin' }}</p>
                    </div>
                </section>
            @endforeach
            <section class="ud-card ud-card--stat">
                <div>
                    <h2>{{ $users->count() }} tài khoản</h2>
                    <p>{{ ($byDept['__admin'] ?? collect())->count() }} Admin ·
                        {{ ($byDept['__none'] ?? collect())->count() }} chưa cấp phòng ban</p>
                </div>
            </section>
        </div>

        @if ($errors->any())
            <div class="ud-alert"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
        @endif

        <div class="ud-table">
            <table>
                <thead>
                    <tr>
                        <th>Tài khoản</th>
                        <th>Email</th>
                        <th>Loại</th>
                        <th>Phòng ban</th>
                        <th>Trợ lý được dùng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php($isAdmin = \App\Services\Claude\AssistantAccess::isAdmin($user))
                        @php($canUse = \App\Services\Claude\AssistantAccess::assistantsFor($user))
                        <tr>
                            <td data-label="Tài khoản"><b>{{ $user->name }}</b> <span class="ud-muted">#{{ $user->id }}</span></td>
                            <td data-label="Email" class="ud-email">{{ $user->email }}</td>
                            <td data-label="Loại">
                                @if ($isAdmin)
                                    <span class="ud-tag ud-tag--admin">Admin</span>
                                @else
                                    <span class="ud-tag">{{ [2 => 'Nhân viên', 3 => 'Kế toán'][$user->role] ?? 'Role ' . $user->role }}</span>
                                @endif
                            </td>
                            <td data-label="Phòng ban">
                                @if ($isAdmin)
                                    <span class="ud-muted">Admin dùng tất cả</span>
                                @else
                                    <form method="POST" action="{{ route('user-departments.update', $user) }}" class="ud-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="department" aria-label="Phòng ban của {{ $user->name }}"
                                            class="{{ $user->department ? '' : 'is-empty' }}">
                                            <option value="">Chưa cấp</option>
                                            @foreach ($departments as $key => $label)
                                                <option value="{{ $key }}" @selected($user->department === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit">Lưu</button>
                                    </form>
                                @endif
                            </td>
                            <td data-label="Trợ lý được dùng">
                                @forelse ($canUse as $name)
                                    <span class="ud-chip">{{ $name }}</span>
                                @empty
                                    <span class="ud-muted">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
