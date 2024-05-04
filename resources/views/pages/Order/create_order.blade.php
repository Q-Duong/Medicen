@extends('layout_not_slider')
@section('content')
@section('title', 'Create Your Account - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>ĐĂNG KÝ THUÊ XE X QUANG</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ URL::to('/') }}">Trang chủ</a>
                        <span>Đăng ký thuê xe X Quang</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="checkout spad">
    <div class="container">
        <div class="checkout__form">
            <form action="{{ URL::to('/save-order-f') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-8 col-md-8 centered">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="category_product_title_2">
                                    <p><i class="fas fa-newspaper"></i> Phiếu đăng ký </p>
                                    <div class="border_bottom"></div>
                                </div>
                            </div>
                        </div>
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-lg-7 col-md-7 centered">
                                <div class="checkout__input  {{ $errors->has('customer_name') ? 'has-error' : '' }}">
                                    <p>Thông tin liên hệ<span>*</span></p>
                                    <input type="text" name="customer_name" placeholder="Họ và tên"
                                        value="{{ old('customer_first_name') }}">
                                    {!! $errors->first(
                                        'customer_name',
                                        '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                                    ) !!}
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 centered">
                                <div class="checkout__input  {{ $errors->has('customer_phone') ? 'has-error' : '' }}">
                                    <p>&nbsp;</p>
                                    <input type="text" name="customer_phone" placeholder="Số điện thoại"
                                        value="{{ old('customer_last_name') }}">
                                    {!! $errors->first(
                                        'customer_phone',
                                        '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                                    ) !!}
                                </div>
                            </div>
                        </div>

                        <div class="checkout__input  {{ $errors->has('customer_address') ? 'has-error' : '' }}">
                            <p>Địa chỉ chụp<span>*</span></p>
                            <input type="text" name="customer_address" placeholder="Điền địa chỉ"
                                value="{{ old('customer_email') }}">
                            {!! $errors->first(
                                'customer_address',
                                '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                            ) !!}
                        </div>

                        <div class="row">
                            <div class="col-lg-6 col-md-6 centered">
                                <div class="checkout__input  {{ $errors->has('ord_start_day') ? 'has-error' : '' }}">
                                    <p>Từ ngày<span>*</span></p>
                                    <input type="date" name="ord_start_day" placeholder="Từ ngày">
                                    {!! $errors->first(
                                        'ord_start_day',
                                        '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                                    ) !!}
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 centered">
                                <div class="checkout__input  {{ $errors->has('ord_end_day') ? 'has-error' : '' }}">
                                    <p>Đến ngày<span>*</span></p>
                                    <input type="date" name="ord_end_day" placeholder="Đến ngày">
                                    {!! $errors->first(
                                        'ord_end_day',
                                        '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                                    ) !!}
                                </div>
                            </div>
                        </div>

                        <div class="checkout__input  {{ $errors->has('order_quantity') ? 'has-error' : '' }}">
                            <p>Số lượng<span>*</span></p>
                            <input type="text" name="order_quantity" placeholder="Điền số lượng chụp"
                                value="{{ old('order_quantity') }}">
                            {!! $errors->first(
                                'order_quantity',
                                '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                            ) !!}
                        </div>

                        <div class="checkout__input  {{ $errors->has('ord_select') ? 'has-error' : '' }}">
                            <p>Bộ phận chụp<span>*</span></p>
                            <div class="quantity">
                                <div class="pro-qty-2">
                                    <select name="ord_select" class="form-control">
                                        <option value="Phổi (1 Tư thế)">Phổi</option>
                                        <option value="Cột sống thắt lưng (1 Tư thế)">Cột sống thắt lưng</option>
                                        <option value="Cột sống cổ (1 Tư thế)">Cột sống cổ</option>
                                        <option value="Khác">Khác</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="checkout__input">
                            <button type="submit" value="submit" class="primary-btn-wistlist"><i
                                    class="fas fa-user-plus"></i> Đăng ký</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
