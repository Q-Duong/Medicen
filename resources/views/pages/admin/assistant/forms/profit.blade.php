{{-- Form "Tính lợi nhuận": cùng các ô chính của trang Tính lợi nhuận X-Quang --}}
<div class="af" data-form="profit" hidden>
    <div class="af__grid">
        <label class="af__field af__field--wide">
            <span>Đơn vị</span>
            <input type="text" name="don_vi" list="assistant-units" placeholder="Không bắt buộc">
        </label>
        <label class="af__field">
            <span>Số cas XQ phổi</span>
            <input type="number" name="so_cas_xq" min="0" inputmode="numeric">
        </label>
        <label class="af__field">
            <span>Đơn giá XQ phổi</span>
            <input type="number" name="gia_xq" min="0" inputmode="numeric"
                placeholder="Mặc định {{ number_format($profitDefaults['thu_xq_gia'] ?? 0, 0, ',', '.') }}">
        </label>
        <label class="af__field">
            <span>Số cas CSTL</span>
            <input type="number" name="so_cas_cstl" min="0" inputmode="numeric">
        </label>
        <label class="af__field">
            <span>Đơn giá CSTL</span>
            <input type="number" name="gia_cstl" min="0" inputmode="numeric"
                placeholder="Mặc định {{ number_format($profitDefaults['thu_cstl_gia'] ?? 0, 0, ',', '.') }}">
        </label>
        <label class="af__field">
            <span>Số ngày công tác <b>*</b></span>
            <input type="number" name="so_ngay" min="1" value="1" required inputmode="numeric">
        </label>
        <label class="af__field">
            <span>Số đêm lưu trú</span>
            <input type="number" name="so_dem" min="0" value="0" inputmode="numeric">
        </label>
        <label class="af__field">
            <span>Tổng km đi về <b>*</b></span>
            <input type="number" name="tong_km" min="0" required inputmode="numeric">
        </label>
        <label class="af__field">
            <span>Số nhân sự đi <b>*</b></span>
            <input type="number" name="so_nguoi" min="1" value="2" required inputmode="numeric">
        </label>
        <label class="af__field">
            <span>Phụ thu (đ)</span>
            <input type="number" name="phu_thu" min="0" inputmode="numeric" placeholder="0">
        </label>
        <label class="af__field">
            <span>Chiết khấu (đ)</span>
            <input type="number" name="chiet_khau" min="0" inputmode="numeric" placeholder="0">
        </label>
    </div>
</div>
