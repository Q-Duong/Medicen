@extends('layouts.default_auth')
@section('title', 'Lịch sử chỉnh sửa - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    {{-- Dùng chung giao diện trang quản lý (assets/css/support/org-staff.css → staff.css) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        // Màu theo loại thao tác
        $kind = function ($a) {
            $a = mb_strtolower((string) $a);
            return match (true) {
                str_contains($a, 'xóa') || str_contains($a, 'hủy') || str_contains($a, 'huỷ') => 'bad',
                str_contains($a, 'thêm') || str_contains($a, 'xếp lịch') => 'ok',
                str_contains($a, 'tiền') || str_contains($a, 'số ca') || str_contains($a, 'doanh thu') || str_contains($a, 'chốt') || str_contains($a, 'duyệt') => 'money',
                default => 'edit',
            };
        };
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Lịch sử chỉnh sửa</h1>
                <p>Ai đã thêm / sửa / xếp lịch / cập nhật tiền cho đơn nào, lúc nào. Hôm nay có {{ number_format($today, 0, ',', '.') }} thao tác.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('history.index') }}" class="sf-filters">
            <label class="sf-field" style="width:130px">
                <span>Mã đơn</span>
                <input type="search" name="order" value="{{ request('order') }}" placeholder="Vd: 2252" inputmode="numeric" autocomplete="off">
            </label>
            <label class="sf-field">
                <span>Người sửa</span>
                <select name="user">
                    <option value="">Tất cả</option>
                    @foreach ($editors as $e)
                        <option value="{{ $e['email'] }}" @selected(request('user') === $e['email'])>{{ $e['name'] }}{{ $e['name'] !== $e['email'] ? ' · ' . $e['email'] : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label class="sf-field sf-field--grow">
                <span>Nội dung</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Vd: xếp lịch, hủy lịch, số ca, thành tiền..." autocomplete="off">
            </label>
            <label class="sf-field">
                <span>Từ ngày</span>
                <input type="date" name="from" value="{{ request('from') }}">
            </label>
            <label class="sf-field">
                <span>Đến ngày</span>
                <input type="date" name="to" value="{{ request('to') }}">
            </label>
            <div class="sf-filters__actions">
                <button type="submit" class="sf-btn sf-btn--dark">Lọc</button>
                @if (request()->anyFilled(['order', 'user', 'q', 'from', 'to']))
                    <a href="{{ route('history.index') }}" class="sf-btn sf-btn--ghost">Bỏ lọc</a>
                @endif
            </div>
        </form>

        <div class="sf-table">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Mã đơn</th>
                        <th>Người sửa</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($getAllHistory as $history)
                        @php($who = $people[$history->user_name] ?? null)
                        <tr>
                            <td data-label="Thời gian" class="sf-nowrap">
                                {{ optional($history->created_at)->format('d/m/Y') }}
                                <div class="sf-note">{{ optional($history->created_at)->format('H:i:s') }} · #{{ $history->id }}</div>
                            </td>
                            <td data-label="Mã đơn">
                                <a href="{{ route('history.index', ['order' => $history->order_id]) }}" class="sf-tag sf-tag--code" title="Xem toàn bộ lịch sử của đơn này">#{{ $history->order_id }}</a>
                            </td>
                            <td data-label="Người sửa">
                                <b>{{ $who ?? $history->user_name }}</b>
                                @if ($who)<div class="sf-note">{{ $history->user_name }}</div>@endif
                            </td>
                            <td data-label="Thao tác"><span class="sf-act sf-act--{{ $kind($history->history_action) }}">{{ $history->history_action }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="sf-empty">Không có lịch sử nào khớp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sf-pager">
            @include('layouts.section.admin.pagination_showing_current', ['items' => $getAllHistory])
            {{ $getAllHistory->links('pagination::custom') }}
        </div>
    </div>
@endsection
