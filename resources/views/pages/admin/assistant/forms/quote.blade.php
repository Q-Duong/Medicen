{{-- Form "Báo giá": ghép thành câu hỏi đầy đủ gửi cho trợ lý --}}
<div class="af" data-form="quote" hidden>
    <div class="af__grid">
        <label class="af__field af__field--wide">
            <span>Đơn vị <b>*</b></span>
            <input type="text" name="don_vi" list="assistant-units" required placeholder="Tên hoặc viết tắt, vd: BV BÌNH CHÁNH">
        </label>
        <label class="af__field">
            <span>Dịch vụ <b>*</b></span>
            <select name="dich_vu" required>
                <option value="XQ Phổi">XQ Phổi</option>
                <option value="XQ CSTL (cột sống thắt lưng)">XQ CSTL</option>
                <option value="Đo loãng xương">Loãng xương</option>
                <option value="XQ Nhũ ảnh">Nhũ ảnh</option>
                <option value="Siêu âm Bụng / Giáp / Nhũ">Siêu âm Bụng/Giáp/Nhũ</option>
                <option value="Siêu âm Tim">Siêu âm Tim</option>
                <option value="Siêu âm ĐMC">Siêu âm ĐMC</option>
            </select>
        </label>
        <label class="af__field">
            <span>Số cas <b>*</b></span>
            <input type="number" name="so_cas" min="1" required inputmode="numeric">
        </label>
        <label class="af__field">
            <span>Ngày chụp</span>
            <input type="date" name="ngay_chup">
        </label>
        <label class="af__field">
            <span>Số ngày chụp</span>
            <input type="number" name="so_ngay" min="1" value="1" inputmode="numeric">
        </label>
        <label class="af__field af__field--wide">
            <span>Địa điểm chụp</span>
            <input type="text" name="dia_diem" placeholder="Quận/huyện, tỉnh hoặc địa chỉ. Để trống nếu chưa xác nhận">
        </label>
    </div>
</div>
