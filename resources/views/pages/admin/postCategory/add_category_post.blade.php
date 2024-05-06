@extends('admin_layout')
@section('admin_content')
<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                Thêm danh mục bài viết
                <span class="tools pull-right">
                    <a href="{{ route('list-category-post') }}" class="primary-btn-submit">Quản lý</a>
                    <a class="fa fa-chevron-down" href="javascript:;"></a>
                </span>
            </header>
            <div class="panel-body">
                <div class="position-center">
                    <form action="{{ route('save-category-post') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label for="exampleInputEmail1">Tên danh mục bài viết</label>
                            <input type="text" name="category_post_name" class="input-control" id="slug"
                                placeholder="Điền tên danh mục bài viết" onkeyup="ChangeToSlug();">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Slug danh mục bài viết</label>
                            <input type="text" name="category_post_slug" class="input-control" id="convert_slug"
                                placeholder="Điền Slug danh mục bài viết" readonly>
                        </div>
                        <button type="submit" class="primary-btn-submit">Thêm danh mục bài
                            viết</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection