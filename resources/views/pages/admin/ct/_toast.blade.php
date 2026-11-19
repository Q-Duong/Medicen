{{-- Thông báo nổi góc phải cho Công nợ CT / Sổ VCB (khóa flash bk_success, lỗi kiểm tra dữ liệu), kèm hộp xác nhận uiConfirm --}}
@php($toastMsg = session('bk_success') ?: ($errors->any() ? $errors->first() : null))
@if ($toastMsg)
    <div class="bk-toast {{ session('bk_success') ? 'is-ok' : 'is-err' }}" role="status" id="bk-toast">
        <i class="fas {{ session('bk_success') ? 'fa-check-circle' : 'fa-exclamation-circle' }} bk-toast__icon"></i>
        <div class="bk-toast__text">
            <b>{{ session('bk_success') ? 'Thành công' : 'Chưa thực hiện được' }}</b>
            <span>{{ $toastMsg }}</span>
        </div>
        <button type="button" class="bk-toast__close" aria-label="Đóng" onclick="this.parentElement.classList.remove('is-show')"><i class="fas fa-times"></i></button>
    </div>
    <script>
        (function () {
            const t = document.getElementById('bk-toast');
            requestAnimationFrame(() => t.classList.add('is-show'));
            setTimeout(() => t.classList.remove('is-show'), {{ session('bk_success') ? 4500 : 9000 }});
        })();
    </script>
@endif
@include('pages.admin.partials.dialog')
