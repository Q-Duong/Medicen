<?php

namespace App\Services\Accountant;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Danh sách đơn còn nợ trên trang Công nợ (mảng XQ), lọc theo tình trạng hạn thanh toán.
 * Dùng chung cho trợ lý Kế toán (cong_no_web) và trợ lý Sales (xem_cong_no).
 *
 * Hạn thanh toán = Ngày xuất hóa đơn + Số ngày hạn (accountant_deadline). Đơn chưa xuất hóa đơn chưa có hạn.
 * "Còn nợ" = accountants.accountant_owe > 0 (đơn đã hủy, status 0, không tính).
 */
class DebtList
{
    public const FILTERS = ['qua_han', 'sap_den_han', 'chua_xuat_hd'];

    private const LIMIT = 80;

    public function find(string $filter, int $days = 7, ?string $keyword = null, ?int $year = null): array
    {
        if (!in_array($filter, self::FILTERS, true)) {
            throw new InvalidArgumentException('loc phải là một trong: ' . implode(', ', self::FILTERS));
        }
        $days = max(1, min(90, $days));
        $today = Carbon::today();
        $like = $keyword ? '%' . addcslashes($keyword, '%_\\') . '%' : null;

        $rows = DB::table('accountants')
            ->join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)
            ->where('accountants.accountant_owe', '>', 0)
            ->when($year, fn ($q) => $q->whereYear('order_details.ord_start_day', $year))
            ->when($like, fn ($q) => $q->where(fn ($w) => $w->where('units.unit_abbreviation', 'like', $like)
                ->orWhere('units.unit_name', 'like', $like)->orWhere('units.unit_code', 'like', $like)))
            ->select('orders.id', 'units.unit_abbreviation', 'order_details.ord_start_day', 'order_details.ord_cty_name',
                'accountants.accountant_number', 'accountants.accountant_date', 'accountants.accountant_deadline', 'accountants.accountant_owe')
            ->get()
            ->map(function ($r) use ($today) {
                $issued = self::date($r->accountant_date);
                $due = $issued && is_numeric($r->accountant_deadline) ? $issued->copy()->addDays((int) $r->accountant_deadline) : null;

                return [
                    'ma_don' => $r->id,
                    'don_vi' => $r->unit_abbreviation,
                    'cong_ty' => $r->ord_cty_name,
                    'ngay_chup' => $r->ord_start_day,
                    'so_hd' => $r->accountant_number,
                    'ngay_hd' => $issued?->toDateString(),
                    'han_tt' => $due?->toDateString(),
                    'con_no' => (int) $r->accountant_owe,
                    'qua_han_ngay' => $due && $due->lt($today) ? $due->diffInDays($today) : 0,
                    'con_ngay_den_han' => $due && $due->gte($today) ? $today->diffInDays($due) : null,
                    '_due' => $due,
                    '_issued' => $issued,
                ];
            });

        $until = $today->copy()->addDays($days);
        [$picked, $scope] = match ($filter) {
            'qua_han' => [$rows->filter(fn ($r) => $r['qua_han_ngay'] > 0)->sortByDesc('qua_han_ngay'), 'Đơn đã quá hạn thanh toán (quá hạn lâu nhất trước)'],
            'sap_den_han' => [$rows->filter(fn ($r) => $r['_due'] && $r['_due']->betweenIncluded($today, $until))->sortBy(fn ($r) => $r['_due']->timestamp),
                'Đơn đến hạn thanh toán từ hôm nay đến ' . $until->format('d/m/Y') . " ({$days} ngày tới)"],
            'chua_xuat_hd' => [$rows->filter(fn ($r) => !$r['_issued'])->sortBy('ngay_chup'), 'Đơn còn nợ nhưng chưa xuất hóa đơn (chưa có hạn thanh toán)'],
        };

        $list = $picked->values()->map(fn ($r) => array_diff_key($r, ['_due' => 1, '_issued' => 1]));

        return [
            'ngay_tinh' => $today->toDateString(),
            'nguon' => 'Trang Công nợ trên web Medicen (mảng XQ, số mới nhất)',
            'loc' => $scope,
            'don_vi_loc' => $keyword,
            'so_don' => $list->count(),
            'tong_con_no' => $list->sum('con_no'),
            'theo_don_vi' => $list->groupBy('don_vi')->map(fn ($g, $u) => ['don_vi' => $u ?: '(chưa có đơn vị)', 'so_don' => $g->count(), 'con_no' => $g->sum('con_no')])
                ->sortByDesc('con_no')->values()->take(30)->all(),
            'don' => $list->take(self::LIMIT)->all(),
            'bi_cat_bot' => $list->count() > self::LIMIT ? 'Chỉ hiện ' . self::LIMIT . ' / ' . $list->count() . ' đơn đầu; hỏi theo từng đơn vị để xem hết.' : null,
        ];
    }

    private static function date($v): ?Carbon
    {
        if (!$v) {
            return null;
        }
        try {
            return preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $v) ? Carbon::parse(substr((string) $v, 0, 10))
                : (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', (string) $v) ? Carbon::createFromFormat('d/m/Y', (string) $v)->startOfDay() : null);
        } catch (\Throwable) {
            return null;
        }
    }
}
