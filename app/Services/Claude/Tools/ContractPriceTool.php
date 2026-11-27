<?php

namespace App\Services\Claude\Tools;

use App\Models\User;
use App\Services\Accounting\ContractPriceAudit;

/**
 * Tool Trợ lý Kế toán: đối chiếu giá các đơn ở trang Công nợ với hợp đồng (kiem_tra_gia_hop_dong) và sửa giá khi kế toán đồng ý (sua_gia_don).
 * Xem App\Services\Accounting\ContractPriceAudit.
 */
class ContractPriceTool
{
    public static function definitions(): array
    {
        $date = fn (string $d) => ['type' => 'string', 'description' => $d . ' (YYYY-MM-DD)'];

        return [
            [
                'name' => 'kiem_tra_gia_hop_dong',
                'description' => 'Đối chiếu Thành tiền các đơn ở trang Công nợ với GIÁ HỢP ĐỒNG (bậc giá theo số ca, giá trọn gói đợt, nhóm đi chung). '
                    . 'Trả về số đơn đúng giá, các đơn THU THIẾU / THU NHIỀU HƠN (giá hiện tại, giá theo hợp đồng, chênh lệch, đơn giá / ca đúng, căn cứ, '
                    . 'đã xuất hóa đơn chưa, đơn đã chốt), đơn vị chưa có hợp đồng. pham_vi "trang_dang_loc" = đúng các đơn kế toán đang lọc trên trang Công nợ '
                    . '(dùng khi ngữ cảnh trang ghi "trang Công nợ đang lọc …" và người dùng nói "các đơn này / đang lọc"); "ma_don" = danh sách mã đơn; '
                    . '"don_vi" = theo đơn vị và / hoặc khoảng ngày chụp.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'pham_vi' => ['type' => 'string', 'enum' => ['trang_dang_loc', 'ma_don', 'don_vi']],
                        'ma_don' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Khi pham_vi = ma_don'],
                        'don_vi' => ['type' => 'string', 'description' => 'Tên viết tắt / tên / mã đơn vị (pham_vi = don_vi)'],
                        'tu_ngay' => $date('Ngày chụp từ (pham_vi = don_vi; mặc định đầu năm)'),
                        'den_ngay' => $date('Ngày chụp đến (pham_vi = don_vi; mặc định 60 ngày tới)'),
                    ],
                    'required' => ['pham_vi'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'sua_gia_don',
                'description' => 'SỬA GIÁ đơn ở trang Công nợ (ghi vào dữ liệu thật). CHỈ GỌI SAU KHI kế toán đã đồng ý rõ ràng trong chat với đúng danh sách thay đổi '
                    . 'bạn vừa liệt kê (mã đơn, giá cũ → giá mới). theo = "hop_dong": máy chủ tự tính lại giá theo hợp đồng; theo = "nhap": dùng don_gia_ca '
                    . '(Thành tiền = đơn giá × số ca) hoặc thanh_tien (trọn gói) kế toán đưa. Lưu như kế toán sửa tay trên trang: tính lại chiết khấu, lợi nhuận, '
                    . 'còn nợ, đơn chuyển "Đã cập nhật doanh thu"; đơn đã chốt không sửa được. Ghi lịch sử chỉnh sửa và nhật ký hoạt động.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'cac_don' => [
                            'type' => 'array',
                            'maxItems' => 100,
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'ma_don' => ['type' => 'integer'],
                                    'theo' => ['type' => 'string', 'enum' => ['hop_dong', 'nhap']],
                                    'don_gia_ca' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Khi theo = nhap: đơn giá / ca mới (đã gồm VAT như trên đơn)'],
                                    'thanh_tien' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Khi theo = nhap và giá trọn gói: thành tiền mới'],
                                ],
                                'required' => ['ma_don', 'theo'],
                                'additionalProperties' => false,
                            ],
                        ],
                        'ke_toan_da_dong_y' => ['type' => 'boolean', 'description' => 'true chỉ khi kế toán vừa đồng ý trong chat'],
                    ],
                    'required' => ['cac_don', 'ke_toan_da_dong_y'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function call(string $name, array $in, ?User $user): array
    {
        if (!$user) {
            return ['loi' => 'Không rõ người dùng.'];
        }
        $audit = app(ContractPriceAudit::class);
        if ($name === 'kiem_tra_gia_hop_dong') {
            return $audit->check($in, $user);
        }
        if (empty($in['ke_toan_da_dong_y'])) {
            return ['loi' => 'Chưa sửa: cần liệt kê thay đổi và chờ kế toán đồng ý trong chat trước.'];
        }

        return $audit->apply((array) ($in['cac_don'] ?? []), $user);
    }
}
