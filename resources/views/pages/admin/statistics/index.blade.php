@extends('layouts.default_auth')
@section('admin_content')
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
                        <form role="form" action="{{ route('statistics.performance_analysis') }}" method="post">
                            @csrf
                            <div class="col-md-4">
                                <div class="col-md-6">
                                    <p class="filter-content-title">Chọn năm</p>
                                    <select name="year" class="input-control select-year">
                                        @for ($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i + 2023 }}"
                                                {{ Carbon\Carbon::now()->format('Y') == $i + 2023 ? 'selected' : '' }}>
                                                {{ $i + 2023 }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <p class="filter-content-title">Chọn tháng</p>
                                    <select name="month" class="input-control select-month-details">
                                        <option disabled="" class="define-month-details">Chọn tháng</option>
                                        <option value="January" {{ Carbon\Carbon::now()->format('F') == 'January' ? 'selected' : '' }}>1</option>
                                        <option value="February" {{ Carbon\Carbon::now()->format('F') == 'February' ? 'selected' : '' }}>2</option>
                                        <option value="March" {{ Carbon\Carbon::now()->format('F') == 'March' ? 'selected' : '' }}>3</option>
                                        <option value="April" {{ Carbon\Carbon::now()->format('F') == 'April' ? 'selected' : '' }}>4</option>
                                        <option value="May" {{ Carbon\Carbon::now()->format('F') == 'May' ? 'selected' : '' }}>5</option>
                                        <option value="June" {{ Carbon\Carbon::now()->format('F') == 'June' ? 'selected' : '' }}>6</option>
                                        <option value="July" {{ Carbon\Carbon::now()->format('F') == 'July' ? 'selected' : '' }}>7</option>
                                        <option value="August" {{ Carbon\Carbon::now()->format('F') == 'August' ? 'selected' : '' }}>8</option>
                                        <option value="September" {{ Carbon\Carbon::now()->format('F') == 'September' ? 'selected' : '' }}>9</option>
                                        <option value="October" {{ Carbon\Carbon::now()->format('F') == 'October' ? 'selected' : '' }}>10</option>
                                        <option value="November" {{ Carbon\Carbon::now()->format('F') == 'November' ? 'selected' : '' }}>11</option>
                                        <option value="December" {{ Carbon\Carbon::now()->format('F') == 'December' ? 'selected' : '' }}>12</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="primary-btn-submit button-submit">Performance Analysis
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                {{-- <form role="form" action="{{ route('statistics.performance_analysis') }}" method="post">
                    @csrf
                    <button type="submit" class="primary-btn-submit button-submit">Performance Analysis
                    </button>
                </form> --}}
            </div>
        </div>
    </div>
@endsection
@push('js')
    </script>
@endpush
