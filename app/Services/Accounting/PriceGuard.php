<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Kiểm soát giá thấp / chiết khấu cao (cần kế toán duyệt):
 *  - lỗ ước tính (giá vốn chuyến theo OrderCostEstimator lớn hơn doanh thu),
 *  - biên lãi ước tính dưới config('finance.price.min_margin_pct'),
 *  - chiết khấu trên config('finance.price.max_discount_pct').
 * Giá sàn / ca = chi phí trực tiếp / số ca, cộng phần phí quản lý, VAT và biên tối thiểu.
 * Duyệt lưu ở bảng price_approvals (Kế toán và Admin, không gồm SubAdmin). Khi có hợp đồng: so thêm với giá đã ký.
 */
class PriceGuard
{
    public function __construct(private OrderCostEstimator $cost)
    {
    }

    /** Các đơn cần xem lại trong khoảng ngày chụp (mặc định 30 ngày trước → 60 ngày tới) */
    public function list(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= Carbon::today()->subDays(30);
        $to ??= Carbon::today()->addDays(60);
        $approvals = DB::table('price_approvals')->get()->keyBy('order_id');
        $names = DB::table('users')->pluck('name', 'id');

        return $this->rows(fn ($q) => $q->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()]))
            ->map(fn ($r) => $this->evaluate($r, $approvals[$r->id] ?? null, $names))
            ->filter(fn ($x) => $x['flags'])
            ->sortBy(fn ($x) => [$x['approved'] ? 1 : 0, $x['date']])
            ->values();
    }

    public function forOrder(int $orderId): ?array
    {
        $r = $this->rows(fn ($q) => $q->where('orders.id', $orderId))->first();
        if (!$r) {
            return null;
        }

        return $this->evaluate($r, DB::table('price_approvals')->where('order_id', $orderId)->first(), DB::table('users')->pluck('name', 'id'));
    }

    private function rows(callable $scope): Collection
    {
        $q = DB::table('orders')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('accountants', 'accountants.order_id', '=', 'orders.id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->leftJoin('car_ktvs', function ($j) {
                $j->on('car_ktvs.order_id', '=', 'orders.id')->where('car_ktvs.car_active', 1);
            })
            ->where('orders.status_id', '!=', 0)->where('orders.order_surcharge', 0);
        $scope($q);

        return $q->get(['orders.id', 'orders.order_quantity', 'orders.order_price', 'orders.order_cost', 'orders.order_discount', 'orders.order_percent_discount',
            'orders.order_vat', 'orders.order_surcharge', 'order_details.ord_start_day', 'order_details.ord_end_day', 'order_details.ord_select',
            'order_details.ord_doctor_read', 'order_details.ord_cty_name', 'units.unit_abbreviation', 'accountants.accountant_distance', 'accountants.accountant_10X12',
            'car_ktvs.car_name', 'car_ktvs.car_driver_name', 'car_ktvs.car_ktv_name_1', 'car_ktvs.car_ktv_name_2', 'car_ktvs.work_over_250'])->unique('id');
    }

    private function evaluate($r, $approval, $names): array
    {
        $cfg = config('finance.price');
        $est = $this->cost->estimate($r);
        $qty = (int) $r->order_quantity;
        $price = (int) $r->order_price;
        $discount = (int) $r->order_discount;
        $discountPct = $price > 0 ? round($discount / $price * 100, 1) : (float) preg_replace('/[^\d.]/', '', (string) $r->order_percent_discount);
        $perCase = $qty > 0 ? (int) round(($price - $discount) / $qty) : null;

        // Giá sàn / ca: doanh thu tối thiểu để còn biên min% sau phí quản lý và VAT
        $mgmt = (float) (app(\App\Services\ProfitSettings::class)->all()['chi_quanly_phantram'] ?? 0) / 100;
        $vatRate = (float) preg_replace('/[^\d.]/', '', (string) $r->order_vat) / 100;
        $vatShare = $vatRate > 0 ? $vatRate / (1 + $vatRate) : 0;
        $keep = 1 - $mgmt - $vatShare - $cfg['min_margin_pct'] / 100;
        $floor = $qty > 0 && $keep > 0 ? (int) (ceil($est['chi_phi_truc_tiep'] / $keep / $qty / 100) * 100) : null;

        $flags = [];
        if ($qty > 0 && $price > 0) {
            if ($est['lai_uoc_tinh'] < 0) {
                $flags[] = 'Lỗ ước tính ' . number_format(-$est['lai_uoc_tinh'], 0, ',', '.') . ' đ';
            } elseif ($est['bien_lai_pct'] !== null && $est['bien_lai_pct'] < $cfg['min_margin_pct']) {
                $flags[] = 'Biên lãi ước tính ' . $est['bien_lai_pct'] . '% < ' . $cfg['min_margin_pct'] . '%';
            }
        }
        if ($discountPct > $cfg['max_discount_pct']) {
            $flags[] = 'Chiết khấu ' . $discountPct . '% > ' . $cfg['max_discount_pct'] . '%';
        }

        return [
            'order_id' => $r->id,
            'unit' => $r->unit_abbreviation,
            'company' => $r->ord_cty_name,
            'date' => $r->ord_start_day,
            'car' => CfoAnalytics::carLabel($r->car_name),
            'qty' => $qty,
            'km' => (int) preg_replace('/\D/', '', (string) $r->accountant_distance),
            'revenue' => $est['doanh_thu'],
            'cost' => $est['tong_chi_phi'],
            'profit' => $est['lai_uoc_tinh'],
            'margin' => $est['bien_lai_pct'],
            'per_case' => $perCase,
            'floor_per_case' => $floor,
            'discount_pct' => $discountPct,
            'flags' => $flags,
            'approved' => (bool) $approval,
            'approved_by' => $approval ? ($names[$approval->approved_by] ?? null) : null,
            'approved_at' => $approval?->approved_at,
            'approval_note' => $approval?->note,
        ];
    }
}
