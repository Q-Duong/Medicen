{{-- Danh sách đơn chờ xếp lịch (30 ngày tới) + nhóm "đã qua ngày" và "xa hơn 30 ngày". Tải lại bằng JS (DispatchController::pending) --}}
<span hidden data-pending-count="{{ $pending->count() }}"></span>
        <p class="sec-title">Đơn chờ xếp lịch <small id="pending-empty" hidden>Đã xếp hết các đơn</small><small>trong {{ config('schedule.suggest_days') }} ngày tới · bấm vào đơn để xếp thủ công, hoặc dùng nút Gợi ý cho tất cả</small></p>
        @if ($pending->isEmpty())
            <p class="empty-note">Không có đơn nào đang chờ xếp lịch.</p>
        @else
            <div class="pend">
                @foreach ($pending as $o)
                    @include('pages.admin.dispatch._pending_card', ['o' => $o, 'group' => 'main'])
                @endforeach
            </div>
        @endif

        @foreach (['past' => [$pendingPast, 'Đã qua ngày, chưa xếp xe', 'trong ' . config('schedule.past_days') . ' ngày gần đây · bấm vào đơn để ghi bổ sung xe / người đã chạy (không dùng gợi ý AI)'],
                   'later' => [$pendingLater, 'Xa hơn ' . config('schedule.suggest_days') . ' ngày tới', 'xếp thủ công hoặc giữ chỗ trước; nút Gợi ý chỉ xử lý đơn trong ' . config('schedule.suggest_days') . ' ngày tới']] as $group => [$list, $label, $hint])
            @if ($list->isNotEmpty())
                <details class="pend-group pend-group--{{ $group }}" data-group-box="{{ $group }}">
                    <summary>
                        <i class="fas fa-chevron-right"></i> {{ $label }} (<span data-group-count>{{ $list->count() }}</span> đơn)
                        <small>{{ $hint }}</small>
                    </summary>
                    <div class="pend">
                        @foreach ($list as $o)
                            @include('pages.admin.dispatch._pending_card', ['o' => $o, 'group' => $group])
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
