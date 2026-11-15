<?php

/*
 * Email nhận đăng ký đặt lịch từ form Liên hệ trên trang chủ (đặt trong .env,
 * nhiều email cách nhau bằng dấu phẩy). CONTACT_MAIL_TO để trống thì không gửi,
 * nội dung đăng ký được ghi vào log để không bị mất.
 */
$emails = fn (string $key) => array_values(array_filter(array_map('trim', explode(',', (string) env($key, '')))));

return [
    'to' => $emails('CONTACT_MAIL_TO'),
    'cc' => $emails('CONTACT_MAIL_CC'),
];
