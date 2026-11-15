<?php

/*
|--------------------------------------------------------------------------
| Bảng tính hiệu quả kinh doanh (admin/profit/calculate) - giá mặc định
|--------------------------------------------------------------------------
|
| Nguồn duy nhất cho đơn giá mặc định: trang tính lợi nhuận hiển thị các giá
| trị này, server dùng chúng khi tính lại, và trợ lý AI dùng khi người dùng
| không nêu giá khác. Đổi giá ở đây là đổi ở cả ba nơi.
|
| Khóa trùng với id ô nhập trên trang (và với input_data đã lưu).
|
*/

return [
    'defaults' => [
        // I. Doanh thu
        'thu_xq_gia' => 28000,          // XQ phổi / cas
        'thu_cstl_gia' => 80000,        // CSTL / cas
        'thu_phuthu_gia' => 2000000,    // Phụ thu / lần
        'thu_bs_gia' => 500000,         // Thu thêm BS đọc trực tiếp / đợt

        // II.1 Vật tư & chiết khấu
        'chi_baophim_gia' => 5500,      // × số ca XQ
        'chi_phim16_gia' => 1125,       // × số ca XQ
        'chi_phim810_gia' => 7500,      // × số ca CSTL
        'chi_phim1012_gia' => 12500,    // × nhập tay
        'chi_chietkhau_gia' => 0,       // tổng tiền chiết khấu

        // II.2 Nhân sự
        'chi_bsdoc_gia' => 1500,        // BS đọc từ xa / unit
        'chi_bstructiep_gia' => 300000, // BS đọc trực tiếp / ngày
        'chi_taixe_gia' => 342000,      // Lương TX công ty / ngày
        'chi_thue_tx_gia' => 800000,    // Thuê TX ngoài / ngày
        'chi_ktv_gia_ca' => 1500,       // Lương KTV công ty / unit
        'chi_ktv_gia_ngay' => 323000,   // Lương KTV công ty / ngày
        'chi_thue_ktv_gia' => 400000,   // Thuê KTV ngoài / ngày
        'chi_bpkq_gia' => 650,          // Lương bộ phận kết quả / unit
        'chi_phucap_xa_gia' => 150000,  // Phụ cấp >250km / lần / người

        // II.3 Vận hành & khác
        'chi_quanly_phantram' => 3.2,   // % doanh thu
        'chi_cauduong_gia' => 3500,     // BOT / lượt
        'chi_dau_gia' => 20000,         // Dầu / lít
        'chi_dau_dinhmuc' => 5,         // km / lít
        'chi_dau_phatsinh_gia' => 20000,
        'chi_ks_gia' => 600000,         // Khách sạn / phòng / đêm
        'chi_dangkiem_gia' => 40000,    // Đăng kiểm + đường bộ / ngày
        'chi_dem_gia' => 100000,        // Phụ cấp ở lại đêm / đêm / người
        'chi_vat_phantram' => 8,        // VAT phải nộp: Thu / (1+VAT) × VAT
    ],

    // Ngưỡng % lợi nhuận để phân loại (quy trình: lãi → tự chốt, hòa/lỗ → báo KTT)
    'margin_break_even' => 0,
];
