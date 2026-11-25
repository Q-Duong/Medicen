@extends('layouts.default_auth')
@section('title', 'Nhật ký hoạt động - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <style>
        .ak-people { display: flex; flex-direction: column; gap: 14px; }
        .ak-card { padding: 0; overflow: hidden; }
        .ak-card > summary { list-style: none; cursor: pointer; }
        .ak-card > summary::-webkit-details-marker, .ak-day > summary::-webkit-details-marker, .ak-more > summary::-webkit-details-marker { display: none; }
        .ak-card__head { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 14px; padding: 13px 18px; background: #fcfbf8; }
        .ak-card[open] > .ak-card__head { border-bottom: 1px solid #efece5; }
        .ak-card__head h2 { margin: 0; font-size: 16px; font-weight: 700; }
        .ak-card__head .ak-sub { color: #73716c; font-size: 13px; }
        .ak-caret { margin-left: auto; color: #a3a19b; transition: transform .15s; }
        details[open] > summary .ak-caret { transform: rotate(90deg); }
        .ak-badge { padding: 2px 8px; border-radius: 999px; background: #fdecea; color: #b42318; font-size: 12px; font-weight: 600; }
        .ak-chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 12px 18px 0; }
        .ak-chip { padding: 3px 9px; border-radius: 999px; background: #f1efe9; color: #4a4945; font-size: 12px; }
        .ak-chip--page { background: #ebf3fd; color: #0b5cad; }
        .ak-flags { margin: 12px 18px 0; border: 1px solid #f3c6bf; border-radius: 11px; background: #fdf1ef; color: #7a1d14; font-size: 13px; }
        .ak-flags > summary { padding: 9px 13px; cursor: pointer; font-weight: 700; list-style: none; }
        .ak-flags ul { margin: 0; padding: 0 13px 10px 31px; }
        .ak-days { padding: 12px 18px 14px; display: flex; flex-direction: column; gap: 8px; }
        .ak-day { border: 1px solid #ebe8e0; border-radius: 11px; background: #fff; overflow: hidden; }
        .ak-day > summary { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 12px; padding: 9px 13px; cursor: pointer; list-style: none; font-size: 13px; color: #4a4945; }
        .ak-day > summary:hover { background: #faf9f5; }
        .ak-day[open] > summary { border-bottom: 1px solid #f1eee7; background: #faf9f5; }
        .ak-day__date { font-weight: 700; color: #1f1e1d; min-width: 118px; }
        .ak-day__meta { color: #73716c; }
        .ak-day--empty > summary { cursor: default; }
        .ak-list table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ak-list td { padding: 7px 13px; border-top: 1px solid #f4f2ec; vertical-align: top; }
        .ak-list tr:first-child td { border-top: 0; }
        .ak-time { width: 62px; white-space: nowrap; color: #73716c; font-variant-numeric: tabular-nums; }
        .ak-area { width: 150px; white-space: nowrap; color: #4a4945; }
        .ak-x { margin-left: 6px; padding: 0 7px; border-radius: 999px; background: #f1efe9; color: #4a4945; font-size: 11.5px; font-weight: 600; }
        .ak-changes { margin: 4px 0 0; padding: 0; list-style: none; color: #73716c; font-size: 12.5px; }
        .ak-changes li::before { content: '↳ '; color: #c96442; }
        .ak-more > summary { display: inline; cursor: pointer; color: #0b5cad; font-size: 12px; list-style: none; }
        .ak-flag { display: inline-block; margin: 3px 4px 0 0; padding: 1px 7px; border-radius: 999px; background: #fdecea; color: #b42318; font-size: 11.5px; font-weight: 600; }
        .ak-meta { color: #a3a19b; font-size: 12px; }
        .ak-note { margin: 0 0 10px; color: #73716c; font-size: 13px; }
        .ak-tools { display: flex; gap: 8px; margin: 0 0 12px; }
        .ak-trunc { padding: 0 18px 12px; color: #9a5b00; font-size: 12.5px; }
        .ak-dayrow td { padding: 9px 13px !important; background: #faf9f5; font-weight: 700; color: #1f1e1d; }
        @media (max-width: 768px) {
            .ak-list tr { display: block; padding: 7px 12px; border-top: 1px solid #f1eee7; }
            .ak-list td { display: block; padding: 1px 0; border: 0; width: auto; }
            .ak-time, .ak-area { display: inline !important; margin-right: 8px; }
            .ak-day__date { min-width: 0; }
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

        @php
            $wd = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
            $dayLabel = fn (int $ts) => $wd[(int) date('w', $ts)] . ', ' . date('d/m/Y', $ts);
            // Gộp các thao tác liên tiếp cùng trang, cùng việc (vd sửa một đơn công nợ nhiều lần trong vài phút):
            // một dòng ×N, thay đổi gộp theo từng mục "giá trị đầu → giá trị cuối". Dòng có cờ "đáng chú ý" / đăng nhập giữ riêng.
            $collapse = function (array $items) {
                $parse = function (array $lines) {
                    $m = [];
                    foreach ($lines as $l) {
                        if (preg_match('/^(.+?):\s(.*?)\s→\s(.*)$/u', $l, $x)) {
                            $m[$x[1]] = [$x[2], $x[3]];
                        } else {
                            $m[$l] = null;
                        }
                    }

                    return $m;
                };
                $out = [];
                foreach ($items as $it) {
                    $i = count($out) - 1;
                    // Dòng chung chung đi kèm ngay sau dòng chi tiết (vd "Sửa công nợ" sau "Sửa công nợ đơn #3525…"): bỏ, đã có dòng chi tiết
                    if ($i >= 0 && empty($it['thay_doi']) && empty($it['chu_y']) && empty($it['ip']) && $out[$i]['trang'] === $it['trang']
                        && $it['ts'] - $out[$i]['_end'] <= 120 && $out[$i]['viec'] !== $it['viec'] && str_starts_with($out[$i]['viec'], $it['viec'])) {
                        continue;
                    }
                    $mergeable = empty($it['chu_y']);
                    if ($i >= 0 && $mergeable && $out[$i]['_merge'] && $out[$i]['trang'] === $it['trang'] && $out[$i]['viec'] === $it['viec']
                        && ($out[$i]['ip'] ?? null) === ($it['ip'] ?? null) && ($out[$i]['thiet_bi'] ?? null) === ($it['thiet_bi'] ?? null)
                        && $it['ts'] - $out[$i]['_end'] <= 1800) {
                        $out[$i]['_n']++;
                        $out[$i]['_end'] = $it['ts'];
                        foreach ($parse($it['thay_doi'] ?? []) as $k => $v) {
                            $out[$i]['_ch'][$k] = isset($out[$i]['_ch'][$k], $v) ? [$out[$i]['_ch'][$k][0], $v[1]] : $v;
                        }
                        continue;
                    }
                    $out[] = $it + ['_n' => 1, '_end' => $it['ts'], '_merge' => $mergeable, '_ch' => $parse($it['thay_doi'] ?? [])];
                }
                foreach ($out as &$o) {
                    if ($o['_n'] > 1) {
                        // Bỏ mục quay về giá trị cũ (gõ rồi sửa lại như cũ)
                        $o['thay_doi'] = collect($o['_ch'])->filter(fn ($v) => $v === null || $v[0] !== $v[1])
                            ->map(fn ($v, $k) => $v === null ? $k : "{$k}: {$v[0]} → {$v[1]}")->values()->all();
                    }
                }

                return $out;
            };
            $byDay = fn (array $items) => collect($items)->groupBy(fn ($it) => date('Y-m-d', $it['ts']))->sortKeysDesc();
        @endphp

        @if ($order)
            <p class="ak-note">Toàn bộ lịch sử đơn <b>#{{ $order }}</b> (không giới hạn ngày), chia theo ngày. Dòng "(lịch sử đơn)" lấy từ lịch sử chỉnh sửa cũ.
                <a href="{{ route('order.edit', $order) }}">Mở đơn #{{ $order }}</a></p>
            <section class="sf-card ak-card">
                <div class="ak-list">
                    <table>
                        <tbody>
                            @forelse ($orderRows->groupBy(fn ($it) => date('Y-m-d', $it['ts'])) as $d => $rows)
                                <tr class="ak-dayrow"><td colspan="4">{{ $dayLabel($rows->first()['ts']) }} <span class="ak-day__meta" style="font-weight:400">· {{ $rows->count() }} thao tác</span></td></tr>
                                @foreach ($rows as $it)
                                    <tr>
                                        <td class="ak-time">{{ date('H:i', $it['ts']) }}</td>
                                        <td style="width:140px">{{ $it['nguoi'] }}</td>
                                        <td class="ak-area">{{ $it['trang'] }}</td>
                                        <td>{{ $it['viec'] }}
                                            @foreach ($it['chu_y'] ?? [] as $f)<span class="ak-flag">{{ $f }}</span>@endforeach
                                            @if (!empty($it['thay_doi']))<ul class="ak-changes">@foreach ($it['thay_doi'] as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
                                        </td>
                                    </tr>
                                @endforeach
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
        @if (count($result['nguoi']) > 1)
            <div class="ak-tools">
                <button type="button" class="sf-btn sf-btn--ghost" data-ak-toggle="1"><i class="fas fa-expand-alt"></i> Mở tất cả</button>
                <button type="button" class="sf-btn sf-btn--ghost" data-ak-toggle="0"><i class="fas fa-compress-alt"></i> Thu gọn tất cả</button>
            </div>
        @endif

        <div class="ak-people">
            @forelse ($result['nguoi'] as $pi => $p)
                @php
                    $days = $byDay($p['dong_thoi_gian'] ?? []);
                    $dayInfo = collect($p['theo_ngay'] ?? [])->keyBy('ngay');
                    // Ngày chỉ có mở trang (không thao tác) vẫn hiện một dòng tóm tắt
                    $allDays = $dayInfo->keys()->map(fn ($d) => \Carbon\Carbon::createFromFormat('d/m/Y', $d)->format('Y-m-d'))
                        ->merge($days->keys())->unique()->sortDesc()->values();
                    $flagCount = count($p['dang_chu_y'] ?? []);
                    $openPerson = $pi === 0 || count($result['nguoi']) === 1;
                @endphp
                <details class="sf-card ak-card" @if ($openPerson) open @endif>
                    <summary class="ak-card__head">
                        <h2>{{ $p['nguoi'] }}</h2>
                        <span class="ak-sub">{{ $p['so_thao_tac'] ?? 0 }} thao tác · {{ $allDays->count() }} ngày</span>
                        @foreach ($p['cau_hoi_tro_ly'] ?? [] as $as => $n)<span class="ak-sub">· {{ $n }} câu hỏi {{ $as }}</span>@endforeach
                        @if ($flagCount)<span class="ak-badge"><i class="fas fa-exclamation-triangle"></i> {{ $flagCount }} đáng chú ý</span>@endif
                        <i class="fas fa-chevron-right ak-caret"></i>
                    </summary>

                    @if (!empty($p['thao_tac_theo_trang']) || !empty($p['trang_da_mo']))
                        <div class="ak-chips">
                            @foreach ($p['thao_tac_theo_trang'] ?? [] as $area => $n)<span class="ak-chip">{{ $area }}: {{ $n }} thao tác</span>@endforeach
                            @foreach ($p['trang_da_mo'] ?? [] as $area => $n)<span class="ak-chip ak-chip--page">Mở {{ $area }} ×{{ $n }}</span>@endforeach
                        </div>
                    @endif
                    @if ($flagCount)
                        <details class="ak-flags" @if ($flagCount <= 5) open @endif>
                            <summary><i class="fas fa-exclamation-triangle"></i> Đáng chú ý ({{ $flagCount }})</summary>
                            <ul>@foreach (array_slice($p['dang_chu_y'], 0, 30) as $f)<li>{{ $f }}</li>@endforeach</ul>
                        </details>
                    @endif

                    <div class="ak-days">
                        @foreach ($allDays as $di => $d)
                            @php
                                $ts = strtotime($d . ' 12:00');
                                $info = $dayInfo[date('d/m/Y', $ts)] ?? null;
                                $items = $days[$d] ?? collect();
                                $dayFlags = $items->filter(fn ($it) => !empty($it['chu_y']))->count();
                            @endphp
                            <details class="ak-day {{ $items->isEmpty() ? 'ak-day--empty' : '' }}" @if ($di === 0 && $items->isNotEmpty()) open @endif>
                                <summary>
                                    <span class="ak-day__date">{{ $dayLabel($ts) }}</span>
                                    @if ($info)<span class="ak-day__meta">vào {{ $info['vao_luc'] }} · cuối {{ $info['thao_tac_cuoi'] }} · hoạt động ~{{ $info['hoat_dong'] }}</span>@endif
                                    <span class="ak-day__meta">· {{ $items->isEmpty() ? 'chỉ mở trang' : $items->count() . ' thao tác' }}</span>
                                    @if ($dayFlags)<span class="ak-badge">{{ $dayFlags }} đáng chú ý</span>@endif
                                    @if ($items->isNotEmpty())<i class="fas fa-chevron-right ak-caret"></i>@endif
                                </summary>
                                @if ($items->isNotEmpty())
                                    <div class="ak-list">
                                        <table>
                                            <tbody>
                                                @foreach ($collapse($items->values()->all()) as $it)
                                                    <tr>
                                                        <td class="ak-time">{{ date('H:i', $it['ts']) }}@if ($it['_n'] > 1 && date('H:i', $it['_end']) !== date('H:i', $it['ts']))<br><span class="ak-meta">→ {{ date('H:i', $it['_end']) }}</span>@endif</td>
                                                        <td class="ak-area">{{ $it['trang'] }}</td>
                                                        <td>
                                                            {{ $it['viec'] }}@if ($it['_n'] > 1)<span class="ak-x">×{{ $it['_n'] }}</span>@endif
                                                            @foreach ($it['chu_y'] ?? [] as $f)<span class="ak-flag">{{ $f }}</span>@endforeach
                                                            @if (!empty($it['thay_doi']))
                                                                <ul class="ak-changes">@foreach (array_slice($it['thay_doi'], 0, 3) as $l)<li>{{ $l }}</li>@endforeach</ul>
                                                                @if (count($it['thay_doi']) > 3)
                                                                    <details class="ak-more"><summary>+ {{ count($it['thay_doi']) - 3 }} thay đổi khác</summary>
                                                                        <ul class="ak-changes">@foreach (array_slice($it['thay_doi'], 3) as $l)<li>{{ $l }}</li>@endforeach</ul></details>
                                                                @endif
                                                            @endif
                                                            @if (!empty($it['ip']) || !empty($it['thiet_bi']))<div class="ak-meta">{{ $it['thiet_bi'] ?? '' }}{{ !empty($it['ip']) ? ' · IP ' . $it['ip'] : '' }}</div>@endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </details>
                        @endforeach
                    </div>
                    @if (!empty($p['bi_cat_bot']))<div class="ak-trunc">{{ $p['bi_cat_bot'] }}</div>@endif
                </details>
            @empty
                <div class="sf-card sf-empty">Không có hoạt động nào khớp bộ lọc.</div>
            @endforelse
        </div>
        @endif
    </div>
@endsection
@push('js')
    <script>
        // Mở / thu gọn tất cả người và ngày
        document.querySelectorAll('[data-ak-toggle]').forEach(function (b) {
            b.addEventListener('click', function () {
                var open = b.dataset.akToggle === '1';
                document.querySelectorAll('.ak-card, .ak-day:not(.ak-day--empty)').forEach(function (d) { d.open = open; });
            });
        });
        // Ngày chỉ mở trang: không có gì để mở
        document.querySelectorAll('.ak-day--empty > summary').forEach(function (s) { s.addEventListener('click', function (e) { e.preventDefault(); }); });
    </script>
@endpush
