@extends('layouts.default_auth')

@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/excel-uploads.css') }}" type="text/css"
        as="style" />
@endpush

@section('admin_content')
    <div class="table-agile-info">
        <div class="import-section">
            <div class="page-head">
                <div>
                    <h1>Danh sách đơn hàng</h1>
                </div>
                <div class="head-actions">
                    <a href="{{ route('order.create') }}" class="btn btn--primary">
                        @include('pages.admin.excel-uploads._icon', ['name' => 'plus']) Thêm đơn
                    </a>
                </div>
            </div>

            <nav class="strip" aria-label="Lọc nhanh theo trạng thái">
                <div class="strip__seg {{ request('status_id') == '' ? 'is-active' : '' }}"
                    onclick="filterByCardStatus('')">
                    <span class="strip__num">{{ $summary['all'] ?? 0 }}</span>
                    <span class="strip__label">Tất cả</span>
                </div>

                <div class="strip__seg strip__seg--schedule {{ request('status_id') === '5' ? 'is-active' : '' }}"
                    onclick="filterByCardStatus('5')">
                    <span class="strip__num">{{ $summary['schedule'] ?? 0 }}</span>
                    <span class="strip__label">Chưa lên lịch</span>
                </div>

                <div class="strip__seg strip__seg--processing {{ request('status_id') === '1' ? 'is-active' : '' }}"
                    onclick="filterByCardStatus('1')">
                    <span class="strip__num">{{ $summary['processing'] ?? 0 }}</span>
                    <span class="strip__label">Đang xử lý</span>
                </div>

                <div class="strip__seg strip__seg--updated {{ request('status_id') === '2' ? 'is-active' : '' }}"
                    onclick="filterByCardStatus('2')">
                    <span class="strip__num">{{ $summary['updated'] ?? 0 }}</span>
                    <span class="strip__label">Đã cập nhật số ca thực tế</span>
                </div>

                <div class="strip__seg strip__seg--revenue-updated {{ request('status_id') === '4' ? 'is-active' : '' }}"
                    onclick="filterByCardStatus('4')">
                    <span class="strip__num">{{ $summary['revenue_updated'] ?? 0 }}</span>
                    <span class="strip__label">Đã cập nhật doanh thu</span>
                </div>

                <div class="strip__seg strip__seg--done {{ request('status_id') === '3' ? 'is-active' : '' }}"
                    onclick="filterByCardStatus('3')">
                    <span class="strip__num">{{ $summary['completed'] ?? 0 }}</span>
                    <span class="strip__label">Hoàn thành</span>
                </div>
            </nav>

            <!-- Form bộ lọc -->
            <form action="{{ route('order.index') }}" method="GET" class="filters" id="filterForm">
                <!-- Input ẩn lưu trạng thái từ thẻ thống kê (Để khi submit form vẫn giữ được trạng thái đang chọn) -->
                <input type="hidden" name="status_id" id="statusInput" value="{{ request('status_id') }}">

                <div class="field">
                    <label for="f-donvi">Đơn vị</label>
                    <select id="f-donvi" name="unit_abbreviation">
                        <option value="">Tất cả đơn vị</option>
                        @if (isset($units))
                            @foreach ($units as $unit)
                                <option value="{{ $unit->unit_abbreviation }}"
                                    {{ request('unit_abbreviation') == $unit->unit_abbreviation ? 'selected' : '' }}>
                                    {{ $unit->unit_abbreviation }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="field">
                    <label for="f-from">Từ ngày</label>
                    <input id="f-from" type="date" name="from_date" value="{{ request('from_date') }}">
                </div>

                <div class="field">
                    <label for="f-to">Đến ngày</label>
                    <input id="f-to" type="date" name="to_date" value="{{ request('to_date') }}">
                </div>

                <div class="field field--grow">
                    <label for="f-q">Mã ĐH hoặc Tên đơn vị</label>
                    <input id="f-q" type="search" name="keyword" value="{{ request('keyword') }}"
                        placeholder="Gõ từ khóa tìm kiếm...">
                </div>

                <div class="filters__actions">
                    <button type="submit" class="btn btn--dark">Lọc</button>
                    @if (request()->anyFilled(['unit_abbreviation', 'from_date', 'to_date', 'keyword', 'status_id', 'schedule_status']))
                        <a class="btn btn--ghost" href="{{ route('order.index') }}">Bỏ lọc</a>
                    @endif
                </div>
            </form>

            <div class="table-wrap">
                <table class="table-import">
                    <thead>
                        <tr class="section-title">
                            <th>Mã ĐH</th>
                            <th>Ngày chụp</th>
                            <th>Đơn vị thuê xe</th>
                            <th>Tên Đơn vị</th>
                            <th>Số lượng</th>
                            <th>Bộ phận chụp</th>
                            <th>Trạng thái</th>
                            <th class="ta-r"><span class="sr-only">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($getAll as $key => $order)
                            <tr class="{{ $order->complete == 0 ? 'uncomplete-order' : '' }}">
                                <td
                                    title="Ngày tạo: {{ \Carbon\Carbon::parse($order->created_at)->format('H:i:s d/m/Y') }}">
                                    {{ $order->id }}</td>
                                <td>{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}</td>
                                <td>{{ $order->unit_abbreviation }}</td>
                                <td>{{ $order->ord_cty_name }}</td>
                                <td>{{ $order->order_quantity }}</td>
                                <td>{{ $order->ord_select }}</td>
                                <td class="status-label-container">
                                    @if ($order->status_id == 0)
                                        <span class="status-label"
                                            style="border: 2px solid #27c24c; color: #27c24c; background-color: #27C24C0F;">Đơn
                                            hàng mới</span>
                                    @elseif($order->status_id == 1)
                                        <span class="status-label"
                                            style="border: 2px solid #FCB322; color: #FCB322; background-color: #FCB3220F;">Đang
                                            xử lý</span>
                                    @elseif($order->status_id == 2)
                                        <span class="status-label"
                                            style="border: 2px solid #c037df; color: #c037df; background-color: #C037DF0F;">Đã
                                            cập nhật số Cas thực tế</span>
                                    @elseif($order->status_id == 3)
                                        <span class="status-label"
                                            style="border: 2px solid #0071e3; color: #0071e3; background-color: #0071E30F;">Hoàn
                                            thành</span>
                                    @elseif($order->status_id == 4)
                                        <span class="status-label"
                                            style="border: 2px solid #00d0e3; color: #00d0e3; background-color: #00D0E30F;">Đã
                                            cập nhật doanh thu</span>
                                    @elseif($order->status_id == 5)
                                        <span class="status-label"
                                            style="border: 2px solid #6f7071; color: #6f7071; background-color: #f9f9f9;">Chưa
                                            lên lịch</span>
                                    @else
                                        <span class="status-label" style="border: 2px solid #e53637; color: #e53637;">Hủy
                                            đơn hàng</span>
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
                                            <a href="{{ route('schedule.create', $order->id) }}"
                                                class="create-schedule-btn">
                                                Thêm Lịch
                                            </a>
                                        @else
                                            <a href="{{ route('schedule.edit', $order->id) }}" class="edit-schedule-btn">
                                                Sửa Lịch
                                            </a>
                                        @endif
                                    </td>
                                @else
                                    @php
                                        $now = Carbon\Carbon::now()->setTime(0, 0, 0);
                                        $dateEqual = Carbon\Carbon::parse($order->ord_start_day);
                                    @endphp
                                    @if ($now->isBefore($dateEqual) || $now->equalTo($dateEqual))
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
                                                    class="create-schedule-btn">
                                                    Thêm Lịch
                                                </a>
                                            @else
                                                <a href="{{ route('schedule.edit', $order->id) }}"
                                                    class="edit-schedule-btn">
                                                    Sửa Lịch
                                                </a>
                                            @endif
                                        </td>
                                    @else
                                        <td class="management">
                                            <a href="{{ route('order.edit', $order->id) }}" class="management-btn"><i
                                                    class="fa fa-pencil-square-o text-success text-active"></i>
                                            </a>
                                            <a href="{{ route('order.copy', $order->id) }}" class="management-btn"><i
                                                    class="far fa-copy"></i></i>
                                            </a>
                                            @if ($order->status_id != 3)
                                                <a href="{{ route('accountant.order.update', $order->id) }}"
                                                    class="management-btn"><i
                                                        class="fas fa-file-import text-warning "></i>
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
            @include('layouts.section.admin.pagination_showing_current', [
                'items' => $getAll,
            ])
            {{ $getAll->links('pagination::custom') }}
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
    <script>
        function filterByCardStatus(status) {
            document.getElementById('statusInput').value = status;
            document.getElementById('filterForm').submit();
        }
    </script>
@endpush
