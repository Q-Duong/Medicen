<?php

/*
 * Email nhận thông báo của Báo cáo thu chi (đặt trong .env, nhiều email cách nhau bằng dấu phẩy).
 * Để trống thì không gửi (ghi log cảnh báo).
 */
$emails = fn (string $key) => array_values(array_filter(array_map('trim', explode(',', (string) env($key, '')))));

return [
    // Lệnh reports:remind (hằng ngày 10:00): các khoản thu/chi sắp đến hạn hoặc quá hạn
    'remind_to' => $emails('REPORT_REMIND_TO'),

    // Khi cập nhật một dòng báo cáo thu chi có chọn "gửi mail"
    'updated_to' => $emails('REPORT_UPDATED_TO'),
];
