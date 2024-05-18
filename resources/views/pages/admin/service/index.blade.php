@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Damh sách dịch vụ
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
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
                            <td>
                                <a href="{{ route('service.edit', $service->id) }}" class="active style-edit"><i
                                        class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <form action="{{ route('service.destroy', $service->id) }} " method="POST">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa bài viết?')"
                                        class="active style-edit">
                                        <i class="fa fa-times text-danger text"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $getAllService->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
