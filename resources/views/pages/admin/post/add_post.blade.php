@extends('admin_layout')
@section('admin_content')
<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                Thêm bài viết
                <span class="tools pull-right">
                    <a href="{{route('list-post')}}" class="primary-btn-submit">Quản lý</a>
                    <a class="fa fa-chevron-down" href="javascript:;"></a>
                </span>
            </header>
            <div class="panel-body">
                <div class="position-center">
                    <form action="{{route('save-post')}}" method="post" enctype="multipart/form-data" id="post">
                        @csrf
                        <div class="form-group {{ $errors->has('post_title') ? 'has-error' : ''}}">
                            <label for="exampleInputEmail1">Tên bài viết</label>
                            <input type="text" name="post_title" class="input-control" placeholder="Điền tên bài viết"
                                id="slug" onkeyup="ChangeToSlug();" value="{{ old('post_title') }}">
                                {!! $errors->first('post_title', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group {{ $errors->has('post_slug') ? 'has-error' : ''}}">
                            <label for="exampleInputEmail1">Slug bài viết</label>
                            <input type="text" name="post_slug" class="input-control" placeholder="Điền Slug bài viết"
                                id="convert_slug" value="{{ old('post_slug') }}" readonly>
                                {!! $errors->first('post_slug', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group {{ $errors->has('post_image') ? 'has-error' : ''}}">
                            <label for="exampleInputEmail1">Hình ảnh bài viết</label>
                            <input type="file" name="post_image" class="input-control" value="{{ old('post_image') }}">
                            {!! $errors->first('post_image', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group {{ $errors->has('post_desc') ? 'has-error' : ''}}">
                            <label for="exampleInputPassword1">Tóm tắt bài viết</label>
                            <textarea name="post_desc" class="textarea-control" rows="3" placeholder="Điền tóm tắt bài viết">
                            {{ old('post_desc') }}
                            </textarea>
                            {!! $errors->first('post_desc', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group {{ $errors->has('post_content') ? 'has-error' : ''}}">
                            <label for="exampleInputPassword1">Nội dung bài viết</label>
                            <textarea name="post_content" class="textarea-control" id="editor"
                                placeholder="Điền nội dung bài viết" >
                                {{ old('post_content') }}
                            </textarea>
                            {!! $errors->first('post_content', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">Danh mục bài viết</label>
                            <select name="post_category_id" class="input-control">
                                @foreach($cate_post as $key =>$cate)
                                <option value="{{$cate->post_category_id}}">{{$cate->post_category_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="primary-btn-submit">Thêm bài viết</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
@push('js')
<script src="{{ versionResource('backend/js/ckeditor/ckeditor.min.js') }}" defer></script>
<script src="{{ versionResource('backend/js/ckeditor/ckeditor-custom.min.js') }}" defer></script>
<script>
    var url_upload_image_ck = "{{ route('upload-image-ck',['_token'=>csrf_token()]) }}";
</script>
@endpush