<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Báo Cáo Thu Chi</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; text-transform: uppercase; font-size: 16px; }

        /* Bảng layout chia 2 cột (Vô hình tuyệt đối) */
        .summary-wrapper-table { width: 100%; border: none !important; margin-bottom: 15px; border-collapse: collapse; }
        .summary-wrapper-table > tbody > tr > td, .summary-wrapper-table > tr > td { border: none !important; padding: 0; vertical-align: top; }

        /* --- BÍ QUYẾT BO GÓC BẢNG CHO DOMPDF --- */
        .table-box {
            border: 1px solid #bdc3c7; /* Viền ngoài màu xám */
            border-radius: 10px;       /* Độ bo góc */
            overflow: hidden;          /* Giấu các góc nhọn bị dư */
            margin-bottom: 15px;
        }
        
        .table-box table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 0; 
            border-style: hidden; /* Xóa viền ngoài cùng của table để dùng viền của table-box */
        }

        th, td { border: 1px solid #dee2e6; padding: 5px 6px; text-align: right; }
        th { background-color: #f8f9fa; font-weight: bold; text-align: center; }
        td:nth-child(1) { text-align: left; }

        /* Xử lý bo góc màu nền tiêu đề để không bị lem ra ngoài khung */
        .table-box thead tr:first-child th:first-child { border-top-left-radius: 9px; }
        .table-box thead tr:first-child th:last-child { border-top-right-radius: 9px; }
        
        /* Màu sắc Bảng Thu - Chi */
        .table-thu th { background-color: #bdf2c9 !important; }
        .table-chi th { background-color: #ffcccc !important; }
        .grand-total td { background-color: #dff9fb !important; color: #23bf65; font-weight: bold; font-size: 12px; }
        .table-chi .grand-total td { color: #ea3e2c; }
        .summary-row td:nth-child(1) { font-weight: bold; }

        .balance-title th { background-color: #dff9fb !important; color: #3498db; padding: 8px; text-align: center; font-size: 13px;}
        .balance-value td { font-weight: bold; font-size: 14px; text-align: center; }

        .detail-table thead th { background-color: #5b9de9; color: #fff; text-align: center; }
        .group-header td { background: #ecf0f1; font-weight: bold; text-transform: uppercase; font-size: 12px; }
        .sub-header { background-color: #fafafa; color: #2c3e50; font-weight: bold; padding-left: 20px !important; }
        
        .total-thu-row td { background-color: #bdf2c9 !important; font-weight: bold; color: #000; border-top: 2px solid #757474; border-bottom: 2px solid #757474; }
        .total-chi-row td { background-color: #ffcccc !important; font-weight: bold; color: #000; border-top: 2px solid #757474; border-bottom: 2px solid #757474; }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
    </style>
</head>
<body>
    @php
        $thuGroups = ['Tiền hiện có (SDĐK)', 'Thu chuyển nhượng', 'CT', 'XQ (số dự thu trong tháng)'];
        $chiGroups = ['Chi tiền vay Bank', 'Chi DV', 'Chi cố định', 'Chi nộp thuế', 'Chi trả NCC', 'Chi mượn cá nhân', 'Chi khác'];
    @endphp

    <h2>BÁO CÁO DỰ TOÁN THU CHI {{ $month === 'all' ? 'NĂM ' . $year : 'THÁNG ' . $month . '/' . $year }}</h2>

    <!-- BẢNG TÓM TẮT -->
    <table class="summary-wrapper-table">
        <tr>
            <td style="width: 49%; padding-right: 1%;">
                <!-- Bọc vào table-box -->
                <div class="table-box">
                    <table class="table-thu">
                        <thead>
                            <tr>
                                <th>Danh mục Thu</th>
                                <th>Số phải thu</th>
                                <th>Đã thu</th>
                                <th>Còn lại</th>
                            </tr>
                            <tr class="grand-total">
                                <td class="text-left">Tổng thu</td>
                                <td class="text-right">{{ number_format($totalThuEst ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalThuAct ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format(($totalThuEst ?? 0) - ($totalThuAct ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($thuGroups as $group)
                                @php
                                    $est = $report->items->where('summary_group', $group)->sum('estimated_amount');
                                    $act = $report->items->where('summary_group', $group)->sum('actual_amount');
                                @endphp
                                <tr class="summary-row">
                                    <td class="text-left">{{ $group }}</td>
                                    <td class="text-right">{{ number_format($est, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($act, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($est - $act, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach

                            <tr>
                                <td style="font-style: italic; font-weight: bold; color: #3498db;" class="text-left">- Tổng nợ X-Quang</td>
                                <td colspan="3" class="text-right" style="font-style: italic; font-weight: bold; color: #3498db;">
                                    {{ number_format($accStats->total_price ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-style: italic; font-weight: bold; color: #e67e22;" class="text-left">- Tổng đã xuất HD</td>
                                <td colspan="3" class="text-right" style="font-style: italic; font-weight: bold; color: #e67e22;">
                                    {{ number_format($accStats->total_da_xuat_hd ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-style: italic; font-weight: bold; color: #c0392b;" class="text-left">- Tổng chưa xuất HD</td>
                                <td colspan="3" class="text-right" style="font-style: italic; font-weight: bold; color: #c0392b;">
                                    {{ number_format($accStats->total_chua_xuat_hd ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>

            <td style="width: 49%; padding-left: 1%;">
                <!-- Bọc vào table-box -->
                <div class="table-box">
                    <table class="table-chi">
                        <thead>
                            <tr>
                                <th>Danh mục Chi</th>
                                <th>Cộng chi</th>
                                <th>Đã chi</th>
                                <th>Chưa chi</th>
                            </tr>
                            <tr class="grand-total">
                                <td class="text-left">Tổng chi</td>
                                <td class="text-right">{{ number_format($totalChiEst ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalChiAct ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format(($totalChiEst ?? 0) - ($totalChiAct ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chiGroups as $group)
                                @php
                                    $est = $report->items->where('summary_group', $group)->sum('estimated_amount');
                                    $act = $report->items->where('summary_group', $group)->sum('actual_amount');
                                @endphp
                                <tr class="summary-row">
                                    <td class="text-left">{{ $group }}</td>
                                    <td class="text-right">{{ number_format($est, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($act, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($est - $act, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- BẢNG CÂN ĐỐI -->
    <div class="table-box">
        <table>
            <thead>
                <tr class="balance-title">
                    <th style="width: 50%;">CÂN ĐỐI DÒNG TIỀN (THU - CHI)</th>
                    <th style="width: 50%;">Thặng Dư Dự Kiến</th>
                </tr>
            </thead>
            <tbody>
                <tr class="balance-value">
                    <td style="color: #2c3e50;">LỢI NHUẬN / THẶNG DƯ</td>
                    <td style="color: #e74c3c;">{{ number_format($balanceEst ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- BẢNG CHI TIẾT -->
    <div class="table-box">
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="12%">Ngày dự kiến</th>
                    <th width="38%">Hạng mục chi tiết</th>
                    <th width="18%">Số tiền (Dự thu/chi)</th>
                    <th width="18%">Số tiền (Đã thu/chi)</th>
                    <th width="14%">Ngày thực tế</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentMainGroup = '';
                    $currentSubGroup = '';
                    $currentType = '';

                    $mainHeaders = [
                        'Tiền hiện có (SDĐK)' => 'SỐ DƯ ĐẦU KỲ', 'CT' => 'I. THU CỐ ĐỊNH', 'XQ (số dự thu trong tháng)' => 'I. THU CỐ ĐỊNH',
                        'Thu chuyển nhượng' => 'II. THU CHUYỂN NHƯỢNG', 'Chi tiền vay Bank' => 'I. CHI TRẢ TIỀN VAY',
                        'Chi DV' => 'II. CHI DỊCH VỤ / CÁC KHOẢN CỐ ĐỊNH', 'Chi cố định' => 'II. CHI DỊCH VỤ / CÁC KHOẢN CỐ ĐỊNH',
                        'Chi nộp thuế' => 'II. CHI DỊCH VỤ / CÁC KHOẢN CỐ ĐỊNH', 'Chi trả NCC' => 'III. CHI TRẢ NỢ NCC',
                        'Chi mượn cá nhân' => 'IV. CHI TIỀN MƯỢN CÁ NHÂN', 'Chi khác' => 'V. CHI KHÁC',
                    ];
                    $subHeaders = [
                        'Chi DV' => '1. Chi các khoản phí DV', 'Chi cố định' => '2. Chi các khoản phí cố định',
                        'Chi nộp thuế' => '3. Chi nộp NSNN', 'Chi trả NCC' => '4. Chi trả nợ NCC',
                        'Chi mượn cá nhân' => '5. Chi tiền mượn cá nhân', 'Chi khác' => '6. Chi khác',
                    ];
                    $groupOrder = [
                        'Tiền hiện có (SDĐK)', 'CT', 'XQ (số dự thu trong tháng)', 'Thu chuyển nhượng',
                        'Chi tiền vay Bank', 'Chi DV', 'Chi cố định', 'Chi nộp thuế',
                        'Chi trả NCC', 'Chi mượn cá nhân', 'Chi khác',
                    ];

                    $orderedItems = $report->items->sortBy(function ($item) use ($groupOrder) {
                        $pos = array_search($item->summary_group, $groupOrder);
                        return $pos === false ? 999 : $pos;
                    })->values();
                @endphp

                @foreach ($orderedItems as $index => $item)
                    @php
                        $main = $mainHeaders[$item->summary_group] ?? $item->summary_group;
                        $sub = $subHeaders[$item->summary_group] ?? '';
                    @endphp

                    {{-- Dòng Tổng Thu / Tổng Chi --}}
                    @if ($currentType !== $item->type)
                        @if ($item->type == 'thu')
                            <tr class="total-thu-row">
                                <td class="text-center">THU</td>
                                <td class="text-center"><em>Tổng thu</em></td>
                                <td class="text-right">{{ number_format($totalThuEst ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalThuAct ?? 0, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        @elseif($item->type == 'chi')
                            <tr class="total-chi-row">
                                <td class="text-center">CHI</td>
                                <td class="text-center"><em>Tổng Chi</em></td>
                                <td class="text-right">{{ number_format($totalChiEst ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalChiAct ?? 0, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        @endif
                        @php $currentType = $item->type; @endphp
                    @endif

                    {{-- Tiêu đề Nhóm Lớn --}}
                    @if ($currentMainGroup !== $main)
                        @php
                            $mainEst = $report->items->filter(function($i) use ($mainHeaders, $main) {
                                return ($mainHeaders[$i->summary_group] ?? $i->summary_group) === $main;
                            })->sum('estimated_amount');
                            $mainAct = $report->items->filter(function($i) use ($mainHeaders, $main) {
                                return ($mainHeaders[$i->summary_group] ?? $i->summary_group) === $main;
                            })->sum('actual_amount');
                        @endphp
                        <tr class="group-header">
                            <td colspan="2" class="text-left">{{ $main }}</td>
                            <td class="text-right" style="color: #23bf65;">{{ number_format($mainEst, 0, ',', '.') }}</td>
                            <td class="text-right" style="color: #3498db;">{{ number_format($mainAct, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                        @php $currentMainGroup = $main; @endphp
                    @endif

                    {{-- Tiêu đề Nhóm Nhỏ --}}
                    @if ($sub && $currentSubGroup !== $sub)
                        <tr>
                            <td colspan="5" class="sub-header text-left">{{ $sub }}</td>
                        </tr>
                        @php $currentSubGroup = $sub; @endphp
                    @endif

                    {{-- Dòng Dữ Liệu --}}
                    <tr>
                        <td class="text-center">
                            {{ $item->expected_date ? $item->expected_date->format('d/m/Y') : '' }}
                        </td>
                        <td class="text-left" style="padding-left: {{ $sub ? '35px' : '15px' }};">
                            - {{ $item->name }}
                        </td>
                        <td class="text-right">
                            {{ number_format($item->estimated_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-right">
                            {{ number_format($item->actual_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            {{ $item->actual_date ? $item->actual_date->format('d/m/Y H:i') : '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</body>
</html>