<?php

/*
 * Nhật ký hoạt động (App\Http\Middleware\RecordActivity): mọi thao tác thay đổi dữ liệu trên web (POST / PUT / PATCH / DELETE),
 * tải file / xuất Excel, đăng nhập / đăng xuất, lượt mở trang chính. Xem App\Services\Activity\ActivityTimeline.
 * Không ghi mật khẩu, nội dung câu hỏi trợ lý AI, số điện thoại (chỉ ghi "đã đổi").
 */
return [
    // Giữ nhật ký bao nhiêu tháng (php artisan activity:prune, chạy hằng tháng)
    'keep_months' => 12,

    // Mở lại cùng một trang trong bấy nhiêu giây thì không ghi thêm lượt
    'visit_dedupe_seconds' => 120,

    // Hai thao tác cách nhau quá bấy nhiêu phút thì coi là nghỉ (không tính vào thời gian hoạt động)
    'idle_minutes' => 15,

    // Giờ làm việc: thao tác ngoài khung này (hoặc Chủ nhật) được nêu là "ngoài giờ"
    'work_hours' => [7, 19],

    // Sửa tiền từ mức này trở lên thì nêu là đáng chú ý
    'big_money' => 5000000,

    // Trang (khu vực) theo tiền tố tên route — tên trang hiện trên nhật ký
    'areas' => [
        'order.' => ['don_hang', 'Đơn hàng'],
        'excel-uploads.' => ['don_hang', 'Đơn hàng'],
        'file.' => ['don_hang', 'Đơn hàng'],
        'dispatch.' => ['lich_xe', 'Xếp lịch xe'],
        'schedule.' => ['lich_xe', 'Lịch xe'],
        'schedules.sales.' => ['lich_xe', 'Lịch xe Sales'],
        'schedules.technicians.' => ['lich_xe', 'Lịch xe KTV'],
        'schedules.drivers.' => ['lich_xe', 'Lịch xe Tài xế'],
        'schedules.results.' => ['ket_qua', 'Kết quả'],
        'accountant_result.' => ['ket_qua', 'Kết quả'],
        'accountant_sales.' => ['cong_no', 'Công nợ Sales'],
        'accountant.' => ['cong_no', 'Công nợ'],
        'export.' => ['cong_no', 'Công nợ'],
        'reports.' => ['bao_cao_thu_chi', 'Báo cáo thu chi'],
        'ct.' => ['cong_no_ct', 'Công nợ CT'],
        'vcb.' => ['so_vcb', 'Sổ VCB chú Lộc'],
        'accounting.' => ['du_lieu_ke_toan', 'Dữ liệu kế toán'],
        'finance.' => ['tai_chinh', 'Phân tích tài chính'],
        'director.' => ['tai_chinh', 'Tổng quan điều hành'],
        'contracts.' => ['hop_dong', 'Hợp đồng'],
        'unit.' => ['don_vi', 'Đơn vị'],
        'customer.' => ['don_vi', 'Khách hàng'],
        'staff.' => ['nhan_su', 'Nhân sự'],
        'employee_timekeeping.' => ['nhan_su', 'Chấm công'],
        'accounts.' => ['tai_khoan', 'Tài khoản'],
        'user-departments.' => ['tai_khoan', 'Phân quyền'],
        'profit.' => ['loi_nhuan', 'Tính lợi nhuận'],
        'task.' => ['nhiem_vu', 'Nhiệm vụ'],
        'assistant.' => ['tro_ly_ai', 'Trợ lý AI'],
        'statistics.' => ['thong_ke', 'Thống kê'],
        'dashboard.' => ['thong_ke', 'Thống kê'],
        'history.' => ['don_hang', 'Lịch sử đơn'],
        'news_admin.' => ['website', 'Tin tức'],
        'sales_logs.' => ['nhat_ky_sales', 'Nhật ký trao đổi khách'],
        'settings' => ['cai_dat', 'Cài đặt'],
        'store-information' => ['cai_dat', 'Cài đặt'],
        'information' => ['cai_dat', 'Thông tin cá nhân'],
        'notifications.' => ['thong_bao', 'Thông báo'],
        'admin.home' => ['trang_chu', 'Trang chủ'],
    ],

    // Tên việc theo route (không có thì ghép "Thêm / Sửa / Xóa" + tên trang)
    'labels' => [
        'order.store' => 'Thêm đơn', 'order.store_copy' => 'Copy đơn', 'order.update' => 'Sửa đơn', 'order.destroy' => 'Xóa đơn',
        'order.print' => 'In đơn',
        'excel-uploads.store' => 'Tải danh sách Excel', 'excel-uploads.update' => 'Sửa danh sách Excel', 'excel-uploads.destroy' => 'Xóa danh sách Excel',
        'excel-uploads.status' => 'Đổi trạng thái danh sách Excel', 'excel-uploads.download' => 'Tải file danh sách',
        'file.delete_file_order' => 'Xóa file đính kèm đơn', 'file.delete_file_total' => 'Xóa tất cả file đính kèm đơn',
        'dispatch.apply' => 'Xếp lịch xe', 'dispatch.cancel' => 'Hủy lịch xe', 'dispatch.actual' => 'Chấm công thực tế',
        'dispatch.holds.store' => 'Giữ chỗ lịch xe', 'dispatch.holds.update' => 'Sửa giữ chỗ', 'dispatch.holds.destroy' => 'Xóa giữ chỗ',
        'dispatch.leaves.store' => 'Thêm ngày nghỉ nhân sự', 'dispatch.leaves.destroy' => 'Xóa ngày nghỉ nhân sự',
        'dispatch.maintenances.store' => 'Thêm lịch bảo trì xe', 'dispatch.maintenances.destroy' => 'Xóa lịch bảo trì xe',
        'schedule.store' => 'Thêm lịch chi tiết', 'schedule.update' => 'Sửa lịch chi tiết', 'schedule.cancel' => 'Hủy lịch',
        'schedules.results.update' => 'Cập nhật kết quả / số ca thực tế', 'schedules.technicians.update' => 'Cập nhật lịch KTV',
        'accountant.update' => 'Sửa công nợ', 'accountant.update_rows' => 'Sửa nhiều dòng công nợ', 'accountant.complete' => 'Hoàn thành đơn (chốt công nợ)',
        'accountant.qty_change' => 'Xử lý số ca đổi', 'accountant_sales.update' => 'Sửa công nợ Sales', 'accountant.order.store' => 'Lưu thông tin báo cáo đơn',
        'export.excel' => 'Xuất Excel công nợ', 'export.misa' => 'Xuất file MISA', 'reports.export_pdf' => 'Xuất PDF báo cáo thu chi',
        'reports.update' => 'Sửa báo cáo thu chi', 'reports.update_order' => 'Sắp xếp báo cáo thu chi', 'reports.seed_default' => 'Tạo khung báo cáo thu chi',
        'sales_logs.store' => 'Ghi nhật ký trao đổi khách', 'sales_logs.update' => 'Sửa nhật ký trao đổi khách', 'sales_logs.destroy' => 'Xóa nhật ký trao đổi khách',
        'sales_logs.followups.store' => 'Thêm việc Sales', 'sales_logs.followups.update' => 'Cập nhật việc Sales', 'sales_logs.followups.destroy' => 'Xóa việc Sales',
        'sales_logs.digest' => 'Tóm tắt ngày Sales',
        'news_admin.store' => 'Thêm tin tức', 'news_admin.update' => 'Sửa tin tức', 'news_admin.destroy' => 'Xóa tin tức',
        'news_admin.toggle' => 'Đăng / gỡ tin tức', 'news_admin.restore' => 'Khôi phục tin tức', 'news_admin.upload' => 'Tải ảnh tin tức',
        'contracts.store' => 'Thêm hợp đồng', 'contracts.update' => 'Sửa hợp đồng', 'contracts.destroy' => 'Xóa hợp đồng',
        'contracts.download' => 'Tải file hợp đồng', 'contracts.draft_download' => 'Tải hợp đồng Word do trợ lý soạn',
        'accounting.import' => 'Nhập sổ MISA', 'accounting.documents.download' => 'Tải tài liệu kế toán',
        'finance.credit.save' => 'Sửa hạn mức tín dụng', 'finance.budget.save' => 'Sửa kế hoạch năm', 'finance.collect.assign' => 'Giao người thu nợ',
        'finance.collect.contact' => 'Ghi lần nhắc nợ', 'finance.price.approve' => 'Duyệt giá',
        'profit.save' => 'Lưu phiếu tính lợi nhuận', 'profit.approve' => 'Duyệt phiếu lợi nhuận', 'profit.delete' => 'Xóa phiếu lợi nhuận',
        'task.create_or_update' => 'Tạo / sửa nhiệm vụ', 'task.update_status' => 'Đổi trạng thái nhiệm vụ', 'task.destroy' => 'Xóa nhiệm vụ',
        'user-departments.update' => 'Đổi phòng ban / phân quyền', 'employee_timekeeping.export' => 'Xuất bảng chấm công',
        'assistant.destroy' => 'Xóa cuộc trò chuyện trợ lý', 'assistant.rename' => 'Đổi tên cuộc trò chuyện trợ lý',
        'store-information' => 'Sửa thông tin công ty',
        'unit.store' => 'Thêm đơn vị', 'unit.update' => 'Sửa đơn vị', 'unit.destroy' => 'Xóa đơn vị',
        'staff.store' => 'Thêm nhân sự', 'staff.update' => 'Sửa nhân sự', 'staff.destroy' => 'Xóa nhân sự',
        'accounts.store' => 'Thêm tài khoản', 'accounts.update' => 'Sửa tài khoản', 'accounts.destroy' => 'Xóa tài khoản',
    ],

    // Xem lịch (trang lịch tải từng tháng / lọc theo người bằng yêu cầu ngầm): ghi là "Xem" để biết KTV, tài xế, Kết quả
    // đã xem lịch nào (trước đây chỉ thấy đăng nhập). Cùng một nội dung xem trong view_dedupe_minutes phút chỉ ghi một lần.
    'view_routes' => [
        'schedules.technicians.select' => 'Xem lịch KTV', 'schedules.technicians.select_technician' => 'Xem lịch KTV',
        'schedules.drivers.select' => 'Xem lịch tài xế', 'schedules.drivers.select_driver' => 'Xem lịch tài xế',
        'schedules.sales.select' => 'Xem lịch Sales', 'schedules.sales.select_driver' => 'Xem lịch Sales', 'schedules.sales.select_technician' => 'Xem lịch Sales',
        'schedules.results.select' => 'Xem lịch Kết quả', 'schedules.results.get' => 'Xem lịch Kết quả', 'schedules.results.search' => 'Tìm lịch Kết quả',
    ],
    'view_dedupe_minutes' => 10,

    // POST chỉ để đọc / tìm / lọc / tải tạm — không ghi là thao tác (tên route, * = khớp mọi chữ)
    'read_routes' => [
        '*.get', '*.filter', '*.filter_options', '*.search', '*.suggest', '*.select', '*.select_*', 'statistics.*', 'assistant.ask', 'assistant.poll',
        'contracts.extract', 'accounting.documents.extract', 'dispatch.suggest', 'file.process', 'file.revert',
        'news_admin.upload_video', // tải video theo từng phần: mỗi phần một request, không ghi nhật ký từng phần
        'logout', 'admin.logout', 'schedules.logout', 'schedules.login.post', 'login', 'password.*', 'contact.submit', 'debugbar.*', 'ignition.*',
    ],

    // GET tải / xuất file: ghi như một thao tác
    'download_routes' => [
        'export.excel', 'export.misa', 'reports.export_pdf', 'contracts.download', 'contracts.draft_download', 'accounting.documents.download',
        'excel-uploads.download', 'order.print',
    ],

    // GET không tính là "mở trang" (gọi ngầm từ trang khác)
    'skip_visit_routes' => [
        'order.group_candidates', 'order.filter_options', 'order.customer_suggestions', 'order.contract_hint', 'contracts.orders', 'notifications.todo',
        'assistant.conversations', 'assistant.messages', 'dispatch.refresh', 'dispatch.pending', 'dispatch.order', 'dispatch.week', 'task.load',
        'csrf.token', 'activity.index', 'sales_logs.image', 'debugbar.*', 'ignition.*', 'sanctum.*',
    ],
];
