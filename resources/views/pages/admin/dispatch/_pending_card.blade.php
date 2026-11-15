{{-- Thẻ một đơn chưa xếp xe. $group: main (30 ngày tới) | past (đã qua ngày) | later (xa hơn 30 ngày) --}}
@php
    $sessionTag = fn ($s) => ['Sáng' => 'S', 'Chiều' => 'C'][trim((string) $s)] ?? 'F';
@endphp
                        <div class="pend__item {{ $group === 'past' ? 'pend__item--past' : '' }} {{ (int) ($o->order_surcharge ?? 0) === 1 ? 'pend__item--sur' : '' }}" data-group="{{ $group }}" tabindex="0" role="button" data-order="{{ $o->id }}" @if (!empty($o->order_group_id)) data-group-id="{{ $o->order_group_id }}" @endif data-date="{{ $o->ord_start_day }}"
                            @if ($o->hold) data-hold="{{ $o->hold['id'] }}" data-hold-date="{{ $o->hold['start'] }}" @endif aria-label="Xếp lịch thủ công đơn {{ $o->id }} {{ $o->unit }}">
                            <b>{{ $o->unit }}</b>@if ((int) ($o->order_surcharge ?? 0) === 1)<span class="tag tag--sur" title="Đơn phụ thu: thu thêm cho đơn chính, không tính vào chấm công">Phụ thu</span>@endif
                            @if (!empty($o->group_code))<div class="pend__grp" title="Nhóm đơn đi chung: bấm để xếp cả nhóm cùng xe"><i class="fas fa-link"></i> Nhóm {{ $o->group_code }}</div>@endif
                            @if ($o->ord_cty_name && mb_strtolower($o->ord_cty_name) !== mb_strtolower((string) $o->unit))
                                <div class="pend__co" title="{{ $o->ord_cty_name }}"><i class="far fa-building"></i> {{ $o->ord_cty_name }}</div>
                            @endif
                            <div class="pend__meta">
                                {{ \Carbon\Carbon::parse($o->ord_start_day)->format('d/m') }}@if ($o->ord_end_day && $o->ord_end_day !== $o->ord_start_day)–{{ \Carbon\Carbon::parse($o->ord_end_day)->format('d/m') }}@endif
                                · <span class="tag tag--{{ $sessionTag($o->ord_session) }}">{{ $o->ord_session ?: 'Cả ngày' }}</span>
                                · {{ number_format($o->order_quantity, 0, ',', '.') }} cas
                            </div>
                            <div class="pend__meta">{{ $o->ord_select }} · #{{ $o->id }}</div>
                            @php
                                $kmText = $o->from_base ? '~' . number_format($o->from_base['km'], 0, ',', '.') . ' km ' . ($o->from_base['source'] === 'don' ? 'theo đơn' : 'ước tính') : null;
                            @endphp
                            @if ($o->area || $kmText)
                                <div class="pend__area" title="Địa chỉ: {{ $o->customer_address ?: 'không có' }}{{ $o->accountant_distance ? ' · Km Sale nhập: ' . $o->accountant_distance : '' }}">
                                    <i class="fas fa-map-marker-alt"></i> {{ $o->area ?? 'Chưa rõ khu vực' }}@if ($kmText) · {{ $kmText }}@endif
                                </div>
                            @else
                                <div class="pend__area unknown" title="{{ $o->customer_address ?: 'Không có địa chỉ' }}">
                                    <i class="fas fa-map-marker-alt"></i> Chưa rõ khu vực: nên nhập số km khi lên đơn hoặc ghi rõ quận/tỉnh
                                </div>
                            @endif
                            @if (\App\Services\Schedule\ScheduleBoard::needsTwoKtv($o->order_quantity))
                                <div class="pend__warn">Từ {{ config('schedule.two_ktv_from_cas') }} cas: cần 2 KTV</div>
                            @endif
                            @if ($o->hold)
                                <div class="pend__hold"><i class="fas fa-thumbtack"></i> Đã giữ chỗ {{ \Carbon\Carbon::parse($o->hold['start'])->format('d/m') }}@if ($o->hold['end'] !== $o->hold['start'])–{{ \Carbon\Carbon::parse($o->hold['end'])->format('d/m') }}@endif · {{ $o->hold['car_label'] }}{{ $o->hold['same_dates'] ? '' : ' (khác ngày đơn)' }}</div>
                            @endif
                            <div class="pend__hint"><i class="fas fa-hand-pointer"></i>
                                {{ $group === 'past' ? 'Bấm để ghi bổ sung xe / người' : ($o->hold ? 'Bấm để xếp vào lịch giữ chỗ' : 'Bấm để xếp thủ công') }}</div>
                        </div>
