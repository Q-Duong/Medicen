{{-- Ghi nhớ số cũ / công nợ cũ (acc_memos), dùng ở Công nợ CT (scope ct) và Sổ VCB (scope vcb). Biến: $memos, $scope, $intro, $canEdit --}}
<section class="rp-card">
    <h2 class="rp-card__title"><i class="fas fa-sticky-note"></i> {{ $scope === 'ct' ? 'Công nợ cũ cần ghi nhớ' : 'Ghi nhớ số cũ' }}</h2>
    <p class="bk-hint" style="margin:-4px 0 6px !important">{{ $intro }}</p>
    @forelse ($memos as $memo)
        <div class="bk-memo">
            <div class="bk-memo__head">
                <span>{{ $memo->title }}@if ($memo->period) <span class="bk-tag">{{ $memo->period }}</span>@endif</span>
                <span class="bk-memo__amount">{{ $memo->amount !== null ? number_format($memo->amount, 0, ',', '.') . ' đ' : 'chưa có số' }}</span>
            </div>
            @if ($memo->note)<p>{{ $memo->note }}</p>@endif
            @if ($canEdit)
                <details>
                    <summary class="bk-edit"><i class="fas fa-pen"></i> Sửa / xóa</summary>
                    <form method="post" action="{{ route('ct.memos.update', $memo) }}" class="bk-form" style="margin-top:8px">
                        @csrf @method('patch')
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        <label class="is-full"><span>Nội dung</span><input type="text" name="title" value="{{ $memo->title }}" maxlength="255" required></label>
                        <label><span>Kỳ</span><input type="text" name="period" value="{{ $memo->period }}" maxlength="50"></label>
                        <label><span>Số tiền (đ)</span><input type="text" name="amount" class="is-money" value="{{ $memo->amount !== null ? number_format($memo->amount, 0, ',', '.') : '' }}" inputmode="numeric"></label>
                        <label class="is-full"><span>Ghi chú</span><textarea name="note" rows="2">{{ $memo->note }}</textarea></label>
                        <div class="bk-actions is-full" style="margin-top:0">
                            <button class="rp-btn" type="submit" form="memo-del-{{ $memo->id }}" style="color:#b42318 !important"><i class="fas fa-trash-alt"></i> Xóa</button>
                            <button type="button" class="rp-btn" data-details-cancel>Hủy</button> <button class="rp-btn rp-btn--primary" type="submit">Lưu</button>
                        </div>
                    </form>
                    <form method="post" action="{{ route('ct.memos.destroy', $memo) }}" id="memo-del-{{ $memo->id }}" data-confirm="Xóa ghi nhớ &quot;{{ $memo->title }}&quot;?" data-confirm-danger>
                        @csrf @method('delete')
                    </form>
                </details>
            @endif
        </div>
    @empty
        <p class="bk-hint">Chưa có ghi nhớ.</p>
    @endforelse
    @if ($canEdit)
        <details style="margin-top:10px">
            <summary class="rp-add" style="display:inline-flex;align-items:center;list-style:none">+ Thêm ghi nhớ</summary>
            <form method="post" action="{{ route('ct.memos.store') }}" class="bk-form" style="margin-top:10px">
                @csrf
                <input type="hidden" name="scope" value="{{ $scope }}">
                <label class="is-full"><span>Nội dung</span><input type="text" name="title" maxlength="255" required></label>
                <label><span>Kỳ</span><input type="text" name="period" maxlength="50" placeholder="Vd: 2023-2024"></label>
                <label><span>Số tiền (đ)</span><input type="text" name="amount" class="is-money" inputmode="numeric"></label>
                <label class="is-full"><span>Ghi chú</span><textarea name="note" rows="2"></textarea></label>
                <div class="bk-actions is-full" style="margin-top:0"><button type="button" class="rp-btn" data-details-cancel>Hủy</button> <button class="rp-btn rp-btn--primary" type="submit">Lưu ghi nhớ</button></div>
            </form>
        </details>
    @endif
</section>
