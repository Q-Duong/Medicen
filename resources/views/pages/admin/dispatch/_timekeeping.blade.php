{{-- Bảng chấm công 1 tháng: cùng cách tính và cùng số liệu với trang Chấm công / file Excel (App\Services\Schedule\Timekeeping) --}}
@php
    $m = $timekeeping['month'];
    $rows = $timekeeping['rows'];
    $prevMonth = $m->copy()->subMonth()->format('Y-m');
    $nextMonth = $m->copy()->addMonth()->format('Y-m');
    $num = fn ($v) => $v > 0 ? number_format($v, 0, ',', '.') : '–';
    $total = fn ($key) => $rows->sum($key);
@endphp
<div class="dp-head" style="margin-bottom:10px">
    <p class="sec-title" style="margin:0"><i class="far fa-calendar-check"></i> Chấm công</p>
    <button type="button" class="btn-g" data-month="{{ $prevMonth }}" aria-label="Tháng trước"><i class="fas fa-chevron-left"></i></button>
    <span class="week" id="tk-label" data-month-current="{{ $m->format('Y-m') }}">Tháng {{ $m->format('m/Y') }}</span>
    <button type="button" class="btn-g" data-month="{{ $nextMonth }}" aria-label="Tháng sau"><i class="fas fa-chevron-right"></i></button>
    <button type="button" class="btn-g" data-month="{{ today()->format('Y-m') }}">Tháng này</button>
    <span class="grow"></span>
    <form method="POST" action="{{ route('employee_timekeeping.export') }}" style="margin:0">
        @csrf
        <input type="hidden" name="month" value="{{ $m->format('F') }}">
        <input type="hidden" name="year" value="{{ $m->format('Y') }}">
        <button type="submit" class="btn-g"><i class="far fa-file-excel"></i> Xuất Excel</button>
    </form>
</div>

<p class="tk-note">
    Tính từ {{ number_format($timekeeping['orders'], 0, ',', '.') }} đơn đã xếp xe trong tháng (bỏ đơn phụ thu), giống file Excel chấm công.
    <b>Tổng số ca (TX)</b>: số ca × hệ số bộ phận (2 tư thế ×2, siêu âm không tính), chỉ đơn đã cập nhật số ca / đã xử lý.
    <b>Số ca KTV</b>: số ca thực tế KTV nhập. <b>Dự kiến</b>: đơn đã xếp nhưng chưa đủ điều kiện tính công (không có trong file Excel).
</p>

@if ($timekeeping['missing']->isNotEmpty())
    @php
        // Gộp các đơn cùng đơn vị, cùng ngày thành 1 dòng; cũ nhất lên đầu (cần chấm trước)
        $dows = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        $groupsMissing = $timekeeping['missing']
            ->groupBy(fn ($o) => $o->ord_start_day . '|' . ($o->unit ?: 'Đơn #' . $o->id))
            ->map(function ($g) use ($dows) {
                $o = $g->first();
                $day = \Carbon\Carbon::parse($o->ord_start_day);

                return [
                    'date' => $day,
                    'dow' => $dows[$day->dayOfWeek],
                    'ago' => $day->diffInDays(today()),
                    'unit' => $o->unit ?: 'Đơn #' . $o->id,
                    'count' => $g->count(),
                    'cars' => $g->pluck('car_name')->filter()->unique()->map(fn ($c) => \App\Services\Schedule\ScheduleBoard::carLabel($c))->join(', '),
                    'ktv' => $g->flatMap(fn ($x) => [trim((string) $x->car_ktv_name_1), trim((string) $x->car_ktv_name_2)])->filter()->unique()->join(', '),
                    'driver' => $g->pluck('car_driver_name')->map(fn ($d) => trim((string) $d))->filter()->unique()->join(', '),
                    'cases' => $g->sum('order_quantity'),
                    'ids' => $g->pluck('id')->take(20)->join(','),
                ];
            })
            ->sortBy(fn ($g) => $g['date']->format('Ymd') . $g['unit'])
            ->values();
        $showFirst = 6;
    @endphp
    {{-- Mặc định thu gọn, bấm tiêu đề để xổ danh sách (giống nhóm đơn chờ xếp lịch) --}}
    <details class="tk-missing">
        <summary class="tk-missing__head">
            <i class="fas fa-chevron-right tk-missing__chev"></i>
            <i class="fas fa-exclamation-triangle"></i>
            <b>{{ $timekeeping['missing']->count() }} đơn đã qua ngày chụp chưa nhập số ca thực tế</b>
            <span class="tk-missing__count">{{ $groupsMissing->count() }} lượt chụp</span>
            <span class="tk-missing__toggle"><span class="when-closed">Xem danh sách</span><span class="when-open">Thu gọn</span></span>
        </summary>
        <p class="tk-missing__sub">Chấm công KTV đang thiếu. Bấm "Nhập số ca" để chấm ngay tại đây, hoặc KTV nhập ở
            <a href="{{ route('schedules.technicians.index') }}" target="_blank" rel="noopener">trang lịch KTV</a>.</p>
        <div class="tk-mlist {{ $groupsMissing->count() > $showFirst ? 'is-collapsed' : '' }}">
            @foreach ($groupsMissing as $g)
                <button type="button" class="tk-mrow {{ $loop->index >= $showFirst ? 'is-extra' : '' }}" data-edit-orders="{{ $g['ids'] }}"
                    title="Mở Sửa lịch {{ $g['unit'] }} ngày {{ $g['date']->format('d/m') }} để nhập số ca thực tế">
                    <span class="tk-mrow__date"><b>{{ $g['date']->format('d/m') }}</b><small>{{ $g['dow'] }} · {{ $g['ago'] }} ngày trước</small></span>
                    <span class="tk-mrow__unit"><b>{{ $g['unit'] }}</b>@if ($g['count'] > 1)<em>{{ $g['count'] }} đơn</em>@endif
                        <small>{{ number_format($g['cases'], 0, ',', '.') }} cas dự kiến</small></span>
                    <span class="tk-mrow__team" title="{{ $g['cars'] }}{{ $g['driver'] ? ' · TX ' . $g['driver'] : '' }}&#10;KTV {{ $g['ktv'] }}">
                        <small>{{ $g['cars'] ?: 'Chưa rõ xe' }}{{ $g['driver'] ? ' · TX ' . $g['driver'] : '' }}</small>
                        <small>KTV {{ $g['ktv'] ?: '—' }}</small>
                    </span>
                    <span class="tk-mrow__go"><i class="fas fa-pen"></i> Nhập số ca</span>
                </button>
            @endforeach
        </div>
        @if ($groupsMissing->count() > $showFirst)
            <button type="button" class="tk-more" data-tk-more data-more="Xem thêm {{ $groupsMissing->count() - $showFirst }} lượt" data-less="Thu gọn">
                <i class="fas fa-chevron-down"></i> <span>Xem thêm {{ $groupsMissing->count() - $showFirst }} lượt</span>
            </button>
        @endif
    </details>
@endif

@if ($rows->isEmpty())
    <p class="empty-note">Tháng này chưa có đơn nào được xếp xe.</p>
@else
    <div class="grid-wrap">
        <table class="tk">
            <thead>
                <tr>
                    <th class="name">Nhân viên</th>
                    <th>Tổng số ca (TX)</th>
                    <th>Số ca KTV</th>
                    <th>Ca TX phụ KTV</th>
                    <th>Ở lại đêm</th>
                    <th>C.tác trên 250km</th>
                    <th class="tk-plan">Dự kiến</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td class="name">
                            <span class="tk-role {{ $r['is_driver'] ? 'tk-role--tx' : '' }}">{{ $r['is_driver'] ? 'TX' : 'KTV' }}</span>
                            {{ $r['nhan_vien'] }}
                        </td>
                        <td>{{ $num($r['tong_so_ca']) }}</td>
                        <td>{{ $num($r['so_ca_ktv']) }}</td>
                        <td>{{ $num($r['so_ca_tx_phu_ktv']) }}</td>
                        <td>{{ $num($r['o_lai_dem']) }}</td>
                        <td>{{ $num($r['cong_tac_tren_250']) }}</td>
                        <td class="tk-plan">{{ $r['du_kien'] > 0 ? '+' . number_format($r['du_kien'], 0, ',', '.') : '–' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="name">Tổng</td>
                    <td>{{ $num($total('tong_so_ca')) }}</td>
                    <td>{{ $num($total('so_ca_ktv')) }}</td>
                    <td>{{ $num($total('so_ca_tx_phu_ktv')) }}</td>
                    <td>{{ $num($total('o_lai_dem')) }}</td>
                    <td>{{ $num($total('cong_tac_tren_250')) }}</td>
                    <td class="tk-plan">{{ $total('du_kien') > 0 ? '+' . number_format($total('du_kien'), 0, ',', '.') : '–' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
