@extends('layouts.default_auth')
@section('title', 'Thêm file')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/excel-uploads.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
<a class="back" href="{{ route('excel-uploads.index') }}">@include('pages.admin.excel-uploads._icon', ['name' => 'back']) Danh sách file</a>

<div class="panel panel--narrow">
    <h1>Thêm file Excel</h1>
    <p class="muted">File sau khi thêm sẽ ở trạng thái “Chờ tiếp nhận”.</p>

    <form method="POST" action="{{ route('excel-uploads.store') }}" enctype="multipart/form-data">
        @csrf
        @include('pages.admin.excel-uploads._form')
        <div class="form-actions">
            <a class="btn btn--danger" href="{{ route('excel-uploads.index') }}">Huỷ</a>
            <button type="submit" class="btn btn--primary">Thêm file</button>
        </div>
    </form>
</div>
@endsection
