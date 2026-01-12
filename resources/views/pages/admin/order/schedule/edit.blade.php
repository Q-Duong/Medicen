@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Cập Nhật Lịch KTV Và Tài Xế
                    <span class="tools pull-right">
                        <a href="{{ route('order.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>

                <div class="panel-body">
                    <form role="form" action="{{ route('schedule.update', $order_id) }}" method="post">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="order_id" value="{{ $order_id }}">
                        @if (Auth::user()->role == 0)
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group">
                                        <label>Chọn Xe</label>
                                        <select name="car_name" class="input-control">
                                            @for ($i = 1; $i < 9; $i++)
                                                @if ($i == 6)
                                                    <option value="{{ $i }}"
                                                        {{ $car->car_name == $i ? 'selected' : '' }}>Xe Thuê</option>
                                                @elseif ($i == 7)
                                                    <option value="{{ $i }}"
                                                        {{ $car->car_name == $i ? 'selected' : '' }}>Xe Tăng Cường
                                                    </option>
                                                @elseif ($i == 8)
                                                    <option value="{{ $i }}"
                                                        {{ $car->car_name == $i ? 'selected' : '' }}>Xe Siêu Âm</option>
                                                @else
                                                    <option value="{{ $i }}"
                                                        {{ $car->car_name == $i ? 'selected' : '' }}>Xe
                                                        {{ $i }}</option>
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
                                                    <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                        {{ $car->car_driver_name == $staff->staff_name ? 'selected' : '' }}>
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
                                            <option selected value=""
                                                {{ $car->car_ktv_name_1 == null ? 'selected' : '' }}>Trống</option>
                                            @foreach ($getAllStaff as $key => $staff)
                                                @if ($staff->staff_role == 'KTV')
                                                    <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                        {{ $car->car_ktv_name_1 == $staff->staff_name ? 'selected' : '' }}>
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
                                            <option selected value=""
                                                {{ $car->car_ktv_name_2 == $staff->staff_name ? 'selected' : '' }}>
                                                Trống</option>
                                            @foreach ($getAllStaff as $key => $staff)
                                                @if ($staff->staff_role == 'KTV')
                                                    <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                        {{ $car->car_ktv_name_2 == $staff->staff_name ? 'selected' : '' }}>
                                                        {{ $staff->staff_name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="block-btn-schedule">
                                    <div class="col-lg-3">
                                        <button value="true" name="zalo" class="primary-btn-submit button-submit">
                                            Cập Nhật Lịch (Gửi Zalo)
                                        </button>
                                    </div>
                                    <div class="col-lg-3">
                                        <button value="true" name="notZalo" class="primary-btn-submit button-submit">
                                            Cập Nhật Lịch (Không gửi Zalo)
                                        </button>
                                    </div>
                                    @if ($car->car_active == 1)
                                        <div class="col-lg-3">
                                            <button type="button" class="primary-btn-submit button-submit"
                                                onclick="scheduleCancel(event)">
                                                Huỷ Lịch
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            @if (Carbon\Carbon::now() < $order->ord_start_day)
                                <div class="row">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn Xe</label>
                                            <select name="car_name" class="input-control"
                                                {{ $car->car_active ? 'disabled' : '' }}>
                                                @for ($i = 1; $i < 9; $i++)
                                                    @if ($i == 6)
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe Thuê</option>
                                                    @elseif ($i == 7)
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe Tăng Cường
                                                        </option>
                                                    @elseif ($i == 8)
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe Siêu Âm
                                                        </option>
                                                    @else
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe
                                                            {{ $i }}</option>
                                                    @endif
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn Tài xế</label>
                                            <select name="car_driver_name" class="input-control"
                                                {{ $car->car_active ? 'disabled' : '' }}>
                                                @foreach ($getAllStaff as $key => $staff)
                                                    @if ($staff->staff_role == 'TX')
                                                        <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                            {{ $car->car_driver_name == $staff->staff_name ? 'selected' : '' }}>
                                                            {{ $staff->staff_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn KTV 1</label>
                                            <select name="car_ktv_name_1" class="input-control"
                                                {{ $car->car_active ? 'disabled' : '' }}>
                                                <option selected value=""
                                                    {{ $car->car_ktv_name_1 == null ? 'selected' : '' }}>Trống</option>
                                                @foreach ($getAllStaff as $key => $staff)
                                                    @if ($staff->staff_role == 'KTV')
                                                        <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                            {{ $car->car_ktv_name_1 == $staff->staff_name ? 'selected' : '' }}>
                                                            {{ $staff->staff_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn KTV 2</label>
                                            <select name="car_ktv_name_2" class="input-control"
                                                {{ $car->car_active ? 'disabled' : '' }}>
                                                <option selected value=""
                                                    {{ $car->car_ktv_name_2 == $staff->staff_name ? 'selected' : '' }}>
                                                    Trống</option>
                                                @foreach ($getAllStaff as $key => $staff)
                                                    @if ($staff->staff_role == 'KTV')
                                                        <option value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                            {{ $car->car_ktv_name_2 == $staff->staff_name ? 'selected' : '' }}>
                                                            {{ $staff->staff_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    @if (!$car->car_active)
                                        <div class="col-lg-3 col-md-4">
                                            <button value="true" name="zalo" class="primary-btn-submit button-submit">
                                                Cập Nhật Lịch (Gửi Zalo)
                                            </button>
                                        </div>
                                        <div class="col-lg-3 col-md-4">
                                            <button value="true" name="notZalo" class="primary-btn-submit button-submit">
                                                Cập Nhật Lịch (Không gửi Zalo)
                                            </button>
                                        </div>
                                    @endif
                                    @if ($car->car_active)
                                        <div class="col-lg-3 col-md-4">
                                            <button type="button" class="primary-btn-submit button-submit"
                                                onclick="scheduleCancel(event)">
                                                Huỷ Lịch
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="row">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn Xe</label>
                                            <select name="car_name" class="input-control" disabled>
                                                @for ($i = 1; $i < 9; $i++)
                                                    @if ($i == 6)
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe Thuê</option>
                                                    @elseif ($i == 7)
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe Tăng Cường
                                                        </option>
                                                    @elseif ($i == 8)
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe Siêu Âm
                                                        </option>
                                                    @else
                                                        <option value="{{ $i }}"
                                                            {{ $car->car_name == $i ? 'selected' : '' }}>Xe
                                                            {{ $i }}</option>
                                                    @endif
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn Tài xế</label>
                                            <select name="car_driver_name" class="input-control" disabled>
                                                @foreach ($getAllStaff as $key => $staff)
                                                    @if ($staff->staff_role == 'TX')
                                                        <option
                                                            value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                            {{ $car->car_driver_name == $staff->staff_name ? 'selected' : '' }}>
                                                            {{ $staff->staff_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn KTV 1</label>
                                            <select name="car_ktv_name_1" class="input-control" disabled>
                                                <option selected value=""
                                                    {{ $car->car_ktv_name_1 == null ? 'selected' : '' }}>Trống</option>
                                                @foreach ($getAllStaff as $key => $staff)
                                                    @if ($staff->staff_role == 'KTV')
                                                        <option
                                                            value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                            {{ $car->car_ktv_name_1 == $staff->staff_name ? 'selected' : '' }}>
                                                            {{ $staff->staff_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>Chọn KTV 2</label>
                                            <select name="car_ktv_name_2" class="input-control" disabled>
                                                <option selected value=""
                                                    {{ $car->car_ktv_name_2 == $staff->staff_name ? 'selected' : '' }}>
                                                    Trống</option>
                                                @foreach ($getAllStaff as $key => $staff)
                                                    @if ($staff->staff_role == 'KTV')
                                                        <option
                                                            value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}"
                                                            {{ $car->car_ktv_name_2 == $staff->staff_name ? 'selected' : '' }}>
                                                            {{ $staff->staff_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('js')
    <script type="text/javascript">
        var url_schedule_cancel = "{{ route('schedule.cancel') }}";
    </script>
    <script src="{{ versionResource('assets/js/tool/schedule/admin/schedule.js') }}" defer></script>
@endpush
