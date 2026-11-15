@extends('layouts.default_auth')
@section('title', 'Quản lý nhân viên - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    {{-- Giao diện ở assets/css/support/org-staff.css (bản nạp: staff.css) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $role = request('role');
        $tab = fn ($r) => route('staff.index', array_filter(['role' => $r, 'q' => request('q'), 'cchn' => request('cchn')], fn ($v) => $v !== null && $v !== ''));
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Quản lý nhân viên</h1>
                <p>Kỹ thuật viên và tài xế dùng khi xếp lịch xe, chấm công.</p>
            </div>
            <a href="{{ route('staff.create') }}" class="sf-btn sf-btn--primary"><i class="fas fa-plus"></i> Thêm nhân viên</a>
        </div>

        {{-- Lọc nhanh theo vai trò --}}
        <nav class="sf-strip" aria-label="Lọc theo vai trò">
            <a href="{{ $tab(null) }}" class="sf-strip__seg {{ $role ? '' : 'is-active' }}"><b>{{ $counts['all'] }}</b><span>Tất cả</span></a>
            <a href="{{ $tab('KTV') }}" class="sf-strip__seg sf-strip__seg--ktv {{ $role === 'KTV' ? 'is-active' : '' }}"><b>{{ $counts['KTV'] }}</b><span>Kỹ thuật viên</span></a>
            <a href="{{ $tab('TX') }}" class="sf-strip__seg sf-strip__seg--tx {{ $role === 'TX' ? 'is-active' : '' }}"><b>{{ $counts['TX'] }}</b><span>Tài xế</span></a>
        </nav>

        <form method="GET" action="{{ route('staff.index') }}" class="sf-filters">
            @if ($role)<input type="hidden" name="role" value="{{ $role }}">@endif
            <label class="sf-field sf-field--grow">
                <span>Tìm kiếm</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Họ tên hoặc số điện thoại..." autocomplete="off">
            </label>
            <label class="sf-field">
                <span>Chứng chỉ hành nghề</span>
                <select name="cchn" onchange="this.form.submit()">
                    <option value="">Tất cả</option>
                    <option value="1" @selected(request('cchn') === '1')>Có CCHN</option>
                    <option value="0" @selected(request('cchn') === '0')>Chưa có</option>
                </select>
            </label>
            <div class="sf-filters__actions">
                <button type="submit" class="sf-btn sf-btn--dark">Lọc</button>
                @if (request()->anyFilled(['q', 'cchn', 'role']))
                    <a href="{{ route('staff.index') }}" class="sf-btn sf-btn--ghost">Bỏ lọc</a>
                @endif
            </div>
        </form>

        <div class="sf-table">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Họ tên</th>
                        <th>Số điện thoại</th>
                        <th>Giới tính</th>
                        <th>Ngày sinh</th>
                        <th>Vai trò</th>
                        <th>Chứng chỉ</th>
                        <th class="sf-actions-h">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($getAllStaff as $staff)
                        @php($isHired = in_array($staff->staff_name, $hired, true))
                        <tr>
                            <td data-label="Mã" class="sf-muted">#{{ $staff->id }}</td>
                            <td data-label="Họ tên">
                                <div class="sf-person">
                                    <span class="sf-avatar sf-avatar--{{ $staff->staff_role === 'TX' ? 'tx' : 'ktv' }}">{{ mb_strtoupper(mb_substr(trim(\Illuminate\Support\Str::afterLast(trim($staff->staff_name), ' ')), 0, 1)) }}</span>
                                    <div>
                                        <b>{{ $staff->staff_name }}</b>
                                        @if ($isHired)<span class="sf-tag sf-tag--hired">Thuê ngoài</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Số điện thoại">{{ $staff->staff_phone }}</td>
                            <td data-label="Giới tính">{{ $staff->staff_gender == 0 ? 'Nam' : 'Nữ' }}</td>
                            <td data-label="Ngày sinh">{{ $staff->staff_birthday ? \Carbon\Carbon::parse($staff->staff_birthday)->format('d/m/Y') : '—' }}</td>
                            <td data-label="Vai trò">
                                <span class="sf-tag sf-tag--{{ $staff->staff_role === 'TX' ? 'tx' : 'ktv' }}">{{ $staff->staff_role === 'TX' ? 'Tài xế' : 'Kỹ thuật viên' }}</span>
                            </td>
                            <td data-label="Chứng chỉ">
                                @if ($staff->staff_certificate)
                                    <span class="sf-tag sf-tag--ok"><i class="fas fa-check-circle"></i> CCHN</span>
                                    @if ($staff->staff_certificate_note)<div class="sf-note">{{ $staff->staff_certificate_note }}</div>@endif
                                @else
                                    <span class="sf-muted">Chưa có</span>
                                @endif
                            </td>
                            <td data-label="Thao tác" class="sf-actions">
                                <a href="{{ route('staff.edit', $staff->id) }}" class="sf-icon-btn" title="Sửa thông tin"><i class="fas fa-pen"></i></a>
                                @if ($isHired)
                                    <span class="sf-icon-btn is-disabled" title="Nhân sự thuê ngoài dùng trong Xếp lịch, không xóa được"><i class="fas fa-lock"></i></span>
                                @else
                                    <form action="{{ route('staff.destroy', $staff->id) }}" method="POST"
                                        data-confirm="Xóa nhân viên {{ $staff->staff_name }}? Các lịch xe, chấm công cũ vẫn giữ tên người này, nhưng sẽ không chọn được người này khi xếp lịch mới."
                                        data-confirm-title="Xóa nhân viên" data-confirm-ok="Xóa" data-confirm-danger>
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="sf-icon-btn sf-icon-btn--danger" title="Xóa nhân viên"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="sf-empty">Không có nhân viên nào khớp bộ lọc.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sf-pager">
            @include('layouts.section.admin.pagination_showing_current', ['items' => $getAllStaff])
            {{ $getAllStaff->links('pagination::custom') }}
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
@endpush
