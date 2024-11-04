@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Liệt kê bài viết
        </div>
        <div class="table-responsive table-content">
            <div id="table-scroll" class="table-scroll">
                <table class="table">
                    <thead>
                        <tr class="section-title">
                            <th>Tên bài viết</th>
                            <th>Slug bài viết</th>
                            <th>Hình sản phẩm</th>
                            <th>Danh mục bài viết</th>
                            <th style="width:60px;">Quản lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($getAllPost as $key => $post)
                            <tr>
                                <td>{{ $post->post_title }}</td>
                                <td>{{ $post->post_slug }}</td>
                                <td><img class="img-fluid" src="{{ asset('uploads/post/' . $post->post_image) }}"
                                        alt="">
                                </td>
                                <td>{{ $post->post_category_name }}</td>
                                <td class="management">
                                    <a href="{{ route('post.edit', $post->id) }}" class="management-btn"><i
                                            class="fa fa-pencil-square-o text-success text-active"></i>
                                    </a>
                                    <form action="{{ route('post.destroy', $post->id) }}" method="POST">
                                        @method('delete')
                                        @csrf
                                        <button onclick="return confirm('Bạn có chắc muốn xóa bài viết?')" href=""
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
        @include('layouts.section.admin.pagination_showing_current', [
            'items' => $getAllPost,
        ])
        {{ $getAllPost->links('pagination::custom') }}
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
@endpush
