@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách đơn hàng
        </div>
        <div class="alert alert-success" style="display:none"></div>
        <div class="filter-accountant">
            <div class="filter-accountant-title">
                <p class="filter-accountant-title-text">
                    Phim
                </p>
            </div>
            <div class="filter-accountant-content">
                <div class="row">
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng 35 X 43 :
                            </div>
                            <div id="total-35"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng Polime :
                            </div>
                            <div id="total-polime"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng 8 X 10:
                            </div>
                            <div id="total-8"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng 10 X 12:
                            </div>
                            <div id="total-10"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng Bao phim:
                            </div>
                            <div id="total-pack"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="filter-accountant">
            <div class="filter-accountant-title">
                <p class="filter-accountant-title-text">
                    Filter
                </p>
            </div>
            <div class="filter-accountant-content">
                <div class="row">
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="cd-intro-month">
                                <label for="schedule-label">Year</label>
                                <select id="schedule-label" class="input-control year-filter">
                                    <option value="all" {{ Session::get('year') == 'all' ? 'selected' : '' }}>All
                                    </option>
                                    @for ($i = 0; $i <= 10; $i++)
                                        <option value="{{ $i + 2023 }}"
                                            {{ Session::get('year') == $i + 2023 ? 'selected' : '' }}>
                                            {{ $i + 2023 }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="cd-intro-month">
                                <label for="schedule-label">Type</label>
                                <select id="schedule-label" data-select="type" class="input-control type-filter">
                                    <option value="all" {{ Session::get('type') == 'all' ? 'selected' : '' }}>All
                                    </option>
                                    <option value="1" {{ Session::get('type') == 1 ? 'selected' : '' }}>X-Ray</option>
                                    <option value="2" {{ Session::get('type') == 2 ? 'selected' : '' }}>UltraSound
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="clear-filter hidden">
                                <label for="schedule-label">Filter</label>
                                <button type="submit" class="primary-btn-filter btn-clear-filter">Clear filter</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive table-content">
        </div>
        <div class="export-excel">
            <form action="{{ route('export.excel') }}" method="POST" id="myForm">
                @csrf
                <div class="col-md-4">
                    <p class="export-excel-title">Xuất file Excel Công Nợ</p>
                    <div class="col-md-6">
                        <select name="year" class="input-control select-year">
                            @for ($i = 0; $i <= 10; $i++)
                                <option value="{{ $i + 2023 }}">{{ $i + 2023 }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select name="month" class="input-control select-month-details">
                            <option disabled="" class="define-month-details">Chọn tháng</option>
                            <option value="January">1</option>
                            <option value="February">2</option>
                            <option value="March">3</option>
                            <option value="April">4</option>
                            <option value="May">5</option>
                            <option value="June">6</option>
                            <option value="July">7</option>
                            <option value="August">8</option>
                            <option value="September">9</option>
                            <option value="October">10</option>
                            <option value="November">11</option>
                            <option value="December">12</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="primary-btn-filter">Xuất file Excel</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    <script type="">
    var url_get_accountant = "{{ route('accountant_result.get') }}";
    var url_filter_accountant = "{{ route('accountant_result.filter') }}";
</script>
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
    <script src="{{ versionResource('backend/js/tool/select2.min.js') }}"></script>
    <script src="{{ versionResource('assets/js/tool/accountant.js') }}"></script>
@endpush
