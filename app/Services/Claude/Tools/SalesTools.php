<?php

namespace App\Services\Claude\Tools;

use App\Models\User;
use App\Services\ProfitCalculator;
use App\Services\Schedule\ScheduleBoard;
use App\Services\ProfitSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Tool cho agent phòng Sales. Tất cả chỉ đọc, trừ cap_nhat_don_gia_mac_dinh
 * (đổi đơn giá mặc định của bảng tính lợi nhuận; chỉ Admin, có ghi lịch sử).
 *
 * Không trả số điện thoại / thông tin cá nhân của khách: agent chỉ cần
 * dữ liệu kinh doanh (đơn vị, lịch sử đơn, lịch xe, công nợ).
 */
class SalesTools implements DepartmentTools
{
    /** Xe chính của công ty (6 = xe thuê, 7 = tăng cường, 8 = siêu âm) */
    private const MAIN_CARS = ['1', '2', '3', '4', '5'];

    private const CAR_LABELS = ['6' => 'Xe Thuê', '7' => 'Xe Tăng Cường', '8' => 'Xe Siêu Âm'];

    private const ORDER_STATUS = [
        0 => 'Đơn hàng mới',
        1 => 'Đang xử lý',
        2 => 'Đã cập nhật số cas thực tế',
        3 => 'Đã xử lý',
        4 => 'Đã cập nhật doanh thu',
        5 => 'Chưa lên lịch',
    ];

    /**
     * Tham số của tool tinh_loi_nhuan → ô tương ứng trên bảng tính lợi nhuận.
     * Một tham số có thể điền nhiều ô (vd. BS đọc trực tiếp vừa là thu vừa là chi).
     */
    private const PROFIT_FIELDS = [
        'so_ngay' => ['inp_so_ngay'],
        'so_dem' => ['inp_so_dem'],
        'tong_km' => ['inp_so_km'],
        'so_nguoi' => ['inp_so_nguoi'],
        'so_cas_xq_phoi' => ['thu_xq_sl'],
        'don_gia_xq_phoi' => ['thu_xq_gia'],
        'so_cas_cstl' => ['thu_cstl_sl'],
        'don_gia_cstl' => ['thu_cstl_gia'],
        'phu_thu' => ['thu_phuthu_gia'],
        'so_ngay_bs_doc_truc_tiep' => ['thu_bs_sl', 'chi_bstructiep_sl'],
        'so_phim_10x12' => ['chi_phim1012_sl'],
        'chiet_khau' => ['chi_chietkhau_gia'],
        'so_ngay_thue_tai_xe' => ['chi_thue_tx_sl'],
        'so_ngay_thue_ktv' => ['chi_thue_ktv_sl'],
        'so_lan_phu_cap_xa' => ['chi_phucap_xa_sl'],
        'so_luot_bot' => ['chi_cauduong_sl'],
        'so_lit_dau_phat_sinh' => ['chi_dau_phatsinh_sl'],
        'so_ngay_dang_kiem' => ['chi_dangkiem_sl'],
    ];

    public function __construct(private ProfitCalculator $profit, private ProfitSettings $settings)
    {
    }

    public function definitions(): array
    {
        $number = fn (string $desc) => ['type' => 'number', 'minimum' => 0, 'description' => $desc];
        $priceProperties = [];
        foreach (ProfitSettings::LABELS as $key => $label) {
            $priceProperties[$key] = $number($label);
        }

        return [
            [
                'name' => 'tinh_loi_nhuan',
                'description' => 'Tính lợi nhuận một đơn bằng đúng công thức "Bảng tính hiệu quả kinh doanh" trên web Medicen '
                    . '(admin/profit/calculate): doanh thu, chi phí vật tư/nhân sự/vận hành/VAT, lợi nhuận ròng, % lãi và kết luận lãi/hòa/lỗ. '
                    . 'Dùng trước khi chốt đơn hoặc khi khách ép giá. Tham số không nêu thì lấy đơn giá mặc định của bảng tính; '
                    . 'số lượng không nêu = 0. Chỉ tính, không lưu phiếu.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'so_ngay' => $number('Số ngày công tác (tính lương TX, KTV công ty)'),
                        'so_dem' => $number('Số đêm lưu trú (khách sạn, phụ cấp đêm). 0 nếu đi về trong ngày'),
                        'tong_km' => $number('Tổng số km cả đi và về (tính tiền dầu)'),
                        'so_nguoi' => $number('Số nhân sự đi (TX + KTV), dùng cho phụ cấp đêm và phụ cấp >250km'),
                        'so_cas_xq_phoi' => $number('Số cas XQ phổi'),
                        'don_gia_xq_phoi' => $number('Đơn giá XQ phổi/cas (VNĐ). Mặc định 28.000'),
                        'so_cas_cstl' => $number('Số cas XQ cột sống thắt lưng'),
                        'don_gia_cstl' => $number('Đơn giá CSTL/cas (VNĐ). Mặc định 80.000'),
                        'phu_thu' => $number('Tổng tiền phụ thu thu của khách (VNĐ). 0 nếu không phụ thu'),
                        'so_ngay_bs_doc_truc_tiep' => $number('Số ngày/đợt có BS đọc trực tiếp tại chỗ (tính cả thu thêm và chi trả BS)'),
                        'so_phim_10x12' => $number('Số phim 10x12 (nếu đơn vị yêu cầu khổ này)'),
                        'chiet_khau' => $number('Tổng tiền chiết khấu cho khách (VNĐ)'),
                        'so_ngay_thue_tai_xe' => $number('Số ngày thuê tài xế ngoài'),
                        'so_ngay_thue_ktv' => $number('Số ngày thuê KTV ngoài'),
                        'so_lan_phu_cap_xa' => $number('Số lần phụ cấp đi xa >250km (nhân với số người)'),
                        'so_luot_bot' => $number('Số lượt qua trạm BOT (cả đi và về)'),
                        'so_lit_dau_phat_sinh' => $number('Số lít dầu di chuyển phát sinh thêm'),
                        'so_ngay_dang_kiem' => $number('Số ngày tính phí đăng kiểm + đường bộ. Mặc định = so_ngay'),
                        'don_gia_khac' => [
                            'type' => 'object',
                            'description' => 'Đơn giá khác mặc định, CHỈ áp dụng cho lần tính này (vd. người dùng báo lương tài xế đợt này 400.000). '
                                . 'Không làm thay đổi giá mặc định.',
                            'properties' => $priceProperties,
                            'additionalProperties' => false,
                        ],
                    ],
                    'required' => ['so_ngay', 'tong_km', 'so_nguoi'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'xem_don_gia_mac_dinh',
                'description' => 'Xem bảng đơn giá mặc định hiện hành của bảng tính lợi nhuận (lương TX/KTV, thuê ngoài, dầu, khách sạn, phụ cấp, VAT…) '
                    . 'và các lần thay đổi gần đây. Dùng khi người dùng hỏi giá đang áp dụng hoặc trước khi cập nhật giá.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass, 'additionalProperties' => false],
            ],
            [
                'name' => 'cap_nhat_don_gia_mac_dinh',
                'description' => 'Đổi đơn giá MẶC ĐỊNH của bảng tính lợi nhuận, áp dụng cho mọi lần tính về sau (trang web và trợ lý). '
                    . 'Chỉ gọi sau khi người dùng đã xác nhận rõ muốn đổi lâu dài (không phải chỉ cho một đơn). '
                    . 'Chỉ tài khoản Admin được phép; tool trả lỗi nếu người dùng không có quyền. Mỗi lần đổi được ghi lịch sử.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ma_don_gia' => ['type' => 'string', 'enum' => array_keys(ProfitSettings::LABELS), 'description' => 'Mã đơn giá cần đổi'],
                        'gia_moi' => $number('Giá trị mới (VNĐ, hoặc % với phí quản lý/VAT, hoặc km/lít với định mức dầu)'),
                        'ly_do' => ['type' => 'string', 'description' => 'Lý do đổi, theo lời người dùng'],
                    ],
                    'required' => ['ma_don_gia', 'gia_moi', 'ly_do'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'tra_cuu_don_vi',
                'description' => 'Tìm đơn vị (bệnh viện, phòng khám, công ty) trong hệ thống Medicen theo tên, tên viết tắt hoặc mã. '
                    . 'Trả về đơn vị đã từng đặt hay chưa (khách cũ/mới), số đơn, ngày đặt gần nhất và các đơn gần đây '
                    . '(dịch vụ, số cas, đơn giá/cas, thành tiền). Dùng trước khi báo giá để biết khách cũ/mới và giá đã từng báo.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_khoa' => ['type' => 'string', 'description' => 'Tên, tên viết tắt hoặc mã đơn vị, ví dụ "Bình Chánh", "SG BÌNH DƯƠNG"'],
                    ],
                    'required' => ['tu_khoa'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'xem_lich_xe',
                'description' => 'Xem lịch xe chụp X-quang lưu động trong một khoảng ngày (tối đa 31 ngày), giống trang Xếp lịch xe: '
                    . 'mỗi ngày xe chính nào trống cả ngày / chỉ trống buổi sáng / chỉ trống buổi chiều, các đơn đã xếp xe '
                    . '(đơn vị, dịch vụ, số cas, buổi, tài xế, KTV), các lịch GIỮ CHỖ (khách đặt trước, chưa lên đơn: tính là đã có lịch), '
                    . 'xe nghỉ BẢO TRÌ / đăng kiểm (xe_bao_tri: không nhận đơn), '
                    . 'tài xế / KTV còn trống cả ngày (đã trừ người nghỉ), KTV có CCHN còn trống, và các đơn đã nhận nhưng chưa xếp xe. '
                    . 'Dùng khi khách hỏi ngày nào còn trống, chốt lịch, kiểm tra còn slot không.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_ngay' => ['type' => 'string', 'description' => 'Ngày bắt đầu, định dạng YYYY-MM-DD'],
                        'den_ngay' => ['type' => 'string', 'description' => 'Ngày kết thúc, định dạng YYYY-MM-DD (không quá 31 ngày sau tu_ngay)'],
                    ],
                    'required' => ['tu_ngay', 'den_ngay'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'xem_nguoi_di_don',
                'description' => 'Tra cứu AI ĐI ĐƠN: tài xế và KTV được xếp cho các đơn đã lên lịch xe trong một ngày / khoảng ngày (tối đa 31 ngày), '
                    . 'có thể lọc theo đơn vị. Trả về xe, buổi, giờ, địa chỉ chụp, họ tên và SỐ ĐIỆN THOẠI liên hệ của tài xế, KTV '
                    . '(lấy từ trang Quản lý nhân viên). Dùng khi hỏi "ngày X ai đi đơn Y", "SĐT tài xế / KTV đi đơn Y", '
                    . 'hoặc khi soạn tin báo khách thông tin người đi chụp.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_ngay' => ['type' => 'string', 'description' => 'Ngày chụp (hoặc ngày bắt đầu), định dạng YYYY-MM-DD'],
                        'den_ngay' => ['type' => 'string', 'description' => 'Ngày kết thúc YYYY-MM-DD; bỏ trống = chỉ một ngày tu_ngay'],
                        'don_vi' => ['type' => 'string', 'description' => 'Tên, tên viết tắt hoặc mã đơn vị để lọc; bỏ trống = mọi đơn vị'],
                    ],
                    'required' => ['tu_ngay'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'xem_nhan_su',
                'description' => 'Danh sách tài xế (TX) và KTV đang làm việc (theo trang Quản lý nhân viên): họ tên, vai trò, SĐT, '
                    . 'có CCHN (chứng chỉ hành nghề) hay không và số / ghi chú CCHN. Dùng khi hỏi "ai có CCHN", "KTV nào có chứng chỉ", '
                    . '"SĐT của tài xế / KTV X", hoặc khi khách yêu cầu KTV có CCHN.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'vai_tro' => ['type' => 'string', 'enum' => ['TX', 'KTV', 'tat_ca'], 'description' => 'Lọc theo vai trò; mặc định tat_ca'],
                        'chi_co_cchn' => ['type' => 'boolean', 'description' => 'true = chỉ người có CCHN'],
                        'ten' => ['type' => 'string', 'description' => 'Tìm theo một phần họ tên; bỏ trống = tất cả'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'soan_hop_dong',
                'description' => 'Soạn hợp đồng (file Word theo mẫu công ty: nguyên tắc hoặc thời vụ) và trả về link tải. Dùng khi Sales nhờ "làm hợp đồng cho đơn vị X", '
                    . '"làm lại hợp đồng của X nhưng đổi giá", "làm hợp đồng cho khách mới giống hợp đồng của Y". Mặc định lấy bảng giá / điều khoản từ hợp đồng đã lưu của '
                    . 'dua_theo (hoặc của chính don_vi) rồi thay các trường được ghi. Phần chữ hợp đồng giữ đúng mẫu, chỉ điền thông tin. Trả về canh_bao (bậc giá dưới giá sàn, '
                    . 'thiếu thông tin Bên A) — luôn nói lại cho Sales. Thông tin Bên A không có thì để dòng chấm cho Sales điền tay.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'loai' => ['type' => 'string', 'enum' => ['nguyen_tac', 'thoi_vu'], 'description' => 'Bỏ trống = theo hợp đồng làm mẫu, không có thì nguyên tắc'],
                        'don_vi' => ['type' => 'string', 'description' => 'Đơn vị Bên A trên web (tên / tên viết tắt / mã). Khách mới chưa có trên web thì bỏ trống và ghi ben_a'],
                        'dua_theo' => ['type' => 'string', 'description' => 'Đơn vị có hợp đồng dùng làm mẫu (giá, điều khoản). Bỏ trống = hợp đồng của chính don_vi'],
                        'ben_a' => ['type' => 'object', 'description' => 'Thông tin Bên A (ghi đè dữ liệu trên web)', 'properties' => [
                            'ten' => ['type' => 'string'], 'dia_chi' => ['type' => 'string'], 'ma_so_thue' => ['type' => 'string'], 'dien_thoai' => ['type' => 'string'],
                            'fax' => ['type' => 'string'], 'tai_khoan' => ['type' => 'string'], 'ngan_hang' => ['type' => 'string'],
                            'dai_dien' => ['type' => 'string', 'description' => 'Vd "Ông Nguyễn Văn A"'], 'chuc_vu' => ['type' => 'string'],
                            'uy_quyen' => ['type' => 'string', 'description' => 'Dòng giấy ủy quyền nếu người ký không phải đại diện pháp luật'],
                        ]],
                        'cach_tinh' => ['type' => 'string', 'enum' => ['theo_ca', 'tron_goi_ngay', 'tron_goi_dot']],
                        'gia_tron_goi' => ['type' => 'integer', 'description' => 'Đồng (đã gồm VAT): thời vụ = trọn gói đợt; nguyên tắc trọn gói ngày'],
                        'bac_gia' => ['type' => 'array', 'description' => 'THAY TOÀN BỘ bảng giá theo số ca / ngày', 'items' => ['type' => 'object', 'properties' => ['tu_ca' => ['type' => 'integer', 'description' => '0 = từ 0'], 'den_ca' => ['type' => 'integer', 'description' => '0 = trở lên'], 'tinh_theo' => ['type' => 'string', 'enum' => ['ca', 'ngay'], 'description' => 'ca = đồng/ca; ngay = trọn gói đồng/ngày'], 'gia' => ['type' => 'integer']], 'required' => ['gia']]],
                        'doi_gia_bac' => ['type' => 'array', 'description' => 'Chỉ đổi giá vài bậc của bảng giá mẫu: tu_ca (mức bắt đầu của bậc, bậc thấp nhất ghi 0) + gia mới', 'items' => ['type' => 'object',
                            'properties' => ['tu_ca' => ['type' => 'integer'], 'gia' => ['type' => 'integer']], 'required' => ['tu_ca', 'gia']]],
                        'cong_them' => ['type' => 'array', 'description' => 'Thay danh sách cộng thêm theo ca (in phim…)', 'items' => ['type' => 'object',
                            'properties' => ['ten' => ['type' => 'string'], 'gia_moi_ca' => ['type' => 'integer']], 'required' => ['ten', 'gia_moi_ca']]],
                        'phu_thu' => ['type' => 'array', 'description' => 'Thay khung phụ thu khoảng cách', 'items' => ['type' => 'object', 'properties' => [
                            'tu_km' => ['type' => 'integer'], 'den_km' => ['type' => 'integer'], 'tu_tien' => ['type' => 'integer'], 'den_tien' => ['type' => 'integer'], 'gom_vat' => ['type' => 'boolean']]]],
                        'ban_kinh_km' => ['type' => 'integer'],
                        'tra_ket_qua' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Hình thức trả kết quả, mỗi ý một dòng'],
                        'so_ngay_thanh_toan' => ['type' => 'integer'],
                        'thoi_han_thang' => ['type' => 'integer', 'description' => '0 = hiệu lực đến khi một bên thanh lý'],
                        'tu_gia_han' => ['type' => 'boolean'],
                        'ngay_ky' => ['type' => 'string', 'description' => 'YYYY-MM-DD, mặc định hôm nay'],
                        'so_hd' => ['type' => 'string', 'description' => 'Bỏ trống = tự đặt dd.mm.yyyy/MEDICEN-<mã>/HĐNT|HĐXQ'],
                        'ma_viet_tat' => ['type' => 'string', 'description' => 'Mã trong số hợp đồng, vd YD115, SANTE'],
                        'ngay_chup' => ['type' => 'string', 'description' => 'Thời vụ: YYYY-MM-DD'],
                        'dia_diem' => ['type' => 'string', 'description' => 'Thời vụ: địa điểm chụp'],
                        'cong_ty_duoc_chup' => ['type' => 'string', 'description' => 'Thời vụ: công ty được chụp (nếu khác Bên A)'],
                        'km_khu_hoi' => ['type' => 'integer', 'description' => 'Để kiểm tra giá sàn (tính dầu)'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'gia_hop_dong',
                'description' => 'Giá theo HỢP ĐỒNG đã ký với một đơn vị (Hành chính → Hợp đồng): loại hợp đồng, bảng giá theo số ca / ngày hoặc trọn gói, cộng thêm in phim, '
                    . 'phụ thu khoảng cách (chỉ tham khảo, chốt theo thực tế), số ngày thanh toán, điều khoản. Ghi so_ca để tính sẵn: giá theo hợp đồng, '
                    . 'giá các đợt trước của đơn vị (hợp đồng "trọn gói theo đợt" không ghi số tiền thì dựa vào đây) và giá sàn có lời (khách mới / đơn vị chưa có hợp đồng). '
                    . 'Dùng khi báo giá hoặc kiểm tra đơn giá trước khi lên đơn.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_khoa' => ['type' => 'string', 'description' => 'Tên viết tắt / tên / mã đơn vị (Bên A trên hợp đồng) — bắt buộc'],
                        'so_ca' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Số ca dự kiến để tính giá (tùy chọn)'],
                        'so_ngay' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 60, 'description' => 'Số ngày chụp, mặc định 1 (nhiều ngày: bậc giá theo số ca bình quân / ngày)'],
                        'km_khu_hoi' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Khoảng cách KHỨ HỒI (km) để tính giá sàn; bỏ trống = chưa tính dầu'],
                        'cong_ty' => ['type' => 'string', 'description' => 'Công ty được chụp (để ưu tiên giá các đợt trước của chính công ty đó)'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'xem_cong_no',
                'description' => 'Xem công nợ chưa thanh toán của một đơn vị: từng hóa đơn (số HĐ, ngày xuất, hạn thanh toán, số tiền, số ngày quá hạn), '
                    . 'tổng nợ, tổng nợ quá hạn và HẠN MỨC TÍN DỤNG (vượt hạn mức / quá hạn quá lâu = cần thu nợ hoặc xin Kế toán duyệt trước khi nhận đơn). Dùng trước khi nhận đơn mới (không nhận đơn khi đang nợ quá hạn) hoặc khi soạn tin nhắc nợ. '
                    . 'Ghi loc (không cần tu_khoa) để lấy danh sách đơn của MỌI đơn vị: qua_han (đã quá hạn), sap_den_han (đến hạn trong so_ngay ngày tới), '
                    . 'chua_xuat_hd (còn nợ nhưng chưa xuất hóa đơn).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_khoa' => ['type' => 'string', 'description' => 'Tên, tên viết tắt hoặc mã đơn vị (bắt buộc nếu không ghi loc)'],
                        'loc' => ['type' => 'string', 'enum' => ['qua_han', 'sap_den_han', 'chua_xuat_hd'], 'description' => 'Lọc danh sách đơn theo hạn thanh toán'],
                        'so_ngay' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 90, 'description' => 'Với loc = sap_den_han: số ngày tới, mặc định 7'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function call(string $name, array $input, ?User $user = null): array
    {
        return match ($name) {
            'tinh_loi_nhuan' => $this->profit($input),
            'xem_don_gia_mac_dinh' => $this->listPrices(),
            'cap_nhat_don_gia_mac_dinh' => $this->updatePrice($input, $user),
            'tra_cuu_don_vi' => $this->findUnits($this->requireString($input, 'tu_khoa')),
            'xem_lich_xe' => $this->carSchedule($this->requireDate($input, 'tu_ngay'), $this->requireDate($input, 'den_ngay')),
            'xem_nguoi_di_don' => $this->crew($input),
            'xem_nhan_su' => $this->staffList($input),
            'gia_hop_dong' => app(\App\Services\Accounting\ContractPricing::class)->forAssistant(
                trim((string) ($input['tu_khoa'] ?? '')) ?: null, (int) ($input['so_ca'] ?? 0), max(1, (int) ($input['so_ngay'] ?? 1)), (int) ($input['km_khu_hoi'] ?? 0), trim((string) ($input['cong_ty'] ?? '')) ?: null),
            'soan_hop_dong' => $this->draftContract($input),
            'xem_cong_no' => !empty($input['loc'])
                ? app(\App\Services\Accountant\DebtList::class)->find((string) $input['loc'], (int) ($input['so_ngay'] ?? 7), trim((string) ($input['tu_khoa'] ?? '')) ?: null)
                : $this->debts($this->requireString($input, 'tu_khoa')),
            default => throw new InvalidArgumentException("Tool không tồn tại: {$name}"),
        };
    }

    // ------------------------------------------------------------------
    // tinh_loi_nhuan
    // ------------------------------------------------------------------

    private function profit(array $input): array
    {
        $fields = [];
        $assumptions = [];

        foreach (self::PROFIT_FIELDS as $param => $targets) {
            if (!array_key_exists($param, $input)) {
                continue;
            }
            $value = $input[$param];
            if (!is_numeric($value) || $value < 0) {
                throw new InvalidArgumentException("Tham số {$param} phải là số không âm.");
            }
            foreach ($targets as $target) {
                $fields[$target] = $value;
            }
        }

        $overrides = [];
        foreach ((array) ($input['don_gia_khac'] ?? []) as $key => $value) {
            if (!array_key_exists($key, ProfitSettings::LABELS) || !is_numeric($value) || $value < 0) {
                throw new InvalidArgumentException("don_gia_khac.{$key} không hợp lệ.");
            }
            $overrides[] = [
                'don_gia' => ProfitSettings::LABELS[$key],
                'mac_dinh' => $this->settings->get($key),
                'lan_nay' => $value + 0,
            ];
            $fields[$key] = $value;
        }

        foreach (['so_ngay', 'tong_km', 'so_nguoi'] as $required) {
            if (!array_key_exists($required, $input)) {
                throw new InvalidArgumentException("Thiếu tham số {$required}.");
            }
        }

        // Phụ thu nhập dạng tổng tiền → 1 lần × số tiền
        if (array_key_exists('phu_thu', $input)) {
            $fields['thu_phuthu_sl'] = 1;
        }

        if (!array_key_exists('so_ngay_dang_kiem', $input)) {
            $fields['chi_dangkiem_sl'] = $fields['inp_so_ngay'];
            $assumptions[] = 'Phí đăng kiểm + đường bộ tính theo số ngày công tác.';
        }
        if (!array_key_exists('so_luot_bot', $input) && $fields['inp_so_km'] > 0) {
            $assumptions[] = 'Chưa tính phí BOT (không nêu số lượt).';
        }
        if (!array_key_exists('so_dem', $input)) {
            $assumptions[] = 'Đi về trong ngày (0 đêm lưu trú).';
        }

        $r = $this->profit->calculate($fields);
        $d = $this->profit->defaults();
        $money = fn (array $items) => array_map(fn ($x) => (int) round($x), $items);

        return [
            'doanh_thu' => $money($r['revenue']) + ['tong' => (int) round($r['total_revenue'])],
            'chi_phi' => [
                'vat_tu_va_chiet_khau' => $money($r['costs']['vat_tu']),
                'nhan_su' => $money($r['costs']['nhan_su']),
                'van_hanh_va_thue' => $money($r['costs']['van_hanh']),
                'tong' => (int) round($r['total_cost']),
            ],
            'loi_nhuan_rong' => (int) round($r['net_profit']),
            'ty_suat_loi_nhuan_phan_tram' => $r['profit_margin'],
            'ket_luan' => [
                'lai' => 'LÃI - theo quy trình có thể tự chốt.',
                'hoa' => 'HÒA VỐN - cần báo Kế toán trưởng trước khi nhận.',
                'lo' => 'LỖ - cần báo Kế toán trưởng; không xác nhận đơn khi chưa được duyệt.',
            ][$r['verdict']],
            'dau_vao_da_dung' => $r['inputs'],
            'gia_dinh' => $assumptions,
            'don_gia_khac_lan_nay' => $overrides,
            'don_gia_chinh_dang_dung' => [
                'luong_tai_xe_ngay' => $fields['chi_taixe_gia'] ?? $d['chi_taixe_gia'],
                'luong_ktv_ngay' => $fields['chi_ktv_gia_ngay'] ?? $d['chi_ktv_gia_ngay'],
                'luong_ktv_unit' => $fields['chi_ktv_gia_ca'] ?? $d['chi_ktv_gia_ca'],
                'dau_vnd_moi_lit' => $fields['chi_dau_gia'] ?? $d['chi_dau_gia'],
                'dinh_muc_km_moi_lit' => $fields['chi_dau_dinhmuc'] ?? $d['chi_dau_dinhmuc'],
            ],
            'luu_y' => 'Muốn lưu phiếu để KTT duyệt: nhập các số trên vào trang Tính lợi nhuận X-Quang (admin/profit/calculate) rồi bấm Lưu.',
        ];
    }

    // ------------------------------------------------------------------
    // xem_don_gia_mac_dinh / cap_nhat_don_gia_mac_dinh
    // ------------------------------------------------------------------

    private function listPrices(): array
    {
        $base = config('profit.defaults');
        $current = $this->settings->all();

        $prices = [];
        foreach (ProfitSettings::LABELS as $key => $label) {
            $prices[] = [
                'ma' => $key,
                'ten' => $label,
                'gia_hien_hanh' => $current[$key],
                'da_doi_so_voi_goc' => (float) $current[$key] !== (float) $base[$key],
            ];
        }

        $changes = DB::table('profit_setting_changes')->orderByDesc('id')->limit(10)
            ->get(['key', 'old_value', 'new_value', 'user_name', 'source', 'reason', 'created_at'])
            ->map(fn ($c) => [
                'ten' => ProfitSettings::LABELS[$c->key] ?? $c->key,
                'tu' => $c->old_value + 0,
                'thanh' => $c->new_value + 0,
                'nguoi_doi' => $c->user_name,
                'qua' => $c->source === 'assistant' ? 'trợ lý AI' : 'web',
                'ly_do' => $c->reason,
                'luc' => $c->created_at,
            ])->all();

        return ['don_gia' => $prices, 'thay_doi_gan_day' => $changes];
    }

    private function updatePrice(array $input, ?User $user): array
    {
        if (!$this->settings->canUpdate($user)) {
            throw new InvalidArgumentException(
                'Người dùng hiện tại không có quyền đổi giá mặc định (chỉ Admin). '
                . 'Có thể dùng giá mới cho lần tính này qua don_gia_khac, hoặc nhờ Admin cập nhật.'
            );
        }

        $key = (string) ($input['ma_don_gia'] ?? '');
        $value = $input['gia_moi'] ?? null;
        $reason = trim((string) ($input['ly_do'] ?? ''));

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('gia_moi phải là số.');
        }
        if ($reason === '') {
            throw new InvalidArgumentException('Cần ghi lý do đổi giá.');
        }

        $change = $this->settings->update($key, (float) $value, $user, 'assistant', mb_substr($reason, 0, 500));

        return [
            'da_cap_nhat' => true,
            'don_gia' => $change['label'],
            'gia_cu' => $change['old'],
            'gia_moi' => $change['new'],
            'ap_dung' => 'Từ bây giờ cho mọi lần tính lợi nhuận (trang web và trợ lý). Phiếu đã lưu trước đó giữ nguyên.',
        ];
    }

    // ------------------------------------------------------------------
    // tra_cuu_don_vi
    // ------------------------------------------------------------------

    private function findUnits(string $keyword): array
    {
        $units = $this->matchUnits($keyword, 8);

        if ($units->isEmpty()) {
            return ['tu_khoa' => $keyword, 'ket_qua' => 'Không tìm thấy đơn vị nào trong hệ thống — có thể là khách mới.', 'don_vi' => []];
        }

        $result = $units->map(function ($unit) {
            $orders = DB::table('orders')
                ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
                ->where('orders.unit_id', $unit->id)
                ->orderByDesc('order_details.ord_start_day')
                ->select('orders.id', 'order_details.ord_start_day', 'order_details.ord_select', 'order_details.ord_cty_name',
                    'orders.order_quantity', 'orders.order_cost', 'orders.order_price', 'orders.order_vat', 'orders.status_id')
                ->get();

            return [
                'ma' => $unit->unit_code,
                'ten_viet_tat' => $unit->unit_abbreviation,
                'ten_day_du' => $unit->unit_name,
                'khach' => $orders->isEmpty() ? 'mới (chưa có đơn)' : 'cũ',
                'tong_so_don' => $orders->count(),
                'ngay_chup_gan_nhat' => $orders->first()->ord_start_day ?? null,
                'don_gan_day' => $orders->take(6)->map(fn ($o) => [
                    'ma_don' => $o->id,
                    'ngay_chup' => $o->ord_start_day,
                    'dich_vu' => $o->ord_select,
                    'cong_ty_kham' => $o->ord_cty_name,
                    'so_cas' => (int) $o->order_quantity,
                    'don_gia_moi_cas' => (int) $o->order_cost,
                    'thanh_tien' => (int) $o->order_price,
                    'vat' => $o->order_vat,
                    'trang_thai' => self::ORDER_STATUS[$o->status_id] ?? (string) $o->status_id,
                ])->values()->all(),
            ];
        })->values()->all();

        return ['tu_khoa' => $keyword, 'don_vi' => $result];
    }

    // ------------------------------------------------------------------
    // xem_lich_xe
    // ------------------------------------------------------------------

    /**
     * Lịch xe theo ngày và theo buổi, dùng cùng cách tính với trang Xếp lịch xe (ScheduleBoard):
     * đơn đã xếp xe, đơn nhiều ngày, lịch GIỮ CHỖ (khách đặt trước, chưa lên đơn) và xe nghỉ BẢO TRÌ đều tính là không trống.
     */
    private function carSchedule(Carbon $from, Carbon $to): array
    {
        if ($to->lt($from)) {
            throw new InvalidArgumentException('den_ngay phải sau hoặc bằng tu_ngay.');
        }
        if ($from->diffInDays($to) > 31) {
            throw new InvalidArgumentException('Khoảng ngày tối đa là 31 ngày.');
        }

        $board = ScheduleBoard::forRange($from, $to);
        $ownCars = config('schedule.own_cars', self::MAIN_CARS);
        $halfName = ['S' => 'sáng', 'C' => 'chiều'];
        $certified = DB::table('staffs')->where('staff_certificate', 1)->pluck('staff_name')->all();

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $date = $d->toDateString();
            $halves = ["{$date}|S", "{$date}|C"];

            // Xe trống theo buổi (không có đơn, không giữ chỗ, không bảo trì)
            $freeAllDay = $freeMorning = $freeAfternoon = [];
            foreach ($ownCars as $car) {
                $s = !$board->entriesFor($car, ["{$date}|S"], true);
                $c = !$board->entriesFor($car, ["{$date}|C"], true);
                if ($s && $c) {
                    $freeAllDay[] = $car;
                } elseif ($s) {
                    $freeMorning[] = $car;
                } elseif ($c) {
                    $freeAfternoon[] = $car;
                }
            }

            $orders = $board->bookings()->filter(fn ($b) => array_intersect($b->halves, $halves))->values();
            $holds = $board->holds()->filter(fn ($h) => array_intersect($h->halves, $halves))->values();
            $maint = $board->maintenances()->filter(fn ($m) => array_intersect($m->halves, $halves))->values();

            $freeStaff = fn (string $role) => $board->staff()->where('staff_role', $role)
                ->reject(fn ($st) => ScheduleBoard::isHiredPerson($st->staff_name))
                ->filter(fn ($st) => !$board->staffConflicts($st->staff_name, $halves))
                ->pluck('staff_name')->values()->all();

            $days[] = [
                'ngay' => $date,
                'thu' => $this->weekday($d),
                'xe_chinh_trong_ca_ngay' => $freeAllDay,
                'xe_chinh_chi_trong_buoi_sang' => $freeMorning,
                'xe_chinh_chi_trong_buoi_chieu' => $freeAfternoon,
                'lich' => $orders->map(fn ($o) => [
                    'xe' => $this->carLabel($o->car_name),
                    'ma_don' => $o->id,
                    'ma_nhom_di_chung' => $o->group_code ?: null,
                    'don_vi' => $o->unit,
                    'dich_vu' => $o->ord_select,
                    'so_cas' => (int) $o->order_quantity,
                    'buoi' => $o->ord_session ?: 'Cả ngày',
                    'tai_xe' => $o->car_driver_name,
                    'ktv' => array_values(array_filter([$o->car_ktv_name_1, $o->car_ktv_name_2])),
                ])->all(),
                // Xe nghỉ bảo trì / đăng kiểm: KHÔNG nhận đơn buổi đó (đã loại khỏi danh sách xe trống)
                'xe_bao_tri' => $maint->map(fn ($m) => [
                    'xe' => $this->carLabel($m->car),
                    'buoi' => $m->maintenance_session,
                    'tu_ngay' => $m->maintenance_start->toDateString(),
                    'den_ngay' => $m->maintenance_end->toDateString(),
                    'ly_do' => $m->maintenance_note,
                ])->all(),
                'giu_cho' => $holds->map(fn ($h) => [
                    'xe' => $this->carLabel($h->car),
                    'khach' => $h->customer,
                    'buoi' => $h->hold_session,
                    'tu_ngay' => $h->hold_start->toDateString(),
                    'den_ngay' => $h->hold_end->toDateString(),
                    'so_cas_du_kien' => $h->expected_cas,
                    'tai_xe' => $h->driver,
                    'ktv' => array_values(array_filter([$h->ktv1, $h->ktv2])),
                    'ghi_chu' => $h->note,
                ])->all(),
                'tai_xe_trong_ca_ngay' => $freeStaff('TX'),
                'ktv_trong_ca_ngay' => $freeStaff('KTV'),
                'ktv_co_cchn_trong_ca_ngay' => array_values(array_intersect($freeStaff('KTV'), $certified)),
            ];
        }

        $unscheduled = DB::table('orders')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()])
            ->where('orders.schedule_status', 0)
            ->whereNotExists(fn ($q) => $q->from('car_ktvs')->whereColumn('car_ktvs.order_id', 'orders.id')->where('car_ktvs.car_active', 1))
            ->orderBy('order_details.ord_start_day')
            ->select('orders.id', 'order_details.ord_start_day', 'order_details.ord_session', 'order_details.ord_select',
                'units.unit_abbreviation', 'orders.order_quantity')
            ->get()
            ->map(fn ($o) => [
                'ma_don' => $o->id,
                'ngay' => $o->ord_start_day,
                'buoi' => $o->ord_session ?: 'Cả ngày',
                'don_vi' => $o->unit_abbreviation,
                'dich_vu' => $o->ord_select,
                'so_cas' => (int) $o->order_quantity,
            ])->values()->all();

        return [
            'tu_ngay' => $from->toDateString(),
            'den_ngay' => $to->toDateString(),
            'ghi_chu' => 'Xe chính: ' . implode(', ', $ownCars) . '. Lịch tính theo buổi (sáng / chiều; "Cả ngày" chiếm cả hai). '
                . '"giu_cho" là khách đã đặt trước nhưng chưa lên đơn: xe / người trong giữ chỗ coi như ĐÃ CÓ LỊCH với khách khác. '
                . '"xe_bao_tri" là xe nghỉ bảo trì / đăng kiểm: KHÔNG nhận đơn buổi đó, kể cả cùng đơn vị. '
                . 'Nhiều đơn của cùng một đơn vị trong cùng buổi có thể chạy chung một xe.',
            'theo_ngay' => $days,
            'don_chua_xep_xe' => $unscheduled,
        ];
    }

    // ------------------------------------------------------------------
    // xem_nguoi_di_don
    // ------------------------------------------------------------------

    /**
     * Tài xế / KTV đi các đơn đã xếp xe, kèm SĐT. SĐT ưu tiên lấy từ bảng nhân viên (staffs, trang Quản lý nhân viên —
     * luôn là số mới nhất); không có thì dùng số đã lưu lúc xếp lịch (car_ktvs). Người thuê ngoài không có SĐT.
     */
    private function crew(array $input): array
    {
        $from = $this->requireDate($input, 'tu_ngay');
        $to = !empty($input['den_ngay']) ? $this->requireDate($input, 'den_ngay') : $from->copy();
        if ($to->lt($from)) {
            throw new InvalidArgumentException('den_ngay phải sau hoặc bằng tu_ngay.');
        }
        if ($from->diffInDays($to) > 31) {
            throw new InvalidArgumentException('Khoảng ngày tối đa là 31 ngày.');
        }
        $keyword = trim((string) ($input['don_vi'] ?? ''));
        $unitIds = null;
        if ($keyword !== '') {
            $units = $this->matchUnits($keyword, 20);
            $unitIds = $units->pluck('id')->all();
        }

        $rows = DB::table('car_ktvs')
            ->join('orders', 'orders.id', '=', 'car_ktvs.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('car_ktvs.car_active', 1)
            ->whereNotNull('car_ktvs.car_name')
            ->where('order_details.ord_start_day', '<=', $to->toDateString())
            ->where(fn ($q) => $q->where('order_details.ord_end_day', '>=', $from->toDateString())
                ->orWhere(fn ($q) => $q->whereNull('order_details.ord_end_day')->where('order_details.ord_start_day', '>=', $from->toDateString())))
            ->when($unitIds !== null, fn ($q) => $q->where(fn ($q) => $q->whereIn('orders.unit_id', $unitIds ?: [0])
                ->orWhere('order_details.ord_cty_name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%')))
            ->orderBy('order_details.ord_start_day')->orderBy('car_ktvs.car_name')
            ->select('orders.id', 'orders.order_quantity', 'car_ktvs.car_name', 'car_ktvs.car_driver_name', 'car_ktvs.car_driver_phone',
                'car_ktvs.car_ktv_name_1', 'car_ktvs.car_ktv_phone_1', 'car_ktvs.car_ktv_name_2', 'car_ktvs.car_ktv_phone_2',
                'order_details.ord_start_day', 'order_details.ord_end_day', 'order_details.ord_session', 'order_details.ord_time',
                'order_details.ord_select', 'customers.customer_address',
                DB::raw("COALESCE(NULLIF(units.unit_abbreviation, ''), order_details.ord_cty_name) as unit"), 'units.unit_name')
            ->get();

        $staff = $this->staffInfo();
        $person = function (?string $name, ?string $savedPhone, string $role) use ($staff) {
            $name = trim((string) $name);
            if ($name === '') {
                return null;
            }
            if (ScheduleBoard::isHiredPerson($name)) {
                return ['ho_ten' => $name, 'vai_tro' => $role, 'sdt' => null, 'ghi_chu' => 'Người thuê ngoài, chưa có tên / SĐT trên hệ thống'];
            }
            $info = $staff[$name] ?? null;
            $phone = ($info['sdt'] ?? null) ?: $this->phone($savedPhone);

            // CCHN chỉ ghi cho KTV (tài xế chỉ ghi khi có)
            $cert = $info && ($role === 'KTV' || $info['co_cchn']) ? $info['cchn'] : null;

            return ['ho_ten' => $name, 'vai_tro' => $role, 'sdt' => $phone] + ($cert ? ['cchn' => $cert] : [])
                + ($phone === null ? ['ghi_chu' => 'Chưa có SĐT trong Quản lý nhân viên'] : []);
        };

        return [
            'tu_ngay' => $from->toDateString(),
            'den_ngay' => $to->toDateString(),
            'loc_don_vi' => $keyword !== '' ? $keyword : null,
            'so_don' => $rows->count(),
            'ghi_chu' => 'Chỉ gồm đơn ĐÃ XẾP XE. SĐT, CCHN lấy từ trang Quản lý nhân viên (mới nhất); cchn null = không có trong danh sách nhân viên. '
                . 'Đơn chưa xếp xe: xem xem_lich_xe (don_chua_xep_xe).',
            'don' => $rows->map(fn ($r) => [
                'ma_don' => $r->id,
                'ngay' => $r->ord_start_day . ($r->ord_end_day && $r->ord_end_day !== $r->ord_start_day ? ' → ' . $r->ord_end_day : ''),
                'thu' => $this->weekday(Carbon::parse($r->ord_start_day)),
                'buoi' => $r->ord_session ?: 'Cả ngày',
                'gio' => $r->ord_time ?: null,
                'don_vi' => $r->unit,
                'ten_don_vi' => $r->unit_name,
                'dia_chi' => $r->customer_address,
                'dich_vu' => $r->ord_select,
                'so_cas' => (int) $r->order_quantity,
                'xe' => $this->carLabel($r->car_name),
                'nguoi_di' => array_values(array_filter([
                    $person($r->car_driver_name, $r->car_driver_phone, 'Tài xế'),
                    $person($r->car_ktv_name_1, $r->car_ktv_phone_1, 'KTV'),
                    $person($r->car_ktv_name_2, $r->car_ktv_phone_2, 'KTV'),
                ])),
            ])->values()->all(),
        ];
    }

    // ------------------------------------------------------------------
    // xem_nhan_su
    // ------------------------------------------------------------------

    private function staffList(array $input): array
    {
        $role = in_array($input['vai_tro'] ?? '', ['TX', 'KTV'], true) ? $input['vai_tro'] : null;
        $onlyCert = (bool) ($input['chi_co_cchn'] ?? false);
        $name = mb_strtolower(trim((string) ($input['ten'] ?? '')));

        $list = collect($this->staffInfo())
            ->reject(fn ($p) => ScheduleBoard::isHiredPerson($p['ho_ten']))
            ->when($role, fn ($c) => $c->where('vai_tro', $role))
            ->when($onlyCert, fn ($c) => $c->filter(fn ($p) => $p['co_cchn']))
            ->when($name !== '', fn ($c) => $c->filter(fn ($p) => str_contains(mb_strtolower($p['ho_ten']), $name)))
            ->sortBy(fn ($p) => ($p['vai_tro'] === 'KTV' ? 0 : 1) . ($p['co_cchn'] ? 0 : 1) . $p['ho_ten'])
            ->values();

        return [
            'so_nguoi' => $list->count(),
            'ghi_chu' => 'Theo trang Quản lý nhân viên. "KTV Thuê" / "TX Thuê" (thuê ngoài) không liệt kê. '
                . 'Cần KTV có CCHN còn trống ngày nào: dùng xem_lich_xe (ktv_co_cchn_trong_ca_ngay).',
            'nhan_su' => $list->map(fn ($p) => [
                'ho_ten' => $p['ho_ten'], 'vai_tro' => $p['vai_tro'], 'sdt' => $p['sdt'], 'cchn' => $p['cchn'],
            ])->all(),
        ];
    }

    /** @return array<string, array{ho_ten: string, vai_tro: string, sdt: ?string, co_cchn: bool, cchn: string}> theo họ tên */
    private function staffInfo(): array
    {
        $out = [];
        foreach (DB::table('staffs')->get(['staff_name', 'staff_role', 'staff_phone', 'staff_certificate', 'staff_certificate_note']) as $st) {
            $has = (bool) $st->staff_certificate;
            $note = trim((string) $st->staff_certificate_note);
            $out[$st->staff_name] = [
                'ho_ten' => $st->staff_name,
                'vai_tro' => $st->staff_role,
                'sdt' => $this->phone($st->staff_phone),
                'co_cchn' => $has,
                'cchn' => $has ? 'Có CCHN' . ($note !== '' ? " ({$note})" : '') : 'Không có CCHN',
            ];
        }

        return $out;
    }

    /** SĐT về dạng 0xxxxxxxxx (số lưu dạng Zalo 84xxxxxxxxx); rỗng → null */
    private function phone(?string $raw): ?string
    {
        $phone = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($phone, '84') && strlen($phone) === 11) {
            $phone = '0' . substr($phone, 2);
        }

        return $phone !== '' ? $phone : null;
    }

    // ------------------------------------------------------------------
    // soan_hop_dong
    // ------------------------------------------------------------------

    private function draftContract(array $in): array
    {
        $pricing = app(\App\Services\Accounting\ContractPricing::class);
        $pick = function (?string $kw) use ($pricing) {
            $kw = trim((string) $kw);
            if ($kw === '') {
                return null;
            }
            $units = $pricing->findUnits($kw, 5);

            return $units->first(fn ($u) => mb_strtolower((string) $u->unit_abbreviation) === mb_strtolower($kw) || (string) $u->unit_code === $kw) ?? $units->first();
        };
        $unit = $pick($in['don_vi'] ?? null);
        if (!empty($in['don_vi']) && !$unit) {
            return ['loi' => 'Không tìm thấy đơn vị "' . $in['don_vi'] . '" trên web. Nếu là khách mới, bỏ don_vi và ghi ben_a (tên, MST, địa chỉ…).'];
        }
        $refUnit = !empty($in['dua_theo']) ? $pick($in['dua_theo']) : $unit;
        if (!empty($in['dua_theo']) && !$refUnit) {
            return ['loi' => 'Không tìm thấy đơn vị mẫu "' . $in['dua_theo'] . '".'];
        }
        $wantType = $in['loai'] ?? null;
        $ref = $refUnit ? \App\Models\CustomerContract::where('unit_id', $refUnit->id)->when($wantType, fn ($q) => $q->where('type', $wantType))
            ->orderByRaw("type = 'nguyen_tac' DESC")->orderByDesc('signed_on')->orderByDesc('id')->first() : null;
        if (!empty($in['dua_theo']) && !$ref) {
            return ['loi' => ($refUnit->unit_abbreviation ?: $refUnit->unit_name) . ' chưa có hợp đồng ' . ($wantType === 'thoi_vu' ? 'thời vụ ' : '') . 'trên web để làm mẫu. Ghi bac_gia / gia_tron_goi trực tiếp.'];
        }
        $d = $ref ? \App\Services\Contracts\ContractDrafter::fromContract($ref) : [];
        $d['type'] = $wantType ?: ($d['type'] ?? 'nguyen_tac');

        // Bên A: dữ liệu web (đơn vị + hợp đồng cũ của chính đơn vị) rồi ghi đè theo ben_a
        $own = $unit ? \App\Models\CustomerContract::where('unit_id', $unit->id)->orderByDesc('signed_on')->first() : null;
        $a = $in['ben_a'] ?? [];
        $d['party_a'] = [
            'name' => $a['ten'] ?? ($own->party_a ?? $unit->unit_name ?? ''),
            'address' => $a['dia_chi'] ?? ($unit->unit_address ?? ''),
            'tax_code' => $a['ma_so_thue'] ?? ($own->party_a_tax_code ?? $unit->unit_tax_code ?? ''),
            'phone' => $a['dien_thoai'] ?? '', 'fax' => $a['fax'] ?? '', 'account' => $a['tai_khoan'] ?? '', 'bank' => $a['ngan_hang'] ?? '',
            'representative' => $a['dai_dien'] ?? '', 'title' => $a['chuc_vu'] ?? '', 'authorization' => $a['uy_quyen'] ?? '',
            'abbreviation' => $unit->unit_abbreviation ?? ($a['ten'] ?? ''),
        ];
        // Mã trong số hợp đồng: dùng lại mã của hợp đồng cũ của đơn vị (vd MEDICEN-YD115) nếu có
        if ($own && preg_match('/MEDICEN-([A-Z0-9]+)/u', (string) $own->contract_no, $mm)) {
            $d['code'] = $mm[1];
        }
        if (!$d['party_a']['name']) {
            return ['loi' => 'Cần don_vi (đơn vị trên web) hoặc ben_a.ten.'];
        }
        if ($unit && empty($d['party_a']['address'])) {
            $d['party_a']['address'] = DB::table('units')->where('id', $unit->id)->value('unit_address') ?? '';
        }

        // Giá
        $map = ['cach_tinh' => 'price_mode', 'gia_tron_goi' => 'package_price', 'ban_kinh_km' => 'radius_km', 'so_ngay_thanh_toan' => 'payment_days',
            'thoi_han_thang' => 'valid_months', 'tu_gia_han' => 'auto_renew', 'ngay_ky' => 'signed_on', 'so_hd' => 'contract_no', 'ma_viet_tat' => 'code',
            'ngay_chup' => 'service_date', 'dia_diem' => 'service_location', 'cong_ty_duoc_chup' => 'end_company', 'km_khu_hoi' => 'km', 'tra_ket_qua' => 'result_delivery'];
        foreach ($map as $k => $f) {
            if (array_key_exists($k, $in) && $in[$k] !== '' && $in[$k] !== null) {
                $d[$f] = $in[$k];
            }
        }
        if (!empty($in['bac_gia'])) {
            $d['tiers'] = array_map(fn ($t) => ['min_cases' => $t['tu_ca'] ?? 0, 'max_cases' => $t['den_ca'] ?? 0, 'unit' => $t['tinh_theo'] ?? 'ca', 'price' => $t['gia']], $in['bac_gia']);
            $d['price_mode'] = $in['cach_tinh'] ?? 'theo_ca';
        }
        $changed = [];
        foreach ($in['doi_gia_bac'] ?? [] as $chg) {
            foreach ($d['tiers'] ?? [] as $i => $t) {
                if ((int) ($t['min_cases'] ?? 0) === (int) $chg['tu_ca']) {
                    $changed[] = 'bậc từ ' . (int) $chg['tu_ca'] . ' ca: ' . number_format((int) $t['price'], 0, ',', '.') . ' → ' . number_format((int) $chg['gia'], 0, ',', '.');
                    $d['tiers'][$i]['price'] = (int) $chg['gia'];
                }
            }
        }
        if (!empty($in['doi_gia_bac']) && count($changed) < count($in['doi_gia_bac'])) {
            return ['loi' => 'Có bậc trong doi_gia_bac không khớp mức "từ ca" của bảng giá mẫu.', 'bac_gia_mau' => $d['tiers'] ?? []];
        }
        if (isset($in['cong_them'])) {
            $d['addons'] = array_map(fn ($x) => ['name' => $x['ten'], 'price_per_case' => $x['gia_moi_ca']], $in['cong_them']);
        }
        if (isset($in['phu_thu'])) {
            $d['surcharges'] = array_map(fn ($x) => ['from_km' => $x['tu_km'] ?? null, 'to_km' => $x['den_km'] ?? null, 'min_amount' => $x['tu_tien'] ?? null,
                'max_amount' => $x['den_tien'] ?? null, 'vat_included' => $x['gom_vat'] ?? false], $in['phu_thu']);
        }
        if ($d['type'] === 'thoi_vu' && $ref && $ref->type !== 'thoi_vu') {
            unset($d['tiers'], $d['addons'], $d['surcharges']);
        }

        try {
            $r = app(\App\Services\Contracts\ContractDrafter::class)->draft($d);
        } catch (\InvalidArgumentException $e) {
            return ['loi' => $e->getMessage()];
        }

        return [
            'tai_file_word' => route('contracts.draft_download', $r['file']),
            'so_hd' => $r['so_hd'],
            'dua_theo_hop_dong' => $ref ? (($ref->contract_no ?: '#' . $ref->id) . ' của ' . ($refUnit->unit_abbreviation ?? '')) : 'không có (dùng giá ghi trực tiếp)',
            'da_doi_gia' => $changed ?: null,
            'tom_tat' => $r['tom_tat'],
            'canh_bao' => $r['canh_bao'],
            'luu_y' => 'File giữ ' . config('contract_templates.keep_days') . ' ngày. Sales mở Word kiểm tra, điền chỗ còn dòng chấm. Khách ký xong thì tải bản đã ký lên Hành chính → Hợp đồng.',
        ];
    }

    // ------------------------------------------------------------------
    // xem_cong_no
    // ------------------------------------------------------------------

    private function debts(string $keyword): array
    {
        $units = $this->matchUnits($keyword, 5);
        if ($units->isEmpty()) {
            return ['tu_khoa' => $keyword, 'ket_qua' => 'Không tìm thấy đơn vị.', 'don_vi' => []];
        }

        $today = now()->startOfDay();

        $result = $units->map(function ($unit) use ($today) {
            $rows = DB::table('accountants')
                ->join('orders', 'orders.id', '=', 'accountants.order_id')
                ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
                ->where('orders.unit_id', $unit->id)
                ->where('accountants.accountant_status', 0) // 0 = chưa thanh toán
                ->where('orders.status_id', '!=', 0)
                ->orderBy('accountants.accountant_date')
                ->select('orders.id', 'order_details.ord_start_day', 'accountants.accountant_number', 'accountants.accountant_date',
                    'accountants.accountant_deadline', 'accountants.accountant_owe', 'accountants.accountant_amount_paid')
                ->get();

            $invoices = $rows->map(function ($r) use ($today) {
                // accountant_owe đã là "Còn nợ" (= Thành tiền − Đã thanh toán), không trừ thêm lần nữa
                $remaining = max(0, (int) $r->accountant_owe);
                $issued = $this->parseDate($r->accountant_date);
                $due = $issued && is_numeric($r->accountant_deadline)
                    ? $issued->copy()->addDays((int) $r->accountant_deadline)
                    : null;
                $overdue = $due && $due->lt($today) ? $due->diffInDays($today) : 0;

                return [
                    'ma_don' => $r->id,
                    'ngay_chup' => $r->ord_start_day,
                    'so_hoa_don' => $r->accountant_number,
                    'ngay_xuat_hd' => $issued?->toDateString(),
                    'han_thanh_toan' => $due?->toDateString(),
                    'con_no' => $remaining,
                    'so_ngay_qua_han' => $overdue,
                    'tinh_trang' => !$issued ? 'chưa xuất hóa đơn' : ($overdue > 0 ? 'quá hạn' : 'trong hạn'),
                ];
            })->filter(fn ($i) => $i['con_no'] > 0)->values();

            $overdueItems = $invoices->where('so_ngay_qua_han', '>', 0);

            $credit = app(\App\Services\Accounting\CreditPolicy::class)->forUnit((int) $unit->id);
            $follow = app(\App\Services\Accounting\DebtCollection::class)->list()->firstWhere('unit_id', (int) $unit->id);

            return [
                'ten_viet_tat' => $unit->unit_abbreviation,
                'ten_day_du' => $unit->unit_name,
                // Hạn mức tín dụng (kế toán đặt ở trang Phân tích tài chính; chưa đặt = gợi ý 2,5 tháng doanh thu)
                'han_muc' => [
                    'han_muc_no' => $credit['limit'],
                    'nguon' => $credit['is_manual'] ? 'kế toán đặt' : 'gợi ý tự động',
                    'dang_no_ke_ca_chua_xuat_hd' => $credit['owe'],
                    'da_dung_pct' => $credit['used_pct'],
                    'qua_han_toi_da_ngay' => $credit['max_days'],
                    'trang_thai' => $credit['status_label'],
                    'can_xu_ly_truoc_khi_nhan_don' => in_array($credit['status'], ['vuot_han_muc', 'qua_han_lau'], true),
                ],
                // Thu nợ: ai phụ trách, lần nhắc gần nhất, khách hẹn trả (Phân tích tài chính → Thu nợ)
                'thu_no' => $follow ? [
                    'phu_trach' => $follow['assignee'], 'trang_thai' => $follow['status_label'],
                    'lan_nhac_gan_nhat' => $follow['last'] ? $follow['last']['date']->format('d/m/Y') . ' · ' . $follow['last']['channel'] . ($follow['last']['note'] ? ': ' . $follow['last']['note'] : '') : null,
                    'hen_tra' => $follow['promise'] ? $follow['promise']['date']->format('d/m/Y') . ($follow['promise']['amount'] ? ' · ' . number_format($follow['promise']['amount'], 0, ',', '.') . ' đ' : '') : null,
                ] : null,
                'tong_con_no' => $invoices->sum('con_no'),
                'tong_qua_han' => $overdueItems->sum('con_no'),
                'qua_han_lau_nhat_ngay' => (int) $overdueItems->max('so_ngay_qua_han'),
                'hoa_don' => $invoices->take(20)->all(),
            ];
        })->values()->all();

        return ['tu_khoa' => $keyword, 'ngay_tinh' => $today->toDateString(), 'don_vi' => $result];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function matchUnits(string $keyword, int $limit)
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';

        return DB::table('units')
            ->where(fn ($q) => $q->where('unit_abbreviation', 'like', $like)
                ->orWhere('unit_name', 'like', $like)
                ->orWhere('unit_code', 'like', $like))
            ->orderBy('unit_abbreviation')
            ->limit($limit)
            ->get(['id', 'unit_code', 'unit_abbreviation', 'unit_name']);
    }

    private function requireString(array $input, string $key): string
    {
        $value = trim((string) ($input[$key] ?? ''));
        if ($value === '' || mb_strlen($value) > 100) {
            throw new InvalidArgumentException("Thiếu hoặc sai tham số {$key}.");
        }

        return $value;
    }

    private function requireDate(array $input, string $key): Carbon
    {
        $value = (string) ($input[$key] ?? '');
        $date = $this->parseDate($value);
        if (!$date || $date->toDateString() !== $value) {
            throw new InvalidArgumentException("Tham số {$key} phải có dạng YYYY-MM-DD.");
        }

        return $date;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function carLabel($car): string
    {
        $car = trim((string) $car);

        return self::CAR_LABELS[$car] ?? "Xe {$car}";
    }

    private function weekday(Carbon $date): string
    {
        return ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][$date->dayOfWeek];
    }
}
