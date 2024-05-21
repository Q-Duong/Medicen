@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Cập nhật bài viết
                    <span class="tools pull-right">
                        <a href="{{ route('post.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form action="{{ route('post.update', $post->id) }}" method="post" enctype="multipart/form-data">
                            @method('patch')
                            @csrf
                            <div class="form-group {{ $errors->has('post_title') ? 'has-error' : '' }}">
                                <label for="exampleInputEmail1">Tên bài viết</label>
                                <input type="text" name="post_title" class="input-control"
                                    placeholder="Điền tên bài viết" id="slug" onkeyup="ChangeToSlug();"
                                    value="{{ $post->post_title }}">
                                @error('post_title')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group hidden {{ $errors->has('post_slug') ? 'has-error' : '' }}">
                                <label for="exampleInputEmail1">Slug bài viết</label>
                                <input type="text" name="post_slug" class="input-control"
                                    placeholder="Điền Slug bài viết" id="convert_slug" value="{{ $post->post_slug }}"
                                    readonly>
                                @error('post_slug')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Hình ảnh bài viết</label>
                                <input type="file" name="post_image" class="input-control">
                                <img class="img-fluid" src="{{ asset('uploads/post/' . $post->post_image) }}"
                                    alt="">
                            </div>
                            <div class="form-group {{ $errors->has('post_desc') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Tóm tắt bài viết</label>
                                <textarea name="post_desc" class="textarea-control" rows="3" placeholder="Điền tóm tắt bài viết"
                                    style="resize:none">{{ $post->post_desc }}
                            </textarea>
                                @error('post_desc')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group {{ $errors->has('post_content') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Nội dung bài viết</label>
                                <textarea name="post_content" rows=8 class="textarea-control" id="editor" placeholder="Điền nội dung bài viết">{{ $post->post_content }}
                            </textarea>
                                @error('post_content')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Danh mục bài viết</label>
                                <select name="post_category_id" class="input-control">
                                    @foreach ($postCategory as $key => $category)
                                        <option {{ $post->id == $category->id ? 'selected' : '' }}
                                            value="{{ $category->id }}">{{ $category->post_category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="primary-btn-submit">Cập nhật bài viết</button>
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
@endpush
