@extends('layouts.default_auth')
@section('admin_content')
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

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Mã đơn hàng</label>
                                        <input type="text" class="input-control" value="{{ $accountant->order_id }}"
                                            disabled>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Mã đơn vị</label>
                                        <input type="text" class="input-control" value="{{ $accountant->unit_code }}"
                                            disabled>
                                    </div>
                                </div>
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

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputPassword1">Ngày chụp</label>
                                        <input type="text" class="input-control"
                                            value="{{ date('d/m/Y', strtotime($accountant->ord_start_day)) }}" disabled>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputPassword1">Bộ phận chụp</label>
                                        <input type="text" class="input-control" value="{{ $accountant->ord_select }}"
                                            disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputPassword1">Số Km đi</label>
                                        <input type="text" name="accountant_distance" class="input-control"
                                            placeholder="Số Km đi" value="{{ $accountant->accountant_distance }}">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label>Ở lại đêm</label>
                                        <select name="overnight" class="input-control">
                                            <option value="0" {{ $accountant->overnight == 0 ? 'selected' : '' }}>
                                                Không
                                            </option>
                                            <option value="1" {{ $accountant->overnight == 1 ? 'selected' : '' }}>
                                                Có
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputEmail1">Ghi chú</label>
                                <textarea type="text" name="accountant_note" class="textarea-control" placeholder="Điền ghi chú" rows="4"
                                    cols="50" disabled>{{ $accountant->accountant_note }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Tài xế phụ</label>
                                <select name="driver_assistance" class="input-control">
                                    <option value="0" {{ $accountant->driver_assistance == 0 ? 'selected' : '' }}>
                                        Không
                                    </option>
                                    <option value="1" {{ $accountant->driver_assistance == 1 ? 'selected' : '' }}>
                                        Có
                                    </option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label>Trọn gói</label>
                                        <select name="order_all_in_one" class="input-control order_all_in_one">
                                            <option value="0"
                                                {{ $accountant->order_all_in_one == 0 ? 'selected' : '' }}>
                                                Không
                                            </option>
                                            <option value="1"
                                                {{ $accountant->order_all_in_one == 1 ? 'selected' : '' }}>
                                                Có
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Số lượng</label>
                                        <input type="text" class="input-control order_quantity"
                                            value="{{ $accountant->order_quantity }}" disabled>
                                    </div>
                                </div>
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
