{{-- Một việc cần làm. Biến: $f (SalesFollowup), $compact (trong thẻ nhật ký: không lặp tên khách) --}}
@php
    $overdue = $f->isOverdue();
    $isToday = $f->due_on && $f->due_on->isToday();
@endphp
<li class="sl-todo {{ $f->done_at ? 'is-done' : '' }} {{ $overdue ? 'is-overdue' : '' }}">
    @if ($canEdit)
    <form method="POST" action="{{ route('sales_logs.followups.update', $f->id) }}" class="sl-todo__check">
        @csrf @method('patch') <input type="hidden" name="toggle" value="1">
        <button type="submit" class="sl-box" title="{{ $f->done_at ? 'Bỏ đánh dấu xong' : 'Đánh dấu đã xong' }}" aria-label="Xong">@if ($f->done_at)<i class="fas fa-check"></i>@endif</button>
    </form>
    @else
    <span class="sl-box" aria-hidden="true">@if ($f->done_at)<i class="fas fa-check"></i>@endif</span>
    @endif
    <div class="sl-todo__body">
        <span class="sl-todo__title">{{ $f->title }}</span>
        <span class="sl-todo__meta">
            @if ($f->due_on)<span class="sl-due {{ $overdue ? 'is-bad' : ($isToday ? 'is-today' : '') }}"><i class="far fa-clock"></i> {{ $isToday ? 'Hôm nay' : $f->due_on->format('d/m') }}</span>@endif
            @if (!$compact && $f->log)<a href="{{ route('sales_logs.index', ['ngay' => $f->log->happened_on->toDateString()]) }}#log-{{ $f->sales_log_id }}">nhật ký {{ $f->log->happened_on->format('d/m') }}</a>@endif
            @if (!$compact && $f->user)<span>{{ $f->user->name }}</span>@endif
        </span>
    </div>
    @if ($canEdit)
    <details class="sl-todo__edit">
        <summary class="sf-icon-btn" title="Sửa"><i class="fas fa-pen"></i></summary>
        <div class="sl-menu__pop">
            <form method="POST" action="{{ route('sales_logs.followups.update', $f->id) }}" class="sl-edit">
                @csrf @method('patch')
                <label>Việc<input type="text" name="title" value="{{ $f->title }}" maxlength="500" required></label>
                <label>Hạn<input type="date" name="due_on" value="{{ $f->due_on?->toDateString() }}"></label>
                <button type="submit" class="sf-btn sf-btn--primary">Lưu</button>
            </form>
            <form method="POST" action="{{ route('sales_logs.followups.destroy', $f->id) }}">
                @csrf @method('delete')
                <button type="submit" class="sf-btn sf-btn--danger"><i class="far fa-trash-alt"></i> Xóa việc</button>
            </form>
        </div>
    </details>
    @endif
</li>
