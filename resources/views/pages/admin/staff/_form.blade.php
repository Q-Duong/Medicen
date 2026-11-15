{{-- Form thêm / sửa nhân viên (dùng chung). Biến: $staff (null khi thêm), $hired (nhân sự thuê ngoài: khóa tên, vai trò) --}}
@php
    $staff = $staff ?? null;
    $hired = $hired ?? false;
    $val = fn ($k, $d = '') => old($k, $staff->{$k} ?? $d);
@endphp
<div class="sf-grid">
    <label class="sf-input @error('staff_name') has-error @enderror">
        <span>Họ tên nhân viên @if ($hired)<em class="sf-lock"><i class="fa fa-lock"></i> cố định</em>@endif</span>
        <input type="text" name="staff_name" value="{{ $val('staff_name') }}" placeholder="Vd: Nguyễn Văn A" autocomplete="off" @readonly($hired)>
        @error('staff_name')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
    </label>
    <label class="sf-input @error('staff_phone') has-error @enderror">
        <span>Số điện thoại</span>
        <input type="text" name="staff_phone" value="{{ $val('staff_phone') }}" placeholder="10 số, bắt đầu bằng 0" inputmode="numeric" autocomplete="off">
        @error('staff_phone')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
    </label>
    <div class="sf-input">
        <span>Giới tính</span>
        <div class="sf-seg">
            <label><input type="radio" name="staff_gender" value="0" @checked((string) $val('staff_gender', '0') === '0')><i>Nam</i></label>
            <label><input type="radio" name="staff_gender" value="1" @checked((string) $val('staff_gender', '0') === '1')><i>Nữ</i></label>
        </div>
    </div>
    <label class="sf-input @error('staff_birthday') has-error @enderror">
        <span>Ngày sinh</span>
        <input type="date" name="staff_birthday" value="{{ $val('staff_birthday') }}">
        @error('staff_birthday')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
    </label>
    <div class="sf-input sf-input--full">
        <span>Vai trò @if ($hired)<em class="sf-lock"><i class="fa fa-lock"></i> cố định</em>@endif</span>
        <div class="sf-seg">
            <label><input type="radio" name="staff_role" value="KTV" @checked($val('staff_role', 'KTV') === 'KTV') @disabled($hired && $val('staff_role') !== 'KTV')><i><i class="fas fa-user-md"></i> Kỹ thuật viên</i></label>
            <label><input type="radio" name="staff_role" value="TX" @checked($val('staff_role', 'KTV') === 'TX') @disabled($hired && $val('staff_role') !== 'TX')><i><i class="fas fa-car"></i> Tài xế</i></label>
        </div>
    </div>
    <div class="sf-input sf-input--full">
        <span>Chứng chỉ hành nghề</span>
        <label class="sf-check">
            <input type="hidden" name="staff_certificate" value="0">
            <input type="checkbox" name="staff_certificate" value="1" @checked(old('staff_certificate', $staff->staff_certificate ?? false))>
            <b>Có chứng chỉ hành nghề (CCHN)</b>
        </label>
        <input type="text" name="staff_certificate_note" maxlength="255" value="{{ $val('staff_certificate_note') }}"
            placeholder="Số CCHN, phạm vi: X-quang, siêu âm… (AI dùng khi gợi ý xếp lịch)" autocomplete="off">
    </div>
</div>
