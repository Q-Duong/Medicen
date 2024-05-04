@extends('admin_layout')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Đơn hàng coppy từ đơn hàng {{ $order->order_id }}
                    <span class="tools pull-right">
                        <a href="{{ route('list-order') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>

                <div class="panel-body">
                    <div class="position-center">
                        <form role="form" action="{{ route('save-order-coppy') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="order_detail_id"
                                value="{{ $order->order->orderdetail->order_detail_id }}">
                            <div class="form-group {{ $errors->has('customer_name') ? 'has-error' : '' }}">
                                <label for="exampleInputEmail1">Họ tên khách hàng</label>
                                <input type="text" name="customer_name" class="input-control"
                                    placeholder="Điền họ tên khách hàng"
                                    value="{{ $order->order->customer->customer_name }}">
                                {!! $errors->first(
                                    'customer_name',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group {{ $errors->has('customer_phone') ? 'has-error' : '' }}">
                                <label for="exampleInputEmail1">Số điện thoại</label>
                                <input type="text" name="customer_phone" class="input-control"
                                    placeholder="Điền số điện thoại" value="{{ $order->order->customer->customer_phone }}">
                                {!! $errors->first(
                                    'customer_phone',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group {{ $errors->has('customer_address') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Địa chỉ chụp</label>
                                <input type="text" name="customer_address" class="input-control"
                                    placeholder="Điền địa chỉ chụp" value="{{ $order->order->customer->customer_address }}">
                                {!! $errors->first(
                                    'customer_address',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group {{ $errors->has('customer_note') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Thêm địa chỉ khác(Nếu có)</label>
                                <textarea type="text" name="customer_note" class="textarea-control" placeholder="Điền địa chỉ khác" rows="4"
                                    cols="50">
                                {{ $order->order->customer->customer_note }}
                            </textarea>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Đơn vị thuê xe</label>
                                <select name="unit_id" class="select-2">
                                    @foreach ($getAllUnit as $key => $unit)
                                        <option {{ $unit->unit_id == $order->order->unit->unit_id ? 'selected' : '' }}
                                            value="{{ $unit->unit_id }}">{{ $unit->unit_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group {{ $errors->has('ord_cty_name') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Tên Cty</label>
                                <input type="text" name="ord_cty_name" class="input-control" placeholder="Điền tên cty"
                                    value="{{ $order->order->orderdetail->ord_cty_name }}">
                                {!! $errors->first(
                                    'ord_cty_name',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Ngày chụp</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 centered">
                                        <div
                                            class="checkout__input  {{ $errors->has('ord_start_day') ? 'has-error' : '' }}">
                                            <input type="date" class="input-control" name="ord_start_day"
                                                value="{{ $order->order->orderdetail->ord_start_day }}">
                                            {!! $errors->first(
                                                'ord_start_day',
                                                '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                                            ) !!}
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 centered">
                                        <div class="checkout__input  {{ $errors->has('ord_end_day') ? 'has-error' : '' }}">
                                            <input type="date" class="input-control" name="ord_end_day"
                                                value="{{ $order->order->orderdetail->ord_end_day }}">
                                            {!! $errors->first(
                                                'ord_end_day',
                                                '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>',
                                            ) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group {{ $errors->has('ord_time') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Giờ khám</label>
                                <input type="text" name="ord_time" class="input-control" placeholder="Điền giờ khám"
                                    value="{{ $order->order->orderdetail->ord_time }}">
                                {!! $errors->first(
                                    'ord_time',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Bộ phận chụp</label>
                                <select name="ord_select" class="input-control">
                                    <option value="Phổi (1 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Phổi (1 Tư thế)' ? 'selected' : '' }}>
                                        Phổi (1 Tư thế)</option>
                                    <option value="Phổi (2 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Phổi (2 Tư thế)' ? 'selected' : '' }}>
                                        Phổi (2 Tư thế)</option>
                                    <option value="Cột sống thắt lưng (1 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Cột sống thắt lưng (1 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống thắt lưng (1 Tư thế)</option>
                                    <option value="Cột sống thắt lưng (2 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Cột sống thắt lưng (2 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống thắt lưng (2 Tư thế)</option>
                                    <option value="Cột sống cổ (1 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Cột sống cổ (1 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống cổ (1 Tư thế)</option>
                                    <option value="Cột sống cổ (2 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Cột sống cổ (2 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống cổ (2 Tư thế)</option>
                                    <option value="Vai (1 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Vai (1 Tư thế)' ? 'selected' : '' }}>
                                        Vai (1 Tư thế)</option>
                                    <option value="Vai (2 Tư thế)2"
                                        {{ $order->order->orderdetail->ord_select == 'Vai (2 Tư thế)' ? 'selected' : '' }}>
                                        Vai (2 Tư thế)</option>
                                    <option value="Gối (1 Tư thế)"
                                        {{ $order->order->orderdetail->ord_select == 'Gối (1 Tư thế)' ? 'selected' : '' }}>
                                        Gối (1 Tư thế)</option>
                                    <option
                                        value="Gối (2 Tư thế)"{{ $order->order->orderdetail->ord_select == 'Gối (2 Tư thế)' ? 'selected' : '' }}>
                                        Gối (2 Tư thế)</option>
                                    <option value="Khác"
                                        {{ $order->order->orderdetail->ord_select == 'Khác' ? 'selected' : '' }}>Khác
                                    </option>
                                </select>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Bác sĩ đọc phim</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" value="" id="id1"
                                                class="accent" checked
                                                {{ $order->order->orderdetail->ord_doctor_read == '' ? 'checked' : '' }}>
                                            <label for="id1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" value="Có" id="id2"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_doctor_read == 'Có' ? 'checked' : '' }}>
                                            <label for="id2" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" value="Không" id="id3"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_doctor_read == 'Không' ? 'checked' : '' }}>
                                            <label for="id3" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phim</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" value="" id="id4"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_film == '' ? 'checked' : '' }}>
                                            <label for="id4" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" value="Bình thường" id="id5"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_film == 'Bình thường' ? 'checked' : '' }}>
                                            <label for="id5" class="radio-title">Bình thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" value="Bất thường" id="id6"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_film == 'Bất thường' ? 'checked' : '' }}>
                                            <label for="id6" class="radio-title">Bất thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" value="Cả 2" id="id7"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_film == 'Cả 2' ? 'checked' : '' }}>
                                            <label for="id7" class="radio-title">Cả 2</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Hình thức in phim</label>
                                <div class="row">
                                    <div class="col-lg-4 col-md-12 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="ko in" id="id8"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'ko in' ? 'checked' : '' }}>
                                            <label for="id8" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN4" id="id9"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'IN4' ? 'checked' : '' }}>
                                            <label for="id9" class="radio-title">16,5 x 21,5(IN4)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN12" id="id10"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'IN12' ? 'checked' : '' }}>
                                            <label for="id10" class="radio-title">11 x 10,5(IN12)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN16" id="id11"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'IN16' ? 'checked' : '' }}>
                                            <label for="id11" class="radio-title">8,5 x 10,5(IN16)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN8X10" id="id12"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'IN8X10' ? 'checked' : '' }}>
                                            <label for="id12" class="radio-title">20,5 x 25,5(IN8X10)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN10X12" id="id13"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'IN10X12' ? 'checked' : '' }}>
                                            <label for="id13" class="radio-title">25,5 x 30,5(IN10X12)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="PhimLon" id="id14"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'PhimLon' ? 'checked' : '' }}>
                                            <label for="id14" class="radio-title">35 x 43(Phim Lớn)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" value="Bệnh lý" id="id32"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form == 'Bệnh lý' ? 'checked' : '' }}>
                                            <label for="id32" class="radio-title">Bệnh lý</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phiếu</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="" id="id15"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_print == '' ? 'checked' : '' }}>
                                            <label for="id15" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="Bình thường" id="id16"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_print == 'Bình thường' ? 'checked' : '' }}>
                                            <label for="id16" class="radio-title">Bình thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="Bất thường" id="id17"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_print == 'Bất thường' ? 'checked' : '' }}>
                                            <label for="id17" class="radio-title">Bất thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="Cả 2" id="id18"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_print == 'Cả 2' ? 'checked' : '' }}>
                                            <label for="id18" class="radio-title">Cả 2</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Hình thức in phiếu</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" value="" id="id19"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form_print == '' ? 'checked' : '' }}>
                                            <label for="id19" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" value="A4" id="id20"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form_print == 'A4' ? 'checked' : '' }}>
                                            <label for="id20" class="radio-title">A4</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" value="A5" id="id21"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_form_print == 'A5' ? 'checked' : '' }}>
                                            <label for="id21" class="radio-title">A5</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phiếu kết quả theo mẫu đơn vị</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" value="" id="id22"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_print_result == '' ? 'checked' : '' }}>
                                            <label for="id22" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" value="Có" id="id23"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_print_result == 'Có' ? 'checked' : '' }}>
                                            <label for="id23" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" value="Không" id="id24"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_print_result == 'Không' ? 'checked' : '' }}>
                                            <label for="id24" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered"></div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Phim & Phiếu</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" value="" id="id25"
                                                class="accent"
                                                {{ $order->order->orderdetail->ord_film_sheet == '' ? 'checked' : '' }}>
                                            <label for="id25" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" value="Bấm flim vào phiếu"
                                                id="id26" class="accent"
                                                {{ $order->order->orderdetail->ord_film_sheet == 'Bấm flim vào phiếu' ? 'checked' : '' }}>
                                            <label for="id26" class="radio-title">Bấm flim vào phiếu</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet"
                                                value="Bỏ flim và phiếu vào bao thư" id="id27" class="accent"
                                                {{ $order->order->orderdetail->ord_film_sheet == 'Bỏ flim và phiếu vào bao thư' ? 'checked' : '' }}>
                                            <label for="id27" class="radio-title">Bỏ flim và phiếu vào bao thư</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet"
                                                value="Bỏ flim và phiếu vào bao vàng" id="id28" class="accent"
                                                {{ $order->order->orderdetail->ord_film_sheet == 'Bỏ flim và phiếu vào bao vàng' ? 'checked' : '' }}>
                                            <label for="id28" class="radio-title">Bỏ flim và phiếu vào bao
                                                vàng</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Ghi chú</label>
                                <textarea type="text" name="ord_note" class="textarea-control" rows="4" cols="50"
                                    placeholder="Điền ghi chú">
                                {{ $order->order->orderdetail->ord_note }}</textarea>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Cảnh báo đơn hàng</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_warning" value="Không" id="id29"
                                                class="accent"
                                                {{ $order->order->order_warning == 'Không' ? 'checked' : '' }}>
                                            <label for="id29" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_warning" value="Có" id="id30"
                                                class="accent"
                                                {{ $order->order->order_warning == 'Có' ? 'checked' : '' }}>
                                            <label for="id30" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group {{ $errors->has('ord_deadline') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Thời hạn giao kết quả</label>
                                <input type="text" name="ord_deadline" class="input-control"
                                    placeholder="Điền thời hạn giao kết quả"
                                    value="{{ $order->order->orderdetail->ord_deadline }}">
                                {!! $errors->first(
                                    'ord_deadline',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group {{ $errors->has('ord_deliver_results') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Địa chỉ & sđt giao kết quả</label>
                                <input type="text" name="ord_deliver_results" class="input-control"
                                    placeholder="Điền địa chỉ & sđt giao kết quả"
                                    value="{{ $order->order->orderdetail->ord_deliver_results }}">
                                {!! $errors->first(
                                    'ord_deliver_results',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group {{ $errors->has('ord_email') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Địa chỉ email khách hàng</label>
                                <input type="text" name="ord_email" class="input-control"
                                    placeholder="Điền Địa chỉ email khách hàng"
                                    value="{{ $order->order->orderdetail->ord_email }}">
                                {!! $errors->first(
                                    'ord_email',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group">
                                <label for="exampleInputEmail1">Danh sách chụp</label>
                                <input type="file" name="ord_list_file[]" class="input-control fied_file" multiple>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Xe đi xa hoặc gần</label>
                                <select name="accountant_distance" class="input-control m-bot15">
                                    <option {{ $order->accountant_distance == 'G' ? 'selected' : '' }} value="G">Gần
                                    </option>
                                    <option {{ $order->accountant_distance == 'X' ? 'selected' : '' }} value="X">Xa
                                    </option>
                                </select>
                            </div>

                            <div class="form-group {{ $errors->has('order_vat') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">VAT</label>
                                <input type="text" name="order_vat" class="input-control" placeholder="Điền VAT"
                                    value="{{ $order->order->order_vat }}">
                                {!! $errors->first(
                                    'order_vat',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Thứ tự đơn</label>
                                <div class="row">
                                    <div class="col-lg-4 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_child" value="1" id="child1"
                                                class="accent" {{ $order->order->order_child == 1 ? 'checked' : '' }}>
                                            <label for="child1" class="radio-title">Đơn chính</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_child" value="2" id="child2"
                                                class="accent" {{ $order->order->order_child == 2 ? 'checked' : '' }}>
                                            <label for="child2" class="radio-title">Đơn phụ 1</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_child" value="3" id="child3"
                                                class="accent" {{ $order->order->order_child == 3 ? 'checked' : '' }}>
                                            <label for="child3" class="radio-title">Đơn phụ 2</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Đơn phụ thu</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_surcharge" value="0" id="surcharge0"
                                                class="accent"{{ $order->order->order_surcharge == 0 ? 'checked' : '' }}>
                                            <label for="surcharge0" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_surcharge" value="1" id="surcharge1"
                                                class="accent" {{ $order->order->order_surcharge == 1 ? 'checked' : '' }}>
                                            <label for="surcharge1" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Trọn gói</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_all_in_one" value="0" id="all0"
                                                class="accent order_all_in_one"
                                                {{ $order->order->order_all_in_one == 0 ? 'checked' : '' }}>
                                            <label for="all0" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_all_in_one" value="1" id="all1"
                                                class="accent order_all_in_one"
                                                {{ $order->order->order_all_in_one == 1 ? 'checked' : '' }}>
                                            <label for="all1" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group {{ $errors->has('order_quantity') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Số lượng</label>
                                <input type="text" name="order_quantity" class="input-control order_quantity"
                                    placeholder="Điền số lượng" value="{{ $order->order->order_quantity }}">
                                {!! $errors->first(
                                    'order_quantity',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div
                                class="form-group block-order-cost {{ $errors->has('order_cost') ? 'has-error' : '' }} {{ $order->order->order_all_in_one == 0 ? '' : 'hidden' }}">
                                <label for="exampleInputPassword1">Đơn giá</label>
                                <input type="text" name="order_cost" class="input-control order_cost"
                                    value="{{ number_format($order->order->order_cost, 0, ',', '.') }}"
                                    placeholder="Điền đơn giá">
                                {!! $errors->first(
                                    'order_cost',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Phần trăm chiết khấu</label>
                                <input type="text" name="order_percent_discount" class="input-control"
                                    value="{{ $order->order->order_percent_discount }}"
                                    placeholder="Điền phần trăm chiết khấu">
                            </div>

                            <div class="form-group {{ $errors->has('order_price') ? 'has-error' : '' }}">
                                <label for="exampleInputPassword1">Tổng tiền</label>
                                <input type="text" name="order_price" class="input-control order_price"
                                    placeholder="Điền tổng tiền"
                                    value="{{ number_format($order->order->order_price, 0, ',', '.') }}">
                                {!! $errors->first(
                                    'order_price',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>
                            @if (Auth::user()->role == 0)
                                <button type="submit" class="primary-btn-filter">Thêm thông tin đơn
                                    hàng coppy
                                </button>
                            @else
                                @if (
                                    ($order->order->order_status == 0 || $order->order->order_status == 1 || $order->order->order_status == 2) &&
                                        Carbon\Carbon::now() < $order->order->orderdetail->ord_start_day)
                                    <button type="submit" class="primary-btn-filter">Thêm thông tin đơn
                                        hàng coppy
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
