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
 * Ai thấy nhóm nào: Admin (role 0/1) thấy tất cả; SubAdmin chỉ nhóm Tài chính; Kế toán (role 3 hoặc phòng ban ke_toan); Sales (sales);
 * "Thu nợ bạn phụ trách": kế toán / sales được giao thu nợ (Phân tích tài chính → Thu nợ).
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
        // Khoản thu nợ được giao cho chính tài khoản này (Phân tích tài chính → Thu nợ)
        'thu_no_cua_toi' => ['label' => 'Thu nợ bạn phụ trách', 'icon' => 'fas fa-phone-volume'],
        // Tổng quan tài chính cho Admin / SubAdmin (chỉ xem)
        'tai_chinh' => ['label' => 'Tài chính', 'icon' => 'fas fa-chart-pie'],
    ];

    /** Nhóm thông báo tài khoản được xem */
    public function groupsFor(User $user): array
    {
        if (AssistantAccess::isFullAdmin($user)) {
            return array_keys(self::GROUPS);
        }
        if (AssistantAccess::isSubAdmin($user)) {
            return ['tai_chinh']; // SubAdmin chỉ xem: tổng quan tài chính (trang Phân tích tài chính)
        }
        $dept = (string) $user->department;
        $groups = [];
        if ((int) $user->role === 3 || $dept === 'ke_toan') {
            $groups[] = 'ke_toan';
        }
        if ($dept === 'sales') {
            $groups[] = 'sales';
        }
        if ((int) $user->role === 3 || in_array($dept, ['ke_toan', 'sales'], true)) {
            $groups[] = 'thu_no_cua_toi';
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
        $seen = [];
        foreach ($groups as $g) {
            // Mục xuất hiện ở nhiều nhóm (vd hợp đồng: Kế toán + Sales) chỉ hiện một lần
            $items = array_values(array_filter($this->{'items' . str_replace('_', '', ucwords($g, '_'))}($today, $user), function ($i) use (&$seen) {
                if ($i['count'] <= 0 || isset($seen[$i['key']])) {
                    return false;
                }

                return $seen[$i['key']] = true;
            }));
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
        $misaEnd = \Illuminate\Support\Facades\DB::table('acc_journal_entries')->max('posted_on');
        $misaStale = $misaEnd && Carbon::parse($misaEnd)->lt($today->copy()->subDays(45));

        return array_merge([
            $this->item('qty_changed', 'Đơn có số ca thay đổi sau khi cập nhật doanh thu', $qtyChanged,
                route('accountant.index', ['qty_changed' => 1]), 'urgent', 'Tính lại tiền theo số ca mới hoặc giữ tiền cũ'),
            $this->item('wait_revenue', 'Đơn đã có số ca thực tế, chờ cập nhật doanh thu', $waitRevenue,
                route('accountant.index', ['todo' => 'wait_revenue'])),
            $this->item('debt_overdue', 'Hóa đơn đã xuất quá 45 ngày chưa thanh toán (1 năm gần đây)', $debt45,
                route('accountant.index', ['todo' => 'overdue']), $debt60 > 0 ? 'urgent' : 'normal', $debt60 > 0 ? "Trong đó {$debt60} đơn quá 60 ngày" : null),
            $this->item('profit_pending', 'Phiếu tính lợi nhuận chờ duyệt', $profit, route('profit.index') . '#pf-history'),
            // Sổ MISA trễ quá 45 ngày: báo cáo CFO / BCTC phải ước tính → nhắc kế toán tải sổ mới
            $this->item('misa_stale', 'Sổ MISA trên web mới có đến ' . ($misaEnd ? Carbon::parse($misaEnd)->format('d/m/Y') : '—') . ', cần tải sổ các tháng sau',
                $misaStale ? 1 : 0, route('accounting.index'), 'normal', 'Báo cáo CFO / BCTC đang phải ước tính các tháng chưa có sổ'),
        ], $this->financeItems(), $this->contractItems(), [$this->noteItem($today)]);
    }

    /** Hợp đồng khách hàng (Hành chính → Hợp đồng): hiện cho cả Sales và Kế toán */
    private function contractItems(): array
    {
        $under = app(\App\Services\Accounting\ContractPricing::class)->underBilled();
        $expiring = \App\Models\CustomerContract::where('type', 'nguyen_tac')->where('auto_renew', false)
            ->whereBetween('valid_to', [Carbon::today()->toDateString(), Carbon::today()->addDays(\App\Http\Controllers\CustomerContractController::EXPIRING_DAYS)->toDateString()])->count();

        return [
            $this->item('contract_under', 'Đơn thu thấp hơn giá hợp đồng', $under->count(), route('contracts.index') . '#thu-thieu',
                'normal', $under->count() ? 'Thiếu ' . number_format(-$under->sum('diff'), 0, ',', '.') . ' đ' : null),
            $this->item('contract_expiring', 'Hợp đồng khách hàng hết hạn trong ' . \App\Http\Controllers\CustomerContractController::EXPIRING_DAYS . ' ngày', $expiring, route('contracts.index', ['tt' => 'sap_het_han'])),
        ];
    }

    /** Việc kế toán từ Phân tích tài chính: duyệt giá, thu nợ, tiền kẹt (chỉ cảnh báo, không khóa gì) */
    private function financeItems(): array
    {
        $prices = app(\App\Services\Accounting\PriceGuard::class)->list()->where('approved', false);
        $lossCount = $prices->where('profit', '<', 0)->count();
        $collect = app(\App\Services\Accounting\DebtCollection::class)->list();
        $late = $collect->where('status', 'qua_hen');
        $noOwner = $collect->where('status', 'chua_phu_trach');
        $stuck = app(\App\Services\Accounting\Receivables::class)->rows()
            ->filter(fn ($r) => $r->bucket === 'chua_xuat_hd' && ($r->days_since_shot ?? 0) >= \App\Services\Accounting\Receivables::STUCK_DAYS);
        $fin = fn (string $tab, array $q = []) => route('finance.index', ['tab' => $tab] + $q);

        return [
            $this->item('price_pending', 'Đơn giá thấp / chiết khấu cao chờ duyệt giá', $prices->count(), $fin('duyet-gia'),
                $lossCount ? 'urgent' : 'normal', $lossCount ? "Trong đó {$lossCount} đơn lỗ ước tính" : null),
            $this->item('promise_late', 'Khách quá ngày hẹn trả nợ', $late->count(), $fin('thu-no', ['loc' => 'qua-hen']), 'urgent',
                $late->take(3)->pluck('unit')->implode(', ') ?: null),
            $this->item('debt_no_owner', 'Nợ quá hạn (hóa đơn đã xuất) chưa ai phụ trách thu', $noOwner->count(), $fin('thu-no', ['loc' => 'chua-phu-trach'])),
            $this->item('stuck_invoice', 'Đơn chụp xong ≥ ' . \App\Services\Accounting\Receivables::STUCK_DAYS . ' ngày chưa xuất hóa đơn', $stuck->count(), $fin('tien-ket'),
                'normal', $stuck->count() ? 'Tổng ' . number_format($stuck->sum('owe'), 0, ',', '.') . ' đ' : null),
        ];
    }

    /** Khoản thu nợ giao cho chính tài khoản đang đăng nhập */
    private function itemsThuNoCuaToi(Carbon $today, User $user): array
    {
        $mine = app(\App\Services\Accounting\DebtCollection::class)->list()->where('assignee_id', $user->id);
        $late = $mine->where('status', 'qua_hen');
        $remind = $mine->where('status', 'can_nhac');
        // Sales không vào được Phân tích tài chính: mở trang Công nợ Sales
        $canFinance = \App\Http\Middleware\PageAccess::allows($user, ['debt_accountant']);
        $url = $canFinance ? route('finance.index', ['tab' => 'thu-no', 'loc' => 'cua-toi']) : route('accountant_sales.index');

        return [
            $this->item('my_promise_late', 'Khách bạn phụ trách đã quá ngày hẹn trả', $late->count(), $url, 'urgent', $late->take(3)->pluck('unit')->implode(', ') ?: null),
            $this->item('my_need_remind', 'Khách bạn phụ trách lâu chưa nhắc nợ (> ' . \App\Services\Accounting\DebtCollection::REMIND_DAYS . ' ngày)', $remind->count(), $url, 'normal',
                $remind->take(3)->pluck('unit')->implode(', ') ?: null),
        ];
    }

    /** Tổng quan tài chính (Admin, SubAdmin): tuần sắp thiếu tiền, khách vượt hạn mức */
    private function itemsTaiChinh(Carbon $today): array
    {
        $f = app(\App\Services\Accounting\CashForecast::class)->build(['nguong' => config('finance.alerts.cash_threshold')]);
        // Chưa có báo cáo tháng này (tiền đầu kỳ = 0 hoặc đang ước tính từ tháng trước): vẫn nhắc kế toán lập báo cáo
        $openingMissing = str_starts_with($f['nguon_tien_dau_ky'], 'Chưa có') || str_starts_with($f['nguon_tien_dau_ky'], 'Ước tính');
        $openingZero = str_starts_with($f['nguon_tien_dau_ky'], 'Chưa có');
        $credit = app(\App\Services\Accounting\CreditPolicy::class)->all()->whereIn('status', ['vuot_han_muc', 'qua_han_lau']);

        return [
            $this->item('cash_low', 'Tuần sắp thiếu tiền trong 13 tuần tới', $openingZero ? 0 : $f['so_tuan_duoi_nguong'], route('finance.index', ['tab' => 'dong-tien']), 'urgent',
                $f['tuan_thap_nhat'] ? 'Thấp nhất ' . number_format($f['tuan_thap_nhat']['so_du'], 0, ',', '.') . ' đ (tuần ' . $f['tuan_thap_nhat']['tuan'] . ')' : null),
            $this->item('no_cash_report', 'Chưa có Báo cáo thu chi tháng ' . $today->month . ' (dự báo dòng tiền chưa tính được)', $openingMissing ? 1 : 0, route('reports.index')),
            $this->item('credit_over', 'Khách vượt hạn mức tín dụng / quá hạn quá lâu', $credit->count(), route('finance.index', ['tab' => 'han-muc'])),
            $this->noteItem($today),
        ];
    }

    /** Ghi nhớ CFO đã tới ngày xem lại mà chưa đánh dấu xong (Kế toán + Admin / SubAdmin) */
    private function noteItem(Carbon $today): array
    {
        $due = \App\Models\CfoNote::where('done', false)->whereNotNull('remind_on')->where('remind_on', '<=', $today->toDateString())->orderBy('remind_on')->get(['content', 'topic']);

        return $this->item('cfo_note_due', 'Ghi nhớ CFO tới ngày xem lại', $due->count(), route('finance.index', ['tab' => 'ghi-nho']), 'normal',
            $due->count() ? \Illuminate\Support\Str::limit($due->map(fn ($n) => $n->topic ?: $n->content)->take(2)->implode(' · '), 90) : null);
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

        // Nhật ký Sales: việc hẹn với khách đến hạn hôm nay / quá hạn chưa xong
        $due = \App\Models\SalesFollowup::due($today)->get(['title', 'due_on']);
        $lateCount = $due->filter(fn ($f) => $f->due_on->lt($today))->count();

        return array_merge([
            $this->item('sales_followups', 'Việc với khách đến hạn (Nhật ký Sales)', $due->count(), route('sales_logs.index'), $lateCount ? 'urgent' : 'normal',
                $due->count() ? ($lateCount ? "{$lateCount} việc quá hạn · " : '') . \Illuminate\Support\Str::limit($due->first()->title, 70) : null),
            $this->item('unscheduled_soon', 'Đơn chụp trong 3 ngày tới chưa lên lịch xe', $unscheduledSoon,
                route('order.index', ['status_id' => 5, 'from_date' => $today->toDateString(), 'to_date' => $soon]), 'urgent'),
            $this->item('cancelled', 'Đơn bị hủy lịch xe, cần xếp lại', $cancelled, route('order.index', ['status_id' => 5])),
            $this->item('incomplete', 'Đơn lưu "Chưa hoàn thành" (còn thiếu thông tin)', $incomplete, route('order.index', ['complete' => '0'])),
        ], $this->contractItems());
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
