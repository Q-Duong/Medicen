@extends('layouts.default_auth')
@section('title', 'Tổng quan điều hành - Medicen')
@push('css')
    {{-- Khung trang + tiêu đề dùng chung với Phân tích tài chính, Báo cáo thu chi (.rp, .rp-head) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/report.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/director.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        // Số tiền gọn: 1,04 tỷ / 284,4 tr; số đầy đủ để ở title (rê chuột)
        $tr = function ($v) {
            $v = (float) $v;
            $a = abs($v);
            return $a >= 1e9 ? number_format($v / 1e9, 2, ',', '.') . ' tỷ' : ($a >= 1e6 ? number_format($v / 1e6, 1, ',', '.') . ' tr' : number_format($v, 0, ',', '.') . ' đ');
        };
        $full = fn ($v) => number_format((int) round((float) $v), 0, ',', '.') . ' đ';
        $n = fn ($v) => number_format((int) $v, 0, ',', '.');
        $days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        $today = $o['today'];
        $cash = $o['cash'];
        $pay = $o['payables'];
        $xq = $o['xq'];
        $monthLabel = 'tháng ' . $today->month;
        $maxAbs = max(1, collect($cash['weeks'])->max(fn ($w) => abs($w['end'])));
        $weekMax = max(1, $o['week']->max('cases'));
    @endphp
    <div class="rp dr">
        <div class="rp-head">
            <div class="rp-head__title">
                <h1>Tổng quan điều hành</h1>
                <p>Tiền đang có, sắp phải chi gì, X-quang tháng này thu về bao nhiêu · {{ $days[$today->dayOfWeek] }}, {{ $today->format('d/m/Y') }}, số liệu lúc {{ now()->format('H:i') }}</p>
            </div>
            <div class="rp-head__actions">
                <a href="{{ route('director.index') }}" class="rp-btn"><i class="fas fa-sync-alt"></i> Tải lại</a>
                <a href="{{ route('finance.index') }}" class="rp-btn"><i class="fas fa-chart-pie"></i> Phân tích tài chính</a>
            </div>
        </div>

        {{-- 4 con số chính --}}
        <div class="dr-kpis">
            <div class="dr-kpi dr-kpi--cash">
                <div class="dr-kpi__label"><i class="fas fa-wallet"></i> Tiền đang có
                    @if ($cash['estimated'])<span class="dr-tag dr-tag--warn">ước tính</span>@endif
                </div>
                <div class="dr-kpi__value" title="{{ $full($cash['amount']) }}">{{ $tr($cash['amount']) }}</div>
                <div class="dr-kpi__note">{{ $cash['source'] }}</div>
            </div>
            <div class="dr-kpi dr-kpi--pay">
                <div class="dr-kpi__label"><i class="fas fa-file-invoice-dollar"></i> Phải chi 30 ngày tới</div>
                <div class="dr-kpi__value" title="{{ $full($pay['soon_total']) }}">{{ $tr($pay['soon_total']) }}</div>
                <div class="dr-kpi__note">7 ngày tới: <b>{{ $tr($pay['week_total']) }}</b>
                    @if ($pay['late_total'] > 0) · <span class="dr-bad">quá ngày chưa ghi chi {{ $tr($pay['late_total']) }}</span>@endif
                </div>
            </div>
            <div class="dr-kpi dr-kpi--xq">
                <div class="dr-kpi__label"><i class="fas fa-x-ray"></i> X-quang {{ $monthLabel }} đã chụp xong</div>
                <div class="dr-kpi__value" title="{{ $full($xq['done']['amount']) }}">{{ $tr($xq['done']['amount']) }}</div>
                <div class="dr-kpi__note">{{ $xq['done']['orders'] }} đơn · {{ $n($xq['done']['cases']) }} ca (1/{{ $today->month }} → hôm qua)
                    @if ($xq['change_pct'] !== null)
                        · <span class="{{ $xq['change_pct'] < 0 ? 'dr-bad' : 'dr-good' }}">{{ $xq['change_pct'] > 0 ? '+' : '' }}{{ number_format($xq['change_pct'], 1, ',', '.') }}% so với cùng kỳ tháng trước</span>
                    @endif
                    <br>Hôm nay {{ $xq['today']['orders'] }} đơn{{ $xq['today']['orders'] ? ' (' . $tr($xq['today']['amount']) . ')' : '' }} · còn lịch {{ $tr($xq['upcoming']['amount']) }} → dự kiến cả tháng <b>{{ $tr($xq['month_total']) }}</b>
                </div>
            </div>
            <div class="dr-kpi dr-kpi--fc {{ $cash['balance_4w'] < $cash['threshold'] ? 'is-bad' : '' }}">
                <div class="dr-kpi__label"><i class="fas fa-chart-line"></i> Dự kiến còn sau 4 tuần</div>
                <div class="dr-kpi__value" title="{{ $full($cash['balance_4w']) }}">{{ $tr($cash['balance_4w']) }}</div>
                <div class="dr-kpi__note">Thu {{ $tr($cash['in_4w']) }} − chi {{ $tr($cash['out_4w']) }}
                    @if ($cash['lowest']) · thấp nhất <b class="{{ $cash['lowest']['so_du'] < $cash['threshold'] ? 'dr-bad' : '' }}">{{ $tr($cash['lowest']['so_du']) }}</b> (tuần {{ $cash['lowest']['tuan'] }}, từ {{ \Carbon\Carbon::parse($cash['lowest']['tu'])->format('d/m') }})@endif
                </div>
            </div>
        </div>

        <div class="dr-grid">
            {{-- Dòng tiền 13 tuần --}}
            <section class="dr-card dr-card--fc">
                <div class="dr-card__head">
                    <h2><i class="fas fa-chart-bar"></i> Số dư dự kiến 13 tuần</h2>
                    <a href="{{ route('finance.index', ['tab' => 'dong-tien']) }}">Chi tiết <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="dr-bars" role="img" aria-label="Số dư cuối mỗi tuần trong 13 tuần tới">
                    @foreach ($cash['weeks'] as $w)
                        <div class="dr-bar {{ $w['end'] < 0 ? 'is-neg' : ($w['low'] ? 'is-low' : '') }}"
                            title="Tuần {{ $w['no'] }} ({{ $w['from']->format('d/m') }}–{{ $w['to']->format('d/m') }}): thu {{ $full($w['in']) }}, chi {{ $full($w['out']) }}, cuối tuần còn {{ $full($w['end']) }}">
                            <span class="dr-bar__val">{{ $tr($w['end']) }}</span>
                            <span class="dr-bar__fill" style="height: {{ max(3, round(abs($w['end']) / $maxAbs * 100)) }}%"></span>
                            <span class="dr-bar__lbl">{{ $w['from']->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="dr-foot">Cột đỏ: dưới ngưỡng cảnh báo {{ $tr($cash['threshold']) }}. Thu gồm công nợ theo hạn hóa đơn, 50% nợ quá hạn, CT và các khoản thu trên Báo cáo thu chi; chi theo Báo cáo thu chi (tháng chưa lập thì lặp lại khoản định kỳ).</p>
            </section>

            {{-- X-quang tháng này --}}
            <section class="dr-card dr-card--xq">
                <div class="dr-card__head">
                    <h2><i class="fas fa-x-ray"></i> X-quang {{ $monthLabel }}</h2>
                    <a href="{{ route('finance.index', ['tab' => 'tien-ket']) }}">Công nợ <i class="fas fa-arrow-right"></i></a>
                </div>
                <dl class="dr-list">
                    <div><dt>Đã chụp xong (1/{{ $today->month }} → hôm qua)</dt><dd title="{{ $full($xq['done']['amount']) }}">{{ $tr($xq['done']['amount']) }} <small>{{ $xq['done']['orders'] }} đơn · {{ $n($xq['done']['cases']) }} ca</small></dd></div>
                    <div><dt>Hôm nay</dt><dd title="{{ $full($xq['today']['amount']) }}">{{ $tr($xq['today']['amount']) }} <small>{{ $xq['today']['orders'] }} đơn · {{ $n($xq['today']['cases']) }} ca</small></dd></div>
                    <div><dt>Lịch còn lại (từ mai đến cuối tháng)</dt><dd title="{{ $full($xq['upcoming']['amount']) }}">{{ $tr($xq['upcoming']['amount']) }} <small>{{ $xq['upcoming']['orders'] }} đơn · {{ $n($xq['upcoming']['cases']) }} ca</small></dd></div>
                    <div class="is-total"><dt>Dự kiến cả tháng</dt><dd title="{{ $full($xq['month_total']) }}">{{ $tr($xq['month_total']) }}</dd></div>
                    <div><dt>Cùng kỳ tháng trước (1 → {{ max(1, $today->day - 1) }}/{{ $today->copy()->subMonthNoOverflow()->month }})</dt><dd>{{ $tr($xq['prev_same']['amount']) }} <small>cả tháng {{ $tr($xq['prev_full']['amount']) }}</small></dd></div>
                </dl>
                <h3 class="dr-h3">Thu tiền</h3>
                <dl class="dr-list">
                    <div class="is-strong"><dt>Có thể thu về trong tháng</dt><dd title="{{ $full($xq['collectible']) }}">{{ $tr($xq['collectible']) }}</dd></div>
                    <div><dt>· Hóa đơn đến hạn đến cuối tháng</dt><dd>{{ $tr($xq['due_this_month']) }} <small>{{ $xq['due_this_month_count'] }} HĐ</small></dd></div>
                    <div><dt>· Nợ quá hạn {{ $tr($xq['overdue']) }}, giả định thu được {{ \App\Services\Finance\DirectorOverview::OVERDUE_COLLECT_PCT }}%</dt><dd>{{ $tr($xq['overdue_collect']) }}</dd></div>
                    <div><dt>Hóa đơn đến hạn 30 ngày tới</dt><dd>{{ $tr($xq['due_30']) }} <small>{{ $xq['due_30_count'] }} HĐ</small></dd></div>
                    @if ($xq['no_due_date'] > 0)
                        <div><dt>Đã xuất HĐ nhưng chưa có hạn thanh toán</dt><dd>{{ $tr($xq['no_due_date']) }}</dd></div>
                    @endif
                    <div class="{{ $xq['stuck'] > 0 ? 'is-warn' : '' }}"><dt>Chưa xuất hóa đơn <small>(chưa thu được)</small></dt><dd>{{ $tr($xq['uninvoiced']) }}
                        @if ($xq['stuck'] > 0)<small>{{ $tr($xq['stuck']) }} kẹt ≥ 7 ngày · {{ $xq['stuck_count'] }} đơn</small>@endif</dd></div>
                    @if ($xq['plan'])
                        <div><dt>Kế toán dự thu XQ (Báo cáo thu chi)</dt><dd>{{ $tr($xq['plan']['estimated']) }} <small>đã ghi thu {{ $tr($xq['plan']['actual']) }}</small></dd></div>
                    @endif
                </dl>
            </section>
        </div>

        {{-- Khoản phải chi --}}
        <section class="dr-card dr-card--pay">
            <div class="dr-card__head">
                <h2><i class="fas fa-file-invoice-dollar"></i> Khoản phải chi</h2>
                <a href="{{ route('reports.index') }}">Báo cáo thu chi <i class="fas fa-arrow-right"></i></a>
            </div>
            @php
                $groups = [
                    ['key' => 'late', 'title' => 'Quá ngày dự kiến, chưa ghi đã chi', 'total' => $pay['late_total'], 'cls' => 'is-bad', 'hint' => 'Đã chi mà kế toán chưa ghi vào Báo cáo thu chi, hoặc đang trễ hạn — cần kiểm tra.'],
                    ['key' => 'soon', 'title' => '30 ngày tới', 'total' => $pay['soon_total'], 'cls' => '', 'hint' => null],
                    ['key' => 'no_date', 'title' => 'Chưa có ngày chi', 'total' => $pay['no_date_total'], 'cls' => 'is-muted', 'hint' => 'Có số nhưng chưa có ngày (thường là theo thỏa thuận, chưa chốt).'],
                ];
            @endphp
            @foreach ($groups as $g)
                @continue($pay[$g['key']]->isEmpty())
                <details class="dr-pay {{ $g['cls'] }}" {{ $g['key'] !== 'no_date' ? 'open' : '' }}>
                    <summary><span>{{ $g['title'] }} <small>{{ $pay[$g['key']]->count() }} khoản</small></span><b title="{{ $full($g['total']) }}">{{ $tr($g['total']) }}</b></summary>
                    @if ($g['hint'])<p class="dr-foot">{{ $g['hint'] }}</p>@endif
                    <div class="dr-table-wrap">
                        <table class="dr-table">
                            <tbody>
                                @foreach ($pay[$g['key']] as $x)
                                    <tr>
                                        <td class="dr-date">{{ $x->date ? $days[$x->date->dayOfWeek] . ' ' . $x->date->format('d/m') : 'T' . $x->month }}</td>
                                        <td>{{ $x->name }}<small>{{ $x->group }}{{ $x->paid > 0 ? ' · đã chi ' . $tr($x->paid) : '' }}</small></td>
                                        <td class="dr-num" title="{{ $full($x->left) }}">{{ $tr($x->left) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endforeach
            @if ($pay['late']->isEmpty() && $pay['soon']->isEmpty() && $pay['no_date']->isEmpty())
                <p class="dr-empty">Báo cáo thu chi tháng này chưa có khoản chi nào còn phải trả.</p>
            @endif
            @if (!$pay['has_next_report'])
                <p class="dr-foot"><i class="fas fa-info-circle"></i> Chưa có Báo cáo thu chi tháng {{ $today->copy()->addMonthNoOverflow()->month }}: các khoản đầu tháng sau (lương, vay…) chưa có trong danh sách này, nhưng đã tính trong số dư dự kiến (lặp lại khoản định kỳ).</p>
            @endif
        </section>

        <div class="dr-grid">
            {{-- Khách nợ nhiều nhất --}}
            <section class="dr-card dr-card--debt">
                <div class="dr-card__head">
                    <h2><i class="fas fa-user-clock"></i> Khách nợ nhiều nhất</h2>
                    <a href="{{ route('finance.index', ['tab' => 'thu-no']) }}">Thu nợ <i class="fas fa-arrow-right"></i></a>
                </div>
                <p class="dr-foot">Nợ phải thu {{ $tr($o['receivables']['total']) }}, quá hạn {{ $tr($o['receivables']['overdue']) }} ({{ number_format($o['receivables']['overdue_pct'], 1, ',', '.') }}%)@if ($o['receivables']['dso']) · khách trả bình quân sau {{ $o['receivables']['dso'] }} ngày @endif</p>
                <div class="dr-table-wrap">
                    <table class="dr-table">
                        <thead><tr><th>Đơn vị</th><th class="dr-num">Đang nợ</th><th class="dr-num">Quá hạn</th></tr></thead>
                        <tbody>
                            @forelse ($o['debtors'] as $u)
                                @php $over = $u['total'] - $u['chua_den_han']; @endphp
                                <tr>
                                    <td>{{ $u['unit'] }}<small>{{ $u['count'] }} HĐ{{ $u['max_overdue'] > 0 ? ' · trễ lâu nhất ' . $u['max_overdue'] . ' ngày' : '' }}</small></td>
                                    <td class="dr-num" title="{{ $full($u['total']) }}">{{ $tr($u['total']) }}</td>
                                    <td class="dr-num {{ $over > 0 ? 'dr-bad' : '' }}" title="{{ $full($over) }}">{{ $over > 0 ? $tr($over) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="dr-empty">Không có khách nợ.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Lịch chụp 7 ngày tới --}}
            <section class="dr-card dr-card--cal">
                <div class="dr-card__head"><h2><i class="far fa-calendar-alt"></i> Lịch chụp 7 ngày tới</h2></div>
                <ul class="dr-days">
                    @foreach ($o['week'] as $d)
                        <li class="{{ $d['orders'] ? '' : 'is-empty' }}">
                            <span class="dr-days__d">{{ $d['date']->isToday() ? 'Hôm nay' : $days[$d['date']->dayOfWeek] . ' ' . $d['date']->format('d/m') }}</span>
                            <span class="dr-days__bar"><i style="width: {{ round($d['cases'] / $weekMax * 100) }}%"></i></span>
                            <span class="dr-days__n">{{ $d['orders'] ? $d['orders'] . ' đơn · ' . $n($d['cases']) . ' ca' : 'trống' }}</span>
                            @if ($d['units'])<small>{{ implode(', ', $d['units']) }}{{ $d['more'] ? ' +' . $d['more'] : '' }}</small>@endif
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Việc cần xử lý --}}
        <section class="dr-card dr-card--alert">
            <div class="dr-card__head"><h2><i class="fas fa-bell"></i> Việc cần chú ý <small>{{ count($o['alerts']) }}</small></h2></div>
            @forelse ($o['alerts'] as $a)
                <details class="dr-alert is-{{ $a['level'] }}">
                    <summary>
                        <i class="fas {{ $a['level'] === 'bad' ? 'fa-exclamation-circle' : ($a['level'] === 'warn' ? 'fa-exclamation-triangle' : 'fa-info-circle') }}"></i>
                        <span>{{ $a['title'] }}</span>
                    </summary>
                    <p>{{ $a['detail'] }}</p>
                    @if ($a['lines'])
                        <ul>@foreach ($a['lines'] as $l)<li>{{ $l }}</li>@endforeach</ul>
                    @endif
                    @if ($a['link'])<a class="dr-btn dr-btn--sm" href="{{ $a['link']['url'] }}">Mở {{ $a['link']['label'] }} <i class="fas fa-arrow-right"></i></a>@endif
                </details>
            @empty
                <p class="dr-empty">Không có cảnh báo nào.</p>
            @endforelse
        </section>

        <p class="dr-foot dr-foot--page">Số liệu dùng chung với Phân tích tài chính và Trợ lý Kế toán. Tiền đang có lấy từ Báo cáo thu chi tháng này (số dư đầu tháng + đã thu − đã chi): kế toán cập nhật báo cáo thì số mới đúng. Muốn hỏi sâu hơn, mở khung Trợ lý ở góc phải dưới.</p>
    </div>
@endsection
