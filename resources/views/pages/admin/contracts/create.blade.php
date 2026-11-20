@extends('layouts.default_auth')
@section('title', 'Tải hợp đồng lên - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/contracts.css') }}" type="text/css" as="style" />
@endpush
@push('js')
    <script src="{{ versionResource('assets/js/tool/contracts.js') }}" defer></script>
@endpush
@section('admin_content')
    <div class="sf hd">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Tải hợp đồng lên</h1>
                <p>Chọn một hoặc nhiều file — AI đọc từng file và điền sẵn. Xem lại, chọn đúng đơn vị rồi bấm <b>Lưu</b> từng hợp đồng.</p>
            </div>
            <a href="{{ route('contracts.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách hợp đồng</a>
        </div>

        <section class="sf-card hd-drop" data-hd-drop data-extract-url="{{ route('contracts.extract') }}">
            <input type="file" id="hd-files" accept=".pdf,.docx,.jpg,.jpeg,.png" multiple hidden>
            <label for="hd-files" class="hd-drop__zone">
                <i class="fas fa-cloud-upload-alt"></i>
                <b>Kéo thả file vào đây hoặc bấm để chọn</b>
                <span>Word (.docx), PDF (cả bản scan), ảnh JPG / PNG — tối đa 30 MB mỗi file, PDF tối đa 20 MB. Mỗi lần tối đa 20 file.</span>
            </label>
            <ul class="hd-drop__notes">
                <li><b>Nguyên tắc</b>: bảng giá theo số ca / ngày, cộng thêm in phim, phụ thu khoảng cách, số ngày thanh toán.</li>
                <li><b>Thời vụ</b>: một đợt chụp — giá trọn gói, ngày chụp, công ty được chụp; gắn với đơn hàng cùng đơn vị và ngày.</li>
                <li>AI đọc mỗi file khoảng 30–90 giây. File được giữ tạm 1 ngày; chỉ lưu vào hệ thống khi bấm Lưu.</li>
            </ul>
        </section>

        <div class="hd-queue" data-hd-queue></div>
    </div>
@endsection
