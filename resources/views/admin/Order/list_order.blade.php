@extends('admin_layout')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách đơn hàng
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light display responsive nowrap" style="width:100%" id="myTable">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>TG đăng ký</th>
                        <th>Đơn vị thuê xe:</th>
                        <th>Mã ĐV:</th>
                        <th>Số lượng:</th>
                        <th>Tổng tiền:</th>
                        <th>Trạng thái:</th>
                        <th>Ngày chụp:</th>
                        <th>Bộ phận chụp:</th>
                        <th>Quản lý</th>
                        <th>Quản lý lịch</th>
                        <th>Xem đơn hàng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getAllOrder as $key => $order)
                        <tr>
                            <td>{{ $order->order_id }}</td>
                            <td>{{ date('d/m/Y', strtotime($order->created_at)) }}</td>
                            <td>{{ $order->unit_name }}</td>
                            <td>{{ $order->unit_code }}</td>
                            <td>{{ $order->order_quantity }}</td>
                            <td>{{ number_format($order->order_price, 0, ',', '.') }}₫</td>
                            <td>
                                @if ($order->order_status == 0)
                                    <span style="color: #27c24c;">Đơn hàng mới</span>
                                @elseif($order->order_status == 1)
                                    <span style="color: #FCB322;">Đang xử lý</span>
                                @elseif($order->order_status == 2)
                                    <span style="color: #c037df;">Đã cập nhật số Cas thực tế</span>
                                @elseif($order->order_status == 3)
                                    <span style="color: #0071e3;">Đã xử lý</span>
                                @elseif($order->order_status == 4)
                                    <span style="color: #00d0e3;">Đã cập nhật doanh thu</span>
                                @else
                                    <span style="color: #e53637;">Hủy đơn hàng</span>
                                @endif
                            </td>
                            <td>{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }} -
                                {{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}</td>
                            <td>{{ $order->ord_select }}</td>
                            @if (Auth::user()->role == 0)
                                <td>
                                    <a href="{{ route('edit-order', $order->order_id) }}"
                                        class="active style-edit space_manage" ui-toggle-class=""><i
                                            class="fa fa-pencil-square-o text-success text-active"></i>
                                    </a>
                                    <a onclick="return confirm('Bạn có chắc muốn xóa đơn hàng?')"
                                        href="{{ route('delete-order', $order->order_id) }}"
                                        class="active style-edit space_manage" ui-toggle-class="">
                                        <i class="fa fa-times text-danger text"></i>
                                    </a>
                                    <a href="{{ route('coppy-order', $order->order_id) }}"
                                        class="active style-edit space_manage" ui-toggle-class=""><i
                                            class="far fa-copy"></i></i>
                                    </a>
                                    <a href="{{ route('update-order-accountant', $order->order_id) }}"
                                        class="active style-edit" ui-toggle-class=""><i
                                            class="fas fa-file-import text-warning "></i>
                                    </a>
                                </td>
                                <td>
                                    @if ($order->schedule_status == 0)
                                        <a href="{{ route('add-schedule', $order->order_id) }}" class="active styling-edit"
                                            ui-toggle-class="">
                                            Thêm lịch <i class="fa fa-calendar-plus"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('edit-schedule', $order->order_id) }}"
                                            class="active styling-edit" ui-toggle-class="">
                                            Sửa lịch <i class="fas fa-calendar-week"></i>
                                        </a>
                                    @endif
                                </td>
                            @else
                                @if (Carbon\Carbon::now() < $order->ord_start_day)
                                    <td>
                                        <a href="{{ route('edit-order', $order->order_id) }}"
                                            class="active style-edit space_manage" ui-toggle-class=""><i
                                                class="fa fa-pencil-square-o text-success text-active"></i>
                                        </a>
                                        <a onclick="return confirm('Bạn có chắc muốn xóa đơn hàng?')"
                                            href="{{ route('delete-order', $order->order_id) }}"
                                            class="active style-edit space_manage" ui-toggle-class="">
                                            <i class="fa fa-times text-danger text"></i>
                                        </a>
                                        <a href="{{ route('coppy-order', $order->order_id) }}"
                                            class="active style-edit space_manage" ui-toggle-class=""><i
                                                class="far fa-copy"></i></i>
                                        </a>
                                        <a href="{{ route('update-order-accountant', $order->order_id) }}"
                                            class="active style-edit" ui-toggle-class=""><i
                                                class="fas fa-file-import text-warning "></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if ($order->schedule_status == 0)
                                            <a href="{{ route('add-schedule', $order->order_id) }}"
                                                class="active styling-edit" ui-toggle-class="">
                                                Thêm lịch <i class="fa fa-calendar-plus"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('edit-schedule', $order->order_id) }}"
                                                class="active styling-edit" ui-toggle-class="">
                                                Sửa lịch <i class="fas fa-calendar-week"></i>
                                            </a>
                                        @endif
                                    </td>
                                @else
                                    <td>
                                        <a href="{{ route('edit-order', $order->order_id) }}"
                                            class="active style-edit space_manage" ui-toggle-class=""><i
                                                class="fa fa-pencil-square-o text-success text-active"></i>
                                        </a>
                                        <a href="{{ route('coppy-order', $order->order_id) }}"
                                            class="active style-edit space_manage" ui-toggle-class=""><i
                                                class="far fa-copy"></i></i>
                                        </a>
                                        <a href="{{ route('update-order-accountant', $order->order_id) }}"
                                            class="active style-edit" ui-toggle-class=""><i
                                                class="fas fa-file-import text-warning "></i>
                                        </a>
                                    </td>
                                    <td></td>
                                @endif
                            @endif
                            <td>
                                <a href="{{ route('view-order', $order->order_id) }}" class="active styling-edit"
                                    ui-toggle-class="">
                                    <i class="fa fa-arrow-right"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
    <script src="{{ versionResource('backend/js/datatables/jquery.dataTables.min.js') }}" defer></script>
    <script src="{{ versionResource('backend/js/datatables/responsive.min.js') }}" defer></script>
    <script type="text/javascript" defer>
        $(document).ready(function() {
            $('#myTable').DataTable();
        });
    </script>
@endpush
