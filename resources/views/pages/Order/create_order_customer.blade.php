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
                        <a href="{{URL::to('/')}}">Trang chủ</a>
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
            <form action="{{URL::to('/save-order-customer')}}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-8 col-md-8 centered">
                       
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="category_product_title_2">
                                    <p><i class="fas fa-newspaper"></i> Phiếu đăng ký chi tiết</p>
                                    <div class="border_bottom"></div>
                                </div>
                            </div>
                        </div>
                        @if(session('error'))
                        <div class="alert alert-danger">
                            {{session('error')}}
                        </div>
                        @endif
                        
                        <div class="row">
                            <div class="col-lg-7 col-md-7 centered">
                                <div class="checkout__input  {{ $errors->has('customer_name') ? 'has-error' : ''}}">
                                    <p>Thông tin liên hệ<span>*</span></p>
                                    <input type="text" name="customer_name" placeholder="Họ và tên" value="{{ old('customer_name') }}">
                                    {!! $errors->first('customer_name', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 centered">
                                <div class="checkout__input  {{ $errors->has('customer_phone') ? 'has-error' : ''}}">
                                    <p>&nbsp;</p>
                                    <input type="text" name="customer_phone" placeholder="Số điện thoại" value="{{ old('customer_phone') }}">
                                    {!! $errors->first('customer_phone', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                                </div>
                            </div>
                        </div>

                        <div class="checkout__input  {{ $errors->has('customer_address') ? 'has-error' : ''}}">
                            <p>Địa chỉ chụp<span>*</span></p>
                            <input type="text" name="customer_address" placeholder="Điền địa chỉ chụp" value="{{ old('customer_address') }}">
                            {!! $errors->first('customer_address', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                        </div>

                        <div class="checkout__order__products  {{ $errors->has('customer_note') ? 'has-error' : ''}}">
                            <p>Thêm địa chỉ khác(Nếu có)</p>
                            <textarea type="text" name="customer_note" placeholder="Điền địa chỉ chụp khác" value="{{ old('customer_note') }}" class="shipping_notes" rows="5"
                            style="resize: none;"></textarea>
                            {!! $errors->first('customer_note', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                        </div>

                        <div class="checkout__input  {{ $errors->has('ord_cty_name') ? 'has-error' : ''}}">
                            <p>Tên cty<span>*</span></p>
                            <input type="text" name="ord_cty_name" placeholder="Điền tên cty" value="{{ old('ord_cty_name') }}">
                            {!! $errors->first('ord_cty_name', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                        </div>

                        <div class="row">
                            <div class="col-lg-6 col-md-6 centered">
                                <div class="checkout__input  {{ $errors->has('ord_start_day') ? 'has-error' : ''}}">
                                    <p>Ngày chụp<span>*</span></p>
                                    <input type="date" name="ord_start_day" placeholder="Từ ngày" value="{{ old('ord_start_day') }}">
                                    {!! $errors->first('ord_start_day', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 centered">
                                <div class="checkout__input  {{ $errors->has('ord_end_day') ? 'has-error' : ''}}">
                                    <p>Đến ngày<span>*</span></p>
                                    <input type="date" name="ord_end_day" placeholder="Đến ngày" value="{{ old('ord_end_day') }}">
                                    {!! $errors->first('ord_end_day', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                                </div>
                            </div>
                        </div>

                        <div class="checkout__input  {{ $errors->has('ord_time') ? 'has-error' : ''}}">
                            <p>Giờ khám<span>*</span></p>
                            <input type="text" name="ord_time" placeholder="Điền giờ khám" value="{{ old('ord_time') }}">
                            {!! $errors->first('ord_time', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                        </div>

                        <div class="checkout__input  {{ $errors->has('order_quantity') ? 'has-error' : ''}}">
                            <p>Số lượng<span>*</span></p>
                            <input type="text" name="order_quantity" placeholder="Điền số lượng chụp" value="{{ old('order_quantity') }}">
                            {!! $errors->first('order_quantity', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                        </div>

                        <div class="checkout__input  {{ $errors->has('ord_select') ? 'has-error' : ''}}">
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

                        <div class="form-group">
                            <p>Bác sĩ đọc phim<span>*</span></p>
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_doctor_read" value="" id="id1" class="accent" checked {{ old('ord_doctor_read') == '' ? 'checked' : '' }}>
                                        <label for="id1" class="accent-l">Trống</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_doctor_read" value="Có" id="id2" class="accent" {{ old('ord_doctor_read') == 'Có' ? 'checked' : '' }}>
                                        <label for="id2" class="accent-l">Có</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_doctor_read" value="Không" id="id3" class="accent" {{ old('ord_doctor_read') == 'Không' ? 'checked' : '' }}>
                                        <label for="id3" class="accent-l">Không</label>
                                    </section>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <p>In phim<span>*</span></p>
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_film" value="" id="id4" class="accent" checked {{ old('ord_film') == '' ? 'checked' : '' }}>
                                        <label for="id4" class="accent-l">Trống</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_film" value="Bình thường" id="id5" class="accent" {{ old('ord_film') == 'Bình thường' ? 'checked' : '' }}>
                                        <label for="id5" class="accent-l">Bình thường</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_film" value="Bất thường" id="id6" class="accent" {{ old('ord_film') == 'Bất thường' ? 'checked' : '' }}>
                                        <label for="id6" class="accent-l">Bất thường</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_film" value="Cả 2" id="id7" class="accent" {{ old('ord_film') == 'Cả 2' ? 'checked' : '' }}>
                                        <label for="id7" class="accent-l">Cả 2</label>
                                    </section>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <p>Hình thức in phim<span>*</span></p>
                            <div class="row">
                                <div class="col-lg-3 col-md-4">
                                    <section>
                                        <input type="radio" name="ord_form" value="ko in" id="id8" class="accent" checked {{ old('ord_form') == 'ko in' ? 'checked' : '' }}>
                                        <label for="id8" class="accent-l">Trống</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN4" id="id9" class="accent" {{ old('ord_form') == 'IN4' ? 'checked' : '' }}>
                                        <label for="id9" class="accent-l">16,5 x 21,5 cm</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN12" id="id10" class="accent" {{ old('ord_form') == 'IN12' ? 'checked' : '' }}>
                                        <label for="id10" class="accent-l">11 x 10,5 cm</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN16" id="id11" class="accent" {{ old('ord_form') == 'IN16' ? 'checked' : '' }}>
                                        <label for="id11" class="accent-l">8,5 x 10,5 cm</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN8X10" id="id12" class="accent" {{ old('ord_form') == 'IN8X10' ? 'checked' : '' }}>
                                        <label for="id12" class="accent-l">20,5 x 25,5 cm</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <section>
                                        <input type="radio" name="ord_form" value="IN10X12" id="id13" class="accent" {{ old('ord_form') == 'IN10X12' ? 'checked' : '' }}>
                                        <label for="id13" class="accent-l">25,5 x 30,5 cm</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <section>
                                        <input type="radio" name="ord_form" value="PhimLon" id="id14" class="accent" {{ old('ord_form') == 'PhimLon' ? 'checked' : '' }}>
                                        <label for="id14" class="accent-l">35 x 43 cm</label>
                                    </section>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <p>In phiếu<span>*</span></p>
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_print" value="" id="id15" class="accent" checked {{ old('ord_print') == '' ? 'checked' : '' }}>
                                        <label for="id15" class="accent-l">Trống</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_print" value="Bình thường" id="id16" class="accent" {{ old('ord_print') == 'Bình thường' ? 'checked' : '' }}>
                                        <label for="id16" class="accent-l">Bình thường</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_print" value="Bất thường" id="id17" class="accent" {{ old('ord_print') == 'Bất thường' ? 'checked' : '' }}>
                                        <label for="id17" class="accent-l">Bất thường</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_print" value="Cả 2" id="id18" class="accent" {{ old('ord_print') == 'Cả 2' ? 'checked' : '' }}> 
                                        <label for="id18" class="accent-l">Cả 2</label>
                                    </section>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <p>Hình thức in phiếu<span>*</span></p>
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_form_print" value="" id="id19" class="accent" checked {{ old('ord_form_print') == '' ? 'checked' : '' }}>
                                        <label for="id19" class="accent-l">Trống</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_form_print" value="A4" id="id20" class="accent" {{ old('ord_form_print') == 'A4' ? 'checked' : '' }}>
                                        <label for="id20" class="accent-l">A4</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_form_print" value="A5" id="id21" class="accent" {{ old('ord_form_print') == 'A5' ? 'checked' : '' }}>
                                        <label for="id21" class="accent-l">A5</label>
                                    </section>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <p>In phiếu kết quả theo mẫu đơn vị<span>*</span></p>
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_print_result" value="" id="id22" class="accent" checked {{ old('ord_print_result') == '' ? 'checked' : '' }}>
                                        <label for="id22" class="accent-l">Trống</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_print_result" value="Có" id="id23" class="accent" {{ old('ord_print_result') == 'Có' ? 'checked' : '' }}>
                                        <label for="id23" class="accent-l">Có</label>
                                    </section>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_print_result" value="Không" id="id24" class="accent" {{ old('ord_print_result') == 'Không' ? 'checked' : '' }}> 
                                        <label for="id24" class="accent-l">Không</label>
                                    </section>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <p>Phim & Phiếu<span>*</span></p>
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <section>
                                        <input type="radio" name="ord_film_sheet" value="" id="id25" class="accent" checked {{ old('ord_film_sheet') == '' ? 'checked' : '' }}>
                                        <label for="id25" class="accent-l">Trống</label>
                                    </section>
                                </div>
                                <div class="col-lg-6 col-md-6 centered">
                                    <section>
                                        <input type="radio" name="ord_film_sheet" value="Bấm flim vào phiếu" id="id26" class="accent" {{ old('ord_film_sheet') == 'Bấm flim vào phiếu' ? 'checked' : '' }}>
                                        <label for="id26" class="accent-l">Bấm flim vào phiếu</label>
                                    </section>
                                </div>
                                <div class="col-lg-6 col-md-6 centered">
                                    <section>
                                        <input type="radio" name="ord_film_sheet" value="Bỏ flim và phiếu vào bao thư" id="id27" class="accent" {{ old('ord_film_sheet') == 'Bỏ flim và phiếu vào bao thư' ? 'checked' : '' }}>
                                        <label for="id27" class="accent-l">Bỏ flim và phiếu vào bao thư</label>
                                    </section>
                                </div>
                                <div class="col-lg-6 col-md-6 centered">
                                    <section>
                                        <input type="radio" name="ord_film_sheet" value="Bỏ flim và phiếu vào bao vàng" id="id28" class="accent" {{ old('ord_film_sheet') == 'Bỏ flim và phiếu vào bao vàng' ? 'checked' : '' }}>
                                        <label for="id28" class="accent-l">Bỏ flim và phiếu vào bao vàng</label>
                                    </section>
                                </div>
                            </div>
                        </div>

                        <div class="checkout__input">
                            <p>Ghi chú</p>
                            <textarea type="text" name="ord_note" class="form-control" value="{{old('ord_note')}}" rows="4" cols="50"></textarea>
                        </div>

                        <div class="checkout__input  {{ $errors->has('ord_deadline') ? 'has-error' : ''}}">
                            <p>Thời hạn giao kết quả<span>*</span></p>
                            <input type="text" name="ord_deadline" placeholder="Điền thời hạn giao kết quả" value="{{ old('ord_deadline') }}">
                            {!! $errors->first('ord_deadline', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                        </div>

                        <div class="checkout__input  {{ $errors->has('ord_deliver_results') ? 'has-error' : ''}}">
                            <p>Địa chỉ & sđt giao kết quả<span>*</span></p>
                            <input type="text" name="ord_deliver_results" placeholder="Điền số thông tin giao kết qủa" value="{{ old('ord_deliver_results') }}">
                            {!! $errors->first('ord_deliver_results', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                        </div>

                        <div class="checkout__input">
                            <button type="submit" value="submit" class="primary-btn-wistlist"><i class="fas fa-user-plus"></i> Đăng ký</button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@endsection