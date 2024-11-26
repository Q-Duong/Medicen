@extends('layouts.default')
@section('content')
@section('title', 'Đăng ký chi tiết - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Đăng ký chi tiết</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ URL::to('/') }}">Trang chủ</a>
                        <span>Đăng ký chi tiết</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="checkout spad">
    <div class="container">
        <div class="checkout__form">
            <form action="{{ route('order.clients.store_details') }}" method="POST" id="order-form">
                @csrf
                <div class="row">
                    <div class="col-lg-8 col-md-8 centered">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="category_product_title_2">
                                    <p><i class="fas fa-newspaper"></i> Đăng ký chi tiết</p>
                                    <div class="border_bottom"></div>
                                </div>
                            </div>
                        </div>
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                        <div class="form-group ">
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group @error('ord_start_day') has-error @enderror">
                                        <label>Ngày chụp</label>
                                        <input type="date" class="input-control" name="ord_start_day"
                                            value="{{ old('ord_start_day') }}">
                                        @error('ord_start_day')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group @error('ord_time') has-error @enderror">
                                        <label>Giờ chụp</label>
                                        <input type="text" name="ord_time" class="input-control"
                                            placeholder="Điền giờ chụp" value="{{ old('ord_time') }}">
                                        @error('ord_time')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group @error('ord_cty_name') has-error @enderror">
                            <label for="exampleInputPassword1">Tên Cty</label>
                            <input type="text" name="ord_cty_name" class="input-control" placeholder="Điền tên cty"
                                value="{{ old('ord_cty_name') }}">
                            @error('ord_cty_name')
                                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group @error('customer_address') has-error @enderror">
                            <label for="exampleInputPassword1">Địa chỉ chụp</label>
                            <input type="text" name="customer_address" class="input-control"
                                placeholder="Điền địa chỉ chụp" value="{{ old('customer_address') }}">
                            @error('customer_address')
                                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group @error('order_quantity') has-error @enderror">
                            <label for="exampleInputPassword1">Số lượng</label>
                            <input type="text" name="order_quantity" class="input-control order_quantity"
                                placeholder="Điền số lượng" value="{{ old('order_quantity') }}">
                            @error('order_quantity')
                                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Thông tin liên hệ</label>
                            <div class="row">
                                <div class="col-lg-7 col-md-7 centered">
                                    <div class="form-group @error('customer_name') has-error @enderror">
                                        <input type="text" name="customer_name" class="input-control"
                                            placeholder="Điền họ tên" value="{{ old('customer_name') }}">
                                        @error('customer_name')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-5 col-md-5 centered">
                                    <div class="form-group @error('customer_phone') has-error @enderror">
                                        <input type="text" name="customer_phone" class="input-control"
                                            placeholder="Điền số điện thoại" value="{{ old('customer_phone') }}">
                                        @error('customer_phone')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">In phim</label>
                            <div class="row">
                                <div class="col-lg-4 col-md-12 centered">
                                    <section>
                                        <input type="radio" name="ord_film" value="" id="film1"
                                            class="accent" checked {{ old('ord_film') == '' ? 'checked' : '' }}>
                                        <label for="film1" class="radio-title">Không in</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12 centered">
                                    <section>
                                        <input type="radio" name="ord_film" value="Bình thường" id="film2"
                                            class="accent" {{ old('ord_film') == 'Bình thường' ? 'checked' : '' }}>
                                        <label for="film2" class="radio-title">Bình thường</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12 centered">
                                    <section>
                                        <input type="radio" name="ord_film" value="Bất thường" id="film3"
                                            class="accent" {{ old('ord_film') == 'Bất thường' ? 'checked' : '' }}>
                                        <label for="film3" class="radio-title">Bất thường</label>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <div class="form-group block-type-of-film hidden">
                            <label for="exampleInputPassword1">Hình thức in phim</label>
                            <div class="row">
                                <div class="col-lg-4 col-md-12">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN4" id="form2"
                                            class="accent" {{ old('ord_form') == 'IN4' ? 'checked' : '' }}>
                                        <label for="form2" class="radio-title">16,5 x 21,5cm (IN4)</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN12" id="form3"
                                            class="accent" {{ old('ord_form') == 'IN12' ? 'checked' : '' }}>
                                        <label for="form3" class="radio-title">11 x 10,5cm (IN12)</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN16" id="form4"
                                            class="accent" {{ old('ord_form') == 'IN16' ? 'checked' : '' }}>
                                        <label for="form4" class="radio-title">8,5 x 10,5cm (IN16)</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN8X10" id="form5"
                                            class="accent" {{ old('ord_form') == 'IN8X10' ? 'checked' : '' }}>
                                        <label for="form5" class="radio-title">20,5 x 25,5cm (IN8X10)</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN10X12" id="form6"
                                            class="accent" {{ old('ord_form') == 'IN10X12' ? 'checked' : '' }}>
                                        <label for="form6" class="radio-title">25,5 x 30,5cm (IN10X12)</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <section>
                                        <input type="radio" name="ord_form" value="PhimLon" id="form7"
                                            class="accent" {{ old('ord_form') == 'PhimLon' ? 'checked' : '' }}>
                                        <label for="form7" class="radio-title">35 x 43cm (Phim Lớn)</label>
                                    </section>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <section>
                                        <input type="radio" name="ord_form" value="Giấy đặc biệt" id="form9"
                                            class="accent" {{ old('ord_form') == 'Giấy đặc biệt' ? 'checked' : '' }}>
                                        <label for="form9" class="radio-title">Giấy đặc biệt</label>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <div class="form-group block-type-of-film-abnormal hidden">
                            <label for="exampleInputPassword1">Hình thức in phim</label>
                            <section>
                                <input type="radio" name="ord_form" value="Bệnh lý" id="form8" class="accent"
                                    {{ old('ord_form') == 'Bệnh lý' ? 'checked' : '' }}>
                                <label for="form8" class="radio-title">Bệnh lý</label>
                            </section>
                        </div>
                        <div class="form-checkbox">
                            <label class="form-label" for="confirm">
                                <input type="checkbox" id="confirm" class="form-checkbox-input confirm"
                                    name="confirm">
                                <span class="form-checkbox-indicator" aria-hidden="true">Tôi xác nhận thông tin trên
                                    chính xác và chịu trách nhiệm nếu có sai sót.</span>
                            </label>
                        </div>
                        <div class="checkout__input">
                            <button type="submit" class="primary-btn-submit btn-submit-order">Đăng ký</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
@push('js')
<script src="{{ versionResource('assets/js/order/order.built.js') }}" defer></script>
@endpush
