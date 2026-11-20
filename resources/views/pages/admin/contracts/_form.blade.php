@php
    /** @var array $v giá trị (từ AI hoặc hợp đồng đã lưu) */
    $val = fn ($k, $d = '') => $v[$k] ?? $d;
    $money = fn ($n) => $n !== null && $n !== '' ? number_format((int) $n, 0, ',', '.') : '';
    $type = $val('type', 'nguyen_tac');
    $mode = $val('price_mode', 'theo_ca');
    $tiers = $val('price_tiers') ?: [];
    $addons = $val('addons') ?: [];
    $surcharges = $val('surcharges') ?: [];
    $suggestIds = collect($suggest)->pluck('id')->all();
    $unitLabel = fn ($u) => trim(($u->unit_abbreviation ?: $u->unit_code) . ' — ' . $u->unit_name);
@endphp
<form class="hd-form" method="post" enctype="multipart/form-data" data-type="{{ $type }}" data-mode="{{ $mode }}"
    action="{{ $c ? route('contracts.update', $c->id) : route('contracts.store') }}" data-orders-url="{{ route('contracts.orders') }}" novalidate>
    @csrf
    @if ($c) @method('patch') @endif
    @if (!$c)
        <input type="hidden" name="tmp" value="{{ $val('tmp') }}">
        <input type="hidden" name="file_name" value="{{ $val('file_name') }}">
    @endif
    <div class="hd-form__errors" hidden></div>
    <fieldset @disabled($readonly)>
        <div class="hd-sec">
            <h3>Hợp đồng</h3>
            <div class="hd-grid">
                <label class="hd-f">
                    <span>Loại</span>
                    <select name="type" data-hd-type>
                        @foreach (\App\Models\CustomerContract::TYPES as $k => $label)<option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="hd-f hd-f--wide">
                    <span>Đơn vị trên web (Bên A) <em class="hd-req">*</em></span>
                    <select name="unit_id" data-hd-unit required>
                        <option value="">— Chọn đơn vị —</option>
                        @if ($suggest)
                            <optgroup label="AI gợi ý (khớp MST / tên)">
                                @foreach ($suggest as $s)<option value="{{ $s['id'] }}" @selected((int) $val('unit_id') === $s['id'])>{{ $s['label'] }}{{ $s['score'] >= 100 ? ' ✓ MST' : '' }}</option>@endforeach
                            </optgroup>
                        @endif
                        <optgroup label="Tất cả đơn vị">
                            @foreach ($units as $u)
                                @continue(in_array($u->id, $suggestIds, true))
                                <option value="{{ $u->id }}" @selected((int) $val('unit_id') === $u->id)>{{ $unitLabel($u) }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @if (!$c && !$val('unit_id') && $val('party_a'))
                        <small class="hd-warn"><i class="fas fa-exclamation-triangle"></i> Không tìm thấy đơn vị khớp "{{ $val('party_a') }}" — chọn tay hoặc thêm đơn vị mới ở trang Đơn vị.</small>
                    @endif
                </label>
                <label class="hd-f"><span>Số hợp đồng</span><input type="text" name="contract_no" value="{{ $val('contract_no') }}" maxlength="120" autocomplete="off"></label>
                <label class="hd-f hd-f--wide"><span>Tên Bên A (theo hợp đồng)</span><input type="text" name="party_a" value="{{ $val('party_a') }}" maxlength="255" autocomplete="off"></label>
                <label class="hd-f"><span>MST Bên A</span><input type="text" name="party_a_tax_code" value="{{ $val('party_a_tax_code') }}" maxlength="30" autocomplete="off"></label>
                <label class="hd-f"><span>Dịch vụ</span><input type="text" name="service" value="{{ $val('service') }}" maxlength="255" placeholder="X-quang phổi" autocomplete="off"></label>
                <label class="hd-f"><span>Ngày ký</span><input type="date" name="signed_on" value="{{ $val('signed_on') }}"></label>
                <label class="hd-f" data-hd-only="nguyen_tac"><span>Hiệu lực từ</span><input type="date" name="valid_from" value="{{ $val('valid_from') }}"></label>
                <label class="hd-f" data-hd-only="nguyen_tac"><span>Hiệu lực đến <small>(trống = đến khi chấm dứt)</small></span><input type="date" name="valid_to" value="{{ $val('valid_to') }}"></label>
                <label class="hd-check" data-hd-only="nguyen_tac"><input type="checkbox" name="auto_renew" value="1" @checked($val('auto_renew'))> Tự động gia hạn</label>
            </div>
        </div>

        <div class="hd-sec" data-hd-only="thoi_vu">
            <h3>Đợt chụp</h3>
            <div class="hd-grid">
                <label class="hd-f"><span>Ngày chụp <em class="hd-req">*</em></span><input type="date" name="service_date" value="{{ $val('service_date') }}" data-hd-date></label>
                <label class="hd-f hd-f--wide"><span>Công ty được chụp <small>(= Tên Cty trên đơn)</small></span><input type="text" name="end_company" value="{{ $val('end_company') }}" maxlength="255" autocomplete="off"></label>
                <label class="hd-f hd-f--wide"><span>Địa điểm chụp</span><input type="text" name="service_location" value="{{ $val('service_location') }}" maxlength="255" autocomplete="off"></label>
                <label class="hd-f hd-f--full">
                    <span>Gắn với đơn hàng</span>
                    <select name="order_id" data-hd-order>
                        <option value="">Tự khớp theo đơn vị + ngày chụp</option>
                        @foreach ($orders as $o)<option value="{{ $o['id'] }}" @selected((int) $val('order_id') === $o['id'])>{{ $o['label'] }}</option>@endforeach
                        @if ($val('order_id') && !collect($orders)->pluck('id')->contains((int) $val('order_id')))<option value="{{ $val('order_id') }}" selected>Đơn #{{ $val('order_id') }}</option>@endif
                    </select>
                    <small class="hd-hint" data-hd-order-hint>{{ $orders ? count($orders) . ' đơn của đơn vị trong ±7 ngày quanh ngày chụp.' : ($val('unit_id') && $val('service_date') ? 'Chưa có đơn nào của đơn vị trong ±7 ngày quanh ngày chụp — để "Tự khớp", khi Sales lên đơn sẽ tự nhận.' : 'Chọn đơn vị và ngày chụp để xem các đơn.') }}</small>
                </label>
            </div>
        </div>

        <div class="hd-sec">
            <h3>Giá</h3>
            <div class="hd-grid">
                <label class="hd-f hd-f--wide" data-hd-only="nguyen_tac">
                    <span>Cách tính</span>
                    <select name="price_mode" data-hd-mode>
                        @foreach (\App\Models\CustomerContract::PRICE_MODES as $k => $label)<option value="{{ $k }}" @selected($mode === $k)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="hd-f" data-hd-pkg><span>Giá trọn gói (đ)<span data-hd-only="thoi_vu"> / đợt</span></span><input type="text" name="package_price" value="{{ $money($val('package_price')) }}" inputmode="numeric" data-hd-money autocomplete="off"></label>
                <label class="hd-check"><input type="checkbox" name="vat_included" value="1" @checked($val('vat_included', true))> Giá đã gồm VAT</label>
            </div>
            <p class="hd-hint" data-hd-dot-note><i class="fas fa-info-circle"></i> Trọn gói theo đợt: hợp đồng không ghi số tiền, mỗi đợt báo giá riêng — hệ thống gợi ý từ giá các đợt trước của đơn vị, đơn vị mới thì theo giá sàn có lời.</p>

            <div class="hd-rows" data-hd-tiers data-hd-only="nguyen_tac">
                <div class="hd-rows__head"><b>Bậc giá theo số ca / ngày</b><button type="button" class="hd-add" data-hd-add="tier"><i class="fas fa-plus"></i> Thêm bậc</button></div>
                <div class="hd-rows__cols hd-cols--tier"><span>Từ ca</span><span>Đến ca <small>(0 = trở lên)</small></span><span>Tính theo</span><span>Giá (đ)</span><span></span></div>
                <div class="hd-rows__list">
                    @foreach ($tiers as $i => $t)
                        @include('pages.admin.contracts._row_tier', ['i' => $i, 't' => $t])
                    @endforeach
                </div>
                <label class="hd-f hd-f--full"><span>Chụp nhiều ngày tính thế nào</span><input type="text" name="averaging_rule" value="{{ $val('averaging_rule') }}" maxlength="500" placeholder="Vd: lấy số ca bình quân các ngày để tính đơn giá" autocomplete="off"></label>
            </div>

            <div class="hd-rows" data-hd-only="nguyen_tac">
                <div class="hd-rows__head"><b>Cộng thêm theo ca</b> <small>(in phim, giấy…)</small><button type="button" class="hd-add" data-hd-add="addon"><i class="fas fa-plus"></i> Thêm</button></div>
                <div class="hd-rows__cols hd-cols--addon"><span>Khoản</span><span>Đ / ca</span><span></span></div>
                <div class="hd-rows__list">
                    @foreach ($addons as $i => $a)
                        @include('pages.admin.contracts._row_addon', ['i' => $i, 'a' => $a])
                    @endforeach
                </div>
            </div>

            <div class="hd-rows" data-hd-only="nguyen_tac">
                <div class="hd-rows__head"><b>Phụ thu khoảng cách</b> <small>(chỉ lưu để tham khảo — chốt theo thực tế)</small><button type="button" class="hd-add" data-hd-add="surcharge"><i class="fas fa-plus"></i> Thêm</button></div>
                <div class="hd-rows__cols hd-cols--sur"><span>Từ km</span><span>Đến km</span><span>Từ (đ/ngày)</span><span>Đến (đ/ngày)</span><span>Gồm VAT</span><span></span></div>
                <div class="hd-rows__list">
                    @foreach ($surcharges as $i => $s)
                        @include('pages.admin.contracts._row_surcharge', ['i' => $i, 's' => $s])
                    @endforeach
                </div>
                <label class="hd-f"><span>Bán kính giá gốc (km)</span><input type="text" name="service_radius_km" value="{{ $val('service_radius_km') }}" inputmode="numeric" autocomplete="off"></label>
            </div>
        </div>

        <div class="hd-sec">
            <h3>Thanh toán và điều khoản</h3>
            <div class="hd-grid">
                <label class="hd-f"><span>Thanh toán trong (ngày)</span><input type="text" name="payment_days" value="{{ $val('payment_days') }}" inputmode="numeric" autocomplete="off"></label>
                <label class="hd-f hd-f--full"><span>Trả kết quả</span><textarea name="result_delivery" rows="3" maxlength="2000">{{ $val('result_delivery') }}</textarea></label>
                <label class="hd-f hd-f--full"><span>Điều khoản đáng chú ý</span><textarea name="notable_terms" rows="5" maxlength="3000">{{ $val('notable_terms') }}</textarea></label>
                <label class="hd-f hd-f--full"><span>Ghi chú nội bộ</span><textarea name="note" rows="2" maxlength="2000">{{ $val('note') }}</textarea></label>
                @if ($c && !$readonly)
                    <label class="hd-f hd-f--full"><span>Thay file gốc <small>(để trống = giữ file cũ)</small></span><input type="file" name="file" accept=".pdf,.docx,.doc,.jpg,.jpeg,.png"></label>
                @endif
            </div>
        </div>
    </fieldset>

    <template data-hd-tpl="tier">@include('pages.admin.contracts._row_tier', ['i' => '__i__', 't' => []])</template>
    <template data-hd-tpl="addon">@include('pages.admin.contracts._row_addon', ['i' => '__i__', 'a' => []])</template>
    <template data-hd-tpl="surcharge">@include('pages.admin.contracts._row_surcharge', ['i' => '__i__', 's' => []])</template>
</form>
