@extends('layouts.default_auth')
@section('title', 'Thêm tài khoản - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Thêm tài khoản</h1>
                <p>Tài khoản mới đăng nhập được ngay bằng email và mật khẩu này.</p>
            </div>
            <a href="{{ route('accounts.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách tài khoản</a>
        </div>
        <form action="{{ route('accounts.store') }}" method="post" class="sf-card" autocomplete="off">
            @csrf
            @include('pages.admin.accounts._form')
            <div class="sf-form-actions">
                <a href="{{ route('accounts.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Thêm tài khoản</button>
            </div>
        </form>
    </div>
@endsection
