<?php

namespace App\Services;

use App\Models\HistoryEdit;
use App\Models\Order;
use App\Models\OrderGroup;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Nhóm đơn đi chung. Chỉ đơn CÙNG đơn vị, CÙNG ngày chụp mới vào chung nhóm (buổi do Sale tự quyết).
 * Nhóm có từ 2 đơn trở lên; còn 1 đơn thì tự giải tán.
 */
class OrderGroups
{
    /** Các đơn khác cùng đơn vị, cùng ngày (để form gợi ý đi chung) */
    public function candidates(int $unitId, string $date, ?int $exceptOrderId = null): Collection
    {
        return DB::table('orders')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('order_groups', 'order_groups.id', '=', 'orders.order_group_id')
            ->leftJoin('car_ktvs', fn ($j) => $j->on('car_ktvs.order_id', '=', 'orders.id')->where('car_ktvs.car_active', 1))
            ->where('orders.unit_id', $unitId)
            ->where('order_details.ord_start_day', $date)
            ->when($exceptOrderId, fn ($q) => $q->where('orders.id', '!=', $exceptOrderId))
            ->orderBy('orders.id')
            ->select('orders.id', 'orders.order_quantity', 'orders.order_surcharge', 'orders.order_group_id', 'order_groups.code as group_code',
                'order_details.ord_session', 'order_details.ord_select', 'order_details.ord_cty_name', 'car_ktvs.car_name', 'car_ktvs.car_driver_name')
            ->get();
    }

    /**
     * Đặt nhóm cho đơn theo lựa chọn trên form: nhóm = đơn này + các đơn được tích.
     * Không tích đơn nào thì đơn rời nhóm. Đơn tích không cùng đơn vị / ngày bị bỏ qua.
     * Các nhóm cũ của những đơn được tích được gộp vào một nhóm (giữ mã nhóm cũ nhất).
     */
    public function sync(Order $order, array $withIds): ?OrderGroup
    {
        $order->refresh();
        $date = (string) DB::table('order_details')->where('id', $order->order_detail_id)->value('ord_start_day');
        $mates = $order->unit_id && $date
            ? $this->candidates((int) $order->unit_id, $date, $order->id)->whereIn('id', array_map('intval', $withIds))
            : collect();

        return DB::transaction(function () use ($order, $mates, $date) {
            $touched = array_filter(array_merge([$order->order_group_id], $mates->pluck('order_group_id')->all()));
            if ($mates->isEmpty()) {
                $this->leave($order);

                return null;
            }

            $memberIds = array_merge([$order->id], $mates->pluck('id')->all());
            $group = OrderGroup::whereIn('id', $touched)->orderBy('id')->first()
                ?? OrderGroup::create(['code' => $this->newCode((int) $order->unit_id, $date), 'unit_id' => $order->unit_id,
                    'group_date' => $date, 'created_by' => Auth::id()]);
            $before = Order::where('order_group_id', $group->id)->pluck('id')->all();

            // Thành viên cũ không còn được chọn thì ra khỏi nhóm
            Order::where('order_group_id', $group->id)->whereNotIn('id', $memberIds)->update(['order_group_id' => null]);
            Order::whereIn('id', $memberIds)->update(['order_group_id' => $group->id]);
            $this->cleanup($touched);

            foreach (array_diff($memberIds, $before) as $id) {
                $this->history($id, "Vào nhóm đi chung {$group->code}");
            }

            return $group->fresh();
        });
    }

    /** Đơn rời nhóm (đổi ngày, đổi đơn vị, bỏ tích, xóa đơn). Nhóm còn 1 đơn thì giải tán. */
    public function leave(Order $order): void
    {
        $groupId = $order->order_group_id;
        if (!$groupId) {
            return;
        }
        $code = OrderGroup::whereKey($groupId)->value('code');
        Order::whereKey($order->id)->update(['order_group_id' => null]);
        $order->order_group_id = null;
        $this->history($order->id, "Rời nhóm đi chung {$code}");
        $this->cleanup([$groupId]);
    }

    /** Giải tán nhóm còn dưới 2 đơn */
    private function cleanup(array $groupIds): void
    {
        foreach (array_unique($groupIds) as $id) {
            if (Order::where('order_group_id', $id)->count() < 2) {
                Order::where('order_group_id', $id)->update(['order_group_id' => null]);
                OrderGroup::whereKey($id)->delete();
            }
        }
    }

    /** Mã nhóm: {mã đơn vị}-{ddmmyy}, cùng ngày đã có nhóm thì thêm -2, -3… */
    private function newCode(int $unitId, string $date): string
    {
        $unit = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) DB::table('units')->where('id', $unitId)->value('unit_code'))) ?: 'DV' . $unitId;
        $base = $unit . '-' . Carbon::parse($date)->format('dmy');
        $code = $base;
        for ($n = 2; OrderGroup::where('code', $code)->exists(); $n++) {
            $code = "{$base}-{$n}";
        }

        return $code;
    }

    private function history(int $orderId, string $text): void
    {
        HistoryEdit::create(['order_id' => $orderId, 'user_name' => Auth::user()?->email ?? 'system', 'history_action' => mb_substr($text, 0, 100)]);
    }
}
