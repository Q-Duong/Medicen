@extends('admin_layout')
@section('admin_content')
<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                Thông tin báo cáo
                <span class="tools pull-right">
                    <a href="{{route('list-order')}}" class="primary-btn-submit">Quản lý</a>
                    <a class="fa fa-chevron-down" href="javascript:;"></a>
                </span>
            </header>
            <div class="panel-body">
                <div class="position-center">
                    <form role="form" action="{{route('save-order-accountant',$order_accountant -> order_id)}}"
                        method="post">
                        @csrf
                        <div class="form-group">
                            <label for="exampleInputEmail1">Mã đơn hàng</label>
                            <input type="text" name="order_id" class="input-control" value="{{$order_accountant -> order_id}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Mã đơn vị</label>
                            <input type="text" name="unit_code" class="input-control" value="{{$order_accountant -> order -> unit -> unit_code}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Đơn vị hợp tác</label>
                            <input type="text" name="unit_name" class="input-control" value="{{$order_accountant -> order -> unit -> unit_name}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Tên Cty</label>
                            <input type="text" name="ord_cty_name" class="input-control" value="{{$order_accountant -> order -> orderdetail -> ord_cty_name}}">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">Ngày chụp</label>
                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="checkout__input">
                                        <input type="text" class="input-control" value="{{ date('d/m/Y', strtotime($order_accountant -> order -> orderdetail -> ord_start_day)) }}" disabled>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="checkout__input">
                                        <input type="text" name="ord_start_day" class="input-control" value="{{ date('d/m/Y', strtotime($order_accountant -> order -> orderdetail -> ord_end_day)) }}" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">Xe đi xa hoặc gần</label>
                            <select name="accountant_distance" class="input-control m-bot15" disabled>
                                <option {{$order_accountant -> accountant_distance =='G' ? 'selected' : ''}}>Gần</option>
                                <option {{$order_accountant -> accountant_distance =='X' ? 'selected' : ''}}>Xa</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Số lượng</label>
                            <input type="text" name="order_quantity" class="input-control order_quantity" value="{{$order_accountant -> order -> order_quantity}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">VAT</label>
                            <input type="text" name="order_vat" class="input-control" value="{{ $order_accountant -> order -> order_vat }}" placeholder="Điền VAT">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">Đơn giá</label>
                            <input type="text" name="order_cost" class="input-control order_cost" value="{{number_format($order_accountant -> order -> order_cost,0,',','.')}}" placeholder="Điền đơn giá">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">% Chiết khấu</label>
                            <input type="text" name="order_percent_discount" class="input-control" placeholder="Điền % Chiết khấu" value="{{$order_accountant -> order -> order_percent_discount}}">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">Tổng tiền</label>
                            <input type="text" name="order_price" class="input-control order_price" placeholder="Điền tổng tiền" value="{{number_format($order_accountant -> order -> order_price,0,',','.')}}">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Bác sĩ đọc kết quả</label>
                            <input type="text" name="accountant_doctor_read" class="input-control" placeholder="Điền bác sĩ đọc kết quả" value="{{$order_accountant -> accountant_doctor_read}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Hình thức in phim</label>
                            <input type="text" name="ord_form" class="input-control ac_order_form" placeholder="Điền hình thức in phim" value="{{$order_accountant -> order -> orderdetail -> ord_form}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">35 X 43</label>
                            <input type="text" name="accountant_35X43" class="input-control ac_35X43" placeholder="Điền 35 X 43" value="{{$order_accountant -> accountant_35X43}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Polime</label>
                            <input type="text" name="accountant_polime" class="input-control" placeholder="Điền Polime" value="{{$order_accountant -> accountant_polime}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">8 X 10</label>
                            <input type="text" name="accountant_8X10" class="input-control" placeholder="Điền 8 X 10" value="{{$order_accountant -> accountant_8X10}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">10 X 12</label>
                            <input type="text" name="accountant_10X12" class="input-control" placeholder="Điền 10 X 12" value="{{$order_accountant -> accountant_10X12}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Bao phim</label>
                            <input type="text" name="accountant_film_bag" class="input-control ac_order_film_bag" placeholder="Điền bao phim" value="{{$order_accountant -> accountant_film_bag}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Ghi chú</label>
                            <textarea type="text" name="accountant_note" class="textarea-control" placeholder="Điền ghi chú"  rows="4" cols="50" disabled>{{$order_accountant -> accountant_note}}</textarea>
                        </div>
                        
                        @if (Auth::user()->role == 0)
                            <button type="submit" class="primary-btn-submit">
                                Cập nhật thông tin báo cáo
                            </button>
                        @else
                            @if($order_accountant -> order -> order_status == 0 || $order_accountant -> order -> order_status == 1 || $order_accountant -> order -> order_status == 2 || $order_accountant -> order -> order_status == 4)
                                <button type="submit" class="primary-btn-submit">
                                    Cập nhật thông tin báo cáo
                                </button>
                            @endif
                        @endif
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
@push('js')
    <script src="{{ versionResource('backend/js/tool/order.min.js') }}" defer></script>
@endpush