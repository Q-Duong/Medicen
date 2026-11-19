{{-- Cảnh báo hạn mức tín dụng ở đầu trang Công nợ (kế toán / sales). Chi tiết: Phân tích tài chính → Hạn mức tín dụng --}}
@php($creditBad = app(\App\Services\Accounting\CreditPolicy::class)->all()->whereIn('status', ['vuot_han_muc', 'qua_han_lau'])->values())
@if ($creditBad->isNotEmpty())
    <div style="display:flex;align-items:flex-start;gap:10px;margin:0 0 14px;padding:10px 14px;border:1px solid #f3c6bf;border-radius:12px;background:#fdf1ef;color:#7a1d14;font-size:13.5px;line-height:1.5;">
        <i class="fas fa-shield-alt" style="margin-top:3px;color:#b42318"></i>
        <div style="flex:1;min-width:0">
            <b>{{ $creditBad->count() }} đơn vị vượt hạn mức tín dụng hoặc quá hạn quá lâu</b> — nên thu nợ / xin duyệt trước khi nhận lịch mới:
            {{ $creditBad->take(8)->map(fn ($c) => $c['unit'] . ' (' . ($c['status'] === 'vuot_han_muc' ? number_format($c['used_pct'], 0, ',', '.') . '% hạn mức' : 'quá hạn ' . $c['max_overdue_days'] . ' ngày') . ')')->implode(', ') }}{{ $creditBad->count() > 8 ? '…' : '' }}
        </div>
        @if (\Illuminate\Support\Facades\Route::has('finance.index') && \App\Http\Middleware\PageAccess::allows(auth()->user(), ['debt_accountant']))
            <a href="{{ route('finance.index', ['tab' => 'han-muc']) }}" style="white-space:nowrap;color:#b42318;font-weight:600">Xem chi tiết</a>
        @endif
    </div>
@endif
