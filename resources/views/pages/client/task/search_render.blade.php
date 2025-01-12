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
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($orders as $key => $order)
                        @if ($order->car_name == 1 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                            <li class="single-event border-child"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-1"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($order->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em class="event-status hidden">{{ $order->status_id }}</em>
                                    <em class="event-start-day hidden">{{ $order->ord_start_day }}</em>
                                    <em class="event-warning hidden">{{ $order->order_warning }}</em>
                                    <em class="event-id hidden">{{ $order->order_id }}</em>
                                    <em class="event-quantity hidden">{{ $order->order_quantity }}</em>
                                    <em class="event-quantity-draft hidden">{{ $order->order_quantity_draft }}</em>
                                    <em class="event-note-ktv hidden">{{ $order->order_note_ktv }}</em>
                                    <em class="event-car-id hidden">{{ $order->id }}</em>
                                    <em class="event-unit hidden">{{ $order->unit_name }}</em>
                                    <em class="event-address hidden">{{ $order->customer_address }}</em>
                                    <em class="event-note hidden">{{ $order->customer_note }}</em>
                                    <em class="event-info-contact hidden">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em class="event-details-id hidden">{{ $order->order_detail_id }}</em>
                                    <em class="event-select hidden">{{ $order->ord_select }}</em>
                                    <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                    <em class="event-time hidden">{{ $order->ord_time }}</em>
                                    <em class="event-list-file-path hidden">{{ $order->ord_list_file_path }}</em>
                                    <em class="event-list-file hidden">{{ $order->ord_list_file }}</em>
                                    <em class="event-total-file-path hidden">{{ $order->ord_total_file_path }}</em>
                                    <em class="event-total-file hidden">{{ $order->ord_total_file_name }}</em>
                                    <em class="event-doctor-read hidden">{{ $order->ord_doctor_read }}</em>
                                    <em class="event-film hidden">{{ $order->ord_film }}</em>
                                    <em class="event-form hidden">{{ $order->ord_form }}</em>
                                    <em class="event-print hidden">{{ $order->ord_print }}</em>
                                    <em class="event-form-print hidden">{{ $order->ord_form_print }}</em>
                                    <em class="event-print-result hidden">{{ $order->ord_print_result }}</em>
                                    <em class="event-film-sheet hidden">{{ $order->ord_film_sheet }}</em>
                                    <em class="event-order-note hidden">{{ $order->ord_note }}</em>
                                    <em class="event-deadline hidden">{{ $order->ord_deadline }}</em>
                                    <em class="event-deliver-results hidden">{{ $order->ord_deliver_results }}</em>
                                    <em class="event-email hidden">{{ $order->ord_email }}</em>
                                    <em class="event-delivery-date hidden">{{ $order->ord_delivery_date }}</em>
                                    <em
                                        class="event-accountant-doctor-read hidden">{{ $order->accountant_doctor_read }}</em>
                                    <em class="event-35X43 hidden">{{ $order->accountant_35X43 }}</em>
                                    <em class="event-polime hidden">{{ $order->accountant_polime }}</em>
                                    <em class="event-8X10 hidden">{{ $order->accountant_8X10 }}</em>
                                    <em class="event-10X12 hidden">{{ $order->accountant_10X12 }}</em>
                                    <em class="event-film-bag hidden">{{ $order->accountant_film_bag }}</em>
                                    <em class="event-accountant-note hidden">{{ $order->accountant_note }}</em>
                                    <em
                                        class="event-route-edit hidden">{{ route('order.edit', $order->order_id) }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 2</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($orders as $key => $order)
                        @if ($order->car_name == 2 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-2"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($order->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em class="event-status hidden">{{ $order->status_id }}</em>
                                    <em class="event-start-day hidden">{{ $order->ord_start_day }}</em>
                                    <em class="event-warning hidden">{{ $order->order_warning }}</em>
                                    <em class="event-id hidden">{{ $order->order_id }}</em>
                                    <em class="event-quantity hidden">{{ $order->order_quantity }}</em>
                                    <em class="event-quantity-draft hidden">{{ $order->order_quantity_draft }}</em>
                                    <em class="event-note-ktv hidden">{{ $order->order_note_ktv }}</em>
                                    <em class="event-car-id hidden">{{ $order->id }}</em>
                                    <em class="event-unit hidden">{{ $order->unit_name }}</em>
                                    <em class="event-address hidden">{{ $order->customer_address }}</em>
                                    <em class="event-note hidden">{{ $order->customer_note }}</em>
                                    <em class="event-info-contact hidden">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em class="event-details-id hidden">{{ $order->order_detail_id }}</em>
                                    <em class="event-select hidden">{{ $order->ord_select }}</em>
                                    <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                    <em class="event-time hidden">{{ $order->ord_time }}</em>
                                    <em class="event-list-file-path hidden">{{ $order->ord_list_file_path }}</em>
                                    <em class="event-list-file hidden">{{ $order->ord_list_file }}</em>
                                    <em class="event-total-file-path hidden">{{ $order->ord_total_file_path }}</em>
                                    <em class="event-total-file hidden">{{ $order->ord_total_file_name }}</em>
                                    <em class="event-doctor-read hidden">{{ $order->ord_doctor_read }}</em>
                                    <em class="event-film hidden">{{ $order->ord_film }}</em>
                                    <em class="event-form hidden">{{ $order->ord_form }}</em>
                                    <em class="event-print hidden">{{ $order->ord_print }}</em>
                                    <em class="event-form-print hidden">{{ $order->ord_form_print }}</em>
                                    <em class="event-print-result hidden">{{ $order->ord_print_result }}</em>
                                    <em class="event-film-sheet hidden">{{ $order->ord_film_sheet }}</em>
                                    <em class="event-order-note hidden">{{ $order->ord_note }}</em>
                                    <em class="event-deadline hidden">{{ $order->ord_deadline }}</em>
                                    <em class="event-deliver-results hidden">{{ $order->ord_deliver_results }}</em>
                                    <em class="event-email hidden">{{ $order->ord_email }}</em>
                                    <em class="event-delivery-date hidden">{{ $order->ord_delivery_date }}</em>
                                    <em
                                        class="event-accountant-doctor-read hidden">{{ $order->accountant_doctor_read }}</em>
                                    <em class="event-35X43 hidden">{{ $order->accountant_35X43 }}</em>
                                    <em class="event-polime hidden">{{ $order->accountant_polime }}</em>
                                    <em class="event-8X10 hidden">{{ $order->accountant_8X10 }}</em>
                                    <em class="event-10X12 hidden">{{ $order->accountant_10X12 }}</em>
                                    <em class="event-film-bag hidden">{{ $order->accountant_film_bag }}</em>
                                    <em class="event-accountant-note hidden">{{ $order->accountant_note }}</em>
                                    <em
                                        class="event-route-edit hidden">{{ route('order.edit', $order->order_id) }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 3</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($orders as $key => $order)
                        @if ($order->car_name == 3 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-3"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($order->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em class="event-status hidden">{{ $order->status_id }}</em>
                                    <em class="event-start-day hidden">{{ $order->ord_start_day }}</em>
                                    <em class="event-warning hidden">{{ $order->order_warning }}</em>
                                    <em class="event-id hidden">{{ $order->order_id }}</em>
                                    <em class="event-quantity hidden">{{ $order->order_quantity }}</em>
                                    <em class="event-quantity-draft hidden">{{ $order->order_quantity_draft }}</em>
                                    <em class="event-note-ktv hidden">{{ $order->order_note_ktv }}</em>
                                    <em class="event-car-id hidden">{{ $order->id }}</em>
                                    <em class="event-unit hidden">{{ $order->unit_name }}</em>
                                    <em class="event-address hidden">{{ $order->customer_address }}</em>
                                    <em class="event-note hidden">{{ $order->customer_note }}</em>
                                    <em class="event-info-contact hidden">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em class="event-details-id hidden">{{ $order->order_detail_id }}</em>
                                    <em class="event-select hidden">{{ $order->ord_select }}</em>
                                    <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                    <em class="event-time hidden">{{ $order->ord_time }}</em>
                                    <em class="event-list-file-path hidden">{{ $order->ord_list_file_path }}</em>
                                    <em class="event-list-file hidden">{{ $order->ord_list_file }}</em>
                                    <em class="event-total-file-path hidden">{{ $order->ord_total_file_path }}</em>
                                    <em class="event-total-file hidden">{{ $order->ord_total_file_name }}</em>
                                    <em class="event-doctor-read hidden">{{ $order->ord_doctor_read }}</em>
                                    <em class="event-film hidden">{{ $order->ord_film }}</em>
                                    <em class="event-form hidden">{{ $order->ord_form }}</em>
                                    <em class="event-print hidden">{{ $order->ord_print }}</em>
                                    <em class="event-form-print hidden">{{ $order->ord_form_print }}</em>
                                    <em class="event-print-result hidden">{{ $order->ord_print_result }}</em>
                                    <em class="event-film-sheet hidden">{{ $order->ord_film_sheet }}</em>
                                    <em class="event-order-note hidden">{{ $order->ord_note }}</em>
                                    <em class="event-deadline hidden">{{ $order->ord_deadline }}</em>
                                    <em class="event-deliver-results hidden">{{ $order->ord_deliver_results }}</em>
                                    <em class="event-email hidden">{{ $order->ord_email }}</em>
                                    <em class="event-delivery-date hidden">{{ $order->ord_delivery_date }}</em>
                                    <em
                                        class="event-accountant-doctor-read hidden">{{ $order->accountant_doctor_read }}</em>
                                    <em class="event-35X43 hidden">{{ $order->accountant_35X43 }}</em>
                                    <em class="event-polime hidden">{{ $order->accountant_polime }}</em>
                                    <em class="event-8X10 hidden">{{ $order->accountant_8X10 }}</em>
                                    <em class="event-10X12 hidden">{{ $order->accountant_10X12 }}</em>
                                    <em class="event-film-bag hidden">{{ $order->accountant_film_bag }}</em>
                                    <em class="event-accountant-note hidden">{{ $order->accountant_note }}</em>
                                    <em
                                        class="event-route-edit hidden">{{ route('order.edit', $order->order_id) }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 4</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($orders as $key => $order)
                        @if ($order->car_name == 4 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-4"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($order->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em class="event-status hidden">{{ $order->status_id }}</em>
                                    <em class="event-start-day hidden">{{ $order->ord_start_day }}</em>
                                    <em class="event-warning hidden">{{ $order->order_warning }}</em>
                                    <em class="event-id hidden">{{ $order->order_id }}</em>
                                    <em class="event-quantity hidden">{{ $order->order_quantity }}</em>
                                    <em class="event-quantity-draft hidden">{{ $order->order_quantity_draft }}</em>
                                    <em class="event-note-ktv hidden">{{ $order->order_note_ktv }}</em>
                                    <em class="event-car-id hidden">{{ $order->id }}</em>
                                    <em class="event-unit hidden">{{ $order->unit_name }}</em>
                                    <em class="event-address hidden">{{ $order->customer_address }}</em>
                                    <em class="event-note hidden">{{ $order->customer_note }}</em>
                                    <em class="event-info-contact hidden">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em class="event-details-id hidden">{{ $order->order_detail_id }}</em>
                                    <em class="event-select hidden">{{ $order->ord_select }}</em>
                                    <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                    <em class="event-time hidden">{{ $order->ord_time }}</em>
                                    <em class="event-list-file-path hidden">{{ $order->ord_list_file_path }}</em>
                                    <em class="event-list-file hidden">{{ $order->ord_list_file }}</em>
                                    <em class="event-total-file-path hidden">{{ $order->ord_total_file_path }}</em>
                                    <em class="event-total-file hidden">{{ $order->ord_total_file_name }}</em>
                                    <em class="event-doctor-read hidden">{{ $order->ord_doctor_read }}</em>
                                    <em class="event-film hidden">{{ $order->ord_film }}</em>
                                    <em class="event-form hidden">{{ $order->ord_form }}</em>
                                    <em class="event-print hidden">{{ $order->ord_print }}</em>
                                    <em class="event-form-print hidden">{{ $order->ord_form_print }}</em>
                                    <em class="event-print-result hidden">{{ $order->ord_print_result }}</em>
                                    <em class="event-film-sheet hidden">{{ $order->ord_film_sheet }}</em>
                                    <em class="event-order-note hidden">{{ $order->ord_note }}</em>
                                    <em class="event-deadline hidden">{{ $order->ord_deadline }}</em>
                                    <em class="event-deliver-results hidden">{{ $order->ord_deliver_results }}</em>
                                    <em class="event-email hidden">{{ $order->ord_email }}</em>
                                    <em class="event-delivery-date hidden">{{ $order->ord_delivery_date }}</em>
                                    <em
                                        class="event-accountant-doctor-read hidden">{{ $order->accountant_doctor_read }}</em>
                                    <em class="event-35X43 hidden">{{ $order->accountant_35X43 }}</em>
                                    <em class="event-polime hidden">{{ $order->accountant_polime }}</em>
                                    <em class="event-8X10 hidden">{{ $order->accountant_8X10 }}</em>
                                    <em class="event-10X12 hidden">{{ $order->accountant_10X12 }}</em>
                                    <em class="event-film-bag hidden">{{ $order->accountant_film_bag }}</em>
                                    <em class="event-accountant-note hidden">{{ $order->accountant_note }}</em>
                                    <em
                                        class="event-route-edit hidden">{{ route('order.edit', $order->order_id) }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe 5</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($orders as $key => $order)
                        @if ($order->car_name == 5 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($order->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em class="event-status hidden">{{ $order->status_id }}</em>
                                    <em class="event-start-day hidden">{{ $order->ord_start_day }}</em>
                                    <em class="event-warning hidden">{{ $order->order_warning }}</em>
                                    <em class="event-id hidden">{{ $order->order_id }}</em>
                                    <em class="event-quantity hidden">{{ $order->order_quantity }}</em>
                                    <em class="event-quantity-draft hidden">{{ $order->order_quantity_draft }}</em>
                                    <em class="event-note-ktv hidden">{{ $order->order_note_ktv }}</em>
                                    <em class="event-car-id hidden">{{ $order->id }}</em>
                                    <em class="event-unit hidden">{{ $order->unit_name }}</em>
                                    <em class="event-address hidden">{{ $order->customer_address }}</em>
                                    <em class="event-note hidden">{{ $order->customer_note }}</em>
                                    <em class="event-info-contact hidden">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em class="event-details-id hidden">{{ $order->order_detail_id }}</em>
                                    <em class="event-select hidden">{{ $order->ord_select }}</em>
                                    <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                    <em class="event-time hidden">{{ $order->ord_time }}</em>
                                    <em class="event-list-file-path hidden">{{ $order->ord_list_file_path }}</em>
                                    <em class="event-list-file hidden">{{ $order->ord_list_file }}</em>
                                    <em class="event-total-file-path hidden">{{ $order->ord_total_file_path }}</em>
                                    <em class="event-total-file hidden">{{ $order->ord_total_file_name }}</em>
                                    <em class="event-doctor-read hidden">{{ $order->ord_doctor_read }}</em>
                                    <em class="event-film hidden">{{ $order->ord_film }}</em>
                                    <em class="event-form hidden">{{ $order->ord_form }}</em>
                                    <em class="event-print hidden">{{ $order->ord_print }}</em>
                                    <em class="event-form-print hidden">{{ $order->ord_form_print }}</em>
                                    <em class="event-print-result hidden">{{ $order->ord_print_result }}</em>
                                    <em class="event-film-sheet hidden">{{ $order->ord_film_sheet }}</em>
                                    <em class="event-order-note hidden">{{ $order->ord_note }}</em>
                                    <em class="event-deadline hidden">{{ $order->ord_deadline }}</em>
                                    <em class="event-deliver-results hidden">{{ $order->ord_deliver_results }}</em>
                                    <em class="event-email hidden">{{ $order->ord_email }}</em>
                                    <em class="event-delivery-date hidden">{{ $order->ord_delivery_date }}</em>
                                    <em
                                        class="event-accountant-doctor-read hidden">{{ $order->accountant_doctor_read }}</em>
                                    <em class="event-35X43 hidden">{{ $order->accountant_35X43 }}</em>
                                    <em class="event-polime hidden">{{ $order->accountant_polime }}</em>
                                    <em class="event-8X10 hidden">{{ $order->accountant_8X10 }}</em>
                                    <em class="event-10X12 hidden">{{ $order->accountant_10X12 }}</em>
                                    <em class="event-film-bag hidden">{{ $order->accountant_film_bag }}</em>
                                    <em class="event-accountant-note hidden">{{ $order->accountant_note }}</em>
                                    <em
                                        class="event-route-edit hidden">{{ route('order.edit', $order->order_id) }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info"><span>Xe Thuê</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($orders as $key => $order)
                        @if ($order->car_name == 6 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($order->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em class="event-status hidden">{{ $order->status_id }}</em>
                                    <em class="event-start-day hidden">{{ $order->ord_start_day }}</em>
                                    <em class="event-warning hidden">{{ $order->order_warning }}</em>
                                    <em class="event-id hidden">{{ $order->order_id }}</em>
                                    <em class="event-quantity hidden">{{ $order->order_quantity }}</em>
                                    <em class="event-quantity-draft hidden">{{ $order->order_quantity_draft }}</em>
                                    <em class="event-note-ktv hidden">{{ $order->order_note_ktv }}</em>
                                    <em class="event-car-id hidden">{{ $order->id }}</em>
                                    <em class="event-unit hidden">{{ $order->unit_name }}</em>
                                    <em class="event-address hidden">{{ $order->customer_address }}</em>
                                    <em class="event-note hidden">{{ $order->customer_note }}</em>
                                    <em class="event-info-contact hidden">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em class="event-details-id hidden">{{ $order->order_detail_id }}</em>
                                    <em class="event-select hidden">{{ $order->ord_select }}</em>
                                    <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                    <em class="event-time hidden">{{ $order->ord_time }}</em>
                                    <em class="event-list-file-path hidden">{{ $order->ord_list_file_path }}</em>
                                    <em class="event-list-file hidden">{{ $order->ord_list_file }}</em>
                                    <em class="event-total-file-path hidden">{{ $order->ord_total_file_path }}</em>
                                    <em class="event-total-file hidden">{{ $order->ord_total_file_name }}</em>
                                    <em class="event-doctor-read hidden">{{ $order->ord_doctor_read }}</em>
                                    <em class="event-film hidden">{{ $order->ord_film }}</em>
                                    <em class="event-form hidden">{{ $order->ord_form }}</em>
                                    <em class="event-print hidden">{{ $order->ord_print }}</em>
                                    <em class="event-form-print hidden">{{ $order->ord_form_print }}</em>
                                    <em class="event-print-result hidden">{{ $order->ord_print_result }}</em>
                                    <em class="event-film-sheet hidden">{{ $order->ord_film_sheet }}</em>
                                    <em class="event-order-note hidden">{{ $order->ord_note }}</em>
                                    <em class="event-deadline hidden">{{ $order->ord_deadline }}</em>
                                    <em class="event-deliver-results hidden">{{ $order->ord_deliver_results }}</em>
                                    <em class="event-email hidden">{{ $order->ord_email }}</em>
                                    <em class="event-delivery-date hidden">{{ $order->ord_delivery_date }}</em>
                                    <em
                                        class="event-accountant-doctor-read hidden">{{ $order->accountant_doctor_read }}</em>
                                    <em class="event-35X43 hidden">{{ $order->accountant_35X43 }}</em>
                                    <em class="event-polime hidden">{{ $order->accountant_polime }}</em>
                                    <em class="event-8X10 hidden">{{ $order->accountant_8X10 }}</em>
                                    <em class="event-10X12 hidden">{{ $order->accountant_10X12 }}</em>
                                    <em class="event-film-bag hidden">{{ $order->accountant_film_bag }}</em>
                                    <em class="event-accountant-note hidden">{{ $order->accountant_note }}</em>
                                    <em
                                        class="event-route-edit hidden">{{ route('order.edit', $order->order_id) }}</em>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

            <li class="events-group">
                <div class="top-info child-7"><span>Xe Tăng Cường</span></div>
                <ul {{ $dayInMonth == 31 ? 'style=height:1550px' : 'style=height:1500px' }}>
                    @foreach ($orders as $key => $order)
                        @if ($order->car_name == 7 && $order->car_active == 1 && $order->status_id != 0 && $order->order_surcharge == 0)
                            <li class="single-event"
                                data-start="{{ Carbon\Carbon::parse($order->ord_start_day)->format('d/m/Y') }}"
                                data-content="event-rowing-workout" data-event="event-5"
                                data-child="{{ $order->order_child }}">
                                @if ($order->order_updated == 1)
                                    <div class="order-status">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                @endif
                                @if ($order->order_warning == 'Có')
                                    <div class="order-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                @endif
                                @php
                                    $str1 = explode(' ', $order->car_ktv_name_1);
                                    $name1 = array_pop($str1);
                                    $str2 = explode(' ', $order->car_ktv_name_2);
                                    $name2 = array_pop($str2);
                                @endphp
                                <a href="javascript:;">
                                    <em class="event-name"><span class="item-title">KTV: {{ $name1 }},
                                            {{ $name2 }}</em>
                                    <em class="event-name-unit"><span class="item-title">Đơn vị:
                                            {{ $order->unit_name }}</em>
                                    <em class="event-status hidden">{{ $order->status_id }}</em>
                                    <em class="event-start-day hidden">{{ $order->ord_start_day }}</em>
                                    <em class="event-warning hidden">{{ $order->order_warning }}</em>
                                    <em class="event-id hidden">{{ $order->order_id }}</em>
                                    <em class="event-quantity hidden">{{ $order->order_quantity }}</em>
                                    <em class="event-quantity-draft hidden">{{ $order->order_quantity_draft }}</em>
                                    <em class="event-note-ktv hidden">{{ $order->order_note_ktv }}</em>
                                    <em class="event-car-id hidden">{{ $order->id }}</em>
                                    <em class="event-unit hidden">{{ $order->unit_name }}</em>
                                    <em class="event-address hidden">{{ $order->customer_address }}</em>
                                    <em class="event-note hidden">{{ $order->customer_note }}</em>
                                    <em class="event-info-contact hidden">{{ $order->customer_name }}
                                        ({{ $order->customer_phone }})</em>
                                    <em class="event-details-id hidden">{{ $order->order_detail_id }}</em>
                                    <em class="event-select hidden">{{ $order->ord_select }}</em>
                                    <em class="event-cty-name hidden">{{ $order->ord_cty_name }}</em>
                                    <em class="event-time hidden">{{ $order->ord_time }}</em>
                                    <em class="event-list-file-path hidden">{{ $order->ord_list_file_path }}</em>
                                    <em class="event-list-file hidden">{{ $order->ord_list_file }}</em>
                                    <em class="event-total-file-path hidden">{{ $order->ord_total_file_path }}</em>
                                    <em class="event-total-file hidden">{{ $order->ord_total_file_name }}</em>
                                    <em class="event-doctor-read hidden">{{ $order->ord_doctor_read }}</em>
                                    <em class="event-film hidden">{{ $order->ord_film }}</em>
                                    <em class="event-form hidden">{{ $order->ord_form }}</em>
                                    <em class="event-print hidden">{{ $order->ord_print }}</em>
                                    <em class="event-form-print hidden">{{ $order->ord_form_print }}</em>
                                    <em class="event-print-result hidden">{{ $order->ord_print_result }}</em>
                                    <em class="event-film-sheet hidden">{{ $order->ord_film_sheet }}</em>
                                    <em class="event-order-note hidden">{{ $order->ord_note }}</em>
                                    <em class="event-deadline hidden">{{ $order->ord_deadline }}</em>
                                    <em class="event-deliver-results hidden">{{ $order->ord_deliver_results }}</em>
                                    <em class="event-email hidden">{{ $order->ord_email }}</em>
                                    <em class="event-delivery-date hidden">{{ $order->ord_delivery_date }}</em>
                                    <em
                                        class="event-accountant-doctor-read hidden">{{ $order->accountant_doctor_read }}</em>
                                    <em class="event-35X43 hidden">{{ $order->accountant_35X43 }}</em>
                                    <em class="event-polime hidden">{{ $order->accountant_polime }}</em>
                                    <em class="event-8X10 hidden">{{ $order->accountant_8X10 }}</em>
                                    <em class="event-10X12 hidden">{{ $order->accountant_10X12 }}</em>
                                    <em class="event-film-bag hidden">{{ $order->accountant_film_bag }}</em>
                                    <em class="event-accountant-note hidden">{{ $order->accountant_note }}</em>
                                    <em
                                        class="event-route-edit hidden">{{ route('order.edit', $order->order_id) }}</em>
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
                <p class="event-item">
                    <span class="item-title">Đơn vị: </span>
                    <span class="event-unit"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Tên Cty: </span>
                    <span class="event-cty-name"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Địa chỉ: </span>
                    <span class="event-address"></span>
                </p>
                <p class="event-item event-note">
                    <span class="item-title">Địa chỉ khác: </span>
                    <span class="event-note-content"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Bộ phận chụp: </span>
                    <span class="event-select"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Danh sách: </span>
                    <span class="event-list-file"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Thông tin người liên hệ: </span>
                    <span class="event-info-contact"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Giờ chụp: </span>
                    <span class="event-time"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Bác sĩ đọc phim: </span>
                    <span class="event-doctor-read"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">In phim: </span>
                    <span class="event-film"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Hình thức in phim: </span>
                    <span class="event-form"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">In phiếu: </span>
                    <span class="event-print"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Hình thức in phiếu: </span>
                    <span class="event-form-print"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">In phiếu kết quả theo mẫu đơn vị: </span>
                    <span class="event-print-result"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Phim & Phiếu: </span>
                    <span class="event-film-sheet"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Ghi chú: </span>
                    <span class="event-order-note"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Cảnh báo đơn hàng: </span>
                    <span class="event-ord-warning"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Thời hạn giao kết quả: </span>
                    <span class="event-deadline"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Địa chỉ & sđt giao kết quả: </span>
                    <span class="event-deliver-results"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Địa chỉ email khách hàng: </span>
                    <span class="event-email"></span>
                </p>
                <p class="schedule-line"></p>
                <p class="event-item">
                    <span class="item-title">Trạng thái đơn: </span>
                    <span class="event-status"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Số Cas KTV chụp: </span>
                    <span class="event-draft"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Ghi chú KTV: </span>
                    <span class="event-noteKtv"></span>
                </p>
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
                            <input type="text" name="accountant_35X43" class="accountant-35X43 input-control">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">Polime: </span>
                            <span class="event-polime"></span>
                            <input type="text" name="accountant_polime" class="accountant-polime input-control">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">8 X 10: </span>
                            <span class="event-8X10"></span>
                            <input type="text" name="accountant_8X10" class="accountant-8X10 input-control">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">10 X 12: </span>
                            <span class="event-10X12"></span>
                            <input type="text" name="accountant_10X12" class="accountant-10X12 input-control">
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
                            <input type="text" name="ord_delivery_date" class="ord-delivery-date input-control"
                                placeholder="Điền ngày trả kết quả">
                        </p>
                    </div>
                    <div>
                        <p class="event-item"><span class="item-title">File kết quả tổng: </span>
                        <p class="total-file">
                            <input type="file" name="ord_total_file_name" class="filepond">
                        </p>
                        <span class="event-total-file"></span>
                        </p>
                    </div>
                    <p class="schedule-line"></p>
                    <div class="row">
                        <div class="col-lg-6 col-md-6">
                            <p class="event-item event-quantity-item"><span class="item-title">Số Cas: </span>
                                <span class="event-quantity-details"></span>
                                <input type="text" name="order_quantity" class="order-quantity input-control"
                                    placeholder="Điền số cas" value="{{ old('order_quantity') }}">
                            </p>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <button type="button"
                                class="submit-quantity-details primary-btn-submit button-submit">Cập
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
