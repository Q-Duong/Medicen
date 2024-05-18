@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Thêm dịch vụ
                    <span class="tools pull-right">
                        <a href="{{ route('service.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form action="{{ route('service.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group @error('service_title') has-error @enderror">
                                <label for="exampleInputEmail1">
                                    Tên dịch vụ</label>
                                <input type="text" name="service_title" class="input-control"
                                    placeholder="Điền tên dịch vụ" id="slug" onkeyup="ChangeToSlug();"
                                    value="{{ old('service_title') }}">
                                @error('service_title')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group hidden @error('service_slug') has-error @enderror ">
                                <label for="exampleInputEmail1">Slug dịch vụ</label>
                                <input type="text" name="service_slug" class="input-control"
                                    placeholder="Điền Slug dịch vụ" id="convert_slug" value="{{ old('service_slug') }}"
                                    readonly>
                                @error('service_slug')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group @error('service_image') has-error @enderror">
                                <label for="exampleInputEmail1">Hình ảnh dịch vụ</label>
                                <input type="file" name="service_image" class="input-control"
                                    value="{{ old('service_image') }}">
                                @error('service_image')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group @error('service_content') has-error @enderror">
                                <label for="exampleInputPassword1">Nội dung dịch vụ</label>
                                <textarea name="service_content" class="textarea-control" id="editor" placeholder="Điền nội dung dịch vụ">
                                {{ old('service_content') }}
                            </textarea>
                                @error('service_content')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <button type="submit" class="primary-btn-submit">Thêm dịch vụ</button>
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
