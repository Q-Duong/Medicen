<?php

/*
 * Phòng ban của tài khoản (cột users.department). Admin cấp trên trang "Phân quyền phòng ban".
 * Trợ lý AI nào cho phòng ban nào dùng: xem 'user_departments' trong config/claude.php.
 */
return [
    'sales' => 'Sales',
    'ke_toan' => 'Kế toán',
    'ket_qua' => 'Kết quả (Office)',
    'ky_thuat' => 'Kỹ thuật (Technician)',
    // Người xếp lịch xe: vào trang Xếp lịch xe, nhận việc xếp lịch trên chuông + mail nhắc 16:00 (KTV để ở Kỹ thuật)
    'dieu_phoi' => 'Điều phối xếp lịch',
    'nhan_su' => 'Nhân sự (HR)',
];
