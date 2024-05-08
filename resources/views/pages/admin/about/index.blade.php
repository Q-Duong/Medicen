@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Damh sách giới thiệu
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
                <thead>
                    <tr>
                        <th>Tên giới thiệu</th>
                        <th>Slug giới thiệu</th>
                        <th>Hình ảnh dụch vụ</th>
                        <th style="width:60px;">Quản lý</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getAllAbout as $key => $about)
                        <tr>
                            <td>{{ $about->about_title }}</td>
                            <td>{{ $about->about_slug }}</td>
                            <td><img class="img-fluid" src="{{ asset('uploads/about/' . $about->about_image) }}"
                                    alt="">
                            </td>
                            <td>
                                <a href="{{ route('about.edit', $about->id) }}" class="active style-edit"><i
                                        class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <form action="{{ route('about.destroy', $about->id) }} " method="POST">
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
            {{ $getAllAbout->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
