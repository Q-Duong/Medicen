@extends('admin_layout')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Thêm Slider
                    <span class="tools pull-right">
                        <a href="{{ route('list-slider') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form action="{{ route('insert-slider') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="exampleInputEmail1">Tên slide</label>
                                <input type="text" name="slider_name" class="input-control"
                                    placeholder="Điền tên slide">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Hình ảnh</label>
                                <input type="file" name="slider_image" class="input-control"
                                    placeholder="Slide">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Mô tả slider</label>
                                <textarea style="resize: none" id="ckeditor3" class="textarea-control" name="slider_desc"
                                    placeholder="Mô tả danh mục"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Hiển thị</label>
                                <select name="slider_status" class="input-control input-sm m-bot15">
                                    <option value="1">Hiển thị</option>
                                    <option value="0">Ẩn</option>
                                </select>
                            </div>
                            <button type="submit" class="primary-btn-submit">Thêm slider</button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
