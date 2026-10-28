<?php

/*
 | Cấu hình đọc file Excel mẫu đơn hàng (public/templates/mau-don-hang-nkl.xlsx).
 | Hệ thống đọc cột A (Mã trường = name của input trên form tạo đơn) → lấy giá trị cột C.
 |
 | Kiểu (type): text, upper, phone, email, date, time, int, money, percent, number, enum
 | enum: options[...] dạng 'Nhãn hiện trong Excel' => 'value của option/radio trên form'.
 |       So khớp theo nhãn hoặc value, không phân biệt hoa thường, khoảng trắng.
 | Thêm/sửa lựa chọn thì sửa cả sheet DanhMuc trong file mẫu.
 */

return [
    'template_code' => 'NKL-DONHANG-V4',
    'sheet'         => 'DonHang',
    'key_column'    => 'A',
    'label_column'  => 'B',
    'value_column'  => 'C',

    'template_path' => public_path('templates/mau-don-hang-nkl.xlsx'),

    // Chiết khấu (order_percent_discount):
    //  - nhỏ hơn threshold → phần trăm, từ threshold trở lên → số tiền
    //  - chữ không phải số thuần (VD: 1000/ca) → giữ nguyên
    'discount_format' => [
        'threshold' => 100,
        'percent'   => '{n}%',  // VD: 5%
        'amount'    => '{n}',   // VD: 10000
    ],

    'options' => [
        // Dịch vụ chụp
        'dich_vu' => [
            'Phổi (1 Tư thế)' => 'Phổi (1 Tư thế)',
            'Phổi (2 Tư thế)' => 'Phổi (2 Tư thế)',
            'Cột sống thắt lưng (1 Tư thế)' => 'Cột sống thắt lưng (1 Tư thế)',
            'Cột sống thắt lưng (2 Tư thế)' => 'Cột sống thắt lưng (2 Tư thế)',
            'Cột sống cổ (1 Tư thế)' => 'Cột sống cổ (1 Tư thế)',
            'Cột sống cổ (2 Tư thế)' => 'Cột sống cổ (2 Tư thế)',
            'Vai (1 Tư thế)' => 'Vai (1 Tư thế)',
            'Vai (2 Tư thế)' => 'Vai (2 Tư thế)',
            'Gối (1 Tư thế)' => 'Gối (1 Tư thế)',
            'Gối (2 Tư thế)' => 'Gối (2 Tư thế)',
            'Cổ tay (2 Tư thế)' => 'Cổ tay (2 Tư thế)',
            'Khuỷu tay (2 Tư thế)' => 'Khuỷu tay (2 Tư thế)',
            'Bàn tay (2 Tư thế)' => 'Bàn tay (2 Tư thế)',
            'Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng' => 'Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng',
            'Siêu âm Tim' => 'Siêu âm Tim',
            'Siêu âm ĐMC, Mạch Máu Chi Dưới' => 'Siêu âm ĐMC, Mạch Máu Chi Dưới',
            'Đo loãng xương' => 'Đo loãng xương',
            'Nhũ Ảnh' => 'Nhũ Ảnh',
            'Khác' => 'Khác',
        ],
        // Bác sĩ đọc phim
        'bac_si_doc' => [
            'Có' => 'Có',
            'Không' => 'Không',
            'Tại chỗ' => 'Tại chỗ',
        ],
        // In phim
        'in_phim' => [
            'Bình thường' => 'Bình thường',
            'Bất thường' => 'Bất thường',
            'Cả 2' => 'Cả 2',
        ],
        // Hình thức in phim
        'hinh_thuc_in_phim' => [
            'Không in' => 'ko in',
            '16,5 x 21,5 (IN4)' => 'IN4',
            'IN6' => 'IN6',
            '11 x 10,5 (IN12)' => 'IN12',
            '8,5 x 10,5 (IN16)' => 'IN16',
            '20,5 x 25,5 (IN8X10)' => 'IN8X10',
            '25,5 x 30,5 (IN10X12)' => 'IN10X12',
            '35 x 43 (Phim lớn)' => 'PhimLon',
            'Bệnh lý' => 'Bệnh lý',
            'Giấy đặc biệt' => 'Giấy đặc biệt',
        ],
        // In phiếu
        'in_phieu' => [
            'Bình thường' => 'Bình thường',
            'Bất thường' => 'Bất thường',
            'Cả 2' => 'Cả 2',
        ],
        // Hình thức in phiếu
        'hinh_thuc_in_phieu' => [
            'A4' => 'A4',
            'A5' => 'A5',
        ],
        // Phiếu theo mẫu đơn vị
        'phieu_mau_don_vi' => [
            'Có' => 'Có',
            'Không' => 'Không',
        ],
        // Phim & Phiếu
        'phim_phieu' => [
            'Bấm film vào phiếu' => 'Bấm flim vào phiếu',
            'Bỏ film và phiếu vào bao thư' => 'Bỏ flim và phiếu vào bao thư',
            'Bỏ film và phiếu vào bao vàng' => 'Bỏ flim và phiếu vào bao vàng',
        ],
        // Có / Không
        'co_khong' => [
            'Có' => '1',
            'Không' => '0',
        ],
    ],

    'fields' => [
        // Thông tin khách hàng
        'customer_name' => ['label' => 'Họ tên khách hàng', 'type' => 'text', 'required' => true],
        'customer_phone' => ['label' => 'Số điện thoại', 'type' => 'phone', 'required' => true],
        'ord_cty_name' => ['label' => 'Tên công ty', 'type' => 'upper', 'required' => true],
        'customer_address' => ['label' => 'Địa chỉ chụp', 'type' => 'text', 'required' => true],
        'ord_email' => ['label' => 'Địa chỉ email khách hàng', 'type' => 'email', 'required' => false],
        'ord_deliver_results' => ['label' => 'Địa chỉ & SĐT giao kết quả', 'type' => 'text', 'required' => false],
        // Lịch chụp và dịch vụ
        'ord_start_day' => ['label' => 'Ngày chụp', 'type' => 'date', 'required' => true],
        'ord_time' => ['label' => 'Giờ chụp', 'type' => 'time', 'required' => false],
        'ord_select' => ['label' => 'Dịch vụ chụp', 'type' => 'enum', 'required' => true, 'options' => 'dich_vu'],
        'order_quantity' => ['label' => 'Số lượng', 'type' => 'int', 'required' => true],
        'accountant_distance' => ['label' => 'Số Km', 'type' => 'number', 'required' => false],
        'ord_note' => ['label' => 'Ghi chú', 'type' => 'text', 'required' => false],
        // Kết quả và in ấn
        'ord_doctor_read' => ['label' => 'Bác sĩ đọc phim', 'type' => 'enum', 'required' => false, 'options' => 'bac_si_doc'],
        'ord_film' => ['label' => 'In phim', 'type' => 'enum', 'required' => false, 'options' => 'in_phim'],
        'ord_form' => ['label' => 'Hình thức in phim', 'type' => 'enum', 'required' => false, 'options' => 'hinh_thuc_in_phim'],
        'ord_print' => ['label' => 'In phiếu', 'type' => 'enum', 'required' => false, 'options' => 'in_phieu'],
        'ord_form_print' => ['label' => 'Hình thức in phiếu', 'type' => 'enum', 'required' => false, 'options' => 'hinh_thuc_in_phieu'],
        'ord_print_result' => ['label' => 'In phiếu kết quả theo mẫu đơn vị', 'type' => 'enum', 'required' => false, 'options' => 'phieu_mau_don_vi'],
        'ord_film_sheet' => ['label' => 'Phim & Phiếu', 'type' => 'enum', 'required' => false, 'options' => 'phim_phieu'],
        // Giá (NKL điền, khách để trống)
        'order_surcharge' => ['label' => 'Đơn phụ thu', 'type' => 'enum', 'required' => false, 'options' => 'co_khong'],
        'order_all_in_one' => ['label' => 'Trọn gói', 'type' => 'enum', 'required' => false, 'options' => 'co_khong'],
        'order_cost' => ['label' => 'Đơn giá (đồng)', 'type' => 'money', 'required' => false],
        'order_vat' => ['label' => 'VAT (%)', 'type' => 'percent', 'required' => false],
        'order_percent_discount' => ['label' => 'Chiết khấu', 'type' => 'discount', 'required' => false],
        'order_price' => ['label' => 'Tổng tiền (đồng)', 'type' => 'money', 'required' => false],
    ],
];
