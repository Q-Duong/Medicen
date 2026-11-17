{{-- Popup hướng dẫn "Đọc bằng AI" (form tài liệu kế toán). Mở bằng nút [data-ad-help] --}}
<dialog class="ad-help" id="ad-help" aria-labelledby="ad-help-title">
    <div class="ad-help__head">
        <h2 id="ad-help-title"><i class="fas fa-magic"></i> Hướng dẫn Đọc bằng AI</h2>
        <button type="button" class="ad-help__close" data-ad-help-close aria-label="Đóng"><i class="fas fa-times"></i></button>
    </div>
    <div class="ad-help__body">
        <h3>Dùng cho tài liệu nào</h3>
        <ul>
            <li><b>Báo cáo tài chính</b> (bản thuế, bản nội bộ / gửi ngân hàng), <b>hợp đồng, phụ lục</b>, biên bản, <b>hóa đơn</b>.</li>
            <li><b>Sổ sách MISA không cần nút này</b>: tải ở tab <i>Sổ sách MISA</i>, hệ thống tự nhận loại sổ và kỳ theo mẫu xuất của MISA.</li>
        </ul>

        <h3>Các bước</h3>
        <ol>
            <li>Chọn file ở ô <b>File gốc</b>.</li>
            <li>Bấm <b>Đọc bằng AI</b>, đợi 20 giây – 2 phút (file càng dài càng lâu). Đừng đóng trang.</li>
            <li>AI điền: loại, tên, các bên, kỳ, ngày ký, hiệu lực, giá trị, tóm tắt (ô vừa điền viền cam).</li>
            <li><b>Kiểm tra lại số liệu</b>, sửa nếu cần rồi bấm <b>Lưu</b>. AI không tự lưu.</li>
        </ol>
        <p class="ad-help__note"><i class="fas fa-info-circle"></i> Trợ lý Kế toán chỉ đọc phần <b>Tóm tắt</b>, không mở file gốc — tóm tắt đủ số liệu thì trợ lý trả lời mới đúng.</p>

        <h3>Giới hạn file</h3>
        <div class="ad-help__table">
            <table>
                <thead><tr><th>Loại file</th><th>AI đọc được</th><th>Ghi chú</th></tr></thead>
                <tbody>
                    <tr><td><b>PDF</b></td><td>Tối đa <b>20 MB</b>, khoảng <b>100 trang</b></td><td>Đọc cả PDF scan (ảnh chụp từng trang). File dài hơn: tách hợp đồng và phụ lục thành file riêng.</td></tr>
                    <tr><td><b>Ảnh JPG / PNG</b></td><td>Tối đa <b>5 MB</b>, 1 ảnh</td><td>Cạnh dài không quá 8000 px. Tài liệu nhiều trang: gộp các ảnh thành 1 file PDF.</td></tr>
                    <tr><td><b>Excel .xlsx / .xls</b></td><td>Tối đa <b>30 MB</b></td><td>Đọc chữ và số của mọi sheet (khoảng 150.000 ký tự đầu), không đọc hình / biểu đồ.</td></tr>
                    <tr><td><b>Word .docx</b></td><td>Tối đa <b>30 MB</b></td><td>Chỉ đọc chữ; hình, chữ ký, con dấu trong file Word bị bỏ qua.</td></tr>
                    <tr class="is-no"><td><b>Word .doc</b> (đời cũ)</td><td>Không đọc được</td><td>Mở bằng Word → Lưu thành .docx hoặc PDF. Vẫn lưu file .doc được nhưng phải nhập tay.</td></tr>
                </tbody>
            </table>
        </div>
        <ul class="ad-help__small">
            <li>Lưu file gốc: tối đa <b>30 MB</b> mỗi tài liệu.</li>
            <li>Mỗi tài khoản bấm <b>Đọc bằng AI</b> tối đa <b>10 lần / phút</b>; mỗi lần đọc tốn một ít phí AI.</li>
            <li>File được gửi tới dịch vụ AI (Claude) để đọc; hệ thống chỉ lưu khi bạn bấm <b>Lưu</b>.</li>
        </ul>

        <h3>Mẹo để AI đọc chính xác</h3>
        <ul>
            <li>Scan thẳng, rõ nét (từ 200 dpi), không bị mờ / lóa. Bản quá mờ AI sẽ báo <i>khó đọc</i> và ghi chú phần không đọc được.</li>
            <li>BCTC: đặt tên file có chữ <b>thuế</b> hoặc <b>nội bộ</b> để AI phân biệt hai bản.</li>
            <li>Hợp đồng có nhiều phụ lục: tải mỗi phụ lục thành một tài liệu riêng để tra cứu dễ hơn.</li>
            <li>AI không bịa số; chỗ không chắc sẽ ghi <i>(không đọc rõ)</i> — nhớ bổ sung tay.</li>
        </ul>
    </div>
</dialog>
