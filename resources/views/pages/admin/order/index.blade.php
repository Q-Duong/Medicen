@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách đơn hàng
        </div>
        <div class="table-responsive table-content">
            <div id="table-scroll" class="table-scroll">
                <table class="table">
                    <thead>
                        <tr class="section-title">
                            <th>TG đăng ký</th>
                            <th>Mã ĐH</th>
                            <th>Ngày chụp</th>
                            <th>Đơn vị thuê xe</th>
                            <th>Tên Đơn vị</th>
                            <th>Loại</th>
                            <th>Số lượng</th>
                            <th>Tổng tiền</th>
                            <th>Bộ phận chụp</th>
                            <th>Trạng thái</th>
                            <th>Quản lý</th>
                            <th>Quản lý lịch</th>
                        </tr>
                    </thead>
                    <tbody class="tbody-content">
                        @foreach ($getAll as $key => $order)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('H:i:s d/m/Y') }}</td>
                                <td>{{ $order->id }}</td>
                                <td>{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}</td>
                                <td>{{ $order->unit_name }}</td>
                                <td>{{ $order->ord_cty_name }}</td>
                                <td>{{ $order->ord_type == 1 ? "X-Quang" : "Siêu Âm" }}</td>
                                <td>{{ $order->order_quantity }}</td>
                                <td>{{ number_format($order->order_price, 0, ',', '.') }}₫</td>
                                <td>{{ $order->ord_select }}</td>
                                <td>
                                    @if ($order->status_id == 0)
                                        <span style="color: #27c24c;">Đơn hàng mới</span>
                                    @elseif($order->status_id == 1)
                                        <span style="color: #FCB322;">Đang xử lý</span>
                                    @elseif($order->status_id == 2)
                                        <span style="color: #c037df;">Đã cập nhật số Cas thực tế</span>
                                    @elseif($order->status_id == 3)
                                        <span style="color: #0071e3;">Đã xử lý</span>
                                    @elseif($order->status_id == 4)
                                        <span style="color: #00d0e3;">Đã cập nhật doanh thu</span>
                                    @else
                                        <span style="color: #e53637;">Hủy đơn hàng</span>
                                    @endif
                                </td>
                                @if (Auth::user()->role == 0)
                                    <td class="management">
                                        <a href="{{ route('order.edit', $order->id) }}" class="management-btn"><i
                                                class="fa fa-pencil-square-o text-success text-active"></i>
                                        </a>
                                        <form action="{{ route('order.destroy', $order->id) }}" method="POST">
                                            @method('delete')
                                            @csrf
                                            <button type="submit" class="management-btn button-submit"
                                                onclick="return confirm('Bạn có chắc muốn xóa đơn hàng?')"><i
                                                    class="fa fa-times text-danger text"></i></button>
                                        </form>
                                        <a href="{{ route('order.copy', $order->id) }}" class="management-btn">
                                            <i class="far fa-copy"></i></i>
                                        </a>
                                        <a href="{{ route('accountant.order.update', $order->id) }}"
                                            class="management-btn">
                                            <i class="fas fa-file-import text-warning "></i>
                                        </a>
                                    </td>
                                    <td class="management-lite">
                                        @if ($order->schedule_status == 0)
                                            <a href="{{ route('schedule.create', $order->id) }}" class="management-btn">
                                                <i class="fa fa-calendar-plus"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('schedule.edit', $order->id) }}" class="management-btn">
                                                <i class="fas fa-calendar-week"></i>
                                            </a>
                                        @endif
                                    </td>
                                @else
                                    @if (Carbon\Carbon::now() < $order->ord_start_day)
                                        <td class="management">
                                            <a href="{{ route('order.edit', $order->id) }}" class="management-btn"><i
                                                    class="fa fa-pencil-square-o text-success text-active"></i>
                                            </a>
                                            <form action="{{ route('order.destroy', $order->id) }}" method="POST">
                                                @method('delete')
                                                @csrf
                                                <button type="submit" class="management-btn button-submit"
                                                    onclick="return confirm('Bạn có chắc muốn xóa đơn hàng?')"><i
                                                        class="fa fa-times text-danger text"></i></button>
                                            </form>
                                            <a href="{{ route('order.copy', $order->id) }}" class="management-btn"><i
                                                    class="far fa-copy"></i></i>
                                            </a>
                                            <a href="{{ route('accountant.order.update', $order->id) }}"
                                                class="management-btn"><i class="fas fa-file-import text-warning "></i>
                                            </a>
                                        </td>
                                        <td class="management-lite">
                                            @if ($order->schedule_status == 0)
                                                <a href="{{ route('schedule.create', $order->id) }}"
                                                    class="management-btn">
                                                    <i class="fa fa-calendar-plus"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('schedule.edit', $order->id) }}" class="management-btn">
                                                    <i class="fas fa-calendar-week"></i>
                                                </a>
                                            @endif
                                        </td>
                                    @else
                                        <td class="management">
                                            <a href="{{ route('order.edit', $order->id) }}" class="management-btn"><i
                                                    class="fa fa-pencil-square-o text-success text-active"></i>
                                            </a>
                                            @if ($order->status_id != 3)
                                                <a href="{{ route('accountant.order.update', $order->id) }}"
                                                    class="management-btn"><i class="fas fa-file-import text-warning "></i>
                                                </a>
                                            @endif
                                        </td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @include('layouts.section.admin.pagination_showing_current', [
            'items' => $getAll,
        ])
        {{ $getAll->links('pagination::custom') }}
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
                    <button type="submit" class="primary-btn-filter button-submit">Xuất file Excel</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
@endpush
