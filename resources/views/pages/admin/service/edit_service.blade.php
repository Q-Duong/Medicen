@extends('admin_layout')
@section('admin_content')
<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                Cập nhật dịch vụ
                <span class="tools pull-right">
                    <a href="{{route('list-service')}}" class="primary-btn-submit">Quản lý</a>
                    <a class="fa fa-chevron-down" href="javascript:;"></a>
                </span>
            </header>
            <div class="panel-body">
                <div class="position-center">
                    <form action="{{route('update-service',$service->service_id)}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('service_title') ? 'has-error' : ''}}">
                            <label for="exampleInputEmail1">Tên dịch vụ</label>
                            <input type="text" name="service_title" class="input-control" placeholder="Điền tên dịch vụ" id="slug" onkeyup="ChangeToSlug();" value="{{$service->service_title}}">
                            {!! $errors->first('service_title', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group {{ $errors->has('service_slug') ? 'has-error' : ''}}">
                            <label for="exampleInputEmail1">Slug dịch vụ</label>
                            <input type="text" name="service_slug" class="input-control" placeholder="Điền Slug dịch vụ"
                                id="convert_slug" value="{{$service->service_slug}}" readonly>
                                {!! $errors->first('service_slug', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Hình ảnh dịch vụ</label>
                            <input type="file" name="service_image" class="input-control">
                            <img class="img-fluid" src="{{asset('uploads/service/'.$service->service_image)}}"
                                alt="">
                        </div>
                        <div class="form-group {{ $errors->has('service_content') ? 'has-error' : ''}}">
                            <label for="exampleInputPassword1">Nội dung dịch vụ</label>
                            <textarea name="service_content" rows=8 class="textarea-control" id="editor"
                                placeholder="Điền nội dung dịch vụ">{{$service->service_content}}
                            </textarea>
                            {!! $errors->first('service_content', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
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