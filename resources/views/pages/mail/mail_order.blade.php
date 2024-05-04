<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
        integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

</head>

<body>
    <div class="container" style="background: #888;border-radius: 12px;padding:15px;">
        <div class="col-md-12">

            <p style="text-align: center;color: #fff">Đây là email tự động. Quý khách vui lòng không trả lời email này.
            </p>
            <div class="row" style="background: #fff;padding: 15px">


                <div class="col-md-6" style="text-align: center;color: #222;font-weight: bold;font-size: 30px">
                    <h3 style="margin: 0">CÔNG TY BÁN HÀNG APPLE STORE</h3>
                    <h4 style="margin: 10px 0">XÁC NHẬN ĐƠN ĐẶT HÀNG</h4>
                </div>

                <div class="col-md-6 logo">
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Chào bạn 
                        <strong style="color: #0071e3; text-decoration: none; font-weight: 400;">
                            {{$shipping_array['customer_first_name']}}{{$shipping_array['customer_last_name']}}
                        </strong>
                    </p>
                </div>

                <div class="col-md-12">
                    <p style="color: #rgb(102,102,102); font-size: 17px;">Bạn đã đăng ký dịch vụ tại shop với thông tin như sau:</p>
                    <h4 style="color: #222; text-transform: uppercase; margin-top: 35px;">Thông tin đơn hàng</h4>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Mã đơn hàng :
                        <strong style="text-transform: uppercase; color:#0071e3">
                            {{$code['order_code']}}
                        </strong>
                    </p>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Mã khuyến mãi áp dụng : 
                        <strong style="text-transform: uppercase; color:#0071e3">
                            {{$code['coupon_code']}}
                        </strong>
                    </p>
                    
                    <!-- @php
                    $fee_ship=0;
                    @endphp -->
                    <!-- <p style="color: #rgb(102,102,102);text-transform: uppercase;">Phí vận chuyển :
                        <strong style="text-transform: uppercase; color:#0071e3">
                            {{number_format($fee_ship,0,',','.')}}₫
                        </strong>
                    </p> -->
                    
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Dịch vụ : <strong style="text-transform: none;color:#0071e3">Đặt hàng trực tuyến</strong></p>

                    <h4 style="color: #222; text-transform: uppercase; margin-top: 35px;">Thông tin người nhận</h4>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Email :
                        @if($shipping_array['shipping_email']=='')
                        <span style="color:#0071e3">Không có thông tin</span>
                        @else
                        <span style="color:#0071e3">{{$shipping_array['shipping_email']}}</span>
                        @endif
                    </p>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Họ và tên người gửi :
                        <span style="color:#0071e3">{{$shipping_array['shipping_first_name']}} {{$shipping_array['shipping_last_name']}}</span>
                    </p>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Địa chỉ nhận hàng :
                        <span style="color:#0071e3">{{$shipping_array['shipping_address']}}</span>
                    </p>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Số điện thoại :
                        <span style="color:#0071e3">{{$shipping_array['shipping_phone']}}</span>
                    </p>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Ghi chú đơn hàng :
                        @if($shipping_array['shipping_notes']=='')
                        <span style="color:#0071e3">Không có thông tin</span>
                        @else
                        <span style="color:#0071e3">{{$shipping_array['shipping_notes']}}</span>
                        @endif
                    </p>
                    <p style="color: #rgb(102,102,102);text-transform: uppercase;">Hình thức thanh toán :
                        <span style="text-transform: none;color: #0071e3">
                            @if($shipping_array['shipping_method']==0)
                            	Chuyển khoản
                            @else
                            	Tiền mặt
                            @endif
                        </span>
                    </p>
                    <h4 style="color: #222;text-transform: uppercase; margin-top: 35px;">Sản phẩm đã đặt</h4>
                    <div class="table-responsive">
                        <table class="table table-striped b-t b-light" width="100%">
                            <thead>
                                <tr style="text-align:left;">
                                    <th>Sản phẩm</th>
                                    <th>Giá tiền</th>
                                    <th>Số lượng đặt</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $fee_ship=0;
                                $sub_total = 0;
                                $total = 0;
                                @endphp

                                @foreach($cart_array as $cart)
                                @php
                                $sub_total = $cart['product_qty']*$cart['product_price'];
                                $total+=$sub_total;
                                @endphp
                                <tr>
                                    <td>{{$cart['product_name']}}</td>
                                    <td>{{number_format($cart['product_price'],0,',','.')}}₫</td>
                                    <td>{{$cart['product_qty']}}</td>
                                    <td>{{number_format($sub_total,0,',','.')}}₫</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td colspan="4"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" align="left">
                                       Phí vận chuyển:
                                    </td>
                                    @if($total>=15000000)
                                    <td colspan="1" align="left">
                                        Miễn phí giao hàng
                                    </td>
                                    @else
                                    @php
                                        $fee_ship=30000;
                                    @endphp
                                    <td colspan="1" align="left">
                                        {{number_format($fee_ship,0,',','.')}}₫
                                    </td>
                                    @endif
                                </tr>
                                <tr>
                                    <td colspan="3" align="left">
                                        Tổng tiền thanh toán khi chưa áp dụng mã giảm giá:
                                    </td>
                                    <td colspan="1" align="left">
                                        {{number_format($total + $fee_ship,0,',','.')}}₫
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p style="color:#rgb(102,102,102)">Mọi chi tiết xin liên hệ website tại : <a target="_blank"
                        href="">Shop</a>, hoặc liên hệ qua số hotline : 0917889558 hoặc 0943705326.Xin cảm ơn
                    quý khách đã đặt hàng shop chúng tôi.
                </p>
            </div>
        </div>
    </div>
</body>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
    integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous">
</script>

</html>