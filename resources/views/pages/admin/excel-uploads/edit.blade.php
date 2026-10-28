@extends('layouts.default_auth')
@section('title', 'Sửa file')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/excel-uploads.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
<a class="back" href="{{ route('excel-uploads.show', $upload) }}">@include('pages.admin.excel-uploads._icon', ['name' => 'back']) Chi tiết file</a>

<div class="panel panel--narrow">
    <h1>Sửa file</h1>

    <form method="POST" action="{{ route('excel-uploads.update', $upload) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('pages.admin.excel-uploads._form')
        <div class="form-actions">
            <a class="btn btn--ghost" href="{{ route('excel-uploads.show', $upload) }}">Huỷ</a>
            <button type="submit" class="btn btn--primary">Lưu thay đổi</button>
        </div>
    </form>
</div>
@endsection
