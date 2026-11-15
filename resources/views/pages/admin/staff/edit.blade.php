@extends('layouts.default_auth')
@section('title', 'Sửa nhân viên - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Sửa nhân viên <span class="sf-muted">#{{ $staff->id }}</span></h1>
                <p>{{ $staff->staff_name }} · {{ $staff->staff_role === 'TX' ? 'Tài xế' : 'Kỹ thuật viên' }}</p>
            </div>
            <a href="{{ route('staff.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách nhân viên</a>
        </div>
        @if ($hired)
            <div class="sf-notice"><i class="fas fa-info-circle"></i> "{{ $staff->staff_name }}" là nhân sự thuê ngoài dùng trong Xếp lịch: giữ nguyên tên và vai trò, không xóa được.</div>
        @endif
        <form action="{{ route('staff.update', $staff->id) }}" method="post" class="sf-card" id="staff-form">
            @csrf
            @method('patch')
            @include('pages.admin.staff._form', ['staff' => $staff, 'hired' => $hired])
        </form>
        <div class="sf-form-actions sf-form-actions--split">
            @unless ($hired)
                <form action="{{ route('staff.destroy', $staff->id) }}" method="POST"
                    data-confirm="Xóa nhân viên {{ $staff->staff_name }}? Các lịch xe, chấm công cũ vẫn giữ tên người này, nhưng sẽ không chọn được người này khi xếp lịch mới."
                    data-confirm-title="Xóa nhân viên" data-confirm-ok="Xóa" data-confirm-danger>
                    @method('DELETE')
                    @csrf
                    <button type="submit" class="sf-btn sf-btn--danger"><i class="far fa-trash-alt"></i> Xóa nhân viên</button>
                </form>
            @else
                <span></span>
            @endunless
            <div class="sf-form-actions__right">
                <a href="{{ route('staff.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" form="staff-form" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Lưu thay đổi</button>
            </div>
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
@endpush
