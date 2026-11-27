{{-- Khung chat Trợ lý AI nổi góc phải dưới trên mọi trang admin (trừ trang Trợ lý). Dùng chung API và lịch sử với trang Trợ lý.
     JS: assets/js/tool/org-assistant-widget.js · CSS: assets/css/support/org-assistant-widget.css --}}
@php
    $awUser = Auth::user();
    $awList = $awUser && !request()->routeIs('assistant.*') ? \App\Services\Claude\AssistantAccess::assistantsFor($awUser) : [];
@endphp
@if ($awList)
    @php
        $awDefault = array_key_exists((string) $awUser->department, $awList) ? (string) $awUser->department : array_key_first($awList);
        $r = request()->route()?->getName() ?? '';
        $awSuggest = match (true) {
            in_array($r, ['order.edit', 'order.copy'], true) => ['Đơn này lời bao nhiêu?', 'Giá đơn này có đúng hợp đồng không?', 'Khách này còn nợ quá hạn không?'],
            str_starts_with($r, 'order.') => ['Đơn nào sắp chụp mà chưa xếp xe?', 'Lịch xe tuần này còn trống ngày nào?'],
            str_starts_with($r, 'sales_logs.') => ['Hôm nay còn việc gì với khách?', 'Tóm tắt hôm nay Sales làm gì'],
            $r === 'unit.edit' => ['Đơn vị này còn nợ bao nhiêu?', 'Hợp đồng của đơn vị này giá thế nào?'],
            $r === 'accountant.index' => ['Các đơn đang lọc đã đúng giá hợp đồng chưa?', 'Nợ quá hạn cần thu hôm nay?'],
            str_starts_with($r, 'finance.') || str_starts_with($r, 'accountant') || str_starts_with($r, 'reports.') => ['Nợ quá hạn cần thu hôm nay?', 'Dòng tiền 4 tuần tới thế nào?'],
            str_starts_with($r, 'contracts.') => ['Hợp đồng nào sắp hết hạn?', 'Đơn nào thu thấp hơn giá hợp đồng?'],
            default => null,
        };
        $awDepts = collect($awList)->map(fn ($title, $key) => [
            'key' => $key, 'title' => $title,
            'ask' => route('assistant.ask', $key),
            'poll' => route('assistant.poll', [$key, '__ID__']),
            'messages' => route('assistant.messages', [$key, '__ID__']),
            'page' => route('assistant.show', $key),
            'suggest' => $awSuggest ?? ($key === 'sales'
                ? ['Hôm nay còn việc gì với khách?', 'Đơn nào sắp chụp mà chưa xếp xe?', 'Soạn tin nhắc nợ khách quá hạn']
                : ['Tình hình công nợ hôm nay?', 'Hôm nay Sales làm gì?', 'Dòng tiền 4 tuần tới thế nào?']),
        ])->values();
    @endphp
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/assistant-widget.css') }}" type="text/css" />
    <div id="aw" class="aw" data-config='@json(['user' => $awUser->id, 'default' => $awDefault, 'depts' => $awDepts])' hidden>
        <button type="button" class="aw-fab" data-aw-toggle aria-label="Mở Trợ lý AI" title="Trợ lý AI">
            <i class="fas fa-robot"></i><span class="aw-dot" hidden></span>
        </button>
        <section class="aw-panel" role="dialog" aria-label="Trợ lý AI" hidden>
            <header class="aw-head">
                <span class="aw-head__icon"><i class="fas fa-robot"></i></span>
                @if (count($awList) > 1)
                    <select class="aw-dept" data-aw-dept aria-label="Chọn trợ lý">
                        @foreach ($awList as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach
                    </select>
                @else
                    <b class="aw-title">{{ reset($awList) }}</b>
                @endif
                <button type="button" class="aw-icon" data-aw-new title="Cuộc trò chuyện mới"><i class="fas fa-plus"></i></button>
                <a class="aw-icon" data-aw-full href="#" title="Mở trang Trợ lý đầy đủ"><i class="fas fa-expand-alt"></i></a>
                <button type="button" class="aw-icon" data-aw-toggle title="Thu nhỏ"><i class="fas fa-times"></i></button>
            </header>
            <div class="aw-body" data-aw-body aria-live="polite"></div>
            <div class="aw-suggest" data-aw-suggest></div>
            <form class="aw-form" data-aw-form>
                <textarea rows="1" maxlength="6000" placeholder="Hỏi trợ lý… (Enter để gửi)" data-aw-input aria-label="Câu hỏi"></textarea>
                <button type="submit" class="aw-send" aria-label="Gửi" data-aw-send><i class="fas fa-paper-plane"></i></button>
            </form>
            <p class="aw-foot">Trợ lý biết bạn đang xem trang nào. Biểu đồ, file PDF / Word: mở trang đầy đủ.</p>
        </section>
    </div>
    <script src="{{ versionResource('assets/js/tool/assistant-widget.js') }}" defer></script>
@endif
