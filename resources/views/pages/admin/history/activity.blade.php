@extends('layouts.default_auth')
@section('title', 'Lịch sử thao tác kế toán - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $kind = ['them' => 'ok', 'tai_len' => 'ok', 'nhap' => 'ok', 'xoa' => 'bad', 'sua' => 'edit'];
        $fmt = function ($v) {
            if ($v === null || $v === '') return '(trống)';
            if (is_bool($v)) return $v ? 'Có' : 'Không';
            if (is_numeric($v) && floor((float) $v) == (float) $v && abs((float) $v) >= 1000) return number_format((float) $v, 0, ',', '.');
            return \Illuminate\Support\Str::limit((string) $v, 140);
        };
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Lịch sử chỉnh sửa</h1>
                <p>Ai đã thêm / sửa / xóa gì ở Báo cáo thu chi, Công nợ, Công nợ CT, Sổ VCB chú Lộc, Dữ liệu kế toán và Trợ lý AI. Hôm nay có {{ number_format($today, 0, ',', '.') }} thao tác.</p>
            </div>
        </div>

        @include('pages.admin.history._tabs', ['tab' => 'ke-toan'])

        <form method="GET" action="{{ route('history.index') }}" class="sf-filters">
            <input type="hidden" name="tab" value="ke-toan">
            <label class="sf-field">
                <span>Trang</span>
                <select name="area">
                    <option value="">Tất cả</option>
                    @foreach (\App\Models\ActivityLog::AREAS as $k => $label)
                        <option value="{{ $k }}" @selected(request('area') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="sf-field">
                <span>Người thao tác</span>
                <select name="user">
                    <option value="">Tất cả</option>
                    @foreach ($actors->unique('user_id') as $a)
                        <option value="{{ $a->user_id }}" @selected((string) request('user') === (string) $a->user_id)>{{ $a->user_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="sf-field sf-field--grow">
                <span>Nội dung</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Vd: CT Lagi, đơn #2252, dự kiến..." autocomplete="off">
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
                @if (request()->anyFilled(['area', 'user', 'q', 'from', 'to']))
                    <a href="{{ route('history.index', ['tab' => 'ke-toan']) }}" class="sf-btn sf-btn--ghost">Bỏ lọc</a>
                @endif
            </div>
        </form>

        <div class="sf-table">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Trang</th>
                        <th>Người thao tác</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td data-label="Thời gian" class="sf-nowrap">
                                {{ optional($log->created_at)->format('d/m/Y') }}
                                <div class="sf-note">{{ optional($log->created_at)->format('H:i:s') }}</div>
                            </td>
                            <td data-label="Trang"><span class="sf-tag sf-tag--staff">{{ $log->areaLabel() }}</span></td>
                            <td data-label="Người thao tác"><b>{{ $log->user_name ?? 'Không rõ' }}</b></td>
                            <td data-label="Thao tác">
                                <span class="sf-act sf-act--{{ $kind[$log->action] ?? 'edit' }}">{{ $log->summary }}</span>
                                @if ($log->changes)
                                    <ul class="hs-changes">
                                        @foreach ($log->changes as $field => $pair)
                                            @php([$old, $new] = array_pad((array) $pair, 2, null))
                                            <li>
                                                <b>{{ $field }}:</b>
                                                @if ($log->action === 'them')
                                                    <span class="hs-new">{{ $fmt($new) }}</span>
                                                @elseif ($log->action === 'xoa')
                                                    <span class="hs-old">{{ $fmt($old) }}</span>
                                                @else
                                                    <span class="hs-old">{{ $fmt($old) }}</span> → <span class="hs-new">{{ $fmt($new) }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="sf-empty">Chưa có thao tác nào khớp bộ lọc. Nhật ký bắt đầu ghi từ khi cập nhật bản này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sf-pager">
            @include('layouts.section.admin.pagination_showing_current', ['items' => $logs])
            {{ $logs->links('pagination::custom') }}
        </div>
    </div>
@endsection
