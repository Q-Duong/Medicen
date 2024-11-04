@extends('layouts.default_auth')
@section('admin_content')
    @if (Auth::user()->role == 0)
        <div class="market-updates">
            <div class="col-md-3 market-update-gd">
                <a href="{{ route('customer.index') }}">
                    <div class="market-update-block clr-block-1">
                        <div class="col-md-4 market-update-right">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="col-md-8 market-update-left">
                            <h4>Khách hàng</h4>
                            <h3>{{ $customer }}</h3>
                            <p>Tổng số khách hàng đã đăng ký.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 market-update-gd">
                <a href="{{ URL::to('/all-product') }}">
                    <div class="market-update-block clr-block-3">
                        <div class="col-md-4 market-update-right">
                            <i class="fa fa-usd"></i>
                        </div>
                        <div class="col-md-8 market-update-left">
                            <h4>Dịch vụ</h4>
                            <h3>{{ $service }}</h3>
                            <p>Tổng số dịch vụ đã kinh doanh.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 market-update-gd">
                <a href="{{ route('order.index') }}">
                    <div class="market-update-block clr-block-4">
                        <div class="col-md-4 market-update-right">
                            <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                        </div>
                        <div class="col-md-8 market-update-left">
                            <h4>Đơn hàng</h4>
                            <h3>{{ $order }}</h3>
                            <p>Tổng số đơn hàng đã nhận.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 market-update-gd">
                <a href="{{ route('post.index') }}">
                    <div class="market-update-block clr-block-2">
                        <div class="col-md-4 market-update-right">
                            <i class="fab fa-blogger-b"></i>
                        </div>
                        <div class="col-md-8 market-update-left">
                            <h4>Bài viết</h4>
                            <h3>{{ $post }}</h3>
                            <p>Tổng bài viết có trên web.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                </a>
            </div>
            <div class="clearfix"> </div>
        </div>
    @endif
    <div class="chart_statistic">
        <p class="chart-statistic-title">Thống kê doanh thu</p>
        <div class="chart-statistic-content">
            <div class="filter">
                <div class="filter-title">
                    <p class="filter-title-text">
                        Bộ lọc
                    </p>
                </div>
                <div class="filter-content">
                    <div class="row">
                        <form autocomplete="off">
                            @csrf
                            <div class="col-md-2">
                                <p class="filter-content-title">Từ ngày:</p>
                                <input type="date" name="from_date" class="input-control">
                            </div>
                            <div class="col-md-2">
                                <p class="filter-content-title">Đến ngày:</p>
                                <input type="date" name="to_date" class="input-control">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="primary-btn-filter btn-revenue-by-date">
                                    Lọc ngày
                                </button>
                            </div>
                            <div class="col-md-2">
                                <p class="filter-content-title">Tên đơn vị:</p>
                                <select name="unit_id" class="select-2 unit_id">
                                    @foreach ($getAllUnit as $key => $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="primary-btn-filter btn-unit-revenue">
                                    Lọc đơn vị
                                </button>
                            </div>
                            <div class="col-md-2">
                                <p class="filter-content-title">Lọc theo:</p>
                                <select class="optional-revenue input-control">
                                    <option>--Chọn--</option>
                                    <option value="7ngay">7 ngày qua</option>
                                    <option value="thangtruoc">tháng trước</option>
                                    <option value="thangnay">tháng này</option>
                                    <option value="365ngayqua">365 ngày qua</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="filter-accountant">
                <div class="filter-accountant-title">
                    <p class="filter-accountant-title-text">
                        Báo cáo doanh thu: <span class="filter-text"></span>
                    </p>
                </div>
                <div class="filter-accountant-content">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="filter-accountant-content-block">
                                <div class="filter-accountant-content-title">
                                    Tổng doanh thu:
                                </div>
                                <div class="total"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="filter-accountant-content-block">
                                <div class="filter-accountant-content-title">
                                    Tổng số đơn:
                                </div>
                                <div class="total_orders"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="filter-accountant-content-block">
                                <div class="filter-accountant-content-title">
                                    Lợi nhuận:
                                </div>
                                <div class="total"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="chart">
                <div class="chart-title">
                    <p class="chart-title-text">
                        Biểu đồ doanh thu: <span class="chart-text"></span>
                    </p>
                </div>
                <div id="chart"></div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('backend/js/chart/raphael-min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/chart/morris.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/chart/chart.min.js') }}" defer></script>
    <script type="text/javascript">
        // Revenue Statistics Url
        var url_revenue_statistics_for_the_month = "{{ route('url-revenue-statistics-for-the-month') }}";
        var url_optional_revenue_statistics = "{{ route('url-optional-revenue-statistics') }}";
        var url_revenue_statistics_by_unit = "{{ route('url-revenue-statistics-by-unit') }}";
        var url_revenue_statistics_by_date = "{{ route('url-revenue-statistics-by-date') }}";
    </script>
@endpush
