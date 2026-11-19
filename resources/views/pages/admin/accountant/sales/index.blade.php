@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info acc-page">
        {{-- Cùng giao diện trang Công nợ Kế toán (accountant/index), nhưng chỉ xem và lọc: không sửa ô, không có Hoàn thành.
             Giữ id / class cũ vì accountant.js dùng (year-filter, type-filter, btn-export-*, total-*). --}}
        <div class="acc-head">
            <div class="acc-head__title">
                <h1>Quản lý công nợ</h1>
                <span class="acc-readonly-tag" title="Trang Sales chỉ xem và lọc, Kế toán nhập ở trang công nợ Kế toán">Chỉ xem</span>
            </div>
            <div class="acc-head__actions">
                <label class="acc-field">
                    <span>Năm</span>
                    <select id="year-select" name="year" class="year-filter filter-input">
                        <option value="all" {{ Session::get('year') == 'all' ? 'selected' : '' }}>Tất cả</option>
                        @php
                            $currentYear = \Carbon\Carbon::now()->year;
                            $startYear = 2023;
                        @endphp
                        @for ($i = $currentYear; $i >= $startYear; $i--)
                            <option value="{{ $i }}" {{ Session::get('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </label>
                <label class="acc-field">
                    <span>Loại</span>
                    <select id="type-select" name="type" class="type-filter filter-input">
                        <option value="all" {{ Session::get('type') == 'all' ? 'selected' : '' }}>Tất cả</option>
                        <option value="1" {{ Session::get('type') == 1 ? 'selected' : '' }}>X-Quang</option>
                        <option value="2" {{ Session::get('type') == 2 ? 'selected' : '' }}>Siêu âm</option>
                    </select>
                </label>
                <label class="acc-field">
                    <span>Đơn</span>
                    {{-- Lọc đơn phụ thu (class type-filter để accountant.js gửi kèm khi tải bảng) --}}
                    <select id="surcharge-select" name="surcharge" class="type-filter filter-input">
                        <option value="all">Tất cả</option>
                        <option value="1">Chỉ đơn phụ thu</option>
                        <option value="0">Không gồm phụ thu</option>
                    </select>
                </label>
                <button type="button" class="acc-btn btn-export-excel">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel
                </button>
                <button type="button" class="acc-btn btn-export-misa">
                    <i class="fa-solid fa-file-export"></i> Xuất Misa
                </button>
            </div>
        </div>
        <div class="alert alert-success" style="display:none"></div>
        @include('pages.admin.accountant._credit_banner')

        {{-- Tổng theo bộ lọc đang dùng (accountant.js điền số vào các id total-*) --}}
        <div class="acc-stats">
            <section class="acc-card acc-card--money">
                <h2 class="acc-card__title">Doanh thu</h2>
                <dl class="acc-card__grid">
                    <div class="acc-stat"><dt>Tổng tiền</dt><dd id="total-price"></dd></div>
                    <div class="acc-stat acc-stat--ok"><dt>Đã thanh toán</dt><dd id="total-amount-paid"></dd></div>
                    <div class="acc-stat acc-stat--bad"><dt>Còn nợ</dt><dd id="total-owe"></dd></div>
                    <div class="acc-stat"><dt>Chiết khấu</dt><dd id="total-discount"></dd></div>
                </dl>
            </section>
            <section class="acc-card">
                <h2 class="acc-card__title">Số lượng</h2>
                <dl class="acc-card__grid">
                    <div class="acc-stat"><dt>Số cas</dt><dd id="total-quantity"></dd></div>
                    <div class="acc-stat"><dt>Số cas KTV</dt><dd id="total-quantity-draft"></dd></div>
                    <div class="acc-stat"><dt>Số cas TX phụ</dt><dd id="total-driver-assistance"></dd></div>
                    <div class="acc-stat"><dt>Tổng số đơn</dt><dd id="total-rows"></dd></div>
                </dl>
            </section>
            <section class="acc-card acc-card--film">
                <h2 class="acc-card__title">Phim</h2>
                <dl class="acc-card__grid">
                    <div class="acc-stat"><dt>35 × 43</dt><dd id="total-35"></dd></div>
                    <div class="acc-stat"><dt>Polime</dt><dd id="total-polime"></dd></div>
                    <div class="acc-stat"><dt>8 × 10</dt><dd id="total-8"></dd></div>
                    <div class="acc-stat"><dt>10 × 12</dt><dd id="total-10"></dd></div>
                </dl>
            </section>
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
                                <x-excel-filter field="unit_abbreviation" label="Đơn vị" />
                            </th>
                            <th class="sticky-col eighth-col">
                                <x-excel-filter field="ord_cty_name" label="Công ty" />
                            </th>
                            <th class="acc-group-cell">
                                <x-excel-filter field="order_group" label="Nhóm" />
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
                                <x-excel-filter field="car_ktv_name_1" label="KTV" />
                            </th>
                            <th>
                                <x-excel-filter field="car_ktv_name_2" label="KTV 2" />
                            </th>
                            <th>
                                <x-excel-filter field="order_quantity_draft" label="Số cas KTV" />
                            </th>
                            <th>
                                <x-excel-filter field="car_driver_name" label="Tài xế" />
                            </th>
                            <th>
                                Số cas TX phụ
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
                            {{-- Trang chỉ xem: không có cột Thao tác (dòng không có nút Hoàn thành) --}}
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
    var url_get_accountant = "{{ route('accountant_sales.get') }}";
    var url_filter_options = "{{ route('accountant_sales.filter_options') }}";
    var url_update_accountant = "{{ route('accountant_sales.update') }}";
    var url_csrf_token = "{{ route('csrf.token') }}";
</script>
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
    <script src="{{ versionResource('assets/js/tool/accountant.js') }}"></script>
@endpush
