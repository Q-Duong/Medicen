@extends('layouts.default_auth')
@section('title', 'Quản lý đơn vị - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    {{-- Dùng chung giao diện trang quản lý (assets/css/support/org-staff.css → staff.css) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $has = request('has_orders');
        $tab = fn ($h) => route('unit.index', array_filter(['has_orders' => $h, 'q' => request('q'), 'sort' => request('sort')], fn ($v) => $v !== null && $v !== ''));
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Quản lý đơn vị</h1>
                <p>Đơn vị thuê xe (khách hàng): tên viết tắt hiện trên đơn hàng, lịch xe, công nợ.</p>
            </div>
            <a href="{{ route('unit.create') }}" class="sf-btn sf-btn--primary"><i class="fas fa-plus"></i> Thêm đơn vị</a>
        </div>

        <nav class="sf-strip" aria-label="Lọc đơn vị">
            <a href="{{ $tab(null) }}" class="sf-strip__seg {{ $has === null || $has === '' ? 'is-active' : '' }}"><b>{{ $counts['all'] }}</b><span>Tất cả</span></a>
            <a href="{{ $tab('1') }}" class="sf-strip__seg sf-strip__seg--acc {{ $has === '1' ? 'is-active' : '' }}"><b>{{ $counts['used'] }}</b><span>Đã có đơn hàng</span></a>
            <a href="{{ $tab('0') }}" class="sf-strip__seg sf-strip__seg--staff {{ $has === '0' ? 'is-active' : '' }}"><b>{{ $counts['unused'] }}</b><span>Chưa có đơn</span></a>
        </nav>

        <form method="GET" action="{{ route('unit.index') }}" class="sf-filters">
            @if ($has !== null && $has !== '')<input type="hidden" name="has_orders" value="{{ $has }}">@endif
            <label class="sf-field sf-field--grow">
                <span>Tìm kiếm</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Mã, tên viết tắt, tên đơn vị hoặc mã số thuế..." autocomplete="off">
            </label>
            <label class="sf-field">
                <span>Sắp xếp</span>
                <select name="sort" onchange="this.form.submit()">
                    <option value="">Theo tên (A → Z)</option>
                    <option value="orders" @selected(request('sort') === 'orders')>Nhiều đơn nhất</option>
                </select>
            </label>
            <div class="sf-filters__actions">
                <button type="submit" class="sf-btn sf-btn--dark">Lọc</button>
                @if (request()->anyFilled(['q', 'has_orders', 'sort']))
                    <a href="{{ route('unit.index') }}" class="sf-btn sf-btn--ghost">Bỏ lọc</a>
                @endif
            </div>
        </form>

        <div class="sf-table">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên viết tắt</th>
                        <th>Tên đơn vị</th>
                        <th>Mã số thuế</th>
                        <th>Số đơn</th>
                        <th class="sf-actions-h">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($getAllUnit as $unit)
                        <tr>
                            <td data-label="Mã"><span class="sf-tag sf-tag--code">{{ $unit->unit_code }}</span></td>
                            <td data-label="Tên viết tắt">
                                <div class="sf-person">
                                    <span class="sf-avatar sf-avatar--unit"><i class="fas fa-building"></i></span>
                                    <b>{{ $unit->unit_abbreviation }}</b>
                                </div>
                            </td>
                            <td data-label="Tên đơn vị">{{ $unit->unit_name }}</td>
                            <td data-label="Mã số thuế">{{ $unit->unit_tax_code ?: '—' }}</td>
                            <td data-label="Số đơn">
                                @if ($unit->orders_count > 0)
                                    <a href="{{ route('order.index', ['unit_abbreviation' => $unit->unit_abbreviation]) }}" class="sf-count" title="Xem các đơn của đơn vị này">{{ number_format($unit->orders_count, 0, ',', '.') }} đơn</a>
                                @else
                                    <span class="sf-muted">Chưa có</span>
                                @endif
                            </td>
                            <td data-label="Thao tác" class="sf-actions">
                                <a href="{{ route('unit.edit', $unit->id) }}" class="sf-icon-btn" title="Sửa đơn vị"><i class="fas fa-pen"></i></a>
                                @if ($unit->orders_count > 0)
                                    <span class="sf-icon-btn is-disabled" title="Đơn vị đã có {{ $unit->orders_count }} đơn hàng, không xóa được"><i class="fas fa-lock"></i></span>
                                @else
                                    <form action="{{ route('unit.destroy', $unit->id) }}" method="POST"
                                        data-confirm="Xóa đơn vị {{ $unit->unit_abbreviation }} ({{ $unit->unit_code }})? Đơn vị này chưa có đơn hàng nào."
                                        data-confirm-title="Xóa đơn vị" data-confirm-ok="Xóa" data-confirm-danger>
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="sf-icon-btn sf-icon-btn--danger" title="Xóa đơn vị"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sf-empty">Không có đơn vị nào khớp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sf-pager">
            @include('layouts.section.admin.pagination_showing_current', ['items' => $getAllUnit])
            {{ $getAllUnit->links('pagination::custom') }}
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
@endpush
