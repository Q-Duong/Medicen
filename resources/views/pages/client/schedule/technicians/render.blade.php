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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                            $dateKey = \Carbon\Carbon::createFromDate(
                                $currentYear,
                                $currentMonthNum,
                                $i,
                            )->format('Y-m-d');
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
                        class="as-svgicon as-svgicon-close as-svgicon-tiny as-svgicon-closetiny"
                        role="img" aria-hidden="true">
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
                    <span class="item-title">Địa chỉ email khách hàng: </span>
                    <span class="event-email"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Giờ chụp: </span>
                    <span class="event-time"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Ghi chú: </span>
                    <span class="event-order-note"></span>
                </p>
                <p class="event-item">
                    <span class="item-title">Số Cas: </span>
                    <span class="event-quantity"></span>
                </p>
                <p class="schedule-line"></p>
                <form onsubmit="required()" method="post">
                    @csrf
                    <div class="form-textbox">
                        <input type="text" class="form-textbox-input order-quantity-ktv"
                            name="order_quantity_draft" autocapitalize="off" autocomplete="off">
                        <span class="form-textbox-label">Số Cas KTV chụp</span>
                    </div>
                    <legend class="rs-form-label">
                        <h3 class="rs-form-label-header typography-body">Ghi chú KTV
                        </h3>
                    </legend>
                    <div class="form-textbox">
                        <textarea name="order_note_ktv" rows=8 class="form-textarea order-note-ktv"></textarea>
                    </div>
                    <div class="rs-overlay-change">
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