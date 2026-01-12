@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Thêm Lịch KTV Và Tài Xế
                    <span class="tools pull-right">
                        <a href="{{ route('order.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <form action="{{ route('schedule.store') }}" method="post">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order_id }}">
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group">
                                    <label>Chọn Xe</label>
                                    <select name="car_name" class="input-control">
                                        @for ($i = 1; $i < 9; $i++)
                                            @if ($i == 6)
                                                <option value="{{ $i }}">Xe Thuê</option>
                                            @elseif ($i == 7)
                                                <option value="{{ $i }}">Xe Tăng Cường</option>
                                            @elseif ($i == 8)
                                                <option value="{{ $i }}">Xe Siêu Âm</option>
                                            @else
                                                <option value="{{ $i }}">Xe {{ $i }}</option>
                                            @endif
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group">
                                    <label>Chọn Tài xế</label>
                                    <select name="car_driver_name" class="input-control">
                                        @foreach ($getAllStaff as $key => $staff)
                                            @if ($staff->staff_role == 'TX')
                                                <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                    {{ $staff->staff_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group">
                                    <label>Chọn KTV 1</label>
                                    <select name="car_ktv_name_1" class="input-control">
                                        <option selected value="">Trống</option>
                                        @foreach ($getAllStaff as $key => $staff)
                                            @if ($staff->staff_role == 'KTV')
                                                <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                    {{ $staff->staff_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group">
                                    <label>Chọn KTV 2</label>
                                    <select name="car_ktv_name_2" class="input-control">
                                        <option selected value="">Trống</option>
                                        @foreach ($getAllStaff as $key => $staff)
                                            @if ($staff->staff_role == 'KTV')
                                                <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                    {{ $staff->staff_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="block-btn-schedule">
                                @if (Auth::user()->role == 0)
                                    <div class="col-lg-4">
                                        <button type="submit" value="true" name="zalo"
                                            class="primary-btn-submit button-submit">
                                            Thêm Lịch (Gửi Zalo)
                                        </button>
                                    </div>
                                    <div class="col-lg-4">
                                        <button type="submit" value="true" name="notZalo"
                                            class="primary-btn-submit button-submit">
                                            Thêm Lịch (Không gửi Zalo)
                                        </button>
                                    </div>
                                @else
                                    @if (Carbon\Carbon::now() < $order->ord_start_day)
                                        <div class="col-lg-4">
                                            <button type="submit" value="true" name="zalo"
                                                class="primary-btn-submit button-submit">
                                                Thêm Lịch (Gửi Zalo)
                                            </button>
                                        </div>
                                        <div class="col-lg-4">
                                            <button type="submit" value="true" name="notZalo"
                                                class="primary-btn-submit button-submit">
                                                Thêm Lịch (Không gửi Zalo)
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('assets/js/tool/schedule/admin/schedule.js') }}" defer></script>
@endpush
