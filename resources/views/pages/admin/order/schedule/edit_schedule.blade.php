@extends('admin_layout')
@section('admin_content')
<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                Cập Nhật Lịch KTV Và Tài Xế
                <span class="tools pull-right">
                    <a href="{{route('list-order')}}" class="primary-btn-submit">Quản lý</a>
                    <a class="fa fa-chevron-down" href="javascript:;"></a>
                </span>
            </header>
            
            <div class="panel-body">
                <div class="container">
                    <form role="form" action="{{route('update-schedule',$order)}}" method="post" id="myForm">
                        @csrf
                        <input type="hidden" name="order_id" value="{{$order}}">
                        <div class="form-group" id="table_field">
                            <div class="row">
                                @foreach($car as $key =>$carktv)
                                    <div class="col-lg-6 col-md-6">
                                        <section>
                                            @if($carktv -> car_active == 1)
                                                <button type="button" id="{{$carktv->car_name}}" name="{{$key + 1}}" class="primary-btn-schedule" onclick="CancleSchedule(event)">
                                                    Huỷ Lịch
                                                </button>
                                            @endif
                                            <input type="checkbox" id="checkCar{{$key + 1}}" onclick="handleSchedule({{$key + 1}})" {{$carktv->car_active == 1 ? 'checked' : ''}} >
                                            <input type="hidden" name="car_name[]" value="{{$carktv->car_name}}">
                                            <input type="hidden" name="car_active[]" value="{{$carktv->car_active}}" id="carActive{{$key + 1}}">
                                            <label for="checkCar{{$key + 1}}" class="accent-l">
                                                @if($key == 5)
                                                    Xe Thuê
                                                @elseif($key == 6)
                                                    Xe Tăng Cường
                                                @else
                                                    Xe {{$key + 1}}
                                                @endif
                                            </label>
                                        </section>
                                        <div id="car{{$key + 1}}" {{$carktv->car_active == 1 ? '':'style=display:none'}}>
                                            <div class="form-group">
                                                <label for="exampleInputPassword1">Tài xế</label>
                                                <select name="car_driver_name[]" id="select_driver_{{$key + 1}}" class="input-control">
                                                    <option {{$carktv -> car_driver_name == 'null' ? 'selected' : ''}} value="">Chọn KTV</option>
                                                    @foreach($staff as $stf)
                                                        @if($stf -> staff_role == 'TX')
                                                            <option {{$carktv -> car_driver_name == $stf->staff_name ? 'selected' : ''}} 
                                                            value="{{$stf->staff_name}}_{{$stf->staff_phone}}">
                                                                {{$stf->staff_name}}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="exampleInputPassword1">KTV 1</label>
                                                <select name="car_ktv_name_1[]" id="select_car_{{$key + 1}}" class="input-control">
                                                    <option {{$carktv -> car_ktv_name_1 == 'null' ? 'selected' : ''}} value="">Chọn KTV</option>
                                                    @foreach($staff as $stf)
                                                        @if($stf -> staff_role == 'KTV')
                                                            <option {{$carktv -> car_ktv_name_1 == $stf->staff_name ? 'selected' : ''}}
                                                            value="{{$stf->staff_name}}_{{$stf->staff_phone}}">
                                                                {{$stf->staff_name}}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="exampleInputPassword1">KTV 2</label>
                                                <select name="car_ktv_name_2[]" id="select_car_{{$key + 8}}" class="input-control">
                                                    <option {{$carktv -> car_ktv_name_2 == 'null' ? 'selected' : ''}} value="">Chọn KTV</option>
                                                    @foreach($staff as $stf)
                                                        @if($stf -> staff_role == 'KTV')
                                                            <option {{$carktv -> car_ktv_name_2 == $stf->staff_name ? 'selected' : ''}}
                                                            value="{{$stf->staff_name}}_{{$stf->staff_phone}}">
                                                                {{$stf->staff_name}}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <div class="block-btn-schedule">
                                <div class="col-lg-4">
                                    <button value="true" name="zalo"  class="primary-btn-submit">
                                        Cập Nhật Lịch (Gửi Zalo)
                                    </button>
                                </div>
                                <div class="col-lg-4">
                                    <button value="true" name="notZalo" class="primary-btn-submit">
                                        Cập Nhật Lịch (Không gửi Zalo)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
@push('js')
<script type="text/javascript">
     var url_cancle_schedule = "{{route('cancle-schedule')}}";
        var url_upload_image_ck = "{{ route('upload-image-ck',['_token'=>csrf_token()]) }}";
        var url_delete_file_order = "{{route('url-delete-file-order',':path')}}";
</script>
    <script src="{{ versionResource('backend/js/tool/order.min.js') }}" defer></script>
@endpush