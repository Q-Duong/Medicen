<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc xếp lịch xe</title>
</head>
@php
    $dows = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    $dayLabel = function ($date) use ($today, $dows) {
        $d = \Carbon\Carbon::parse($date);
        $diff = $today->diffInDays($d, false);
        $rel = $diff === 0 ? 'Hôm nay' : ($diff === 1 ? 'Ngày mai' : ($diff === 2 ? 'Ngày kia' : ''));

        return trim($rel . ' ' . $dows[$d->dayOfWeek] . ' ' . $d->format('d/m/Y'));
    };
    $th = 'padding:8px 10px;text-align:left;font-size:12px;color:#73716c;font-weight:600;border-bottom:1px solid #e7e4dc;background:#faf9f5;';
    $td = 'padding:9px 10px;font-size:14px;color:#1f1e1d;border-bottom:1px solid #f0eee8;vertical-align:top;';
@endphp
<body style="font-family:'Segoe UI',Arial,sans-serif;background:#f4f3ef;margin:0;padding:20px;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e7e4dc;">
        <div style="padding:22px 24px;background:#1f1e1d;color:#ffffff;">
            <div style="font-size:13px;opacity:.75;">Medicen · Xếp lịch xe</div>
            <div style="font-size:20px;font-weight:600;margin-top:4px;">Nhắc xếp lịch ngày {{ $today->format('d/m/Y') }}</div>
        </div>
        <div style="padding:22px 24px;color:#1f1e1d;line-height:1.55;font-size:14.5px;">
            @if ($count)
                <div style="padding:12px 14px;border-radius:12px;background:#fcebeb;color:#a32d2d;margin-bottom:16px;">
                    <b>{{ $count }} đơn</b> chụp từ hôm nay đến {{ $until->format('d/m') }} <b>chưa được xếp xe</b>.
                </div>
                @foreach ($orders as $date => $list)
                    <div style="font-weight:600;margin:14px 0 6px;">{{ $dayLabel($date) }} · {{ $list->count() }} đơn</div>
                    <table style="width:100%;border-collapse:collapse;border:1px solid #e7e4dc;border-radius:10px;">
                        <tr><th style="{{ $th }}">Đơn vị</th><th style="{{ $th }}">Buổi</th><th style="{{ $th }}">Số cas</th><th style="{{ $th }}">Dịch vụ</th></tr>
                        @foreach ($list as $o)
                            <tr>
                                <td style="{{ $td }}"><b>{{ $o->unit }}</b>
                                    @if ((int) ($o->order_surcharge ?? 0) === 1)<span style="color:#5b2596;font-size:12px;"> · Phụ thu</span>@endif
                                    @if (!empty($o->group_code))<span style="color:#3c3489;font-size:12px;"> · Nhóm {{ $o->group_code }}</span>@endif
                                    <div style="font-size:12px;color:#73716c;">#{{ $o->id }}{{ $o->ord_cty_name && $o->ord_cty_name !== $o->unit ? ' · ' . $o->ord_cty_name : '' }}</div></td>
                                <td style="{{ $td }}">{{ $o->ord_session ?: 'Cả ngày' }}</td>
                                <td style="{{ $td }}">{{ number_format($o->order_quantity, 0, ',', '.') }}</td>
                                <td style="{{ $td }}">{{ $o->ord_select }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endforeach
            @endif

            @if ($holds->isNotEmpty())
                <div style="padding:12px 14px;border-radius:12px;background:#fdf3e1;color:#854f0b;margin:20px 0 10px;">
                    <b>{{ $holds->count() }} lịch giữ chỗ</b> sắp tới, khách <b>chưa lên đơn</b>: nhắc Sale lên đơn hoặc xóa giữ chỗ nếu khách không còn cần.
                </div>
                <table style="width:100%;border-collapse:collapse;border:1px solid #e7e4dc;">
                    <tr><th style="{{ $th }}">Ngày</th><th style="{{ $th }}">Khách / đơn vị</th><th style="{{ $th }}">Xe</th><th style="{{ $th }}">Ghi chú</th></tr>
                    @foreach ($holds as $h)
                        <tr>
                            <td style="{{ $td }}">{{ $h->hold_start->format('d/m') }}{{ $h->hold_end->eq($h->hold_start) ? '' : '–' . $h->hold_end->format('d/m') }}<div style="font-size:12px;color:#73716c;">{{ $h->hold_session }}</div></td>
                            <td style="{{ $td }}"><b>{{ $h->customer }}</b>@if ($h->expected_cas)<div style="font-size:12px;color:#73716c;">~{{ number_format($h->expected_cas, 0, ',', '.') }} cas</div>@endif</td>
                            <td style="{{ $td }}">{{ \App\Services\Schedule\ScheduleBoard::carLabel($h->car) }}</td>
                            <td style="{{ $td }}">{{ $h->note }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <div style="text-align:center;margin:26px 0 6px;">
                <a href="{{ $url }}" style="display:inline-block;padding:11px 22px;border-radius:12px;background:#c96442;color:#ffffff;text-decoration:none;font-weight:600;">Mở trang Xếp lịch xe</a>
            </div>
            <p style="font-size:12px;color:#a3a19b;margin:18px 0 0;">Email tự động gửi lúc {{ config('schedule.remind_at') }} hằng ngày khi có đơn sắp chạy chưa xếp xe hoặc giữ chỗ chưa lên đơn.</p>
        </div>
    </div>
</body>
</html>
