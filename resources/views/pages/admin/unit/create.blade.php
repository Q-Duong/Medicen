@extends('layouts.default_auth')
@section('title', 'Thêm đơn vị - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Thêm đơn vị</h1>
                <p>Đơn vị mới chọn được ngay khi lên đơn hàng.</p>
            </div>
            <a href="{{ route('unit.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách đơn vị</a>
        </div>
        <form action="{{ route('unit.store') }}" method="post" class="sf-card">
            @csrf
            @include('pages.admin.unit._form')
            <div class="sf-form-actions">
                <a href="{{ route('unit.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Thêm đơn vị</button>
            </div>
        </form>
    </div>
@endsection
