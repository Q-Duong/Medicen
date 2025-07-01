@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/filepond.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/filepond-preview.css') }}" type="text/css"
        as="style" />
@endpush
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Thêm đơn hàng
                    <span class="tools pull-right">
                        <a href="{{ route('order.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form role="form" action="{{ route('order.store') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-group @error('customer_name') has-error @enderror">
                                <label for="exampleInputEmail1">Họ tên khách hàng</label>
                                <input type="text" name="customer_name" class="input-control"
                                    placeholder="Điền họ tên khách hàng" value="{{ old('customer_name') }}">
                                @error('customer_name')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group @error('customer_phone') has-error @enderror">
                                <label for="exampleInputEmail1">Số điện thoại</label>
                                <input type="text" name="customer_phone" class="input-control"
                                    placeholder="Điền số điện thoại" value="{{ old('customer_phone') }}">
                                @error('customer_phone')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            {{-- <div class="form-group {{ $errors->has('customer_address') ? 'has-error' : ''}}" id="table_field">
                            <label for="exampleInputEmail1">Địa chỉ</label>
                            <div class="row input_address">
                                <div class="col-lg-12 centered">
                                    <input type="text" name="customer_address[]" class="form-control" placeholder="Enter email" value="{{old('customer_address')}}">
                                    <div class="bg_add" id="add"> 
                                        <i class="fa fa-plus add_address"  aria-hidden="true"></i>
                                    </div>
                                    {!! $errors->first('ord_start_day', '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> :message</div>') !!}
                                </div>
                            </div>
                        </div> --}}
                            <div class="form-group @error('customer_address') has-error @enderror">
                                <label for="exampleInputPassword1">Địa chỉ chụp</label>
                                <input type="text" name="customer_address" class="input-control"
                                    placeholder="Điền địa chỉ chụp" value="{{ old('customer_address') }}">
                                @error('customer_address')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Thêm địa chỉ khác(Nếu có)</label>
                                <textarea type="text" name="customer_note" class="textarea-control" placeholder="Điền địa chỉ khác"
                                    value="{{ old('customer_note') }}" rows="4" cols="50"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Đơn vị thuê xe</label>
                                <select name="unit_id" class="select-2 unit-id">
                                    @foreach ($getAllUnit as $key => $unit)
                                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->unit_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="suggest hidden">
                                <img src="{{asset('assets/images/suggest.jpg')}}" class="suggest-img">
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

                            <div class="form-group ">
                                <label for="exampleInputPassword1">Ngày chụp</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 centered">
                                        <div class="checkout__input @error('ord_start_day') has-error @enderror">
                                            <input type="date" class="input-control" name="ord_start_day"
                                                value="{{ old('ord_start_day') }}"
                                                @if (Auth::user()->role == 0) @else min="<?= date('Y-m-d') ?>" onkeydown="return false" @endif>
                                            @error('ord_start_day')
                                                <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                    {{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('ord_time') has-error @enderror">
                                <label for="exampleInputPassword1">Giờ khám</label>
                                <input type="text" name="ord_time" class="input-control" placeholder="Điền giờ khám"
                                    value="{{ old('ord_time') }}">
                                @error('ord_time')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Bộ phận chụp</label>
                                <select name="ord_select" class="input-control">
                                    <option selected value="Phổi (1 Tư thế)" {{ old('ord_select') == 'Phổi (1 Tư thế)' ? 'selected' : '' }}>Phổi (1 Tư thế)</option>
                                    <option value="Phổi (2 Tư thế)" {{ old('ord_select') == 'Phổi (2 Tư thế)' ? 'selected' : '' }}>Phổi (2 Tư thế)</option>
                                    <option value="Cột sống thắt lưng (1 Tư thế)" {{ old('ord_select') == 'Cột sống thắt lưng (1 Tư thế)' ? 'selected' : '' }}>Cột sống thắt lưng (1 Tư thế)</option>
                                    <option value="Cột sống thắt lưng (2 Tư thế)" {{ old('ord_select') == 'Cột sống thắt lưng (2 Tư thế)' ? 'selected' : '' }}>Cột sống thắt lưng (2 Tư thế)</option>
                                    <option value="Cột sống cổ (1 Tư thế)" {{ old('ord_select') == 'Cột sống cổ (1 Tư thế)' ? 'selected' : '' }}>Cột sống cổ (1 Tư thế)</option>
                                    <option value="Cột sống cổ (2 Tư thế)" {{ old('ord_select') == 'Cột sống cổ (2 Tư thế)' ? 'selected' : '' }}>Cột sống cổ (2 Tư thế)</option>
                                    <option value="Vai (1 Tư thế)" {{ old('ord_select') == 'Vai (1 Tư thế)' ? 'selected' : '' }}>Vai (1 Tư thế)</option>
                                    <option value="Vai (2 Tư thế)" {{ old('ord_select') == 'Vai (2 Tư thế)' ? 'selected' : '' }}>Vai (2 Tư thế)</option>
                                    <option value="Gối (1 Tư thế)" {{ old('ord_select') == 'Gối (1 Tư thế)' ? 'selected' : '' }}>Gối (1 Tư thế)</option>
                                    <option value="Gối (2 Tư thế)" {{ old('ord_select') == 'Gối (2 Tư thế)' ? 'selected' : '' }}>Gối (2 Tư thế)</option>
                                    <option value="Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng" {{ old('ord_select') == 'Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng' ? 'selected' : '' }}>Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng</option>
                                    <option value="Siêu âm Tim" {{ old('ord_select') == 'Siêu âm Tim' ? 'selected' : '' }}>Siêu âm Tim</option>
                                    <option value="Siêu âm ĐMC, Mạch Máu Chi Dưới" {{ old('ord_select') == 'Siêu âm ĐMC, Mạch Máu Chi Dưới' ? 'selected' : '' }}>Siêu âm ĐMC, Mạch Máu Chi Dưới</option>
                                    <option value="Đo loãng xương" {{ old('ord_select') == 'Đo loãng xương' ? 'selected' : '' }}>Đo loãng xương</option>
                                    <option value="Khác" {{ old('ord_select') == 'Khác' ? 'selected' : '' }}>Khác</option>
                                </select>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Bác sĩ đọc phim</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" value="" id="doctor1"
                                                class="accent" checked
                                                {{ old('ord_doctor_read') == '' ? 'checked' : '' }}>
                                            <label for="doctor1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" value="Có" id="doctor2"
                                                class="accent" {{ old('ord_doctor_read') == 'Có' ? 'checked' : '' }}>
                                            <label for="doctor2" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" value="Không" id="doctor3"
                                                class="accent"
                                                {{ old('ord_ord_doctor_readfilm') == 'Không' ? 'checked' : '' }}>
                                            <label for="doctor3" class="radio-title">Không</label>
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
                                            <input type="radio" name="ord_film" value="" id="film1"
                                                class="accent" checked {{ old('ord_film') == '' ? 'checked' : '' }}>
                                            <label for="film1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" value="Bình thường" id="film2"
                                                class="accent" {{ old('ord_film') == 'Bình thường' ? 'checked' : '' }}>
                                            <label for="film2" class="radio-title">Bình thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" value="Bất thường" id="film3"
                                                class="accent" {{ old('ord_film') == 'Bất thường' ? 'checked' : '' }}>
                                            <label for="film3" class="radio-title">Bất thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" value="Cả 2" id="film4"
                                                class="accent" {{ old('ord_film') == 'Cả 2' ? 'checked' : '' }}>
                                            <label for="film4" class="radio-title">Cả 2</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Hình thức in phim</label>
                                <div class="row">
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="ko in" id="form1"
                                                class="accent" checked {{ old('ord_form') == 'ko in' ? 'checked' : '' }}>
                                            <label for="form1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN4" id="form2"
                                                class="accent" {{ old('ord_form') == 'IN4' ? 'checked' : '' }}>
                                            <label for="form2" class="radio-title">16,5 x 21,5(IN4)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN12" id="form3"
                                                class="accent" {{ old('ord_form') == 'IN12' ? 'checked' : '' }}>
                                            <label for="form3" class="radio-title">11 x 10,5(IN12)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN16" id="form4"
                                                class="accent" {{ old('ord_form') == 'IN16' ? 'checked' : '' }}>
                                            <label for="form4" class="radio-title">8,5 x 10,5(IN16)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN8X10" id="form5"
                                                class="accent" {{ old('ord_form') == 'IN8X10' ? 'checked' : '' }}>
                                            <label for="form5" class="radio-title">20,5 x 25,5(IN8X10)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="IN10X12" id="form6"
                                                class="accent" {{ old('ord_form') == 'IN10X12' ? 'checked' : '' }}>
                                            <label for="form6" class="radio-title">25,5 x 30,5(IN10X12)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="PhimLon" id="form7"
                                                class="accent" {{ old('ord_form') == 'PhimLon' ? 'checked' : '' }}>
                                            <label for="form7" class="radio-title">35 x 43(Phim Lớn)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" value="Bệnh lý" id="form8"
                                                class="accent" {{ old('ord_form') == 'Bệnh lý' ? 'checked' : '' }}>
                                            <label for="form8" class="radio-title">Bệnh lý</label>
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

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phiếu</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="" id="print1"
                                                class="accent" checked {{ old('ord_print') == '' ? 'checked' : '' }}>
                                            <label for="print1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="Bình thường" id="print2"
                                                class="accent" {{ old('ord_print') == 'Bình thường' ? 'checked' : '' }}>
                                            <label for="print2" class="radio-title">Bình thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="Bất thường" id="print3"
                                                class="accent" {{ old('ord_print') == 'Bất thường' ? 'checked' : '' }}>
                                            <label for="print3" class="radio-title">Bất thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" value="Cả 2" id="print4"
                                                class="accent" {{ old('ord_print') == 'Cả 2' ? 'checked' : '' }}>
                                            <label for="print4" class="radio-title">Cả 2</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Hình thức in phiếu</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" value="" id="form_print1"
                                                class="accent" checked {{ old('ord_form_print') == '' ? 'checked' : '' }}>
                                            <label for="form_print1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" value="A4" id="form_print2"
                                                class="accent" {{ old('ord_form_print') == 'A4' ? 'checked' : '' }}>
                                            <label for="form_print2" class="radio-title">A4</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" value="A5" id="form_print3"
                                                class="accent" {{ old('ord_form_print') == 'A5' ? 'checked' : '' }}>
                                            <label for="form_print3" class="radio-title">A5</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phiếu kết quả theo mẫu đơn vị</label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" value="" id="print_result1"
                                                class="accent" checked
                                                {{ old('ord_print_result') == '' ? 'checked' : '' }}>
                                            <label for="print_result1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" value="Có" id="print_result2"
                                                class="accent" {{ old('ord_print_result') == 'Có' ? 'checked' : '' }}>
                                            <label for="print_result2" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" value="Không" id="print_result3"
                                                class="accent" {{ old('ord_print_result') == 'Không' ? 'checked' : '' }}>
                                            <label for="print_result3" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Phim & Phiếu</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" value="" id="film_sheet1"
                                                class="accent" checked {{ old('ord_film_sheet') == '' ? 'checked' : '' }}>
                                            <label for="film_sheet1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" value="Bấm flim vào phiếu"
                                                id="film_sheet2" class="accent"
                                                {{ old('ord_film_sheet') == 'Bấm flim vào phiếu' ? 'checked' : '' }}>
                                            <label for="film_sheet2" class="radio-title">Bấm flim vào phiếu</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet"
                                                value="Bỏ flim và phiếu vào bao thư" id="film_sheet3" class="accent"
                                                {{ old('ord_film_sheet') == 'Bỏ flim và phiếu vào bao thư' ? 'checked' : '' }}>
                                            <label for="film_sheet3" class="radio-title">Bỏ flim và phiếu vào bao thư</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet"
                                                value="Bỏ flim và phiếu vào bao vàng" id="film_sheet4" class="accent"
                                                {{ old('ord_film_sheet') == 'Bỏ flim và phiếu vào bao vàng' ? 'checked' : '' }}>
                                            <label for="film_sheet4" class="radio-title">Bỏ flim và phiếu vào bao
                                                vàng</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Ghi chú</label>
                                <textarea type="text" name="ord_note" class="textarea-control" value="{{ old('ord_note') }}" rows="4"
                                    cols="50" placeholder="Điền ghi chú"></textarea>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Cảnh báo đơn hàng</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_warning" value="Không" id="warning1"
                                                class="accent" checked
                                                {{ old('order_warning') == 'Không' ? 'checked' : '' }}>
                                            <label for="warning1" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_warning" value="Có" id="warning2"
                                                class="accent" {{ old('order_warning') == 'Có' ? 'checked' : '' }}>
                                            <label for="warning2" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('ord_deadline') has-error @enderror">
                                <label for="exampleInputPassword1">Thời hạn giao kết quả</label>
                                <input type="text" name="ord_deadline" class="input-control"
                                    placeholder="Điền thời hạn giao kết quả" value="{{ old('ord_deadline') }}">
                                @error('ord_deadline')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group @error('ord_deliver_results') has-error @enderror">
                                <label for="exampleInputPassword1">Địa chỉ & sđt giao kết quả</label>
                                <input type="text" name="ord_deliver_results" class="input-control"
                                    placeholder="Điền địa chỉ & sđt giao kết quả"
                                    value="{{ old('ord_deliver_results') }}">
                                @error('ord_deliver_results')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Địa chỉ email khách hàng</label>
                                <input type="text" name="ord_email" class="input-control"
                                    placeholder="Điền địa chỉ email khách hàng" value="{{ old('ord_email') }}">
                                {!! $errors->first(
                                    'ord_email',
                                    '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>',
                                ) !!}
                            </div>

                            <div class="form-group">
                                <label for="exampleInputEmail1">Danh sách chụp</label>
                            </div>
                            <input type="file" name="ord_list_file[]" class="filepond" multiple>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Xe đi xa hoặc gần</label>
                                <select name="accountant_distance" class="input-control">
                                    <option selected value="G" {{ old('accountant_distance') == 'G' ? 'selected' : '' }}>Gần</option>
                                    <option value="X" {{ old('accountant_distance') == 'X' ? 'selected' : '' }}>Xa</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">VAT</label>
                                <input type="text" name="order_vat" class="input-control" placeholder="Điền VAT"
                                    value="{{ old('order_vat') }}">
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Thứ tự đơn</label>
                                <div class="row">
                                    <div class="col-lg-4 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_child" value="1" id="child1"
                                                class="accent" checked {{ old('order_child') == 1 ? 'checked' : '' }}>
                                            <label for="child1" class="radio-title">Đơn chính</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_child" value="2" id="child2"
                                                class="accent" {{ old('order_child') == 2 ? 'checked' : '' }}>
                                            <label for="child2" class="radio-title">Đơn phụ 1</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_child" value="3" id="child3"
                                                class="accent" {{ old('order_child') == 3 ? 'checked' : '' }}>
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
                                                class="accent" checked {{ old('order_surcharge') == 0 ? 'checked' : '' }}>
                                            <label for="surcharge0" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_surcharge" value="1" id="surcharge1"
                                                class="accent" {{ old('order_surcharge') == 1 ? 'checked' : '' }}>
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
                                                class="accent order_all_in_one" checked
                                                {{ old('order_all_in_one') == 0 ? 'checked' : '' }}>
                                            <label for="all0" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_all_in_one" value="1" id="all1"
                                                class="accent order_all_in_one"
                                                {{ old('order_all_in_one') == 1 ? 'checked' : '' }}>
                                            <label for="all1" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                </div>
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

                            <div class="form-group block-order-cost">
                                <label for="exampleInputPassword1">Đơn giá</label>
                                <input type="text" name="order_cost" class="input-control order_cost"
                                    value="{{ old('order_cost') }}" placeholder="Điền đơn giá">
                            </div>

                            <div class="form-group @error('order_percent_discount') has-error @enderror">
                                <label for="exampleInputPassword1">Phần trăm chiết khấu</label>
                                <input type="text" name="order_percent_discount" class="input-control"
                                    value="{{ old('order_percent_discount') }}" placeholder="Điền phần trăm chiết khấu">
                                @error('order_percent_discount')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group @error('order_price') has-error @enderror">
                                <label for="exampleInputPassword1">Tổng tiền</label>
                                <input type="text" name="order_price" class="input-control order_price"
                                    placeholder="Điền tổng tiền" value="{{ old('order_price') }}">
                                @error('order_price')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="primary-btn-submit button-submit">Thêm đơn hàng
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('assets/js/support/file/filepond.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/support/file/filepond-preview.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/tool/order/order.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/support/essential.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/support/file/org-handle-file.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datepicker/1.0.10/datepicker.min.js"></script>
    <script>
        var url_file_process = "{{ route('file.process') }}";
        var url_file_revert = "{{ route('file.revert') }}";
        var files = [];
        @foreach (old('ord_list_file', []) as $file)
            files.push({
                source: '{{ $file }}',
                options: {
                    type: 'local'
                }
            });
        @endforeach
    </script>
@endpush
