@extends('layouts.default_auth')
@section('title', 'Sửa tài khoản - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Sửa tài khoản <span class="sf-muted">#{{ $account->id }}</span></h1>
                <p>{{ $account->name }} · {{ $account->email }}</p>
            </div>
            <a href="{{ route('accounts.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách tài khoản</a>
        </div>
        <form action="{{ route('accounts.update', $account->id) }}" method="post" class="sf-card" id="account-form" autocomplete="off">
            @csrf
            @method('patch')
            @include('pages.admin.accounts._form')
            {{-- Ô loại tài khoản bị khóa (tài khoản của chính mình) không gửi lên: gửi giá trị hiện tại --}}
            @if ($isMe)<input type="hidden" name="role" value="{{ $account->role }}">@endif
        </form>
        <div class="sf-form-actions sf-form-actions--split">
            @unless ($isMe)
                <form action="{{ route('accounts.destroy', $account->id) }}" method="POST"
                    data-confirm="Xóa tài khoản {{ $account->name }} ({{ $account->email }})? Người dùng này sẽ không đăng nhập được nữa. Không hoàn tác được."
                    data-confirm-title="Xóa tài khoản" data-confirm-ok="Xóa" data-confirm-danger>
                    @method('DELETE')
                    @csrf
                    <button type="submit" class="sf-btn sf-btn--danger"><i class="far fa-trash-alt"></i> Xóa tài khoản</button>
                </form>
            @else
                <span></span>
            @endunless
            <div class="sf-form-actions__right">
                <a href="{{ route('accounts.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" form="account-form" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Lưu thay đổi</button>
            </div>
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
@endpush
