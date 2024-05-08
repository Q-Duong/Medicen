@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Quản lý danh mục bài viết
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
                <thead>
                    <tr>
                        <th>Tên danh mục bài viết</th>
                        <th>Slug danh mục bài viết</th>
                        <th style="width:60px;">Quản lý</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getAllPostCategory as $key => $post_category)
                        <tr>
                            <td>{{ $post_category->post_category_name }}</td>
                            <td>{{ $post_category->post_category_slug }}</td>
                            <td>
                                <a href="{{ route('post_category.edit', $post_category->id) }}" class="active style-edit"><i
                                        class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <form action="{{ route('post_category.destroy', $post_category->id) }}" method="POST">
                                    @method('delete')
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Nếu bạn xóa Danh mục tin tức thì tin túc thuộc danh mục cũng sẻ bị xóa. Bạn có chắc muốn xóa danh mục?')"
                                        class="active style-edit">
                                        <i class="fa fa-times text-danger text"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $getAllPostCategory->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
