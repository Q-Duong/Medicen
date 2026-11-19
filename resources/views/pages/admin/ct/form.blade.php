@extends('layouts.default_auth')
@section('title', ($row->exists ? 'Công nợ CT ' . $row->title : 'Thêm kỳ CT') . ' - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/report.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $canEdit = $canEdit ?? true;
        $m = fn ($v) => $v === null || $v === '' ? '' : number_format((int) $v, 0, ',', '.');
        $v = fn ($k) => old($k, $row->{$k});
        $money = ['revenue' => 'Tổng doanh thu', 'cost' => 'Tổng chi phí', 'hospital_share' => 'Bệnh viện chia lãi', 'medicen_share' => 'Medicen chia lãi',
            'hospital_collect' => 'Bệnh viện thu về', 'medicen_collect' => 'Medicen thu về'];
    @endphp
    <div class="rp">
        <div class="rp-head">
            <div class="rp-head__title">
                <h1>{{ $row->exists ? 'Công nợ CT ' . $row->title : 'Thêm kỳ CT' }}</h1>
                <p>Mỗi bệnh viện mỗi tháng một dòng · Medicen thu về là số đi vào công nợ</p>
            </div>
            <div class="rp-head__actions">
                <a href="{{ route('ct.index', ['year' => $row->year]) }}" class="rp-btn"><i class="fas fa-arrow-left"></i> Danh sách</a>
            </div>
        </div>
        @include('pages.admin.ct._toast')

        <div class="bk-layout">
            <section class="rp-card">
                <h2 class="rp-card__title">Số liệu kỳ</h2>
                <form method="post" action="{{ $row->exists ? route('ct.update', $row) : route('ct.store') }}" class="bk-form" id="ct-form">
                    @csrf
                    @if ($row->exists) @method('patch') @endif
                    <label class="is-wide">
                        <span>Bệnh viện</span>
                        <select name="partner_id" required @disabled(!$canEdit)>
                            <option value="">Chọn bệnh viện</option>
                            @foreach ($partners as $p)
                                <option value="{{ $p->id }}" @selected($v('partner_id') == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label><span>Tháng</span>
                        <select name="month" @disabled(!$canEdit)>@for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}" @selected($v('month') == $i)>{{ $i }}</option>@endfor</select>
                    </label>
                    <label><span>Năm</span><input type="number" name="year" value="{{ $v('year') }}" min="2020" max="2100" @readonly(!$canEdit)></label>
                    <label><span>Loại</span>
                        <select name="kind" id="ct-kind" @disabled(!$canEdit)>
                            @foreach (\App\Models\Accounting\CtReceivable::KINDS as $k => $label)<option value="{{ $k }}" @selected($v('kind') === $k)>{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <label><span>Đợt BHYT</span><input type="text" name="label" value="{{ $v('label') }}" maxlength="100" placeholder="Vd: Q1+2" @readonly(!$canEdit)></label>
                    <label><span>Số ca</span><input type="number" name="quantity" value="{{ $v('quantity') }}" min="0" @readonly(!$canEdit)></label>

                    @foreach ($money as $k => $label)
                        <label @class(['is-wide' => in_array($k, ['revenue', 'cost'])])>
                            <span>{{ $label }}</span>
                            <input type="text" name="{{ $k }}" class="is-money" data-money="{{ $k }}" value="{{ $m($v($k)) }}" inputmode="numeric" required @readonly(!$canEdit)>
                        </label>
                        @if ($k === 'cost')
                            <label class="is-full"><span>Lãi / lỗ (tự tính)</span><input type="text" class="is-money is-readonly" id="ct-profit" readonly tabindex="-1"></label>
                        @endif
                    @endforeach
                    <p class="bk-hint is-full" id="ct-check"></p>
                    <label class="is-full"><span>Ghi chú</span><textarea name="note" rows="2" @readonly(!$canEdit)>{{ $v('note') }}</textarea></label>
                    @if ($canEdit)
                        <div class="bk-actions is-full">
                            @if ($row->exists)
                                <button type="submit" form="ct-del" class="rp-btn" style="color:#b42318 !important"><i class="fas fa-trash-alt"></i> Xóa kỳ</button>
                            @endif
                            <button type="submit" class="rp-btn rp-btn--primary"><i class="fas fa-save"></i> {{ $row->exists ? 'Lưu' : 'Thêm kỳ CT' }}</button>
                        </div>
                    @endif
                </form>
                @if ($row->exists && $canEdit)
                    <form method="post" action="{{ route('ct.destroy', $row) }}" id="ct-del" data-confirm="Xóa công nợ CT {{ $row->title }} và các lần thanh toán? Dòng thu tự sinh ở Sổ VCB cũng bị xóa." data-confirm-danger>
                        @csrf @method('delete')
                    </form>
                @endif
            </section>

            <div class="bk-side">
                @if ($row->exists)
                    <section class="rp-card rp-card--thu">
                        <h2 class="rp-card__title"><i class="fas fa-money-check-alt"></i> Thanh toán</h2>
                        <dl class="rp-kpi__grid" style="margin-bottom:10px">
                            <div><dt>Medicen thu về</dt><dd>{{ $m($row->medicen_collect) }}</dd></div>
                            <div class="is-ok"><dt>Đã thanh toán</dt><dd>{{ $m($row->paid) }}</dd></div>
                            <div class="{{ $row->outstanding > 0 ? 'is-bad' : 'is-ok' }}"><dt>Còn phải thu</dt><dd>{{ $m($row->outstanding) }}</dd></div>
                        </dl>
                        @foreach ($row->payments as $p)
                            <div class="bk-memo">
                                <div class="bk-memo__head">
                                    <span>{{ $p->paid_on->format('d/m/Y') }} <span class="bk-tag {{ $p->method === 'L-VCB' ? 'bk-tag--lvcb' : 'bk-tag--hdb' }}">{{ \App\Models\Accounting\CtPayment::methodLabel($p->method) }}</span></span>
                                    <span style="font-variant-numeric:tabular-nums">{{ $m($p->amount) }}</span>
                                </div>
                                @if ($p->note)<p>{{ $p->note }}</p>@endif
                                @if ($canEdit)
                                    <details>
                                        <summary class="bk-edit"><i class="fas fa-pen"></i> Sửa / xóa</summary>
                                        <form method="post" action="{{ route('ct.payments.update', $p) }}" class="bk-form" style="margin-top:8px">
                                            @csrf @method('patch')
                                            <label><span>Ngày</span><input type="date" name="paid_on" value="{{ $p->paid_on->toDateString() }}" required></label>
                                            <label><span>Hình thức</span>
                                                <select name="method">
                                                    @foreach (\App\Models\Accounting\CtPayment::METHODS + (isset(\App\Models\Accounting\CtPayment::METHODS[$p->method]) ? [] : [$p->method => \App\Models\Accounting\CtPayment::methodLabel($p->method)]) as $k => $label)
                                                        <option value="{{ $k }}" @selected($p->method === $k)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="is-full"><span>Số tiền</span><input type="text" name="amount" class="is-money" value="{{ $m($p->amount) }}" inputmode="numeric" required></label>
                                            <label class="is-full"><span>Ghi chú</span><input type="text" name="note" value="{{ $p->note }}" maxlength="255"></label>
                                            <div class="bk-actions is-full" style="margin-top:0">
                                                <button type="submit" form="pay-del-{{ $p->id }}" class="rp-btn" style="color:#b42318 !important"><i class="fas fa-trash-alt"></i></button>
                                                <button type="button" class="rp-btn" data-details-cancel>Hủy</button> <button type="submit" class="rp-btn rp-btn--primary">Lưu</button>
                                            </div>
                                        </form>
                                        <form method="post" action="{{ route('ct.payments.destroy', $p) }}" id="pay-del-{{ $p->id }}" data-confirm="Xóa lần thanh toán {{ $m($p->amount) }} đ ngày {{ $p->paid_on->format('d/m/Y') }}?" data-confirm-danger>
                                            @csrf @method('delete')
                                        </form>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                        @if ($canEdit)
                            <h3 class="bk-section-title">Thêm lần thanh toán</h3>
                            <form method="post" action="{{ route('ct.payments.store', $row) }}" class="bk-form">
                                @csrf
                                <label><span>Ngày</span><input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" required></label>
                                <label><span>Hình thức</span>
                                    <select name="method">
                                        @foreach (\App\Models\Accounting\CtPayment::METHODS as $k => $label)<option value="{{ $k }}" @selected(old('method') === $k)>{{ $label }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="is-full"><span>Số tiền</span><input type="text" name="amount" class="is-money" value="{{ old('amount', $row->outstanding > 0 ? $m($row->outstanding) : '') }}" inputmode="numeric" required></label>
                                <label class="is-full"><span>Ghi chú</span><input type="text" name="note" maxlength="255"></label>
                                <div class="bk-actions is-full" style="margin-top:0"><button type="button" class="rp-btn" data-details-cancel>Hủy</button> <button class="rp-btn rp-btn--primary" type="submit"><i class="fas fa-plus"></i> Ghi nhận</button></div>
                            </form>
                            <p class="bk-hint">Chọn "VCB chú Lộc" thì hệ thống tự thêm dòng thu vào Sổ VCB chú Lộc.</p>
                        @endif
                    </section>
                @else
                    <section class="rp-card">
                        <h2 class="rp-card__title">Hướng dẫn</h2>
                        <p class="bk-hint">Thêm kỳ trước, sau đó nhập các lần bệnh viện thanh toán (HDB hoặc VCB chú Lộc). Còn phải thu = Medicen thu về − đã thanh toán.</p>
                        <p class="bk-hint">Á Châu thanh toán BHYT theo đợt: chọn Loại "BHYT" và ghi đợt (vd Q1+2) — số này đi vào dòng "CT Á Châu (bhyt)" của Báo cáo thu chi.</p>
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        // Ô tiền: định dạng 1.234.567 (cho phép số âm); tự tính lãi / lỗ và kiểm tra phần chia
        (function () {
            const fmt = (n) => new Intl.NumberFormat('vi-VN').format(n);
            const num = (el) => { const s = (el.value || '').replace(/[^\d-]/g, ''); return s === '' || s === '-' ? 0 : parseInt(s, 10); };
            // Định dạng ô tiền làm chung trong partials/dialog; ở đây chỉ tính lại lãi / lỗ và kiểm tra
            document.querySelectorAll('.is-money:not([readonly])').forEach((el) => el.addEventListener('input', calc));
            const get = (k) => { const el = document.querySelector('[data-money="' + k + '"]'); return el ? num(el) : 0; };
            function calc() {
                const profit = get('revenue') - get('cost');
                const pEl = document.getElementById('ct-profit');
                if (pEl) pEl.value = fmt(profit);
                const msg = [];
                if (get('hospital_share') + get('medicen_share') !== profit) msg.push('BV chia + Medicen chia (' + fmt(get('hospital_share') + get('medicen_share')) + ') khác lãi / lỗ (' + fmt(profit) + ').');
                if (get('hospital_collect') + get('medicen_collect') !== get('revenue')) msg.push('BV thu về + Medicen thu về (' + fmt(get('hospital_collect') + get('medicen_collect')) + ') khác doanh thu (' + fmt(get('revenue')) + ').');
                const box = document.getElementById('ct-check');
                if (box) {
                    box.textContent = msg.length ? 'Kiểm tra: ' + msg.join(' ') : (get('revenue') ? '✓ Số chia khớp với doanh thu và lãi / lỗ.' : '');
                    box.classList.toggle('is-bad', msg.length > 0);
                }
            }
            calc();
        })();
    </script>
@endpush
