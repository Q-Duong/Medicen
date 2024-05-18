@extends('admin_llayout')
@section('admin_content')
<div class="table-agile-info">

    <div class="panel panel-default">
        <div class="panel-heading">
            Thông tin đăng nhập
            <span class="tools pull-right">
                <a class="fa fa-chevron-down" href="javascript:;"></a>
                <a href="{{URL::to('/manage-order')}}" class="btn btn-info edit">Quản lý</a>
            </span>
        </div>

        <div class="table-responsive">
            @if(session('success'))
                <div class="alert alert-success">{!! session('success') !!}</div>
            @endif
            <table class="table table-striped b-t b-light">
                <thead>
                    <tr>
                        <th>Tên khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th style="width:30px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{$customer->customer_first_name}} {{$customer->customer_last_name}}</td>
                        <td>{{$customer->customer_phone}}</td>
                        <td>{{$customer->customer_email}}</td>
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
            Thông tin vận chuyển
            <span class="tools pull-right">
                <a class="fa fa-chevron-down" href="javascript:;"></a>
                <a href="{{URL::to('/manage-order')}}" class="btn btn-info edit">Quản lý</a>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped b-t b-light">
                <thead>
                    <tr>
                        <th>Tên người vận chuyển</th>
                        <th>Địa chỉ</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Ghi chú</th>
                        <th>Hình thức thanh toán</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{$shipping->shipping_first_name}} {{$shipping->shipping_last_name}}</td>
                        <td>{{$shipping->shipping_address}},
                            {{$wards->name_xaphuong}},
                            {{$province->name_quanhuyen}},
                            {{$city->name_thanhpho}}
                        </td>
                        <td>{{$shipping->shipping_phone}}</td>
                        <td>{{$shipping->shipping_email}}</td>
                        <td>{{$shipping->shipping_notes}}</td>
                        <td>@if($shipping->shipping_method==0) Chuyển khoản @else Tiền mặt @endif</td>
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
            Liệt kê chi tiết đơn hàng
            <span class="tools pull-right">
                <a class="fa fa-chevron-down" href="javascript:;"></a>
                <a href="{{URL::to('/manage-order')}}" class="btn btn-info edit">Quản lý</a>
            </span>
        </div>
        <div class="row w3-res-tb">
            
        </div>

        <div class="table-responsive">
            @if(session('success'))
            <div class="alert alert-success">{!! session('success') !!}</div>
            @endif
            <table class="table table-striped b-t b-light">
                <thead>
                    <tr>
                        <th>Thứ tự</th>
                        <th>Tên sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Giá sản phẩm</th>
                        <th>Tổng tiền</th>
                        <th>Mã khuyến mãi</th>
                        <th style="width:30px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $i = 0;
                    $fee_ship=0;
                    $total = 0;
                    @endphp
                    @foreach($order_details as $key => $details)

                    @php
                    $i++;
                    $subtotal = $details->product_price*$details->product_sales_quantity;
                    $total+=$subtotal;
                    @endphp
                    <tr class="color_qty_{{$details->product_id}}">
                        <td><i>{{$i}}</i></td>
                        <td>{{$details->product_name}}</td>

                        <td width="195px">
                            <input type="number" min="1" class="style order_qty_{{$details->product_id}}"
                                value="{{$details->product_sales_quantity}}" name="product_sales_quantity"
                                {{$order_status != 1 ? 'disabled' : ''}}>

                            <input type="hidden" name="order_qty_storage"
                                class="order_qty_storage_{{$details->product_id}}"
                                value="{{$details->product->product_quantity}}">

                            <input type="hidden" name="order_code" class="order_code" value="{{$details->order_code}}">

                            <input type="hidden" name="order_product_id" class="order_product_id"
                                value="{{$details->product_id}}">
                            @if($order_status==1)
                            <button class="btn btn-info update_quantity_order style_update"
                                data-product_id="{{$details->product_id}}" name="update_quantity_order">Cập
                                nhật</button>
                            @endif
                        </td>
                        <td>{{number_format($details->product_price,0,',','.')}}₫</td>
                        <td>{{number_format( $subtotal,0,',','.')}}₫</td>
                        <td>@if($details->product_coupon!='no')
                            {{$details->product_coupon}}
                            @else
                            Không dùng mã
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="6">
                            Tạm tính: {{number_format($total,0,',','.')}}₫
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            @php
                            $total_coupon = 0;
                            @endphp
                            @if($coupon_condition==1)
                            @php
                            $total_after_coupon = ($total*$coupon_number)/100;
                            echo 'Giảm giá: '.number_format($total_after_coupon,0,',','.').'₫';
                            $total_coupon = $total + $fee_ship - $total_after_coupon ;
                            @endphp
                            @else
                            @php
                            echo 'Giảm giá: '.number_format($coupon_number,0,',','.').'₫';
                            $total_coupon = $total + $fee_ship - $coupon_number ;
                            @endphp
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Phí vận chuyển: {{number_format($details->product_fee_ship,0,',','.')}}₫
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Tổng tiền: {{number_format($total_coupon + $details->product_fee_ship,0,',','.')}}₫
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            @foreach($order as $key => $or)
                            @if($or->order_status==1)
                            <form>
                                {{csrf_field()}}
                                <select class="form-control order_details">
                                    <option value="">----Chọn hình thức đơn hàng-----</option>
                                    <option id="{{$or->order_id}}" selected value="1">Chưa xử lý</option>
                                    <option id="{{$or->order_id}}" value="2">Đã xử lý-Đã giao hàng</option>
                                    <option id="{{$or->order_id}}" value="3">Hủy đơn hàng</option>
                                </select>
                            </form>
                            @elseif($or->order_status==2)
                            <form>
                                {{csrf_field()}}
                                <select class="form-control order_details">
                                    <option id="{{$or->order_id}}" selected value="2">Đã xử lý-Đã giao hàng</option>
                                </select>
                            </form>

                            @else
                            <form>
                                {{csrf_field()}}
                                <select class="form-control order_details">
                                    <option id="{{$or->order_id}}" selected value="3">Hủy đơn hàng</option>
                                </select>
                            </form>
                            @endif
                            @endforeach
                        </td>
                    </tr>
                </tbody>
            </table>

            <a target="_blank" href="{{url('/print-order/'.$details->order_code)}}" class="btn btn-info"><i class="fa fa-print" aria-hidden="true"></i> In đơn hàng
            </a>
        </div>
    </div>
</div>
@endsection