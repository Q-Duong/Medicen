<?php

/*
 * Soạn hợp đồng khách hàng ra file Word (App\Services\Contracts\ContractDrafter): thông tin Bên B (Nam Khánh Linh / Medicen)
 * theo đúng các hợp đồng đã ký (HĐNT Phan Rang 2026, HĐ thời vụ TTC 2026). Đổi địa chỉ / tài khoản / người đại diện thì sửa ở đây.
 */
return [
    'ben_b' => [
        'name' => 'CÔNG TY TNHH ĐẦU TƯ TRANG THIẾT BỊ Y TẾ NAM KHÁNH LINH',
        'address' => 'Số nhà 59, Đường số 9 KDC Nam Sài Gòn – Thế Kỉ 21, Xã Bình Hưng, TP.HCM',
        'phone' => '081 234 8381',
        'accounts' => [
            ['no' => '1603.2010.43096', 'bank' => 'AGRIBANK – Chi nhánh Lý Thường Kiệt, TP.HCM'],
            ['no' => '005.704.070.000.746', 'bank' => 'HDBank – chi nhánh Vạn Hạnh, TP.HCM'],
        ],
        'tax_code' => '0307659096',
        'representative' => 'Ông Thái Văn Lộc',
        'title' => 'Giám đốc',
        'sign_name' => 'THÁI VĂN LỘC',
        'contract_code' => 'MEDICEN',
    ],

    // Hình thức trả kết quả mặc định khi Sales không nói gì (theo HĐNT Phan Rang 2026)
    'default_result_delivery' => [
        'Bảng tổng kết toàn bộ kết quả gửi qua Email và file hình ảnh.',
        'In film bình thường kích thước 8x10cm.',
        'In film kích thước 18x22 (cm).',
        'In film kèm giấy kết quả cho từng ca theo biểu mẫu Bên A.',
    ],

    // File Word soạn xong giữ lại bao nhiêu ngày (storage/app/hop-dong/soan)
    'keep_days' => 7,
];
