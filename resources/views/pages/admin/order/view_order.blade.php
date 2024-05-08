@extends('layouts.default_auth')
@section('admin_content')
<div class="table-agile-info">
    <input type="hidden" value="{{$order -> order_quantity}}"
    name="order_quantity">
    <div class="panel panel-default">
        <div class="panel-heading">
            Thông tin khách hàng
            <span class="tools pull-right">
                <a class="fa fa-chevron-down" href="javascript:;"></a>
                <a href="{{route('list-order')}}" class="btn btn-info edit">Quản lý</a>
            </span>
        </div>

        <div class="table-responsive">
            @if(session('success'))
                <div class="alert alert-success">{!! session('success') !!}</div>
            @endif
            <table class="table table-striped b-t b-light">
                <thead>
                    <tr>
                        <th>Họ tên khách hàng</th>
                        <th>Đơn vị thuê</th>
                        <th>Tên Cty</th>
                        <th>Số điện thoại</th>
                        <th>Địa chỉ</th>
                        <th>Địa chỉ khác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{$customer -> customer_name}}</td>
                        <td>{{$order_detail -> ord_unit}}</td>
                        <td>{{$order_detail -> ord_cty_name}}</td>
                        <td>{{$customer -> customer_phone}}</td>
                        <td>{{$customer -> customer_address}}</td>
                        <td>{{$customer -> customer_note}}</td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</div>
<br>
<div class="table-agile-info">
    <div class="panel panel-default">
        <div class="panel-heading">
            Thông tin đăng ký chụp
            <span class="tools pull-right">
                <a class="fa fa-chevron-down" href="javascript:;"></a>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped b-t b-light">
                <thead>
                    <tr>
                        <th>Bộ phận chụp</th>
                        <th>Ngày bắt đầu</th>
                        <th>Ngày kết thúc</th>
                        <th>Giờ khám</th>
                        <th>Thời hạn giao kết quả</th>
                        <th>Thông tin trả kết quả</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{$order_detail -> ord_select}}</td>
                        <td>{{$order_detail -> ord_start_day}}</td>
                        <td>{{$order_detail -> ord_end_day}}</td>
                        <td>{{$order_detail -> ord_time}}</td>
                        <td>{{$order_detail -> ord_deadline}}</td>
                        <td>{{$order_detail -> ord_deliver_results}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<br><br>
<div class="table-agile-info">
    <div class="panel panel-default">
        <div class="panel-heading">
            Chi tiết phiếu kết quả
            <span class="tools pull-right">
                <a class="fa fa-chevron-down" href="javascript:;"></a>
            </span>
        </div>
        <div class="table-responsive">
            @if(session('success'))
            <div class="alert alert-success">{!! session('success') !!}</div>
            @endif
            <table class="table table-striped b-t b-light">
                <thead>
                    <tr>
                        <th>In phim</th>
                        <th>Hình thức in phim</th>
                        <th>In phiếu</th>
                        <th>Hình thức in phiếu</th>
                        <th>In phiếu kết quả theo mẫu đơn vị</th>
                        <th>Phim & Phiếu</th>
                        <th style="width:30px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr >
                        <td>{{$order_detail -> ord_film}}</td>
                        <td>{{$order_detail -> ord_form}}</td>
                        <td>{{$order_detail -> ord_print}}</td>
                        <td>{{$order_detail -> ord_form_print}}</td>
                        <td>{{$order_detail -> ord_print_result}}</td>
                        <td>{{$order_detail -> ord_film_sheet}}</td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Số lượng: {{$order -> order_quantity}}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Đơn giá: {{number_format($order -> order_cost,0,',','.')}}₫
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Phụ thu: {{number_format($order -> order_surcharge,0,',','.')}}₫
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Phần trăm chiết khấu: {{$order -> order_percent_discount}}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Thành tiền chiết khấu: {{number_format($order -> order_discount,0,',','.')}}₫
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Tổng tiền: {{number_format($order -> order_price,0,',','.')}}₫
                        </td>
                    </tr>
                    {{-- @if(Session::get('admin_role') == 1)
                        <tr>
                            <td colspan="4">
                                @if($order -> order_status == 0)
                                    <form>
                                        @csrf
                                        <select class="form-control order_details">
                                            <option disabled>----Chọn hình thức đơn hàng-----</option>
                                            <option id="{{$order->order_id}}" disabled selected value="0">Đơn hàng mới</option>
                                            <option id="{{$order->order_id}}" value="1">Đang xử lý</option>
                                            <option id="{{$order->order_id}}" value="2">Đã cập nhật số Cas thực tế</option>
                                            <option id="{{$order->order_id}}" value="3">Đã xử lý</option>
                                            <option id="{{$order->order_id}}" value="4">Hủy đơn hàng</option>
                                        </select>
                                    </form>
                                @elseif($order -> order_status == 1)
                                    <form>
                                        @csrf
                                        <select class="form-control order_details inprocess">
                                            <option disabled>----Chọn hình thức đơn hàng-----</option>
                                            <option id="{{$order->order_id}}" disabled value="0">Đơn hàng mới</option>
                                            <option id="{{$order->order_id}}" disabled selected value="1">Đang xử lý</option>
                                            <option id="{{$order->order_id}}" value="2">Đã cập nhật số Cas thực tế</option>
                                            <option id="{{$order->order_id}}" value="3">Đã xử lý</option>
                                            <option id="{{$order->order_id}}" value="4">Hủy đơn hàng</option>
                                        </select>
                                    </form>
                                @elseif($order -> order_status == 2)
                                    <form>
                                        @csrf
                                        <select class="form-control order_details inprocess">
                                            <option disabled>----Chọn hình thức đơn hàng-----</option>
                                            <option id="{{$order->order_id}}" disabled value="0">Đơn hàng mới</option>
                                            <option id="{{$order->order_id}}" disabled value="1">Đang xử lý</option>
                                            <option id="{{$order->order_id}}" selected value="2">Đã cập nhật số Cas thực tế</option>
                                            <option id="{{$order->order_id}}" value="3">Đã xử lý</option>
                                            <option id="{{$order->order_id}}" value="4">Hủy đơn hàng</option>
                                        </select>
                                    </form>
                                @elseif($order -> order_status == 3)
                                    Trạng thái đơn hàng: <p class="status_success">ĐÃ XỬ LÝ</p>
                                @else
                                    Trạng thái đơn hàng: <p class="status_cancle">HUỶ ĐƠN HÀNG </p>
                                @endif
                            </td>
                        </tr>
                    @endif --}}
                    <tr>
                        <td colspan="6">
                            <a target="_blank" href="{{route('print-order',$order->order_id)}}" class="btn btn-danger"><i class="fa fa-print" aria-hidden="true"></i> In đơn hàng </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            
        </div>
    </div>
</div>
@endsection