@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Quản Lý Công Nợ
        </div>
        <div class="alert alert-success" style="display:none"></div>
        <div class="filter-section accountant">
            <div class="row">
                <!-- Block 1: Filter -->
                <div class="col-lg-2 col-md-2 col-sm-6">
                    <div class="filter-tiles">
                        <div class="filter-title">
                            <p class="filter-title-text">Filter</p>
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="tile-content-wrapper">
                            <ul class="filter-content">
                                <li class="filter-content-block">
                                    <select id="year-select" name="year" class="input-control year-filter filter-input">
                                        <option value="all" {{ Session::get('year') == 'all' ? 'selected' : '' }}>All
                                        </option>
                                        @php
                                            $currentYear = \Carbon\Carbon::now()->year;
                                            $startYear = 2023;
                                        @endphp

                                        @for ($i = $startYear; $i <= $currentYear; $i++)
                                            <option value="{{ $i }}"
                                                {{ Session::get('year') == $i ? 'selected' : '' }}>
                                                {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                </li>
                                <li class="filter-content-block">
                                    <select id="type-select" name="type" class="input-control type-filter filter-input">
                                        <option value="all" {{ Session::get('type') == 'all' ? 'selected' : '' }}>All
                                        </option>
                                        <option value="1" {{ Session::get('type') == 1 ? 'selected' : '' }}>X-Ray
                                        </option>
                                        <option value="2" {{ Session::get('type') == 2 ? 'selected' : '' }}>
                                            UltraSound
                                        </option>
                                    </select>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-4 col-sm-6">
                    <div class="filter-tiles">
                        <div class="filter-title">
                            <p class="filter-title-text">Doanh thu</p>
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="tile-content-wrapper">
                            <ul class="filter-content text-ellipsis">
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;Tổng tiền :</div>
                                    <div class="filter-content-details" id="total-price"></div>
                                </li>
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;Đã thanh toán :</div>
                                    <div class="filter-content-details" id="total-amount-paid"></div>
                                </li>
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;Còn nợ :</div>
                                    <div class="filter-content-details" id="total-owe"></div>
                                </li>
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;Chiết khấu :</div>
                                    <div class="filter-content-details" id="total-discount"></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-4 col-sm-6">
                    <div class="filter-tiles">
                        <div class="filter-title">
                            <p class="filter-title-text">Phim</p>
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="tile-content-wrapper">
                            <ul class="filter-content">
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;Số Cas :</div>
                                    <div class="filter-content-details" id="total-quantity"></div>
                                </li>
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;35 X 43 :</div>
                                    <div class="filter-content-details" id="total-35"></div>
                                </li>
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;Polime :</div>
                                    <div class="filter-content-details" id="total-polime"></div>
                                </li>
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;8 X 10 :</div>
                                    <div class="filter-content-details" id="total-8"></div>
                                </li>
                                <li class="filter-content-block">
                                    <div class="filter-content-title">•&nbsp;10 X 12 :</div>
                                    <div class="filter-content-details" id="total-10"></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 col-md-2 col-sm-6">
                    <div class="filter-tiles">
                        <div class="filter-title">
                            <p class="filter-title-text">Export</p>
                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="tile-content-wrapper">
                            <form action="{{ route('export.excel') }}" method="POST" id="myForm">
                                @csrf
                                <ul class="filter-content">
                                    <li class="filter-content-block">
                                        <select name="year" class="input-control select-year">
                                            @php
                                                $currentYear = \Carbon\Carbon::now()->year;
                                                $startYear = 2023;
                                            @endphp
                                            <option value="" selected disabled>Year</option>
                                            @for ($i = $startYear; $i <= $currentYear; $i++)
                                                <option value="{{ $i }}"
                                                    {{ Session::get('year') == $i ? 'selected' : '' }}>
                                                    {{ $i }}
                                                </option>
                                            @endfor
                                        </select>
                                    </li>
                                    <li class="filter-content-block">
                                        <select name="month" class="input-control select-month-details">
                                            <option value="" selected disabled>Month</option>
                                            <option value="all">All</option>
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
                                    </li>
                                    <li class="filter-content-block">
                                        <select  name="type" class="input-control">
                                            <option value="all">All</option>
                                            <option value="1">X-Ray</option>
                                            <option value="2">UltraSound</option>
                                        </select>
                                    </li>
                                    <button type="submit" class="primary-btn-filter">Export</button>
                                </ul>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive table-content">
            <div id="table-scroll" class="table-scroll">
                <table class="table">
                    <thead>
                        <tr class="section-title">
                            <th class="sticky-col first-col">
                                STT
                            </th>
                            <th class="sticky-col second-col">
                                <x-excel-filter field="order_id" label="Mã ĐH" />
                            </th>
                            <th class="sticky-col third-col">
                                <x-excel-filter field="accountant_month" label="Tháng" />
                            </th>
                            <th class="sticky-col fourth-col">
                                <x-excel-filter field="ord_start_day" label="Ngày chụp" />
                            </th>
                            <th class="sticky-col fifth-col">
                                <x-excel-filter field="car_name" label="Xe" />
                            </th>
                            <th class="sticky-col six-col">
                                <x-excel-filter field="accountant_distance" label="Km" />
                            </th>
                            <th class="sticky-col seven-col">
                                <x-excel-filter field="unit_name" label="Đơn vị" />
                            </th>
                            <th class="sticky-col eighth-col">
                                <x-excel-filter field="ord_cty_name" label="Công ty" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_deadline" label="THCN" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_number" label="Số HĐ" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_date" label="Ngày HĐ" />
                            </th>
                            <th>
                                <x-excel-filter field="order_vat" label="VAT" />
                            </th>
                            <th>
                                <x-excel-filter field="order_quantity" label="Số lượng" />
                            </th>
                            <th>
                                <x-excel-filter field="order_cost" label="Đơn giá" />
                            </th>
                            <th>
                                <x-excel-filter field="order_price" label="Thành tiền" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_status" label="Trạng thái thanh toán" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_day_payment" label="Ngày TT" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_method" label="Hình thức" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_amount_paid" label="Số tiền đã thanh toán" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_owe" label="Còn nợ" />
                            </th>
                            <th>
                                <x-excel-filter field="order_percent_discount" label="% CK" />
                            </th>
                            <th>
                                <x-excel-filter field="order_discount" label="Thành tiền CK" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_discount_day" label="Ngày trích CK" />
                            </th>
                            <th>
                                <x-excel-filter field="order_profit" label="Lợi nhuận" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_doctor_read" label="BS Đọc" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_doctor_date_payment" label="NTT" />
                            </th>
                            <th>
                                <x-excel-filter field="ord_form" label="HT in Phim" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_35X43" label="35x43" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_polime" label="Polime" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_8X10" label="8x10" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_10X12" label="10x12" />
                            </th>
                            <th>
                                <x-excel-filter field="accountant_note" label="Ghi chú" />
                            </th>
                            <th>
                                <x-excel-filter field="ord_note" label="Ghi chú Sales" />
                            </th>
                            <th>
                                <x-excel-filter field="status_id" label="Trạng thái ĐH" />
                            </th>
                        </tr>
                    </thead>
                    <tbody class="tbody-content">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script type="">
    var url_get_accountant = "{{ route('accountant_result.get') }}";
    var url_filter_options = "{{ route('accountant_result.filter_options') }}";
</script>
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
    <script src="{{ versionResource('assets/js/tool/accountant.js') }}"></script>
@endpush
