@extends('layouts.default_auth')
@section('title', ($doc ? 'Sửa' : 'Thêm') . ' tài liệu kế toán - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <style>
        .ktd-ai__row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .ktd-ai__row input[type=file] { flex: 1 1 260px; min-width: 0; }
        .ktd-ai__hint { display: block; margin-top: 6px; color: #73716c; font-size: 12.5px; line-height: 1.45; }
        .ktd-ai__hint.is-busy { color: #8a3a1f; }
        .ktd-ai__hint.is-ok { color: #2f7a4a; }
        .ktd-ai__hint.is-warn, .ktd-ai__hint.is-err { color: #b42318; }
        .ktd-filled { box-shadow: 0 0 0 3px rgba(201, 100, 66, .18) !important; transition: box-shadow .4s; }
        #ktd-ai-btn[disabled] { opacity: .55; cursor: not-allowed; }
        #ktd-ai-btn .fa-spin { margin-right: 2px; }
        .ktd-help-btn { display: inline-grid; place-items: center; width: 38px; height: 38px; border: 1px solid #e2ded3; border-radius: 11px; background: #fff; color: #73716c; font-size: 17px; cursor: pointer; }
        .ktd-help-btn:hover, .ktd-help-btn:focus-visible { border-color: #e7c3b4; color: #c96442; background: #fbefe9; }
        .ktd-help { width: min(760px, calc(100vw - 32px)); max-height: calc(100vh - 48px); padding: 0; border: 0; border-radius: 18px; color: #1f1e1d; box-shadow: 0 24px 64px rgba(30, 25, 15, .28); }
        .ktd-help::backdrop { background: rgba(31, 30, 29, .45); }
        .ktd-help__head { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #ece8de; background: #fff; }
        .ktd-help__head h2 { margin: 0; font-size: 17px; font-weight: 700; }
        .ktd-help__head h2 i { margin-right: 6px; color: #c96442; }
        .ktd-help__close { width: 34px; height: 34px; border: 0; border-radius: 10px; background: #f5f4ef; color: #73716c; cursor: pointer; }
        .ktd-help__close:hover { background: #ece8de; color: #1f1e1d; }
        .ktd-help__body { padding: 6px 20px 20px; font-size: 13.5px; line-height: 1.55; }
        .ktd-help__body h3 { margin: 16px 0 6px; color: #a3a19b; font-size: 11.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .ktd-help__body ul, .ktd-help__body ol { margin: 0; padding-left: 20px; }
        .ktd-help__body li + li { margin-top: 3px; }
        .ktd-help__note { margin: 10px 0 0; padding: 9px 12px; border-radius: 10px; background: #fbefe9; color: #8a3a1f; }
        .ktd-help__table { overflow-x: auto; border: 1px solid #ece8de; border-radius: 12px; }
        .ktd-help__table table { width: 100%; min-width: 520px; border-collapse: collapse; font-size: 13px; }
        .ktd-help__table th { padding: 8px 12px; background: #faf9f5; color: #73716c; font-size: 12px; font-weight: 650; text-align: left; }
        .ktd-help__table td { padding: 8px 12px; border-top: 1px solid #ece8de; vertical-align: top; }
        .ktd-help__table tr.is-no td { background: #fef6f5; }
        .ktd-help__table tr.is-no td:nth-child(2) { color: #b42318; font-weight: 600; }
        .ktd-help__small { margin-top: 10px !important; color: #73716c; font-size: 12.5px; }
    </style>
@endpush
@push('js')
    <script src="{{ versionResource('assets/js/tool/accounting-doc.js') }}" defer></script>
@endpush
@section('admin_content')
    @php
        $v = fn ($k) => old($k, $doc->{$k} ?? '');
        $val = old('value', $doc && $doc->value ? number_format($doc->value, 0, ',', '.') : '');
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>{{ $doc ? 'Sửa tài liệu' : 'Thêm tài liệu' }}</h1>
                <p>Trợ lý Kế toán đọc phần <b>Tóm tắt</b> để trả lời; file gốc để tải về đối chiếu.</p>
            </div>
            <a href="{{ route('accounting.index', ['tab' => 'tai-lieu']) }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách tài liệu</a>
        </div>
        <form action="{{ $doc ? route('accounting.documents.update', $doc->id) : route('accounting.documents.store') }}" method="post" enctype="multipart/form-data" class="sf-card">
            @csrf
            @if ($doc) @method('patch') @endif
            <div class="sf-grid">
                <div class="sf-input sf-input--full ktd-ai @error('file') has-error @enderror">
                    <span>File gốc <small class="sf-muted">(PDF, Excel, Word, ảnh — tối đa 30 MB{{ $doc && $doc->file_path ? '; để trống = giữ file cũ' : '' }})</small></span>
                    <div class="ktd-ai__row">
                        <input type="file" name="file" id="ktd-file" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png">
                        <button type="button" class="sf-btn sf-btn--dark" id="ktd-ai-btn" data-url="{{ route('accounting.documents.extract') }}" disabled>
                            <i class="fas fa-magic"></i> <span>Đọc bằng AI</span>
                        </button>
                        <button type="button" class="ktd-help-btn" data-ktd-help title="Hướng dẫn và giới hạn file" aria-label="Hướng dẫn Đọc bằng AI">
                            <i class="fas fa-question-circle"></i>
                        </button>
                    </div>
                    <small class="ktd-ai__hint" id="ktd-ai-status">Chọn file rồi bấm <b>Đọc bằng AI</b>: AI tự điền loại, tên, các bên, ngày, giá trị và tóm tắt. Kiểm tra lại rồi mới bấm Lưu.</small>
                    @error('file')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                </div>
                <label class="sf-input @error('type') has-error @enderror">
                    <span>Loại</span>
                    <select name="type" id="ktd-type">
                        @foreach ($docTypes as $k => $label)<option value="{{ $k }}" @selected($v('type') === $k)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="sf-input @error('title') has-error @enderror">
                    <span>Tên tài liệu</span>
                    <input type="text" name="title" value="{{ $v('title') }}" maxlength="255" placeholder="Vd: HĐ thuê máy SA của T.D số 016240426" autocomplete="off">
                    @error('title')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                </label>
                <label class="sf-input">
                    <span>Các bên / đối tác <small class="sf-muted">(không bắt buộc)</small></span>
                    <input type="text" name="parties" value="{{ $v('parties') }}" maxlength="255" autocomplete="off">
                </label>
                <label class="sf-input">
                    <span>Kỳ <small class="sf-muted">(BCTC: vd 2025, 6T/2026)</small></span>
                    <input type="text" name="period" value="{{ $v('period') }}" maxlength="50" autocomplete="off">
                </label>
                <label class="sf-input"><span>Ngày ký</span><input type="date" name="signed_on" value="{{ $v('signed_on') }}"></label>
                <label class="sf-input"><span>Giá trị (đ)</span><input type="text" name="value" value="{{ $val }}" inputmode="numeric" placeholder="Vd: 4.650.000.000" autocomplete="off"></label>
                <label class="sf-input"><span>Hiệu lực từ</span><input type="date" name="valid_from" value="{{ $v('valid_from') }}"></label>
                <label class="sf-input @error('valid_to') has-error @enderror"><span>Hiệu lực đến</span><input type="date" name="valid_to" value="{{ $v('valid_to') }}">
                    @error('valid_to')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror</label>
                <label class="sf-input sf-input--full @error('summary') has-error @enderror">
                    <span>Tóm tắt / số liệu chính</span>
                    <textarea name="summary" rows="10" placeholder="Hợp đồng: nội dung, đơn giá, thời hạn, thanh toán, điều khoản đáng chú ý.&#10;BCTC: các chỉ tiêu chính của bảng cân đối và kết quả kinh doanh (dán nguyên văn được).">{{ $v('summary') }}</textarea>
                    @error('summary')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                </label>
            </div>
            <div class="sf-form-actions">
                <a href="{{ route('accounting.index', ['tab' => 'tai-lieu']) }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> {{ $doc ? 'Lưu thay đổi' : 'Lưu tài liệu' }}</button>
            </div>
        </form>
    </div>
    @include('pages.admin.accounting._ai_help')
@endsection
