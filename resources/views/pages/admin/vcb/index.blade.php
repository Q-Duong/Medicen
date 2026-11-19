@extends('layouts.default_auth')
@section('title', 'Sổ VCB chú Lộc - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/report.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $m = fn ($v) => $v === null ? '—' : number_format((int) $v, 0, ',', '.');
        $tagClass = ['ct' => 'bk-tag--ct', 'recurring' => 'bk-tag--recurring', 'import' => 'bk-tag--import', 'opening' => 'bk-tag--opening'];
        $openDate = $opening?->entry_date;
    @endphp
    <div class="rp">
        <div class="rp-head">
            <div class="rp-head__title">
                <h1>Sổ VCB chú Lộc</h1>
                <p>Tiền công ty đi qua TK VCB cá nhân chú Lộc · tháng {{ $month }}/{{ $year }}@if (!$canEdit) · chỉ xem @endif</p>
            </div>
            <form class="rp-head__actions" method="get">
                <label class="rp-field"><span>Tháng</span>
                    <select name="month" class="rp-month" onchange="this.form.submit()">@for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}" @selected($i == $month)>{{ $i }}</option>@endfor</select>
                </label>
                <label class="rp-field"><span>Năm</span>
                    <select name="year" onchange="this.form.submit()">@for ($y = now()->year + 1; $y >= 2025; $y--)<option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>@endfor</select>
                </label>
            </form>
        </div>
        @include('pages.admin.ct._toast')

        @if (!$opening)
            <div class="bk-alert bk-alert--warn">
                <i class="fas fa-info-circle"></i> Chưa chốt số dư đầu: các dòng chỉ để ghi nhớ, chưa tính được số dư và chưa đưa được vào Báo cáo thu chi.
                @if ($canEdit) Nhập số dư thực tế tại một ngày gần đây ở ô "Số dư chốt" bên phải. @endif
            </div>
        @endif

        <div class="rp-kpis">
            <section class="rp-card rp-kpi rp-card--debt">
                <h2 class="rp-card__title"><i class="fas fa-wallet"></i> Số dư đầu tháng</h2>
                <p class="rp-kpi__big">{{ $m($start_balance) }}</p>
                <p class="rp-kpi__note">Cuối ngày {{ \Carbon\Carbon::create($year, $month, 1)->subDay()->format('d/m/Y') }} · vào dòng "Tiền hiện có (VCB chú Lộc)" của báo cáo tháng {{ $month }}</p>
            </section>
            <section class="rp-card rp-kpi rp-card--thu">
                <h2 class="rp-card__title"><i class="fas fa-arrow-down"></i> Thu trong tháng</h2>
                <p class="rp-kpi__big is-pos">{{ $m($thu) }}</p>
                <p class="rp-kpi__note">Gồm thu CT qua VCB chú (tự sinh từ Công nợ CT)</p>
            </section>
            <section class="rp-card rp-kpi rp-card--chi">
                <h2 class="rp-card__title"><i class="fas fa-arrow-up"></i> Chi / chuyển lại công ty</h2>
                <p class="rp-kpi__big" style="color:#b4573a">{{ $m($chi) }}</p>
                <p class="rp-kpi__note">Gồm khoản cố định hằng tháng</p>
            </section>
            <section class="rp-card rp-kpi rp-kpi--balance {{ ($end_balance ?? 0) < 0 ? 'rp-card--neg' : 'rp-card--thu' }}">
                <h2 class="rp-card__title"><i class="fas fa-piggy-bank"></i> Số dư cuối tháng</h2>
                <p class="rp-kpi__big {{ ($end_balance ?? 0) < 0 ? 'is-neg' : 'is-pos' }}">{{ $m($end_balance) }}</p>
                <p class="rp-kpi__note">TK VCB chú còn giữ đến {{ \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('d/m/Y') }}</p>
            </section>
        </div>

        <div class="bk-layout">
            <section class="rp-card">
                <h2 class="rp-card__title">Các dòng tháng {{ $month }}/{{ $year }}</h2>
                @if ($canEdit)
                    <form method="post" action="{{ route('vcb.store') }}" class="bk-form" style="margin-bottom:12px">
                        @csrf
                        <label><span>Ngày</span><input type="date" name="entry_date" value="{{ old('entry_date', now()->year == $year && now()->month == $month ? now()->toDateString() : \Carbon\Carbon::create($year, $month, 1)->toDateString()) }}" required></label>
                        <label class="is-wide"><span>Nội dung</span><input type="text" name="description" value="{{ old('description') }}" maxlength="255" required placeholder="Vd: Chú ck HDB Cty, BS đọc CT Sa Đéc T10…"></label>
                        <label><span>Thu</span><input type="text" name="thu" class="is-money" value="{{ old('thu') }}" inputmode="numeric" placeholder="0"></label>
                        <label><span>Chi / ck lại Cty</span><input type="text" name="chi" class="is-money" value="{{ old('chi') }}" inputmode="numeric" placeholder="0"></label>
                        <div><button class="rp-btn rp-btn--primary" type="submit"><i class="fas fa-plus"></i> Thêm dòng</button></div>
                    </form>
                @endif
                <div class="rp-scroll">
                    <table class="bk-table">
                        <thead>
                            <tr>
                                <th class="is-text">Ngày</th>
                                <th class="is-text">Nội dung</th>
                                <th>Thu</th>
                                <th>Chi / ck lại Cty</th>
                                <th class="is-text">Nguồn</th>
                                @if ($canEdit)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $e)
                                @php $isCounted = $counted($e); @endphp
                                <tr class="{{ $isCounted || $e->source === 'opening' ? '' : 'is-muted' }}">
                                    <td class="is-text">{{ $e->entry_date->format('d/m/Y') }}</td>
                                    <td class="is-text is-wrap">{{ $e->description }}@if ($e->note)<br><small style="color:#a3a19b">{{ $e->note }}</small>@endif</td>
                                    <td class="{{ $e->thu ? 'is-pos' : '' }}">{{ $e->thu ? $m($e->thu) : '' }}</td>
                                    <td>{{ $e->chi ? $m($e->chi) : '' }}</td>
                                    <td class="is-text">
                                        <span class="bk-tag {{ $tagClass[$e->source] ?? '' }}">{{ \App\Models\Accounting\VcbEntry::SOURCES[$e->source] ?? $e->source }}</span>
                                        @if (!$isCounted && $e->source !== 'opening')<span class="bk-tag" title="Trước ngày chốt số dư: chỉ ghi nhớ, không cộng vào số dư">chưa tính</span>@endif
                                    </td>
                                    @if ($canEdit)
                                        <td>
                                            @if ($e->source === 'ct')
                                                <a href="{{ route('ct.edit', \App\Models\Accounting\CtPayment::find($e->ct_payment_id)?->receivable_id ?? 0) }}" class="rp-icon" title="Sửa ở Công nợ CT"><i class="fas fa-link"></i></a>
                                            @elseif ($e->source !== 'opening')
                                                @php($editData = json_encode(['url' => route('vcb.update', $e), 'entry_date' => $e->entry_date->toDateString(), 'description' => $e->description, 'thu' => $m($e->thu), 'chi' => $m($e->chi), 'note' => $e->note], JSON_UNESCAPED_UNICODE))
                                                <button type="button" class="rp-icon" title="Sửa" data-edit="{{ $editData }}"><i class="fas fa-pen"></i></button>
                                                <form method="post" action="{{ route('vcb.destroy', $e) }}" style="display:inline" data-confirm="Xóa dòng &quot;{{ $e->description }}&quot;?" data-confirm-danger>
                                                    @csrf @method('delete')
                                                    <button type="submit" class="rp-icon rp-icon--danger" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td class="is-text" colspan="6" style="color:#a3a19b">Tháng này chưa có dòng nào.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="bk-side">
                <section class="rp-card rp-card--debt">
                    <h2 class="rp-card__title"><i class="fas fa-flag"></i> Số dư chốt</h2>
                    @if ($opening)
                        <p style="font-size:20px;font-weight:700;font-variant-numeric:tabular-nums">{{ $m($opening->thu) }} đ</p>
                        <p class="bk-hint">Cuối ngày {{ $openDate->format('d/m/Y') }}@if ($opening->note) · {{ $opening->note }}@endif. Các dòng sau ngày này mới cộng vào số dư.</p>
                    @else
                        <p class="bk-hint">Chưa chốt.</p>
                    @endif
                    @if ($canEdit)
                        <details @if (!$opening) open @endif style="margin-top:8px">
                            <summary class="rp-add" style="display:inline-flex;list-style:none">{{ $opening ? 'Chốt lại số dư' : 'Nhập số dư chốt' }}</summary>
                            <form method="post" action="{{ route('vcb.opening') }}" class="bk-form" style="margin-top:10px" @if ($opening) data-confirm="Thay số dư chốt? Số dư các tháng sẽ tính lại từ ngày mới." @endif>
                                @csrf
                                <label><span>Cuối ngày</span><input type="date" name="date" value="{{ old('date', $openDate?->toDateString() ?? now()->subDay()->toDateString()) }}" required></label>
                                <label><span>Số dư (đ)</span><input type="text" name="amount" class="is-money" value="{{ old('amount', $opening ? $m($opening->thu) : '') }}" inputmode="numeric" required></label>
                                <label class="is-full"><span>Ghi chú</span><input type="text" name="note" maxlength="255" placeholder="Vd: theo sao kê VCB ngày …"></label>
                                <div class="bk-actions is-full" style="margin-top:0"><button type="button" class="rp-btn" data-details-cancel>Hủy</button> <button class="rp-btn rp-btn--primary" type="submit">Lưu số dư chốt</button></div>
                            </form>
                        </details>
                    @endif
                </section>

                <section class="rp-card">
                    <h2 class="rp-card__title"><i class="fas fa-redo"></i> Khoản trừ cố định hằng tháng</h2>
                    @foreach ($recurrings as $r)
                        <div class="bk-memo">
                            <div class="bk-memo__head">
                                <span style="{{ $r->active ? '' : 'color:#a3a19b;text-decoration:line-through' }}">{{ $r->description }}</span>
                                <span style="font-variant-numeric:tabular-nums">{{ $m($r->chi) }}</span>
                            </div>
                            <p>Ngày {{ $r->day }} hằng tháng{{ $r->active ? '' : ' · đã tắt' }}</p>
                            @if ($canEdit)
                                <details>
                                    <summary class="bk-edit"><i class="fas fa-pen"></i> Sửa</summary>
                                    <form method="post" action="{{ route('vcb.recurrings.update', $r) }}" class="bk-form" style="margin-top:8px">
                                        @csrf @method('patch')
                                        <label class="is-full"><span>Nội dung</span><input type="text" name="description" value="{{ $r->description }}" maxlength="255" required></label>
                                        <label><span>Số tiền</span><input type="text" name="chi" class="is-money" value="{{ $m($r->chi) }}" inputmode="numeric" required></label>
                                        <label><span>Ngày</span><input type="number" name="day" value="{{ $r->day }}" min="1" max="31" required></label>
                                        <input type="hidden" name="active" value="0">
                                        <label class="bk-check is-full"><input type="checkbox" name="active" value="1" @checked($r->active)> Đang trừ hằng tháng</label>
                                        <div class="bk-actions is-full" style="margin-top:0"><button type="button" class="rp-btn" data-details-cancel>Hủy</button> <button class="rp-btn rp-btn--primary" type="submit">Lưu</button></div>
                                    </form>
                                </details>
                            @endif
                        </div>
                    @endforeach
                    @if ($canEdit)
                        <details style="margin-top:8px">
                            <summary class="rp-add" style="display:inline-flex;list-style:none">+ Thêm khoản cố định</summary>
                            <form method="post" action="{{ route('vcb.recurrings.store') }}" class="bk-form" style="margin-top:10px">
                                @csrf
                                <label class="is-full"><span>Nội dung</span><input type="text" name="description" maxlength="255" required></label>
                                <label><span>Số tiền</span><input type="text" name="chi" class="is-money" inputmode="numeric" required></label>
                                <label><span>Ngày</span><input type="number" name="day" min="1" max="31" value="15" required></label>
                                <div class="bk-actions is-full" style="margin-top:0"><button type="button" class="rp-btn" data-details-cancel>Hủy</button> <button class="rp-btn rp-btn--primary" type="submit">Thêm</button></div>
                            </form>
                        </details>
                        <p class="bk-hint">Mỗi tháng (từ tháng chốt số dư) hệ thống tự thêm dòng chi cho các khoản này.</p>
                    @endif
                </section>

                @if ($yearBalances->isNotEmpty())
                    <section class="rp-card">
                        <h2 class="rp-card__title"><i class="fas fa-chart-line"></i> Số dư cuối các tháng {{ $year }}</h2>
                        <table class="bk-table">
                            <tbody>
                                @foreach ($yearBalances as $mm => $bal)
                                    @continue($bal === null)
                                    <tr><td class="is-text"><a href="{{ route('vcb.index', ['year' => $year, 'month' => $mm]) }}">Tháng {{ $mm }}</a></td><td class="{{ $bal < 0 ? 'is-neg' : 'is-strong' }}">{{ $m($bal) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif

                @include('pages.admin.ct._memos', ['scope' => 'vcb', 'intro' => 'Số liệu cũ trước khi theo dõi trên web (chưa đối chiếu được), chỉ để ghi nhớ.'])
            </div>
        </div>
    </div>

    @if ($canEdit)
        <dialog class="bk-dialog" id="vcb-edit">
            <form method="post" class="bk-form" id="vcb-edit-form">
                @csrf @method('patch')
                <h2 class="is-full">Sửa dòng</h2>
                <label><span>Ngày</span><input type="date" name="entry_date" required></label>
                <label class="is-wide"><span>Nội dung</span><input type="text" name="description" maxlength="255" required></label>
                <label><span>Thu</span><input type="text" name="thu" class="is-money" inputmode="numeric"></label>
                <label><span>Chi / ck lại Cty</span><input type="text" name="chi" class="is-money" inputmode="numeric"></label>
                <label class="is-full"><span>Ghi chú</span><input type="text" name="note" maxlength="1000"></label>
                <div class="bk-actions is-full" style="margin-top:0">
                    <button type="button" class="rp-btn" onclick="this.closest('dialog').close()">Hủy</button>
                    <button type="submit" class="rp-btn rp-btn--primary">Lưu</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
@push('js')
    <script>
        (function () {
            // Ô tiền tự thêm dấu chấm: xử lý chung trong partials/dialog
            const dlg = document.getElementById('vcb-edit');
            document.querySelectorAll('[data-edit]').forEach((b) => b.addEventListener('click', () => {
                const d = JSON.parse(b.dataset.edit);
                const f = document.getElementById('vcb-edit-form');
                f.action = d.url;
                ['entry_date', 'description', 'thu', 'chi', 'note'].forEach((k) => { f.elements[k].value = d[k] && d[k] !== '0' ? d[k] : ''; });
                dlg.showModal();
            }));
        })();
    </script>
@endpush
