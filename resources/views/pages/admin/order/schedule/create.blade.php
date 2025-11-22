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
                            <div class="col-lg-6 col-md-6">
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
                            <div class="col-lg-6 col-md-6">
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
                            <div class="col-lg-6 col-md-6">
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
                            <div class="col-lg-6 col-md-6">
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

                            {{-- <div class="row">
                                    @for ($i = 1; $i < 9; $i++)
                                        <div class="col-lg-6 col-md-6">
                                            <section>
                                                <input type="checkbox" id="checkCar{{ $i }}"
                                                    onclick="handleSchedule({{ $i }})">
                                                <input type="hidden" name="car_name[]" value="{{ $i }}">
                                                <input type="hidden" name="car_active[]" value="0"
                                                    id="carActive{{ $i }}">
                                                <label for="checkCar{{ $i }}" class="accent-l">
                                                    @if ($i == 6)
                                                        Xe Thuê
                                                    @elseif ($i == 7)
                                                        Xe Tăng Cường
                                                    @elseif ($i == 8)
                                                        Xe Siêu Âm
                                                    @else
                                                        Xe {{ $i }}
                                                    @endif
                                                </label>
                                            </section>
                                            <div style="display:none" id="car{{ $i }}">
                                                <div class="form-group">
                                                    <label for="exampleInputPassword1">Tài xế</label>
                                                    <select name="car_driver_name[]" id="select_driver_{{ $i }}"
                                                        class="input-control">
                                                        <option selected value="">Chọn TX</option>
                                                        @foreach ($getAllStaff as $key => $staff)
                                                            @if ($staff->staff_role == 'TX')
                                                                <option
                                                                    value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                                    {{ $staff->staff_name }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputPassword1">KTV 1</label>
                                                    <select name="car_ktv_name_1[]" id="select_car_{{ $i }}"
                                                        class="input-control">
                                                        <option selected value="">Chọn KTV</option>
                                                        @foreach ($getAllStaff as $key => $staff)
                                                            @if ($staff->staff_role == 'KTV')
                                                                <option
                                                                    value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                                    {{ $staff->staff_name }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputPassword1">KTV 2</label>
                                                    <select name="car_ktv_name_2[]" id="select_car_{{ $i + 7 }}"
                                                        class="input-control">
                                                        <option selected value="">Chọn KTV</option>
                                                        @foreach ($getAllStaff as $key => $staff)
                                                            @if ($staff->staff_role == 'KTV')
                                                                <option
                                                                    value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                                    {{ $staff->staff_name }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div> --}}
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
