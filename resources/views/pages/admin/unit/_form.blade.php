{{-- Form thêm / sửa đơn vị. Biến: $unit (null khi thêm) --}}
@php
    $unit = $unit ?? null;
    $v = fn ($k) => old($k, $unit->{$k} ?? '');
@endphp
<div class="sf-grid">
    <label class="sf-input @error('unit_code') has-error @enderror">
        <span>Mã đơn vị</span>
        <input type="text" name="unit_code" value="{{ $v('unit_code') }}" maxlength="20" placeholder="Vd: PKQT" autocomplete="off">
        @error('unit_code')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
        <small class="sf-hint">Mã ngắn, dùng trong mã nhóm đi chung (vd PKQT-251126).</small>
    </label>
    <label class="sf-input @error('unit_abbreviation') has-error @enderror">
        <span>Tên viết tắt</span>
        <input type="text" name="unit_abbreviation" value="{{ $v('unit_abbreviation') }}" placeholder="Vd: PK QUỐC TẾ" autocomplete="off">
        @error('unit_abbreviation')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
        <small class="sf-hint">Tên hiện trên đơn hàng, lịch xe, công nợ.</small>
    </label>
    <label class="sf-input sf-input--full @error('unit_name') has-error @enderror">
        <span>Tên đơn vị (đầy đủ)</span>
        <textarea name="unit_name" rows="2" placeholder="Tên đầy đủ trên hợp đồng / hóa đơn">{{ $v('unit_name') }}</textarea>
        @error('unit_name')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
    </label>
    <label class="sf-input @error('unit_tax_code') has-error @enderror">
        <span>Mã số thuế <small class="sf-muted">(không bắt buộc)</small></span>
        <input type="text" name="unit_tax_code" value="{{ $v('unit_tax_code') }}" maxlength="30" placeholder="Vd: 0312345678" autocomplete="off">
        @error('unit_tax_code')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
    </label>
    <label class="sf-input sf-input--full @error('unit_address') has-error @enderror">
        <span>Địa chỉ <small class="sf-muted">(không bắt buộc)</small></span>
        <input type="text" name="unit_address" value="{{ $v('unit_address') }}" maxlength="255" placeholder="Số nhà, đường, phường / xã, quận / huyện, tỉnh / thành" autocomplete="off">
        @error('unit_address')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
    </label>
</div>
