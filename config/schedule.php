<?php

/*
 * Cấu hình trang Xếp lịch xe (Technician duyệt) và gợi ý xếp lịch.
 */
return [
    // Xe nhà: mỗi buổi chỉ chạy được một đơn
    'own_cars' => ['1', '2', '3', '4', '5'],
    // Xe siêu âm: chỉ dùng cho đơn có dịch vụ siêu âm, cũng không chạy 2 đơn cùng buổi
    'ultrasound_car' => '8',
    // Xe thuê / tăng cường: không giới hạn, dùng khi xe nhà đã kín
    'hired_cars' => ['6', '7'],
    'car_labels' => ['6' => 'Xe Thuê', '7' => 'Xe Tăng Cường', '8' => 'Xe Siêu Âm'],

    // Tên dùng cho tài xế / KTV thuê ngoài (có trong bảng staffs, không giới hạn số buổi)
    'hired_driver' => 'TX Thuê',
    'hired_ktv' => 'KTV Thuê',

    // Từ số cas này trở lên cần 2 KTV
    'two_ktv_from_cas' => (int) env('SCHEDULE_TWO_KTV_FROM_CAS', 250),

    // Gợi ý cho các đơn chưa xếp trong bao nhiêu ngày tới
    'suggest_days' => 30,
    // Đơn đã qua ngày mà chưa xếp xe: hiện trong bao nhiêu ngày gần đây (để ghi bổ sung xe / người)
    'past_days' => 60,
    // Đơn xa hơn suggest_days: hiện tới bao nhiêu ngày tới (xếp thủ công)
    'future_days' => 365,

    /*
     * Ước tính quãng đường giữa 2 điểm chụp theo tỉnh/thành (quận/huyện với TP.HCM) đọc từ địa chỉ khách,
     * miễn phí, không dùng Google Maps. Xem App\Services\Schedule\RouteDistance.
     */
    // Email nhắc người xếp lịch (lệnh schedule:remind, chạy hằng ngày lúc 'remind_at'):
    // đơn trong 'remind_days' ngày tới (tính cả hôm nay) chưa xếp xe, và giữ chỗ trong 'remind_hold_days' ngày tới chưa lên đơn.
    // Gửi cho tài khoản phòng Kỹ thuật (users.department = ky_thuat) + các email thêm trong SCHEDULE_REMIND_TO (cách nhau dấu phẩy).
    'remind_at' => env('SCHEDULE_REMIND_AT', '16:00'),
    'remind_days' => (int) env('SCHEDULE_REMIND_DAYS', 2),
    'remind_hold_days' => (int) env('SCHEDULE_REMIND_HOLD_DAYS', 7),
    'remind_to' => array_values(array_filter(array_map('trim', explode(',', (string) env('SCHEDULE_REMIND_TO', ''))))),

    'distance' => [
        'enabled' => true,
        // Đường bộ thường dài hơn đường chim bay khoảng 35%
        'road_factor' => 1.35,
        // Tốc độ trung bình xe chụp (gồm nghỉ, kẹt xe)
        'avg_speed_kmh' => (int) env('SCHEDULE_AVG_SPEED_KMH', 45),
        // Cùng quận / cùng tỉnh (không rõ chi tiết hơn): coi như gần
        'same_place_km' => 8,
        // Số km Sale nhập khi lên đơn (accountants.accountant_distance) là km CẢ ĐI LẪN VỀ:
        // hệ thống chia đôi để ra km một chiều từ công ty tới điểm khám.
        'order_km_round_trip' => (bool) env('SCHEDULE_ORDER_KM_ROUND_TRIP', true),
        // Điểm cách công ty không quá chừng này km thì coi là "gần công ty"
        'near_base_km' => 40,
        // Nơi xe xuất phát (để hiện "cách ~X km" trên thẻ đơn chờ)
        'base' => env('SCHEDULE_BASE_ADDRESS', 'TP Hồ Chí Minh'),
        'base_label' => env('SCHEDULE_BASE_LABEL', 'TP.HCM'),
    ],
    // Chiều hôm trước ở điểm A, sáng hôm sau ở điểm B: tối đa bao nhiêu phút chạy xe
    'next_day_max_minutes' => (int) env('SCHEDULE_NEXT_DAY_MAX_MINUTES', 240),
    // Sáng ở A, chiều cùng ngày ở B (khác đơn vị): tối đa bao nhiêu phút chạy xe
    'same_day_max_minutes' => (int) env('SCHEDULE_SAME_DAY_MAX_MINUTES', 90),

    // Phòng ban được vào trang xếp lịch (Admin luôn vào được)
    'user_departments' => ['ky_thuat'],
];
