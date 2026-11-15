{{-- Bảng lịch xe cả tuần để xuất ảnh PNG (DispatchController::weekSheet). Tự chứa style để chụp ảnh đúng. --}}
@php
    $sessionTag = ['Sáng' => 'S', 'Chiều' => 'C', 'Cả ngày' => 'F'];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp
<div class="ws" id="week-sheet">
    {{-- Giao diện ở assets/css/support/org-dispatch.css (mục Ảnh lịch xe cả tuần) --}}
    <div class="ws__head">
        <h1 class="ws__title">Lịch xe tuần {{ $monday->format('d/m') }} – {{ $sunday->format('d/m/Y') }}</h1>
        <div class="ws__meta">Medicen · xuất lúc {{ now()->format('H:i d/m/Y') }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th class="car">Xe</th>
                @foreach ($days as $day)
                    <th class="{{ $day['weekend'] ? 'sun' : '' }}">{{ $day['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <th>{{ $row['label'] }}@if ($row['hired'])<small>xe thuê</small>@endif</th>
                    @foreach ($days as $day)
                        @php
                            $entries = $row['days'][$day['date']] ?? [];
                            $orders = collect($entries)->filter(fn ($e) => empty($e['hold_id']) && empty($e['maintenance_id']));
                        @endphp
                        <td class="{{ $day['weekend'] ? 'sun' : '' }}">
                            @foreach ($entries as $e)
                                @if (!empty($e['maintenance_id']))
                                    <div class="e e--maint"><span class="t t--{{ $sessionTag[$e['session']] }}">{{ $e['session'] }}</span><b style="display:inline">Bảo trì</b>
                                        @if (!empty($e['note']))<div class="p">{{ $e['note'] }}</div>@endif</div>
                                @elseif (!empty($e['hold_id']))
                                    <div class="e e--hold"><span class="t t--{{ $sessionTag[$e['session']] }}">{{ $e['session'] }}</span>Giữ chỗ
                                        <b>{{ $e['unit'] }}</b>
                                        @if ($e['quantity'])~{{ $fmt($e['quantity']) }} cas · @endif{{ $e['driver'] ? 'TX ' . $e['driver'] : '' }}{{ $e['ktv'] ? ' · KTV ' . implode(', ', $e['ktv']) : '' }}</div>
                                @else
                                    <div class="e {{ !empty($e['surcharge']) ? 'e--sur' : '' }}">
                                        <span class="t t--{{ $sessionTag[$e['session']] }}">{{ $e['session'] }}</span>#{{ $e['order_id'] }}@if (!empty($e['group']))<span class="g">{{ $e['group'] }}</span>@endif
                                        <b>{{ $e['unit'] }}</b>
                                        <div>{{ $fmt($e['quantity']) }} cas</div>
                                        <div class="p">TX {{ $e['driver'] ?: '—' }}</div>
                                        <div class="p">KTV {{ $e['ktv'] ? implode(', ', $e['ktv']) : '—' }}</div>
                                    </div>
                                @endif
                            @endforeach
                            @if ($orders->count() > 1)
                                <div class="tot">{{ $orders->count() }} đơn · {{ $fmt($orders->sum('quantity')) }} cas</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="ws__foot">
        <div><b>Nghỉ trong tuần:</b>
            @forelse ($leaves as $l)
                {{ $l->staff?->staff_name ?? '?' }} ({{ $l->leave_start->format('d/m') }}{{ $l->leave_end->eq($l->leave_start) ? '' : '–' . $l->leave_end->format('d/m') }}{{ $l->leave_session !== 'Cả ngày' ? ' ' . $l->leave_session : '' }}){{ $loop->last ? '' : ', ' }}
            @empty
                không có
            @endforelse
        </div>
        <div class="ws__legend">Không gồm đơn phụ thu · nhãn tím = mã nhóm đi chung · viền cam = giữ chỗ · sọc xám = xe bảo trì</div>
    </div>
</div>
