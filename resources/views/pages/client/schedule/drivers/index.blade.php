@extends('layouts.default')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/schedule.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/org-form.built.css') }}" type="text/css"
        as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/file.css') }}" type="text/css" as="style" />
@endpush
@section('content')
@section('title', 'Lịch xe chi tiết tài xế - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>LỊCH XE CHI TIẾT TÀI XẾ</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home.index') }}">Trang chủ</a>
                        <span>Lịch xe chi tiết tài xế</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="schedule-render">
    <header class="cd-intro">
        <div class="container">
            <div class="schedule-support">
                <div class="row justify-content-center">
                    <div class="col-lg-3 col-6">
                        <div class="form-dropdown">
                            <select class="form-dropdown-select select-year">
                                @for ($i = 0; $i <= 10; $i++)
                                    <option {{ $currentYear == $i + 2023 ? 'selected' : '' }}
                                        value="{{ $i + 2023 }}">
                                        {{ $i + 2023 }}</option>
                                @endfor
                            </select>
                            <span class="form-dropdown-chevron" aria-hidden="true"><i
                                    class="fa-solid fa-angle-down"></i></span>
                            <span class="form-dropdown-label" aria-hidden="true">Năm</span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="form-dropdown">
                            <select class="form-dropdown-select select-month">
                                <option disabled class="define-month">Chọn tháng</option>
                                @foreach ($months as $key => $month)
                                    <option {{ $months[$key] == $currentMonth ? 'selected' : '' }}
                                        value="{{ $months[$key] }}">
                                        {{ $key + 1 }}</option>
                                @endforeach
                            </select>
                            <span class="form-dropdown-chevron" aria-hidden="true"><i
                                    class="fa-solid fa-angle-down"></i></span>
                            <span class="form-dropdown-label" aria-hidden="true">Tháng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="cd-schedule loading">
        <div class="timeline">
            <ul>
                @for ($i = 0; $i < $dayInMonth; $i++)
                    <li>
                        <div class="timeline-day"><span>{{ $i + 1 }}</span></div>
                    </li>
                @endfor
            </ul>
        </div>
        <div class="events">
            <ul class="wrap">
                <li class="events-group">
                    <div class="top-info child-1"><span>Xe 1</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 1 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event border-child"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-1"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 2</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 2 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-2"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 3</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 3 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-3"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 4</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 4 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-4"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 5</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 5 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-5"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
                <li class="events-group">
                    <div class="top-info"><span>Xe Thuê</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 6 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-5"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
                <li class="events-group">
                    <div class="top-info child-7"><span>Xe Tăng Cường</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 7 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-5"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info child-8"><span>Xe Siêu Âm</span></div>
                    <ul>
                        @foreach ($orders as $key => $order)
                            @if ($order->car_name == 8 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <li class="single-event"
                                    data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-5"
                                    data-child="{{ $order->order_child }}">
                                    @if ($order->order_updated == 1)
                                        <div class="order-status">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    @endif
                                    <a href="javascript:;">
                                        <em class="event-name-id"><span class="item-title">Mã Đơn:
                                                {{ $order->order_id }}</em>
                                        <em class="event-name"><span class="item-title">KTV:
                                                {{ $name1 }},
                                                {{ $name2 }}</em>
                                        <em class="event-name-unit"><span class="item-title">Đơn vị:
                                                {{ $order->unit_abbreviation }}</em>
                                        <em class="event-id hidden">{{ $order->order_id }}</em>
                                        <em class="event-car-id hidden">{{ $order->id }}</em>
                                        <em class="event-unit hidden">{{ $order->unit_abbreviation }}</em>
                                        <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                        <em class="event-time hidden">{{ $order->ord_time }} giờ</em>
                                        <em class="event-address hidden">{{ $order->customer_address }}</em>
                                        <em class="event-note hidden">{{ $order->customer_note }}</em>
                                        <em class="event-info-contact hidden">{{ $order->customer_name }}
                                            ({{ $order->customer_phone }})</em>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            </ul>
        </div>
        <div class="event-modal">
            <header class="header">
                <div class="content">
                    <span class="event-date"></span>
                    <h3 class="event-name-id"></h3>
                    <h3 class="event-name"></h3>
                    <h3 class="event-name-unit"></h3>
                </div>

                <div class="header-bg"></div>
            </header>

            <div class="body">
                <div class="event-info">
                    <p class="event-item"><span class="item-title">Mã đơn hàng: </span><span class="event-id"></span>
                    </p>
                    <p class="hidden event-car-id"></p>
                    <p class="event-item"><span class="item-title">Đơn vị: </span><span class="event-unit"></span>
                    </p>
                    <p class="event-item"><span class="item-title">Tên Cty: </span><span
                            class="event-cty-name"></span>
                    </p>
                    <p class="event-item"><span class="item-title">Địa chỉ: </span><span
                            class="event-address"></span>
                    </p>
                    <p class="event-item event-note"><span class="item-title">Địa chỉ khác: </span><span
                            class="event-note-content"></span></p>
                    <p class="event-item"><span class="item-title">Giờ chụp: </span><span class="event-time"></span>
                    </p>
                    <p class="event-item"><span class="item-title">Thông tin người liên hệ: </span><span
                            class="event-info-contact"></span></p>

                </div>
                <div class="body-bg"></div>
            </div>
            <a href="javascript:;" class="close"></a>
        </div>
        <div class="cover-layer"></div>
    </div>
</div>
@endsection
@push('js')
<script type="text/javascript">
    var day = {{ $dayInMonth }};
    var url_select_technologist = "{{ route('schedules.drivers.select') }}";
</script>
<script src="{{ asset('assets/js/support/essential.js') }}"></script>
<script src="{{ asset('assets/js/tool/schedule/technologist/schedule.js') }}"></script>
@endpush
