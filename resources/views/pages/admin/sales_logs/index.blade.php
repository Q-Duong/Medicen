@extends('layouts.default_auth')
@section('title', 'Nhật ký Sales - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/sales-log.css') }}" type="text/css" as="style" />
@endpush
@push('js')
    <script src="{{ versionResource('assets/js/tool/sales-log.js') }}" defer></script>
@endpush
@section('admin_content')
    @php
        $isToday = $day->isToday();
        $wd = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        $q = fn ($extra) => array_filter(array_merge(['ngay' => $day->toDateString(), 'nguoi' => $userId], $extra));
        $newId = (int) session('sl_new');
    @endphp
    <div class="sf sl">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Nhật ký Sales</h1>
                <p>Dán đoạn chat Zalo, ảnh chụp màn hình hoặc ghi lại cuộc gọi với khách — AI tóm tắt và tách việc cần làm để nhắc lại.
                    {{ config('sales_log.digest_at') }} mỗi ngày gửi email nhắc việc; giám đốc cũng hỏi được Trợ lý AI ("hôm nay Sales làm gì").</p>
            </div>
            <form method="GET" action="{{ route('sales_logs.index') }}" class="sl-daynav">
                <a href="{{ route('sales_logs.index', $q(['ngay' => $day->copy()->subDay()->toDateString()])) }}" class="sf-icon-btn" title="Ngày trước"><i class="fas fa-chevron-left"></i></a>
                <input type="date" name="ngay" value="{{ $day->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()">
                @if (!$isToday)
                    <a href="{{ route('sales_logs.index', $q(['ngay' => $day->copy()->addDay()->toDateString()])) }}" class="sf-icon-btn" title="Ngày sau"><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route('sales_logs.index', $q(['ngay' => null])) }}" class="sf-btn sf-btn--ghost">Hôm nay</a>
                @endif
                @if ($users->count() > 1)
                    <select name="nguoi" onchange="this.form.submit()">
                        <option value="">Tất cả Sales</option>
                        @foreach ($users as $u)<option value="{{ $u->id }}" @selected($userId === $u->id)>{{ $u->name }}</option>@endforeach
                    </select>
                @endif
            </form>
        </div>

        @if ($errors->any())
            <div class="sl-errors"><i class="fas fa-exclamation-circle"></i><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="sl-grid">
            <div class="sl-main">
                {{-- Ghi nhanh --}}
                @if (!$canEdit)
                    <p class="sl-readonly"><i class="far fa-eye"></i> Bạn đang xem nhật ký (chỉ xem). Sales ghi trao đổi với khách và đánh dấu việc ở đây.</p>
                @else
                <form method="POST" action="{{ route('sales_logs.store') }}" enctype="multipart/form-data" class="sf-card sl-add" id="sl-add">
                    @csrf
                    <input type="hidden" name="happened_on" value="{{ $day->toDateString() }}">
                    <div class="sl-kinds" role="radiogroup" aria-label="Loại">
                        @foreach (\App\Models\SalesLog::KINDS as $k => $label)
                            <label class="sl-kind"><input type="radio" name="kind" value="{{ $k }}" @checked(old('kind', 'chat') === $k)><span><i class="{{ \App\Models\SalesLog::KIND_ICONS[$k] }}"></i> {{ $label }}</span></label>
                        @endforeach
                    </div>
                    <div class="sl-drop" data-sl-drop>
                        <textarea name="raw_text" rows="5" maxlength="30000" data-sl-text
                            placeholder="Dán đoạn chat Zalo vào đây (copy cả đoạn rồi Ctrl+V), hoặc dán thẳng ẢNH CHỤP MÀN HÌNH chat.&#10;Sau cuộc gọi: ghi vài dòng, vd “Gọi chị Lan BV Quốc Ánh, muốn chụp 300 ca ngày 5/12, chờ báo giá trước thứ 4”.">{{ old('raw_text') }}</textarea>
                        <div class="sl-thumbs" data-sl-thumbs></div>
                    </div>
                    <div class="sl-add__foot">
                        <label class="sl-file sf-btn sf-btn--ghost"><i class="far fa-image"></i> Thêm ảnh chụp
                            <input type="file" name="images[]" accept="image/png,image/jpeg,image/webp" multiple data-sl-file></label>
                        <select name="unit_id" class="sl-unit" title="Đơn vị (để trống: AI tự nhận)">
                            <option value="">Đơn vị: AI tự nhận</option>
                            @foreach ($units as $u)<option value="{{ $u->id }}" @selected((int) old('unit_id') === $u->id)>{{ $u->unit_abbreviation ?: $u->unit_name }}</option>@endforeach
                        </select>
                        <button type="submit" class="sf-btn sf-btn--primary sl-submit" data-sl-submit><i class="fas fa-magic"></i> <span>Lưu &amp; để AI đọc</span></button>
                    </div>
                    <p class="sl-hint"><i class="fas fa-lock"></i> Số điện thoại, email trong đoạn chat được che trước khi gửi AI. Ghi cho ngày {{ $day->format('d/m/Y') }}{{ $isToday ? '' : ' (đang xem ngày cũ)' }}.</p>
                </form>
                @endif

                {{-- Tóm tắt ngày --}}
                <section class="sf-card sl-digest">
                    <div class="sl-digest__head">
                        <h2><i class="fas fa-clipboard-list"></i> Tóm tắt {{ $isToday ? 'hôm nay' : 'ngày ' . $day->format('d/m') }}</h2>
                        @if ($canEdit)
                        <form method="POST" action="{{ route('sales_logs.digest') }}" data-sl-busy="AI đang tóm tắt…">
                            @csrf <input type="hidden" name="ngay" value="{{ $day->toDateString() }}">
                            <button type="submit" class="sf-btn sf-btn--ghost"><i class="fas fa-sync-alt"></i> {{ $digest ? 'Tóm tắt lại' : 'Tóm tắt bằng AI' }}</button>
                        </form>
                        @endif
                    </div>
                    @if ($digest)
                        <div class="sl-md">{!! \Illuminate\Support\Str::markdown($digest->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                        <p class="sl-meta">Cập nhật {{ $digest->updated_at->format('H:i d/m') }}{{ $digest->emailed_at ? ' · đã gửi email lúc ' . $digest->emailed_at->format('H:i') : '' }}</p>
                    @else
                        <p class="sl-meta">Chưa có. {{ config('sales_log.digest_at') }} hệ thống tự tóm tắt và gửi email nhắc việc; hoặc bấm "Tóm tắt bằng AI" để xem ngay.</p>
                    @endif
                </section>

                {{-- Dòng thời gian --}}
                <h2 class="sf-section">Trao đổi với khách · {{ $wd[$day->dayOfWeek] }}, {{ $day->format('d/m/Y') }} <small>{{ $logs->count() }} mục</small></h2>
                @forelse ($logs as $l)
                    <article class="sf-card sl-log {{ $l->important ? 'is-important' : '' }} {{ $newId === $l->id ? 'is-new' : '' }}" id="log-{{ $l->id }}">
                        <header class="sl-log__head">
                            <span class="sl-log__icon sl-log__icon--{{ $l->kind }}"><i class="{{ \App\Models\SalesLog::KIND_ICONS[$l->kind] ?? 'far fa-sticky-note' }}"></i></span>
                            <div class="sl-log__who">
                                <b>{{ $l->customer ?: 'Chưa rõ khách' }}</b>
                                @if ($l->unit)<span class="sl-chip">{{ $l->unit->unit_abbreviation ?: $l->unit->unit_name }}</span>@endif
                                @if ($l->important)<span class="sl-chip sl-chip--bad"><i class="fas fa-exclamation-triangle"></i> Đáng chú ý</span>@endif
                                <div class="sl-meta">{{ \App\Models\SalesLog::KINDS[$l->kind] ?? '' }} · {{ $l->created_at->format('H:i') }} · {{ $l->user->name ?? '—' }}</div>
                            </div>
                            @if ($canEdit)
                            <details class="sl-menu">
                                <summary class="sf-icon-btn" title="Sửa / xóa"><i class="fas fa-ellipsis-h"></i></summary>
                                <div class="sl-menu__pop">
                                    <form method="POST" action="{{ route('sales_logs.update', $l->id) }}" class="sl-edit">
                                        @csrf @method('patch')
                                        <label>Khách<input type="text" name="customer" value="{{ $l->customer }}" maxlength="191"></label>
                                        <label>Loại<select name="kind">@foreach (\App\Models\SalesLog::KINDS as $k => $label)<option value="{{ $k }}" @selected($l->kind === $k)>{{ $label }}</option>@endforeach</select></label>
                                        <label>Đơn vị<select name="unit_id"><option value="">—</option>@foreach ($units as $u)<option value="{{ $u->id }}" @selected($l->unit_id === $u->id)>{{ $u->unit_abbreviation ?: $u->unit_name }}</option>@endforeach</select></label>
                                        <label>Tóm tắt<textarea name="summary" rows="4" maxlength="3000">{{ $l->summary }}</textarea></label>
                                        <label class="sl-check"><input type="checkbox" name="important" value="1" @checked($l->important)> Đáng chú ý</label>
                                        <button type="submit" class="sf-btn sf-btn--primary">Lưu</button>
                                    </form>
                                    <form method="POST" action="{{ route('sales_logs.destroy', $l->id) }}" onsubmit="return confirm('Xóa mục nhật ký này và các việc chưa xong của nó?')">
                                        @csrf @method('delete')
                                        <button type="submit" class="sf-btn sf-btn--danger"><i class="far fa-trash-alt"></i> Xóa mục này</button>
                                    </form>
                                </div>
                            </details>
                            @endif
                        </header>
                        @if ($l->summary)
                            <p class="sl-log__summary">{!! nl2br(e($l->summary)) !!}</p>
                        @elseif ($l->ai_status === 'failed')
                            <p class="sl-log__summary sl-muted"><i class="fas fa-exclamation-circle"></i> AI chưa đọc được{{ $l->ai_error ? ': ' . $l->ai_error : '' }}. Bấm ··· để tự ghi tóm tắt.</p>
                        @endif
                        @if ($l->followups->isNotEmpty())
                            <ul class="sl-todos">
                                @foreach ($l->followups as $f)
                                    @include('pages.admin.sales_logs._todo', ['f' => $f, 'compact' => true])
                                @endforeach
                            </ul>
                        @endif
                        @if ($canEdit)
                        <form method="POST" action="{{ route('sales_logs.followups.store') }}" class="sl-addtodo">
                            @csrf <input type="hidden" name="sales_log_id" value="{{ $l->id }}">
                            <input type="text" name="title" placeholder="+ Thêm việc cho khách này" maxlength="500" required>
                            <input type="date" name="due_on" min="{{ today()->toDateString() }}">
                            <button type="submit" class="sf-icon-btn" title="Thêm"><i class="fas fa-plus"></i></button>
                        </form>
                        @endif
                        @if ($l->raw_text || $l->images)
                            <details class="sl-raw">
                                <summary>Nội dung gốc{{ $l->images ? ' · ' . count($l->images) . ' ảnh' : '' }}</summary>
                                @if ($l->raw_text)<pre>{{ $l->raw_text }}</pre>@endif
                                @if ($l->images)
                                    <div class="sl-raw__imgs">
                                        @foreach ($l->images as $img)<a href="{{ route('sales_logs.image', $img) }}" target="_blank" rel="noopener"><img src="{{ route('sales_logs.image', $img) }}" alt="Ảnh chụp" loading="lazy"></a>@endforeach
                                    </div>
                                @endif
                            </details>
                        @endif
                    </article>
                @empty
                    <div class="sf-card sf-empty">{{ $isToday ? 'Hôm nay chưa ghi trao đổi nào. Dán đoạn chat hoặc ảnh chụp ở khung trên.' : 'Ngày này không có nhật ký.' }}</div>
                @endforelse
            </div>

            {{-- Việc cần làm --}}
            <aside class="sl-side">
                <section class="sf-card sl-todo-card">
                    <h2><i class="far fa-check-square"></i> Việc cần làm</h2>
                    @foreach ($groups as $key => [$label, $items])
                        @if ($items->isNotEmpty())
                            <h3 class="sl-todo-group sl-todo-group--{{ $key }}">{{ $label }} <b>{{ $items->count() }}</b></h3>
                            <ul class="sl-todos">
                                @foreach ($items as $f)
                                    @include('pages.admin.sales_logs._todo', ['f' => $f, 'compact' => false])
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                    @if (collect($groups)->every(fn ($g) => $g[1]->isEmpty()))
                        <p class="sl-meta">Không còn việc nào. 🎉</p>
                    @endif
                    @if ($canEdit)
                    <form method="POST" action="{{ route('sales_logs.followups.store') }}" class="sl-addtodo sl-addtodo--side">
                        @csrf
                        <input type="text" name="title" placeholder="+ Thêm việc (vd Gọi lại BV Quốc Ánh)" maxlength="500" required>
                        <input type="date" name="due_on" value="{{ today()->toDateString() }}">
                        <button type="submit" class="sf-icon-btn" title="Thêm"><i class="fas fa-plus"></i></button>
                    </form>
                    @endif
                    @if ($doneToday->isNotEmpty())
                        <details class="sl-done">
                            <summary>Đã xong hôm nay ({{ $doneToday->count() }})</summary>
                            <ul class="sl-todos">@foreach ($doneToday as $f) @include('pages.admin.sales_logs._todo', ['f' => $f, 'compact' => false]) @endforeach</ul>
                        </details>
                    @endif
                </section>
            </aside>
        </div>
    </div>
@endsection
