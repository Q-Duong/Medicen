{{--
    Khung "Đi chung với đơn khác" trên form tạo / sửa / copy đơn.
    Khi chọn đơn vị + ngày chụp, tải các đơn khác cùng đơn vị, cùng ngày; Sale tích đơn nào đi chung (cùng xe, cùng đội)
    thì các đơn đó vào chung một nhóm, có mã nhóm. Không tự tích: chỉ gợi ý.
    Giao diện ở assets/css/support/org-order.css, xử lý ở assets/js/tool/order/org-order.js.
    Biến: $groupExclude (id đơn đang sửa), $groupPreset (id các đơn đang chung nhóm), $groupCode (mã nhóm hiện tại)
--}}
@php
    $groupPreset = collect(old('group_with', $groupPreset ?? []))->map(fn ($v) => (int) $v)->values()->all();
@endphp
<div class="og" id="order-group" data-url="{{ route('order.group_candidates') }}" data-exclude="{{ $groupExclude ?? '' }}" data-preset='@json($groupPreset)' hidden>
    <input type="hidden" name="group_touched" value="1">
    <div class="og__head">
        <i class="fas fa-link"></i> <b>Đi chung với đơn khác</b>
        @if (!empty($groupCode))<span class="og__code" id="og-code">Nhóm {{ $groupCode }}</span>@endif
        <small id="og-sub"></small>
    </div>
    <div class="og__list" id="og-list"></div>
    <p class="og__hint">Tích các đơn sẽ <b>đi chung một chuyến</b> (cùng xe, cùng đội) để tạo mã nhóm. Đơn cùng đơn vị nhưng đi xe khác thì để trống.</p>
</div>
