@extends('layouts.default_auth')
@section('admin_content')
    @php
        $ultraSound = ['Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng', 'Siêu âm Tim', 'Siêu âm ĐMC, Mạch Máu Chi Dưới'];
    @endphp
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Thông tin báo cáo
                    <span class="tools pull-right">
                        <a href="{{ route('order.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form role="form" action="{{ route('accountant.order.store', $accountant->order_id) }}"
                            method="post">
                            @csrf
                            <div class="form-group">
                                <label for="exampleInputEmail1">Mã đơn hàng</label>
                                <input type="text" class="input-control" value="{{ $accountant->order_id }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Mã đơn vị</label>
                                <input type="text" class="input-control" value="{{ $accountant->unit_code }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Đơn vị hợp tác</label>
                                <input type="text" class="input-control" value="{{ $accountant->unit_name }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Tên Cty</label>
                                <input type="text" name="ord_cty_name" class="input-control"
                                    value="{{ $accountant->ord_cty_name }}">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Ngày chụp</label>
                                <input type="text" class="input-control"
                                    value="{{ date('d/m/Y', strtotime($accountant->ord_start_day)) }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Bộ phận chụp</label>
                                <input type="text" class="input-control" value="{{ $accountant->ord_select }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Xe đi xa hoặc gần</label>
                                <input type="text" class="input-control"
                                    value="{{ $accountant->accountant_distance == 'G' ? 'Gần' : 'Xa' }}" disabled>
                            </div>
                            <div class="radio-group">
                                <label for="exampleInputPassword1">Trọn gói</label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_all_in_one" value="0" id="all0"
                                                class="accent order_all_in_one"
                                                {{ $accountant->order_all_in_one == 0 ? 'checked' : '' }}>
                                            <label for="all0" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_all_in_one" value="1" id="all1"
                                                class="accent order_all_in_one"
                                                {{ $accountant->order_all_in_one == 1 ? 'checked' : '' }}>
                                            <label for="all1" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Số lượng</label>
                                @if (in_array($accountant->ord_select, $ultraSound))
                                    <input type="text" name="order_quantity" class="input-control order_quantity"
                                        value="{{ $accountant->order_quantity }}">
                                @else
                                    <input type="text" class="input-control order_quantity"
                                        value="{{ $accountant->order_quantity }}" disabled>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">VAT</label>
                                <input type="text" name="order_vat" class="input-control"
                                    value="{{ $accountant->order_vat }}" placeholder="Điền VAT">
                            </div>
                            <div class="form-group block-order-cost {{ $accountant->order_all_in_one == 0 ? '' : 'hidden' }}">
                                <label for="exampleInputPassword1">Đơn giá</label>
                                <input type="text" name="order_cost" class="input-control order_cost"
                                    value="{{ number_format($accountant->order_cost, 0, ',', '.') }}"
                                    placeholder="Điền đơn giá" >
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">% Chiết khấu</label>
                                <input type="text" name="order_percent_discount" class="input-control"
                                    placeholder="Điền % Chiết khấu" value="{{ $accountant->order_percent_discount }}">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Tổng tiền</label>
                                <input type="text" name="order_price" class="input-control order_price"
                                    placeholder="Điền tổng tiền"
                                    value="{{ number_format($accountant->order_price, 0, ',', '.') }}">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Bác sĩ đọc kết quả</label>
                                <input type="text" class="input-control" placeholder="Điền bác sĩ đọc kết quả"
                                    value="{{ $accountant->accountant_doctor_read }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Hình thức in phim</label>
                                <input type="text" class="input-control ac_order_form"
                                    placeholder="Điền hình thức in phim" value="{{ $accountant->ord_form }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">35 X 43</label>
                                <input type="text" class="input-control ac_35X43" placeholder="Điền 35 X 43"
                                    value="{{ $accountant->accountant_35X43 }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Polime</label>
                                <input type="text" class="input-control" placeholder="Điền Polime"
                                    value="{{ $accountant->accountant_polime }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">8 X 10</label>
                                <input type="text" class="input-control" placeholder="Điền 8 X 10"
                                    value="{{ $accountant->accountant_8X10 }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">10 X 12</label>
                                <input type="text" class="input-control" placeholder="Điền 10 X 12"
                                    value="{{ $accountant->accountant_10X12 }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Bao phim</label>
                                <input type="text" class="input-control ac_order_film_bag" placeholder="Điền bao phim"
                                    value="{{ $accountant->accountant_film_bag }}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Ghi chú</label>
                                <textarea type="text" name="accountant_note" class="textarea-control" placeholder="Điền ghi chú" rows="4"
                                    cols="50" disabled>{{ $accountant->accountant_note }}</textarea>
                            </div>

                            @if (Auth::user()->role == 0)
                                <button type="submit" class="primary-btn-submit">
                                    Cập nhật thông tin báo cáo
                                </button>
                            @else
                                @if (
                                    $accountant->status_id == 0 ||
                                        $accountant->status_id == 1 ||
                                        $accountant->status_id == 2 ||
                                        $accountant->status_id == 4)
                                    <button type="submit" class="primary-btn-submit">
                                        Cập nhật thông tin báo cáo
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
    <script src="{{ versionResource('assets/js/tool/order/order.js') }}"></script>
@endpush
