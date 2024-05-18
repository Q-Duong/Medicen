@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Thêm giới thiệu
                    <span class="tools pull-right">
                        <a href="{{ route('about.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form action="{{ route('about.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group @error('about_title') has-error @enderror">
                                <label for="exampleInputEmail1">
                                    Tên giới thiệu</label>
                                <input type="text" name="about_title" class="input-control"
                                    placeholder="Điền tên giới thiệu" id="slug" onkeyup="ChangeToSlug();"
                                    value="{{ old('about_title') }}">
                                @error('about_title')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group hidden @error('about_slug') has-error @enderror ">
                                <label for="exampleInputEmail1">Slug giới thiệu</label>
                                <input type="text" name="about_slug" class="input-control"
                                    placeholder="Điền Slug giới thiệu" id="convert_slug" value="{{ old('about_slug') }}"
                                    readonly>
                                @error('about_slug')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group @error('about_image') has-error @enderror">
                                <label for="exampleInputEmail1">Hình ảnh giới thiệu</label>
                                <input type="file" name="about_image" class="input-control"
                                    value="{{ old('about_image') }}">
                                @error('about_image')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group {{ $errors->has('about_content') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Nội dung giới thiệu</label>
                                <textarea name="about_content" class="textarea-control" id="editor" placeholder="Điền nội dung giới thiệu">
                                {{ old('about_content') }}
                            </textarea>
                                @error('about_content')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <button type="submit" class="primary-btn-submit">Thêm giới thiệu</button>
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
