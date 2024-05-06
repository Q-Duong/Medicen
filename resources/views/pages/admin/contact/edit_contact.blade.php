@extends('admin_layout')
@section('admin_content')
<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <a href="{{URL::to('/lien-he')}}" class="title_info">Đi đến trang thông tin Nam Khánh Linh <i class="far fa-arrow-alt-circle-right"></i></a>
            </header>
            <div class="panel-body">
                <div class="position-center">
                    <form action="{{route('update-contact')}}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="exampleInputPassword1">Thông tin liên hệ</label>
                            <textarea style="resize: none" rows="8" class="textarea-control"
                                name="info_contact" id="ckeditor1" placeholder="Điền thông tin liên hệ">{{$contact -> info_contact}}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">Bản đồ</label>
                            <textarea style="resize: none" rows="8" class="textarea-control" name="info_map" id="exampleInputPassword1" placeholder="Điền bản đồ">{{$contact -> info_map}}</textarea>
                        </div>
                        <button type="submit" class="primary-btn-submit">Cập nhật thông tin</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection