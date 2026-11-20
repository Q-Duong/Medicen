@extends('layouts.default_auth')
@section('title', 'Hợp đồng ' . ($contract->contract_no ?: '#' . $contract->id) . ' - Medicen')
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
        $st = ['khop' => ['Khớp', 'hd-tag--ok'], 'thieu' => ['Thu thiếu', 'hd-tag--bad'], 'du' => ['Cao hơn HĐ', 'hd-tag--info'], 'khong_tinh' => ['Không có giá chuẩn', '']];
    @endphp
    <div class="sf hd">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>{{ \App\Models\CustomerContract::TYPES[$contract->type] }} {{ $contract->contract_no ?: '#' . $contract->id }}</h1>
                <p>{{ $contract->unit->unit_abbreviation ?? '' }} — {{ $contract->party_a }}{{ $contract->end_company ? ' · chụp cho ' . $contract->end_company : '' }}</p>
            </div>
            <div class="hd-head-actions">
                @if ($contract->file_path)
                    <a href="{{ route('contracts.download', $contract->id) }}" class="sf-btn sf-btn--ghost"><i class="fas fa-download"></i> File gốc</a>
                @endif
                <a href="{{ route('contracts.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>
            </div>
        </div>

        <section class="sf-card hd-card" data-hd-card>
            {!! $form !!}
            @if ($canEdit)
                <div class="sf-form-actions sf-form-actions--split hd-card__foot">
                    <form method="post" action="{{ route('contracts.destroy', $contract->id) }}" onsubmit="return confirm('Xóa hợp đồng này và file gốc? Không khôi phục được.')">
                        @csrf @method('delete')
                        <button type="submit" class="sf-btn sf-btn--danger"><i class="far fa-trash-alt"></i> Xóa</button>
                    </form>
                    <div class="sf-form-actions__right">
                        <a href="{{ route('contracts.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                        <button type="button" class="sf-btn sf-btn--primary" data-hd-save><i class="fas fa-check"></i> Lưu thay đổi</button>
                    </div>
                </div>
            @endif
        </section>

        <h2 class="sf-section">Đối chiếu đơn theo hợp đồng này <small>(Thành tiền trên đơn so với giá hợp đồng, đã gồm VAT; không tính in phim / phụ thu)</small></h2>
        <div class="sf-table">
            <table>
                <thead><tr><th>Đơn</th><th>Ngày</th><th>Công ty</th><th>Số ca</th><th>Thành tiền</th><th>Theo hợp đồng</th><th>Kết quả</th></tr></thead>
                <tbody>
                    @forelse ($checks as $r)
                        <tr>
                            <td data-label="Đơn"><a href="{{ route('order.edit', $r['order_id']) }}">#{{ $r['order_id'] }}</a></td>
                            <td data-label="Ngày" class="sf-nowrap">{{ $d($r['date']) }}</td>
                            <td data-label="Công ty">{{ $r['company'] ?: '—' }}</td>
                            <td data-label="Số ca">{{ $r['qty'] }}</td>
                            <td data-label="Thành tiền" class="sf-nowrap">{{ $m($r['actual']) }}</td>
                            <td data-label="Theo hợp đồng">@if ($r['expected'] !== null)<span class="sf-nowrap">{{ $m($r['expected']) }}</span>@endif<div class="hd-sub">{{ $r['basis'] }}</div></td>
                            <td data-label="Kết quả" class="sf-nowrap"><span class="hd-tag {{ $st[$r['status']][1] }}">{{ $st[$r['status']][0] }}</span>
                                @if ($r['diff'] && $r['status'] !== 'khop')<div class="hd-sub {{ $r['diff'] < 0 ? 'hd-neg' : '' }}">{{ $r['diff'] > 0 ? '+' : '' }}{{ $m($r['diff']) }}</div>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="sf-empty">Chưa có đơn nào áp dụng hợp đồng này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
