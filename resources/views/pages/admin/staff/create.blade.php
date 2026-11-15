@extends('layouts.default_auth')
@section('title', 'Thêm nhân viên - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Thêm nhân viên</h1>
                <p>Nhân viên mới dùng được ngay khi xếp lịch xe.</p>
            </div>
            <a href="{{ route('staff.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách nhân viên</a>
        </div>
        <form action="{{ route('staff.store') }}" method="post" class="sf-card">
            @csrf
            @include('pages.admin.staff._form')
            <div class="sf-form-actions">
                <a href="{{ route('staff.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Thêm nhân viên</button>
            </div>
        </form>
    </div>
@endsection
