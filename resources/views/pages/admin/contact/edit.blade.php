@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <a href="{{ route('contact.show') }}" class="title_info">Đi đến trang thông tin Nam Khánh Linh <i
                            class="far fa-arrow-alt-circle-right"></i></a>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form action="{{ route('contact.update') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('patch')
                            <div class="form-group @error('contact') has-error @enderror">
                                <label for="exampleInputPassword1">Thông tin liên hệ</label>
                                <textarea style="resize: none" rows="8" class="textarea-control" name="contact" id="ckeditor1"
                                    placeholder="Điền thông tin liên hệ">{{ $contact->contact }}</textarea>
                                @error('contact')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group @error('contact_map') has-error @enderror">
                                <label for="exampleInputPassword1">Bản đồ</label>
                                <textarea style="resize: none" rows="8" class="textarea-control" name="contact_map" id="exampleInputPassword1"
                                    placeholder="Điền bản đồ">{{ $contact->contact_map }}</textarea>
                                @error('contact_map')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="primary-btn-submit">Cập nhật thông tin</button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
