{{-- Phần đầu email tài chính (dùng chung cho cảnh báo tuần và báo cáo CFO). Biến: $heading, $sub --}}
<div style="background:#1f1e1d;color:#fff;padding:22px 24px;border-bottom:4px solid #c96442;">
    <div style="font-size:12px;letter-spacing:.6px;text-transform:uppercase;color:#d9d5cb;">Medicen · Nam Khánh Linh</div>
    <div style="font-size:20px;font-weight:700;margin-top:4px;">{{ $heading }}</div>
    @if (!empty($sub))<div style="font-size:13px;color:#d9d5cb;margin-top:4px;">{{ $sub }}</div>@endif
</div>
