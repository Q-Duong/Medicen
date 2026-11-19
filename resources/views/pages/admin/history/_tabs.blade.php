{{-- Hai loại lịch sử: đơn hàng (history_edits) và thao tác kế toán / trợ lý (activity_logs) --}}
<nav class="hs-tabs" aria-label="Loại lịch sử">
    <a href="{{ route('history.index') }}" @class(['is-active' => $tab === 'don'])><i class="fas fa-file-medical"></i> Đơn hàng</a>
    <a href="{{ route('history.index', ['tab' => 'ke-toan']) }}" @class(['is-active' => $tab === 'ke-toan'])><i class="fas fa-calculator"></i> Kế toán &amp; trợ lý AI</a>
</nav>
<style>
    .hs-tabs { display: flex; gap: 6px; margin: 0 0 14px; padding: 4px; width: fit-content; max-width: 100%; overflow-x: auto; border: 1px solid #e2ded3; border-radius: 12px; background: #faf9f5; }
    .hs-tabs a { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 9px; color: #73716c !important; font-size: 14px; font-weight: 600; text-decoration: none !important; white-space: nowrap; }
    .hs-tabs a:hover { color: #1f1e1d !important; }
    .hs-tabs a.is-active { background: #fff; color: #c96442 !important; box-shadow: 0 1px 3px rgba(31, 30, 29, .08); }
    .hs-changes { margin: 6px 0 0; padding: 0; list-style: none; font-size: 12.5px; color: #4a4843; }
    .hs-changes li { padding: 1px 0; }
    .hs-changes b { font-weight: 600; color: #73716c; }
    .hs-changes .hs-old { color: #b42318; text-decoration: line-through; text-decoration-color: rgba(180, 35, 24, .35); }
    .hs-changes .hs-new { color: #18794e; font-weight: 600; }
</style>
