@extends('layouts.default_auth')
@section('title', 'Tài khoản của tôi - Medicen')
@push('css')
    {{-- Dùng chung giao diện trang quản lý (assets/css/support/org-staff.css → staff.css) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $p = fn ($k, $d = '') => old($k, $profile->{$k} ?? $d);
        $roleLabel = [0 => 'Admin', 1 => 'Quản lý', 2 => 'Nhân viên', 3 => 'Kế toán'][(int) $user->role] ?? 'Tài khoản';
        $dept = $user->department ? (config('departments')[$user->department] ?? $user->department) : null;
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Tài khoản của tôi</h1>
                <p>{{ $user->display_name }} · {{ $user->email }} · {{ $roleLabel }}{{ $dept ? ' · ' . $dept : '' }}</p>
            </div>
        </div>

        <form action="{{ route('store-information') }}" method="post" class="sf-card" autocomplete="off">
            @csrf
            @if ($errors->any())
                <div class="sf-alert"><i class="fas fa-exclamation-circle"></i> Vui lòng kiểm tra lại các ô báo đỏ.</div>
            @endif

            <h2 class="sf-section">Thông tin cá nhân</h2>
            <div class="sf-grid">
                <label class="sf-input @error('profile_firstname') has-error @enderror">
                    <span>Họ và tên đệm</span>
                    <input type="text" name="profile_firstname" value="{{ $p('profile_firstname') }}" placeholder="Vd: Nguyễn Văn" autocomplete="off">
                </label>
                <label class="sf-input @error('profile_lastname') has-error @enderror">
                    <span>Tên</span>
                    <input type="text" name="profile_lastname" value="{{ $p('profile_lastname') }}" placeholder="Vd: An" autocomplete="off">
                </label>
                <label class="sf-input @error('profile_phone') has-error @enderror">
                    <span>Số điện thoại</span>
                    <input type="text" name="profile_phone" value="{{ $p('profile_phone') }}" placeholder="10 số, bắt đầu bằng 0" inputmode="numeric" autocomplete="off">
                    @error('profile_phone')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                </label>
                <label class="sf-input @error('day_of_birth') has-error @enderror">
                    <span>Ngày sinh</span>
                    <input type="date" name="day_of_birth" value="{{ $p('day_of_birth') }}">
                </label>
                <div class="sf-input">
                    <span>Giới tính</span>
                    <div class="sf-seg">
                        <label><input type="radio" name="profile_gender" value="0" @checked((string) $p('profile_gender', '0') === '0')><i>Nam</i></label>
                        <label><input type="radio" name="profile_gender" value="1" @checked((string) $p('profile_gender', '0') === '1')><i>Nữ</i></label>
                    </div>
                </div>
                <div class="sf-input">
                    <span>Email đăng nhập</span>
                    <input type="text" value="{{ $user->email }}" readonly>
                    <small class="sf-hint">Email, loại tài khoản, phòng ban do Admin đổi ở trang Quản lý tài khoản.</small>
                </div>
            </div>

            <h2 class="sf-section">Đổi mật khẩu <small>(để trống nếu không đổi)</small></h2>
            <div class="sf-grid">
                <label class="sf-input sf-input--full @error('current_password') has-error @enderror">
                    <span>Mật khẩu hiện tại</span>
                    <div class="sf-pass">
                        <input type="password" name="current_password" autocomplete="current-password" placeholder="Bắt buộc khi đổi mật khẩu">
                        <button type="button" class="sf-pass__toggle" tabindex="-1" title="Hiện / ẩn" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'"><i class="far fa-eye"></i></button>
                    </div>
                    @error('current_password')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                </label>
                <label class="sf-input @error('password') has-error @enderror">
                    <span>Mật khẩu mới</span>
                    <div class="sf-pass">
                        <input type="password" name="password" autocomplete="new-password" placeholder="Tối thiểu 6 ký tự">
                        <button type="button" class="sf-pass__toggle" tabindex="-1" title="Hiện / ẩn" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'"><i class="far fa-eye"></i></button>
                    </div>
                    @error('password')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                </label>
                <label class="sf-input">
                    <span>Nhập lại mật khẩu mới</span>
                    <div class="sf-pass">
                        <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới">
                        <button type="button" class="sf-pass__toggle" tabindex="-1" title="Hiện / ẩn" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'"><i class="far fa-eye"></i></button>
                    </div>
                </label>
            </div>

            <div class="sf-form-actions">
                <a href="{{ route('dashboard.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Lưu thay đổi</button>
            </div>
        </form>
    </div>
@endsection
