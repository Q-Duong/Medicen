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
                        @if ($ord->car_name == 1 && $ord->car_active == 1 && $ord->order_status != 0)
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
                                    <em style="display:none" class="event-start-day">{{ $ord->ord_start_day }}</em>
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
                                    <em style="display:none"
                                        class="event-customer-name">{{ $ord->customer_name }}</em>
                                    <em style="display:none"
                                        class="event-customer-phone">{{ $ord->customer_phone }}</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }}</em>
                                    <em style="display:none" class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                                        class="event-deliver-results">{{ $ord->ord_deliver_results }}
                                    <em style="display:none" class="event-email">{{ $ord->ord_email }}</em>
                                    </em>
                                    <em style="display:none"
                                        class="event-accountant-doctor-read">{{ $ord->accountant_doctor_read }}</em>
                                    <em style="display:none" class="event-35X43">{{ $ord->accountant_35X43 }}</em>
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
                        @if ($ord->car_name == 2 && $ord->car_active == 1 && $ord->order_status != 0)
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
                                    <em style="display:none"
                                        class="event-start-day">{{ $ord->ord_start_day }}</em>
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
                                    <em style="display:none"
                                        class="event-customer-name">{{ $ord->customer_name }}</em>
                                    <em style="display:none"
                                        class="event-customer-phone">{{ $ord->customer_phone }}</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }}</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($ord->car_name == 3 && $ord->car_active == 1 && $ord->order_status != 0)
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
                                    <em style="display:none"
                                        class="event-start-day">{{ $ord->ord_start_day }}</em>
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
                                        class="event-customer-name">{{ $ord->customer_name }}</em>
                                    <em style="display:none"
                                        class="event-customer-phone">{{ $ord->customer_phone }}</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }}</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($ord->car_name == 4 && $ord->car_active == 1 && $ord->order_status != 0)
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
                                        class="event-start-day">{{ $ord->ord_start_day }}</em>
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
                                        class="event-customer-name">{{ $ord->customer_name }}</em>
                                    <em style="display:none"
                                        class="event-customer-phone">{{ $ord->customer_phone }}</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }}</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($ord->car_name == 5 && $ord->car_active == 1 && $ord->order_status != 0)
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
                                    <em class="event-name"><span class="item-title">Kỹ thuật viên:
                                            {{ $name1 }}, {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $ord->unit_name }}</em>
                                    <em style="display:none" class="event-status">{{ $ord->order_status }}</em>
                                    <em style="display:none"
                                        class="event-start-day">{{ $ord->ord_start_day }}</em>
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
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }}</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($ord->car_name == 6 && $ord->car_active == 1 && $ord->order_status != 0)
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
                                        class="event-start-day">{{ $ord->ord_start_day }}</em>
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
                                        class="event-customer-name">{{ $ord->customer_name }}</em>
                                    <em style="display:none"
                                        class="event-customer-phone">{{ $ord->customer_phone }}</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }}</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                        @if ($ord->car_name == 7 && $ord->car_active == 1 && $ord->order_status != 0)
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
                                        class="event-start-day">{{ $ord->ord_start_day }}</em>
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
                                        class="event-customer-name">{{ $ord->customer_name }}</em>
                                    <em style="display:none"
                                        class="event-customer-phone">{{ $ord->customer_phone }}</em>
                                    <em style="display:none" class="event-time">{{ $ord->ord_time }}</em>
                                    <em style="display:none"
                                        class="event-quantity">{{ $ord->order_quantity }}</em>
                                    <em style="display:none"
                                        class="event-quantity-draft">{{ $ord->order_quantity_draft }}</em>
                                    <em style="display:none" class="event-note-ktv">{{ $ord->order_note_ktv }}</em>
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
                <form class="form-order">
                    <div class="section-ord-unit">
                        <p class="event-item"><span class="item-title">Đơn vị: </span>
                            <span class="event-ord-unit"></span>
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Tên Cty: </span>
                            <span class="event-ord-cty-name"></span>
                            <input type="text" name="ord_cty_name" class="ord-cty-name input-control"
                                placeholder="Điền tên cty ">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Địa chỉ: </span>
                            <span class="event-customer-address"></span>
                            <input type="text" name="customer_address" class="customer-address input-control"
                                placeholder="Điền tên cty">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Địa chỉ khác: </span>
                            <span class="event-customer-note"></span>
                            <input type="text" name="customer_note" class="customer-note input-control"
                                placeholder="Điền địa chỉ khác (Nếu có)">
                        </p>
                    </div>
                    <p class="event-item"><span class="item-title">Bộ phận chụp: </span><span
                            class="event-select"></span></p>
                    <div>
                        <p class="event-item"><span class="item-title">Tên người liên hệ: </span>
                            <span class="event-customer-name"></span>
                            <input type="text" name="customer_name" class="customer-name input-control"
                                placeholder="Điền tên người liên hệ">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">SĐT người liên hệ: </span>
                            <span class="event-customer-phone"></span>
                            <input type="text" name="customer_phone" class="customer-phone input-control"
                                placeholder="Điền SĐT người liên hệ">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Giờ chụp: </span>
                            <span class="event-ord-time"></span>
                            <input type="text" name="ord_time" class="ord-time input-control"
                                placeholder="Điền giờ chụp">
                        </p>
                    </div>

                    <div>
                        <p class="event-item"><span class="item-title">Bác sĩ đọc phim: </span>
                            <span class="event-ord-doctor-read"></span>
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_doctor_read" value="" id="id1">
                                <label for="id1" class="accent-l">Trống</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_doctor_read" value="Có" id="id2"
                                    class="input-order-schedule">
                                <label for="id2" class="accent-l">Có</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_doctor_read" value="Không" id="id3"
                                    class="input-order-schedule">
                                <label for="id3" class="accent-l">Không</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div class="">
                        <p class="event-item"><span class="item-title">In phim: </span>
                            <span class="event-ord-film"></span>
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_film" value="" id="id4">
                                <label for="id4" class="accent-l">Trống</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_film" value="Bình thường" id="id5"
                                    class="input-order-schedule">
                                <label for="id5" class="accent-l">Bình thường</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_film" value="Bất thường" id="id6"
                                    class="input-order-schedule">
                                <label for="id6" class="accent-l">Bất thường</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_film" value="Cả 2" id="id7"
                                    class="input-order-schedule">
                                <label for="id7" class="accent-l">Cả 2</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div class="">
                        <p class="event-item"><span class="item-title">Hình thức in phim: </span>
                            <span class="event-ord-form"></span>
                        <div class="row">
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="ko in" id="id8">
                                <label for="id8" class="accent-l">Trống</label>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="IN4" id="id9"
                                    class="input-order-schedule">
                                <label for="id9" class="accent-l">16,5 x 21,5(IN4)</label>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="IN12" id="id10"
                                    class="input-order-schedule">
                                <label for="id10" class="accent-l">11 x 10,5(IN12)</label>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="IN16" id="id11">
                                <label for="id11" class="accent-l">8,5 x 10,5(IN16)</label>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="IN8X10" id="id12"
                                    class="input-order-schedule">
                                <label for="id12" class="accent-l">20,5 x 25,5(IN8X10)</label>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="IN10X12" id="id13"
                                    class="input-order-schedule">
                                <label for="id13" class="accent-l">25,5 x 30,5(IN10X12)</label>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="PhimLon" id="id14"
                                    class="">
                                <label for="id14" class="accent-l">35 x 43(Phim Lớn)</label>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <input type="radio" name="ord_form" value="Bệnh lý" id="id32"
                                    class="input-order-schedule">
                                <label for="id32" class="accent-l">Bệnh lý</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div class="">
                        <p class="event-item"><span class="item-title">In phiếu: </span>
                            <span class="event-ord-print"></span>
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_print" value="" id="id15">
                                <label for="id15" class="accent-l">Trống</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_print" value="Bình thường" id="id16"
                                    class="input-order-schedule">
                                <label for="id16" class="accent-l">Bình thường</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_print" value="Bất thường" id="id17"
                                    class="input-order-schedule">
                                <label for="id17" class="accent-l">Bất thường</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_print" value="Cả 2" id="id18"
                                    class="input-order-schedule">
                                <label for="id18" class="accent-l">Cả 2</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div class="">
                        <p class="event-item"><span class="item-title">Hình thức in phiếu: </span>
                            <span class="event-ord-form-print"></span>
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_form_print" value="" id="id19">
                                <label for="id19" class="accent-l">Trống</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_form_print" value="A4" id="id20"
                                    class="input-order-schedule">
                                <label for="id20" class="accent-l">A4</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_form_print" value="A5" id="id21"
                                    class="input-order-schedule">
                                <label for="id21" class="accent-l">A5</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div class="">
                        <p class="event-item"><span class="item-title">In phiếu kết quả theo mẫu đơn vị: </span>
                            <span class="event-ord-print-result"></span>
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_print_result" value="" id="id22">
                                <label for="id22" class="accent-l">Trống</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_print_result" value="Có" id="id23"
                                    class="input-order-schedule">
                                <label for="id23" class="accent-l">Có</label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <input type="radio" name="ord_print_result" value="Không" id="id24"
                                    class="input-order-schedule">
                                <label for="id24" class="accent-l">Không</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div class="">
                        <p class="event-item"><span class="item-title">Phim & Phiếu: </span>
                            <span class="event-ord-film-sheet"></span>
                        <div class="row">
                            <div class="col-lg-6 col-md-6">
                                <input type="radio" name="ord_film_sheet" value="" id="id25">
                                <label for="id25" class="accent-l">Trống</label>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <input type="radio" name="ord_film_sheet" value="Bấm flim vào phiếu"
                                    id="id26" class="input-order-schedule">
                                <label for="id26" class="accent-l">Bấm flim vào phiếu</label>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <input type="radio" name="ord_film_sheet" value="Bỏ flim và phiếu vào bao thư"
                                    id="id27" class="">
                                <label for="id27" class="accent-l">Bỏ flim và phiếu vào bao thư</label>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <input type="radio" name="ord_film_sheet" value="Bỏ flim và phiếu vào bao vàng"
                                    id="id28" class="input-order-schedule">
                                <label for="id28" class="accent-l">Bỏ flim và phiếu vào bao vàng</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Ghi chú: </span>
                            <span class="event-order-note"></span>
                            <input type="text" name="ord_note" class="ord-note input-control"
                                placeholder="Điền ghi chú">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Cảnh báo đơn hàng: </span>
                            <span class="event-order-warning"></span>
                        <div class="row">
                            <div class="col-lg-6 col-md-6">
                                <input type="radio" name="order_warning" value="Không" id="id29">
                                <label for="id29" class="accent-l">Không</label>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <input type="radio" name="order_warning" value="Có" id="id30"
                                    class="input-order-schedule">
                                <label for="id30" class="accent-l">Có</label>
                            </div>
                        </div>
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Thời hạn giao kết quả: </span>
                            <span class="event-ord-deadline"></span>
                            <input type="text" name="ord_deadline" class="ord-deadline input-control"
                                placeholder="Điền thời hạn giao kết quả">
                        </p>
                    </div>

                    <p class="event-item"><span class="item-title">Địa chỉ & sđt giao kết quả: </span>
                        <span class="event-deliver-results"></span>
                    </p>

                    <p class="event-item"><span class="item-title">Địa chỉ email khách hàng: </span>
                        <span class="event-email"></span>
                    </p>

                    <p class="item_ktv"></p>
                    <p class="event-item"><span class="item-title">Trạng thái đơn: </span><span
                            class="event-status"></span></p>
                    <p class="event-item"><span class="item-title">Số Cas: </span><span
                            class="event-quantity"></span></p>
                    <p class="event-item"><span class="item-title">Số Cas KTV chụp: </span><span
                        class="event-draft"></span></p>
                    <p class="event-item"><span class="item-title">Ghi chú KTV: </span><span
                            class="event-noteKtv"></span></p>
                    <p class="event-item"><span class="item-title">Bác sĩ đọc: </span>
                        <span class="event-accountant-doctor-read-clone"></span>
                    </p>
                    <p class="event-item"><span class="item-title">35 X 43: </span>
                        <span class="event-35X43-clone"></span>
                    </p>
                    <p class="event-item"><span class="item-title">Polime: </span>
                        <span class="event-polime-clone"></span>
                    </p>
                    <p class="event-item"><span class="item-title">8 X 10: </span>
                        <span class="event-8X10-clone"></span>
                    </p>
                    <p class="event-item"><span class="item-title">10 X 12: </span>
                        <span class="event-10X12-clone"></span>
                    </p>
                    <p class="event-item"><span class="item-title">Ghi chú báo cáo(Anh Sơn): </span>
                        <span class="event-accountant-note-clone"></span>
                    </p>
                    <p class="event-item"><span class="item-title">Ngày trả kết quả: </span>
                        <span class="event-delivery-date-clone"></span>
                    </p>
                    <div class="section-form-sale">
                        <button type="button" name="update_order" class="primary-btn-submit update-order">Cập
                            nhật</button>
                    </div>
                </form>
            </div>
            <div class="body-bg"></div>
        </div>
        <a href="javascript:;" class="close"></a>
    </div>
    <div class="cover-layer"></div>
</div>
<div class="accountant">
    <div class="container-fluid">
        <div class="account-header">
            <div class="account-header-title">
                <h3>Cas phim</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">Tổng số hình chụp: </span>
                    <span class="account-content-child">{{ $accountant_total_complete }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">35 X 43: </span>
                    <span class="account-content-child">{{ $accountant_total_35 }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">8 X 10: </span>
                    <span class="account-content-child">{{ $accountant_total_8 }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">10 X 12: </span>
                    <span class="account-content-child">{{ $accountant_total_10 }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">Tổng số Cas chụp: </span>
                    <span class="account-content-child">{{ $accountant_total_cas }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="account-header">
            <div class="account-header-title">
                <h3>Bác sĩ đọc</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">Hồ Chí Trung: </span>
                    <span class="account-content-child">{{ $accountant_total_T }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">Võ Nguyễn Thành Nhân: </span>
                    <span class="account-content-child">{{ $accountant_total_N }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">Không: </span>
                    <span class="account-content-child">{{ $accountant_total_K }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="account-content">
                    <span class="account-title">Nguyễn Thanh Giang: </span>
                    <span class="account-content-child">{{ $accountant_total_G }}</span>
                </div>
            </div>
        </div>
    </div>
</div>