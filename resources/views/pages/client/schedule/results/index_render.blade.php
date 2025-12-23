<header class="cd-intro">
    <div class="container">
        <div class="schedule-support">
            <div class="row justify-content-center">
                <div class="col-lg-3 col-6">
                    <div class="form-dropdown">
                        <select class="form-dropdown-select select-year">
                            @for ($i = 0; $i <= 10; $i++)
                                <option {{ $currentYear == $i + 2023 ? 'selected' : '' }} value="{{ $i + 2023 }}">
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
                            @foreach ($months as $key => $mth)
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
                <div class="search-box">
                    <div class="btn-search">
                        <button class="btn-schedule-search"><i class="fas fa-search"></i></button>
                    </div>
                    <input type="text" class="schedule-search" name="search-keywords" placeholder="Nhập tên Cty">
                    <div class="search-results"></div>
                </div>
            </div>
        </div>
    </div>
</header>
<div class="schedule">
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
                    <ul style="height: {{ $dayInMonth == 31 ? '1 0px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[1][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-blue' }} border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-1"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-blue stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 2</span></div>
                    <ul style="height: {{ $dayInMonth == 31 ? '1550px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[2][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-gray' }} border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-2"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-gray stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 3</span></div>
                    <ul style="height: {{ $dayInMonth == 31 ? '1550px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[3][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-green' }}  border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-3"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-green stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 4</span></div>
                    <ul style="height: {{ $dayInMonth == 31 ? '1550px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[4][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-red' }} border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-4"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-red stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe 5</span></div>
                    <ul style="height: {{ $dayInMonth == 31 ? '1550px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[5][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-yellow' }} border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-5"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-yellow stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info"><span>Xe Thuê</span></div>
                    <ul style="height: {{ $dayInMonth == 31 ? '1550px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[6][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-purple' }} border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-6"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-purple stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info child-7"><span>Xe Tăng Cường</span></div>
                    <ul style="height: {{ $dayInMonth == 31 ? '1550px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[7][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-orange' }} border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-7"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-orange stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>

                <li class="events-group">
                    <div class="top-info child-8"><span>Xe Siêu Âm</span></div>
                    <ul style="height: {{ $dayInMonth == 31 ? '1550px' : '1500px' }}">
                        @for ($i = 1; $i <= $dayInMonth; $i++)
                            @php
                                $dateKey = \Carbon\Carbon::createFromDate($currentYear, $currentMonthNum, $i)->format(
                                    'Y-m-d',
                                );
                                $dailyOrders = $scheduleData[8][$dateKey] ?? collect([]);
                                $count = $dailyOrders->count();
                            @endphp
                            @if ($count > 0)
                                @php
                                    $firstOrder = $dailyOrders->first();
                                @endphp
                                <li class="single-event {{ $count > 1 ? 'bg-back' : 'bg-teal' }} border-child"
                                    data-start="{{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}"
                                    data-content="event-rowing-workout" data-event="event-8"
                                    data-json='{{ json_encode($dailyOrders) }}'>
                                    @if ($count > 1)
                                        <div class="multi-event-wrapper" onclick="openMultiModal(this)"
                                            data-json='{{ json_encode($dailyOrders) }}'>
                                            <div class="event-card bg-teal stack-front">
                                                <div class="notification-icon-block">
                                                    <div class="more-badge">
                                                        <span>{{ $count }}</span>
                                                    </div>
                                                    @if ($firstOrder->order_warning == 'Có')
                                                        <div class="order-warning">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                        </div>
                                                    @endif
                                                    @if ($firstOrder->order_updated == 1)
                                                        <div class="order-status">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="event-title">
                                                    <div class="sub-title">
                                                        <span class="sub-title-date"><b>Ngày:</b>
                                                            {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                        </span>
                                                        <p class="line">-</p>
                                                        <span class="sub-title-technician"><b>KTV:</b>
                                                            {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                        </span>
                                                    </div>
                                                    <p class="event-name-unit"><b>Đơn vị:</b>
                                                        {{ $firstOrder->unit_abbreviation }}
                                                    </p>
                                                    <div class="see-more">(Click để xem thêm
                                                        {{ $count }} lịch khác)</div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="notification-icon-block">
                                            @if ($firstOrder->order_warning == 'Có')
                                                <div class="order-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            @endif
                                            @if ($firstOrder->order_updated == 1)
                                                <div class="order-status">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="javascript:;">
                                            <div class="event-title">
                                                <div class="sub-title">
                                                    <span class="sub-title-date"><b>Ngày:</b>
                                                        {{ Carbon\Carbon::parse($firstOrder->ord_start_day)->format('d/m/Y') }}
                                                    </span>
                                                    <p class="line">-</p>
                                                    <span class="sub-title-technician"><b>KTV:</b>
                                                        {{ getShortName($firstOrder->car_ktv_name_1) }}{{ $firstOrder->car_ktv_name_2 ? ', ' . getShortName($firstOrder->car_ktv_name_2) : '' }}
                                                    </span>
                                                </div>
                                                <p class="event-name-unit"><b>Đơn vị:</b>
                                                    {{ $firstOrder->unit_abbreviation }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </li>
                            @endif
                        @endfor
                    </ul>
                </li>
            </ul>
        </div>

        <div id="calendarModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 id="header-date-label" class="header-date-label"></h4>
                    <h2 id="header-headline" class="header-headline"></h2>
                </div>
                <div id="modalBody" class="modal-body">
                </div>
                <button type="button" class="rc-close" aria-label="close" data-autom="overlay-close"
                    onclick="closeMultiModal()">
                    <span class="rc-closesvg">
                        <svg width="21" height="21"
                            class="as-svgicon as-svgicon-close as-svgicon-tiny as-svgicon-closetiny" role="img"
                            aria-hidden="true">
                            <path fill="none" d="M0 0h21v21H0z"></path>
                            <path
                                d="m12.12 10 4.07-4.06a1.5 1.5 0 1 0-2.11-2.12L10 7.88 5.94 3.81a1.5 1.5 0 1 0-2.12 2.12L7.88 10l-4.07 4.06a1.5 1.5 0 0 0 0 2.12 1.51 1.51 0 0 0 2.13 0L10 12.12l4.06 4.07a1.45 1.45 0 0 0 1.06.44 1.5 1.5 0 0 0 1.06-2.56Z">
                            </path>
                        </svg>
                    </span>
                </button>
            </div>
        </div>

        <div class="event-modal">
            <header class="header">
                <div class="content">
                    <div class="sub-title">
                        <span class="sub-title-date"><b>Ngày:</b>
                            <span class="event-date"></span>
                        </span>
                        <p class="line">-</p>
                        <span class="sub-title-technician"><b>KTV:</b>
                            <span class="event-technician"></span>
                        </span>
                    </div>
                    <h2 class="event-unit"></h2>
                </div>
                <div class="header-bg"></div>
            </header>

            <div class="body">
                <div class="event-info">
                    <p class="event-item">
                        <span class="item-title">Mã đơn hàng: </span>
                        <span class="event-order-id"></span>
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
                        <span class="event-other-address"></span>
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
                    <p class="schedule-line"></p>
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
                    <p class="schedule-line"></p>
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
                    <p class="schedule-line"></p>
                    <form onsubmit="required()" method="post">
                        @csrf
                        <div class="form-dropdown">
                            <select class="form-dropdown-select accountant-doctor-read" name="accountant_doctor_read">
                                <option class="doctor-empty" value="Không">Không</option>
                                <option class="doctor-N" value="Nhân">Võ Nguyễn Thành Nhân</option>
                                <option class="doctor-T" value="Trung">Hồ Chí Trung</option>
                                <option class="doctor-G" value="Giang">Nguyễn Thanh Giang</option>
                                <option class="doctor-A" value="Ân">Võ Duy Ân</option>
                            </select>
                            <span class="form-dropdown-chevron" aria-hidden="true"><i
                                    class="fa-solid fa-angle-down"></i></span>
                            <span class="form-dropdown-label" aria-hidden="true">Bác sĩ đọc</span>
                        </div>
                        <div class="form-textbox">
                            <input type="text" class="form-textbox-input accountant-35X43" name="accountant_35X43"
                                autocapitalize="off" autocomplete="off">
                            <span class="form-textbox-label">35 X 43</span>
                        </div>
                        <div class="form-textbox">
                            <input type="text" class="form-textbox-input accountant-polime"
                                name="accountant_polime" autocapitalize="off" autocomplete="off">
                            <span class="form-textbox-label">Polime</span>
                        </div>
                        <div class="form-textbox">
                            <input type="text" class="form-textbox-input accountant-8X10" name="accountant_8X10"
                                autocapitalize="off" autocomplete="off">
                            <span class="form-textbox-label">8 X 10</span>
                        </div>
                        <div class="form-textbox">
                            <input type="text" class="form-textbox-input accountant-10X12" name="accountant_10X12"
                                autocapitalize="off" autocomplete="off">
                            <span class="form-textbox-label">10 X 12</span>
                        </div>
                        <legend class="rs-form-label">
                            <h3 class="rs-form-label-header typography-body">Ghi chú
                            </h3>
                        </legend>
                        <div class="form-textbox">
                            <textarea name="accountant_note" rows=8 class="form-textarea accountant-note"></textarea>
                        </div>
                        <div class="form-textbox">
                            <input type="date" class="form-textbox-input ord-delivery-date"
                                name="ord_delivery_date" autocapitalize="off" autocomplete="off">
                            <span class="form-textbox-label">Ngày trả kết quả</span>
                        </div>
                        <legend class="rs-form-label">
                            <h3 class="rs-form-label-header typography-body">Hình thức trả kết quả
                            </h3>
                        </legend>
                        <div class="block-order-send-result"></div>
                        <legend class="rs-form-label">
                            <h3 class="rs-form-label-header typography-body"> File kết quả tổng</h3>
                        </legend>
                        <div class="form-textbox">
                            <p class="total-file">
                                <input type="file" name="ord_total_file_name" class="filepond">
                            </p>
                            <span class="event-total-file"></span>
                            </p>
                        </div>
                        <div class="form-textbox">
                            <input type="text" class="form-textbox-input form-textbox-entered order-quantity"
                                name="order_quantity" autocapitalize="off" autocomplete="off">
                            <span class="form-textbox-label">Số Cas</span>
                        </div>
                        <div class="rs-overlay-change">
                            <button type="button"
                                class="form-button button-submit rs-lookup-submit submit-quantity-details">Cập
                                nhật</button>
                        </div>
                    </form>
                </div>
                <div class="body-bg"></div>
            </div>
            <button type="button" class="rc-close close" aria-label="close" data-autom="overlay-close">
                <span class="rc-closesvg">
                    <svg width="21" height="21"
                        class="as-svgicon as-svgicon-close as-svgicon-tiny as-svgicon-closetiny" role="img"
                        aria-hidden="true">
                        <path fill="none" d="M0 0h21v21H0z"></path>
                        <path
                            d="m12.12 10 4.07-4.06a1.5 1.5 0 1 0-2.11-2.12L10 7.88 5.94 3.81a1.5 1.5 0 1 0-2.12 2.12L7.88 10l-4.07 4.06a1.5 1.5 0 0 0 0 2.12 1.51 1.51 0 0 0 2.13 0L10 12.12l4.06 4.07a1.45 1.45 0 0 0 1.06.44 1.5 1.5 0 0 0 1.06-2.56Z">
                        </path>
                    </svg>
                </span>
            </button>
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
                        <span class="account-content-child">{{ $statistic_complete }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">35 X 43: </span>
                        <span class="account-content-child">{{ $statistic_35 }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">8 X 10: </span>
                        <span class="account-content-child">{{ $statistic_8 }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">10 X 12: </span>
                        <span class="account-content-child">{{ $statistic_10 }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">Tổng số Cas chụp: </span>
                        <span class="account-content-child">{{ $statistic_cas }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">Tổng số Cas siêu âm: </span>
                        <span class="account-content-child">{{ $statistic_ultrasound }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">Tổng số Cas loãng xương: </span>
                        <span class="account-content-child">{{ $statistic_bone }}</span>
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
                        <span class="account-content-child">{{ $statistic_T }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">Võ Nguyễn Thành Nhân: </span>
                        <span class="account-content-child">{{ $statistic_N }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">Không: </span>
                        <span class="account-content-child">{{ $statistic_K }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">Võ Duy Ân: </span>
                        <span class="account-content-child">{{ $statistic_A }}</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="account-content">
                        <span class="account-title">Nguyễn Thanh Giang: </span>
                        <span class="account-content-child">{{ $statistic_G }}</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="{{ versionResource('assets/js/support/file/handle-file.js') }}"></script>
</div>
