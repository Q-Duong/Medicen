@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Liệt kê bài viết
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
                <thead>
                    <tr>
                        <th>Tên bài viết</th>
                        <th>Slug bài viết</th>
                        <th>Hình sản phẩm</th>
                        <th>Danh mục bài viết</th>
                        <!-- <th style="table-layout: fixed;">Tóm tắt bài viết</th>
                                                <th style="table-layout: fixed;">Nội dung bài viết</th> -->
                        <th style="width:60px;">Quản lý</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getAllPost as $key => $post)
                        <tr>
                            <td>{{ $post->post_title }}</td>
                            <td>{{ $post->post_slug }}</td>
                            <td><img class="img-fluid" src="{{ asset('uploads/post/' . $post->post_image) }}" alt="">
                            </td>
                            <td>{{ $post->post_category->post_category_name }}</td>
                            <td>
                                <a href="{{ route('post.edit', $post->id) }}" class="active style-edit"><i
                                        class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>

                                <form action="{{ route('post.destroy', $post->id) }}" method="POST">
                                    @method('delete')
                                    @csrf
                                    <button onclick="return confirm('Bạn có chắc muốn xóa bài viết?')" href=""
                                        class="active style-edit">
                                        <i class="fa fa-times text-danger text"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $getAllPost->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
