@extends('layouts.default_auth')
@section('title', 'Báo cáo thu chi - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/report.css') }}" type="text/css" as="style" />
@endpush

@section('admin_content')
    @php
        $isYearly = $isYearly ?? false;
        // Kế toán lập / sửa báo cáo tháng; cả năm và các tài khoản khác chỉ xem (ReportController::canEdit)
        $canEdit = !$isYearly && \App\Http\Controllers\ReportController::canEdit(Auth::user());
        $period = $isYearly ? 'năm ' . $year : 'tháng ' . $month . '/' . $year;
        $money = fn ($v) => number_format((float) ($v ?? 0), 0, ',', '.');

        $mainHeaders = [
            'Tiền hiện có (SDĐK)' => 'Số dư đầu kỳ',
            'CT' => 'I. Thu cố định',
            'XQ (số dự thu trong tháng)' => 'I. Thu cố định',
            'Thu chuyển nhượng' => 'II. Thu chuyển nhượng',
            'Chi tiền vay Bank' => 'I. Chi trả tiền vay',
            'Chi DV' => 'II. Chi dịch vụ / các khoản cố định',
            'Chi cố định' => 'II. Chi dịch vụ / các khoản cố định',
            'Chi nộp thuế' => 'II. Chi dịch vụ / các khoản cố định',
            'Chi trả NCC' => 'III. Chi trả nợ NCC',
            'Chi mượn cá nhân' => 'IV. Chi tiền mượn cá nhân',
            'Chi khác' => 'V. Chi khác',
        ];
        $subHeaders = [
            'Chi DV' => '1. Chi các khoản phí dịch vụ',
            'Chi cố định' => '2. Chi các khoản phí cố định',
            'Chi nộp thuế' => '3. Chi nộp ngân sách nhà nước',
            'Chi trả NCC' => '4. Chi trả nợ nhà cung cấp',
            'Chi mượn cá nhân' => '5. Chi tiền mượn cá nhân',
            'Chi khác' => '6. Chi khác',
        ];
        $groupOrder = array_keys($mainHeaders);
        $thuGroups = ['Tiền hiện có (SDĐK)', 'Thu chuyển nhượng', 'CT', 'XQ (số dự thu trong tháng)'];
        $chiGroups = ['Chi tiền vay Bank', 'Chi DV', 'Chi cố định', 'Chi nộp thuế', 'Chi trả NCC', 'Chi mượn cá nhân', 'Chi khác'];
        $groupLabel = ['Tiền hiện có (SDĐK)' => 'Tiền hiện có (số dư đầu kỳ)', 'XQ (số dự thu trong tháng)' => 'XQ (dự thu trong tháng)',
            'Chi tiền vay Bank' => 'Chi tiền vay ngân hàng', 'Chi DV' => 'Chi dịch vụ', 'Chi trả NCC' => 'Chi trả nhà cung cấp'];

        $orderedItems = $report->items
            ->sortBy(fn ($item) => (($pos = array_search($item->summary_group, $groupOrder)) === false ? 999 : $pos))
            ->values();
        $today = \Carbon\Carbon::now()->startOfDay();
    @endphp

    <div class="rp">
        {{-- Đầu trang: cùng kiểu Công nợ / Đơn hàng (thẻ trắng, vạch cam, ô chọn có nhãn) --}}
        <div class="rp-head">
            <div class="rp-head__title">
                <h1>Báo cáo thu chi</h1>
                <p>Kế hoạch dự thu, dự chi {{ $period }}@if ($isYearly) · tổng hợp 12 tháng, chỉ xem @elseif (!$canEdit) · chỉ xem @endif</p>
            </div>
            <div class="rp-head__actions">
                <label class="rp-field">
                    <span>Tháng</span>
                    <select id="selectMonth" aria-label="Chọn tháng" class="rp-month">
                        <option value="all" @selected($month === 'all')>Cả năm</option>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected($month !== 'all' && $i == $month)>{{ $i }}</option>
                        @endfor
                    </select>
                </label>
                <label class="rp-field">
                    <span>Năm</span>
                    <select id="selectYear" aria-label="Chọn năm">
                        @for ($y = date('Y') + 1; $y >= 2025; $y--)
                            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endfor
                    </select>
                </label>
                @if (!$isYearly && $report->id && $report->items->isNotEmpty())
                    <a href="{{ route('reports.export_pdf', $report->id) }}" class="rp-btn">
                        <i class="fas fa-file-pdf"></i> Xuất PDF
                    </a>
                @endif
            </div>
        </div>

        {{-- Thẻ tổng: report.js điền số vào các id tong-* / balance-est --}}
        <div class="rp-kpis">
            <section class="rp-card rp-kpi rp-card--thu">
                <h2 class="rp-card__title"><i class="fas fa-arrow-down"></i> Tổng thu</h2>
                <dl class="rp-kpi__grid">
                    <div><dt>Dự thu</dt><dd id="tong-thu-est">0</dd></div>
                    <div class="is-ok"><dt>Đã thu</dt><dd id="tong-thu-act">0</dd></div>
                    <div><dt>Còn phải thu</dt><dd id="tong-thu-diff">0</dd></div>
                </dl>
            </section>
            <section class="rp-card rp-kpi rp-card--chi">
                <h2 class="rp-card__title"><i class="fas fa-arrow-up"></i> Tổng chi</h2>
                <dl class="rp-kpi__grid">
                    <div><dt>Dự chi</dt><dd id="tong-chi-est">0</dd></div>
                    <div class="is-ok"><dt>Đã chi</dt><dd id="tong-chi-act">0</dd></div>
                    <div><dt>Chưa chi</dt><dd id="tong-chi-diff">0</dd></div>
                </dl>
            </section>
            <section class="rp-card rp-kpi rp-kpi--balance" id="balance-card">
                <h2 class="rp-card__title"><i class="fas fa-balance-scale"></i> Cân đối dự kiến</h2>
                <p class="rp-kpi__big" id="balance-est">0</p>
                <p class="rp-kpi__note">Tổng dự thu − tổng dự chi</p>
            </section>
            <section class="rp-card rp-kpi rp-card--debt">
                <h2 class="rp-card__title"><i class="fas fa-file-invoice-dollar"></i> Công nợ X-Quang {{ $year }}</h2>
                <dl class="rp-kpi__grid">
                    <div class="is-bad"><dt>Tổng còn nợ</dt><dd>{{ $money($accStats->total_price ?? 0) }}</dd></div>
                    <div><dt>Đã xuất HĐ</dt><dd>{{ $money($accStats->total_da_xuat_hd ?? 0) }}</dd></div>
                    <div><dt>Chưa xuất HĐ</dt><dd>{{ $money($accStats->total_chua_xuat_hd ?? 0) }}</dd></div>
                </dl>
            </section>
        </div>

        {{-- Tổng theo nhóm (report.js điền sum-est / sum-act / sum-diff theo data-group) --}}
        <div class="rp-sums">
            @foreach (['thu' => $thuGroups, 'chi' => $chiGroups] as $type => $groups)
                <section class="rp-card rp-card--{{ $type }}">
                    <h2 class="rp-card__title"><i class="fas fa-arrow-{{ $type === 'thu' ? 'down' : 'up' }}"></i> {{ $type === 'thu' ? 'Thu theo nhóm' : 'Chi theo nhóm' }}</h2>
                    <div class="rp-scroll">
                        <table class="rp-sum">
                            <thead>
                                <tr>
                                    <th>Nhóm</th>
                                    <th>{{ $type === 'thu' ? 'Dự thu' : 'Dự chi' }}</th>
                                    <th>{{ $type === 'thu' ? 'Đã thu' : 'Đã chi' }}</th>
                                    <th>{{ $type === 'thu' ? 'Còn lại' : 'Chưa chi' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $g)
                                    <tr class="summary-row" data-group="{{ $g }}" data-type="{{ $type }}">
                                        <td>{{ $groupLabel[$g] ?? $g }}</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>

        @if ($report->items->isEmpty())
            <section class="rp-card rp-empty">
                <i class="fas fa-folder-open"></i>
                <h2>Chưa có báo cáo {{ $period }}</h2>
                @if ($canEdit)
                    <p>Tạo khung mẫu gồm các khoản thu chi thường gặp (CT, XQ, vay ngân hàng, lương, thuế…) rồi sửa số cho tháng này.</p>
                    <form action="{{ route('reports.seed_default', $report->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="rp-btn rp-btn--primary"><i class="fas fa-plus"></i> Tạo khung báo cáo mẫu</button>
                    </form>
                @elseif ($isYearly)
                    <p>Năm {{ $year }} chưa có báo cáo tháng nào.</p>
                @else
                    <p>Phòng Kế toán chưa lập báo cáo cho tháng này.</p>
                @endif
            </section>
        @else
            <section class="rp-card rp-detail">
                {{-- Đầu bảng chi tiết: nút Lưu tất cả ngay trên bảng nó lưu; có thay đổi chưa lưu thì hiện thêm thanh lưu nổi dưới màn hình --}}
                <div class="rp-detail__head">
                    <div class="rp-detail__info">
                        <h2 class="rp-card__title">Chi tiết dự thu / dự chi</h2>
                        <div class="rp-legend">
                            <span class="rp-badge rp-badge--overdue">Quá hạn</span>
                            <span class="rp-badge rp-badge--soon">Còn ≤ 5 ngày</span>
                            @if ($canEdit)
                                <span class="rp-legend__hint"><i class="fas fa-grip-vertical"></i> kéo để đổi thứ tự trong nhóm · nút <i class="fas fa-check"></i> lưu 1 dòng và gửi email báo cập nhật</span>
                            @endif
                        </div>
                    </div>
                    @if ($canEdit)
                        <div class="rp-detail__save">
                            <button type="submit" form="reportForm" class="rp-btn rp-btn--primary rp-save-all" id="btn-save-all" title="Lưu toàn bộ bảng (không gửi email)">
                                <i class="fas fa-save"></i> <span>Lưu tất cả</span>
                            </button>
                        </div>
                    @endif
                </div>

                <form id="reportForm" action="{{ route('reports.update', $report->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="rp-scroll">
                        <table class="rp-table{{ $canEdit ? ' is-editable' : '' }}">
                            <thead>
                                <tr>
                                    <th class="rp-col-name">Hạng mục</th>
                                    <th class="rp-col-date">Ngày dự kiến</th>
                                    <th class="rp-col-money">Dự thu / chi</th>
                                    <th class="rp-col-money">Đã thu / chi</th>
                                    <th class="rp-col-date">Ngày thực tế</th>
                                    @if ($canEdit)
                                        <th class="rp-col-act"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody id="sortable-table-body">
                                @php
                                    $currentMainGroup = $currentSubGroup = $currentType = '';
                                    $cols = $canEdit ? 6 : 5;
                                @endphp
                                @foreach ($orderedItems as $index => $item)
                                    @php
                                        $main = $mainHeaders[$item->summary_group] ?? $item->summary_group;
                                        $sub = $subHeaders[$item->summary_group] ?? '';
                                        // Nhắc hạn: khoản chưa thu / chi có ngày dự kiến đã qua hoặc trong 5 ngày tới
                                        $badge = '';
                                        $rowClass = '';
                                        if ($item->expected_date && $item->actual_amount == 0 && $item->estimated_amount > 0) {
                                            $diff = $today->diffInDays(\Carbon\Carbon::parse($item->expected_date)->startOfDay(), false);
                                            if ($diff < 0) {
                                                $rowClass = 'is-overdue';
                                                $badge = '<span class="rp-badge rp-badge--overdue">Quá hạn ' . abs($diff) . ' ngày</span>';
                                            } elseif ($diff <= 5) {
                                                $rowClass = 'is-soon';
                                                $badge = '<span class="rp-badge rp-badge--soon">' . ($diff === 0 ? 'Hôm nay' : 'Còn ' . $diff . ' ngày') . '</span>';
                                            }
                                        }
                                    @endphp

                                    {{-- Dải THU / CHI --}}
                                    @if ($currentType !== $item->type)
                                        <tr class="rp-band rp-band--{{ $item->type }} {{ $item->type === 'thu' ? 'total-thu-row' : 'total-chi-row' }}">
                                            <td>{{ $item->type === 'thu' ? 'Thu' : 'Chi' }}</td>
                                            <td class="rp-band__label">{{ $item->type === 'thu' ? 'Tổng thu' : 'Tổng chi' }}</td>
                                            <td class="rp-num"><span id="detail-tong-{{ $item->type }}-est">0</span></td>
                                            <td class="rp-num"><span id="detail-tong-{{ $item->type }}-act">0</span></td>
                                            <td colspan="{{ $cols - 4 }}"></td>
                                        </tr>
                                        @php $currentType = $item->type; @endphp
                                    @endif

                                    {{-- Nhóm lớn --}}
                                    @if ($currentMainGroup !== $main)
                                        <tr class="group-header rp-group" data-main-header="{{ $main }}">
                                            <td colspan="2">{{ $main }}</td>
                                            <td class="rp-num rp-num--est"><span class="header-sum-est">0</span></td>
                                            <td class="rp-num rp-num--act"><span class="header-sum-act">0</span></td>
                                            <td></td>
                                            @if ($canEdit)
                                                <td class="rp-col-act">
                                                    @if (!$sub)
                                                        <button type="button" class="rp-add add-row-btn" data-type="{{ $item->type }}"
                                                            data-group="{{ $item->summary_group }}" data-main="{{ $main }}" data-sub="0">+ Thêm dòng</button>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                        @php $currentMainGroup = $main; @endphp
                                    @endif

                                    {{-- Nhóm nhỏ --}}
                                    @if ($sub && $currentSubGroup !== $sub)
                                        <tr class="rp-subgroup">
                                            <td colspan="5">{{ $sub }}</td>
                                            @if ($canEdit)
                                                <td class="rp-col-act">
                                                    <button type="button" class="rp-add add-row-btn" data-type="{{ $item->type }}"
                                                        data-group="{{ $item->summary_group }}" data-main="{{ $main }}" data-sub="1">+ Thêm dòng</button>
                                                </td>
                                            @endif
                                        </tr>
                                        @php $currentSubGroup = $sub; @endphp
                                    @endif

                                    @php
                                        // Dòng lấy số tự động: VCB chú Lộc (Sổ VCB) / CT (Công nợ CT) — chỉ sửa được ngày dự kiến
                                        $auto = $item->auto_key;
                                        $lock = !$canEdit || $auto;
                                        // Bấm "Tự động": VCB -> sổ VCB tháng trước (số dư cuối tháng đó là số đầu kỳ);
                                        // CT -> Công nợ CT đúng bệnh viện, đánh dấu các kỳ tạo nên số của tháng báo cáo
                                        $autoUrl = null;
                                        if ($auto === 'vcb') {
                                            $prev = \Carbon\Carbon::create($year, (int) $month, 1)->subMonth();
                                            $autoUrl = route('vcb.index', ['year' => $prev->year, 'month' => $prev->month]);
                                        } elseif ($auto) {
                                            [, $ctCode, $ctKind] = array_pad(explode(':', $auto), 3, 'thuong');
                                            $ctPartnerIds ??= \App\Models\Accounting\CtPartner::pluck('id', 'code');
                                            $autoUrl = route('ct.index', ['year' => $year, 'partner' => $ctPartnerIds[$ctCode] ?? null, 'loai' => $ctKind, 'bao_cao' => (int) $month]);
                                        }
                                    @endphp
                                    <tr class="item-row {{ $rowClass }}{{ $sub ? ' is-sub' : '' }}{{ $auto ? ' is-auto' : '' }}" data-id="{{ $item->id }}"
                                        data-group="{{ $item->summary_group }}" data-type="{{ $item->type }}" data-main-group="{{ $main }}">
                                        <td class="rp-col-name">
                                            <input type="hidden" class="row-id" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            <div class="rp-name">
                                                @if ($canEdit)
                                                    <i class="fas fa-grip-vertical drag-handle" title="Kéo để đổi thứ tự"></i>
                                                @endif
                                                <input type="text" class="item-name" name="items[{{ $index }}][name]" value="{{ $item->name }}"
                                                    @readonly($lock) aria-label="Hạng mục">
                                                @if ($auto)
                                                    <a href="{{ $autoUrl }}" class="rp-auto" title="{{ $auto === 'vcb' ? 'Số dư cuối tháng trước của Sổ VCB chú Lộc' : 'Dự thu = phần Medicen còn phải thu đến đầu tháng; Đã thu = tiền nhận trong tháng (Công nợ CT)' }}"><i class="fas fa-link"></i> Tự động</a>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="rp-col-date">
                                            <input type="date" class="date-input expected-date" name="items[{{ $index }}][expected_date]"
                                                value="{{ $item->expected_date ? $item->expected_date->format('Y-m-d') : '' }}" @readonly(!$canEdit) aria-label="Ngày dự kiến">
                                            {!! $badge !!}
                                        </td>
                                        <td class="rp-col-money">
                                            <input type="text" class="currency-input" inputmode="numeric" value="{{ $item->estimated_amount }}" @readonly($lock) aria-label="Số tiền dự kiến">
                                            <input type="hidden" class="est-input" name="items[{{ $index }}][estimated_amount]" value="{{ $item->estimated_amount }}">
                                        </td>
                                        <td class="rp-col-money">
                                            <input type="text" class="currency-input" inputmode="numeric" value="{{ $item->actual_amount }}" @readonly($lock) aria-label="Số tiền thực tế">
                                            <input type="hidden" class="act-input" name="items[{{ $index }}][actual_amount]" value="{{ $item->actual_amount }}">
                                        </td>
                                        <td class="rp-col-date">
                                            <input type="datetime-local" class="date-input actual-date" name="items[{{ $index }}][actual_date]"
                                                value="{{ $item->actual_date ? $item->actual_date->format('Y-m-d\TH:i') : '' }}" @readonly($lock) aria-label="Ngày thực tế">
                                        </td>
                                        @if ($canEdit)
                                            <td class="rp-col-act">
                                                @if (!$auto)
                                                <button type="button" class="rp-icon btn-save-row" title="Lưu dòng này (gửi email báo cập nhật)" aria-label="Lưu dòng"><i class="fas fa-check"></i></button>
                                                <button type="button" class="rp-icon rp-icon--danger btn-delete-row" title="Xóa dòng này" aria-label="Xóa dòng"><i class="fas fa-trash-alt"></i></button>
                                                @endif
                                                <input type="hidden" name="items[{{ $index }}][_delete]" class="delete-flag" value="0">
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            </section>
            @if ($canEdit)
                <div class="rp-savebar" id="rp-savebar" hidden>
                    <span><i class="fas fa-circle"></i> Có <b id="rp-dirty-count">0</b> dòng đã sửa chưa lưu</span>
                    <button type="submit" form="reportForm" class="rp-btn rp-btn--primary rp-save-all"><i class="fas fa-save"></i> <span>Lưu tất cả</span></button>
                </div>
            @endif
        @endif
    </div>
    @include('pages.admin.partials.dialog')
@endsection
@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script>
        var url_reports_update = "{{ route('reports.update', $report->id ?: 0) }}";
        var url_reports_update_order = "{{ route('reports.update_order') }}";
    </script>
    <script src="{{ versionResource('assets/js/tool/report.js') }}" defer></script>
@endpush
