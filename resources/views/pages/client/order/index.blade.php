@extends('layouts.default')
@section('content')
@section('title', 'Đăng ký - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>ĐĂNG KÝ THUÊ XE X QUANG</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home.index') }}">Trang chủ</a>
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
            <form action="{{ route('order.clients.store') }}" method="POST">
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
                        <div class="row">
                            <div class="col-lg-7 col-md-7 centered">
                                <div class="form-group @error('customer_name') has-error @enderror">
                                    <p>Thông tin liên hệ<span>*</span></p>
                                    <input type="text" class="input-control" name="customer_name"
                                        placeholder="Điền họ và tên" value="{{ old('customer_name') }}">
                                    @error('customer_name')
                                        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 centered">
                                <div class="form-group @error('customer_phone') has-error @enderror">
                                    <p>&nbsp;</p>
                                    <input type="text" class="input-control" name="customer_phone"
                                        placeholder="Điền số điện thoại" value="{{ old('customer_phone') }}">
                                    @error('customer_phone')
                                        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group @error('customer_address') has-error @enderror">
                            <p>Địa chỉ chụp<span>*</span></p>
                            <input type="text" class="input-control" name="customer_address"
                                placeholder="Điền địa chỉ" value="{{ old('customer_address') }}">
                            @error('customer_address')
                                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-lg-6 col-md-6 centered">
                                <div class="form-group @error('ord_start_day') has-error @enderror">
                                    <p>Ngày chụp<span>*</span></p>
                                    <input type="date" class="input-control" name="ord_start_day"
                                        placeholder="Từ ngày" value="{{ old('ord_start_day') }}">
                                    @error('ord_start_day')
                                        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 centered">
                                <div class="form-group @error('ord_end_day') has-error @enderror">
                                    <p>&nbsp;</p>
                                    <input type="date" class="input-control" name="ord_end_day"
                                        placeholder="Đến ngày" value="{{ old('ord_end_day') }}">
                                    @error('ord_end_day')
                                        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group @error('order_quantity') has-error @enderror">
                            <p>Số lượng<span>*</span></p>
                            <input type="text" class="input-control" name="order_quantity"
                                placeholder="Điền số lượng chụp" value="{{ old('order_quantity') }}">
                            @error('order_quantity')
                                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="label">Bộ phận chụp<span>*</span></label>
                            <select id="label" name="ord_select" class="input-control">
                                <option value="Phổi (1 Tư thế)">Phổi</option>
                                <option value="Cột sống thắt lưng (1 Tư thế)">Cột sống thắt lưng
                                </option>
                                <option value="Cột sống cổ (1 Tư thế)">Cột sống cổ</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="checkout__input">
                            <button type="submit" class="primary-btn-submit">Đăng ký</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
