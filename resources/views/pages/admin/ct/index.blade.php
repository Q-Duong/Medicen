@extends('layouts.default_auth')
@section('title', 'Công nợ CT - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/report.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $m = fn ($v) => number_format((int) $v, 0, ',', '.');
        $neg = fn ($v) => (int) $v < 0 ? 'is-neg' : '';
        $q = fn (array $extra = []) => array_filter(array_merge(['year' => $year, 'partner' => $partnerId, 'tab' => $tab, 'loai' => $kind, 'bao_cao' => $focus['month'] ?? null], $extra));
        // Đánh dấu khi mở từ Báo cáo thu chi: kỳ trước tháng X còn nợ tại đầu tháng X, và lần trả trong tháng X
        $focusRow = function ($r) use ($focus) {
            if (!$focus || ($r->year * 100 + $r->month) >= ($focus['start']->year * 100 + $focus['start']->month)) {
                return false;
            }
            $paidBefore = $r->payments->filter(fn ($p) => $p->paid_on->lt($focus['start']))->sum('amount');
            $paidIn = $r->payments->filter(fn ($p) => $p->paid_on->betweenIncluded($focus['start'], $focus['end']))->sum('amount');

            return $r->medicen_collect - $paidBefore > 0 || $paidIn > 0;
        };
        $focusPay = fn ($p) => $focus && $p->paid_on->betweenIncluded($focus['start'], $focus['end']);
        $t = $summary['total'];
    @endphp
    <div class="rp">
        <div class="rp-head">
            <div class="rp-head__title">
                <h1>Công nợ CT</h1>
                <p>Phần Medicen thu về từ máy CT đặt tại bệnh viện hợp tác · năm {{ $year }}@if (!$canEdit) · chỉ xem @endif</p>
            </div>
            <form class="rp-head__actions" method="get">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="rp-field">
                    <span>Năm</span>
                    <select name="year" onchange="this.form.submit()">
                        @for ($y = now()->year + 1; $y >= 2026; $y--)
                            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endfor
                    </select>
                </label>
                <label class="rp-field">
                    <span>Bệnh viện</span>
                    <select name="partner" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        @foreach ($partners as $p)
                            <option value="{{ $p->id }}" @selected($partnerId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($canEdit)
                    <a href="{{ route('ct.create', ['year' => $year, 'partner' => $partnerId]) }}" class="rp-btn rp-btn--primary"><i class="fas fa-plus"></i> Thêm kỳ CT</a>
                @endif
            </form>
        </div>
        @include('pages.admin.ct._toast')

        <div class="rp-kpis">
            <section class="rp-card rp-kpi rp-card--debt">
                <h2 class="rp-card__title"><i class="fas fa-x-ray"></i> Doanh thu CT {{ $year }}</h2>
                <dl class="rp-kpi__grid">
                    <div><dt>Tổng doanh thu</dt><dd>{{ $m($t['revenue']) }}</dd></div>
                    <div><dt>Tổng chi phí</dt><dd>{{ $m($t['cost']) }}</dd></div>
                    <div><dt>Lãi / lỗ</dt><dd class="{{ $neg($t['profit']) }}">{{ $m($t['profit']) }}</dd></div>
                </dl>
            </section>
            <section class="rp-card rp-kpi rp-card--thu">
                <h2 class="rp-card__title"><i class="fas fa-hand-holding-usd"></i> Medicen thu về {{ $year }}</h2>
                <dl class="rp-kpi__grid">
                    <div><dt>Phải thu</dt><dd>{{ $m($t['medicen_collect']) }}</dd></div>
                    <div class="is-ok"><dt>Đã thu</dt><dd>{{ $m($t['paid']) }}</dd></div>
                    <div class="is-bad"><dt>Còn phải thu</dt><dd>{{ $m($t['outstanding']) }}</dd></div>
                </dl>
            </section>
            <section class="rp-card rp-kpi rp-kpi--balance {{ $totalOutstanding > 0 ? 'rp-card--neg' : 'rp-card--thu' }}">
                <h2 class="rp-card__title"><i class="fas fa-exclamation-circle"></i> Còn phải thu mọi kỳ</h2>
                <p class="rp-kpi__big {{ $totalOutstanding > 0 ? 'is-neg' : 'is-pos' }}">{{ $m($totalOutstanding) }}</p>
                <p class="rp-kpi__note">Số này đi vào dòng CT của Báo cáo thu chi</p>
            </section>
            <section class="rp-card rp-kpi">
                <h2 class="rp-card__title"><i class="fas fa-users"></i> Bệnh viện chia {{ $year }}</h2>
                <dl class="rp-kpi__grid">
                    <div><dt>BV chia lãi</dt><dd>{{ $m($t['hospital_share']) }}</dd></div>
                    <div><dt>Medicen chia lãi</dt><dd>{{ $m($t['medicen_share']) }}</dd></div>
                    <div><dt>BV thu về</dt><dd>{{ $m($t['hospital_collect']) }}</dd></div>
                </dl>
            </section>
        </div>

        @if ($focus)
            <section class="rp-card rp-card--debt bk-focus">
                <div>
                    <h2 class="rp-card__title"><i class="fas fa-link"></i> Từ Báo cáo thu chi tháng {{ $focus['month'] }}/{{ $year }}
                        · {{ $focus['partner']->name ?? 'tất cả bệnh viện' }}{{ $kind === 'bhyt' ? ' (BHYT)' : '' }}</h2>
                    <p class="bk-hint">Dòng tô xanh là các kỳ còn nợ tại ngày 01/{{ sprintf('%02d', $focus['month']) }} hoặc được trả trong tháng {{ $focus['month'] }}; lần trả trong tháng in đậm.</p>
                </div>
                <dl class="rp-kpi__grid">
                    <div><dt>Dự thu (còn phải thu đến đầu tháng)</dt><dd>{{ $m($focus['est']) }}</dd></div>
                    <div class="is-ok"><dt>Đã thu trong tháng {{ $focus['month'] }}</dt><dd>{{ $m($focus['paid']) }}</dd></div>
                    <div class="{{ $focus['est'] - $focus['paid'] > 0 ? 'is-bad' : '' }}"><dt>Còn lại</dt><dd>{{ $m($focus['est'] - $focus['paid']) }}</dd></div>
                </dl>
                <div class="bk-actions" style="margin-top:6px">
                    <a href="{{ route('reports.index', ['month' => $focus['month'], 'year' => $year]) }}" class="rp-btn"><i class="fas fa-arrow-left"></i> Về báo cáo tháng {{ $focus['month'] }}</a>
                    <a href="{{ route('ct.index', ['year' => $year]) }}" class="rp-btn">Bỏ lọc</a>
                </div>
            </section>
        @endif

        <nav class="bk-tabs">
            <a href="{{ route('ct.index', $q(['tab' => null])) }}" class="{{ $tab === 'chi-tiet' ? 'is-active' : '' }}"><i class="fas fa-list"></i>Chi tiết theo tháng</a>
            <a href="{{ route('ct.index', $q(['tab' => 'tong-hop'])) }}" class="{{ $tab === 'tong-hop' ? 'is-active' : '' }}"><i class="fas fa-table"></i>Tổng hợp</a>
            <a href="{{ route('ct.index', $q(['tab' => 'ghi-nho'])) }}" class="{{ $tab === 'ghi-nho' ? 'is-active' : '' }}"><i class="fas fa-sticky-note"></i>Công nợ cũ ({{ $memos->count() }})</a>
            <a href="{{ route('ct.index', $q(['tab' => 'don-vi'])) }}" class="{{ $tab === 'don-vi' ? 'is-active' : '' }}"><i class="fas fa-hospital"></i>Bệnh viện ({{ $partners->count() }})</a>
        </nav>

        @if ($tab === 'chi-tiet')
            <section class="rp-card">
                @if ($rows->isEmpty())
                    <div class="rp-empty">
                        <i class="fas fa-folder-open"></i>
                        <h2>Chưa có công nợ CT năm {{ $year }}</h2>
                        @if ($canEdit)<p>Mỗi tháng thêm một dòng cho mỗi bệnh viện: số ca, doanh thu, chi phí, phần chia và Medicen thu về.</p>@endif
                    </div>
                @else
                    <div class="rp-scroll">
                        <table class="bk-table">
                            <thead>
                                <tr>
                                    <th class="is-text">Bệnh viện</th>
                                    <th>Số ca</th>
                                    <th>Doanh thu</th>
                                    <th>Chi phí</th>
                                    <th>Lãi / lỗ</th>
                                    <th>Medicen thu về</th>
                                    <th>Đã thanh toán</th>
                                    <th>Còn phải thu</th>
                                    <th></th>
                                    <th>BV chia</th>
                                    <th>Medicen chia</th>
                                    <th>BV thu về</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $lastMonth = null; @endphp
                                @foreach ($rows as $r)
                                    @if ($lastMonth !== $r->month)
                                        <tr class="is-group"><td class="is-text" colspan="12"><i class="far fa-calendar-alt"></i>Tháng {{ $r->month }}/{{ $r->year }}</td></tr>
                                        @php $lastMonth = $r->month; @endphp
                                    @endif
                                    <tr class="{{ $focusRow($r) ? 'is-focus' : '' }}">
                                        <td class="is-text is-strong">
                                            {{ $r->partner->name }}
                                            @if ($r->kind === 'bhyt')<span class="bk-tag bk-tag--bhyt">BHYT{{ $r->label ? ' ' . $r->label : '' }}</span>@endif
                                        </td>
                                        <td>{{ $r->quantity !== null ? $m($r->quantity) : '' }}</td>
                                        <td>{{ $m($r->revenue) }}</td>
                                        <td>{{ $m($r->cost) }}</td>
                                        <td class="{{ $neg($r->profit) }}">{{ $m($r->profit) }}</td>
                                        <td class="is-strong">{{ $m($r->medicen_collect) }}</td>
                                        <td>
                                            <div class="bk-pays">
                                                @forelse ($r->payments as $p)
                                                    <span class="{{ $focusPay($p) ? 'is-focus' : '' }}">{{ $p->paid_on->format('d/m') }} · <span class="bk-tag {{ $p->method === 'L-VCB' ? 'bk-tag--lvcb' : 'bk-tag--hdb' }}">{{ $p->method }}</span> <b>{{ $m($p->amount) }}</b></span>
                                                @empty
                                                    <span>—</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="{{ $r->outstanding > 0 ? 'is-due' : 'is-pos' }}">{{ $r->outstanding > 0 ? $m($r->outstanding) : 'Đủ' }}</td>
                                        <td>
                                            <a href="{{ route('ct.edit', $r) }}" class="rp-icon" title="{{ $canEdit ? 'Sửa / nhập thanh toán' : 'Xem' }}"><i class="fas {{ $canEdit ? 'fa-pen' : 'fa-eye' }}"></i></a>
                                        </td>
                                        <td class="{{ $neg($r->hospital_share) }}">{{ $m($r->hospital_share) }}</td>
                                        <td class="{{ $neg($r->medicen_share) }}">{{ $m($r->medicen_share) }}</td>
                                        <td class="{{ $neg($r->hospital_collect) }}">{{ $m($r->hospital_collect) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="is-text">Tổng {{ $partnerId ? '' : 'năm ' . $year }}</td>
                                    <td>{{ $m($rows->sum('quantity')) }}</td>
                                    <td>{{ $m($rows->sum('revenue')) }}</td>
                                    <td>{{ $m($rows->sum('cost')) }}</td>
                                    <td>{{ $m($rows->sum(fn ($r) => $r->profit)) }}</td>
                                    <td>{{ $m($rows->sum('medicen_collect')) }}</td>
                                    <td>{{ $m($rows->sum(fn ($r) => $r->paid)) }}</td>
                                    <td class="is-due">{{ $m($rows->sum(fn ($r) => $r->outstanding)) }}</td>
                                    <td></td>
                                    <td>{{ $m($rows->sum('hospital_share')) }}</td>
                                    <td>{{ $m($rows->sum('medicen_share')) }}</td>
                                    <td>{{ $m($rows->sum('hospital_collect')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>
        @elseif ($tab === 'tong-hop')
            <div class="rp-sums" style="grid-template-columns:1fr">
                @foreach (['by_partner' => 'Theo bệnh viện', 'by_month' => 'Theo tháng'] as $key => $title)
                    <section class="rp-card">
                        <h2 class="rp-card__title">{{ $title }} · năm {{ $year }}</h2>
                        <div class="rp-scroll">
                            <table class="bk-table">
                                <thead>
                                    <tr>
                                        <th class="is-text">{{ $key === 'by_partner' ? 'Bệnh viện' : 'Tháng' }}</th>
                                        <th>Doanh thu</th><th>Chi phí</th><th>Lãi / lỗ</th><th>BV chia</th><th>Medicen chia</th>
                                        <th>BV thu về</th><th>Medicen thu về</th><th>Đã thanh toán</th><th>Còn phải thu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($summary[$key] as $k => $s)
                                        <tr>
                                            <td class="is-text is-strong">{{ $key === 'by_partner' ? $s['partner']->name : 'Tháng ' . $k }}</td>
                                            <td>{{ $m($s['revenue']) }}</td>
                                            <td>{{ $m($s['cost']) }}</td>
                                            <td class="{{ $neg($s['profit']) }}">{{ $m($s['profit']) }}</td>
                                            <td class="{{ $neg($s['hospital_share']) }}">{{ $m($s['hospital_share']) }}</td>
                                            <td class="{{ $neg($s['medicen_share']) }}">{{ $m($s['medicen_share']) }}</td>
                                            <td>{{ $m($s['hospital_collect']) }}</td>
                                            <td class="is-strong">{{ $m($s['medicen_collect']) }}</td>
                                            <td>{{ $m($s['paid']) }}</td>
                                            <td class="{{ $s['outstanding'] > 0 ? 'is-due' : '' }}">{{ $m($s['outstanding']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td class="is-text">Tổng cộng</td>
                                        <td>{{ $m($t['revenue']) }}</td><td>{{ $m($t['cost']) }}</td><td>{{ $m($t['profit']) }}</td>
                                        <td>{{ $m($t['hospital_share']) }}</td><td>{{ $m($t['medicen_share']) }}</td><td>{{ $m($t['hospital_collect']) }}</td>
                                        <td>{{ $m($t['medicen_collect']) }}</td><td>{{ $m($t['paid']) }}</td><td class="is-due">{{ $m($t['outstanding']) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>
                @endforeach
            </div>
        @elseif ($tab === 'ghi-nho')
            @include('pages.admin.ct._memos', ['scope' => 'ct', 'intro' => 'Công nợ cũ của các bệnh viện không còn hợp tác: chỉ để ghi nhớ, không tính vào Công nợ CT và Báo cáo thu chi.'])
        @else
            <section class="rp-card">
                <h2 class="rp-card__title">Bệnh viện hợp tác</h2>
                <div class="rp-scroll">
                    <table class="bk-table">
                        <thead><tr><th class="is-text">Mã</th><th class="is-text">Tên</th><th class="is-text">Trạng thái</th><th>Còn phải thu</th>@if ($canEdit)<th></th>@endif</tr></thead>
                        <tbody>
                            @foreach ($partners as $p)
                                @php $out = app(\App\Services\Accounting\CtBook::class)->totalOutstanding($p->id); @endphp
                                <tr class="{{ $p->active ? '' : 'is-muted' }}">
                                    <td class="is-text"><span class="bk-tag">{{ $p->code }}</span></td>
                                    @if ($canEdit)
                                        <td class="is-text" colspan="2">
                                            <form method="post" action="{{ route('ct.partners.update', $p) }}" class="bk-form" style="grid-template-columns: 1fr auto auto">
                                                @csrf @method('patch')
                                                <input type="text" name="name" value="{{ $p->name }}" maxlength="100" aria-label="Tên bệnh viện">
                                                <label class="bk-check"><input type="checkbox" name="active" value="1" @checked($p->active)> Đang hợp tác</label>
                                                <button class="rp-btn" type="submit">Lưu</button>
                                            </form>
                                        </td>
                                    @else
                                        <td class="is-text">{{ $p->name }}</td>
                                        <td class="is-text">{{ $p->active ? 'Đang hợp tác' : 'Ngừng' }}</td>
                                    @endif
                                    <td class="{{ $out > 0 ? 'is-due' : '' }}">{{ $m($out) }}</td>
                                    @if ($canEdit)
                                        <td>
                                            @if ($p->receivables_count === 0)
                                                <form method="post" action="{{ route('ct.partners.destroy', $p) }}" data-confirm="Xóa bệnh viện &quot;{{ $p->name }}&quot;? (chưa có kỳ công nợ nào)" data-confirm-danger>
                                                    @csrf @method('delete')
                                                    <button type="submit" class="rp-icon rp-icon--danger" title="Xóa (chưa có kỳ công nợ nào)"><i class="fas fa-trash-alt"></i></button>
                                                </form>
                                            @else
                                                <span class="bk-tag" title="Đã có {{ $p->receivables_count }} kỳ công nợ: không xóa được, bỏ chọn &quot;Đang hợp tác&quot; để ngừng">{{ $p->receivables_count }} kỳ</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($canEdit)
                    <h3 class="bk-section-title">Thêm bệnh viện</h3>
                    <form method="post" action="{{ route('ct.partners.store') }}" class="bk-form">
                        @csrf
                        <label class="is-wide"><span>Tên bệnh viện</span><input type="text" name="name" maxlength="100" required placeholder="Vd: Tân An"></label>
                        <label><span>Mã ngắn</span><input type="text" name="code" maxlength="10" required placeholder="Vd: TA"></label>
                        <div><button class="rp-btn rp-btn--primary" type="submit"><i class="fas fa-plus"></i> Thêm</button></div>
                    </form>
                    <p class="bk-hint">Mã ngắn dùng để nối với dòng "CT …" của Báo cáo thu chi, không đổi được sau khi tạo.</p>
                @endif
            </section>
        @endif
    </div>
@endsection
