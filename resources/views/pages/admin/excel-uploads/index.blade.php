@extends('layouts.default_auth')
@section('title', 'File Excel')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/excel-uploads.css') }}" type="text/css" as="style" />
@endpush

@php
    $total = $counts->sum();
    $current = $filters['trang_thai'] ?? null;
    $base = \Illuminate\Support\Arr::except($filters, ['trang_thai']);
@endphp

@section('admin_content')
    <div class="table-agile-info">
        <div class="import-section">
            <div class="page-head">
                <div>
                    <h1>File Excel từ đơn vị</h1>
                    <p class="muted">File mới nhất nằm trên cùng. Bấm tên file để xem nội dung.</p>
                </div>
                @if (Auth::user()->name == 'HR' || Auth::user()->name == 'Admin')
                    <div class="head-actions">
                        <a href="{{ route('excel-uploads.template') }}" class="btn btn--ghost">
                            @include('pages.admin.excel-uploads._icon', ['name' => 'download']) Tải file mẫu
                        </a>
                        <a href="{{ route('excel-uploads.create') }}" class="btn btn--primary">
                            @include('pages.admin.excel-uploads._icon', ['name' => 'plus']) Thêm file
                        </a>
                    </div>
                @endif
            </div>

            {{-- Dải trạng thái: vừa thống kê vừa lọc nhanh --}}
            <nav class="strip" aria-label="Lọc nhanh theo trạng thái">
                <a href="{{ route('excel-uploads.index', $base) }}" @class(['strip__seg', 'is-active' => !$current])
                    @if (!$current) aria-current="page" @endif>
                    <span class="strip__num">{{ $total }}</span>
                    <span class="strip__label">Tất cả</span>
                </a>
                @foreach ($statuses as $st)
                    <a href="{{ route('excel-uploads.index', $base + ['trang_thai' => $st->value]) }}"
                        @class([
                            'strip__seg',
                            'strip__seg--' . $st->css(),
                            'is-active' => $current === $st->value,
                        ]) @if ($current === $st->value) aria-current="page" @endif>
                        <span class="strip__num">{{ $counts[$st->value] ?? 0 }}</span>
                        <span class="strip__label">{{ $st->label() }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Bộ lọc --}}
            <form method="GET" action="{{ route('excel-uploads.index') }}" class="filters">
                <div class="field">
                    <label for="f-donvi">Đơn vị</label>
                    <select id="f-donvi" name="unit_id">
                        <option value="">Tất cả đơn vị</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>{{ $unit->unit_abbreviation }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="f-from">Từ ngày</label>
                    <input id="f-from" type="date" name="tu_ngay" value="{{ $filters['tu_ngay'] ?? '' }}">
                </div>
                <div class="field">
                    <label for="f-to">Đến ngày</label>
                    <input id="f-to" type="date" name="den_ngay" value="{{ $filters['den_ngay'] ?? '' }}">
                </div>
                <div class="field">
                    <label for="f-status">Trạng thái</label>
                    <select id="f-status" name="trang_thai">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" @selected($current === $st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field field--grow">
                    <label for="f-q">Tên file</label>
                    <input id="f-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                        placeholder="Gõ một phần tên file">
                </div>
                <div class="filters__actions">
                    <button type="submit" class="btn btn--dark">Lọc</button>
                    @if ($filters)
                        <a class="btn btn--ghost" href="{{ route('excel-uploads.index') }}">Bỏ lọc</a>
                    @endif
                </div>
            </form>

            {{-- Danh sách --}}
            @if ($uploads->isEmpty())
                <div class="empty">
                    @if ($filters)
                        <p><strong>Không có file nào khớp bộ lọc.</strong></p>
                        <p class="muted">Thử bỏ bớt điều kiện lọc, hoặc thêm file mới.</p>
                        <a class="btn btn--ghost" href="{{ route('excel-uploads.index') }}">Bỏ lọc</a>
                    @else
                        <p><strong>Chưa có file nào.</strong></p>
                        <p class="muted">Thêm file Excel đầu tiên theo file mẫu đơn hàng.</p>
                        @if (Auth::user()->name == 'HR' || Auth::user()->name == 'Admin')
                            <a class="btn btn--primary" href="{{ route('excel-uploads.create') }}">
                                @include('pages.admin.excel-uploads._icon', ['name' => 'plus']) Thêm file
                            </a>
                        @endif
                    @endif
                </div>
            @else
                <div class="table-wrap">
                    <table class="table-import">
                        <thead>
                            <tr class="section-title">
                                <th>Ngày chụp</th>
                                <th>Đơn vị</th>
                                <th>File</th>
                                <th>Trạng thái</th>
                                <th>Thêm lúc</th>
                                <th class="ta-r"><span class="sr-only">Thao tác</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($uploads as $u)
                                @php
                                    $next = collect($u->trang_thai->nextSteps())->first();
                                @endphp
                                <tr>
                                    <td data-label="Ngày chụp" class="nowrap tnum">{{ $u->ngay->format('d/m/Y') }}</td>
                                    <td data-label="Đơn vị">{{ $u->unit?->unit_abbreviation ?? '—' }}</td>
                                    <td data-label="File">
                                        <a class="file-link" href="{{ route('excel-uploads.show', $u) }}">
                                            <span class="file-ext file-ext--{{ $u->extension }}"
                                                aria-hidden="true">{{ $u->extension }}</span>
                                            <span class="file-meta">
                                                <span class="file-name">{{ $u->ten_file }}</span>
                                                <span class="file-size">{{ $u->kich_thuoc_dep }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td data-label="Trạng thái">
                                        @include('pages.admin.excel-uploads._status', [
                                            'status' => $u->trang_thai,
                                        ])
                                        @if ($u->order_id)
                                            <span class="order-tag">Đơn #{{ $u->order_id }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Thêm lúc" class="nowrap muted tnum">
                                        {{ $u->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="row-actions">
                                        @if (Auth::user()->name == 'Sale' || Auth::user()->name == 'Admin')
                                            @if ($u->canCreateOrder())
                                                <a class="btn btn--sm btn--soft"
                                                    href="{{ route('excel-uploads.to-order', $u) }}">Tạo đơn</a>
                                            @elseif ($next && !$u->order_id)
                                                <form method="POST" action="{{ route('excel-uploads.status', $u) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="trang_thai" value="{{ $next->value }}">
                                                    <button class="btn btn--sm btn--soft"
                                                        type="submit">{{ $next->actionLabel() }}</button>
                                                </form>
                                            @endif
                                        @endif
                                        <a class="icon-btn" href="{{ route('excel-uploads.show', $u) }}" title="Xem"
                                            aria-label="Xem {{ $u->ten_file }}">
                                            @include('pages.admin.excel-uploads._icon', ['name' => 'eye'])
                                        </a>
                                        <a class="icon-btn" href="{{ route('excel-uploads.download', $u) }}"
                                            title="Tải về" aria-label="Tải về {{ $u->ten_file }}">
                                            @include('pages.admin.excel-uploads._icon', [
                                                'name' => 'download',
                                            ])
                                        </a>
                                        @if (Auth::user()->name == 'HR' || Auth::user()->name == 'Admin')
                                            @if ($u->trang_thai->canEdit())
                                                <a class="icon-btn" href="{{ route('excel-uploads.edit', $u) }}"
                                                    title="Sửa" aria-label="Sửa {{ $u->ten_file }}">
                                                    @include('pages.admin.excel-uploads._icon', [
                                                        'name' => 'edit',
                                                    ])
                                                </a>
                                                <form method="POST" action="{{ route('excel-uploads.destroy', $u) }}"
                                                    onsubmit="return confirm('Xoá file “{{ addslashes($u->ten_file) }}”? Không khôi phục được.')">
                                                    @csrf @method('DELETE')
                                                    <button class="icon-btn icon-btn--danger" type="submit"
                                                        title="Xoá" aria-label="Xoá {{ $u->ten_file }}">
                                                        @include('pages.admin.excel-uploads._icon', [
                                                            'name' => 'trash',
                                                        ])
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $uploads->links('pagination::custom') }}
            @endif
        </div>
    </div>
@endsection
