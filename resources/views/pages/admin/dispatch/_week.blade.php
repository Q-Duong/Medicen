{{-- Lịch xe + bảng nhân sự của 1 tuần. Dùng trong index và trả về riêng cho JS (DispatchController::week) --}}
@php
    $prev = $monday->copy()->subWeek()->format('Y-m-d');
    $next = $monday->copy()->addWeek()->format('Y-m-d');
@endphp
        <div class="dp-card">
            <div class="dp-head" style="margin-bottom:10px">
                <p class="sec-title" style="margin:0">Lịch xe</p>
                <a class="btn-g" href="{{ route('dispatch.index', ['tuan' => $prev]) }}" data-week="{{ $prev }}" aria-label="Tuần trước"><i class="fas fa-chevron-left"></i></a>
                <span class="week" id="week-label" data-monday="{{ $monday->format('Y-m-d') }}">Tuần {{ $monday->format('d/m') }} – {{ $monday->copy()->addDays(6)->format('d/m/Y') }}</span>
                <a class="btn-g" href="{{ route('dispatch.index', ['tuan' => $next]) }}" data-week="{{ $next }}" aria-label="Tuần sau"><i class="fas fa-chevron-right"></i></a>
                <a class="btn-g" href="{{ route('dispatch.index') }}" data-week="{{ today()->format('Y-m-d') }}">Tuần này</a>
                <small style="color:var(--muted)">mỗi ngày chia 2 ô: sáng | chiều</small>
                <span class="grow"></span>
                <button type="button" class="btn-g" data-week-image="{{ $monday->format('Y-m-d') }}" title="Tải ảnh PNG lịch xe cả tuần (đủ đơn vị, buổi, số cas, TX, KTV)"><i class="far fa-image"></i> Tải ảnh tuần</button>
            </div>
            <div class="grid-wrap">
                <table class="wk" id="car-grid">
                    <thead>
                        <tr>
                            <th class="row-h" rowspan="2">Xe</th>
                            @foreach ($grid['days'] as $day)
                                <th colspan="2" class="day-end">{{ $day['label'] }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($grid['days'] as $day)
                                <th class="half">S</th><th class="half day-end">C</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grid['cars'] as $car)
                            <tr class="{{ $car['hired'] ? 'hired' : '' }}">
                                <th class="row-h">{{ $car['label'] }}</th>
                                @foreach ($grid['days'] as $day)
                                    @php
                                        $s = $car['cells'][$day['date'] . '|S'] ?? [];
                                        $c = $car['cells'][$day['date'] . '|C'] ?? [];
                                        $ids = fn ($items) => collect($items)->pluck('order_id')->sort()->values()->all();
                                        $merged = $s && $ids($s) === $ids($c);
                                        $halves = $merged ? ['SC' => $s] : ['S' => $s, 'C' => $c];
                                    @endphp
                                    @foreach ($halves as $half => $items)
                                        @php
                                            // Xe nghỉ bảo trì: hiện riêng, kèm cảnh báo nếu vẫn còn đơn / giữ chỗ trùng
                                            $maintItems = collect($items)->filter(fn ($i) => !empty($i['maintenance_id']));
                                            $items = collect($items)->filter(fn ($i) => empty($i['maintenance_id']))->values()->all();
                                            // Nhiều đơn cùng đơn vị = chạy chung, khác đơn vị = trùng lịch
                                            $byUnit = collect($items)->groupBy('unit_id');
                                            $clash = $byUnit->count() > 1 && !$car['hired'];
                                            $orderItems = collect($items)->filter(fn ($i) => empty($i['hold_id']));
                                            $holdItems = collect($items)->filter(fn ($i) => !empty($i['hold_id']));
                                            $onlyHold = $orderItems->isEmpty() && $holdItems->isNotEmpty();
                                            // Đơn phụ thu: ô viền tím (toàn phụ thu thì nền tím)
                                            $surCount = $orderItems->where('surcharge', true)->count();
                                            $surClass = $surCount ? ($surCount === $orderItems->count() ? 'slot--sur' : 'slot--has-sur') : '';
                                            // Đơn đã hoàn thành (kế toán đã chốt): ô màu xanh như nhãn "Hoàn thành"; một số đơn trong ô đã hoàn thành thì viền xanh
                                            $doneCount = $orderItems->where('closed', true)->count();
                                            $doneClass = $doneCount ? ($doneCount === $orderItems->count() ? 'slot--done' : 'slot--has-done') : '';
                                            $first = $orderItems->first() ?? $holdItems->first();
                                            $tip = collect($items)->map(fn ($i) => !empty($i['hold_id'])
                                                ? 'Giữ chỗ: ' . $i['unit'] . ($i['quantity'] ? ' · ~' . $i['quantity'] . ' cas' : '') . ($i['driver'] ? ' · TX ' . $i['driver'] : '') . ($i['ktv'] ? ' · KTV ' . implode(', ', $i['ktv']) : '') . ($i['note'] ? ' · ' . $i['note'] : '')
                                                : $i['unit'] . ' #' . $i['order_id'] . (!empty($i['closed']) ? ' (ĐÃ HOÀN THÀNH)' : '') . (!empty($i['surcharge']) ? ' (PHỤ THU)' : '') . (!empty($i['group']) ? ' · nhóm ' . $i['group'] : '') . ' · ' . $i['quantity'] . ' cas · TX ' . $i['driver'] . ' · KTV ' . implode(', ', $i['ktv']))->join("\n");
                                        @endphp
                                        <td @if ($merged) colspan="2" @else data-car="{{ $car['key'] }}" data-key="{{ $day['date'] }}|{{ $half }}" @endif
                                            class="{{ $day['weekend'] ? 'weekend' : '' }} {{ $half !== 'S' ? 'day-end' : '' }}">
                                            @if ($maintItems->isNotEmpty())
                                                <div class="slot slot--maint" role="button" tabindex="0" data-maint="{{ $maintItems->first()['maintenance_id'] }}"
                                                    title="Xe nghỉ bảo trì{{ $maintItems->first()['note'] ? ': ' . $maintItems->first()['note'] : '' }}{{ $items ? "\nVẫn còn lịch: " . collect($items)->pluck('unit')->unique()->join(', ') . ' — cần đổi xe' : '' }}">
                                                    <b><i class="fas fa-tools"></i> Bảo trì</b>
                                                    @if ($items)
                                                        <span class="slot__warn"><i class="fas fa-exclamation-triangle"></i> {{ collect($items)->pluck('unit')->unique()->join(', ') }}</span>
                                                    @else
                                                        <span>{{ $maintItems->first()['note'] ?: 'Xe nghỉ' }}</span>
                                                    @endif
                                                </div>
                                            @elseif ($items)
                                                <div class="slot slot--click {{ $clash ? 'slot--clash' : ($onlyHold ? 'slot--hold' : 'slot--bk') }} {{ $surClass }} {{ $clash ? '' : $doneClass }}" role="button" tabindex="0"
                                                    @if ($orderItems->isNotEmpty()) data-orders="{{ $orderItems->pluck('order_id')->unique()->join(',') }}" @endif
                                                    @if ($onlyHold) data-hold="{{ $holdItems->first()['hold_id'] }}" @endif
                                                    title="{{ $clash ? 'TRÙNG LỊCH khác đơn vị' . "\n" : '' }}{{ $tip }}{{ "\n" }}{{ $onlyHold ? 'Bấm để xếp đơn vào chỗ này hoặc xóa giữ chỗ' : ($doneClass === 'slot--done' ? 'Đơn đã hoàn thành, chỉ xem (không sửa lịch được)' : 'Bấm để sửa xe / nhân sự') }}">
                                                    @if ($clash)
                                                        <b><i class="fas fa-exclamation-triangle"></i> Trùng {{ $byUnit->count() }} đơn vị</b>
                                                        <span>{{ $byUnit->map(fn ($g) => $g[0]['unit'])->join(', ') }}</span>
                                                    @elseif ($onlyHold)
                                                        <b><i class="fas fa-thumbtack"></i> {{ $first['unit'] }}</b>
                                                        <span>Giữ chỗ{{ $first['quantity'] ? ' · ~' . number_format($first['quantity'], 0, ',', '.') . ' cas' : '' }}</span>
                                                    @else
                                                        <b>@if ($doneClass === 'slot--done')<i class="fas fa-check-circle slot__done"></i> @endif @if ($surCount)<em class="slot__sur">PT</em>@endif{{ $first['unit'] }}</b>
                                                        <span>@if ($orderItems->count() > 1)<em class="slot__n">{{ $orderItems->count() }} đơn</em> @endif{{ number_format($orderItems->sum('quantity'), 0, ',', '.') }} cas · {{ $first['driver'] }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="slot slot--empty" role="button" tabindex="0" title="Bấm để giữ chỗ (khách đặt trước, chưa lên đơn)"></div>
                                            @endif
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dp-card">
            @php
                $monthLabel = $grid['month']->format('m/Y');
                $maxCases = max(1, collect($grid['staff'])->max(fn ($p) => $p['cases']['done'] + $p['cases']['planned']));
                $groups = collect($grid['staff'])->groupBy('role');
            @endphp
            <p class="sec-title">Nhân sự trống / bận
                <span class="legend" style="margin-left:6px">
                    <span class="key"><i style="background:#e9f6f0;border:1px solid #bfe3d3"></i> Trống</span>
                    <span class="key"><i style="background:#8a877f"></i> Đã có lịch</span>
                    <span class="key"><i style="background:#ef9f27"></i> Nghỉ</span>
                    <span>Mỗi ngày: nửa trái = sáng, nửa phải = chiều</span>
                </span>
            </p>
            <div class="grid-wrap">
                <table class="ppl">
                    <thead>
                        <tr>
                            <th class="name">Nhân sự</th>
                            <th class="cases" title="Đã chụp: đơn đã nhập số ca thực tế (order_quantity_draft). Dự kiến: đơn đã xếp chưa nhập số thực tế. Bộ phận 2 tư thế tính x2.">Số ca tháng {{ $monthLabel }}</th>
                            @foreach ($grid['days'] as $day)
                                <th>{{ $day['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['TX' => 'Tài xế', 'KTV' => 'Kỹ thuật viên'] as $role => $roleLabel)
                            @if ($groups->has($role))
                                <tr class="grp"><td colspan="{{ count($grid['days']) + 2 }}">{{ $roleLabel }}</td></tr>
                                @foreach ($groups[$role] as $person)
                                    @php
                                        $total = $person['cases']['done'] + $person['cases']['planned'];
                                    @endphp
                                    <tr>
                                        <td class="name" title="{{ $person['certificate_note'] }}">
                                            {{ $person['name'] }}@if ($person['certificate'])<span class="cchn">CCHN</span>@endif
                                            <small>{{ $person['week_halves'] }} buổi có lịch tuần này</small>
                                        </td>
                                        <td class="cases" title="Đã chụp {{ number_format($person['cases']['done'], 0, ',', '.') }} ca, dự kiến thêm {{ number_format($person['cases']['planned'], 0, ',', '.') }} ca">
                                            <b>{{ number_format($person['cases']['done'], 0, ',', '.') }}</b> ca
                                            @if ($person['cases']['planned'])
                                                <small>+{{ number_format($person['cases']['planned'], 0, ',', '.') }} dự kiến</small>
                                            @endif
                                            <div class="meter"><i style="width: {{ round($total / $maxCases * 100) }}%"></i></div>
                                        </td>
                                        @foreach ($grid['days'] as $day)
                                            @php
                                                [$sState, $sWhy] = $person['cells'][$day['date'] . '|S'];
                                                [$cState, $cWhy] = $person['cells'][$day['date'] . '|C'];
                                                $full = $sState === $cState && $sWhy === $cWhy && $sState !== 'free';
                                            @endphp
                                            <td>
                                                <div class="bar {{ $full ? 'full' : '' }}">
                                                    <span class="{{ $sState }}" title="Sáng {{ $day['label'] }}: {{ $sWhy ?: 'Trống' }}">{{ $sState === 'leave' ? 'Nghỉ' : '' }}</span>
                                                    <span class="{{ $cState }}" title="Chiều {{ $day['label'] }}: {{ $cWhy ?: 'Trống' }}">{{ $cState === 'leave' && !$full ? 'Nghỉ' : '' }}</span>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

