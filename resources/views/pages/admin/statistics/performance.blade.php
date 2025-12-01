<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation Letter</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            width: 100%;
            height: 100%;
            /* background: #f5f5f7; */
            margin: 0;
        }

        .main {
            background: #ffffff;
            margin: 0 auto;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .img-logo {
            height: 50px;
            width: 50px;
            float: left;
        }

        .title {
            text-align: center;
            color: #346fdb;
        }

        .sub-title {
            color: #346fdb;
            margin: 0 0 2px 0;
        }

        .title-center {
            text-align: center;
            color: #346fdb;
            margin-bottom: 2px;
        }


        .title-left {
            text-align: left;
            color: #346fdb;
            margin-bottom: 0;
        }

        .content {
            width: 90%;
            margin: 0 auto;
        }

        .list-item {
            margin: 0;
        }

        .list-item li {
            font-size: 13px;
        }

        .type-1 {
            color: #27c24c;
        }

        .type-2 {
            color: #FCB322;
        }

        .type-3 {
            color: #e53637;
        }

        .description-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 1px solid black;
        }

        .description-table th,
        .description-table td {
            border: 1px solid black;
            padding: 3px;
            font-size: 11pt;
        }

        .description-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .description-table td {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="main">
        <div>
            <h2 class="title">
                BÁO CÁO HIỆU SUẤT HOẠT ĐỘNG THÁNG 11
            </h2>
        </div>
        <div class="description">
            <h5 class="title-left">Chú thích</h5>
            <div class="content">
                <ul class="list-item">
                    <li><strong class="type-1">Màu Xanh (Đạt/Tốt): </strong>Từ 90% - 100%</li>
                    <li><strong class="type-2">Màu Vàng (Cảnh báo): </strong>Từ 80% - 89%</li>
                    <li><strong class="type-3">Màu Đỏ (Nguy hiểm): </strong>Dưới 79%</li>
                </ul>
            </div>
        </div>
        <div class="section-1">
            <h4 class="sub-title">TỔNG QUAN THÁNG 11</h4>
            <div class="content">
                <ul class="list-item">
                    <li><strong>KPI: <span
                                class="{{ colorKPI($totalPerformance['kpi']) }}">{{ $totalPerformance['kpi'] }}%</span></strong>
                    </li>
                    <li><strong>Tổng số đơn:</strong> {{ $orders->count() }}</li>
                    <li><strong>Tổng số đơn đã hoàn thành:</strong> {{ $totalPerformance['total'] }}</li>
                    <li><strong>Tổng số đơn đã trễ deadline:</strong> {{ $totalPerformance['missed'] }}</li>
                </ul>
            </div>

            <div class="section-2">
                <h4 class="sub-title">CHI TIẾT 3 BỘ PHẬN</h4>
                <div>
                    <h5 class="title-center {{ colorKPI($totalPerformanceSales['kpi']) }}">1. BỘ PHẬN SALES</h5>
                    <div class="content">
                        <ul class="list-item">
                            <li><strong>KPI: <span
                                        class="{{ colorKPI($totalPerformanceSales['kpi']) }}">{{ $totalPerformanceSales['kpi'] }}%</span></strong>
                            </li>
                            <li><strong>Tổng số đơn đã vi phạm:</strong> {{ $totalPerformanceSales['missed'] }}</li>
                            <li><strong>Những đơn đã vi phạm:</strong>
                                <table class="description-table">
                                    <thead>
                                        <tr>
                                            <th>Stt</th>
                                            <th>Đơn vị</th>
                                            <th>Ngày</th>
                                            <th>Mô tả vi phạm</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($totalPerformanceSales['data'] as $key => $result)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $result->unit_abbreviation }}</td>
                                                <td>Ngày
                                                    {{ Carbon\Carbon::parse($result->ord_start_day)->format('d/m/Y') }}
                                                </td>
                                                <td>{{ $result->description }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h5 class="title-center">2. BỘ PHẬN KTV</h5>
                    @foreach ($totalPerformanceTechnicians as $key => $technician)
                        <div class="content">
                            <h5 class="title-left {{ colorKPI($technician['kpi']) }}">{{ $technician['name'] }}</h5>
                            <ul class="list-item">
                                <li><strong>KPI:
                                        <span
                                            class="{{ colorKPI($technician['kpi']) }}">{{ $technician['kpi'] }}%</span>
                                    </strong>
                                </li>
                                <li><strong>Tổng số đơn đã hoàn thành:</strong> {{ $technician['total'] }}
                                </li>
                                <li><strong>Tổng số đơn đã trễ deadline:</strong> {{ $technician['missed'] }}
                                </li>
                                @if ($technician['data']->count() >= 1)
                                    <li><strong>Những ngày đã trễ deadline:</strong>
                                        <table class="description-table">
                                            <thead>
                                                <tr>
                                                    <th>Stt</th>
                                                    <th>Đơn vị</th>
                                                    <th>Ngày</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($technician['data'] as $key => $technicianData)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $technicianData->unit_abbreviation }}</td>
                                                        <td>Ngày
                                                            {{ Carbon\Carbon::parse($technicianData->ord_start_day)->format('d/m/Y') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endforeach
                </div>

                <div>
                    <h5 class="title-center {{ colorKPI($totalPerformanceResults['kpi']) }}">3. BỘ PHẬN KẾT QUẢ</h5>
                    <div class="content">
                        <ul class="list-item">
                            <li><strong>KPI: <span
                                        class="{{ colorKPI($totalPerformanceResults['kpi']) }}">{{ $totalPerformanceResults['kpi'] }}%</span>
                                </strong>
                            </li>
                            <li><strong>Tổng số đơn đã hoàn thành:</strong>
                                {{ $totalPerformanceResults['total'] }}</li>
                            <li><strong>Tổng số đơn đã trễ deadline:</strong>
                                {{ $totalPerformanceResults['missed'] }}</li>
                            <li><strong>Những ngày đã trễ deadline:</strong>
                                <table class="description-table">
                                    <thead>
                                        <tr>
                                            <th>Stt</th>
                                            <th>Đơn vị</th>
                                            <th>Ngày</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($totalPerformanceResults['data'] as $key => $result)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $result->unit_abbreviation }}</td>
                                                <td>Ngày
                                                    {{ Carbon\Carbon::parse($result->ord_start_day)->format('d/m/Y') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="margin: 0 auto; width: 600px; padding: 40px 0">
        <div style="text-align: center; margin-top: 25px ; font-size: 13px">Bản quyền © Medicen.
            {{ \Carbon\Carbon::now()->year }} Bảo lưu mọi quyền.</div>
        <div style="text-align: center; margin-top: 5px ; font-size: 13px">59 Đường số 9, KDC Nam Sài Gòn - Thế Kỷ
            21,
            Xã Bình Hưng, Huyện Bình Chánh, TP.Hồ Chí Minh.</div>
    </div>
</body>

</html>
