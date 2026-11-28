<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Trao đổi khách ngày {{ $day->format('d/m') }}</title></head>
<body style="margin:0;padding:20px;background:#f4f2eb;font-family:'Segoe UI',Arial,sans-serif;color:#1f1e1d;">
<div style="max-width:680px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2ded3;">
    @include('emails.finance._layout_head', ['heading' => 'Trao đổi khách ngày ' . $day->format('d/m/Y'), 'sub' => ($stats['nhat_ky'] ?? 0) . ' trao đổi với khách · ' . ($stats['viec_xong'] ?? 0) . ' việc xong · ' . ($stats['qua_han'] ?? 0) . ' việc quá hạn'])
    <div style="padding:22px 24px;font-size:14.5px;line-height:1.6;">
        {!! $html !!}
        <div style="margin-top:22px;">
            <a href="{{ $url }}" style="display:inline-block;padding:10px 18px;border-radius:9px;background:#c96442;color:#fff;text-decoration:none;font-weight:600;">Mở Nhật ký trao đổi khách</a>
        </div>
        <p style="margin:18px 0 0;color:#a3a19b;font-size:12px;">Tóm tắt do AI viết từ nhật ký trao đổi khách ghi trên web (đoạn chat / ảnh chụp / ghi chú cuộc gọi) và thao tác trên web. Tài liệu nội bộ.</p>
    </div>
</div>
</body>
</html>
