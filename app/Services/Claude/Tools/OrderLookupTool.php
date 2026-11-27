<?php

namespace App\Services\Claude\Tools;

use App\Services\Accounting\OrderCostEstimator;
use App\Services\Accounting\PriceGuard;
use Illuminate\Support\Facades\DB;

/**
 * Tool "xem_don_hang" (Trợ lý Sales + Kế toán): chi tiết một đơn theo mã — đơn vị, ngày chụp, số ca, giá, chiết khấu, xe / người đi,
 * km, in phim, công nợ, và LÃI ƯỚC TÍNH (chi phí chuyến theo đơn giá mặc định, giống Phân tích tài chính → Duyệt giá).
 * Dùng khi người dùng hỏi "đơn này…" từ khung chat nổi (trang đang xem ghi mã đơn). Không trả số điện thoại khách.
 */
class OrderLookupTool
{
    public static function definition(): array
    {
        return [
            'name' => 'xem_don_hang',
            'description' => 'Chi tiết MỘT đơn hàng theo mã đơn: đơn vị, công ty, ngày chụp, buổi, dịch vụ, số ca, đơn giá, thành tiền, chiết khấu, VAT, '
                . 'xe và người đi, km, in phim, bác sĩ đọc, trạng thái, nhóm đi chung, công nợ (số HĐ, đã trả, còn nợ) và LÃI ƯỚC TÍNH của đơn '
                . '(chi phí chuyến theo đơn giá mặc định, biên lãi, giá sàn / ca). Khi người dùng hỏi "đơn này" và ngữ cảnh trang có "đơn #…", '
                . 'GỌI TOOL NÀY với mã đó trước, không hỏi lại thông tin đã có trên đơn.',
            'input_schema' => [
                'type' => 'object',
                'properties' => ['ma_don' => ['type' => 'integer', 'description' => 'Mã đơn (số sau dấu #)']],
                'required' => ['ma_don'],
                'additionalProperties' => false,
            ],
        ];
    }

    public function call(array $in): array
    {
        $id = (int) ($in['ma_don'] ?? 0);
        $o = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->leftJoin('accountants', 'accountants.order_id', '=', 'orders.id')
            ->leftJoin('car_ktvs', fn ($j) => $j->on('car_ktvs.order_id', '=', 'orders.id')->where('car_ktvs.car_active', 1))
            ->leftJoin('order_groups', 'order_groups.id', '=', 'orders.order_group_id')
            ->where('orders.id', $id)
            ->first(['orders.*', 'order_details.*', 'orders.id as order_id', 'units.unit_abbreviation', 'units.unit_name',
                'accountants.accountant_distance', 'accountants.accountant_10X12', 'accountants.accountant_number', 'accountants.accountant_date',
                'accountants.accountant_amount_paid', 'accountants.accountant_owe', 'accountants.accountant_day_payment', 'accountants.accountant_status',
                'car_ktvs.car_name', 'car_ktvs.car_driver_name', 'car_ktvs.car_ktv_name_1', 'car_ktvs.car_ktv_name_2', 'car_ktvs.work_over_250', 'order_groups.code as group_code']);
        if (!$o) {
            return ['loi' => "Không có đơn #{$id}."];
        }
        $status = DB::table('status')->pluck('status_name', 'id');
        $d = fn ($v) => $v ? date('d/m/Y', strtotime($v)) : null;
        $est = (int) $o->status_id === 0 ? null : app(OrderCostEstimator::class)->estimate($o);
        $guard = app(PriceGuard::class)->forOrder($id);
        $people = array_values(array_filter([$o->car_driver_name, $o->car_ktv_name_1, $o->car_ktv_name_2]));

        return array_filter([
            'ma_don' => $id,
            'trang_thai' => $status[$o->status_id] ?? (string) $o->status_id,
            'don_vi' => trim(($o->unit_abbreviation ?? '') . ' — ' . ($o->unit_name ?? ''), ' —'),
            'cong_ty_duoc_chup' => $o->ord_cty_name,
            'ngay_chup' => $d($o->ord_start_day) . ($o->ord_end_day && $o->ord_end_day !== $o->ord_start_day ? ' → ' . $d($o->ord_end_day) : ''),
            'buoi' => $o->ord_session ?: null,
            'gio' => $o->ord_time ?: null,
            'dich_vu' => $o->ord_select,
            'loai' => $o->order_surcharge ? 'Đơn phụ thu' : null,
            'so_ca' => (int) $o->order_quantity,
            'don_gia_ca' => (int) $o->order_cost ?: null,
            'tron_goi' => (int) $o->order_all_in_one === 1 ? 'Có' : null,
            'thanh_tien' => (int) $o->order_price,
            'chiet_khau' => (int) $o->order_discount ?: null,
            'chiet_khau_ghi' => $o->order_percent_discount ?: null,
            'vat' => $o->order_vat ?: null,
            'nhom_di_chung' => $o->group_code,
            'xe' => $o->car_name,
            'nguoi_di' => $people ?: null,
            'km_khu_hoi' => (int) $o->accountant_distance ?: null,
            'in_phim_10x12' => $o->accountant_10X12 ?: null,
            'bac_si_doc' => $o->ord_doctor_read ?: null,
            'ghi_chu' => $o->ord_note ? mb_strimwidth(strip_tags($o->ord_note), 0, 300, '…') : null,
            'cong_no' => array_filter([
                'so_hoa_don' => $o->accountant_number ?: null, 'ngay_xuat' => $d($o->accountant_date),
                'da_tra' => (int) $o->accountant_amount_paid ?: null, 'con_no' => (int) $o->accountant_owe ?: null,
                'han_thanh_toan' => $d($o->accountant_day_payment), 'da_thanh_toan_xong' => (int) $o->accountant_status === 1 ? true : null,
            ]) ?: null,
            'lai_uoc_tinh' => $est ? array_filter([
                'doanh_thu' => $est['doanh_thu'], 'chi_phi_truc_tiep' => $est['chi_phi_truc_tiep'], 'phi_quan_ly' => $est['phi_quan_ly'],
                'vat_phai_nop' => $est['vat_phai_nop'] ?? null, 'tong_chi_phi' => $est['tong_chi_phi'], 'lai' => $est['lai_uoc_tinh'], 'bien_lai_pct' => $est['bien_lai_pct'],
                'chi_tiet_chi_phi' => $est['chi_tiet'] ?: null,
                'gia_binh_quan_ca' => $guard['per_case'] ?? null, 'gia_san_ca' => $guard['floor_per_case'] ?? null,
                'canh_bao_gia' => !empty($guard['flags']) ? $guard['flags'] : null,
                'cach_tinh' => 'Chi phí chuyến theo đơn giá mặc định (xăng, cầu đường, công TX / KTV, phim, phí quản lý, VAT); km lấy từ công nợ nếu đã nhập, chưa có thì ước theo mặc định.',
            ], fn ($v) => $v !== null) : null,
            'xem_tren_web' => route('order.edit', $id),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
