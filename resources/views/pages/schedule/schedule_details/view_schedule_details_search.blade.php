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
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($order as $key => $ord)
                        @if ($ord->car_name == 1 && $ord->car_active == 1 && $ord->order_status != 0 && $ord->order_surcharge == 0)
                            <li class="single-event border-child"
                                data-start="{{ Carbon\Carbon::parse($ord->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($ord->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-1"
                                data-child="{{ $ord->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($ord->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $ord->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $ord->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none" class="event-warning">{{ $ord->order_warning }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none"
                                        class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-note">{{ $ord->customer_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none"
                                        class="event-doctor-read">{{ $ord->ord_doctor_read }}</em>
                                    <em style="display:none" class="event-film">{{ $ord->ord_film }}</em>
                                    <em style="display:none" class="event-form">{{ $ord->ord_form }}</em>
                                    <em style="display:none" class="event-print">{{ $ord->ord_print }}</em>
                                    <em style="display:none"
                                        class="event-form-print">{{ $ord->ord_form_print }}</em>
                                    <em style="display:none"
                                        class="event-print-result">{{ $ord->ord_print_result }}</em>
                                    <em style="display:none"
                                        class="event-film-sheet">{{ $ord->ord_film_sheet }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-deadline">{{ $ord->ord_deadline }}</em>
                                    <em style="display:none"
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}</em>
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none"
                                        class="event-35X43">{{ $ord->accountant_35X43 }}</em>
                                    <em style="display:none"
                                        class="event-polime">{{ $ord->accountant_polime }}</em>
                                    <em style="display:none" class="event-8X10">{{ $ord->accountant_8X10 }}</em>
                                    <em style="display:none"
                                        class="event-10X12">{{ $ord->accountant_10X12 }}</em>
                                    <em style="display:none"
                                        class="event-film-bag">{{ $ord->accountant_film_bag }}</em>
                                    <em style="display:none"
                                        class="event-accountant-note">{{ $ord->accountant_note }}</em>
                                    <em style="display:none"
                                        class="event-delivery-date">{{ $ord->ord_delivery_date }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 2</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($order as $key => $ord)
                        @if ($ord->car_name == 2 && $ord->car_active == 1 && $ord->order_status != 0 && $ord->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($ord->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($ord->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-2"
                                data-child="{{ $ord->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($ord->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $ord->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $ord->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none" class="event-warning">{{ $ord->order_warning }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none" class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none"
                                        class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-note">{{ $ord->customer_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none" class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none"
                                        class="event-doctor-read">{{ $ord->ord_doctor_read }}</em>
                                    <em style="display:none" class="event-film">{{ $ord->ord_film }}</em>
                                    <em style="display:none" class="event-form">{{ $ord->ord_form }}</em>
                                    <em style="display:none" class="event-print">{{ $ord->ord_print }}</em>
                                    <em style="display:none"
                                        class="event-form-print">{{ $ord->ord_form_print }}</em>
                                    <em style="display:none"
                                        class="event-print-result">{{ $ord->ord_print_result }}</em>
                                    <em style="display:none"
                                        class="event-film-sheet">{{ $ord->ord_film_sheet }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none" class="event-deadline">{{ $ord->ord_deadline }}</em>
                                    <em style="display:none"
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}</em>
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none"
                                        class="event-35X43">{{ $ord->accountant_35X43 }}</em>
                                    <em style="display:none"
                                        class="event-polime">{{ $ord->accountant_polime }}</em>
                                    <em style="display:none" class="event-8X10">{{ $ord->accountant_8X10 }}</em>
                                    <em style="display:none"
                                        class="event-10X12">{{ $ord->accountant_10X12 }}</em>
                                    <em style="display:none"
                                        class="event-film-bag">{{ $ord->accountant_film_bag }}</em>
                                    <em style="display:none"
                                        class="event-accountant-note">{{ $ord->accountant_note }}</em>
                                    <em style="display:none"
                                        class="event-delivery-date">{{ $ord->ord_delivery_date }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 3</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($order as $key => $ord)
                        @if ($ord->car_name == 3 && $ord->car_active == 1 && $ord->order_status != 0 && $ord->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($ord->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($ord->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-3"
                                data-child="{{ $ord->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($ord->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $ord->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $ord->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none" class="event-warning">{{ $ord->order_warning }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none"
                                        class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none"
                                        class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-note">{{ $ord->customer_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none"
                                        class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none"
                                        class="event-doctor-read">{{ $ord->ord_doctor_read }}</em>
                                    <em style="display:none" class="event-film">{{ $ord->ord_film }}</em>
                                    <em style="display:none" class="event-form">{{ $ord->ord_form }}</em>
                                    <em style="display:none" class="event-print">{{ $ord->ord_print }}</em>
                                    <em style="display:none"
                                        class="event-form-print">{{ $ord->ord_form_print }}</em>
                                    <em style="display:none"
                                        class="event-print-result">{{ $ord->ord_print_result }}</em>
                                    <em style="display:none"
                                        class="event-film-sheet">{{ $ord->ord_film_sheet }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none"
                                        class="event-deadline">{{ $ord->ord_deadline }}</em>
                                    <em style="display:none"
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}</em>
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none"
                                        class="event-35X43">{{ $ord->accountant_35X43 }}</em>
                                    <em style="display:none"
                                        class="event-polime">{{ $ord->accountant_polime }}</em>
                                    <em style="display:none" class="event-8X10">{{ $ord->accountant_8X10 }}</em>
                                    <em style="display:none"
                                        class="event-10X12">{{ $ord->accountant_10X12 }}</em>
                                    <em style="display:none"
                                        class="event-film-bag">{{ $ord->accountant_film_bag }}</em>
                                    <em style="display:none"
                                        class="event-accountant-note">{{ $ord->accountant_note }}</em>
                                    <em style="display:none"
                                        class="event-delivery-date">{{ $ord->ord_delivery_date }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 4</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($order as $key => $ord)
                        @if ($ord->car_name == 4 && $ord->car_active == 1 && $ord->order_status != 0 && $ord->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($ord->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($ord->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-4"
                                data-child="{{ $ord->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($ord->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $ord->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $ord->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none"
                                        class="event-warning">{{ $ord->order_warning }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none"
                                        class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none"
                                        class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-note">{{ $ord->customer_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none"
                                        class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none"
                                        class="event-doctor-read">{{ $ord->ord_doctor_read }}</em>
                                    <em style="display:none" class="event-film">{{ $ord->ord_film }}</em>
                                    <em style="display:none" class="event-form">{{ $ord->ord_form }}</em>
                                    <em style="display:none" class="event-print">{{ $ord->ord_print }}</em>
                                    <em style="display:none"
                                        class="event-form-print">{{ $ord->ord_form_print }}</em>
                                    <em style="display:none"
                                        class="event-print-result">{{ $ord->ord_print_result }}</em>
                                    <em style="display:none"
                                        class="event-film-sheet">{{ $ord->ord_film_sheet }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none"
                                        class="event-deadline">{{ $ord->ord_deadline }}</em>
                                    <em style="display:none"
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}</em>
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none"
                                        class="event-35X43">{{ $ord->accountant_35X43 }}</em>
                                    <em style="display:none"
                                        class="event-polime">{{ $ord->accountant_polime }}</em>
                                    <em style="display:none" class="event-8X10">{{ $ord->accountant_8X10 }}</em>
                                    <em style="display:none"
                                        class="event-10X12">{{ $ord->accountant_10X12 }}</em>
                                    <em style="display:none"
                                        class="event-film-bag">{{ $ord->accountant_film_bag }}</em>
                                    <em style="display:none"
                                        class="event-accountant-note">{{ $ord->accountant_note }}</em>
                                    <em style="display:none"
                                        class="event-delivery-date">{{ $ord->ord_delivery_date }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 5</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($order as $key => $ord)
                        @if ($ord->car_name == 5 && $ord->car_active == 1 && $ord->order_status != 0 && $ord->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($ord->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($ord->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $ord->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($ord->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $ord->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $ord->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none"
                                        class="event-warning">{{ $ord->order_warning }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none"
                                        class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none"
                                        class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-note">{{ $ord->customer_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none"
                                        class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none"
                                        class="event-doctor-read">{{ $ord->ord_doctor_read }}</em>
                                    <em style="display:none" class="event-film">{{ $ord->ord_film }}</em>
                                    <em style="display:none" class="event-form">{{ $ord->ord_form }}</em>
                                    <em style="display:none" class="event-print">{{ $ord->ord_print }}</em>
                                    <em style="display:none"
                                        class="event-form-print">{{ $ord->ord_form_print }}</em>
                                    <em style="display:none"
                                        class="event-print-result">{{ $ord->ord_print_result }}</em>
                                    <em style="display:none"
                                        class="event-film-sheet">{{ $ord->ord_film_sheet }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none"
                                        class="event-deadline">{{ $ord->ord_deadline }}</em>
                                    <em style="display:none"
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}</em>
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none"
                                        class="event-35X43">{{ $ord->accountant_35X43 }}</em>
                                    <em style="display:none"
                                        class="event-polime">{{ $ord->accountant_polime }}</em>
                                    <em style="display:none" class="event-8X10">{{ $ord->accountant_8X10 }}</em>
                                    <em style="display:none"
                                        class="event-10X12">{{ $ord->accountant_10X12 }}</em>
                                    <em style="display:none"
                                        class="event-film-bag">{{ $ord->accountant_film_bag }}</em>
                                    <em style="display:none"
                                        class="event-accountant-note">{{ $ord->accountant_note }}</em>
                                    <em style="display:none"
                                        class="event-delivery-date">{{ $ord->ord_delivery_date }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe Thuê</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($order as $key => $ord)
                        @if ($ord->car_name == 6 && $ord->car_active == 1 && $ord->order_status != 0 && $ord->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($ord->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($ord->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $ord->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($ord->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $ord->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $ord->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none"
                                        class="event-warning">{{ $ord->order_warning }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none"
                                        class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none"
                                        class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-note">{{ $ord->customer_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none"
                                        class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none"
                                        class="event-doctor-read">{{ $ord->ord_doctor_read }}</em>
                                    <em style="display:none" class="event-film">{{ $ord->ord_film }}</em>
                                    <em style="display:none" class="event-form">{{ $ord->ord_form }}</em>
                                    <em style="display:none" class="event-print">{{ $ord->ord_print }}</em>
                                    <em style="display:none"
                                        class="event-form-print">{{ $ord->ord_form_print }}</em>
                                    <em style="display:none"
                                        class="event-print-result">{{ $ord->ord_print_result }}</em>
                                    <em style="display:none"
                                        class="event-film-sheet">{{ $ord->ord_film_sheet }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none"
                                        class="event-deadline">{{ $ord->ord_deadline }}</em>
                                    <em style="display:none"
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}</em>
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none"
                                        class="event-35X43">{{ $ord->accountant_35X43 }}</em>
                                    <em style="display:none"
                                        class="event-polime">{{ $ord->accountant_polime }}</em>
                                    <em style="display:none" class="event-8X10">{{ $ord->accountant_8X10 }}</em>
                                    <em style="display:none"
                                        class="event-10X12">{{ $ord->accountant_10X12 }}</em>
                                    <em style="display:none"
                                        class="event-film-bag">{{ $ord->accountant_film_bag }}</em>
                                    <em style="display:none"
                                        class="event-accountant-note">{{ $ord->accountant_note }}</em>
                                    <em style="display:none"
                                        class="event-delivery-date">{{ $ord->ord_delivery_date }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info child-7"><span>Xe Tăng Cường</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($order as $key => $ord)
                        @if ($ord->car_name == 7 && $ord->car_active == 1 && $ord->order_status != 0 && $ord->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($ord->ord_start_day)->format('d/m/Y') }}"
                                data-end="{{ Carbon\Carbon::parse($ord->ord_end_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $ord->order_child }}">
                                @if ($ord->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($ord->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $ord->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $ord->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none"
                                        class="event-warning">{{ $ord->order_warning }}</em>
                                    <em style="display:none" class="event-id">{{ $ord->order_id }}</em>
                                    <em style="display:none" class="event-car-id">{{ $ord->car_ktv_id }}</em>
                                    <em style="display:none" class="event-unit">{{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-select">{{ $ord->ord_select }}</em>
                                    <em style="display:none"
                                        class="event-cty-name">{{ $ord->ord_cty_name }}</em>
                                    <em style="display:none"
                                        class="event-address">{{ $ord->customer_address }}</em>
                                    <em style="display:none" class="event-note">{{ $ord->customer_note }}</em>
                                    <em style="display:none" class="event-list-file">{{ $ord->ord_list_file }}
                                        <p href="https://drive.google.com/file/d/{{ $ord->ord_list_file_path }}/view"
                                            target="_blank" class="dowload_file"></p>
                                    </em>
                                    <em style="display:none"
                                        class="event-info-contact">{{ $ord->customer_name }}
                                        ({{ $ord->customer_phone }})</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }} giờ</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none"
                                        class="event-doctor-read">{{ $ord->ord_doctor_read }}</em>
                                    <em style="display:none" class="event-film">{{ $ord->ord_film }}</em>
                                    <em style="display:none" class="event-form">{{ $ord->ord_form }}</em>
                                    <em style="display:none" class="event-print">{{ $ord->ord_print }}</em>
                                    <em style="display:none"
                                        class="event-form-print">{{ $ord->ord_form_print }}</em>
                                    <em style="display:none"
                                        class="event-print-result">{{ $ord->ord_print_result }}</em>
                                    <em style="display:none"
                                        class="event-film-sheet">{{ $ord->ord_film_sheet }}</em>
                                    <em style="display:none" class="event-order-note">{{ $ord->ord_note }}</em>
                                    <em style="display:none"
                                        class="event-deadline">{{ $ord->ord_deadline }}</em>
                                    <em style="display:none"
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}</em>
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none"
                                        class="event-35X43">{{ $ord->accountant_35X43 }}</em>
                                    <em style="display:none"
                                        class="event-polime">{{ $ord->accountant_polime }}</em>
                                    <em style="display:none" class="event-8X10">{{ $ord->accountant_8X10 }}</em>
                                    <em style="display:none"
                                        class="event-10X12">{{ $ord->accountant_10X12 }}</em>
                                    <em style="display:none"
                                        class="event-film-bag">{{ $ord->accountant_film_bag }}</em>
                                    <em style="display:none"
                                        class="event-accountant-note">{{ $ord->accountant_note }}</em>
                                    <em style="display:none"
                                        class="event-delivery-date">{{ $ord->ord_delivery_date }}</em>
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
                        class="event-cty-name"></span></p>
                <p class="event-item"><span class="item-title">Địa chỉ: </span><span
                        class="event-address"></span></p>
                <p class="event-item event-note"><span class="item-title">Địa chỉ khác: </span><span
                        class="event-note-content"></span></p>
                <p class="event-item"><span class="item-title">Bộ phận chụp: </span><span
                        class="event-select"></span></p>
                <p class="event-item"><span class="item-title">Danh sách: </span><span
                        class="event-list-file"></span></p>
                <p class="event-item"><span class="item-title">Thông tin người liên hệ: </span><span
                        class="event-info-contact"></span></p>
                <p class="event-item"><span class="item-title">Giờ chụp: </span><span class="event-time"></span>
                </p>
                <p class="event-item"><span class="item-title">Bác sĩ đọc phim: </span><span
                        class="event-doctor-read"></span></p>
                <p class="event-item"><span class="item-title">In phim: </span><span class="event-film"></span>
                </p>
                <p class="event-item"><span class="item-title">Hình thức in phim: </span><span
                        class="event-form"></span></p>
                <p class="event-item"><span class="item-title">In phiếu: </span><span class="event-print"></span>
                </p>
                <p class="event-item"><span class="item-title">Hình thức in phiếu: </span><span
                        class="event-form-print"></span></p>
                <p class="event-item"><span class="item-title">In phiếu kết quả theo mẫu đơn vị: </span><span
                        class="event-print-result"></span></p>
                <p class="event-item"><span class="item-title">Phim & Phiếu: </span><span
                        class="event-film-sheet"></span></p>
                <p class="event-item"><span class="item-title">Ghi chú: </span><span
                        class="event-order-note"></span></p>
                <p class="event-item"><span class="item-title">Cảnh báo đơn hàng: </span><span
                        class="event-ord-warning"></span></p>
                <p class="event-item"><span class="item-title">Thời hạn giao kết quả: </span><span
                        class="event-deadline"></span></p>
                <p class="event-item"><span class="item-title">Địa chỉ & sđt giao kết quả: </span><span
                        class="event-deliver-results"></span></p>
                <p class="event-item"><span class="item-title">Địa chỉ email khách hàng: </span><span
                            class="event-email"></span></p>
                <p class="item_ktv"></p>
                <p class="event-item"><span class="item-title">Trạng thái đơn: </span><span
                        class="event-status"></span></p>
                <p class="event-item"><span class="item-title">Số Cas KTV chụp: </span><span
                        class="event-draft"></span></p>
                <form onsubmit="required()" method="post">
                    @csrf
                    <div>
                        <p class="event-item"><span class="item-title">Bác sĩ đọc: </span>
                            <span class="event-accountant-doctor-read"></span>
                            <select name="accountant_doctor_read" class="input-control accountant-doctor-read">
                                <option class="doctor-empty" value="Không">Không</option>
                                <option class="doctor-N" value="Nhân">Võ Nguyễn Thành Nhân</option>
                                <option class="doctor-T" value="Trung">Hồ Chí Trung</option>
                                <option class="doctor-G" value="Giang">Nguyễn Thanh Giang</option>
                            </select>
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">35 X 43: </span>
                            <span class="event-35X43"></span>
                            <input type="text" name="accountant_35X43" class="accountant-35X43 input-control"
                                placeholder="">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Polime: </span>
                            <span class="event-polime"></span>
                            <input type="text" name="accountant_polime"
                                class="accountant-polime input-control" placeholder="">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">8 X 10: </span>
                            <span class="event-8X10"></span>
                            <input type="text" name="accountant_8X10" class="accountant-8X10 input-control"
                                placeholder="">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">10 X 12: </span>
                            <span class="event-10X12"></span>
                            <input type="text" name="accountant_10X12" class="accountant-10X12 input-control"
                                placeholder="">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Ghi chú: </span>
                            <span class="event-accountant-note"></span>
                            <input type="text" name="accountant_note" class="accountant-note input-control"
                                placeholder="Điền ghi chú">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Ngày trả kết quả: </span>
                            <span class="event-delivery-date"></span>
                            <input type="text" name="ord_delivery_date"
                                class="ord_delivery_date input-control" placeholder="Điền ngày trả kết quả">
                        </p>
                    </div>
                    <p class="item_ktv"></p>
                    <div class="row">
                        <div class="col-lg-6 col-md-6">
                            <p class="event-item event-quantity-item"><span class="item-title">Số Cas: </span>
                                <span class="event-quantity-details"></span>
                                <input type="text" name="order_quantity"
                                    class="order_quantity_details input-control" placeholder="Điền số cas"
                                    value="{{ old('order_quantity') }}">
                            </p>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <button type="button" class="send_quantity_details primary-btn-submit">Cập
                                nhật</button>
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