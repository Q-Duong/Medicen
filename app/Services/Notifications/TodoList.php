<?php

namespace App\Services\Notifications;

use App\Models\CarMaintenance;
use App\Models\Order;
use App\Models\ProfitReport;
use App\Models\User;
use App\Services\Claude\AssistantAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Chuông thông báo trên header: "việc cần làm" của từng tài khoản, ĐẾM TRỰC TIẾP từ dữ liệu (không có bảng thông báo,
 * không có đã đọc / chưa đọc): làm xong việc thì số tự giảm. Mỗi dòng có link sang đúng trang, lọc sẵn.
 *
 * Ai thấy nhóm nào: Admin (role 0/1) thấy tất cả; Kế toán (role 3 hoặc phòng ban ke_toan); Sales (sales);
 * Xếp lịch (config schedule.user_departments: phòng Điều phối xếp lịch); Kết quả (ket_qua).
 * Kết quả lưu cache 60 giây mỗi tài khoản (trang hỏi lại mỗi phút).
 */
class TodoList
{
    public const GROUPS = [
        'ke_toan' => ['label' => 'Kế toán', 'icon' => 'fas fa-wallet'],
        'sales' => ['label' => 'Sales', 'icon' => 'fas fa-clipboard-list'],
        'xep_lich' => ['label' => 'Xếp lịch / Kỹ thuật', 'icon' => 'fas fa-shuttle-van'],
        'ket_qua' => ['label' => 'Kết quả', 'icon' => 'fas fa-file-medical'],
    ];

    /** Nhóm thông báo tài khoản được xem */
    public function groupsFor(User $user): array
    {
        if (AssistantAccess::isAdmin($user)) {
            return array_keys(self::GROUPS);
        }
        $dept = (string) $user->department;
        $groups = [];
        if ((int) $user->role === 3 || $dept === 'ke_toan') {
            $groups[] = 'ke_toan';
        }
        if ($dept === 'sales') {
            $groups[] = 'sales';
        }
        if (in_array($dept, config('schedule.user_departments', []), true)) {
            $groups[] = 'xep_lich';
        }
        if ($dept === 'ket_qua') {
            $groups[] = 'ket_qua';
        }

        return $groups;
    }

    /** @return array{total:int, urgent:int, groups: array<int, array>} */
    public function for(User $user): array
    {
        $groups = $this->groupsFor($user);
        $key = 'todo:' . $user->id . ':' . implode(',', $groups);

        return Cache::remember($key, 60, fn () => $this->build($user, $groups));
    }

    private function build(User $user, array $groups): array
    {
        $today = Carbon::today();
        $out = [];
        foreach ($groups as $g) {
            $items = array_values(array_filter($this->{'items' . str_replace('_', '', ucwords($g, '_'))}($today), fn ($i) => $i['count'] > 0));
            if ($items) {
                $out[] = ['key' => $g] + self::GROUPS[$g] + ['items' => $items];
            }
        }
        $all = collect($out)->flatMap(fn ($g) => $g['items']);

        return [
            'total' => (int) $all->sum('count'),
            'urgent' => (int) $all->where('level', 'urgent')->sum('count'),
            'groups' => $out,
            'checked_at' => now()->format('H:i'),
        ];
    }

    private function item(string $key, string $title, int $count, string $url, string $level = 'normal', ?string $hint = null): array
    {
        return compact('key', 'title', 'count', 'url', 'level', 'hint');
    }

    /** Đơn có lịch xe đang chạy */
    private function scheduled()
    {
        return Order::query()
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->whereExists(fn ($q) => $q->from('car_ktvs')->whereColumn('car_ktvs.order_id', 'orders.id')->where('car_ktvs.car_active', 1));
    }

    private function itemsKeToan(Carbon $today): array
    {
        // Đếm bằng đúng bộ lọc của trang Công nợ (chỉ đơn đã xếp xe, như bảng Công nợ) để số trên chuông khớp với trang
        $acc = fn (array $p) => \App\Models\Accountant::getAccountantByFilter($p);
        $qtyChanged = $acc(['qty_changed' => 1])->count();
        $waitRevenue = $acc(['todo' => 'wait_revenue'])->count();
        $debt45 = $acc(['todo' => 'overdue'])->count();
        $debt60 = $acc(['todo' => 'overdue'])->where('order_details.ord_start_day', '<=', $today->copy()->subDays(60)->toDateString())->count();

        $profit = ProfitReport::where('status', 0)->count();

        return [
            $this->item('qty_changed', 'Đơn có số ca thay đổi sau khi cập nhật doanh thu', $qtyChanged,
                route('accountant.index', ['qty_changed' => 1]), 'urgent', 'Tính lại tiền theo số ca mới hoặc giữ tiền cũ'),
            $this->item('wait_revenue', 'Đơn đã có số ca thực tế, chờ cập nhật doanh thu', $waitRevenue,
                route('accountant.index', ['todo' => 'wait_revenue'])),
            $this->item('debt_overdue', 'Công nợ quá hạn 45 ngày chưa thanh toán (1 năm gần đây)', $debt45,
                route('accountant.index', ['todo' => 'overdue']), $debt60 > 0 ? 'urgent' : 'normal', $debt60 > 0 ? "Trong đó {$debt60} đơn quá 60 ngày" : null),
            $this->item('profit_pending', 'Phiếu tính lợi nhuận chờ duyệt', $profit, route('profit.index') . '#pf-history'),
        ];
    }

    private function itemsSales(Carbon $today): array
    {
        $soon = $today->copy()->addDays(2)->toDateString();
        $unscheduledSoon = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.status_id', 5)
            ->whereBetween('order_details.ord_start_day', [$today->toDateString(), $soon])
            ->count();
        $incomplete = Order::where('complete', 0)->where('status_id', '!=', 3)->count();
        // Bị hủy lịch (đổi ngày / bấm hủy) mà chưa xếp lại, ngày chụp chưa qua
        $cancelled = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.status_id', 5)
            ->where('order_details.ord_start_day', '>=', $today->toDateString())
            ->whereExists(fn ($q) => $q->from('history_edits')->whereColumn('history_edits.order_id', 'orders.id')->where('history_edits.history_action', 'like', 'Hủy lịch%'))
            ->count();

        return [
            $this->item('unscheduled_soon', 'Đơn chụp trong 3 ngày tới chưa lên lịch xe', $unscheduledSoon,
                route('order.index', ['status_id' => 5, 'from_date' => $today->toDateString(), 'to_date' => $soon]), 'urgent'),
            $this->item('cancelled', 'Đơn bị hủy lịch xe, cần xếp lại', $cancelled, route('order.index', ['status_id' => 5])),
            $this->item('incomplete', 'Đơn lưu "Chưa hoàn thành" (còn thiếu thông tin)', $incomplete, route('order.index', ['complete' => '0'])),
        ];
    }

    private function itemsXepLich(Carbon $today): array
    {
        $soon = $today->copy()->addDays(2)->toDateString();
        $pendingSoon = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.schedule_status', 0)
            ->where('orders.status_id', '!=', 3)
            ->whereBetween('order_details.ord_start_day', [$today->toDateString(), $soon])
            ->whereNotExists(fn ($q) => $q->from('car_ktvs')->whereColumn('car_ktvs.order_id', 'orders.id')->where('car_ktvs.car_active', 1))
            ->count();
        // Đã qua ngày chụp (60 ngày gần đây, không trước schedule.timekeeping_from) mà KTV chưa nhập số ca
        // (giống mục "Chưa chấm công" ở trang Xếp lịch)
        $missingFrom = max($today->copy()->subDays(60)->toDateString(), (string) config('schedule.timekeeping_from', '2000-01-01'));
        $missing = $this->scheduled()
            ->whereBetween('order_details.ord_start_day', [$missingFrom, $today->copy()->subDay()->toDateString()])
            ->where('orders.status_id', '!=', 3)
            // Không tính đơn phụ thu và đơn chỉ có tiền không có số ca (in thêm phim / giấy…): không cần chấm công
            ->where('orders.order_surcharge', 0)
            ->where('orders.order_quantity', '>', 0)
            ->where(fn ($q) => $q->whereNull('orders.order_quantity_draft')->orWhere('orders.order_quantity_draft', 0))
            ->whereExists(fn ($q) => $q->from('car_ktvs')->whereColumn('car_ktvs.order_id', 'orders.id')->where('car_ktvs.car_active', 1)
                ->whereNotNull('car_ktvs.car_ktv_name_1')->where('car_ktvs.car_ktv_name_1', '!=', ''))
            ->count();
        // Đã qua ngày chụp mà vẫn chưa xếp xe (trang Xếp lịch hiện ở nhóm "đã qua ngày" của Đơn chờ xếp lịch)
        $pastUnscheduled = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.schedule_status', 0)
            ->where('orders.status_id', '!=', 3)
            ->whereBetween('order_details.ord_start_day', [$today->copy()->subDays((int) config('schedule.past_days', 60))->toDateString(), $today->copy()->subDay()->toDateString()])
            ->whereNotExists(fn ($q) => $q->from('car_ktvs')->whereColumn('car_ktvs.order_id', 'orders.id')->where('car_ktvs.car_active', 1))
            ->count();
        $maint = CarMaintenance::where('maintenance_end', '>=', $today->toDateString())
            ->where('maintenance_start', '<=', $today->copy()->addDays(7)->toDateString())->count();

        return [
            $this->item('past_unscheduled', 'Đơn đã qua ngày chụp mà chưa xếp xe', $pastUnscheduled, route('dispatch.index'), 'urgent', 'Xếp lại hoặc kiểm tra đơn (' . (int) config('schedule.past_days', 60) . ' ngày gần đây)'),
            $this->item('pending_soon', 'Đơn chụp trong 3 ngày tới chưa xếp xe', $pendingSoon, route('dispatch.index'), 'urgent'),
            $this->item('missing_actual', 'Đơn đã qua ngày chụp chưa chấm công', $missing, route('dispatch.index'), 'normal', 'KTV chưa nhập số ca chụp'),
            $this->item('maintenance', 'Xe có lịch bảo trì trong 7 ngày tới', $maint, route('dispatch.index')),
        ];
    }

    private function itemsKetQua(Carbon $today): array
    {
        $from = $today->copy()->subDays(60)->toDateString();
        $yesterday = $today->copy()->subDay()->toDateString();
        // Đã chụp xong (có lịch xe, qua ngày chụp) nhưng Kết quả chưa nhập số ca thực tế
        $noActual = $this->scheduled()
            ->whereIn('orders.status_id', [0, 1])
            ->whereBetween('order_details.ord_start_day', [$from, $yesterday])
            ->count();
        // Quá thời hạn giao kết quả mà chưa có ngày trả
        $late = Order::join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.status_id', '!=', 3)
            ->whereBetween('order_details.ord_start_day', [$from, $today->toDateString()])
            ->whereNotNull('order_details.ord_deadline')->where('order_details.ord_deadline', '!=', '')
            ->where('order_details.ord_deadline', '<', $today->toDateString())
            ->where(fn ($q) => $q->whereNull('order_details.ord_delivery_date')->orWhere('order_details.ord_delivery_date', ''))
            ->count();

        return [
            $this->item('no_actual', 'Đơn đã chụp chưa nhập số ca thực tế', $noActual, route('schedules.results.index'), 'normal', '60 ngày gần đây'),
            $this->item('late_result', 'Đơn quá hạn giao kết quả chưa có ngày trả', $late, route('schedules.results.index'), 'urgent', '60 ngày gần đây'),
        ];
    }
}
