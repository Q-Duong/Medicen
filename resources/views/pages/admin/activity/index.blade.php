@extends('layouts.default_auth')
@section('title', 'Nhật ký hoạt động - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <style>
        .ak-people { display: flex; flex-direction: column; gap: 16px; }
        .ak-card { padding: 0; overflow: hidden; }
        .ak-card__head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 14px; padding: 14px 18px; border-bottom: 1px solid #efece5; background: #fcfbf8; }
        .ak-card__head h2 { margin: 0; font-size: 16px; font-weight: 700; }
        .ak-card__head .ak-sub { color: #73716c; font-size: 13px; }
        .ak-days { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 18px 0; }
        .ak-day { padding: 6px 11px; border: 1px solid #e7e3da; border-radius: 10px; background: #fff; font-size: 13px; }
        .ak-day b { margin-right: 6px; }
        .ak-chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 18px 0; }
        .ak-chip { padding: 3px 9px; border-radius: 999px; background: #f1efe9; color: #4a4945; font-size: 12px; }
        .ak-chip--page { background: #ebf3fd; color: #0b5cad; }
        .ak-flags { margin: 12px 18px 0; padding: 10px 13px; border: 1px solid #f3c6bf; border-radius: 11px; background: #fdf1ef; color: #7a1d14; font-size: 13px; }
        .ak-flags b { display: block; margin-bottom: 3px; }
        .ak-flags ul { margin: 0; padding-left: 18px; }
        .ak-list { margin: 12px 0 0; }
        .ak-list table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ak-list th { padding: 7px 12px; background: #faf9f5; color: #73716c; font-size: 11.5px; font-weight: 700; text-align: left; text-transform: uppercase; }
        .ak-list td { padding: 8px 12px; border-top: 1px solid #f1eee7; vertical-align: top; }
        .ak-time { white-space: nowrap; color: #4a4945; font-variant-numeric: tabular-nums; }
        .ak-area { white-space: nowrap; }
        .ak-changes { margin: 4px 0 0; padding: 0; list-style: none; color: #73716c; font-size: 12.5px; }
        .ak-changes li::before { content: '↳ '; color: #c96442; }
        .ak-flag { display: inline-block; margin: 3px 4px 0 0; padding: 1px 7px; border-radius: 999px; background: #fdecea; color: #b42318; font-size: 11.5px; font-weight: 600; }
        .ak-meta { color: #a3a19b; font-size: 12px; }
        .ak-note { margin: 0 0 14px; color: #73716c; font-size: 13px; }
        .ak-trunc { padding: 8px 18px 12px; color: #9a5b00; font-size: 12.5px; }
        @media (max-width: 768px) {
            .ak-list thead { display: none; }
            .ak-list tr { display: block; padding: 8px 12px; border-top: 1px solid #f1eee7; }
            .ak-list td { display: block; padding: 2px 0; border: 0; }
        }
    </style>
@endpush
@section('admin_content')
    @php $q = request()->only(['tu', 'den', 'nguoi', 'q', 'trang', 'loai', 'don']); @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Nhật ký hoạt động</h1>
                <p>Ai đã làm gì trên web, lúc nào: đăng nhập, trang đã mở, từng thao tác thêm / sửa / xóa kèm số cũ → số mới, tải / xuất file.
                    Gõ <b>mã đơn</b> để xem toàn bộ lịch sử một đơn.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('activity.index') }}" class="sf-filters">
            <label class="sf-field"><span>Mã đơn</span><input type="text" name="don" value="{{ $order ? '#' . $order : '' }}" placeholder="#2350" inputmode="numeric" style="width:110px"></label>
            <label class="sf-field"><span>Từ ngày</span><input type="date" name="tu" value="{{ $from->toDateString() }}"></label>
            <label class="sf-field"><span>Đến ngày</span><input type="date" name="den" value="{{ $to->toDateString() }}"></label>
            <label class="sf-field"><span>Người</span>
                <select name="nguoi">
                    <option value="">Tất cả</option>
                    @foreach ($people as $p)<option value="{{ $p->id }}" @selected($userId === $p->id)>{{ $p->name }}</option>@endforeach
                </select></label>
            <label class="sf-field"><span>Trang</span>
                <select name="trang">
                    <option value="">Tất cả</option>
                    @foreach ($areas as $k => $label)<option value="{{ $k }}" @selected(request('trang') === $k)>{{ $label }}</option>@endforeach
                </select></label>
            <label class="sf-field"><span>Loại việc</span>
                <select name="loai">
                    <option value="">Tất cả</option>
                    @foreach ($types as $k => $label)<option value="{{ $k }}" @selected(request('loai') === $k)>{{ $label }}</option>@endforeach
                </select></label>
            <div class="sf-filters__actions">
                <button type="submit" class="sf-btn sf-btn--dark">Xem</button>
                @if (array_filter($q))<a href="{{ route('activity.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-times"></i> Bỏ lọc</a>@endif
            </div>
        </form>

        @if ($order)
            <p class="ak-note">Toàn bộ lịch sử đơn <b>#{{ $order }}</b> (không giới hạn ngày). Dòng "(lịch sử đơn)" lấy từ lịch sử chỉnh sửa cũ.
                <a href="{{ route('order.edit', $order) }}">Mở đơn #{{ $order }}</a></p>
            <section class="sf-card ak-card">
                <div class="ak-list" style="margin:0">
                    <table>
                        <thead><tr><th>Lúc</th><th>Người</th><th>Trang</th><th>Việc</th></tr></thead>
                        <tbody>
                            @forelse ($orderRows as $it)
                                <tr>
                                    <td class="ak-time">{{ date('d/m/Y H:i', $it['ts']) }}</td>
                                    <td>{{ $it['nguoi'] }}</td>
                                    <td class="ak-area">{{ $it['trang'] }}</td>
                                    <td>{{ $it['viec'] }}
                                        @foreach ($it['chu_y'] ?? [] as $f)<span class="ak-flag">{{ $f }}</span>@endforeach
                                        @if (!empty($it['thay_doi']))<ul class="ak-changes">@foreach ($it['thay_doi'] as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="sf-empty">Không có lịch sử nào của đơn #{{ $order }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @else
        <p class="ak-note">
            {{ $result['pham_vi'] }}.
            @if ($result['nhat_ky_tu']) Nhật ký chi tiết có từ {{ $result['nhat_ky_tu'] }}; trước đó chỉ có lịch sử chỉnh sửa đơn hàng. @endif
            "Hoạt động" là ước tính: cộng các quãng giữa hai thao tác / lượt mở trang cách nhau không quá {{ config('activity.idle_minutes') }} phút.
        </p>

        <div class="ak-people">
            @forelse ($result['nguoi'] as $p)
                <section class="sf-card ak-card">
                    <header class="ak-card__head">
                        <h2>{{ $p['nguoi'] }}</h2>
                        <span class="ak-sub">{{ $p['so_thao_tac'] ?? 0 }} thao tác</span>
                        @foreach ($p['cau_hoi_tro_ly'] ?? [] as $as => $n)<span class="ak-sub">· {{ $n }} câu hỏi {{ $as }}</span>@endforeach
                    </header>
                    @if (!empty($p['theo_ngay']))
                        <div class="ak-days">
                            @foreach ($p['theo_ngay'] as $d)
                                <span class="ak-day"><b>{{ $d['ngay'] }}</b>vào {{ $d['vao_luc'] }} · thao tác cuối {{ $d['thao_tac_cuoi'] }} · hoạt động ~{{ $d['hoat_dong'] }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if (!empty($p['thao_tac_theo_trang']) || !empty($p['trang_da_mo']))
                        <div class="ak-chips">
                            @foreach ($p['thao_tac_theo_trang'] ?? [] as $area => $n)<span class="ak-chip">{{ $area }}: {{ $n }} thao tác</span>@endforeach
                            @foreach ($p['trang_da_mo'] ?? [] as $area => $n)<span class="ak-chip ak-chip--page">Mở {{ $area }} ×{{ $n }}</span>@endforeach
                        </div>
                    @endif
                    @if (!empty($p['dang_chu_y']))
                        <div class="ak-flags"><b><i class="fas fa-exclamation-triangle"></i> Đáng chú ý</b>
                            <ul>@foreach (array_slice($p['dang_chu_y'], 0, 20) as $f)<li>{{ $f }}</li>@endforeach</ul></div>
                    @endif
                    @if (!empty($p['dong_thoi_gian']))
                        <div class="ak-list">
                            <table>
                                <thead><tr><th>Lúc</th><th>Trang</th><th>Việc</th></tr></thead>
                                <tbody>
                                    @foreach ($p['dong_thoi_gian'] as $it)
                                        <tr>
                                            <td class="ak-time">{{ $it['luc'] }}</td>
                                            <td class="ak-area">{{ $it['trang'] }}</td>
                                            <td>
                                                {{ $it['viec'] }}
                                                @foreach ($it['chu_y'] ?? [] as $f)<span class="ak-flag">{{ $f }}</span>@endforeach
                                                @if (!empty($it['thay_doi']))<ul class="ak-changes">@foreach ($it['thay_doi'] as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
                                                @if (!empty($it['ip']) || !empty($it['thiet_bi']))<div class="ak-meta">{{ $it['thiet_bi'] ?? '' }}{{ !empty($it['ip']) ? ' · IP ' . $it['ip'] : '' }}</div>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if (!empty($p['bi_cat_bot']))<div class="ak-trunc">{{ $p['bi_cat_bot'] }}</div>@endif
                </section>
            @empty
                <div class="sf-card sf-empty">Không có hoạt động nào khớp bộ lọc.</div>
            @endforelse
        </div>
        @endif
    </div>
@endsection
