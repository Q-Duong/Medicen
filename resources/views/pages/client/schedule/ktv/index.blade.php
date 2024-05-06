@extends('layouts.default')
@section('content')
@section('title', 'Lịch KTV Và Tài Xế - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>LỊCH KTV VÀ TÀI XẾ X QUANG</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ URL::to('/') }}">Trang chủ</a>
                        <span>Lịch KTV và Tài Xế X Quang</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<header class="cd-intro">
    <h3>Lịch KTV Và Tài Xế X Quang</h3>
    <div class="container">
        <div class="row justify-content-end">
            <div class="col-lg-3 col-6">
                <div class="cd-intro-month">
                    <label for="schedule-label">Năm</label>
                    <select id="schedule-label" class="form-control select-year">
                        @for ($i = 0; $i <= 10; $i++)
                            <option {{ $currentYear == $i + 2023 ? 'selected' : '' }} value="{{ $i + 2023 }}">
                                {{ $i + 2023 }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="cd-intro-month">
                    <label for="schedule-label">Tháng</label>
                    <select id="schedule-label" class="form-control select-month">
                        <option disabled class="define-month">Chọn tháng</option>
                        @foreach ($months as $key => $month)
                            <option {{ $months[$key] == $currentMonth ? 'selected' : '' }} value="{{ $months[$key] }}">
                                {{ $key + 1 }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</header>
<div class="cd-schedule loading">
    <div class="timeline">
        <ul>
            @for ($i = 0; $i < $dayInMonth; $i++)
                <li><span>{{ $i + 1 }}</span></li>
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
                                data-end="{{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}"
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
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em style="display:none" class="event-id">{{ $order->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $order->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $order->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $order->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $order->ord_cty_name }}</em>
                                    <em style="display:none" class="event-address">{{ $order->customer_address }}</em>
                                    <em style="display:none" class="event-order-note">{{ $order->ord_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $order->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $order->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-list-file">{{ $order->ord_list_file }}</em>
                                    <em style="display:none" class="event-info-contact">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $order->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $order->order_quantity }}
                                        Cas</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $order->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $order->order_note_ktv }}</em>
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
                        @if ($order->car_name == 2 && $order->car_active == 1 && $order->order_status != 0 && $order->order_surcharge == 0)
                            @php
                                $str1 = explode(' ', $order->car_ktv_name_1);
                                $name1 = array_pop($str1);
                                $str2 = explode(' ', $order->car_ktv_name_2);
                                $name2 = array_pop($str2);
                            @endphp
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-2"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                <a href="javascript:;">
                                    <em class="event-name-id"><span class="item-title">Mã Đơn:
                                            {{ $ord->order_id }}</em>
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none" class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}
                                        Cas</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($order->car_name == 3 && $order->car_active == 1 && $order->order_status != 0 && $order->order_surcharge == 0)
                            @php
                                $str1 = explode(' ', $order->car_ktv_name_1);
                                $name1 = array_pop($str1);
                                $str2 = explode(' ', $order->car_ktv_name_2);
                                $name2 = array_pop($str2);
                            @endphp
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-3"
                                data-child="{{ $order->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                <a href="javascript:;">
                                    <em class="event-name-id"><span class="item-title">Mã Đơn:
                                            {{ $ord->order_id }}</em>
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none" class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}
                                        Cas</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($order->car_name == 4 && $order->car_active == 1 && $order->order_status != 0 && $order->order_surcharge == 0)
                            @php
                                $str1 = explode(' ', $order->car_ktv_name_1);
                                $name1 = array_pop($str1);
                                $str2 = explode(' ', $order->car_ktv_name_2);
                                $name2 = array_pop($str2);
                            @endphp
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-4"
                                data-child="{{ $order->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                <a href="javascript:;">
                                    <em class="event-name-id"><span class="item-title">Mã Đơn:
                                            {{ $ord->order_id }}</em>
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none" class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}
                                        Cas</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($order->car_name == 5 && $order->car_active == 1 && $order->order_status != 0 && $order->order_surcharge == 0)
                            @php
                                $str1 = explode(' ', $order->car_ktv_name_1);
                                $name1 = array_pop($str1);
                                $str2 = explode(' ', $order->car_ktv_name_2);
                                $name2 = array_pop($str2);
                            @endphp
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $order->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                <a href="javascript:;">
                                    <em class="event-name-id"><span class="item-title"> Mã Đơn:
                                            {{ $ord->order_id }}</em>
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none" class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}
                                        Cas</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($order->car_name == 6 && $order->car_active == 1 && $order->order_status != 0 && $order->order_surcharge == 0)
                            @php
                                $str1 = explode(' ', $order->car_ktv_name_1);
                                $name1 = array_pop($str1);
                                $str2 = explode(' ', $order->car_ktv_name_2);
                                $name2 = array_pop($str2);
                            @endphp
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $order->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                <a href="javascript:;">
                                    <em class="event-name-id"><span class="item-title"> Mã Đơn:
                                            {{ $ord->order_id }}</em>
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none" class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}
                                        Cas</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($order->car_name == 7 && $order->car_active == 1 && $order->order_status != 0 && $order->order_surcharge == 0)
                            @php
                                $str1 = explode(' ', $order->car_ktv_name_1);
                                $name1 = array_pop($str1);
                                $str2 = explode(' ', $order->car_ktv_name_2);
                                $name2 = array_pop($str2);
                            @endphp
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($order->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $order->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                <a href="javascript:;">
                                    <em class="event-name-id"><span class="item-title"> Mã Đơn:
                                            {{ $ord->order_id }}</em>
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none" class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}
                                        Cas</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                <p class="event-item"><span class="item-title">Mã đơn hàng: </span><span class="event-id"></span></p>
                <p class="hidden event-car-id"></p>
                <p class="event-item"><span class="item-title">Đơn vị: </span><span class="event-unit"></span></p>
                <p class="event-item"><span class="item-title">Tên Cty: </span><span class="event-cty-name"></span>
                </p>
                <p class="event-item"><span class="item-title">Địa chỉ: </span><span class="event-address"></span>
                </p>
                <p class="event-item event-note"><span class="item-title">Địa chỉ khác: </span><span
                        class="event-note-content"></span></p>
                <p class="event-item"><span class="item-title">Bộ phận chụp: </span><span
                        class="event-select"></span></p>
                <p class="event-item"><span class="item-title">Danh sách: </span><span
                        class="event-list-file"></span></p>
                <p class="event-item"><span class="item-title">Thông tin người liên hệ: </span><span
                        class="event-info-contact"></span></p>
                <p class="event-item"><span class="item-title">Giờ chụp: </span><span class="event-time"></span></p>
                <p class="event-item"><span class="item-title">Ghi chú: </span><span class="event-order-note"></span>
                </p>
                <p class="event-item"><span class="item-title">Số Cas: </span><span class="event-quantity"></span>
                </p>
                <p class="item_ktv"></p>
                <form>
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <p class="event-item event-quantity-draft"><span class="item-title">Số Cas KTV
                                    chụp:</span>
                                <span class="event-draft"></span>
                                <input type="text" name="order_quantity_draft"
                                    class="order_quantity_draft input-control" placeholder="Số cas thực tế"
                                    value="{{ old('order_quantity_draft') }}">
                            </p>
                        </div>
                        <div class="col-lg-8 col-md-6">
                            <p class="event-item event-note-ktv"><span class="item-title">Ghi chú KTV:</span>
                                <span class="event-noteKtv"></span>
                                <input type="text" name="order_note_ktv" class="order_note_ktv input-control"
                                    placeholder="Điền ghi chú" value="{{ old('order_note_note') }}">
                            </p>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <button type="button" class="send_quantity_draft primary-btn-submit">Cập nhật</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="body-bg"></div>
        </div>
        <a href="javascript:;" class="close"></a>
    </div>
    <div class="cover-layer"></div>
</div>
@endsection
@push('js')
<script type="text/javascript">
    var day = {{ $dayInMonth }};
    var _token = "{{ csrf_token() }}";
    var url_select_month_ktv = "{{ route('schedule.show_ktv') }}";
    var url_update_quantity_ktv = "{{ route('schedule.update_quantity_ktv', ':id') }}";
</script>
<script src="{{ asset('assets/js/schedule/org-schedule.js') }}" defer></script>
@endpush
