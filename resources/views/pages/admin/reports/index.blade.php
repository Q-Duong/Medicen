@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/report.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/filepond-preview.css') }}" type="text/css"
        as="style" />
@endpush

@section('admin_content')
    <div class="table-agile-info">
        <h2 class="panel-heading">BÁO CÁO DỰ TOÁN THU CHI
            {{ $month === 'all' ? 'NĂM ' . $year : 'THÁNG ' . $month . '/' . $year }}
        </h2>

        <div class="table-content">
            <div id="reportContainer">

                <div class="sticky-summary-section">
                    <div class="filter-section">
                        <div class="filter-group">
                            <select name="month" id="selectMonth" class="select-filter">
                                <option value="all" {{ $month === 'all' ? 'selected' : '' }}>Cả năm</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}"
                                        {{ $month != 'all' && $i == $month ? 'selected' : '' }}>Tháng
                                        {{ $i }}</option>
                                @endfor
                            </select>

                            <select name="year" id="selectYear" class="select-filter">
                                @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>Năm
                                        {{ $y }}</option>
                                @endfor
                            </select>

                            <button type="button" class="btn btn-primary btn-filter"
                                onclick="window.location.href='?month='+document.getElementById('selectMonth').value+'&year='+document.getElementById('selectYear').value">Lọc</button>
                        </div>
                    </div>

                    <div class="summary-wrapper">
                        <!-- BẢNG TỔNG THU -->
                        <div class="summary-box">
                            <table class="table-thu">
                                <thead>
                                    <tr class="section-title">
                                        <th>Danh mục Thu</th>
                                        <th>Số phải thu</th>
                                        <th>Đã thu</th>
                                        <th>Còn lại</th>
                                    </tr>
                                    <tr class="grand-total">
                                        <td>Tổng thu</td>
                                        <td id="tong-thu-est">0</td>
                                        <td id="tong-thu-act">0</td>
                                        <td id="tong-thu-diff">0</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="summary-row" data-group="Tiền hiện có (SDĐK)" data-type="thu">
                                        <td>Tiền hiện có (SDĐK)</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="Thu chuyển nhượng" data-type="thu">
                                        <td>Thu chuyển nhượng</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="CT" data-type="thu">
                                        <td>CT</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="XQ (số dự thu trong tháng)" data-type="thu">
                                        <td>XQ (số dự thu trong tháng)</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr>
                                        <td style="font-style: italic; font-size: 14px; font-weight: bold; color: #3498db;">
                                            - Tổng nợ X-Quang</td>
                                        <td colspan="3"
                                            style="font-style: italic; font-size: 14px; text-align: center; font-weight: bold; color: #3498db;">
                                            {{ number_format($accStats->total_owe ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-style: italic; font-size: 14px; font-weight: bold; color: #e67e22;">
                                            - Tổng đã xuất HD</td>
                                        <td colspan="3"
                                            style="font-style: italic; font-size: 14px; text-align: center; font-weight: bold; color: #e67e22;">
                                            {{ number_format($accStats->total_da_xuat_hd ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-style: italic; font-size: 14px; font-weight: bold; color: #c0392b;">
                                            - Tổng chưa xuất HD</td>
                                        <td colspan="3"
                                            style="font-style: italic; font-size: 14px; text-align: center;  font-weight: bold; color: #c0392b;">
                                            {{ number_format($accStats->total_chua_xuat_hd ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- BẢNG TỔNG CHI -->
                        <div class="summary-box">
                            <table class="table-chi">
                                <thead>
                                    <tr>
                                        <th>Danh mục Chi</th>
                                        <th>Cộng chi</th>
                                        <th>Đã chi</th>
                                        <th>Chưa chi</th>
                                    </tr>
                                    <tr class="grand-total">
                                        <td>Tổng chi</td>
                                        <td id="tong-chi-est">0</td>
                                        <td id="tong-chi-act">0</td>
                                        <td id="tong-chi-diff">0</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="summary-row" data-group="Chi tiền vay Bank" data-type="chi">
                                        <td>Chi tiền vay Bank</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="Chi DV" data-type="chi">
                                        <td>Chi DV</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="Chi cố định" data-type="chi">
                                        <td>Chi cố định</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="Chi nộp thuế" data-type="chi">
                                        <td>Chi nộp thuế</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="Chi trả NCC" data-type="chi">
                                        <td>Chi trả NCC</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="Chi mượn cá nhân" data-type="chi">
                                        <td>Chi mượn cá nhân</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                    <tr class="summary-row" data-group="Chi khác" data-type="chi">
                                        <td>Chi khác</td>
                                        <td class="sum-est">0</td>
                                        <td class="sum-act">0</td>
                                        <td class="sum-diff">0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="balance-wrapper">
                        <table>
                            <thead>
                                <tr class="section-title">
                                    <th>CÂN ĐỐI DÒNG TIỀN (THU - CHI)</th>
                                    <th>Thặng Dư Dự Kiến</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="background-color: #ffffff;">
                                    <td
                                        style="width: 50%; padding: 10px; font-weight: bold; text-align: center; border: 1px solid #dee2e6; color: #2c3e50;">
                                        LỢI NHUẬN / THẶNG DƯ</td>
                                    <td id="balance-est"
                                        style="width: 50%; padding: 10px; text-align: center; font-weight: bold; font-size: 17px; border: 1px solid #dee2e6;">
                                        0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <form id="reportForm" action="{{ route('reports.update', $report->id) }}" method="POST">
                        @csrf @method('PUT')

                        @if (!$report->items->isEmpty() && (!isset($isYearly) || !$isYearly) && Auth::user()->role == 3)
                            <button type="submit" class="btn btn-success btn-save-all" id="btn-save-all">Lưu
                                Tất
                                Cả</button>
                        @endif
                    </form>
                </div>

                @if ($report->items->isEmpty())
                    <div style="text-align: center; padding: 80px 20px; background: #fff; border-radius: 8px;">
                        <i class="fas fa-folder-open" style="font-size: 50px; color: #bdc3c7; margin-bottom: 15px;"></i>
                        <h4 style="color: #7f8c8d;">Chưa có dữ liệu báo cáo cho tháng này</h4>

                        @if ((!isset($isYearly) || !$isYearly) && Auth::user()->role == 3)
                            <form action="{{ route('reports.seed_default', $report->id) }}" method="POST"
                                style="margin-top: 20px;">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-seed-default"
                                    style="padding: 10px 20px; font-size: 15px;">
                                    + Tạo khung báo cáo mẫu ngay
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    <form id="reportForm" action="{{ route('reports.update', $report->id) }}" method="POST">
                        @csrf @method('PUT')
                        <!-- GÓI CUỘN RIÊNG CHO BẢNG CHI TIẾT -->
                        <div class="detail-table-wrapper">
                            <table class="detail-table">
                                <thead>
                                    <tr>
                                        <th width="12%">Ngày dự kiến</th>
                                        <th width="28%">Hạng mục chi tiết</th>
                                        <th width="18%">Số tiền (Dự thu/chi)</th>
                                        <th width="18%">Số tiền (Đã thu/chi)</th>
                                        <th width="12%">Ngày thực tế</th>
                                        <th width="12%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="sortable-table-body">
                                    @php
                                        $currentMainGroup = '';
                                        $currentSubGroup = '';
                                        $currentType = '';

                                        $mainHeaders = [
                                            'Tiền hiện có (SDĐK)' => 'SỐ DƯ ĐẦU KỲ',
                                            'CT' => 'I. THU CỐ ĐỊNH',
                                            'XQ (số dự thu trong tháng)' => 'I. THU CỐ ĐỊNH',
                                            'Thu chuyển nhượng' => 'II. THU CHUYỂN NHƯỢNG',
                                            'Chi tiền vay Bank' => 'I. CHI TRẢ TIỀN VAY',
                                            'Chi DV' => 'II. CHI DỊCH VỤ / CÁC KHOẢN CỐ ĐỊNH',
                                            'Chi cố định' => 'II. CHI DỊCH VỤ / CÁC KHOẢN CỐ ĐỊNH',
                                            'Chi nộp thuế' => 'II. CHI DỊCH VỤ / CÁC KHOẢN CỐ ĐỊNH',
                                            'Chi trả NCC' => 'III. CHI TRẢ NỢ NCC',
                                            'Chi mượn cá nhân' => 'IV. CHI TIỀN MƯỢN CÁ NHÂN',
                                            'Chi khác' => 'V. CHI KHÁC',
                                        ];

                                        $subHeaders = [
                                            'Chi DV' => '1. Chi các khoản phí DV',
                                            'Chi cố định' => '2. Chi các khoản phí cố định',
                                            'Chi nộp thuế' => '3. Chi nộp NSNN',
                                            'Chi trả NCC' => '4. Chi trả nợ NCC',
                                            'Chi mượn cá nhân' => '5. Chi tiền mượn cá nhân',
                                            'Chi khác' => '6. Chi khác',
                                        ];

                                        $groupOrder = [
                                            'Tiền hiện có (SDĐK)',
                                            'CT',
                                            'XQ (số dự thu trong tháng)',
                                            'Thu chuyển nhượng',
                                            'Chi tiền vay Bank',
                                            'Chi DV',
                                            'Chi cố định',
                                            'Chi nộp thuế',
                                            'Chi trả NCC',
                                            'Chi mượn cá nhân',
                                            'Chi khác',
                                        ];

                                        $orderedItems = $report->items
                                            ->sortBy(function ($item) use ($groupOrder) {
                                                $pos = array_search($item->summary_group, $groupOrder);
                                                return $pos === false ? 999 : $pos;
                                            })
                                            ->values();
                                    @endphp

                                    @foreach ($orderedItems as $index => $item)
                                        @php
                                            $main = $mainHeaders[$item->summary_group] ?? $item->summary_group;
                                            $sub = $subHeaders[$item->summary_group] ?? '';

                                            // LOGIC TÍNH TOÁN NGÀY NHẮC NHỞ
                                            $reminderBadge = '';
                                            $rowBgColor = '';

                                            if ($item->expected_date && $item->actual_amount == 0) {
                                                $today = \Carbon\Carbon::now()->startOfDay();
                                                $expected = \Carbon\Carbon::parse($item->expected_date)->startOfDay();
                                                $diff = $today->diffInDays($expected, false); // Âm là quá hạn, dương là còn hạn

                                                if ($diff < 0) {
                                                    $rowBgColor = 'background-color: #facfcf;'; // Đỏ nhạt (Quá hạn)
                                                    $reminderBadge =
                                                        '<span class="overdue-badge">Quá hạn ' .
                                                        abs($diff) .
                                                        ' ngày</span>';
                                                } elseif ($diff >= 0 && $diff <= 5) {
                                                    $rowBgColor = 'background-color: #f8ecae;'; // Vàng nhạt (Sắp đến hạn trong 5 ngày)
                                                    $reminderBadge =
                                                        '<span class="due-soon-badge">Còn ' . $diff . ' ngày</span>';
                                                }
                                            }
                                        @endphp

                                        {{-- Dòng Tổng Thu / Tổng Chi --}}
                                        @if ($currentType !== $item->type)
                                            @if ($item->type == 'thu')
                                                <tr class="total-thu-row">
                                                    <td style="text-align: center;"><strong>THU</strong></td>
                                                    <td style="text-align: center;"><em><strong>Tổng thu</strong></em></td>
                                                    <td
                                                        style="text-align: right; font-weight: bold; font-size: 15px; font-style: italic;">
                                                        <span id="detail-tong-thu-est">0</span>
                                                    </td>
                                                    <td
                                                        style="text-align: right; font-weight: bold; font-size: 15px; font-style: italic;">
                                                        <span id="detail-tong-thu-act">0</span>
                                                    </td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                            @elseif($item->type == 'chi')
                                                <tr class="total-chi-row">
                                                    <td style="text-align: center;"><strong>CHI</strong></td>
                                                    <td style="text-align: center;"><em><strong>Tổng Chi</strong></em></td>
                                                    <td
                                                        style="text-align: right; font-weight: bold; font-size: 15px; font-style: italic;">
                                                        <span id="detail-tong-chi-est">0</span>
                                                    </td>
                                                    <td
                                                        style="text-align: right; font-weight: bold; font-size: 15px; font-style: italic;">
                                                        <span id="detail-tong-chi-act">0</span>
                                                    </td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                            @endif
                                            @php $currentType = $item->type; @endphp
                                        @endif

                                        {{-- Tiêu đề Nhóm Lớn --}}
                                        @if ($currentMainGroup !== $main)
                                            <tr class="group-header" data-main-header="{{ $main }}">
                                                <td colspan="2">
                                                    {{-- {{ $item->type == 'thu' ? 'THU' : 'CHI' }}:  --}}
                                                    {{ $main }}
                                                </td>
                                                <td style="text-align: right; color: #23bf65; font-size: 15px;"><span
                                                        class="header-sum-est">0</span></td>
                                                <td style="text-align: right; color: #3498db; font-size: 15px;"><span
                                                        class="header-sum-act">0</span></td>
                                                <td></td>

                                                @if (!$sub && (!isset($isYearly) || !$isYearly) && Auth::user()->role == 3)
                                                    <td style="text-align: center;">
                                                        <button type="button" class="btn btn-sm add-row-btn"
                                                            data-type="{{ $item->type }}"
                                                            data-group="{{ $item->summary_group }}"
                                                            data-main="{{ $main }}" data-sub="0">+ Thêm
                                                            dòng</button>
                                                    </td>
                                                @else
                                                    <td style="text-align: center;">
                                                    </td>
                                                @endif

                                            </tr>
                                            @php $currentMainGroup = $main; @endphp
                                        @endif

                                        {{-- Tiêu đề Nhóm Nhỏ --}}
                                        @if ($sub && $currentSubGroup !== $sub)
                                            <tr class="sub-header-row">
                                                <td colspan="5" class="sub-header">
                                                    {{ $sub }}
                                                </td>

                                                @if ((!isset($isYearly) || !$isYearly) && Auth::user()->role == 3)
                                                    <td style="text-align: center; background-color: #fafafa;">
                                                        <button type="button" class="btn btn-sm add-row-btn"
                                                            data-type="{{ $item->type }}"
                                                            data-group="{{ $item->summary_group }}"
                                                            data-main="{{ $main }}" data-sub="1">+ Thêm
                                                            dòng</button>
                                                    </td>
                                                @else
                                                    <td style="text-align: center; background-color: #fafafa;">
                                                    </td>
                                                @endif

                                            </tr>
                                            @php $currentSubGroup = $sub; @endphp
                                        @endif

                                        <tr class="item-row" data-id="{{ $item->id }}"
                                            data-group="{{ $item->summary_group }}" data-type="{{ $item->type }}"
                                            data-main-group="{{ $main }}" style="{{ $rowBgColor }}">

                                            <input type="hidden" class="row-id" name="items[{{ $index }}][id]"
                                                value="{{ $item->id }}">

                                            <td style="text-align: center">
                                                {!! $reminderBadge !!}
                                                <input type="date" class="date-input expected-date"
                                                    name="items[{{ $index }}][expected_date]"
                                                    value="{{ $item->expected_date ? $item->expected_date->format('Y-m-d') : '' }}"
                                                    {{ (isset($isYearly) && $isYearly) || Auth::user()->role != 3 ? 'readonly' : '' }}>
                                            </td>

                                            <td
                                                style="padding-left: {{ $sub ? '35px' : '15px' }}; text-align: left; vertical-align: middle;">
                                                <div
                                                    style="display: flex; align-items: center; width: 100%; height: 100%;">
                                                    @if ((!isset($isYearly) || !$isYearly) && Auth::user()->role == 3)
                                                        <i class="fas fa-grip-vertical drag-handle"
                                                            style="cursor: grab; color: #bdc3c7; margin-right: 8px; font-size: 16px; padding: 10px 5px; display: flex; align-items: center; height: 100%;"></i>
                                                    @endif
                                                    <input type="text" class="item-name"
                                                        name="items[{{ $index }}][name]"
                                                        value="{{ $item->name }}"
                                                        style="width: 100%; border: none; font-weight: bold; background: transparent; outline: none; padding: 8px 0; font-size: 15px;"
                                                        {{ (isset($isYearly) && $isYearly) || Auth::user()->role != 3 ? 'readonly' : '' }}>
                                                </div>
                                            </td>

                                            <td>
                                                <input type="text" class="currency-input"
                                                    value="{{ $item->estimated_amount }}"
                                                    {{ (isset($isYearly) && $isYearly) || Auth::user()->role != 3 ? 'readonly' : '' }}>
                                                <input type="hidden" class="est-input"
                                                    name="items[{{ $index }}][estimated_amount]"
                                                    value="{{ $item->estimated_amount }}">
                                            </td>

                                            <td>
                                                <input type="text" class="currency-input"
                                                    value="{{ $item->actual_amount }}"
                                                    {{ (isset($isYearly) && $isYearly) || Auth::user()->role != 3 ? 'readonly' : '' }}>
                                                <input type="hidden" class="act-input"
                                                    name="items[{{ $index }}][actual_amount]"
                                                    value="{{ $item->actual_amount }}">
                                            </td>

                                            <td>
                                                <input type="datetime-local" class="date-input actual-date"
                                                    name="items[{{ $index }}][actual_date]"
                                                    value="{{ $item->actual_date ? $item->actual_date->format('Y-m-d\TH:i') : '' }}"
                                                    {{ (isset($isYearly) && $isYearly) || Auth::user()->role != 3 ? 'readonly' : '' }}>
                                            </td>

                                            @if ((!isset($isYearly) || !$isYearly) && Auth::user()->role == 3)
                                                <td style="text-align: center;">
                                                    <button type="button" class="action-btn btn-save-row"
                                                        title="Lưu dòng này">Lưu</button>
                                                    <button type="button" class="action-btn btn-delete-row"
                                                        title="Xóa dòng này">✕</button>
                                                    <input type="hidden" name="items[{{ $index }}][_delete]"
                                                        class="delete-flag" value="0">
                                                </td>
                                            @else
                                                <td style="text-align: center;"></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="{{ versionResource('assets/js/tool/report.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/support/essential.js') }}" defer></script>
    <script defer>
        var url_reports_update = "{{ route('reports.update', $report->id) }}";
        var url_reports_update_order = "{{ route('reports.update_order') }}";
    </script>
@endpush
