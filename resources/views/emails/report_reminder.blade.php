<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cảnh báo Thu Chi</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px;">
    
    <div style="max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <div style="background-color: #2c3e50; color: #ffffff; padding: 25px 20px; text-align: center; border-bottom: 4px solid #e74c3c;">
            <h2 style="margin: 0; font-size: 22px; letter-spacing: 1px;">BÁO CÁO NHẮC NHỞ HÀNG NGÀY</h2>
        </div>

        <div style="padding: 30px 25px; color: #333333; line-height: 1.6; font-size: 15px;">
            <p style="margin-top: 0;">Chào bạn,</p>
            
            <div style="background-color: #f8f9fa; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 25px; border-radius: 0 5px 5px 0;">
                Hệ thống <strong>Medicen</strong> ghi nhận hiện có <strong>{{ $items->count() }} khoản mục</strong> đang cần bạn xử lý (bao gồm các khoản đã quá hạn hoặc sắp đến hạn trong 5 ngày tới).
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">
                <thead>
                    <tr>
                        <th width="45%" style="padding: 12px 15px; border-bottom: 1px solid #eeeeee; text-align: left; background-color: #f1f2f6; font-weight: 600; color: #2d3436; text-transform: uppercase; font-size: 13px;">Tên Hạng Mục</th>
                        <th width="25%" style="padding: 12px 15px; border-bottom: 1px solid #eeeeee; text-align: right; background-color: #f1f2f6; font-weight: 600; color: #2d3436; text-transform: uppercase; font-size: 13px;">Số Tiền</th>
                        <th width="30%" style="padding: 12px 15px; border-bottom: 1px solid #eeeeee; text-align: center; background-color: #f1f2f6; font-weight: 600; color: #2d3436; text-transform: uppercase; font-size: 13px;">Tình Trạng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php
                            $expectedDate = \Carbon\Carbon::parse($item->expected_date);
                            $diff = $today->diffInDays($expectedDate, false);
                        @endphp
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #eeeeee; text-align: left;">
                                <strong style="color: #2c3e50;">{{ $item->name }}</strong><br>
                                <span style="font-size: 12px; color: #7f8c8d;">Nhóm: {{ $item->summary_group }}</span>
                            </td>
                            
                            <td style="padding: 12px 15px; border-bottom: 1px solid #eeeeee; text-align: right; font-weight: bold; color: #2c3e50;">
                                {{ number_format($item->estimated_amount, 0, ',', '.') }} ₫
                            </td>
                            
                            <td style="padding: 12px 15px; border-bottom: 1px solid #eeeeee; text-align: center;">
                                @if($diff < 0)
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; text-align: center; min-width: 90px; background-color: #fde1e1; color: #c0392b; border: 1px solid #fadbd8;">Quá hạn {{ abs($diff) }} ngày</span>
                                @elseif($diff == 0)
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; text-align: center; min-width: 90px; background-color: #fef5e7; color: #d35400; border: 1px solid #fdebd0;">Hạn hôm nay</span>
                                @else
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; text-align: center; min-width: 90px; background-color: #fef5e7; color: #d35400; border: 1px solid #fdebd0;">Còn {{ $diff }} ngày</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="text-align: center; margin-top: 35px; margin-bottom: 10px;">
                <a href="{{ url('/admin/reports?month=' . $today->month . '&year=' . $today->year) }}" style="display: inline-block; padding: 12px 25px; background-color: #3498db; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 5px;">Truy cập Hệ thống ngay</a>
            </div>
        </div>

        <div style="background-color: #f1f2f6; color: #7f8c8d; text-align: center; padding: 20px; font-size: 13px; border-top: 1px solid #e2e6ea;">
            <p style="margin: 0;">Đây là email tự động từ hệ thống Medicen, vui lòng không trả lời email này.</p>
            <p style="margin: 5px 0 0 0;">Được tạo lúc: {{ \Carbon\Carbon::now()->format('H:i - d/m/Y') }}</p>
        </div>
        
    </div>

</body>
</html>