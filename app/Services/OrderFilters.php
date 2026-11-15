<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bộ lọc danh sách đơn hàng, dùng chung cho: danh sách (Order::getAll), các thẻ đếm trạng thái, và gợi ý
 * lựa chọn của các ô lọc (liên kết: chọn ô này thì ô kia chỉ gợi ý những giá trị còn có đơn).
 *
 * Ô lọc: unit_abbreviation (đơn vị), group (mã nhóm đi chung, "__any" = có nhóm, "__none" = không nhóm),
 * from_date / to_date (ngày chụp), keyword (mã ĐH / tên đơn vị / tên Cty / mã nhóm), status_id, schedule_status.
 * Query phải join order_details, units và (left) order_groups.
 */
class OrderFilters
{
    public const FIELDS = ['unit_abbreviation', 'group', 'complete', 'from_date', 'to_date', 'keyword', 'status_id', 'schedule_status'];

    /** Query gốc (đã join) để lọc / đếm / lấy gợi ý */
    public static function base(): EloquentBuilder
    {
        return \App\Models\Order::query()
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->join('units', 'units.id', '=', 'orders.unit_id')
            ->leftJoin('order_groups', 'order_groups.id', '=', 'orders.order_group_id');
    }

    /** Áp các ô lọc (trừ các ô trong $except) */
    public static function apply(EloquentBuilder|QueryBuilder $query, Request $request, array $except = []): EloquentBuilder|QueryBuilder
    {
        $on = fn (string $f) => !in_array($f, $except, true) && $request->filled($f);

        if ($on('unit_abbreviation')) {
            $query->where('units.unit_abbreviation', $request->unit_abbreviation);
        }
        if ($on('group')) {
            $g = (string) $request->group;
            if ($g === '__any') {
                $query->whereNotNull('orders.order_group_id');
            } elseif ($g === '__none') {
                $query->whereNull('orders.order_group_id');
            } else {
                $query->where('order_groups.code', $g);
            }
        }
        // Thông tin đơn: 0 = lưu bằng nút "Cập nhật chưa hoàn thành" (còn thiếu thông tin), 1 = đã đủ
        if ($on('complete') && in_array((string) $request->complete, ['0', '1'], true)) {
            $query->where('orders.complete', (int) $request->complete);
        }
        if ($on('from_date')) {
            $query->whereDate('order_details.ord_start_day', '>=', $request->from_date);
        }
        if ($on('to_date')) {
            $query->whereDate('order_details.ord_start_day', '<=', $request->to_date);
        }
        if ($on('keyword')) {
            $kw = trim((string) $request->keyword);
            $query->where(function ($q) use ($kw) {
                // Gõ số: đúng mã đơn (trước đây "12" ra cả đơn 1200, 3120…), vẫn tìm thêm trong tên
                if (ctype_digit($kw)) {
                    $q->where('orders.id', (int) $kw);
                } else {
                    $q->where('orders.id', 'LIKE', "%{$kw}%");
                }
                $q->orWhere('order_details.ord_cty_name', 'LIKE', "%{$kw}%")
                    ->orWhere('units.unit_name', 'LIKE', "%{$kw}%")
                    ->orWhere('units.unit_abbreviation', 'LIKE', "%{$kw}%")
                    ->orWhere('order_groups.code', 'LIKE', "%{$kw}%");
            });
        }
        if ($on('status_id')) {
            $s = $request->status_id;
            if ($s === 'processing') {
                $query->whereIn('orders.status_id', [1, 2]);
            } elseif ($s === 'completed') {
                $query->whereIn('orders.status_id', [3, 4]);
            } else {
                $query->where('orders.status_id', $s);
            }
        }
        if ($on('schedule_status')) {
            if ($request->schedule_status === '0') {
                $query->where('orders.schedule_status', 0);
            } elseif ($request->schedule_status === '1') {
                $query->where('orders.schedule_status', '!=', 0);
            }
        }

        return $query;
    }

    /**
     * Gợi ý cho các ô chọn, mỗi ô tính theo TẤT CẢ ô lọc khác (không tính chính nó) để chọn ô này
     * thì ô kia chỉ còn những giá trị có đơn. Kèm số đơn.
     *
     * @return array{units: array<int, array{value:string,count:int}>, groups: array<int, array{value:string,count:int,date:?string,unit:?string}>}
     */
    public static function options(Request $request): array
    {
        $units = self::apply(self::base(), $request, ['unit_abbreviation'])
            ->whereNotNull('units.unit_abbreviation')->where('units.unit_abbreviation', '!=', '')
            ->groupBy('units.unit_abbreviation')
            ->orderBy('units.unit_abbreviation')
            ->get([DB::raw('units.unit_abbreviation as value'), DB::raw('COUNT(*) as count')])
            ->map(fn ($r) => ['value' => (string) $r->value, 'count' => (int) $r->count])->values()->all();

        $groups = self::apply(self::base(), $request, ['group'])
            ->whereNotNull('orders.order_group_id')
            ->groupBy('order_groups.code')
            ->orderByRaw('MAX(order_details.ord_start_day) DESC')
            ->limit(300)
            ->get([DB::raw('order_groups.code as value'), DB::raw('COUNT(*) as count'),
                DB::raw('MAX(order_details.ord_start_day) as date'), DB::raw('MAX(units.unit_abbreviation) as unit')])
            ->map(fn ($r) => ['value' => (string) $r->value, 'count' => (int) $r->count, 'date' => $r->date, 'unit' => $r->unit])->values()->all();

        $complete = self::apply(self::base(), $request, ['complete'])
            ->groupBy('orders.complete')
            ->get([DB::raw('orders.complete as value'), DB::raw('COUNT(*) as count')])
            ->mapWithKeys(fn ($r) => [(string) (int) $r->value => (int) $r->count]);

        return ['units' => $units, 'groups' => $groups, 'complete' => ['0' => $complete['0'] ?? 0, '1' => $complete['1'] ?? 0]];
    }
}
