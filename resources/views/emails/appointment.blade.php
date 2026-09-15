<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo Đặt lịch khám mới</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f7; color: #333333; margin: 0; padding: 20px;">
    
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; border-top: 5px solid #0071e3;">
        <h2 style="color: #1d1d1f; margin-top: 0;">Thông Báo Có Khách Hàng Đặt Lịch Mới</h2>
        <p>Chào bộ phận CSKH,</p>
        <p>Hệ thống vừa ghi nhận một lượt đăng ký đặt lịch khám mới từ website với các thông tin sau:</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eeeeee; width: 130px;"><strong>Họ và tên:</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eeeeee;">{{ $appointmentData['name'] }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eeeeee;"><strong>Số điện thoại:</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eeeeee;">
                    <a href="tel:{{ $appointmentData['phone'] }}" style="color: #0071e3; font-weight: bold;">{{ $appointmentData['phone'] }}</a>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eeeeee;"><strong>Ngày khám:</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eeeeee; color: #e30000; font-weight: bold;">
                    {{ \Carbon\Carbon::parse($appointmentData['date'])->format('d/m/Y') }}
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; vertical-align: top;"><strong>Lời nhắn:</strong></td>
                <td style="padding: 10px; background-color: #f9f9fb; border-radius: 6px; font-style: italic;">
                    {{ $appointmentData['message'] ?? 'Không có lời nhắn' }}
                </td>
            </tr>
        </table>

        <p style="margin-top: 30px; font-size: 0.9em; color: #888888;">
            * Đây là email tự động gửi từ hệ thống website Medicen. Vui lòng liên hệ lại khách hàng sớm nhất có thể.
        </p>
    </div>

</body>
</html>