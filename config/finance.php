<?php

/*
 * Cảnh báo sớm hằng tuần (finance:alerts) và báo cáo CFO tự động đầu tháng (finance:cfo-report).
 * Người nhận: FINANCE_REPORT_TO (nhiều email cách nhau dấu phẩy); để trống thì dùng chung email của Báo cáo thu chi
 * (REPORT_REMIND_TO + REPORT_UPDATED_TO, xem config/reports.php).
 */
$emails = fn (string $key) => array_values(array_filter(array_map('trim', explode(',', (string) env($key, '')))));

return [
    'report_to' => $emails('FINANCE_REPORT_TO'),

    // Cảnh báo sớm (thứ Hai 07:30)
    'alerts' => [
        'cash_threshold' => (int) env('FINANCE_CASH_THRESHOLD', 100000000), // số dư dự báo dưới mức này = cảnh báo
        'stuck_threshold' => (int) env('FINANCE_STUCK_THRESHOLD', 200000000), // tiền kẹt chưa xuất HĐ vượt mức này
        'overdue_increase' => (int) env('FINANCE_OVERDUE_INCREASE', 20000000), // nợ quá hạn tăng hơn mức này so với tuần trước
        'customer_idle_days' => 45, // khách đặt thường xuyên mà ngưng quá số ngày này
        'car_idle_days' => 5,       // xe không chạy quá số ngày này
        'contract_days' => 30,      // hợp đồng hết hạn trong số ngày này
    ],

    // Kiểm soát giá (Phân tích tài chính → Duyệt giá): đơn lỗ / biên lãi ước tính dưới mức / chiết khấu cao cần kế toán duyệt
    'price' => [
        'min_margin_pct' => (float) env('FINANCE_MIN_MARGIN_PCT', 10),
        'max_discount_pct' => (float) env('FINANCE_MAX_DISCOUNT_PCT', 10),
    ],

    // Báo cáo CFO tự động (ngày 3 hằng tháng 08:00): chạy dưới tài khoản này (email); để trống = tài khoản tên "Admin"
    'cfo_user' => env('FINANCE_CFO_USER'),
];
