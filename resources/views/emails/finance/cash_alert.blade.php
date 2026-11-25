@php
    $m = fn ($v) => number_format((int) $v, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Cảnh báo tiền gấp</title></head>
<body style="margin:0;padding:20px;background:#f4f2eb;font-family:'Segoe UI',Arial,sans-serif;color:#1f1e1d;">
<div style="max-width:680px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2ded3;">
    @include('emails.finance._layout_head', ['heading' => 'Cảnh báo tiền gấp', 'sub' => 'Dự báo dòng tiền lúc ' . now()->format('H:i d/m/Y') . ' · xét ' . $days . ' ngày tới'])
    <div style="padding:22px 24px;font-size:14.5px;line-height:1.6;">
        <div style="padding:12px 14px;border-left:4px solid #b42318;background:#fdf1ef;border-radius:0 10px 10px 0;">
            <b style="color:#b42318;">Số dư dự báo xuống {{ $m($low['cuoi_ky']) }} đ</b> vào cuối tuần {{ \Carbon\Carbon::parse($low['tu'])->format('d/m') }} – {{ \Carbon\Carbon::parse($low['den'])->format('d/m') }},
            dưới ngưỡng {{ $m($f['nguong_canh_bao']) }} đ. Bắt đầu thiếu từ tuần {{ \Carbon\Carbon::parse($first['tu'])->format('d/m') }}.
        </div>

        <table style="width:100%;border-collapse:collapse;margin:16px 0 6px;font-size:13.5px;">
            <tr>
                <th style="padding:7px 9px;border:1px solid #e2ded3;background:#f4f2eb;text-align:left;">Tuần</th>
                <th style="padding:7px 9px;border:1px solid #e2ded3;background:#f4f2eb;text-align:right;">Thu</th>
                <th style="padding:7px 9px;border:1px solid #e2ded3;background:#f4f2eb;text-align:right;">Chi</th>
                <th style="padding:7px 9px;border:1px solid #e2ded3;background:#f4f2eb;text-align:right;">Số dư cuối</th>
            </tr>
            @foreach ($weeks as $w)
                <tr style="{{ $w['canh_bao'] ? 'background:#fdf1ef;color:#b42318;' : '' }}">
                    <td style="padding:7px 9px;border:1px solid #e2ded3;">{{ \Carbon\Carbon::parse($w['tu'])->format('d/m') }} – {{ \Carbon\Carbon::parse($w['den'])->format('d/m') }}</td>
                    <td style="padding:7px 9px;border:1px solid #e2ded3;text-align:right;">{{ $m($w['tong_thu']) }}</td>
                    <td style="padding:7px 9px;border:1px solid #e2ded3;text-align:right;">{{ $m($w['tong_chi']) }}</td>
                    <td style="padding:7px 9px;border:1px solid #e2ded3;text-align:right;font-weight:600;">{{ $m($w['cuoi_ky']) }}</td>
                </tr>
            @endforeach
        </table>
        <div style="color:#73716c;font-size:12.5px;">Tiền đầu kỳ {{ $m($f['tien_dau_ky']) }} đ — {{ $f['nguon_tien_dau_ky'] }}</div>

        @if ($outs)
            <div style="margin-top:18px;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.4px;color:#73716c;">Khoản chi lớn sắp tới</div>
            <ul style="margin:6px 0 0;padding-left:18px;">
                @foreach ($outs as $k)<li>{{ $k['date'] }} · {{ $k['label'] }}: <b>{{ $m($k['amount']) }} đ</b></li>@endforeach
            </ul>
        @endif

        @if (!empty($f['khoan_chua_chot']['tong_thu']))
            <p style="margin:14px 0 0;font-size:13px;color:#4a4843;">Khoản thu theo thỏa thuận chưa có ngày (chưa tính vào dự báo): <b>{{ $m($f['khoan_chua_chot']['tong_thu']) }} đ</b> — nếu chốt được ngày thu thì số dư sẽ cao hơn.</p>
        @endif

        <p style="margin:16px 0 0;font-size:13.5px;">Việc nên làm ngay: đốc thu nợ quá hạn, xuất hóa đơn các đơn đã chụp, cân nhắc lùi khoản chi chưa gấp hoặc chuẩn bị nguồn vay.</p>
        <div style="margin-top:18px;">
            <a href="{{ $url }}" style="display:inline-block;padding:10px 18px;border-radius:9px;background:#c96442;color:#fff;text-decoration:none;font-weight:600;">Xem dòng tiền 13 tuần</a>
        </div>
        <p style="margin:18px 0 0;color:#a3a19b;font-size:12px;">Email này chỉ gửi khi số dư dự báo trong {{ $days }} ngày tới dưới ngưỡng. Dự báo dựa trên số dư đầu kỳ, nợ phải thu, lịch trả vay và Báo cáo thu chi; số tạm tính, kiểm tra lại trước khi quyết định.</p>
    </div>
</div>
</body>
</html>
