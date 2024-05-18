@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách đơn hàng
        </div>
        <div class="alert alert-success" style="display:none"></div>
        <div class="filter-accountant">
            <div class="filter-accountant-title">
                <p class="filter-accountant-title-text">
                    Báo cáo doanh thu
                </p>
            </div>
            <div class="filter-accountant-content">
                <div class="row">
                    <div class="col-md-4">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng tiền :
                            </div>
                            <div id="total-price"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng tiền đã thanh toán:
                            </div>
                            <div id="total-amount-paid"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng tiền còn nợ:
                            </div>
                            <div id="total-owe"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Tổng tiền chiết khấu:
                            </div>
                            <div id="total-discount"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-accountant-content-block">
                            <div class="filter-accountant-content-title">
                                Số Cas:
                            </div>
                            <div id="total-quantity"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive table-content">
        </div>
        <div class="export-excel">
            <form action="{{ route('export-excel') }}" method="POST" id="myForm">
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
    <script src="{{ versionResource('backend/js/datatables/nightly.dataTables.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/datatables/api.sum.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/datatables/dataTables-custom.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/tool/accountant.min.js') }}" defer></script>
    <script type="">
         // Accountant Url
         var urlGetAccountant = "{{route('url-get-list-accountant')}}";
        var urlUpdateAccountant = "{{route('url-update-accountant',':id')}}";
        var urlCompleteAccountant = "{{route('url-complete-accountant',':id')}}";
        var urlFilterAccountant = "{{route('url-filter-accountant')}}";
    </script>
@endpush
