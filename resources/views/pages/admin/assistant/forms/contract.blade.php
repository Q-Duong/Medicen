{{-- Form "Soạn hợp đồng": ghép thành yêu cầu gửi trợ lý (tool soan_hop_dong tạo file Word theo mẫu công ty) --}}
<div class="af" data-form="contract" hidden>
    <div class="af__grid">
        <label class="af__field">
            <span>Loại hợp đồng <b>*</b></span>
            <select name="loai" data-af-switch>
                <option value="nguyen_tac">Nguyên tắc (dài hạn)</option>
                <option value="thoi_vu">Thời vụ (một đợt)</option>
            </select>
        </label>
        <label class="af__field af__field--wide" data-af-new="0">
            <span>Đơn vị (Bên A) trên web <b>*</b></span>
            <input type="text" name="don_vi" list="assistant-units" data-af-required placeholder="Tên hoặc viết tắt, vd: PK 115 Y DƯỢC">
        </label>
        <label class="af__field af__field--wide" data-af-new="1" hidden>
            <span>Tên Bên A <b>*</b></span>
            <input type="text" name="ten_ben_a" data-af-required placeholder="Tên đầy đủ theo giấy phép kinh doanh">
        </label>
        <label class="af__field">
            <span>Làm giống hợp đồng của</span>
            <input type="text" name="dua_theo" list="assistant-contract-units" placeholder="Trống = HĐ cũ của chính đơn vị">
        </label>

        <label class="af__check af__field--full">
            <input type="checkbox" name="khach_moi" value="1" data-af-switch> Khách mới chưa có trên web
        </label>
        <label class="af__field" data-af-new="1" hidden>
            <span>MST</span>
            <input type="text" name="mst" inputmode="numeric">
        </label>
        <label class="af__field" data-af-new="1" hidden>
            <span>Mã viết tắt</span>
            <input type="text" name="ma" placeholder="Vd PKHB (trong số HĐ)">
        </label>
        <label class="af__field af__field--wide" data-af-new="1" hidden>
            <span>Địa chỉ</span>
            <input type="text" name="dia_chi">
        </label>

        <label class="af__field">
            <span>Người đại diện</span>
            <input type="text" name="dai_dien" placeholder="Vd Ông Nguyễn Văn A">
        </label>
        <label class="af__field">
            <span>Chức vụ</span>
            <input type="text" name="chuc_vu" placeholder="Vd Giám đốc">
        </label>
        <label class="af__field">
            <span>Điện thoại Bên A</span>
            <input type="text" name="dien_thoai" inputmode="tel">
        </label>
        <label class="af__field">
            <span>Ngày ký</span>
            <input type="date" name="ngay_ky">
        </label>

        <label class="af__field af__field--full" data-af-only="nguyen_tac">
            <span>Thay đổi giá so với hợp đồng mẫu</span>
            <textarea name="doi_gia" rows="2" placeholder="Vd: bậc 100–149 ca còn 21.000/ca; bậc dưới 50 ca trọn gói 2.200.000/ngày. Để trống = giữ nguyên giá mẫu"></textarea>
        </label>
        <label class="af__field" data-af-only="nguyen_tac">
            <span>Thanh toán trong (ngày)</span>
            <input type="number" name="thanh_toan" min="1" max="120" inputmode="numeric" placeholder="Theo HĐ mẫu">
        </label>
        <label class="af__field" data-af-only="nguyen_tac">
            <span>Thời hạn</span>
            <select name="thoi_han">
                <option value="">Theo hợp đồng mẫu</option>
                <option value="0">Đến khi một bên thanh lý</option>
                <option value="12">12 tháng, tự gia hạn</option>
                <option value="12-khong">12 tháng, không tự gia hạn</option>
            </select>
        </label>

        <label class="af__field" data-af-only="thoi_vu" hidden>
            <span>Ngày chụp <b>*</b></span>
            <input type="date" name="ngay_chup" data-af-required>
        </label>
        <label class="af__field" data-af-only="thoi_vu" hidden>
            <span>Giá trọn gói (đ) <b>*</b></span>
            <input type="number" name="gia_tron_goi" min="0" inputmode="numeric" data-af-required placeholder="Đã gồm VAT">
        </label>
        <label class="af__field af__field--wide" data-af-only="thoi_vu" hidden>
            <span>Công ty được chụp</span>
            <input type="text" name="cong_ty" placeholder="Nếu khác Bên A">
        </label>
        <label class="af__field af__field--full" data-af-only="thoi_vu" hidden>
            <span>Địa điểm chụp</span>
            <input type="text" name="dia_diem_chup">
        </label>
    </div>
</div>
