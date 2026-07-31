<div style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333333; max-width: 600px; margin: 0 auto; border: 1px solid #e2e6ea; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); background-color: #ffffff;">
    
    <h2 style="color: #2c3e50; text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-top: 0; text-transform: uppercase; font-size: 20px;">
        Cập nhật Báo cáo Thu Chi Tháng {{ $report->month }}/{{ $report->year }}
    </h2>
    
    <p style="font-size: 15px;">Hệ thống vừa ghi nhận một thay đổi chi tiết như sau:</p>
    
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px;">
        <tr>
            <td style="padding: 12px; border: 1px solid #eeeeee; font-weight: bold; width: 35%; background: #f8f9fa; color: #2c3e50;">Thao tác:</td>
            <td style="padding: 12px; border: 1px solid #eeeeee; color: {{ isset($item['_delete']) && $item['_delete'] == 1 ? '#c0392b' : '#27ae60' }}; font-weight: bold; font-size: 15px;">
                {{ isset($item['_delete']) && $item['_delete'] == 1 ? 'ĐÃ XÓA BẢN GHI' : 'VỪA LƯU CẬP NHẬT' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 12px; border: 1px solid #eeeeee; font-weight: bold; background: #f8f9fa; color: #2c3e50;">Nhóm:</td>
            <td style="padding: 12px; border: 1px solid #eeeeee;">
                <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; background-color: {{ $item['type'] == 'thu' ? '#dff9fb' : '#ffcccc' }}; color: {{ $item['type'] == 'thu' ? '#22a6b3' : '#eb4d4b' }}; margin-right: 5px;">
                    {{ $item['type'] == 'thu' ? 'THU' : 'CHI' }}
                </span>
                <strong>{{ $item['summary_group'] }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px; border: 1px solid #eeeeee; font-weight: bold; background: #f8f9fa; color: #2c3e50;">Tên hạng mục:</td>
            <td style="padding: 12px; border: 1px solid #eeeeee; font-weight: bold; color: #333333;">{{ $item['name'] }}</td>
        </tr>
        <tr>
            <td style="padding: 12px; border: 1px solid #eeeeee; font-weight: bold; background: #f8f9fa; color: #2c3e50;">Dự kiến:</td>
            <td style="padding: 12px; border: 1px solid #eeeeee;">
                Số tiền: <strong>{{ number_format($item['estimated_amount'], 0, ',', '.') }} ₫</strong><br>
                Ngày: <em style="color: #7f8c8d;">{{ !empty($item['expected_date']) ? \Carbon\Carbon::parse($item['expected_date'])->format('d/m/Y') : 'Không có' }}</em>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px; border: 1px solid #eeeeee; font-weight: bold; background: #f8f9fa; color: #2c3e50;">Thực tế (Đã thu/chi):</td>
            <td style="padding: 12px; border: 1px solid #eeeeee;">
                Số tiền: <strong style="color: #27ae60;">{{ number_format($item['actual_amount'], 0, ',', '.') }} ₫</strong><br>
                Ngày: <em style="color: #7f8c8d;">{{ !empty($item['actual_date']) ? \Carbon\Carbon::parse($item['actual_date'])->format('H:i - d/m/Y') : 'Không có' }}</em>
            </td>
        </tr>
    </table>

    <!-- NÚT TRUY CẬP NHANH -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ url('/admin/reports?month=' . $report->month . '&year=' . $report->year) }}" style="display: inline-block; padding: 12px 25px; background-color: #3498db; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 5px; font-size: 14px;">Truy cập Báo cáo ngay</a>
    </div>

    <p style="text-align: center; margin-top: 25px; font-size: 12px; color: #7f8c8d; border-top: 1px solid #eeeeee; padding-top: 15px;">
        Đây là email tự động từ hệ thống Medicen, vui lòng không trả lời.<br>
        Thời gian gửi: {{ \Carbon\Carbon::now()->format('H:i - d/m/Y') }}
    </p>
    
</div>