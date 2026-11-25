<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Báo cáo CFO tháng {{ $label }}</title>
    <style>
        @page { margin: 22mm 16mm 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; line-height: 1.5; color: #1f1e1d; }
        .head { border-bottom: 3px solid #c96442; padding-bottom: 8px; margin-bottom: 14px; }
        .head .org { font-size: 9px; letter-spacing: .5px; text-transform: uppercase; color: #73716c; }
        .head h1 { margin: 3px 0 0; font-size: 18px; }
        .head .sub { color: #73716c; font-size: 9.5px; }
        * { font-family: 'DejaVu Sans', sans-serif !important; }
        th, b, strong, h1, h2, h3, h4 { font-weight: bold !important; }
        table { page-break-inside: auto; font-size: 9.5px !important; }
        th, td { font-size: 9.5px !important; padding: 5px 7px !important; }
        tr { page-break-inside: avoid; }
        .foot { margin-top: 18px; padding-top: 8px; border-top: 1px solid #e2ded3; color: #8a877f; font-size: 8.5px; }
    </style>
</head>
<body>
    <div class="head">
        <div class="org">Medicen · Nam Khánh Linh · Tài liệu nội bộ</div>
        <h1>Báo cáo CFO tháng {{ $label }}</h1>
        <div class="sub">Trợ lý Kế toán (vai CFO) lập tự động lúc {{ now()->format('H:i d/m/Y') }}</div>
    </div>
    {!! $html !!}
    <div class="foot">Số liệu do trợ lý AI tổng hợp từ dữ liệu nội bộ; số ghi "ước tính" là tạm tính từ dữ liệu cũ. Biểu đồ xem trên web. Không chuyển tài liệu ra ngoài công ty.</div>
</body>
</html>
