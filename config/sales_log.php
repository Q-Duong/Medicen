<?php

/*
 * Nhật ký Sales (Kinh doanh → Nhật ký Sales) và email nhắc việc cuối ngày (lệnh sales:digest, 17:00 thứ 2 – thứ 7).
 * Người nhận: SALES_DIGEST_TO trong .env (nhiều email cách nhau dấu phẩy); để trống thì không gửi.
 */
return [
    'digest_to' => array_values(array_filter(array_map('trim', explode(',', (string) env('SALES_DIGEST_TO', ''))))),
    'digest_at' => env('SALES_DIGEST_AT', '17:00'),
    // Ảnh chụp màn hình chat giữ bao nhiêu ngày (tóm tắt và việc cần làm vẫn giữ) — lệnh sales:prune-images
    'image_days' => (int) env('SALES_LOG_IMAGE_DAYS', 90),
];
