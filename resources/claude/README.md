# Trợ lý AI theo phòng ban (Claude Managed Agents)

Mỗi phòng ban là một **Agent** lưu trên Claude, gồm:

- `agents/<phong_ban>.md`: system prompt (vai trò, quy tắc).
- `skills/<ten-skill>/SKILL.md`: kiến thức nghiệp vụ, được upload lên Claude dạng custom skill.
- Một class tool trong `app/Services/Claude/Tools/` để agent đọc dữ liệu thật của Medicen.

Agent chạy trên máy chủ Claude. Khi cần dữ liệu, agent gọi tool. Medicen chạy truy vấn trên DB local rồi gửi kết quả về. DB không mở ra Internet.

## Cài đặt lần đầu

1. Thêm `ANTHROPIC_API_KEY=...` vào `.env`. Tạo key ở https://platform.claude.com, mục API keys.
2. Dựng agent:

   ```bash
   php artisan claude:setup sales
   ```

   ID của environment, skill và agent được lưu ở `storage/app/claude/agents.json`.
3. Hỏi thử từ terminal:

   ```bash
   php artisan claude:ask sales "Tuần sau xe nào còn trống?"
   ```

4. Dùng trên web: menu **Trợ lý Sales (AI)**, đường dẫn `/admin/assistant/sales`.

## Đơn giá bảng tính lợi nhuận

- Giá gốc nằm ở `config/profit.php`. Giá đã đổi lưu trong bảng `profit_settings`, và mỗi lần đổi được ghi vào `profit_setting_changes`.
- Trang Tính lợi nhuận, server khi lưu phiếu, và trợ lý đều dùng chung `App\Services\ProfitCalculator`, nên ra cùng một kết quả.
- Trợ lý Sales:
  - Người dùng báo giá khác thì trợ lý tính lại ngay với giá đó, chỉ cho lần tính này.
  - Người dùng xác nhận muốn áp dụng lâu dài thì trợ lý đổi giá mặc định. Chỉ tài khoản Admin (role 0) được đổi.

## Khi sửa prompt, skill hoặc tool

Sửa file rồi chạy lại `php artisan claude:setup sales`.

- Skill chỉ tạo version mới khi nội dung đổi.
- Agent được cập nhật lên version mới; các cuộc trò chuyện đang mở vẫn chạy version cũ.

## Thêm phòng ban mới (ví dụ Kế toán)

1. Viết `agents/accountant.md` và skill (nếu có) trong `skills/`.
2. Tạo `app/Services/Claude/Tools/AccountantTools.php`, implement `DepartmentTools`.
3. Khai báo `'accountant' => [...]` trong `config/claude.php`.
4. Thêm `'accountant' => 'Trợ lý Kế toán'` vào `AssistantController::DEPARTMENTS`, route và menu theo quyền.
5. Chạy `php artisan claude:setup accountant`.

## Xem lại hội thoại, chi phí

Vào Claude Console → Managed Agents → Sessions. Mỗi cuộc trò chuyện là một session, có tên user và thời gian.

## Lịch sử chat

- Mỗi cuộc trò chuyện lưu ở bảng `assistant_conversations` và `assistant_messages`, ứng với một session trên Claude. Link `/admin/assistant/sales?c=<id>` mở lại đúng cuộc trò chuyện (reload không mất).
- Nhân viên chỉ thấy, đổi tên, xóa cuộc trò chuyện của mình. Admin (role 0) có thêm tab "Tất cả nhân viên": xem và xóa được, nhưng chỉ xem, không hỏi tiếp trong cuộc trò chuyện của người khác.
- Lệnh `php artisan assistant:prune` xóa cuộc trò chuyện không hoạt động quá 90 ngày (đổi bằng `CLAUDE_HISTORY_DAYS` trong `.env`), xóa luôn session trên Claude. Lệnh được lên lịch chạy 02:00 hằng ngày, nên server cần có cron `* * * * * php artisan schedule:run`.

## Phân quyền

- Cột `users.department` (danh sách ở `config/departments.php`), Admin cấp ở trang **Phân quyền phòng ban** (`/admin/user-departments`).
- Trợ lý nào cho phòng nào: `user_departments` của từng trợ lý trong `config/claude.php`. Admin (role 0, 1) dùng tất cả.

## Xếp lịch xe (`/admin/xep-lich`)

- Dành cho phòng Kỹ thuật (Technician) và Admin (`config/schedule.php` → `user_departments`).
- Lưới tuần: xe × ngày × buổi (sáng/chiều; "Cả ngày" chiếm cả hai), bảng nhân sự trống/bận/nghỉ, danh sách đơn chờ xếp (`schedule_status = 0`).
- Lịch nghỉ ghi ở bảng `staff_leaves` (trên chính trang này). Chứng chỉ hành nghề ở Quản lý nhân viên (`staff_certificate`, `staff_certificate_note`).
- Nút **Gợi ý xếp lịch**: code tính ai/xe nào trống và lập phương án theo quy tắc; nếu có API key thì Claude xem lại và giải thích (một lần gọi Messages API, không dùng Managed Agent). Mọi đề xuất được code kiểm tra trùng lịch, lịch nghỉ, đúng vai trò TX/KTV trước khi hiện và lần nữa khi bấm **Áp dụng**.
- Áp dụng ghi vào `car_ktvs` và chuyển đơn sang `schedule_status = 1`, `status_id = 1` giống trang tạo lịch cũ; có tùy chọn gửi Zalo.
- Quy tắc chỉnh trong `config/schedule.php`: xe nhà, xe siêu âm, xe/người thuê, số cas cần 2 KTV.
- Nhiều đơn của **cùng đơn vị** (`orders.unit_id`) cùng buổi được chạy chung xe / đội, không tính trùng lịch; khác đơn vị mới là trùng.
- Số ca trong tháng của mỗi TX/KTV: đơn đã nhập `order_quantity_draft` (> 0) tính "đã chụp", chưa nhập thì tính `order_quantity` là "dự kiến"; bộ phận "2 Tư thế" nhân 2. Gợi ý ưu tiên người ít ca để chia đều.
- Quãng đường (`RouteDistance`): ưu tiên số km Sale nhập khi lên đơn (`accountants.accountant_distance`, là km cả đi lẫn về, hệ thống chia đôi để ra km một chiều; nếu sau này đổi quy ước thì đặt `SCHEDULE_ORDER_KM_ROUND_TRIP=false`). Địa chỉ khách (`customers.customer_address`, nhận diện bằng `VietnamPlaces`) chỉ dùng để biết hướng khi khớp số km đơn. Hai điểm đều định vị được: tính theo toạ độ (đường chim bay × 1,35); không thì theo km từ công ty (một điểm gần công ty ≤ 40 km: lấy km điểm xa; cả hai xa, chưa rõ hướng: tính như đi qua công ty). Tốc độ trung bình 45 km/h. Ngưỡng: khác ngày `SCHEDULE_NEXT_DAY_MAX_MINUTES` (240 phút), sáng–chiều `SCHEDULE_SAME_DAY_MAX_MINUTES` (90 phút). Thử: `php artisan schedule:distance "địa chỉ 1" "địa chỉ 2"`.
- Bấm vào ô lịch đã xếp trên lưới để sửa xe / tài xế / KTV (lịch hiện tại của đơn đó không tính là bận khi kiểm tra). KTV để trống được (đơn chỉ thuê xe).
- Giữ chỗ (`schedule_holds`): bấm ô trống trên lưới để giữ xe / người cho khách đặt trước chưa lên đơn; chỗ giữ tính là bận với đơn vị khác. Đơn cùng đơn vị (khớp `unit_id`) cùng ngày được gợi ý xếp vào chỗ giữ và tự đánh dấu đã dùng; bấm ô giữ chỗ để sửa, chọn đơn xếp vào (có thể đổi ngày chụp của đơn sang ngày giữ chỗ, ghi vào `history_edits`) hoặc xóa giữ chỗ. Giữ chỗ nhiều ngày: mỗi đơn chỉ chiếm ngày của nó (chọn 1 ngày trong khoảng giữ khi đổi ngày); phần đơn dùng tách thành bản ghi `used` gắn `order_id`, các ngày còn lại vẫn `active` (giữa khoảng thì tách 2). Không đổi ngày mà đơn nằm ngoài khoảng giữ thì chỗ giữ để nguyên.
- Xe bảo trì (`car_maintenances`, migration `2026_11_09_100000`): khung "Xe bảo trì" trên trang Xếp lịch (xe, ngày, buổi, ghi chú). Xe bảo trì bận với mọi đơn (kể cả cùng đơn vị) và giữ chỗ; lưới xe hiện ô sọc "Bảo trì" (còn đơn trùng thì cảnh báo đỏ để đổi xe); tool `xem_lich_xe` của Sales trả `xe_bao_tri` và loại xe đó khỏi danh sách trống. Đổi prompt / tool thì chạy lại `php artisan claude:setup sales`.
- Nhóm đơn đi chung (`order_groups`, cột `orders.order_group_id`, migration `2026_11_09_200000`): form tạo / sửa / copy đơn có khung "Đi chung với đơn khác" gợi ý các đơn cùng đơn vị, cùng ngày (không tự tích); Sale tích thì tạo / vào nhóm mã `{mã đơn vị}-{ddmmyy}` (trùng thêm -2). Đổi ngày / đơn vị hoặc xóa đơn thì rời nhóm; nhóm còn 1 đơn tự giải tán (`App\Services\OrderGroups`). Trang Xếp lịch: mở 1 đơn của nhóm ra cả nhóm, gợi ý cùng xe / đội.
- Đổi ngày / buổi chụp của đơn đã xếp xe (sửa đơn): `App\Services\Schedule\ScheduleCancel` hủy lịch (Zalo báo hủy, xóa dòng `car_ktvs`), đơn về Chưa lên lịch (schedule_status 0, status 5); form sửa đơn hỏi xác nhận trước.
- Hủy lịch (không gửi Zalo): nút "Hủy lịch đơn này" trong form Sửa lịch ở trang Xếp lịch, nút Huỷ Lịch cũ ở trang sửa lịch đơn hàng, và khi đổi ngày / buổi đều dùng `ScheduleCancel` → đơn về Chưa lên lịch, hiện lại ở Đơn chờ. Đã bỏ toàn bộ nút / ô gửi Zalo khi xếp lịch.
- Lịch sử xếp lịch: `App\Services\Schedule\ScheduleHistory` ghi vào history_edits "Xếp lịch / Sửa lịch (nơi thao tác): Xe, TX, KTV · trước: …" (trang Xếp lịch và trang đơn hàng); sửa mà không đổi gì thì không ghi.
- Đơn từ ngưỡng `two_ktv_from_cas` chỉ cảnh báo "nên có 2 KTV", gợi ý không tự thêm KTV 2.
- Email nhắc: lệnh `schedule:remind` (cron 16:00, `SCHEDULE_REMIND_*` trong .env) gửi cho tài khoản phòng Kỹ thuật + `SCHEDULE_REMIND_TO`: đơn từ hôm nay tới `remind_days` ngày chưa xếp xe, giữ chỗ trong `remind_hold_days` ngày chưa lên đơn. Không có gì thì không gửi. Chạy thử: `php artisan schedule:remind --dry-run`.
- Ảnh lịch tuần: nút "Tải ảnh tuần" trên lưới Lịch xe → `xep-lich/anh-tuan` dựng bảng đầy đủ, trình duyệt chụp bằng html2canvas (cdnjs) thành PNG.
- Hộp thoại xác nhận / thông báo: `pages/admin/partials/dialog.blade.php` (`uiConfirm`, `uiAlert`, form `data-confirm`), dùng ở trang Xếp lịch và trang trợ lý thay cho `confirm()` / `alert()`.
- Danh sách đơn chờ: đơn trong `suggest_days` (30) ngày tới (nút Gợi ý chỉ xử lý nhóm này); nhóm thu gọn "Đã qua ngày, chưa xếp xe" (`past_days` = 60 ngày gần đây, ghi bổ sung theo thực tế, không cảnh báo quãng đường) và "Xa hơn 30 ngày" (tới `future_days`, xếp thủ công).
- Bấm vào đơn chờ để xếp thủ công (ô chọn hiện trống / bận / xa và số ca tháng); không gọi AI.
- Chấm công (`App\Services\Schedule\Timekeeping`): cùng cách tính với trang Chấm công / file Excel `Bao_cao_nhan_vien.xlsx` (EmployeeController::export dùng chung service này, đã so khớp 24 tháng). Trang Xếp lịch có khung Chấm công theo tháng (đổi tháng không tải lại trang, nút Xuất Excel, cảnh báo đơn đã chụp chưa nhập số ca thực tế). Cột "Số ca tháng" để chia đều việc khi gợi ý dùng đúng số liệu này: TX = tổng số ca, KTV = số ca KTV, cộng phần dự kiến của đơn chưa đủ điều kiện tính công.
- Chấm công sau khi chụp ngay trên trang Xếp lịch: trong khung Sửa lịch có khối "Chấm công sau khi chụp" (số ca thực tế, tài xế phụ KTV, công tác trên 250km, ghi chú KTV). Lưu qua `App\Services\Schedule\ActualCases`, dùng chung với trang lịch KTV (ScheduleTechniciansController::update), nên ghi dữ liệu và KPI phần 2 giống hệt. Các đơn trong cảnh báo "chưa nhập số ca thực tế" bấm được để mở thẳng khối này.

## File giao diện (CSS / JS tách riêng)

### Trang Xếp lịch xe

- CSS: `public/assets/css/support/org-dispatch.css` (dễ đọc) → `dispatch.css` (trang nạp). Gồm cả style của ảnh lịch tuần.
- JS: `public/assets/js/tool/dispatch/org-dispatch.js` → `dispatch.js`. Đường dẫn API và tên KTV thuê đọc từ `data-config` của `<div id="dispatch">` (Blade điền ở đầu `dispatch/index.blade.php`).

```bash
node_modules/.bin/esbuild public/assets/css/support/org-dispatch.css --minify --outfile=public/assets/css/support/dispatch.css
node_modules/.bin/esbuild public/assets/js/tool/dispatch/org-dispatch.js --minify --target=es2019 --outfile=public/assets/js/tool/dispatch/dispatch.js
```

### Trang Trợ lý AI (chat)

- CSS: `public/assets/css/support/org-assistant.css` → `assistant.css`.
- JS: `public/assets/js/tool/assistant/org-assistant.js` → `assistant.js`. Đường dẫn API, chế độ, cuộc trò chuyện mở sẵn đọc từ `data-config` của `<div id="assistant">`.

```bash
node_modules/.bin/esbuild public/assets/css/support/org-assistant.css --minify --outfile=public/assets/css/support/assistant.css
node_modules/.bin/esbuild public/assets/js/tool/assistant/org-assistant.js --minify --target=es2019 --outfile=public/assets/js/tool/assistant/assistant.js
```

### Phần Đơn hàng

- CSS: sửa ở `public/assets/css/support/org-order.css` (bản dễ đọc), trang nạp `order.css` (bản rút gọn).
- JS: sửa ở `public/assets/js/tool/order/org-order.js`, trang nạp `order.js`.
- Sửa xong thì rút gọn lại (esbuild có sẵn trong node_modules):

```bash
node_modules/.bin/esbuild public/assets/css/support/org-order.css --minify --outfile=public/assets/css/support/order.css
node_modules/.bin/esbuild public/assets/js/tool/order/org-order.js --minify --target=es2019 --outfile=public/assets/js/tool/order/order.js
```

- Khi deploy, tăng `VERSION_RESOURCE` trong `.env` để trình duyệt tải file mới (đường dẫn có `?v=`).

## Công nợ: tự lưu từng ô

- `AccountantController::saveAccountantRow` (dùng chung trang Kế toán và Sales): trang chỉ gửi các ô đã đổi + giá trị cũ + tên ô người dùng tự sửa. Ô đã bị người khác đổi trong lúc đó thì không ghi đè, trả `conflict` + giá trị mới nhất. Tính tổng sau khi lưu (lỗi tính tổng không làm mất dữ liệu).
- JS (`org-accountant.js` → `accountant.js`): mỗi dòng lưu riêng (400ms sau khi ngừng gõ), lưu lỗi thì dòng nền đỏ + thông báo + thanh "N dòng chưa lưu được · Lưu lại"; đóng trang khi còn dòng chưa lưu thì trình duyệt hỏi lại.
- Ô liên kết do máy chủ tính (`App\Services\Accountant\RowCalculator`, cùng công thức với `calculateRow` trong JS, đã so khớp 20.800 trường hợp dữ liệu thật): Thành tiền, Thành tiền CK, Lợi nhuận, Còn nợ, Trạng thái TT. Trang gửi `client=2`; máy chủ trả `computed` để trang hiện đúng.
- Ngày TT không còn tự tính từ Ngày HĐ + THCN (phép tính cũ chưa từng chạy được, chỉ làm xóa Ngày TT). Ô ngày nhập sai (có chữ, hoặc ngày không có thật) bị từ chối; cách ghi gộp kiểu "2025-04-05+25" vẫn cho lưu.
- Không cho lọc / tải lại bảng hay bấm Hoàn thành khi dòng còn số chưa lưu.
- Lưu lần lượt từng dòng (hàng đợi); trong lúc lưu khóa cả bảng (ô chỉ đọc, hiện "Đang lưu…"). Chuyển sang dòng khác thì dòng trước được lưu ngay.
- Trạng thái thanh toán: người dùng tự chọn thì giữ lựa chọn; sửa Ngày TT / Hình thức thì đủ cả hai = đã TT, thiếu = chưa TT.
- Đơn chỉ chuyển "Đã cập nhật doanh thu" khi sửa ô doanh thu (SL, đơn giá, thành tiền, VAT, CK, đã thanh toán).
- Form đơn hàng dùng chung công thức tiền (`App\Services\Accountant\OrderMoney` → `RowCalculator`): đơn mới tính CK / Lợi nhuận / Còn nợ ngay; sửa đơn chỉ tính lại khi ô tiền đổi; Còn nợ = Thành tiền − Đã thanh toán (trước đây bị đặt lại bằng Thành tiền). Đơn trạng thái 3 / 4 khóa ô tiền (form + máy chủ). Sửa đơn không còn đưa trạng thái 2/3/4 về 1.
- Bộ phận Kết quả lưu số ca thực tế (`ScheduleResultsController::update` → `OrderMoney::applyActualQuantity`): số ca đổi thì Thành tiền = SL × ĐG (trọn gói giữ Thành tiền), tính lại CK / Lợi nhuận / Còn nợ, ghi lịch sử "Cập nhật số ca thực tế … thành tiền A → B". Đơn 3 / 4 chỉ lưu số ca, không đụng tiền, và giữ trạng thái (trước đây đơn 3 bị đưa về 2).
- Trang công nợ Kế toán thao tác kiểu Excel (chỉ trang có biến `url_update_rows`, trang Sales không có): ↑ ↓ / Enter đi cùng cột, ← → sang ô bên khi con trỏ ở đầu/cuối chữ, Esc trả lại giá trị lúc vào ô; kéo chuột / Shift chọn vùng, Delete xóa vùng, Ctrl+C chép vùng; dán vùng từ Excel (tự đổi tiền "1,200,000", ngày d/m/Y, chọn theo chữ "Đã thanh toán"/"VCB"; ô khóa / sai định dạng bỏ qua và báo), dán một giá trị vào vùng thì điền cả vùng. Các dòng đổi lưu chung một lần qua `PATCH admin/accountant/update-rows` (`AccountantController::updateRows`, tối đa 300 dòng, JSON) — mỗi dòng vẫn qua `saveRowData` (kiểm tra trùng sửa, RowCalculator) như lưu từng dòng. Mã ở cuối `org-accountant.js` (mục BẢNG KIỂU EXCEL), CSS `.acc-sel*`, `.acc-cell-bad`.
- Trang công nợ Kế toán ẩn / hiện cột và cố định cột (cuối `org-accountant.js`, mục ẨN / HIỆN CỘT): nút "Cột" trên bảng + chuột phải vào tiêu đề cột; lưu theo máy trong localStorage `acc_columns_v1` (theo tên cột). Vị trí cột cố định do JS tính theo bề rộng thật (thẻ `<style id="acc-col-style">`), bỏ vị trí đặt cứng của `.sticky-col` (trước bị chồng cột). Phím mũi tên / dán / xóa / chép bỏ qua cột đang ẩn.
- Trang công nợ Kế toán: đầu trang `.acc-head` (cùng kiểu trang Đơn hàng, chứa chọn Năm / Loại + Xuất Excel / Misa), thẻ tổng số `.acc-stats` (giữ id `total-*`), bảng trong thẻ trắng. Nút kéo điền `.acc-fill` ở góc ô / vùng đang chọn có dữ liệu: kéo lên / xuống tới dòng nào thì điền tới đó (vùng nhiều dòng lặp lại theo thứ tự), ô trống thì kéo để xóa trống; bấm đúp điền hết các dòng đang hiện bên dưới (hỏi lại khi > 10 dòng, ô trống luôn hỏi, dùng `uiConfirm` — trang có include `partials.dialog`). Lưu chung qua lưu nhiều dòng.
- Trang công nợ Kế toán: Ctrl+Z / Ctrl+Y (Cmd trên Mac) hoàn tác / làm lại sửa ô, dán, điền, xóa vùng (tối đa 50 bước, chỉ trong lần mở trang); thao tác nhiều ô hỏi lại (`uiConfirm`), một ô làm ngay; chỉ trả lại ô chưa bị sửa tiếp. Đang gõ dở trong ô thì Ctrl+Z là hoàn tác chữ của trình duyệt. Mọi ô có `autocomplete="off"` (Chrome chỉ tắt danh sách đã nhập với "off", mã lạ thì vẫn hiện). Bấm chuột lần đầu vào ô chữ thì chọn hết chữ (gõ là thay), bấm lần hai mới đặt con trỏ.
- Trang công nợ Kế toán: `.acc-page` có nền trắng bo tròn + lề 15px như trang Xếp lịch. Thông báo `accToast(msg|[tóm tắt, chi tiết...], type)` có nút ×; lỗi giữ 15 giây, cảnh báo 8 giây; lưu nhiều dòng thì liệt kê tối đa 4 đơn lỗi kèm lý do.
- Nút Hoàn thành (`AccountantController::complete`): khóa dòng đơn + dòng thống kê (`lockForUpdate`) nên bấm 2 lần / 2 tab không cộng doanh thu 2 lần; lần sau trả `already`. Trang: hỏi lại trước khi chốt (`uiConfirm`), chặn khi dòng còn số chưa lưu (đang chờ, trong hàng đợi, đang lưu, lưu lỗi) hoặc bảng đang lưu, báo lỗi bằng thông báo thay cho `alert`.
- Sửa ngày / buổi ở form đơn hàng: chỉ hủy lịch xe khi đơn CHƯA chụp; đơn 2/3/4 (`ScheduleCancel::SHOT_STATUSES`) giữ lịch (dữ liệu chấm công), form ghi rõ. Bộ phận Kết quả lưu ở đơn đã chốt (3): giữ nguyên số ca (không lệch doanh thu đã chốt), các thông tin kết quả khác vẫn lưu.
- Số ca đổi sau khi cập nhật doanh thu (migration `2026_11_14_100000_add_qty_change_flag_to_orders`, chạy bằng `--path`): Kết quả nhập số ca khác ở đơn 4 thì lưu số ca, giữ tiền, ghi `orders.qty_changed_from` (số ca lúc tính tiền) + lịch sử. Trang Công nợ: nhãn "130 → 140" dưới ô số ca → "Tính lại tiền theo số ca mới" (`OrderMoney::recalcForQuantity`) / "Giữ tiền cũ" (`PATCH accountant/qty-change`), nút lọc `qty_changed=1`, nhắc khi Hoàn thành. Kế toán tự sửa số ca / đơn giá / thành tiền cũng bỏ đánh dấu; Kết quả trả về đúng số cũ thì tự bỏ.
- Đơn đã hoàn thành (status 3, `Order::STATUS_CLOSED`, `Order::closedMessage()`): khóa hết. Form sửa đơn chỉ xem (`<fieldset disabled>`, không có nút Cập nhật); chặn ở máy chủ: sửa / xóa đơn, Thông tin báo cáo, thêm / sửa / hủy lịch (trang cũ + Xếp lịch xe: `editPlans` bỏ đơn đã hoàn thành, `apply` khi sửa, `cancelSchedule`), chấm công (Xếp lịch + trang KTV), Kết quả, công nợ. Danh sách đơn: chỉ còn Xem, khóa, Copy. Thông báo lỗi chuyển trang dùng `->with('error', ...)` (layout chỉ hiện `success` / `error`).
- Lưới tuần Xếp lịch: ô của đơn đã hoàn thành tô xanh (`slot--done`, cả ô đều hoàn thành; `slot--has-done` vạch xanh khi chỉ một số đơn), có chú thích "Hoàn thành". `ScheduleBoard::grid` thêm `closed`. Danh sách đơn đã bỏ nút "Thông tin báo cáo" (Trọn gói / Qua đêm / Km sửa ở form Cập nhật đơn, TX phụ KTV sửa ở chấm công Xếp lịch); route `accountant.order.update` vẫn giữ.
- Form sửa đơn: ai cũng có nút Cập nhật (bỏ xét quyền / ngày chụp đã qua), trừ đơn đã hoàn thành. Đơn 2 / 4 (`Order::QTY_LOCKED_STATUSES`) khóa số lượng + cả phần bác sĩ đọc phim / in phim / hình thức in phim / in phiếu / hình thức in phiếu / in phiếu theo mẫu / phim & phiếu (`Order::PRINT_LOCKED_FIELDS`) + cảnh báo đơn hàng, ở form và máy chủ. Lọc danh sách đơn có ô "Thông tin đơn" (`complete`: chưa hoàn thành / đã đủ thông tin, kèm số đơn).
- Lọc danh sách đơn: `App\\Services\\OrderFilters` dùng chung cho danh sách, thẻ đếm, gợi ý ô lọc (`GET admin/order/filter-options`). Ô Nhóm đi chung (mã nhóm / chỉ đơn đi chung / không đi chung). Ô chọn liên kết: mỗi ô gợi ý theo các ô khác, kèm số đơn. Từ khóa là số = đúng mã đơn; tìm cả tên viết tắt đơn vị và mã nhóm.
- Form sửa đơn: Admin (role 0) sửa được mọi ô đang khóa ở đơn 2 / 4 (số lượng, tiền, phần in phim / phiếu / cảnh báo), có ghi chú "Quyền Admin"; tiền tính lại và bỏ cảnh báo số ca đổi. Đơn đã hoàn thành vẫn khóa với mọi người; đơn này ẩn nhãn "đã khóa" từng ô (đã có thông báo chung).
- Form đơn: gõ số lượng hay đơn giá đều tự tính Thành tiền + CK + lợi nhuận ngay; số lượng tự thêm dấu chấm (máy chủ bỏ dấu khi lưu). Ô % chiết khấu: số tiền từ 4 chữ số tự thêm dấu chấm (cả dạng /ca). Thanh thống kê trạng thái danh sách đơn đổi sang tông trắng / be (override trong `org-order.css`).
- Trang Phân quyền phòng ban: giao diện chung (CSS `org-departments.css` → `departments.css`): nền trắng có lề, đầu trang vạch cam, thẻ trợ lý → phòng ban, bảng trong thẻ, điện thoại mỗi tài khoản một thẻ. Thông báo lưu xong dùng popup chung của layout.
- Quản lý nhân viên: sidebar bấm thẳng vào danh sách (bỏ menu con). Danh sách / thêm / sửa cùng kiểu chung (CSS `org-staff.css` → `staff.css`, form dùng chung `staff/_form.blade.php`): lọc nhanh theo vai trò, tìm tên / SĐT, lọc CCHN; xóa hỏi lại bằng `uiConfirm`, có nút Xóa trong trang sửa. "KTV Thuê" / "TX Thuê" (`StaffController::hiredNames`) không xóa, không đổi tên / vai trò được.
- Quản lý tài khoản (`AccountController`, `admin/accounts`, chỉ Admin): danh sách lọc theo loại / tìm kiếm; thêm / sửa tên, email, mật khẩu (để trống = giữ; đổi thì bỏ remember_token), loại, phòng ban, thông tin cá nhân (bảng `profiles`); xóa (xóa luôn profile riêng). Chặn tự xóa / tự hạ quyền, luôn còn ≥ 1 Admin. Form nhắc các tên tài khoản đang dùng để phân quyền (`AccountController::SPECIAL_NAMES`: Admin, Sale, Office, Technician, Accountant).
- Quản lý đơn vị: sidebar bấm thẳng; danh sách / thêm / sửa cùng kiểu nhân viên (dùng chung `staff.css`), có tên viết tắt + mã số thuế trong form, số đơn mỗi đơn vị, lọc có / chưa có đơn, sắp xếp; đơn vị đã có đơn không xóa được; mã trùng chỉ chặn khi thêm / đổi mã (dữ liệu cũ có vài mã trùng).
- Đã bỏ mục "Chấm công" (employee_timekeeping) khỏi mọi sidebar (route vẫn còn).
- Tên hiển thị `User::display_name` = họ tên profile (`profile_firstname` là họ + tên đệm, `profile_lastname` là tên), chưa có profile thì tên tài khoản. Trang chat AI dùng tên này (lời chào, danh sách / xem cuộc trò chuyện của người khác); thông tin gửi sang Claude vẫn dùng tên tài khoản.
- Lịch sử chỉnh sửa (`HistoryController`): giao diện chung (`staff.css`), lọc mã đơn / người sửa / nội dung / khoảng ngày, hiện họ tên người sửa (theo email), nhãn màu theo loại thao tác, bấm mã đơn để xem toàn bộ lịch sử đơn đó.
- Tính lợi nhuận X-Quang: giao diện chung (CSS `org-profit.css` → `profit.css`), bỏ Bootstrap 5 tải từ CDN; giữ nguyên id / class mà `calculation.js` dùng (tính toán, lưu, xem lại, duyệt, xóa không đổi).
- Khung trang (header + sidebar): `assets/css/support/org-shell.css` → `shell.css`, nạp trong `layouts/default_auth` cho mọi trang. Header mới (logo, nút ☰, ô tài khoản có họ tên + loại + phòng ban; bỏ các ô thông báo / thư / công việc mẫu và ô tìm kiếm không dùng); header cao 64px (đã chỉnh cả phần màn hình nhỏ). Sidebar: biểu tượng riêng từng mục, mục đang mở nền cam nhạt + vạch cam. "Quản lý chỉnh sửa đơn hàng" đổi tên thành "Lịch sử chỉnh sửa".
- Thống kê doanh thu (`pages/admin/index`): giao diện chung (`org-dashboard.css` → `dashboard.css`), JS mới `assets/js/tool/org-dashboard.js` → `dashboard.js` (thay `backend/js/chart/chart.min.js`): nút nhanh Tháng này / 7 ngày / Tháng trước / 365 ngày, lọc theo ngày / đơn vị, thẻ Doanh thu / Lợi nhuận (+ tỷ suất) / Số đơn / Số ca, biểu đồ cột Doanh thu & Lợi nhuận. API thống kê trả thêm `total_profit`, `total_quantity`, `profit` từng ngày (trước đây ô Lợi nhuận hiện nhầm doanh thu).
- Tính KPI (`pages/admin/statistics/index`): giao diện chung, giải thích 3 phần. Sửa tính ngày đầu / cuối tháng (tháng 9 trước bị "-00-30"; ngày 31 có thể nhảy sang tháng sau). KPI phần 1 (Sales) hiện không còn chỗ ghi nhận.
- Chuông "Việc cần làm" trên header (`App\Services\Notifications\TodoList`, `GET admin/notifications/todo`, JS `assets/js/tool/org-shell.js` → `shell.js` nạp ở layout): đếm trực tiếp từ dữ liệu (không có bảng thông báo / đã đọc), cache 60 giây mỗi tài khoản, trang hỏi lại mỗi phút; `?fresh=1` bỏ cache. Nhóm theo quyền: Admin tất cả; Kế toán (role 3 / ke_toan): số ca thay đổi sau cập nhật doanh thu, chờ cập nhật doanh thu, công nợ quá hạn 45 / 60 ngày, phiếu lợi nhuận chờ duyệt; Sales: chụp trong 3 ngày chưa lên lịch, bị hủy lịch cần xếp lại, đơn "Chưa hoàn thành"; Xếp lịch (schedule.user_departments): chưa xếp xe trong 3 ngày, chưa chấm công (60 ngày), xe bảo trì 7 ngày tới; Kết quả: chưa nhập số ca thực tế, quá hạn giao kết quả (60 ngày). Trang Công nợ nhận `?qty_changed=1` để bật sẵn bộ lọc số ca thay đổi.
- Trang "Tài khoản của tôi" (`AdminController::information`, view `pages/admin/setting/account`; `settings` chuyển về trang này; lưu ở `store_information`): sửa thông tin cá nhân + đổi mật khẩu (phải nhập đúng mật khẩu hiện tại; đổi xong bỏ "ghi nhớ đăng nhập" ở máy khác). Đã xóa view cũ `setting/index` và route debug `hash-password` (có mật khẩu dạng chữ trong code). Chuông thông báo: công nợ quá hạn chỉ tính đơn chụp trong 1 năm gần đây.
- Chuông: các mục Kế toán mở trang Công nợ lọc sẵn (`?todo=wait_revenue|overdue`, `?qty_changed=1`; `AccountantBuilder` bỏ lọc năm khi có `todo`, trang hiện nhãn "Đang lọc … · Bỏ lọc") và đếm bằng chính bộ lọc đó nên số khớp với trang. Nhóm Xếp lịch có thêm "Đơn đã qua ngày chụp mà chưa xếp xe" (config schedule.past_days).
- Lịch tài xế / KTV (`schedule/drivers`, `schedule/technicians`): địa chỉ trong popup đơn là link mở bản đồ (tab mới) khi khách có `customer_location` (link Google Maps hoặc toạ độ "lat,lng" → google.com/maps?q=); khách không có toạ độ thì địa chỉ chỉ là chữ. `OrderBuilder` lấy thêm `customer_location` cho 3 truy vấn lịch; JS `technologist/org-schedule.js` (`mapLinkFrom`, `setAddress`) → `schedule.js`; CSS `.event-map-link` trong `css/built/schedule.css`.
- Chat trợ lý trên web chạy kiểu "gửi rồi hỏi lại" (hosting LiteSpeed cắt request giữ kết nối lâu → lỗi 500): `POST assistant/{dept}/ask` chỉ gửi câu hỏi (`DepartmentAgentRunner::begin`) và trả 202; trình duyệt gọi `POST assistant/{dept}/conversations/{id}/poll` mỗi 1,5 giây, mỗi lần `runner->poll()` đọc event mới (list, không stream), chạy tool đang chờ, xong thì lưu câu trả lời. Trạng thái lượt nằm trong cache `assistant:turn:{id}` (+ lock chống chạy tool 2 lần); đang chờ thì `ask` trả 409; tải lại trang giữa chừng thì `messages` báo `pending` và JS hỏi tiếp. `runner->ask()` (stream) vẫn dùng cho `claude:ask`.
- Xóa tài khoản (`AccountController::destroy`): tài khoản còn dòng KPI (`performance_analysis`, khóa RESTRICT) thì chặn bằng thông báo thay vì lỗi 500; xóa được thì xóa luôn các cuộc trò chuyện Chat AI của người đó (`AssistantConversation::purge`, cả session Claude). Sửa lịch ở trang Xếp lịch (`ScheduleSuggester::options/validate`, `retiredOnOrder`): TX/KTV đang xếp mà đã bị xóa khỏi Quản lý nhân viên vẫn hiện "(đã nghỉ)", được giữ khi lưu (kèm SĐT cũ trên lịch, `DispatchController::apply`); chọn người đã xóa khác thì vẫn bị chặn.
- Skill `nkl-sale` (rà soát 15/11/2026): bỏ bảng gán Xe → TX cố định (lấy nhân sự từ `xem_lich_xe`, ghi đủ họ tên, không nhắc người đã nghỉ / chuyện riêng); bỏ "báo giá đệm"; quy trình lịch theo web (Kỹ thuật giữ chỗ + xếp xe, không còn file Excel); công nợ xem trên web; bảng km phụ thu (Bình Dương ~30 km), số tiền tính bằng `tinh_loi_nhuan`; KTV thuê ngoài không còn danh sách tên, tính chung là "KTV Thuê"; "cas" → "ca". Sửa skill xong phải chạy `php artisan claude:setup sales` (production) để agent dùng bản mới.
- Font Awesome 6: bỏ kit `kit.fontawesome.com/4b68e3663c.js` (kit chỉ cho phép localhost → production bị 403, mất icon `fa-solid` / `fa-regular` / tên FA6); `default` và `default_auth` nạp FA 6.7.2 Free + v4-shims từ cdnjs (giống cấu hình kit: free, css, shim v4).
- Thanh cuộn bảng công nợ (`.table-scroll`, cả trang Sales / Kết quả): kiểu macOS — mảnh, bo tròn, không nút mũi tên, ẩn khi đứng yên; hiện khi đang cuộn hoặc rê chuột tới mép (JS gắn `.sb-on`, cuối `org-accountant.js`). Ô Đơn vị / Tên Cty cố định đổi `overflow: scroll` → `auto` (Windows từng hiện thanh cuộn trong từng ô).
- Email Báo cáo thu chi đặt trong `.env` (`config/reports.php`): `REPORT_REMIND_TO` (lệnh `reports:remind`, 10:00) và `REPORT_UPDATED_TO` (cập nhật dòng báo cáo có chọn gửi mail), nhiều email cách nhau dấu phẩy; để trống thì không gửi và ghi log cảnh báo. Form Liên hệ trang chủ: `CONTACT_MAIL_TO`, `CONTACT_MAIL_CC` (`config/contact.php`; trống thì ghi đăng ký vào log).
- Phòng ban **Điều phối xếp lịch** (`dieu_phoi`, `config/departments.php`): `config('schedule.user_departments')` = `['dieu_phoi']` quyết định trang Xếp lịch xe (route `department:` đọc từ config), link sidebar, nhóm việc xếp lịch trên chuông, người nhận mail `schedule:remind`. Tài khoản KTV giữ `ky_thuat` (vào lịch KTV theo tên Technician, không vào được Xếp lịch). Deploy xong phải đổi phòng ban tài khoản xếp lịch sang Điều phối xếp lịch.
- Trang Tính lợi nhuận X-Quang: chỉ Admin + phòng ban trong `config('profit.user_departments')` (sales, ke_toan); route `profit.*` có middleware `department:`, link sidebar ẩn với phòng khác. Trang Công nợ Sales làm lại giao diện giống Công nợ Kế toán (acc-head, thẻ tổng, cột Nhóm), vẫn chỉ xem; bỏ cột "Thao tác" (dòng không có nút).
- Phân quyền ở server theo nhóm trang: middleware `access:<nhóm>` (`App\Http\Middleware\PageAccess`, bảng `config/access.php`: phòng ban, tên tài khoản kiểu cũ, role). Thay `isSale` (cho qua mọi role 2) / `isAccountant` ở các nhóm Đơn hàng, Công nợ Kế toán / Sales / Kết quả, Thu chi, Hợp đồng, Xuất Excel/Misa, KPI, upload; Đơn vị, Khách hàng, Bài viết, Chấm công: chỉ Admin. Route công khai `test-zalo`, `clear-cache`, `clear/*`, `upload-image-ck` giờ cần đăng nhập Admin.
- Trang 403 (`resources/views/403.blade.php`) làm lại: thẻ giữa trang, icon khóa, tên + phòng ban tài khoản, nút Quay lại / Về trang chủ. Quản lý đơn vị: nhóm quyền `units` (Kế toán) thêm / sửa; xóa (`unit.destroy`) chỉ Admin, nút xóa ẩn với người không phải Admin; link sidebar hiện theo `PageAccess::allows`.
- Trang Công nợ bộ phận Kết quả (`accountant-result`) làm lại giao diện giống Công nợ Kế toán (acc-head, thẻ Doanh thu / Số lượng / Phim, nhãn Chỉ xem, chỉ Xuất Excel). Đơn vị có thêm cột `units.unit_address` (migration `2026_11_16_100000_add_address_to_units_table.php`): form thêm / sửa, cột Địa chỉ trong danh sách, tìm theo địa chỉ; link "x đơn" chỉ hiện cho người vào được Đơn hàng; nút xóa ở trang sửa chỉ Admin.
- Sửa lỗi cập nhật lịch bộ phận Kết quả ("filename on null"): route `file/*` cho cả nhóm `debt_result` (trang lịch Kết quả tải file kết quả lên qua `file.process`); `ScheduleResultsController::update` kiểm tra file kết quả TRƯỚC khi lưu (không còn lưu dở số ca / tiền), tên file trùng file đang có = không đổi; lỗi có lời nhắn thì JS báo và giữ form.
- Xếp lịch xe: lưới tuần có đường kẻ dọc ngăn giữa các ngày (`.day-end` trên ô chiều / ô gộp và tiêu đề ngày; bảng Nhân sự trống / bận dùng `.day`, `org-dispatch.css`). Link bản đồ trên lịch TX / KTV (`mapLinkFrom`) nhận thêm link thiếu `https://` (google.com/maps/..., maps.app.goo.gl/...) và toạ độ lẫn trong chữ.
- Ô chọn ngày / giờ không tràn trên điện thoại: `assets/css/support/org-form-fix.css` → `form-fix.css`, nạp ở cả `default_auth` và `default` (min-width 0, max-width 100%; iOS Safari bỏ appearance mặc định).
- Nhắc "Đơn đã qua ngày chụp chưa chấm công" (chuông `TodoList` + `Timekeeping::missingActual` trang Xếp lịch) chỉ tính đơn chụp từ `config('schedule.timekeeping_from')` (`SCHEDULE_TIMEKEEPING_FROM`, mặc định 2026-11-01). Đơn 01–10/2026 điền số ca thực tế bằng SQL một lần (`storage/app/dong-bo-cong-no-2026/dien_so_ca_thuc_te_truoc_T11.sql`, không commit).
- Trang công nợ Sales (`accountant-sales`) chỉ xem và lọc: mọi ô khóa (`sales/data_render` `$shouldDisable = true`), `updateRowSales` trả 403 kèm thông báo; nhãn "Chỉ xem" ở tiêu đề.
- Hết phiên ở trang công nợ: `GET /csrf-token` lấy mã bảo mật mới, trang tự thử lại; nút "Lưu lại" cũng lấy mã mới trước.

