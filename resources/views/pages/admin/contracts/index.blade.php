@extends('layouts.default_auth')
@section('title', 'Hợp đồng khách hàng - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/contracts.css') }}" type="text/css" as="style" />
@endpush
@push('js')
    <script src="{{ versionResource('assets/js/tool/contracts.js') }}" defer></script>
@endpush
@section('admin_content')
    @php
        $d = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';
        $m = fn ($n) => number_format((int) $n, 0, ',', '.');
        $statusLabels = ['hieu_luc' => 'Còn hiệu lực', 'sap_het_han' => 'Sắp hết hạn', 'het_han' => 'Hết hạn', 'sap_chup' => 'Thời vụ sắp chụp', 'da_chup' => 'Thời vụ đã chụp'];
        $statusTag = ['hieu_luc' => 'hd-tag--ok', 'sap_het_han' => 'hd-tag--warn', 'het_han' => 'hd-tag--bad', 'sap_chup' => 'hd-tag--info', 'da_chup' => ''];
        $qs = fn ($extra) => array_filter(array_merge(request()->only(['q', 'loai', 'don_vi', 'tt']), $extra), fn ($x) => $x !== null && $x !== '');
    @endphp
    <div class="sf hd">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Hợp đồng khách hàng</h1>
                <p>Giá theo hợp đồng của từng đơn vị: đối chiếu với Thành tiền trên đơn, hạn thanh toán, gợi ý báo giá cho Sales và CFO AI. Chỉ cảnh báo, không chặn lên đơn.</p>
            </div>
            @if ($canEdit)
                <a href="{{ route('contracts.create') }}" class="sf-btn sf-btn--primary"><i class="fas fa-file-upload"></i> Tải hợp đồng lên</a>
            @endif
        </div>

        <nav class="hd-chips" aria-label="Lọc theo tình trạng">
            <a href="{{ route('contracts.index', $qs(['tt' => null])) }}" class="{{ !request('tt') ? 'is-active' : '' }}">Tất cả <b>{{ $total }}</b></a>
            @foreach ($statusLabels as $k => $label)
                @if (($counts[$k] ?? 0) > 0 || request('tt') === $k)
                    <a href="{{ route('contracts.index', $qs(['tt' => $k])) }}" class="{{ request('tt') === $k ? 'is-active' : '' }} {{ $k === 'sap_het_han' || $k === 'het_han' ? 'is-alert' : '' }}">{{ $label }} <b>{{ $counts[$k] ?? 0 }}</b></a>
                @endif
            @endforeach
        </nav>

        <form method="GET" action="{{ route('contracts.index') }}" class="sf-filters">
            @if (request('tt'))<input type="hidden" name="tt" value="{{ request('tt') }}">@endif
            <label class="sf-field sf-field--grow"><span>Tìm kiếm</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Số HĐ, Bên A, công ty được chụp…" autocomplete="off"></label>
            <label class="sf-field"><span>Loại</span>
                <select name="loai" onchange="this.form.submit()">
                    <option value="">Tất cả</option>
                    @foreach (\App\Models\CustomerContract::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('loai') === $k)>{{ $v }}</option>@endforeach
                </select></label>
            <label class="sf-field"><span>Đơn vị</span>
                <select name="don_vi" onchange="this.form.submit()">
                    <option value="">Tất cả</option>
                    @foreach ($units as $u)<option value="{{ $u->id }}" @selected((int) request('don_vi') === $u->id)>{{ $u->unit_abbreviation ?: $u->unit_code }}</option>@endforeach
                </select></label>
            <div class="sf-filters__actions">
                <button type="submit" class="sf-btn sf-btn--dark">Lọc</button>
                @if (request()->hasAny(['q', 'loai', 'don_vi', 'tt']) && array_filter(request()->only(['q', 'loai', 'don_vi', 'tt'])))
                    <a href="{{ route('contracts.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-times"></i> Bỏ lọc</a>
                @endif
            </div>
        </form>

        <div class="sf-table" data-hd-collapse="10">
            <table>
                <thead><tr><th>Đơn vị</th><th>Loại / số HĐ</th><th>Hiệu lực / ngày chụp</th><th>Giá</th><th>TT</th><th>Tình trạng</th><th class="sf-actions-h">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($list as $c)
                        <tr>
                            <td data-label="Đơn vị" class="hd-unit">
                                <b>{{ $c->unit->unit_abbreviation ?? '—' }}</b>
                                <div class="hd-sub">{{ $c->party_a }}</div>
                                @if ($c->end_company)<div class="hd-sub">Chụp cho: {{ $c->end_company }}</div>@endif
                            </td>
                            <td data-label="Loại / số HĐ"><span class="hd-type hd-type--{{ $c->type }}">{{ \App\Models\CustomerContract::TYPES[$c->type] }}</span>
                                <div class="hd-sub">{{ $c->contract_no ?: 'Không số' }}</div></td>
                            <td data-label="Hiệu lực" class="sf-nowrap">
                                @if ($c->type === 'thoi_vu')
                                    Chụp {{ $d($c->service_date) }}
                                    <div class="hd-sub">{{ $c->order_id ? 'Đơn #' . $c->order_id : 'tự khớp đơn' }}</div>
                                @else
                                    {{ $d($c->valid_from ?? $c->signed_on) }} → {{ $c->valid_to ? $d($c->valid_to) : 'khi chấm dứt' }}
                                    @if ($c->auto_renew)<div class="hd-sub">tự gia hạn</div>@endif
                                @endif
                            </td>
                            <td data-label="Giá" class="hd-price">{!! nl2br(e($c->priceSummary("\n"))) !!}</td>
                            <td data-label="Thanh toán" class="sf-nowrap">{{ $c->payment_days ? $c->payment_days . ' ngày' : '—' }}</td>
                            <td data-label="Tình trạng"><span class="hd-tag {{ $statusTag[$c->status] ?? '' }}">{{ $statusLabels[$c->status] ?? '' }}</span></td>
                            <td class="sf-actions sf-nowrap">
                                <a href="{{ route('contracts.edit', $c->id) }}" class="sf-icon-btn" title="{{ $canEdit ? 'Xem / sửa' : 'Xem' }}"><i class="fas fa-{{ $canEdit ? 'pen' : 'eye' }}"></i></a>
                                @if ($c->file_path)<a href="{{ route('contracts.download', $c->id) }}" class="sf-icon-btn" title="Tải file gốc"><i class="fas fa-download"></i></a>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="sf-empty">
                            {{ $total ? 'Không có hợp đồng khớp bộ lọc.' : 'Chưa có hợp đồng nào. Bấm "Tải hợp đồng lên" để AI đọc file và điền sẵn.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="sf-section" id="thu-thieu"><i class="fas fa-exclamation-triangle hd-ico-warn"></i> Đơn thu thấp hơn giá hợp đồng <small>(90 ngày qua → 60 ngày tới, lệch quá {{ \App\Services\Accounting\ContractPricing::TOLERANCE * 100 }}%)</small></h2>
        <div class="sf-table" data-hd-collapse="10">
            <table>
                <thead><tr><th>Đơn</th><th>Ngày</th><th>Đơn vị / công ty</th><th>Số ca</th><th>Thành tiền</th><th>Theo hợp đồng</th><th>Thiếu</th></tr></thead>
                <tbody>
                    @forelse ($underBilled as $r)
                        <tr>
                            <td data-label="Đơn"><a href="{{ route('order.edit', $r['order_id']) }}">#{{ $r['order_id'] }}</a></td>
                            <td data-label="Ngày" class="sf-nowrap">{{ $d($r['date']) }}</td>
                            <td data-label="Đơn vị"><b>{{ $r['unit'] }}</b><div class="hd-sub">{{ $r['company'] }}</div></td>
                            <td data-label="Số ca">{{ $r['qty'] }}</td>
                            <td data-label="Thành tiền" class="sf-nowrap">{{ $m($r['actual']) }}</td>
                            <td data-label="Theo hợp đồng"><span class="sf-nowrap">{{ $m($r['expected']) }}</span><div class="hd-sub">{{ $r['basis'] }} · <a href="{{ route('contracts.edit', $r['contract_id']) }}">HĐ {{ $r['contract_no'] ?: '#' . $r['contract_id'] }}</a></div></td>
                            <td data-label="Thiếu" class="sf-nowrap hd-neg">{{ $m(-$r['diff']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="sf-empty">Không có đơn nào thu thấp hơn giá hợp đồng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($missing->isNotEmpty())
            <h2 class="sf-section">Đơn vị có đơn 6 tháng gần đây nhưng chưa có hợp đồng nguyên tắc còn hiệu lực</h2>
            <div class="hd-missing">
                @foreach ($missing as $u)
                    <a href="{{ route('contracts.index', ['don_vi' => $u->id]) }}" title="{{ $u->unit_name }}">
                        <b>{{ $u->unit_abbreviation ?: $u->unit_name }}</b>
                        <span>{{ $u->n }} đơn · {{ $m($u->revenue) }} đ · gần nhất {{ $d($u->last_day) }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
