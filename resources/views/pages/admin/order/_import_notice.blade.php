{{--
    Đặt @include('order._import_notice') NGAY BÊN TRONG thẻ <form> của trang tạo đơn.
    - Giữ excel_upload_id để khi lưu đơn, file Excel được gắn với đơn hàng.
    - Hiện thông báo đã điền sẵn + các cảnh báo khi đọc file.
--}}
@if (old('excel_upload_id'))
    <input type="hidden" name="excel_upload_id" value="{{ old('excel_upload_id') }}">
@endif

@if (session('importNotice'))
    @php $notice = session('importNotice'); @endphp
    <div class="alert {{ $notice['warnings'] ? 'alert-warning' : 'alert-info' }}" role="status">
        Đã điền sẵn thông tin từ file <strong>{{ $notice['file'] }}</strong>. Kiểm tra lại trước khi lưu đơn.
        @if ($notice['warnings'])
            <ul class="mb-0 mt-2">
                @foreach ($notice['warnings'] as $w)
                    <li>{{ $w }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
