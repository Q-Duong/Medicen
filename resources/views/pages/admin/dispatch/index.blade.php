@extends('layouts.default_auth')
@section('title', 'Xếp lịch xe - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/dispatch.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $sessionTag = fn ($s) => ['Sáng' => 'S', 'Chiều' => 'C'][trim((string) $s)] ?? 'F';
    @endphp
    @php
        // Cấu hình cho assets/js/tool/dispatch/org-dispatch.js
        $dispatchConfig = [
            'suggestUrl' => route('dispatch.suggest'),
            'applyUrl' => route('dispatch.apply'),
            'orderBase' => url('admin/xep-lich/don'),
            'weekUrl' => route('dispatch.week'),
            'pageUrl' => route('dispatch.index'),
            'refreshUrl' => route('dispatch.refresh'),
            'cancelBase' => url('admin/xep-lich/huy'),
            'timekeepingUrl' => route('dispatch.timekeeping'),
            'sheetUrl' => route('dispatch.week_sheet'),
            'pendingUrl' => route('dispatch.pending'),
            'editUrl' => route('dispatch.edit'),
            'holdBase' => url('admin/xep-lich/giu-cho'),
            'hiredKtv' => config('schedule.hired_ktv'),
        ];
    @endphp
    <div class="dp" id="dispatch" data-config='@json($dispatchConfig)'>
        <div class="dp-card">
            <div class="dp-head">
                <h3>Xếp lịch xe</h3>
                <span class="grow"></span>
                <span class="legend">
                    <span><i style="background:var(--ok-bg);border:1px solid #9fe1cb"></i>Đã xếp</span>
                    <span><i style="background:var(--ai-bg);border:1px dashed var(--ai)"></i>Gợi ý</span>
                    <span><i style="background:var(--bad-bg)"></i>Trùng lịch</span>
                    <span><i style="background:#e6f0fc;border:1px solid #0071e3"></i>Hoàn thành</span>
                </span>
                <button type="button" class="btn-ai" id="btn-suggest" {{ $pending->isEmpty() ? 'disabled' : '' }}>
                    <i class="fas fa-magic"></i> Gợi ý xếp lịch (<span id="pending-count" style="margin:0;padding:0">{{ $pending->count() }}</span> đơn)
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash flash--bad">{{ $errors->first() }}</div>
        @endif

        {{-- Gợi ý (hiện sau khi bấm nút) --}}
        <div class="dp-card sug" id="sug" hidden>
            <p class="sec-title"><i class="fas fa-magic" style="color:var(--ai)"></i> Phương án xếp lịch <span class="src" id="sug-src"></span>
                <small>Xem lại, sửa nếu cần rồi bấm Áp dụng. Hệ thống kiểm tra trùng lịch lần nữa trước khi lưu.</small></p>
            <p class="sug__note" id="sug-note" hidden></p>
            <div id="sug-list"></div>
            <div class="sug__foot">
                <button type="button" class="btn-g" id="sug-close">Đóng</button>
                <button type="button" class="btn-ai" id="sug-apply"><i class="fas fa-check"></i> Áp dụng đã chọn</button>
            </div>
        </div>

        {{-- Giữ chỗ: tạo mới (bấm ô trống trên lưới) hoặc xem / xếp đơn vào (bấm ô giữ chỗ) --}}
        <div class="dp-card" id="hold-panel" hidden>
            <p class="sec-title"><i class="fas fa-thumbtack" style="color:#ef9f27"></i> <span id="hold-title">Giữ chỗ</span>
                <small id="hold-sub">Khách đã đặt trước nhưng chưa lên đơn. Xe / người được giữ sẽ không bị xếp cho đơn khác.</small></p>
            <form class="hold-form" id="hold-form" novalidate>
                <label class="wide">Khách / đơn vị đặt trước *
                    <input name="customer" list="dp-units" maxlength="255" required placeholder="Tên đơn vị hoặc khách, vd: BV BÌNH CHÁNH">
                </label>
                <label>Từ ngày * <input type="date" name="hold_start" required></label>
                <label>Đến ngày <input type="date" name="hold_end"></label>
                <label>Buổi
                    <select name="hold_session">@foreach ($sessions as $session)<option>{{ $session }}</option>@endforeach</select>
                </label>
                <label>Xe *
                    <select name="car">@foreach ($grid['cars'] as $car)<option value="{{ $car['key'] }}">{{ $car['label'] }}</option>@endforeach</select>
                </label>
                <label>Tài xế
                    <select name="driver"><option value="">— Chưa chọn —</option>@foreach ($staffList->where('staff_role', 'TX') as $st)<option>{{ $st->staff_name }}</option>@endforeach</select>
                </label>
                <label>Số cas dự kiến <input type="number" name="expected_cas" min="1" inputmode="numeric"></label>
                <label>KTV 1
                    <select name="ktv1"><option value="">— Chưa chọn —</option>@foreach ($staffList->where('staff_role', 'KTV') as $st)<option>{{ $st->staff_name }}</option>@endforeach</select>
                </label>
                <label>KTV 2
                    <select name="ktv2"><option value="">— Không —</option>@foreach ($staffList->where('staff_role', 'KTV') as $st)<option>{{ $st->staff_name }}</option>@endforeach</select>
                </label>
                <label class="wide">Ghi chú <input name="note" maxlength="500" placeholder="Liên hệ, địa điểm dự kiến, đã cọc chưa…"></label>
                <div class="hold-err" id="hold-err" hidden></div>
                <div class="actions">
                    <button type="button" class="btn-g" data-hold-close>Đóng</button>
                    <button type="submit" class="btn-ai" id="hold-submit"><i class="fas fa-thumbtack"></i> Lưu giữ chỗ</button>
                </div>
            </form>
            <div id="hold-view" hidden></div>
        </div>
        <datalist id="dp-units">@foreach ($units as $u)<option value="{{ $u }}"></option>@endforeach</datalist>

        <div class="dp-card" id="pending-view">
            @include('pages.admin.dispatch._pending')
        </div>

        {{-- Lịch xe + nhân sự của một tuần: tải lại bằng JS khi đổi tuần / mở một đơn ở tuần khác --}}
        <div id="week-view">
            @include('pages.admin.dispatch._week')
        </div>

        {{-- Chấm công tháng: cùng số liệu với trang Chấm công / file Excel --}}
        <div class="dp-card" id="timekeeping-view">
            @include('pages.admin.dispatch._timekeeping')
        </div>

        <div class="dp-card" id="lich-nghi">
            <p class="sec-title"><i class="far fa-calendar-times"></i> Lịch nghỉ <small>người đang nghỉ sẽ không được gợi ý</small></p>
            <form class="lv-form" method="POST" action="{{ route('dispatch.leaves.store') }}">
                @csrf
                <label>Nhân sự
                    <select name="staff_id" required>
                        @foreach ($staffList as $s)
                            <option value="{{ $s->id }}" @selected(old('staff_id') == $s->id)>{{ $s->staff_role }} · {{ $s->staff_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Từ ngày <input type="date" name="leave_start" required value="{{ old('leave_start') }}"></label>
                <label>Đến ngày <input type="date" name="leave_end" required value="{{ old('leave_end') }}"></label>
                <label>Buổi
                    <select name="leave_session">
                        @foreach ($sessions as $session)
                            <option @selected(old('leave_session') === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Ghi chú <input type="text" name="leave_note" maxlength="255" placeholder="Nghỉ phép, đi học, khám bệnh…" value="{{ old('leave_note') }}"></label>
                <button type="submit" class="btn-g" style="height:34px"><i class="fas fa-plus"></i> Thêm</button>
            </form>
            @if ($leaves->isEmpty())
                <p class="empty-note">Chưa có lịch nghỉ nào.</p>
            @else
                <table class="lv-list">
                    <thead><tr><th>Nhân sự</th><th>Thời gian</th><th>Buổi</th><th>Ghi chú</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($leaves as $leave)
                            <tr class="{{ $leave->leave_end->lt(today()) ? 'past' : '' }}">
                                <td>{{ $leave->staff?->staff_role }} · {{ $leave->staff?->staff_name ?? 'Đã xóa' }}</td>
                                <td>{{ $leave->leave_start->format('d/m/Y') }}@if (!$leave->leave_end->eq($leave->leave_start)) – {{ $leave->leave_end->format('d/m/Y') }}@endif</td>
                                <td>{{ $leave->leave_session }}</td>
                                <td>{{ $leave->leave_note }}</td>
                                <td>
                                    <form method="POST" action="{{ route('dispatch.leaves.destroy', $leave) }}" data-confirm-title="Xóa lịch nghỉ?" data-confirm="{{ $leave->staff?->staff_name }} ({{ $leave->leave_start->format('d/m') }}{{ $leave->leave_end->eq($leave->leave_start) ? '' : ' – ' . $leave->leave_end->format('d/m') }}) sẽ lại được gợi ý xếp lịch trong những ngày này." data-confirm-ok="Xóa" data-confirm-danger>
                                        @csrf @method('DELETE')
                                        <button class="lv-del" type="submit" aria-label="Xóa"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="dp-card" id="bao-tri">
            <p class="sec-title"><i class="fas fa-tools"></i> Xe bảo trì <small>xe đang bảo trì / đăng kiểm sẽ không được xếp đơn, giữ chỗ, và Sales AI báo là không trống</small></p>
            <form class="lv-form" method="POST" action="{{ route('dispatch.maintenances.store') }}">
                @csrf
                <label>Xe
                    <select name="car" required>
                        @foreach ($carList as $key => $label)
                            <option value="{{ $key }}" @selected(old('car') == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Từ ngày <input type="date" name="maintenance_start" required value="{{ old('maintenance_start') }}"></label>
                <label>Đến ngày <input type="date" name="maintenance_end" required value="{{ old('maintenance_end') }}"></label>
                <label>Buổi
                    <select name="maintenance_session">
                        @foreach ($sessions as $session)
                            <option @selected(old('maintenance_session') === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Ghi chú <input type="text" name="maintenance_note" maxlength="255" placeholder="Bảo dưỡng định kỳ, đăng kiểm, sửa máy…" value="{{ old('maintenance_note') }}"></label>
                <button type="submit" class="btn-g" style="height:34px"><i class="fas fa-plus"></i> Thêm</button>
            </form>
            @if ($maintenances->isEmpty())
                <p class="empty-note">Chưa có lịch bảo trì nào.</p>
            @else
                <table class="lv-list">
                    <thead><tr><th>Xe</th><th>Thời gian</th><th>Buổi</th><th>Ghi chú</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($maintenances as $m)
                            <tr class="{{ $m->maintenance_end->lt(today()) ? 'past' : '' }}" data-maint-row="{{ $m->id }}">
                                <td><b>{{ \App\Services\Schedule\ScheduleBoard::carLabel($m->car) }}</b></td>
                                <td>{{ $m->maintenance_start->format('d/m/Y') }}@if (!$m->maintenance_end->eq($m->maintenance_start)) – {{ $m->maintenance_end->format('d/m/Y') }}@endif</td>
                                <td>{{ $m->maintenance_session }}</td>
                                <td>{{ $m->maintenance_note }}</td>
                                <td>
                                    <form method="POST" action="{{ route('dispatch.maintenances.destroy', $m) }}"
                                        data-confirm-title="Xóa lịch bảo trì?" data-confirm="{{ \App\Services\Schedule\ScheduleBoard::carLabel($m->car) }} ({{ $m->maintenance_start->format('d/m') }}{{ $m->maintenance_end->eq($m->maintenance_start) ? '' : ' – ' . $m->maintenance_end->format('d/m') }}) sẽ lại được xếp đơn trong những ngày này." data-confirm-ok="Xóa" data-confirm-danger>
                                        @csrf @method('DELETE')
                                        <button class="lv-del" type="submit" aria-label="Xóa"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
    <script src="{{ versionResource('assets/js/tool/dispatch/dispatch.js') }}" defer></script>
@endpush
