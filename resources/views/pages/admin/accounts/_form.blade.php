{{-- Form thêm / sửa tài khoản. Biến: $account (null khi thêm), $profile, $roles, $departments, $specialNames, $isMe --}}
@php
    $v = fn ($k, $d = '') => old($k, $account->{$k} ?? $d);
    $p = fn ($k, $d = '') => old($k, $profile->{$k} ?? $d);
@endphp
@if ($errors->any())
    <div class="sf-alert"><i class="fas fa-exclamation-circle"></i> Vui lòng kiểm tra lại các ô báo đỏ.</div>
@endif

<h2 class="sf-section">Đăng nhập</h2>
<div class="sf-grid">
    <label class="sf-input @error('name') has-error @enderror">
        <span>Tên tài khoản</span>
        <input type="text" name="name" value="{{ $v('name') }}" list="special-names" placeholder="Vd: Sale" autocomplete="off">
        <datalist id="special-names">
            @foreach ($specialNames as $n => $why)<option value="{{ $n }}">{{ $why }}</option>@endforeach
        </datalist>
        @error('name')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
        <small class="sf-hint">Một số trang phân quyền theo tên tài khoản:
            @foreach ($specialNames as $n => $why)<b>{{ $n }}</b> ({{ $why }}){{ $loop->last ? '.' : ', ' }}@endforeach
            Đổi tên các tài khoản này có thể mất quyền vào những trang đó.</small>
    </label>
    <label class="sf-input @error('email') has-error @enderror">
        <span>Email đăng nhập</span>
        <input type="email" name="email" value="{{ $v('email') }}" placeholder="ten@congty.com" autocomplete="off">
        @error('email')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
    </label>
    <label class="sf-input @error('password') has-error @enderror">
        <span>{{ $account ? 'Mật khẩu mới' : 'Mật khẩu' }}</span>
        <div class="sf-pass">
            <input type="password" name="password" autocomplete="new-password" placeholder="{{ $account ? 'Để trống nếu không đổi' : 'Tối thiểu 6 ký tự' }}">
            <button type="button" class="sf-pass__toggle" tabindex="-1" title="Hiện / ẩn mật khẩu" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'"><i class="far fa-eye"></i></button>
        </div>
        @error('password')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
        @if ($account)<small class="sf-hint">Đổi mật khẩu thì tài khoản này phải đăng nhập lại ở các máy đã "ghi nhớ đăng nhập".</small>@endif
    </label>
    <label class="sf-input">
        <span>Nhập lại mật khẩu</span>
        <div class="sf-pass">
            <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="{{ $account ? 'Để trống nếu không đổi' : 'Nhập lại mật khẩu' }}">
            <button type="button" class="sf-pass__toggle" tabindex="-1" title="Hiện / ẩn mật khẩu" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'"><i class="far fa-eye"></i></button>
        </div>
    </label>
</div>

<h2 class="sf-section">Quyền</h2>
<div class="sf-grid">
    <div class="sf-input @error('role') has-error @enderror">
        <span>Loại tài khoản @if ($isMe)<em class="sf-lock"><i class="fa fa-lock"></i> tài khoản của bạn</em>@endif</span>
        <div class="sf-seg">
            @foreach ($roles as $r => $label)
                <label><input type="radio" name="role" value="{{ $r }}" @checked((string) $v('role', 2) === (string) $r)
                    @disabled($isMe && (int) $r !== (int) $account->role)><i>{{ $label }}</i></label>
            @endforeach
        </div>
        @error('role')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
        <small class="sf-hint">Admin: toàn quyền. Kế toán: trang Công nợ. Nhân viên: theo phòng ban.</small>
    </div>
    <label class="sf-input @error('department') has-error @enderror">
        <span>Phòng ban</span>
        <select name="department">
            <option value="">Chưa cấp</option>
            @foreach ($departments as $key => $label)
                <option value="{{ $key }}" @selected($v('department') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <small class="sf-hint">Quyết định trợ lý AI, trang Xếp lịch được dùng (Admin dùng tất cả).</small>
    </label>
</div>

<h2 class="sf-section">Thông tin cá nhân <small>(không bắt buộc)</small></h2>
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
</div>
