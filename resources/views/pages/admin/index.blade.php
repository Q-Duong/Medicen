@extends('layouts.default_auth')
@section('title', 'Thống kê doanh thu - Medicen')
@push('css')
    {{-- Giao diện ở assets/css/support/org-dashboard.css (bản nạp: dashboard.css) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/dashboard.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $dsConfig = [
            'month' => route('statistics.revenue_for_the_month'),
            'optional' => route('statistics.optional_revenue'),
            'unit' => route('statistics.revenue_by_unit'),
            'date' => route('statistics.revenue_by_date'),
        ];
    @endphp
    <div class="ds" id="dashboard" data-config='@json($dsConfig)'>
        <div class="ds-head">
            <div class="ds-head__title">
                <h1>Thống kê doanh thu</h1>
                <p>Doanh thu, lợi nhuận theo ngày chốt đơn (kế toán bấm Hoàn thành). <span class="ds-current">Đang xem: <b class="filter-text">tháng này</b></span></p>
            </div>
            <div class="ds-chips" role="group" aria-label="Khoảng thời gian">
                <button type="button" class="ds-chip is-active" data-opt="month">Tháng này</button>
                <button type="button" class="ds-chip" data-opt="7ngay">7 ngày qua</button>
                <button type="button" class="ds-chip" data-opt="thangtruoc">Tháng trước</button>
                <button type="button" class="ds-chip" data-opt="365ngayqua">365 ngày qua</button>
            </div>
        </div>

        <form class="ds-filters" autocomplete="off" onsubmit="return false">
            @csrf
            <div class="ds-filter">
                <label class="ds-field"><span>Từ ngày</span><input type="date" name="from_date"></label>
                <label class="ds-field"><span>Đến ngày</span><input type="date" name="to_date"></label>
                <button type="button" class="ds-btn ds-btn--dark btn-revenue-by-date">Xem theo ngày</button>
            </div>
            <div class="ds-filter ds-filter--grow">
                <label class="ds-field ds-field--grow"><span>Đơn vị</span>
                    <select name="unit_id" class="select-2 unit_id">
                        @foreach ($getAllUnit as $key => $unit)
                            <option value="{{ $unit->id }}">{{ $unit->unit_abbreviation ?: $unit->unit_name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="button" class="ds-btn ds-btn--dark btn-unit-revenue">Xem theo đơn vị</button>
            </div>
        </form>

        <div class="ds-tiles">
            <div class="ds-tile ds-tile--money"><span>Doanh thu</span><b class="ds-total">—</b></div>
            <div class="ds-tile ds-tile--profit"><span>Lợi nhuận</span><b class="ds-profit">—</b><small class="ds-margin"></small></div>
            <div class="ds-tile"><span>Số đơn</span><b class="ds-orders">—</b></div>
            <div class="ds-tile"><span>Số ca</span><b class="ds-quantity">—</b></div>
        </div>

        <section class="ds-card">
            <div class="ds-card__head">
                <h2>Biểu đồ doanh thu · <span class="chart-text">tháng này</span></h2>
                <div class="ds-legend"><span><i class="ds-dot ds-dot--sales"></i>Doanh thu</span><span><i class="ds-dot ds-dot--profit"></i>Lợi nhuận</span></div>
            </div>
            <div id="chart" class="ds-chart"></div>
            <div class="ds-empty" hidden>Không có dữ liệu trong khoảng này.</div>
        </section>

        @if (Auth::user()->role == 0)
            <h2 class="ds-section">Tổng quan</h2>
            <div class="ds-links">
                <a href="{{ route('order.index') }}" class="ds-link"><i class="fas fa-file-alt"></i><div><b>{{ number_format($order, 0, ',', '.') }}</b><span>Đơn hàng</span></div></a>
                <a href="{{ route('customer.index') }}" class="ds-link"><i class="fas fa-users"></i><div><b>{{ number_format($customer, 0, ',', '.') }}</b><span>Khách hàng</span></div></a>
                <a href="{{ URL::to('/all-product') }}" class="ds-link"><i class="fas fa-briefcase-medical"></i><div><b>{{ number_format($service, 0, ',', '.') }}</b><span>Dịch vụ</span></div></a>
                <a href="{{ route('post.index') }}" class="ds-link"><i class="fas fa-newspaper"></i><div><b>{{ number_format($post, 0, ',', '.') }}</b><span>Bài viết</span></div></a>
            </div>
        @endif
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('backend/js/chart/raphael-min.js') }}"></script>
    <script src="{{ versionResource('backend/js/chart/morris.min.js') }}"></script>
    {{-- Thay cho backend/js/chart/chart.min.js: thêm lợi nhuận, số ca; nguồn ở assets/js/tool/org-dashboard.js --}}
    <script src="{{ versionResource('assets/js/tool/dashboard.js') }}" defer></script>
@endpush
