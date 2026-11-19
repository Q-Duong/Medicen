<?php

/*
 * Quyền vào từng nhóm trang trong /admin (middleware 'access:<nhóm>', xem App\Http\Middleware\PageAccess).
 *
 * Admin (role 0, 1) luôn vào được. Tài khoản khác được vào khi khớp MỘT trong:
 *  - 'departments': phòng ban của tài khoản (users.department, trang Phân quyền phòng ban);
 *  - 'names':       tên tài khoản kiểu cũ (Sale, Accountant, Office, HR, SubAdmin) — sidebar đang hiện link theo tên này;
 *  - 'roles':       role cũ (3 = kế toán).
 * Nhóm không có trong danh sách = chỉ Admin.
 * SubAdmin loại Admin (role 0/1, tên 'SubAdmin'): CHỈ vào được các route trong 'subadmin_routes'
 * (middleware App\Http\Middleware\SubAdminScope chạy cho mọi route cần đăng nhập; sidebar cũng lọc theo danh sách này).
 */
return [
    'subadmin_routes' => [
        'admin.home', '403', 'information', 'settings', 'store-information', 'notifications.todo', 'password.confirm',
        // Thống kê doanh thu
        'dashboard.index', 'statistics.revenue_by_date', 'statistics.optional_revenue', 'statistics.revenue_for_the_month', 'statistics.revenue_by_unit',
        // Công nợ Sales (chỉ xem) + xuất Excel / Misa trên trang
        'accountant_sales.index', 'accountant_sales.get', 'accountant_sales.filter_options', 'export.excel', 'export.misa',
        // Báo cáo thu chi: chỉ xem + xuất PDF (không lưu / sửa / tạo khung)
        'reports.index', 'reports.export_pdf',
        // Phân tích tài chính (chỉ xem)
        'finance.index',
        // Trợ lý AI (chỉ trợ lý trong config('claude.subadmin_assistants'))
        'assistant.*',
    ],

    // Đơn hàng: danh sách / thêm / sửa / copy / in, lịch cũ của Sale, upload file đơn, upload danh sách Excel
    'orders' => [
        'departments' => ['sales'],
        'names' => ['Sale', 'SubAdmin'],
    ],

    // Trang "Thông tin báo cáo" cũ của đơn (accountant.order.*)
    'order_report' => [
        'departments' => ['sales', 'ke_toan'],
        'names' => ['Sale', 'SubAdmin', 'Accountant'],
        'roles' => [3],
    ],

    // Công nợ Kế toán + Báo cáo thu chi + Dữ liệu kế toán: chỉ Admin và Kế toán
    'debt_accountant' => [
        'departments' => ['ke_toan'],
        'names' => ['Accountant'],
        'roles' => [3],
    ],

    // Công nợ Sales (chỉ xem)
    'debt_sales' => [
        'departments' => ['sales'],
        'names' => ['Sale', 'SubAdmin'],
    ],

    // Công nợ của bộ phận Kết quả
    'debt_result' => [
        'departments' => ['ket_qua'],
        'names' => ['Office'],
    ],

    // Xuất Excel / Misa (nút trên các trang công nợ)
    'debt_export' => [
        'departments' => ['sales', 'ke_toan', 'ket_qua'],
        'names' => ['Sale', 'Accountant', 'Office', 'SubAdmin'],
        'roles' => [3],
    ],

    // Hợp đồng: sửa (HR) và chỉ xem
    'contract_edit' => [
        'departments' => ['nhan_su'],
        'names' => ['HR'],
    ],
    'contract_view' => [
        'departments' => ['sales', 'ke_toan', 'nhan_su'],
        'names' => ['Sale', 'Accountant', 'SubAdmin', 'HR'],
        'roles' => [3],
    ],

    // Quản lý đơn vị: Kế toán thêm / sửa (xóa chỉ Admin)
    'units' => [
        'departments' => ['ke_toan'],
        'names' => ['Accountant'],
        'roles' => [3],
    ],

    // Trang Thống kê doanh thu (admin/dashboard) + các API số liệu của trang
    'dashboard' => [
        'departments' => ['sales', 'ke_toan'],
        'names' => ['Sale', 'Accountant', 'SubAdmin'],
        'roles' => [3],
    ],

    // Tính KPI
    'kpi' => [
        'names' => ['SubAdmin'],
    ],

    // Xuất bảng chấm công (nút trên trang Xếp lịch xe)
    'timekeeping' => [
        'departments' => ['dieu_phoi'],
    ],
];
