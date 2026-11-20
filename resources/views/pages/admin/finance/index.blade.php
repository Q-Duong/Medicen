@extends('layouts.default_auth')
@section('title', 'Phân tích tài chính - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/report.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $m = fn ($v) => number_format((int) round((float) $v), 0, ',', '.');
        $tr = function ($v) {
            $v = (float) $v;
            $a = abs($v);
            return $a >= 1e9 ? number_format($v / 1e9, 2, ',', '.') . ' tỷ' : ($a >= 1e6 ? number_format($v / 1e6, 1, ',', '.') . ' tr' : number_format($v, 0, ',', '.'));
        };
        $pct = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.') . '%';
        $d = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';
    @endphp
    <div class="rp fn">
        <div class="rp-head">
            <div class="rp-head__title">
                <h1>Phân tích tài chính</h1>
                <p>Tiền đang kẹt ở đâu, đơn nào lãi / lỗ, tiền 13 tuần tới có đủ chi không · số liệu mới nhất trên web</p>
            </div>
            <div class="rp-head__actions">
                <a href="{{ route('assistant.show', 'ke_toan') }}" class="rp-btn"><i class="fas fa-user-tie"></i> Hỏi CFO AI</a>
            </div>
        </div>

        <nav class="bk-tabs">
            @foreach ($tabs as $k => $label)
                <a href="{{ route('finance.index', ['tab' => $k]) }}" class="{{ $tab === $k ? 'is-active' : '' }}">
                    <i class="fas {{ ['tien-ket' => 'fa-hourglass-half', 'thu-no' => 'fa-phone-volume', 'han-muc' => 'fa-shield-alt', 'duyet-gia' => 'fa-tags', 'lai-uoc-tinh' => 'fa-coins', 'dong-tien' => 'fa-water', 'ke-hoach' => 'fa-bullseye'][$k] }}"></i>{{ $label }}
                </a>
            @endforeach
        </nav>
        @include('pages.admin.ct._toast')

        {{-- ============================== THU NỢ ============================== --}}
        @if ($tab === 'thu-no')
            @php
                $cs = $collectAll->countBy('status');
                $chips = ['' => ['Tất cả', $collectAll->count()], 'cua-toi' => ['Của tôi', $collectAll->where('assignee_id', auth()->id())->count()],
                    'qua-hen' => ['Quá hẹn', $cs['qua_hen'] ?? 0], 'chua-phu-trach' => ['Chưa ai phụ trách', $cs['chua_phu_trach'] ?? 0], 'can-nhac' => ['Lâu chưa nhắc', $cs['can_nhac'] ?? 0]];
            @endphp
            <div class="rp-kpis">
                <section class="rp-card rp-kpi rp-card--debt">
                    <h2 class="rp-card__title"><i class="fas fa-hand-holding-usd"></i> Cần thu (quá hạn + tiền kẹt)</h2>
                    <p class="rp-kpi__big">{{ $m($collectAll->sum('overdue') + $collectAll->sum('stuck')) }}</p>
                    <p class="rp-kpi__note">{{ $collectAll->count() }} đơn vị · quá hạn {{ $tr($collectAll->sum('overdue')) }} · chưa xuất HĐ {{ $tr($collectAll->sum('stuck')) }}</p>
                </section>
                <section class="rp-card rp-kpi rp-card--neg">
                    <h2 class="rp-card__title"><i class="fas fa-calendar-times"></i> Quá ngày hẹn trả</h2>
                    <p class="rp-kpi__big is-neg">{{ $cs['qua_hen'] ?? 0 }} đơn vị</p>
                    <p class="rp-kpi__note">Khách đã hẹn nhưng chưa trả, cần nhắc lại</p>
                </section>
                <section class="rp-card rp-kpi fn-kpi--stuck">
                    <h2 class="rp-card__title"><i class="fas fa-user-slash"></i> Chưa ai phụ trách</h2>
                    <p class="rp-kpi__big">{{ $cs['chua_phu_trach'] ?? 0 }} đơn vị</p>
                    <p class="rp-kpi__note">Giao người phụ trách ở cột "Phụ trách"</p>
                </section>
            </div>

            <nav class="fn-chips">
                @foreach ($chips as $k => [$label, $n])
                    <a href="{{ route('finance.index', array_filter(['tab' => 'thu-no', 'loc' => $k])) }}" class="{{ $filter === $k ? 'is-active' : '' }}">{{ $label }} <b>{{ $n }}</b></a>
                @endforeach
            </nav>

            <section class="rp-card">
                <div class="rp-scroll">
                    <table class="bk-table fn-collect">
                        <thead>
                            <tr>
                                <th class="is-text">Đơn vị</th>
                                <th>Cần thu</th>
                                <th class="is-text">Phụ trách</th>
                                <th class="is-text">Lần nhắc gần nhất</th>
                                <th class="is-text">Hẹn trả</th>
                                <th class="is-text">Trạng thái</th>
                                @if ($canEdit)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($collect as $c)
                                <tr>
                                    <td class="is-text"><b>{{ $c['unit'] }}</b><div class="fn-sub">{{ $c['max_overdue_days'] ? 'quá hạn lâu nhất ' . $c['max_overdue_days'] . ' ngày' : 'chưa xuất hóa đơn' }}</div></td>
                                    <td><b>{{ $m($c['overdue'] + $c['stuck']) }}</b><div class="fn-sub">{{ $c['overdue'] ? 'QH ' . $tr($c['overdue']) : '' }}{{ $c['overdue'] && $c['stuck'] ? ' · ' : '' }}{{ $c['stuck'] ? 'kẹt ' . $tr($c['stuck']) : '' }}</div></td>
                                    <td class="is-text">
                                        @if ($canEdit)
                                            <form method="post" action="{{ route('finance.collect.assign') }}">
                                                @csrf
                                                <input type="hidden" name="unit_id" value="{{ $c['unit_id'] }}"><input type="hidden" name="loc" value="{{ $filter }}">
                                                <select name="assignee_id" class="fn-select" onchange="this.form.submit()">
                                                    <option value="">— Chưa giao —</option>
                                                    @foreach ($people->groupBy('group') as $g => $ps)
                                                        <optgroup label="{{ $g }}">@foreach ($ps as $p)<option value="{{ $p->id }}" @selected($c['assignee_id'] == $p->id)>{{ $p->name }}</option>@endforeach</optgroup>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            {{ $c['assignee'] ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="is-text is-wrap">
                                        @if ($c['last'])
                                            <b>{{ $c['last']['date']->format('d/m') }}</b> <span class="fn-sub">({{ $c['last']['days_ago'] }} ngày trước · {{ $c['last']['channel'] }}{{ $c['last']['by'] ? ' · ' . $c['last']['by'] : '' }})</span>
                                            @if ($c['last']['note'])<div class="fn-sub">{{ \Illuminate\Support\Str::limit($c['last']['note'], 90) }}</div>@endif
                                        @else
                                            <span class="fn-sub">Chưa nhắc lần nào</span>
                                        @endif
                                    </td>
                                    <td class="is-text">
                                        @if ($c['promise'])
                                            <b class="{{ $c['status'] === 'qua_hen' ? 'fn-bad' : '' }}">{{ $c['promise']['date']->format('d/m/Y') }}</b>
                                            @if ($c['promise']['amount'])<div class="fn-sub">{{ $m($c['promise']['amount']) }}</div>@endif
                                        @else
                                            <span class="fn-sub">—</span>
                                        @endif
                                    </td>
                                    <td class="is-text"><span class="fn-status fn-status--c-{{ $c['status'] }}">{{ $c['status_label'] }}</span></td>
                                    @if ($canEdit)
                                        <td class="is-text">
                                            <details class="fn-edit">
                                                <summary class="bk-edit" title="Ghi lần nhắc nợ"><i class="fas fa-phone"></i> Nhắc</summary>
                                                <form method="post" action="{{ route('finance.collect.contact') }}" class="fn-edit__form">
                                                    @csrf
                                                    <h3 class="fn-edit__title">Nhắc nợ · {{ $c['unit'] }} <small class="fn-sub">cần thu {{ $m($c['overdue'] + $c['stuck']) }}</small></h3>
                                                    <input type="hidden" name="unit_id" value="{{ $c['unit_id'] }}"><input type="hidden" name="loc" value="{{ $filter }}">
                                                    <label class="fn-f"><span>Ngày nhắc</span><input type="date" name="contacted_on" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
                                                    <label class="fn-f"><span>Kênh</span><select name="channel">@foreach (\App\Services\Accounting\DebtCollection::CHANNELS as $ck => $cl)<option value="{{ $ck }}">{{ $cl }}</option>@endforeach</select></label>
                                                    <label class="fn-f fn-f--full"><span>Nội dung / khách trả lời</span><input type="text" name="note" maxlength="2000" placeholder="Vd: kế toán BV hẹn trình ký tuần sau"></label>
                                                    <label class="fn-f"><span>Khách hẹn trả ngày</span><input type="date" name="promised_date"></label>
                                                    <label class="fn-f"><span>Số tiền hẹn</span><input type="text" name="promised_amount" class="is-money" inputmode="numeric" placeholder="để trống nếu trả hết"></label>
                                                    @if ($c['contacts'])
                                                        <div class="fn-hist">
                                                            <b>Đã nhắc {{ $c['contact_count'] }} lần</b>
                                                            @foreach ($c['contacts'] as $h)
                                                                <div>{{ $h['date']->format('d/m/Y') }} · {{ $h['channel'] }}{{ $h['by'] ? ' · ' . $h['by'] : '' }}{{ $h['note'] ? ': ' . $h['note'] : '' }}{{ $h['promised_date'] ? ' → hẹn ' . $h['promised_date']->format('d/m') . ($h['promised_amount'] ? ' (' . $m($h['promised_amount']) . ')' : '') : '' }}</div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    <div class="bk-actions"><button type="button" class="rp-btn" data-details-cancel>Hủy</button><button class="rp-btn rp-btn--primary" type="submit">Lưu lần nhắc</button></div>
                                                </form>
                                            </details>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="7" class="is-text">Không có đơn vị nào trong mục này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="bk-hint">Danh sách gồm các đơn vị có nợ quá hạn hoặc đơn đã chụp ≥ 7 ngày chưa xuất hóa đơn. "Lâu chưa nhắc" = quá {{ \App\Services\Accounting\DebtCollection::REMIND_DAYS }} ngày chưa nhắc. Người ghi lần nhắc đầu tiên tự thành người phụ trách nếu chưa giao.</p>
            </section>
        @endif

        {{-- ============================== DUYỆT GIÁ ============================== --}}
        @if ($tab === 'duyet-gia')
            @php
                $pending = $prices->where('approved', false);
                $pc = config('finance.price');
            @endphp
            <div class="rp-kpis">
                <section class="rp-card rp-kpi {{ $pending->count() ? 'rp-card--neg' : 'rp-card--thu' }}">
                    <h2 class="rp-card__title"><i class="fas fa-tags"></i> Chờ duyệt</h2>
                    <p class="rp-kpi__big {{ $pending->count() ? 'is-neg' : '' }}">{{ $pending->count() }} đơn</p>
                    <p class="rp-kpi__note">Lỗ ước tính {{ $tr(-$pending->where('profit', '<', 0)->sum('profit')) }} · {{ $pending->where('profit', '<', 0)->count() }} đơn lỗ</p>
                </section>
                <section class="rp-card rp-kpi rp-card--thu">
                    <h2 class="rp-card__title"><i class="fas fa-check-circle"></i> Đã duyệt</h2>
                    <p class="rp-kpi__big">{{ $prices->where('approved', true)->count() }} đơn</p>
                    <p class="rp-kpi__note">Đơn chụp từ 30 ngày trước đến 60 ngày tới</p>
                </section>
                <section class="rp-card rp-kpi">
                    <h2 class="rp-card__title"><i class="fas fa-ruler"></i> Quy tắc</h2>
                    <p class="rp-kpi__note" style="margin-top:2px">Cần duyệt khi: <b>lỗ ước tính</b>, <b>biên lãi &lt; {{ $pc['min_margin_pct'] }}%</b> hoặc <b>chiết khấu &gt; {{ $pc['max_discount_pct'] }}%</b>. Giá sàn / ca = chi phí chuyến ước tính / số ca + phí quản lý, VAT và biên {{ $pc['min_margin_pct'] }}%.</p>
                </section>
            </div>
            <section class="rp-card">
                <div class="rp-scroll">
                    <table class="bk-table fn-collect">
                        <thead>
                            <tr>
                                <th class="is-text">Đơn</th><th class="is-text">Đơn vị</th><th>Ngày chụp</th><th>Số ca · km</th>
                                <th>Giá / ca</th><th>Giá sàn / ca</th><th>Lãi ƯT</th><th class="is-text">Lý do</th><th class="is-text">Duyệt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prices as $p)
                                <tr>
                                    <td class="is-text"><a href="{{ route('order.edit', $p['order_id']) }}"><b>#{{ $p['order_id'] }}</b></a><div class="fn-sub">{{ $p['car'] ?? 'chưa xếp xe' }}</div></td>
                                    <td class="is-text is-wrap"><b>{{ $p['unit'] }}</b><div class="fn-sub">{{ \Illuminate\Support\Str::limit($p['company'], 50) }}</div></td>
                                    <td>{{ $d($p['date']) }}</td>
                                    <td>{{ $m($p['qty']) }}<div class="fn-sub">{{ $p['km'] ? $p['km'] . ' km' : 'chưa có km' }}</div></td>
                                    <td><b class="{{ $p['floor_per_case'] && $p['per_case'] < $p['floor_per_case'] ? 'fn-bad' : '' }}">{{ $p['per_case'] ? $m($p['per_case']) : '—' }}</b></td>
                                    <td>{{ $p['floor_per_case'] ? $m($p['floor_per_case']) : '—' }}</td>
                                    <td class="{{ $p['profit'] < 0 ? 'fn-bad' : '' }}">{{ $m($p['profit']) }}<div class="fn-sub">biên {{ $pct($p['margin']) }}</div></td>
                                    <td class="is-text is-wrap">{{ implode(' · ', $p['flags']) }}</td>
                                    <td class="is-text">
                                        @if ($p['approved'])
                                            <span class="fn-status fn-status--ok">Đã duyệt</span>
                                            <div class="fn-sub">{{ $p['approved_by'] }} · {{ \Carbon\Carbon::parse($p['approved_at'])->format('d/m') }}{{ $p['approval_note'] ? ' · ' . $p['approval_note'] : '' }}</div>
                                            @if ($canApprove)
                                                <form method="post" action="{{ route('finance.price.approve') }}" data-confirm="Bỏ duyệt giá đơn #{{ $p['order_id'] }}?">@csrf<input type="hidden" name="order_id" value="{{ $p['order_id'] }}"><input type="hidden" name="undo" value="1"><button class="fn-link" type="submit">Bỏ duyệt</button></form>
                                            @endif
                                        @elseif ($canApprove)
                                            <details class="fn-edit">
                                                <summary class="bk-edit"><i class="fas fa-check"></i> Duyệt</summary>
                                                <form method="post" action="{{ route('finance.price.approve') }}" class="fn-edit__form">
                                                    @csrf
                                                    <h3 class="fn-edit__title">Duyệt giá đơn #{{ $p['order_id'] }} · {{ $p['unit'] }}</h3>
                                                    <p class="bk-hint" style="flex-basis:100%;margin:0">{{ implode(' · ', $p['flags']) }}. Giá {{ $p['per_case'] ? $m($p['per_case']) . ' đ/ca' : '—' }}, giá sàn {{ $p['floor_per_case'] ? $m($p['floor_per_case']) . ' đ/ca' : '—' }}.</p>
                                                    <input type="hidden" name="order_id" value="{{ $p['order_id'] }}">
                                                    <label class="fn-f fn-f--full"><span>Lý do chấp nhận (tùy chọn)</span><input type="text" name="note" maxlength="255" placeholder="Vd: khách lớn, ghép chuyến với đơn khác"></label>
                                                    <div class="bk-actions"><button type="button" class="rp-btn" data-details-cancel>Hủy</button><button class="rp-btn rp-btn--primary" type="submit">Duyệt</button></div>
                                                </form>
                                            </details>
                                        @else
                                            <span class="fn-status fn-status--c-chua_phu_trach">Chờ kế toán duyệt</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="is-text">Không có đơn nào cần duyệt giá.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="bk-hint">Chi phí là ước tính theo đơn giá mặc định (Bảng tính hiệu quả kinh doanh), chưa gồm khấu hao xe, lãi vay; đơn chưa có số ca không kiểm tra được. Khi có hợp đồng từng đơn vị sẽ so thêm với giá đã ký.</p>
            </section>
        @endif

        {{-- ============================== HẠN MỨC TÍN DỤNG ============================== --}}
        @if ($tab === 'han-muc')
            @php
                $cnt = $credits->countBy('status');
            @endphp
            <div class="rp-kpis">
                <section class="rp-card rp-kpi rp-card--neg">
                    <h2 class="rp-card__title"><i class="fas fa-ban"></i> Vượt hạn mức</h2>
                    <p class="rp-kpi__big is-neg">{{ $cnt['vuot_han_muc'] ?? 0 }} đơn vị</p>
                    <p class="rp-kpi__note">Nợ {{ $m($credits->where('status', 'vuot_han_muc')->sum('owe')) }} · Sales nên thu nợ trước khi nhận lịch mới</p>
                </section>
                <section class="rp-card rp-kpi fn-kpi--stuck">
                    <h2 class="rp-card__title"><i class="fas fa-clock"></i> Quá hạn quá lâu</h2>
                    <p class="rp-kpi__big">{{ $cnt['qua_han_lau'] ?? 0 }} đơn vị</p>
                    <p class="rp-kpi__note">Có hóa đơn quá hạn lâu hơn số ngày cho phép</p>
                </section>
                <section class="rp-card rp-kpi">
                    <h2 class="rp-card__title"><i class="fas fa-shield-alt"></i> Cách đặt hạn mức</h2>
                    <p class="rp-kpi__note" style="margin-top:2px">Chưa đặt tay thì dùng <b>gợi ý</b> = 2,5 tháng doanh thu bình quân 6 tháng gần nhất (tối thiểu 20 triệu), quá hạn tối đa 30 ngày. Nợ tính cả đơn chưa xuất hóa đơn.</p>
                </section>
            </div>
            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-building"></i> Hạn mức từng đơn vị ({{ $credits->count() }})</h2>
                <div class="rp-scroll">
                    <table class="bk-table fn-credit">
                        <thead>
                            <tr>
                                <th class="is-text">Đơn vị</th>
                                <th>Đang nợ</th>
                                <th>Hạn mức</th>
                                <th class="is-text">Đã dùng</th>
                                <th>Quá hạn lâu nhất</th>
                                <th class="is-text">Trạng thái</th>
                                @if ($canEdit)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($credits as $c)
                                <tr>
                                    <td class="is-text is-wrap"><b>{{ $c['unit'] }}</b><div class="fn-sub">DT bq {{ $tr($c['avg_month_revenue']) }}/tháng{{ $c['note'] ? ' · ' . $c['note'] : '' }}</div></td>
                                    <td><b>{{ $m($c['owe']) }}</b>@if ($c['uninvoiced'])<div class="fn-sub">chưa xuất HĐ {{ $tr($c['uninvoiced']) }}</div>@endif</td>
                                    <td>{{ $m($c['limit']) }}<div class="fn-sub">{{ $c['is_manual'] ? 'đặt tay' : 'gợi ý' }}</div></td>
                                    <td class="is-text">
                                        <span class="fn-meter fn-meter--{{ $c['status'] }}"><i style="width: {{ min(100, (int) ($c['used_pct'] ?? 0)) }}%"></i></span>
                                        <span class="fn-sub">{{ $c['used_pct'] !== null ? number_format($c['used_pct'], 0, ',', '.') . '%' : '—' }}</span>
                                    </td>
                                    <td class="{{ $c['max_overdue_days'] > $c['max_days'] ? 'fn-bad' : '' }}">{{ $c['max_overdue_days'] ? $c['max_overdue_days'] . ' ngày' : '—' }}<div class="fn-sub">tối đa {{ $c['max_days'] }} ngày</div></td>
                                    <td class="is-text"><span class="fn-status fn-status--{{ $c['status'] }}">{{ $c['status_label'] }}</span></td>
                                    @if ($canEdit)
                                        <td class="is-text">
                                            <details class="fn-edit">
                                                <summary class="bk-edit"><i class="fas fa-pen"></i> Sửa</summary>
                                                <form method="post" action="{{ route('finance.credit.save') }}" class="fn-edit__form">
                                                    @csrf
                                                    <h3 class="fn-edit__title">Hạn mức tín dụng · {{ $c['unit'] }}</h3>
                                                    <input type="hidden" name="unit_id" value="{{ $c['unit_id'] }}">
                                                    <label class="fn-f"><span>Hạn mức nợ <em>trống = gợi ý {{ $tr($c['suggested_limit']) }}</em></span>
                                                        <input type="text" name="limit_amount" class="is-money" inputmode="numeric" value="{{ $c['is_manual'] && $c['limit'] !== $c['suggested_limit'] ? $m($c['limit']) : '' }}" placeholder="{{ $m($c['suggested_limit']) }}"></label>
                                                    <label class="fn-f"><span>Quá hạn tối đa (ngày)</span><input type="number" name="max_overdue_days" min="0" max="365" value="{{ $c['is_manual'] && $c['max_days'] !== \App\Services\Accounting\CreditPolicy::DEFAULT_DAYS ? $c['max_days'] : '' }}" placeholder="{{ \App\Services\Accounting\CreditPolicy::DEFAULT_DAYS }}"></label>
                                                    <label class="fn-f fn-f--full"><span>Ghi chú</span><input type="text" name="note" maxlength="255" value="{{ $c['note'] }}" placeholder="Vd: theo hợp đồng số…"></label>
                                                    <div class="bk-actions"><button type="button" class="rp-btn" data-details-cancel>Hủy</button><button class="rp-btn rp-btn--primary" type="submit">Lưu</button></div>
                                                </form>
                                            </details>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- ============================== KẾ HOẠCH NĂM ============================== --}}
        @if ($tab === 'ke-hoach')
            @php
                $T = $cmp['totals'];
                $upTo = $cmp['up_to_month'];
            @endphp
            <form method="get" class="rp-card fn-filter">
                <input type="hidden" name="tab" value="ke-hoach">
                <label class="fn-f"><span>Năm kế hoạch</span>
                    <select name="nam" onchange="this.form.submit()">
                        @for ($y = now()->year + 2; $y >= 2024; $y--)<option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>@endfor
                    </select>
                </label>
                @if ($canEdit)
                    <label class="fn-f"><span>Tăng trưởng so với {{ $year - 1 }}</span><span class="fn-f__unit"><input type="number" name="goi_y" step="1" min="-50" max="200" value="{{ $suggestPct ?? 10 }}"><i>%</i></span></label>
                    <button class="rp-btn" type="submit"><i class="fas fa-magic"></i> Gợi ý từ thực tế {{ $year - 1 }}</button>
                @endif
                <span class="bk-hint">Thực tế: doanh thu & số ca từ đơn hàng, CT từ Công nợ CT, các khoản chi lấy số <b>đã chi</b> trong Báo cáo thu chi. Lũy kế tính đến hết tháng {{ $upTo ?: '—' }}.</span>
            </form>

            @if ($suggestPct !== null)
                <div class="rp-card fn-note">
                    <i class="fas fa-magic"></i> Bảng nhập bên dưới đang điền <b>gợi ý</b> = thực tế từng tháng năm {{ $year - 1 }} {{ $suggestPct >= 0 ? '+' : '' }}{{ $suggestPct }}% (tháng chưa có số dùng bình quân). Chưa lưu — xem lại, sửa rồi bấm <b>Lưu kế hoạch</b>.
                </div>
            @endif

            @if ($cmp['has_plan'])
                <div class="rp-kpis">
                    <section class="rp-card rp-kpi rp-card--thu">
                        <h2 class="rp-card__title"><i class="fas fa-arrow-down"></i> Thu lũy kế {{ $upTo ? 'đến T' . $upTo : '' }}</h2>
                        <p class="rp-kpi__big">{{ $T['thu_plan_ytd'] ? number_format($T['thu_actual_ytd'] / $T['thu_plan_ytd'] * 100, 1, ',', '.') . '%' : '—' }}</p>
                        <p class="rp-kpi__note">Thực tế {{ $tr($T['thu_actual_ytd']) }} / kế hoạch {{ $tr($T['thu_plan_ytd']) }} · cả năm {{ $tr($T['thu_plan_year']) }}</p>
                    </section>
                    <section class="rp-card rp-kpi {{ $T['chi_actual_ytd'] > $T['chi_plan_ytd'] ? 'rp-card--neg' : '' }}">
                        <h2 class="rp-card__title"><i class="fas fa-arrow-up"></i> Chi lũy kế {{ $upTo ? 'đến T' . $upTo : '' }}</h2>
                        <p class="rp-kpi__big {{ $T['chi_actual_ytd'] > $T['chi_plan_ytd'] ? 'is-neg' : '' }}">{{ $T['chi_plan_ytd'] ? number_format($T['chi_actual_ytd'] / $T['chi_plan_ytd'] * 100, 1, ',', '.') . '%' : '—' }}</p>
                        <p class="rp-kpi__note">Thực tế {{ $tr($T['chi_actual_ytd']) }} / kế hoạch {{ $tr($T['chi_plan_ytd']) }} · cả năm {{ $tr($T['chi_plan_year']) }}</p>
                    </section>
                    <section class="rp-card rp-kpi">
                        <h2 class="rp-card__title"><i class="fas fa-balance-scale"></i> Kế hoạch thu − chi cả năm</h2>
                        <p class="rp-kpi__big {{ $T['thu_plan_year'] - $T['chi_plan_year'] < 0 ? 'is-neg' : 'is-pos' }}">{{ $m($T['thu_plan_year'] - $T['chi_plan_year']) }}</p>
                        <p class="rp-kpi__note">Chưa gồm khoản vay mới / thu khác ngoài các dòng kế hoạch</p>
                    </section>
                </div>

                <section class="rp-card">
                    <h2 class="rp-card__title"><i class="fas fa-chart-bar"></i> Doanh thu (XQ + CT) theo tháng: kế hoạch và thực tế</h2>
                    <div class="fn-chart"><canvas id="fn-budget-chart"></canvas></div>
                </section>

                <section class="rp-card">
                    <h2 class="rp-card__title"><i class="fas fa-table"></i> So với kế hoạch</h2>
                    <div class="rp-scroll">
                        <table class="bk-table">
                            <thead><tr><th class="is-text">Khoản</th><th>KH cả năm</th><th>KH lũy kế</th><th>Thực tế lũy kế</th><th>% đạt</th><th>Chênh lệch</th><th>Ước cả năm</th></tr></thead>
                            <tbody>
                                @foreach ($cmp['lines'] as $k => $l)
                                    @php
                                        $bad = $l['pct_ytd'] !== null && ($l['type'] === 'chi' ? $l['pct_ytd'] > 105 : $l['pct_ytd'] < 95);
                                    @endphp
                                    <tr>
                                        <td class="is-text"><b>{{ $l['label'] }}</b> <span class="fn-sub">{{ ['thu' => 'thu', 'chi' => 'chi', 'so' => 'ca'][$l['type']] }}</span></td>
                                        <td>{{ $m($l['plan_year']) }}</td>
                                        <td>{{ $m($l['plan_ytd']) }}</td>
                                        <td>{{ $m($l['actual_ytd']) }}</td>
                                        <td class="{{ $bad ? 'fn-bad' : 'fn-in' }}"><b>{{ $pct($l['pct_ytd']) }}</b></td>
                                        <td class="{{ ($l['type'] === 'chi' ? $l['diff_ytd'] > 0 : $l['diff_ytd'] < 0) ? 'fn-bad' : '' }}">{{ $l['diff_ytd'] > 0 ? '+' : '' }}{{ $m($l['diff_ytd']) }}</td>
                                        <td>{{ $m($l['forecast_year']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                @php
                    $rev = fn ($src) => array_map(fn ($mm) => ($cmp['lines']['dt_xq'][$src][$mm] ?? 0) + ($cmp['lines']['dt_ct'][$src][$mm] ?? 0), range(1, 12));
                    $budgetChart = [
                        'labels' => array_map(fn ($mm) => 'T' . $mm, range(1, 12)),
                        'plan' => $rev('plan'),
                        'actual' => array_map(fn ($v, $i) => $i < $upTo ? $v : null, $rev('actual'), range(0, 11)),
                    ];
                @endphp
                <script type="application/json" id="fn-budget-data">@json($budgetChart)</script>
            @elseif (!$canEdit)
                <section class="rp-card"><div class="rp-empty"><i class="fas fa-bullseye"></i><h2>Chưa có kế hoạch năm {{ $year }}</h2><p>Kế toán / giám đốc nhập kế hoạch để so với thực tế.</p></div></section>
            @endif

            @if ($canEdit)
                <form method="post" action="{{ route('finance.budget.save') }}" class="rp-card fn-budget">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <h2 class="rp-card__title"><i class="fas fa-pen"></i> Nhập kế hoạch năm {{ $year }} <small class="fn-sub">tiền (đ) theo tháng; số ca là số lượng</small></h2>
                    <div class="rp-scroll">
                        <table class="bk-table fn-budget__grid">
                            <thead><tr><th class="is-text">Khoản</th>@for ($mm = 1; $mm <= 12; $mm++)<th>T{{ $mm }}</th>@endfor<th>Cả năm</th></tr></thead>
                            <tbody>
                                @foreach (\App\Services\Accounting\Budget::LINES as $k => [$label, $type])
                                    <tr data-row>
                                        <td class="is-text"><b>{{ $label }}</b></td>
                                        @for ($mm = 1; $mm <= 12; $mm++)
                                            <td><input type="text" name="plan[{{ $k }}][{{ $mm }}]" class="{{ $type === 'so' ? '' : 'is-money' }}" inputmode="numeric" value="{{ $grid[$k][$mm] ? number_format($grid[$k][$mm], 0, ',', '.') : '' }}"></td>
                                        @endfor
                                        <td class="fn-budget__sum" data-sum>{{ $m(array_sum($grid[$k])) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="bk-actions" style="justify-content:flex-end;display:flex;gap:8px;margin-top:12px">
                        <a href="{{ route('finance.index', ['tab' => 'ke-hoach', 'nam' => $year]) }}" class="rp-btn">Hủy thay đổi</a>
                        <button type="submit" class="rp-btn rp-btn--primary"><i class="fas fa-save"></i> Lưu kế hoạch</button>
                    </div>
                </form>
            @endif
        @endif

        {{-- ============================== TIỀN KẸT & TUỔI NỢ ============================== --}}
        @if ($tab === 'tien-ket')
            <div class="rp-kpis">
                <section class="rp-card rp-kpi rp-card--debt">
                    <h2 class="rp-card__title"><i class="fas fa-file-invoice-dollar"></i> Tổng phải thu XQ</h2>
                    <p class="rp-kpi__big">{{ $m($r['total']) }}</p>
                    <p class="rp-kpi__note">Số ngày thu tiền bình quân 6 tháng: <b>{{ $r['dso'] !== null ? $r['dso'] . ' ngày' : 'chưa đủ dữ liệu' }}</b></p>
                </section>
                <section class="rp-card rp-kpi rp-card--neg">
                    <h2 class="rp-card__title"><i class="fas fa-exclamation-triangle"></i> Đã quá hạn</h2>
                    <p class="rp-kpi__big is-neg">{{ $m($r['overdue']) }}</p>
                    <p class="rp-kpi__note">{{ $pct($r['overdue_pct']) }} tổng phải thu · {{ $r['overdue_rows']->count() }} hóa đơn</p>
                </section>
                <section class="rp-card rp-kpi fn-kpi--stuck">
                    <h2 class="rp-card__title"><i class="fas fa-hourglass-half"></i> Tiền kẹt: chưa xuất hóa đơn</h2>
                    <p class="rp-kpi__big">{{ $m($r['stuck_total']) }}</p>
                    <p class="rp-kpi__note">{{ $r['stuck']->count() }} đơn đã chụp xong ≥ {{ \App\Services\Accounting\Receivables::STUCK_DAYS }} ngày · tổng chưa xuất HĐ {{ $m($r['uninvoiced']) }}</p>
                </section>
            </div>

            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-layer-group"></i> Phân tuổi nợ</h2>
                <div class="fn-aging">
                    @php($max = max(1, $r['buckets']->max('amount')))
                    @foreach ($r['buckets'] as $b)
                        <div class="fn-aging__row fn-aging__row--{{ $b['key'] }}">
                            <span class="fn-aging__label">{{ $b['label'] }}</span>
                            <span class="fn-aging__bar"><i style="width: {{ max(1, round($b['amount'] / $max * 100)) }}%"></i></span>
                            <span class="fn-aging__amt">{{ $m($b['amount']) }}</span>
                            <span class="fn-aging__cnt">{{ $b['count'] }} đơn</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-building"></i> Theo đơn vị <small class="fn-sub">xếp theo số tiền đã quá hạn + chưa xuất HĐ</small></h2>
                <div class="rp-scroll">
                    <table class="bk-table">
                        <thead>
                            <tr>
                                <th class="is-text">Đơn vị</th>
                                <th>Tổng nợ</th>
                                <th>Chưa đến hạn</th>
                                <th>1–30</th>
                                <th>31–60</th>
                                <th>61–90</th>
                                <th>&gt; 90</th>
                                <th>Chưa xuất HĐ</th>
                                <th>Quá hạn lâu nhất</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($r['by_unit']->take(40) as $u)
                                <tr>
                                    <td class="is-text"><b>{{ $u['unit'] }}</b> <span class="fn-sub">{{ $u['count'] }} đơn</span></td>
                                    <td><b>{{ $m($u['total']) }}</b></td>
                                    <td>{{ $u['chua_den_han'] ? $m($u['chua_den_han']) : '' }}</td>
                                    <td class="fn-warn">{{ $u['qh_1_30'] ? $m($u['qh_1_30']) : '' }}</td>
                                    <td class="fn-bad">{{ $u['qh_31_60'] ? $m($u['qh_31_60']) : '' }}</td>
                                    <td class="fn-bad">{{ $u['qh_61_90'] ? $m($u['qh_61_90']) : '' }}</td>
                                    <td class="fn-bad"><b>{{ $u['qh_tren_90'] ? $m($u['qh_tren_90']) : '' }}</b></td>
                                    <td class="fn-stuck">{{ $u['chua_xuat_hd'] ? $m($u['chua_xuat_hd']) : '' }}</td>
                                    <td>{{ $u['max_overdue'] ? $u['max_overdue'] . ' ngày' : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="is-text">Không có công nợ.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="fn-two">
                <section class="rp-card">
                    <h2 class="rp-card__title"><i class="fas fa-hourglass-half"></i> Đơn chưa xuất hóa đơn ({{ $r['stuck']->count() }})</h2>
                    <p class="bk-hint">Đã chụp xong từ {{ \App\Services\Accounting\Receivables::STUCK_DAYS }} ngày trở lên. Chưa có hóa đơn thì khách chưa có nghĩa vụ trả và chưa tính được hạn.</p>
                    <div class="rp-scroll fn-list">
                        <table class="bk-table">
                            <thead><tr><th class="is-text">Đơn</th><th class="is-text">Đơn vị</th><th>Ngày chụp</th><th>Số ngày</th><th>Còn nợ</th></tr></thead>
                            <tbody>
                                @forelse ($r['stuck']->take(60) as $x)
                                    <tr>
                                        <td class="is-text">#{{ $x->id }}</td>
                                        <td class="is-text is-wrap">{{ $x->unit }}<div class="fn-sub">{{ \Illuminate\Support\Str::limit($x->company, 60) }}</div></td>
                                        <td>{{ $x->shot?->format('d/m/Y') }}</td>
                                        <td class="{{ $x->days_since_shot > 30 ? 'fn-bad' : 'fn-warn' }}">{{ $x->days_since_shot }}</td>
                                        <td><b>{{ $m($x->owe) }}</b></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="is-text">Không có đơn nào kẹt.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="rp-card">
                    <h2 class="rp-card__title"><i class="fas fa-exclamation-triangle"></i> Hóa đơn quá hạn ({{ $r['overdue_rows']->count() }})</h2>
                    <p class="bk-hint">Quá hạn lâu nhất trước. Gợi ý: hỏi CFO AI "soạn tin nhắc nợ cho …" để có tin nhắn gửi khách.</p>
                    <div class="rp-scroll fn-list">
                        <table class="bk-table">
                            <thead><tr><th class="is-text">Đơn vị</th><th class="is-text">Số HĐ</th><th>Hạn TT</th><th>Quá hạn</th><th>Còn nợ</th></tr></thead>
                            <tbody>
                                @forelse ($r['overdue_rows']->take(60) as $x)
                                    <tr>
                                        <td class="is-text is-wrap">{{ $x->unit }} <span class="fn-sub">#{{ $x->id }}</span></td>
                                        <td class="is-text">{{ $x->invoice_no }}</td>
                                        <td>{{ $x->due?->format('d/m/Y') }}</td>
                                        <td class="fn-bad">{{ $x->overdue_days }} ngày</td>
                                        <td><b>{{ $m($x->owe) }}</b></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="is-text">Không có hóa đơn quá hạn.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endif

        {{-- ============================== LÃI ƯỚC TÍNH ============================== --}}
        @if ($tab === 'lai-uoc-tinh')
            @php($t = $a['tong'])
            <form method="get" class="rp-card fn-filter">
                <input type="hidden" name="tab" value="lai-uoc-tinh">
                <label class="fn-f"><span>Từ ngày</span><input type="date" name="tu" value="{{ $from->toDateString() }}"></label>
                <label class="fn-f"><span>Đến ngày</span><input type="date" name="den" value="{{ $to->toDateString() }}"></label>
                <button class="rp-btn rp-btn--primary" type="submit"><i class="fas fa-sync-alt"></i> Xem</button>
                <span class="bk-hint">Chi phí từng đơn là <b>ước tính</b> theo đơn giá mặc định của Bảng tính hiệu quả (vật tư, KTV, tài xế, dầu khứ hồi, khách sạn, phụ cấp, phí quản lý 3,2%, VAT). Chưa gồm khấu hao xe, lãi vay, lương văn phòng.</span>
            </form>
            <div class="rp-kpis">
                <section class="rp-card rp-kpi rp-card--thu">
                    <h2 class="rp-card__title"><i class="fas fa-hand-holding-usd"></i> Doanh thu thuần</h2>
                    <p class="rp-kpi__big">{{ $m($t['doanh_thu_thuan']) }}</p>
                    <p class="rp-kpi__note">{{ $t['so_don'] }} đơn · {{ $m($t['so_ca']) }} ca · giá bq {{ $m($t['gia_binh_quan_ca']) }} đ/ca · so kỳ trước {{ $a['thay_doi_so_voi_ky_truoc']['doanh_thu_thuan'] ?? '—' }}</p>
                </section>
                <section class="rp-card rp-kpi">
                    <h2 class="rp-card__title"><i class="fas fa-receipt"></i> Chi phí ước tính</h2>
                    <p class="rp-kpi__big">{{ $m($t['chi_phi_uoc_tinh']) }}</p>
                    <p class="rp-kpi__note">Theo từng chuyến đi</p>
                </section>
                <section class="rp-card rp-kpi {{ $t['lai_uoc_tinh'] >= 0 ? 'rp-card--thu' : 'rp-card--neg' }}">
                    <h2 class="rp-card__title"><i class="fas fa-coins"></i> Lãi ước tính</h2>
                    <p class="rp-kpi__big {{ $t['lai_uoc_tinh'] < 0 ? 'is-neg' : 'is-pos' }}">{{ $m($t['lai_uoc_tinh']) }}</p>
                    <p class="rp-kpi__note">Biên {{ $pct($t['bien_lai_uoc_tinh_pct']) }} · {{ $t['so_don_lo_uoc_tinh'] }} đơn lỗ</p>
                </section>
            </div>

            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-chart-bar"></i> Theo tháng</h2>
                <div class="fn-chart"><canvas id="fn-month-chart"></canvas></div>
            </section>

            <div class="fn-two">
                <section class="rp-card">
                    <h2 class="rp-card__title"><i class="fas fa-building"></i> Khách hàng (top 15 doanh thu)</h2>
                    <div class="rp-scroll">
                        <table class="bk-table">
                            <thead><tr><th class="is-text">Đơn vị</th><th>Doanh thu</th><th>Tỷ trọng</th><th>Giá bq/ca</th><th>Lãi ước tính</th><th>Biên</th></tr></thead>
                            <tbody>
                                @foreach ($a['khach_hang']['top'] as $c)
                                    <tr>
                                        <td class="is-text"><b>{{ $c['don_vi'] }}</b> <span class="fn-sub">{{ $c['so_don'] }} đơn</span></td>
                                        <td>{{ $m($c['doanh_thu']) }}</td>
                                        <td>{{ $pct($c['ty_trong']) }}</td>
                                        <td>{{ $c['gia_binh_quan_ca'] ? $m($c['gia_binh_quan_ca']) : '—' }}</td>
                                        <td class="{{ $c['lai_uoc_tinh'] < 0 ? 'fn-bad' : '' }}">{{ $m($c['lai_uoc_tinh']) }}</td>
                                        <td class="{{ ($c['bien_lai_pct'] ?? 0) < 10 ? 'fn-bad' : '' }}">{{ $pct($c['bien_lai_pct']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="bk-hint">Top 1 chiếm {{ $pct($a['khach_hang']['tap_trung']['top1_pct']) }}, top 5 chiếm {{ $pct($a['khach_hang']['tap_trung']['top5_pct']) }} doanh thu.</p>
                </section>
                <section class="rp-card">
                    <h2 class="rp-card__title"><i class="fas fa-truck"></i> Theo xe</h2>
                    <div class="rp-scroll">
                        <table class="bk-table">
                            <thead><tr><th class="is-text">Xe</th><th>Ngày chạy</th><th>Ca / ngày</th><th>Doanh thu</th><th>Lãi ước tính</th><th>Lãi / ngày</th></tr></thead>
                            <tbody>
                                @foreach ($a['theo_xe'] as $x)
                                    <tr>
                                        <td class="is-text"><b>{{ $x['xe'] }}</b></td>
                                        <td>{{ $x['so_ngay_chay'] }}</td>
                                        <td>{{ $x['ca_binh_quan_ngay'] }}</td>
                                        <td>{{ $m($x['doanh_thu']) }}</td>
                                        <td class="{{ $x['lai_uoc_tinh'] < 0 ? 'fn-bad' : '' }}">{{ $m($x['lai_uoc_tinh']) }}</td>
                                        <td>{{ $x['lai_binh_quan_ngay'] !== null ? $m($x['lai_binh_quan_ngay']) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-arrow-down"></i> 15 đơn lãi ước tính thấp nhất</h2>
                <p class="bk-hint">Xem lại giá, số ca tối thiểu hoặc phụ thu khoảng cách cho các đơn vị này.</p>
                <div class="rp-scroll">
                    <table class="bk-table">
                        <thead><tr><th class="is-text">Đơn</th><th class="is-text">Đơn vị</th><th>Ngày</th><th>Số ca</th><th>Km khứ hồi</th><th>Doanh thu</th><th>Chi phí ƯT</th><th>Lãi ƯT</th><th>Biên</th></tr></thead>
                        <tbody>
                            @forelse ($a['don_lai_thap'] as $o)
                                <tr>
                                    <td class="is-text">#{{ $o['ma_don'] }} <span class="fn-sub">{{ $o['xe'] ?? '' }}</span></td>
                                    <td class="is-text">{{ $o['don_vi'] }}</td>
                                    <td>{{ $d($o['ngay']) }}</td>
                                    <td>{{ $m($o['so_ca']) }}</td>
                                    <td>{{ $o['km_khu_hoi'] ?: '—' }}</td>
                                    <td>{{ $m($o['doanh_thu']) }}</td>
                                    <td>{{ $m($o['chi_phi_uoc_tinh']) }}</td>
                                    <td class="{{ $o['lai_uoc_tinh'] < 0 ? 'fn-bad' : '' }}"><b>{{ $m($o['lai_uoc_tinh']) }}</b></td>
                                    <td>{{ $pct($o['bien_lai_pct']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="is-text">Không có đơn trong kỳ.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            @php($chartMonth = ['labels' => array_map(fn ($r) => substr($r['thang'], 5) . '/' . substr($r['thang'], 2, 2), $a['theo_thang']),
                'revenue' => array_column($a['theo_thang'], 'doanh_thu_thuan'), 'profit' => array_column($a['theo_thang'], 'lai_uoc_tinh'),
                'margin' => array_column($a['theo_thang'], 'bien_lai_pct')])
            <script type="application/json" id="fn-month-data">@json($chartMonth)</script>
        @endif

        {{-- ============================== DÒNG TIỀN 13 TUẦN ============================== --}}
        @if ($tab === 'dong-tien')
            <form method="get" class="rp-card fn-filter">
                <input type="hidden" name="tab" value="dong-tien">
                <label class="fn-f fn-f--wide"><span>Tiền hiện có <em>để trống = tự tính</em></span><input type="text" name="so_du" class="is-money" inputmode="numeric" value="{{ $opt['so_du'] }}" placeholder="{{ $m($f['tien_dau_ky']) }}"></label>
                <label class="fn-f"><span title="Phần trăm nợ quá hạn dự kiến thu được trong 4 tuần đầu">Thu nợ quá hạn</span><span class="fn-f__unit"><input type="number" name="qua_han" min="0" max="100" value="{{ $f['gia_dinh']['thu_no_qua_han_pct'] }}"><i>%</i></span></label>
                <label class="fn-f"><span title="Phần trăm đơn chưa xuất hóa đơn dự kiến thu được">Thu đơn chưa xuất HĐ</span><span class="fn-f__unit"><input type="number" name="chua_hd" min="0" max="100" value="{{ $f['gia_dinh']['thu_chua_xuat_hd_pct'] }}"><i>%</i></span></label>
                <label class="fn-f"><span>Ngưỡng cảnh báo</span><span class="fn-f__unit"><input type="text" name="nguong" class="is-money" inputmode="numeric" value="{{ $m($f['nguong_canh_bao']) }}"><i>đ</i></span></label>
                <label class="fn-f"><span title="Cộng thêm doanh thu dự kiến từ đơn mới (bình quân 3 tháng)">Doanh thu đơn mới</span>
                    <select name="don_moi"><option value="1" @selected($opt['don_moi'])>Có tính</option><option value="0" @selected(!$opt['don_moi'])>Không tính</option></select>
                </label>
                <button class="rp-btn rp-btn--primary" type="submit"><i class="fas fa-sync-alt"></i> Tính lại</button>
            </form>

            <div class="rp-kpis">
                <section class="rp-card rp-kpi">
                    <h2 class="rp-card__title"><i class="fas fa-wallet"></i> Tiền hiện có</h2>
                    <p class="rp-kpi__big">{{ $m($f['tien_dau_ky']) }}</p>
                    <p class="rp-kpi__note">{{ $f['nguon_tien_dau_ky'] }}</p>
                </section>
                <section class="rp-card rp-kpi {{ $f['tien_cuoi_13_tuan'] < $f['nguong_canh_bao'] ? 'rp-card--neg' : 'rp-card--thu' }}">
                    <h2 class="rp-card__title"><i class="fas fa-flag-checkered"></i> Sau 13 tuần</h2>
                    <p class="rp-kpi__big {{ $f['tien_cuoi_13_tuan'] < 0 ? 'is-neg' : '' }}">{{ $m($f['tien_cuoi_13_tuan']) }}</p>
                    <p class="rp-kpi__note">Thu {{ $tr($f['tong_thu']) }} · Chi {{ $tr($f['tong_chi']) }}</p>
                </section>
                <section class="rp-card rp-kpi {{ $f['so_tuan_duoi_nguong'] ? 'rp-card--neg' : 'rp-card--thu' }}">
                    <h2 class="rp-card__title"><i class="fas fa-exclamation-circle"></i> Tuần thấp nhất</h2>
                    <p class="rp-kpi__big {{ ($f['tuan_thap_nhat']['so_du'] ?? 0) < 0 ? 'is-neg' : '' }}">{{ $m($f['tuan_thap_nhat']['so_du'] ?? 0) }}</p>
                    <p class="rp-kpi__note">Tuần {{ $f['tuan_thap_nhat']['tuan'] ?? '—' }} (từ {{ $d($f['tuan_thap_nhat']['tu'] ?? null) }}) · {{ $f['so_tuan_duoi_nguong'] }} tuần dưới ngưỡng {{ $tr($f['nguong_canh_bao']) }}</p>
                </section>
            </div>

            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-chart-area"></i> Số dư tiền dự kiến từng tuần</h2>
                <div class="fn-chart"><canvas id="fn-cash-chart"></canvas></div>
            </section>

            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-table"></i> Chi tiết từng tuần</h2>
                <div class="rp-scroll">
                    <table class="bk-table fn-cash">
                        <thead>
                            <tr><th class="is-text">Tuần</th><th>Đầu tuần</th><th>Thu</th><th>Chi</th><th>Cuối tuần</th><th class="is-text">Khoản lớn</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($f['tuan'] as $w)
                                <tr class="{{ $w['canh_bao'] ? 'is-alert' : '' }}">
                                    <td class="is-text"><b>T{{ $w['tuan'] }}</b> <span class="fn-sub">{{ \Carbon\Carbon::parse($w['tu'])->format('d/m') }}–{{ \Carbon\Carbon::parse($w['den'])->format('d/m') }}</span></td>
                                    <td>{{ $m($w['dau_ky']) }}</td>
                                    <td class="fn-in" title="{{ collect($w['thu'])->map(fn ($v, $k) => $k . ': ' . $m($v))->implode("\n") }}">{{ $w['tong_thu'] ? '+' . $m($w['tong_thu']) : '' }}</td>
                                    <td class="fn-out" title="{{ collect($w['chi'])->map(fn ($v, $k) => $k . ': ' . $m($v))->implode("\n") }}">{{ $w['tong_chi'] ? '−' . $m($w['tong_chi']) : '' }}</td>
                                    <td><b class="{{ $w['cuoi_ky'] < 0 ? 'fn-bad' : '' }}">{{ $m($w['cuoi_ky']) }}</b>@if ($w['canh_bao']) <i class="fas fa-exclamation-triangle fn-bad" title="Dưới ngưỡng cảnh báo"></i>@endif</td>
                                    <td class="is-text is-wrap"><div class="fn-items">
                                        @foreach ($w['khoan_lon'] as $it)
                                            <span class="{{ $it['dir'] === 'in' ? 'fn-in' : 'fn-out' }}">{{ $it['dir'] === 'in' ? '+' : '−' }}{{ $tr($it['amount']) }} {{ \Illuminate\Support\Str::limit($it['label'], 38) }} <small>{{ $it['date'] }}</small></span>
                                        @endforeach
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rp-card">
                <h2 class="rp-card__title"><i class="fas fa-info-circle"></i> Giả định</h2>
                <ul class="fn-notes">
                    <li>Hóa đơn XQ chưa quá hạn: thu đúng hạn thanh toán. Nợ quá hạn hiện tại {{ $m($f['gia_dinh']['no_qua_han_hien_tai']) }}: thu {{ $f['gia_dinh']['thu_no_qua_han_pct'] }}%, rải đều 4 tuần đầu.</li>
                    <li>Đơn chưa xuất hóa đơn: xuất trong 7 ngày, thu sau {{ $f['gia_dinh']['so_ngay_thu_tien_binh_quan'] }} ngày (số ngày thu tiền bình quân), thu {{ $f['gia_dinh']['thu_chua_xuat_hd_pct'] }}%.</li>
                    <li>@if ($f['gia_dinh']['tinh_don_moi'])Đơn mới: doanh thu XQ bình quân 3 tháng gần nhất {{ $m($f['gia_dinh']['doanh_thu_xq_binh_quan_thang']) }}/tháng, vào tiền sau {{ 7 + $f['gia_dinh']['so_ngay_thu_tien_binh_quan'] }} ngày.@else Không tính doanh thu từ đơn mới (kịch bản thận trọng).@endif</li>
                    <li>CT và các khoản chi: theo Báo cáo thu chi (phần chưa thu / chưa chi). Tháng chưa có báo cáo: chi lặp lại các khoản định kỳ (bỏ khoản "đáo hạn" và khoản có chữ "bù" như "Trả bù lương tháng 10"), CT bình quân 3 tháng {{ $m($f['gia_dinh']['ct_binh_quan_thang']) }}.</li>
                    @foreach ($f['ghi_chu'] as $n)<li>{{ $n }}</li>@endforeach
                </ul>
            </section>
            @php($chartCash = ['labels' => array_map(fn ($w) => 'T' . $w['tuan'] . ' ' . \Carbon\Carbon::parse($w['tu'])->format('d/m'), $f['tuan']),
                'in' => array_column($f['tuan'], 'tong_thu'), 'out' => array_map(fn ($w) => -$w['tong_chi'], $f['tuan']),
                'balance' => array_column($f['tuan'], 'cuoi_ky'), 'threshold' => $f['nguong_canh_bao']])
            <script type="application/json" id="fn-cash-data">@json($chartCash)</script>
        @endif
    </div>
@endsection
@push('js')
    @if ($tab === 'ke-hoach')
        <script>
            // Cộng tổng cả năm từng dòng khi nhập kế hoạch
            document.querySelectorAll('.fn-budget__grid tr[data-row]').forEach(function (tr) {
                const sum = () => {
                    let t = 0;
                    tr.querySelectorAll('input').forEach(i => { t += parseInt((i.value || '').replace(/\D/g, ''), 10) || 0; });
                    tr.querySelector('[data-sum]').textContent = new Intl.NumberFormat('vi-VN').format(t);
                };
                tr.addEventListener('input', sum);
            });
        </script>
    @endif
    @if (in_array($tab, ['lai-uoc-tinh', 'dong-tien', 'ke-hoach'], true))
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            (function () {
                if (!window.Chart) return;
                const short = v => {
                    const a = Math.abs(v);
                    return a >= 1e9 ? (v / 1e9).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' tỷ'
                        : a >= 1e6 ? (v / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 0 }) + ' tr' : v.toLocaleString('vi-VN');
                };
                const full = v => new Intl.NumberFormat('vi-VN').format(Math.round(v)) + ' đ';
                const base = {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12 } },
                        tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + (c.dataset.yAxisID === 'y1' ? c.parsed.y + '%' : full(c.parsed.y)) } } },
                    scales: { x: { grid: { display: false } }, y: { grid: { color: '#eeebe3' }, ticks: { callback: short } } },
                };
                const month = document.getElementById('fn-month-data');
                if (month) {
                    const d = JSON.parse(month.textContent);
                    new Chart(document.getElementById('fn-month-chart'), {
                        type: 'bar',
                        data: { labels: d.labels, datasets: [
                            { label: 'Doanh thu thuần', data: d.revenue, backgroundColor: '#2f6fd1cc', borderRadius: 5, order: 2 },
                            { label: 'Lãi ước tính', data: d.profit, backgroundColor: '#0f9d6bcc', borderRadius: 5, order: 2 },
                            { type: 'line', label: 'Biên lãi', data: d.margin, borderColor: '#c96442', backgroundColor: '#c96442', yAxisID: 'y1', tension: .25, order: 1 },
                        ] },
                        options: Object.assign({}, base, { scales: Object.assign({}, base.scales, { y1: { position: 'right', grid: { display: false }, ticks: { callback: v => v + '%' } } }) }),
                    });
                }
                const budget = document.getElementById('fn-budget-data');
                if (budget) {
                    const d = JSON.parse(budget.textContent);
                    new Chart(document.getElementById('fn-budget-chart'), {
                        type: 'bar',
                        data: { labels: d.labels, datasets: [
                            { label: 'Kế hoạch', data: d.plan, backgroundColor: '#e2ded3', borderRadius: 5 },
                            { label: 'Thực tế', data: d.actual, backgroundColor: '#2f6fd1cc', borderRadius: 5 },
                        ] },
                        options: base,
                    });
                }
                const cash = document.getElementById('fn-cash-data');
                if (cash) {
                    const d = JSON.parse(cash.textContent);
                    new Chart(document.getElementById('fn-cash-chart'), {
                        type: 'bar',
                        data: { labels: d.labels, datasets: [
                            { type: 'line', label: 'Số dư cuối tuần', data: d.balance, borderColor: '#c96442', backgroundColor: '#c9644222', fill: true, tension: .25, order: 0,
                                pointBackgroundColor: d.balance.map(v => v < d.threshold ? '#b42318' : '#c96442'), pointRadius: 4 },
                            { label: 'Thu', data: d.in, backgroundColor: '#0f9d6bb3', borderRadius: 4, order: 1 },
                            { label: 'Chi', data: d.out, backgroundColor: '#b42318a6', borderRadius: 4, order: 1 },
                            { type: 'line', label: 'Ngưỡng cảnh báo', data: d.labels.map(() => d.threshold), borderColor: '#b42318', borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, order: 0 },
                        ] },
                        options: base,
                    });
                }
            })();
        </script>
    @endif
@endpush
