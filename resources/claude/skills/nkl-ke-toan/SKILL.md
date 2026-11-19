---
name: nkl-ke-toan
description: "Skill nghiệp vụ Kế toán nội bộ của Công ty TNHH Đầu tư Trang thiết bị Y tế Nam Khánh Linh (NKL / Medicen): hệ thống tài khoản, cách đọc sổ nhật ký chung / sổ quỹ / sổ ngân hàng / công nợ MISA, nguồn số liệu (sổ sách, báo cáo tài chính, web Medicen), cách tính kết quả kinh doanh, tách mảng XQ / CT, đối chiếu công nợ, kế hoạch thu chi và cách trình bày. Dùng khi người dùng hỏi về doanh thu, chi phí, lợi nhuận, lãi lỗ, dòng tiền, tồn quỹ, số dư ngân hàng, công nợ phải thu / phải trả, lương, vay, khấu hao, thuế, báo cáo tài chính, hợp đồng, kế hoạch thu chi."
---

# SKILL – KẾ TOÁN NKL

## 1. Công ty và các mảng

- **XQ**: chụp X-quang, siêu âm, đo loãng xương lưu động bằng xe (Xe 1–5, xe siêu âm 8, xe thuê 6–7) cho bệnh viện, phòng khám, doanh nghiệp. Đơn hàng, lịch xe, công nợ từng đơn quản lý trên web Medicen.
- **CT**: máy CT đặt / liên kết tại bệnh viện đối tác (Lagi – YSG, Cà Mau, Bảo Lộc, Imed…), thu theo hợp đồng liên kết / thuê. Chưa có trên web, chỉ có trong sổ sách MISA, kế hoạch thu chi và hợp đồng.
- **VP**: chi phí văn phòng / chung.
- Mã số thuế: 0307659096. Ngân hàng: HDBank (HDB, tài khoản chính), Vietcombank (VCB), Agribank (AGRI).

## 2. Nguồn số liệu — luôn ghi rõ nguồn khi trả lời

| Nguồn | Có gì | Mức cập nhật |
|---|---|---|
| Sổ sách MISA (tool `ket_qua_kinh_doanh`, `phat_sinh_tai_khoan`, `tim_but_toan`, `so_quy_ngan_hang`, `cong_no_doi_tac`) | Toàn công ty XQ + CT, từ 01/2023 | Đến ngày của file kế toán tải lên gần nhất (xem `tong_quan_du_lieu`) |
| Lương thực trả (`luong_thuc_tra`) | Tổng lương theo tháng, tách CT / XQ, thưởng lễ / Tết | Theo file lương kế toán tải lên |
| Báo cáo tài chính (`tai_lieu` loại bctc) | Số chính thức đã lập / nộp (bản Thuế và bản Nội bộ) | Theo năm |
| Web Medicen (`cong_no_web`, `thu_chi_web`) | Công nợ XQ từng hóa đơn; kế hoạch thu chi theo tháng | Hằng ngày |

Quy tắc:
- Câu hỏi về số **chính thức / đã báo cáo** (lợi nhuận năm, tổng tài sản, vốn): ưu tiên báo cáo tài chính; nếu sổ sách khác thì nêu chênh lệch và giải thích "do bút toán điều chỉnh khi lập báo cáo".
- Câu hỏi về **chi tiết / theo tháng / theo khoản**: dùng sổ sách.
- Câu hỏi về **công nợ khách XQ hiện tại**: dùng `cong_no_web` (mới nhất). Sổ MISA (`cong_no_doi_tac`) là số đến cuối kỳ sổ, dùng để đối chiếu.
- Kỳ chưa có trong sổ: nói rõ "chưa có sổ sách sau ngày …", không ngoại suy.

Báo cáo tài chính có **hai bản** mỗi năm:
- **Bản nộp thuế**: khớp với sổ sách MISA (vd 2025: doanh thu 7,92 tỷ, lỗ trước thuế 4,70 tỷ; 2024: doanh thu 9,23 tỷ, lãi trước thuế 4,30 tỷ).
- **Bản nội bộ** (gửi ngân hàng): có bút toán điều chỉnh, số khác sổ (vd 2025: chi phí quản lý 8,09 tỷ thay vì 8,58; thu nhập khác 4,59 tỷ thay vì 1,75; chi phí khác 0 thay vì 1,49; lãi trước thuế 118 triệu; 2024 nội bộ: doanh thu 10,60 tỷ, lãi trước thuế 4,64 tỷ; 6 tháng 2026 nội bộ: lãi trước thuế 6,8 triệu).
Khi trả lời về lãi lỗ năm, nêu rõ đang dùng bản nào; người dùng không nói thì đưa cả hai và giải thích chênh lệch. Tra bằng `tai_lieu` loại bctc với từ khóa năm + "thuế" / "nội bộ".

## 3. Hệ thống tài khoản thường gặp (Thông tư 200)

| TK | Tên | Ghi chú |
|---|---|---|
| 111 | Tiền mặt | Sổ quỹ |
| 112 (11211 HDB, 11212 VCB, 11213 AGRI) | Tiền gửi ngân hàng | Sổ ngân hàng |
| 131 | Phải thu khách hàng | Dư Nợ = khách còn nợ |
| 133 | Thuế GTGT được khấu trừ | |
| 138 / 1388 | Phải thu khác | |
| 141 | Tạm ứng | Tạm ứng cho TX / KTV đi đường (dầu, cầu đường…) |
| 156 | Hàng hóa | |
| 211, 214 | TSCĐ, hao mòn | Xe, máy XQ, máy CT, máy SA |
| 242 | Chi phí trả trước | |
| 331 | Phải trả người bán | Dư Có = công ty còn nợ NCC (vd Imed, Thành Phát) |
| 333 (33311 GTGT, 3334 TNDN, 3335 TNCN) | Thuế phải nộp | |
| 334 | Phải trả người lao động | Lương |
| 338 (3383 BHXH…) | Phải trả khác | |
| 341 | Vay và nợ thuê tài chính | Vay ngân hàng, cho thuê tài chính (Chailease) |
| 411, 421 | Vốn chủ sở hữu, lợi nhuận chưa phân phối | |
| 511 (5111 bán hàng, 5113 dịch vụ) | Doanh thu | Ghi Có |
| 515 | Doanh thu tài chính | Lãi tiền gửi |
| 632 | Giá vốn | |
| 635 (6351 lãi vay) | Chi phí tài chính | |
| 642 | Chi phí quản lý DN | 6421 lương nhân viên, 6422 vật liệu, 6423 đồ dùng, 6424 khấu hao, 6425 thuế phí, 6427 dịch vụ mua ngoài, 6428 bằng tiền khác |
| 711, 811 | Thu nhập khác, chi phí khác | |
| 821 | Chi phí thuế TNDN | |
| 911 | Xác định kết quả | Bút toán kết chuyển cuối kỳ — không cộng vào doanh thu / chi phí |

Sổ năm 2023 dùng tài khoản 3 số (642, 511…); từ 2024 dùng tài khoản con 4–5 số. Tool tìm theo đầu số nên hỏi "642" sẽ gồm cả 6421…6428.

## 4. Cách đọc từng loại sổ

- **Nhật ký chung**: mỗi dòng là một cặp Nợ – Có với một số tiền. Doanh thu = phát sinh Có 511; chi phí = phát sinh Nợ 6xx / 8xx; bỏ các dòng đối ứng 911 (kết chuyển).
- **Sổ quỹ tiền mặt / ngân hàng**: thu (Nợ 111/112), chi (Có 111/112), tồn sau từng dòng. Sổ tiền mặt từ 2025 có:
  - **khoản mục**: XQ, CT, VP, VAY → dùng để tách chi phí bằng tiền mặt theo mảng;
  - **mã công trình**: XE1…XE8 → chi phí theo xe (dầu, cầu đường, sửa xe…);
  - **mục thu / chi**: phí cầu đường, chi tạm ứng, nhập quỹ, thu tiền vay…
  Sổ ngân hàng và nhật ký chung không có khoản mục: muốn tách CT thì lọc diễn giải theo từ khóa (CT, Lagi, YSG, Cà Mau, Bảo Lộc, Imed) và nói rõ là ước tính theo diễn giải.
- **Tổng hợp công nợ (131 / 331)**: số dư đầu kỳ, phát sinh Nợ / Có, số dư cuối kỳ của từng đối tác trong một kỳ.
- **Sổ năm 2023**: không có cột tồn → không tính được số dư cuối từng ngày trong 2023; số dư đầu 2024 có trong sổ 2024.

## 5. Tách mảng XQ / CT

- Lương: `luong_thuc_tra` có sẵn CT / XQ.
- Chi tiền mặt: `so_quy_ngan_hang` (TIEN_MAT) chia theo khoản mục.
- **Công nợ CT từ 2026: dùng `cong_no_ct`** (số kế toán nhập theo từng bệnh viện, từng tháng; Medicen thu về là phần công ty được nhận, đã trừ phần bệnh viện). Thanh toán CT hình thức "L-VCB" là tiền vào TK VCB chú Lộc (xem `so_vcb_chu_loc`), "HDB" là vào TK HDB công ty.
- Tiền công ty đi qua TK cá nhân chú Lộc: `so_vcb_chu_loc`. Dòng "Tiền hiện có (VCB chú Lộc)" của báo cáo thu chi = số dư cuối tháng trước của sổ này. Công nợ cũ Thiên Phú (2023–2024) chỉ để ghi nhớ, không tính vào công nợ hiện tại.
- Doanh thu CT trước 2026 / đối chiếu sổ sách: tìm diễn giải có "CT" hoặc tên bệnh viện đặt máy (`tim_but_toan` / `phat_sinh_tai_khoan` 511 với tu_khoa); kế hoạch thu CT hằng tháng có trong `thu_chi_web` (nhóm "CT").
- Khấu hao máy CT: `phat_sinh_tai_khoan` 6424 / 214 với tu_khoa "CT".
- Doanh thu XQ chi tiết theo đơn vị / tháng: công nợ web (`cong_no_web`) hoặc trang Thống kê doanh thu.
- Luôn nói rõ phần nào là số trực tiếp, phần nào ước tính theo diễn giải.

## 6. Việc thường làm

**Báo cáo nhanh tháng / quý / năm**: `ket_qua_kinh_doanh` (chia theo tháng) + `so_quy_ngan_hang` (tồn quỹ, ngân hàng) + `cong_no_web` (phải thu XQ). Trình bày: doanh thu, chi phí lớn, lợi nhuận, tiền hiện có, nợ phải thu, việc cần chú ý.

**Chi phí bất thường**: `phat_sinh_tai_khoan` theo tháng của 642x / 632 / 635, chỉ ra tháng tăng mạnh, rồi `tim_but_toan` để xem bút toán lớn của tháng đó.

**Đối chiếu công nợ khách hàng**: so `cong_no_web` (theo hóa đơn) với `cong_no_doi_tac` phai_thu (theo sổ MISA) cho cùng khách; chênh lệch thường do hóa đơn chưa nhập MISA hoặc tiền về chưa gạch nợ trên web.

**Nhắc nợ**: liệt kê hóa đơn quá hạn từ `cong_no_web`, soạn tin nhắc nợ / thư đối chiếu trong khối `tin-nhan`:
> Dạ [tên], bên em gửi anh/chị đối chiếu công nợ đến ngày [ngày]: hóa đơn số [..] ngày [..] số tiền [..]; tổng còn [..]. Anh/chị kiểm tra và sắp xếp thanh toán giúp em ạ. Em cảm ơn!

**Kế hoạch dòng tiền**: `thu_chi_web` (khoản sắp đến hạn) + số dư quỹ / ngân hàng cuối kỳ gần nhất → cảnh báo nếu chi đến hạn lớn hơn tiền có + thu dự kiến.

**Vay / lãi vay**: `phat_sinh_tai_khoan` 341 (dư Có = còn nợ vay), 635 (lãi vay theo tháng).

**Hợp đồng**: `tai_lieu` loại hop_dong — bên ký, thời hạn, giá trị, điều khoản thanh toán; nhắc hợp đồng sắp hết hạn.

## 7. Trình bày

- Ghi nguồn + kỳ cho mỗi bảng số.
- So sánh nhiều kỳ: bảng có cột chênh lệch và % thay đổi.
- Số âm (lỗ) ghi dấu trừ và nói rõ "lỗ".
- Không đưa lương từng người (dữ liệu chỉ có tổng theo mảng); không đưa số tài khoản ngân hàng đầy đủ khi không cần.
