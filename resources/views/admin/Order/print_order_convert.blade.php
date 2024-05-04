<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            body{
			font-family: DejaVu Sans;
			width: 100%;
		}
		.table-styling{
			width: 100%;
			border:1px solid #000;
			border-collapse: collapse;
			font-weight: 200;
		}
		
		.table-styling thead tr th{
			border:1px solid #000;
		}
		.table-styling tbody tr th{
			font-weight: 300;
			border:1px solid #000;
		}
		p{
			font-weight: 600;
		}
		.title{
			width:100%;
			height: 50px;
			margin-bottom:-30px;
		}
		.title h4{
			width:60%;
			float:left;
			font-size: 20px;
            text-align: center
		}
		/* .title h5{
			width:40%;
			float: left;
			text-align: center;
			margin-top:5px;
			font-weight:400;
			font-size: 15px;
		} */
		.title p{
			width:30%;
			float: right;
			text-align: center;
			font-size: 13px;
			margin-top:15px;
			font-weight:400;
		}
		.title_a{
			clear: both;
		}
		.title_a h2{
			text-align: center;
		}
		.title_a h5{
			margin-top:-26px;
			text-align: center;
			font-weight:400;
		}
		.content{
			width:100%;
			font-weight:200;
		}
		.content_left{
			float: left;
			width:55%;
			font-weight:200;
		}
		.content_right{
			float: right;
			width:45%;
			font-weight:200;
		}
		/* span{
			font-weight:200;
		} */
        .total{
            text-align: left;
            
        }
        .signature_left{
            width:300px; 
            text-align: left;
            padding: 40px 0 0 40px;
        }
        .signature_right{
            width:300px; 
            text-align: right;
            padding: 40px 40px 0 0 ;
        }
		.signal{
			width:100%;
			margin:80px 0 0 40px;
           
		}
		.signal p{
			font-weight:200;
            
		}
        </style>
    </head>
    <body>
       
        <div class="title">
			<h4>CTY TNHH ĐẦU TƯ TRANG THIẾT BỊ NAM KHÁNH LINH</h4>
			<p>59 Đường số 9, KDC Nam Sài Gòn - Thế Kỷ 21, Xã Bình Hưng, Huyện Bình Chánh, TP.Hồ Chí Minh</p>
		</div>
		<div class="title_a">
			<h2>HOÁ ĐƠN</h2>
		</div>
		<div class="content">
			<div class="content_left">
				<h5>Tên khách hàng: {{$customer->customer_name}}</h5>
				<h5>Số điện thoại: {{$customer->customer_phone}}</h5>
				<h5>Địa chỉ: {{$customer->customer_address}}</h5>
			</div>
			<div class="content_right">
			<h5>Ngày lập hóa đơn: {{$now}}</h5>
			<h5>Mã hóa đơn: {{$order->order_id}}</h5>
            <h5>&nbsp;</h5>
			
			</div>
		</div>
		
		<h4>Thông tin đơn hàng:</h4>				
			<table class="table-styling">
				<thead>
					<tr>
						<th>In phim</th>
                        <th>Hình thức in phim</th>
                        <th>In phiếu</th>
                        <th>Hình thức in phiếu</th>
                        <th>In phiếu kết quả theo mẫu đơn vị</th>
                        <th>Phim & Phiếu</th>
					</tr>
				</thead>
				<tbody>	
					<tr>
                        <th><h5>{{$order_detail -> ord_film}}</h5></th>
						<th><h5>{{$order_detail -> ord_form}}</h5></th>
                        <th><h5>{{$order_detail -> ord_print}}</h5></th>
                        <th><h5>{{$order_detail -> ord_form_print}}</h5></th>
                        <th><h5>{{$order_detail -> ord_print_result}}</h5></th>
                        <th><h5>{{$order_detail -> ord_film_sheet}}</h5></th>			
                    </tr>
                    <tr>
                        <th colspan="6" class="total">
                            <h4>Bộ phận chụp: {{$order_detail -> ord_select}}</h4>
                            <h4>Số lượng: {{$order -> order_quantity}}</h4>
                            <h4>Tổng tiền: {{number_format($order -> order_price,0,',','.')}}₫</h4>
                           
                        </th>
                </tr>v
				</tbody>
			
		    </table>
		
            <table>
                <thead>
                    <tr>
                        <th class="signature_left">Người lập phiếu</th>
                        <th class="signature_right">Người nhận</th>
                    </tr>
                </thead>
                <tbody>	
                </tbody>
            </table>
            <div class="signal">
                <h5>Huỳnh Quốc Dương</h5>
            </div>
    </body>
</html>
