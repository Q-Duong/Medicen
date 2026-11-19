<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Cảnh báo tài chính</title></head>
<body style="margin:0;padding:20px;background:#f4f2eb;font-family:'Segoe UI',Arial,sans-serif;color:#1f1e1d;">
<div style="max-width:680px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2ded3;">
    @include('emails.finance._layout_head', ['heading' => 'Cảnh báo tài chính tuần ' . $today->weekOfYear, 'sub' => 'Kiểm tra lúc ' . now()->format('H:i d/m/Y')])
    <div style="padding:22px 24px;font-size:14.5px;line-height:1.6;">
        @if (empty($alerts))
            <div style="padding:14px 16px;border-radius:10px;background:#e6f5ec;color:#18794e;font-weight:600;">✅ Không có cảnh báo nào trong tuần này.</div>
        @else
            <p style="margin:0 0 14px;">Có <b>{{ count($alerts) }} điểm cần chú ý</b>:</p>
            @foreach ($alerts as $a)
                @php($c = ['bad' => ['#b42318', '#fdf1ef', '🔴'], 'warn' => ['#a15c00', '#fff8e6', '🟠'], 'info' => ['#2f6fd1', '#ebf3fd', 'ℹ️']][$a['level']] ?? ['#4a4843', '#f4f2eb', '•'])
                <div style="margin:0 0 12px;padding:12px 14px;border-left:4px solid {{ $c[0] }};background:{{ $c[1] }};border-radius:0 10px 10px 0;">
                    <div style="font-weight:700;color:{{ $c[0] }};">{{ $c[2] }} {{ $a['title'] }}</div>
                    <div style="margin-top:2px;">{{ $a['detail'] }}</div>
                    @if (!empty($a['lines']))
                        <ul style="margin:6px 0 0;padding-left:18px;color:#4a4843;font-size:13.5px;">
                            @foreach ($a['lines'] as $l)<li>{{ $l }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        @endif

        <div style="margin-top:20px;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.4px;color:#73716c;">Số liệu chính</div>
        <table style="width:100%;border-collapse:collapse;margin-top:6px;font-size:14px;">
            @foreach ($overview as $k => $v)
                <tr>
                    <td style="padding:7px 0;border-bottom:1px solid #eeebe3;color:#4a4843;">{{ $k }}</td>
                    <td style="padding:7px 0;border-bottom:1px solid #eeebe3;text-align:right;font-weight:600;">{{ $v }}</td>
                </tr>
            @endforeach
        </table>

        <div style="margin-top:22px;">
            <a href="{{ $url }}" style="display:inline-block;padding:10px 18px;border-radius:9px;background:#c96442;color:#fff;text-decoration:none;font-weight:600;">Mở trang Phân tích tài chính</a>
        </div>
        <p style="margin:18px 0 0;color:#a3a19b;font-size:12px;">Email tự động gửi sáng thứ Hai hằng tuần. Muốn đổi ngưỡng cảnh báo (mức tiền, số ngày) thì báo bộ phận kỹ thuật.</p>
    </div>
</div>
</body>
</html>
