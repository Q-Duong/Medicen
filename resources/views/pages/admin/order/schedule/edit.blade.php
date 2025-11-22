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
                                            <select name="car_name" class="input-control" {{$car->car_active ? 'disabled' : ''}}>
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
                                            <select name="car_driver_name" class="input-control" {{$car->car_active ? 'disabled' : ''}}>
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
                                            <select name="car_ktv_name_1" class="input-control" {{$car->car_active ? 'disabled' : ''}}>
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
                                            <select name="car_ktv_name_2" class="input-control" {{$car->car_active ? 'disabled' : ''}}>
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

                    {{-- <form role="form" action="{{ route('schedule.update', $order_id) }}" method="post"
                            id="myForm">
                            @csrf
                            @method('patch')
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
                            </div>
                            <div class="form-group" id="table_field">
                                <div class="row">
                                    @foreach ($cars as $key => $carktv)
                                        <div class="col-lg-6 col-md-6">
                                            <section>
                                                @if (Auth::user()->role == 0 && $carktv->car_active == 1)
                                                    <button type="button" id="{{ $carktv->car_name }}"
                                                        name="{{ $key + 1 }}" class="primary-btn-schedule"
                                                        onclick="scheduleCancel(event)">
                                                        Huỷ Lịch
                                                    </button>
                                                @else
                                                    @if ($carktv->car_active == 1 && Carbon\Carbon::now() < $order->ord_start_day)
                                                        <button type="button" id="{{ $carktv->car_name }}"
                                                            name="{{ $key + 1 }}" class="primary-btn-schedule"
                                                            onclick="scheduleCancel(event)">
                                                            Huỷ Lịch
                                                        </button>
                                                    @endif
                                                @endif
                                                <input type="checkbox" id="checkCar{{ $key + 1 }}"
                                                    onclick="handleSchedule({{ $key + 1 }})"
                                                    {{ $carktv->car_active == 1 ? 'checked' : '' }}>
                                                <input type="hidden" name="car_name[]" value="{{ $carktv->car_name }}">
                                                <input type="hidden" name="car_active[]"
                                                    value="{{ $carktv->car_active }}" id="carActive{{ $key + 1 }}">
                                                <label for="checkCar{{ $key + 1 }}" class="accent-l">
                                                    @if ($key == 5)
                                                        Xe Thuê
                                                    @elseif($key == 6)
                                                        Xe Tăng Cường
                                                    @else
                                                        Xe {{ $key + 1 }}
                                                    @endif
                                                </label>
                                            </section>
                                            <div id="car{{ $key + 1 }}"
                                                {{ $carktv->car_active == 1 ? '' : 'style=display:none' }}>
                                                <div class="form-group">
                                                    <label for="exampleInputPassword1">Tài xế</label>
                                                    <select name="car_driver_name[]"
                                                        id="select_driver_{{ $key + 1 }}" class="input-control">
                                                        <option {{ $carktv->car_driver_name == 'null' ? 'selected' : '' }}
                                                            value="">Chọn KTV</option>
                                                        @foreach ($getAllStaff as $staff)
                                                            @if ($staff->staff_role == 'TX')
                                                                <option
                                                                    {{ $carktv->car_driver_name == $staff->staff_name ? 'selected' : '' }}
                                                                    value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                                    {{ $staff->staff_name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputPassword1">KTV 1</label>
                                                    <select name="car_ktv_name_1[]" id="select_car_{{ $key + 1 }}"
                                                        class="input-control">
                                                        <option {{ $carktv->car_ktv_name_1 == 'null' ? 'selected' : '' }}
                                                            value="">Chọn KTV</option>
                                                        @foreach ($getAllStaff as $staff)
                                                            @if ($staff->staff_role == 'KTV')
                                                                <option
                                                                    {{ $carktv->car_ktv_name_1 == $staff->staff_name ? 'selected' : '' }}
                                                                    value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                                    {{ $staff->staff_name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputPassword1">KTV 2</label>
                                                    <select name="car_ktv_name_2[]" id="select_car_{{ $key + 8 }}"
                                                        class="input-control">
                                                        <option {{ $carktv->car_ktv_name_2 == 'null' ? 'selected' : '' }}
                                                            value="">Chọn KTV</option>
                                                        @foreach ($getAllStaff as $staff)
                                                            @if ($staff->staff_role == 'KTV')
                                                                <option
                                                                    {{ $carktv->car_ktv_name_2 == $staff->staff_name ? 'selected' : '' }}
                                                                    value="{{ $staff->staff_name }}_{{ $staff->staff_phone }}">
                                                                    {{ $staff->staff_name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if (Auth::user()->role == 0)
                                <div class="row">
                                    <div class="block-btn-schedule">
                                        <div class="col-lg-4">
                                            <button value="true" name="zalo"
                                                class="primary-btn-submit button-submit">
                                                Cập Nhật Lịch (Gửi Zalo)
                                            </button>
                                        </div>
                                        <div class="col-lg-4">
                                            <button value="true" name="notZalo"
                                                class="primary-btn-submit button-submit">
                                                Cập Nhật Lịch (Không gửi Zalo)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                @if (!$is_active)
                                    <div class="row">
                                        <div class="block-btn-schedule">
                                            <div class="col-lg-4">
                                                <button value="true" name="zalo"
                                                    class="primary-btn-submit button-submit">
                                                    Cập Nhật Lịch (Gửi Zalo)
                                                </button>
                                            </div>
                                            <div class="col-lg-4">
                                                <button value="true" name="notZalo"
                                                    class="primary-btn-submit button-submit">
                                                    Cập Nhật Lịch (Không gửi Zalo)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </form> --}}
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
