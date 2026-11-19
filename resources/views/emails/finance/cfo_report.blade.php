<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Báo cáo CFO tháng {{ $label }}</title></head>
<body style="margin:0;padding:20px;background:#f4f2eb;font-family:'Segoe UI',Arial,sans-serif;color:#1f1e1d;">
<div style="max-width:720px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2ded3;">
    @include('emails.finance._layout_head', ['heading' => 'Báo cáo CFO tháng ' . $label, 'sub' => 'Trợ lý Kế toán (vai CFO) lập tự động lúc ' . now()->format('H:i d/m/Y')])
    <div style="padding:22px 24px;font-size:14.5px;line-height:1.6;">
        {!! $html !!}
        <div style="margin-top:22px;">
            <a href="{{ $url }}" style="display:inline-block;padding:10px 18px;border-radius:9px;background:#c96442;color:#fff;text-decoration:none;font-weight:600;">Xem trên web (biểu đồ, xuất PDF, hỏi tiếp)</a>
        </div>
        <p style="margin:18px 0 0;color:#a3a19b;font-size:12px;">Báo cáo được lưu trong Trợ lý Kế toán của tài khoản {{ $user }}. Số liệu do trợ lý AI tổng hợp từ dữ liệu nội bộ, kiểm tra lại số quan trọng trước khi ra quyết định. Tài liệu nội bộ, không chuyển ra ngoài.</p>
    </div>
</div>
</body>
</html>
