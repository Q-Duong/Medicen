@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Damh sách dịch vụ
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light table-bordered">
                <thead>
                    <tr>
                        <th>Tên dịch vụ</th>
                        <th>Slug dịch vụ</th>
                        <th>Hình ảnh dụch vụ</th>
                        <th style="width:60px;">Quản lý</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getAllService as $key => $service)
                        <tr>
                            <td>{{ $service->service_title }}</td>
                            <td>{{ $service->service_slug }}</td>
                            <td><img class="img-fluid" src="{{ asset('uploads/service/' . $service->service_image) }}"
                                    alt="">
                            </td>
                            <td class="management">
                                <a href="{{ route('service.edit', $service->id) }}" class="management-btn"><i
                                        class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <form action="{{ route('service.destroy', $service->id) }} " method="POST">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa bài viết?')"
                                        class="management-btn button-submit">
                                        <i class="fa fa-times text-danger text"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
@endpush