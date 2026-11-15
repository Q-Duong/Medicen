<?php

namespace App\Services\Schedule;

use Anthropic\Client;
use Carbon\Carbon;
use App\Models\ScheduleHold;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gợi ý xếp xe, tài xế, KTV cho các đơn chưa xếp lịch.
 *
 * 1. Code tính xe / người nào dùng được từng buổi (ScheduleBoard), kiểm tra có kịp di chuyển
 *    giữa các điểm chụp (RouteDistance, ước tính theo tỉnh/thành), và lập phương án theo quy tắc:
 *    cùng đơn vị cùng buổi thì đi chung xe; chia đều số ca trong tháng; ưu tiên KTV có CCHN.
 * 2. Nếu có API key Claude: Claude xem lại phương án và giải thích.
 * 3. Mọi đề xuất (kể cả của Claude) đều được code kiểm tra lại; sai thì dùng phương án theo quy tắc.
 * Không ghi gì vào lịch: Technician xem và bấm Áp dụng.
 */
class ScheduleSuggester
{
    private ?ScheduleBoard $board = null;

    /** @var array<string, array<string, int>> lịch sử: xe => [tài xế => số lần] */
    private array $driverHistory = [];

    /** @var array<string, array<string, int>> "Y-m" => [tên => số ca done + planned] */
    private array $monthCases = [];

    public function __construct(private Client $client, private RouteDistance $routes)
    {
    }

    /**
     * Đơn chưa xếp lịch có ngày chụp từ hôm nay trong N ngày tới.
     * $pastDays > 0: lấy cả đơn đã qua ngày trong ngần ấy ngày gần đây (ghi bổ sung).
     */
    public function pendingOrders(?int $days = null, ?int $orderId = null, int $pastDays = 0): Collection
    {
        $today = Carbon::today();
        $until = $today->copy()->addDays($days ?? (int) config('schedule.suggest_days', 30));
        $today = $today->copy()->subDays($pastDays);

        return $this->hydrate($this->ordersQuery()
            ->where('orders.schedule_status', 0)
            ->when($orderId, fn ($q) => $q->where('orders.id', $orderId))
            ->whereBetween('order_details.ord_start_day', [$today->toDateString(), $until->toDateString()])
            ->whereNotExists(fn ($q) => $q->from('car_ktvs')->whereColumn('car_ktvs.order_id', 'orders.id')->where('car_ktvs.car_active', 1)));
    }

    /** Đơn đã xếp lịch (để sửa), kèm xe / người đang được xếp */
    public function scheduledOrders(array $ids): Collection
    {
        return $this->hydrate($this->ordersQuery()
            ->join('car_ktvs', fn ($j) => $j->on('car_ktvs.order_id', '=', 'orders.id')->where('car_ktvs.car_active', 1))
            ->whereIn('orders.id', $ids)
            ->addSelect('car_ktvs.car_name', 'car_ktvs.car_driver_name', 'car_ktvs.car_ktv_name_1', 'car_ktvs.car_ktv_name_2',
                'car_ktvs.driver_assistance', 'car_ktvs.work_over_250', 'orders.order_quantity_draft', 'orders.order_note_ktv'));
    }

    /**
     * Phương án sửa cho các đơn đã xếp: điền sẵn xe / người hiện tại; lựa chọn được tính
     * như thể đơn đó chưa xếp (lịch hiện tại của nó không tính là bận).
     */
    public function editPlans(array $ids): array
    {
        $orders = $this->scheduledOrders($ids);
        // Đơn đã hoàn thành (kế toán đã chốt): không sửa lịch được nữa
        $closed = $orders->filter(fn ($o) => (int) $o->status_id === \App\Models\Order::STATUS_CLOSED)->pluck('id');
        $orders = $orders->reject(fn ($o) => (int) $o->status_id === \App\Models\Order::STATUS_CLOSED)->values();
        if ($orders->isEmpty()) {
            return ['items' => [], 'source' => 'edit', 'note' => $closed->isNotEmpty()
                ? 'Đơn #' . $closed->join(', #') . ' đã hoàn thành (kế toán đã chốt), không sửa lịch được nữa.'
                : 'Không tìm thấy lịch đã xếp của đơn này.'];
        }
        $this->prepare($orders, $orders->pluck('id')->all());

        $items = $orders->map(function ($o) {
            $plan = ['car' => (string) $o->car_name, 'driver' => $o->car_driver_name, 'ktv1' => $o->car_ktv_name_1, 'ktv2' => $o->car_ktv_name_2];

            return $this->item($o, $plan, ['Lịch đang xếp: ' . ScheduleBoard::carLabel($o->car_name) . ', TX ' . ($o->car_driver_name ?: '—')
                . ', KTV ' . (implode(', ', array_filter([$o->car_ktv_name_1, $o->car_ktv_name_2])) ?: 'không có')], 'edit') + [
                    'edit' => true,
                    // Chấm công sau khi chụp (giống trang lịch KTV)
                    'actual' => [
                        'order_quantity_draft' => $o->order_quantity_draft !== null && (int) $o->order_quantity_draft > 0 ? (int) $o->order_quantity_draft : null,
                        'driver_assistance' => (int) $o->driver_assistance === 1,
                        'work_over_250' => (int) $o->work_over_250 === 1,
                        'order_note_ktv' => $o->order_note_ktv,
                        'past' => self::isPast($o),
                        'has_ktv' => (bool) array_filter([$o->car_ktv_name_1, $o->car_ktv_name_2]),
                    ],
                ];
        })->values()->all();

        return ['items' => $items, 'source' => 'edit', 'note' => null];
    }

    private function ordersQuery()
    {
        return DB::table('orders')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('accountants', 'accountants.order_id', '=', 'orders.id')
            ->leftJoin('order_groups', 'order_groups.id', '=', 'orders.order_group_id')
            ->orderBy('order_details.ord_start_day')
            ->orderBy('orders.unit_id')
            ->orderBy('orders.order_group_id')
            ->orderByDesc('orders.order_quantity')
            ->select('orders.id', 'orders.unit_id', 'orders.order_quantity', 'orders.status_id', 'orders.order_surcharge', 'orders.order_group_id', 'order_groups.code as group_code', 'order_details.ord_start_day',
                'order_details.ord_end_day', 'order_details.ord_session', 'order_details.ord_time',
                'order_details.ord_select', 'order_details.ord_note', 'order_details.ord_cty_name', 'customers.customer_address',
                'accountants.accountant_distance',
                DB::raw("COALESCE(NULLIF(units.unit_abbreviation, ''), order_details.ord_cty_name) as unit"));
    }

    private function hydrate($query): Collection
    {
        // Lịch giữ chỗ của cùng đơn vị (để gợi ý "xếp vào lịch giữ chỗ")
        $holds = ScheduleHold::active()->whereNotNull('unit_id')->where('hold_end', '>=', Carbon::today()->subDays(7))
            ->orderBy('hold_start')->get()->groupBy('unit_id');

        return $query->get()
            ->map(function ($o) use ($holds) {
                $o->order_quantity = (int) $o->order_quantity;
                $o->unit_id = (int) $o->unit_id;
                $o->halves = ScheduleBoard::halvesOf($o->ord_start_day, $o->ord_end_day, $o->ord_session);
                // Vị trí điểm chụp: số km Sale nhập (đáng tin về độ xa) + địa chỉ (để biết hướng, chỉ tin khi khớp số km)
                $o->site = $this->routes->site($o->customer_address, $o->accountant_distance);
                $o->area = $o->site['name'];
                $o->from_base = $o->site['km_base'] !== null ? ['km' => round($o->site['km_base']), 'source' => $o->site['km_source']] : null;
                // Giữ chỗ cùng đơn vị: ưu tiên chỗ trùng ngày, không có thì chỗ gần ngày nhất
                $o->hold = null;
                // Đơn đã qua ngày (ghi bổ sung) thì không gợi ý xếp vào lịch giữ chỗ
                if ($o->unit_id && isset($holds[$o->unit_id]) && !self::isPast($o)) {
                    $day = Carbon::parse($o->ord_start_day);
                    // Chỗ giữ chứa ngày của đơn trước (khoảng cách 0), rồi chỗ có ngày gần nhất
                    $h = $holds[$o->unit_id]->sortBy(fn ($h) => $day->between($h->hold_start, $h->hold_end) ? 0
                        : min(abs($h->hold_start->diffInDays($day, false)), abs($h->hold_end->diffInDays($day, false))))->first();
                    $o->hold = self::holdInfo($h, $o);
                }

                return $o;
            })
            ->filter(fn ($o) => $o->halves)
            ->values();
    }

    /**
     * @return array{items: list<array>, source: string, note: ?string}
     */
    public function suggest(?Collection $orders = null, bool $useAi = true): array
    {
        $orders ??= $this->pendingOrders();
        if ($orders->isEmpty()) {
            return ['items' => [], 'source' => 'rule', 'note' => 'Không có đơn nào đang chờ xếp lịch.'];
        }

        $this->prepare($orders);
        $rule = $this->rulePlan($orders);
        $notes = [];

        if (!$useAi || !config('claude.api_key')) {
            if ($useAi) {
                array_unshift($notes, 'Chưa có API key Claude: gợi ý theo quy tắc.');
            }

            return ['items' => $rule, 'source' => 'rule', 'note' => implode(' ', $notes) ?: null];
        }

        try {
            return ['items' => $this->aiPlan($orders, $rule), 'source' => 'ai', 'note' => implode(' ', $notes) ?: null];
        } catch (Throwable $e) {
            Log::warning('Gợi ý xếp lịch bằng Claude lỗi, dùng quy tắc', ['error' => $e->getMessage()]);
            array_unshift($notes, 'Không gọi được Claude, đang hiện gợi ý theo quy tắc.');

            return ['items' => $rule, 'source' => 'rule', 'note' => implode(' ', $notes)];
        }
    }

    /** Dựng bảng tình trạng cho khoảng ngày của các đơn (dùng lại cho kiểm tra khi áp dụng) */
    public function prepare(Collection $orders, array $ignoreOrders = [], array $ignoreHolds = []): ScheduleBoard
    {
        $dates = $orders->flatMap(fn ($o) => array_map(fn ($k) => substr($k, 0, 10), $o->halves))->sort()->values();
        $this->board = ScheduleBoard::forRange(Carbon::parse($dates->first()), Carbon::parse($dates->last()), $ignoreOrders, $ignoreHolds);
        $this->driverHistory = $this->loadDriverHistory();
        $this->monthCases = [];

        return $this->board;
    }

    /**
     * Kiểm tra cứng một phương án: xe / người có lịch của đơn vị khác hoặc đang nghỉ, sai vai trò.
     * Trả về danh sách lỗi (rỗng = hợp lệ).
     */
    public function validate(object $order, array $plan, ?ScheduleBoard $board = null, array $reservedCars = [], array $reservedStaff = []): array
    {
        $board ??= $this->board;
        $errors = [];
        $staff = $board->staff()->keyBy('staff_name');
        $car = trim((string) ($plan['car'] ?? ''));
        $driver = trim((string) ($plan['driver'] ?? ''));
        $ktvs = array_values(array_filter([trim((string) ($plan['ktv1'] ?? '')), trim((string) ($plan['ktv2'] ?? ''))]));

        if (!in_array($car, $this->allCars(), true)) {
            $errors[] = 'Xe không hợp lệ';
        } elseif ($c = $board->carConflicts($car, $order->halves, $order->unit_id, $reservedCars)) {
            $errors[] = ScheduleBoard::carLabel($car) . ' đã có lịch đơn vị khác: ' . implode(', ', $c);
        }

        // Sửa lịch: người đang xếp mà đã bị xóa khỏi Quản lý nhân viên thì vẫn được giữ (không bắt chọn lại)
        $kept = self::retiredOnOrder($order, $staff);

        if (!$driver || (($staff[$driver]->staff_role ?? null) !== 'TX' && ($kept['driver'] ?? null) !== $driver)) {
            $errors[] = 'Tài xế không có trong danh sách TX';
        } elseif ($c = $board->staffConflicts($driver, $order->halves, $order->unit_id, $reservedStaff)) {
            $errors[] = "{$driver} không trống: " . implode(', ', $c);
        }

        if (count($ktvs) !== count(array_unique($ktvs)) && !in_array(config('schedule.hired_ktv'), $ktvs, true)) {
            $errors[] = 'KTV 1 và KTV 2 trùng người';
        }
        foreach ($ktvs as $ktv) {
            if (($staff[$ktv]->staff_role ?? null) !== 'KTV' && !in_array($ktv, $kept['ktv'] ?? [], true)) {
                $errors[] = "{$ktv} không có trong danh sách KTV";
            } elseif ($c = $board->staffConflicts($ktv, $order->halves, $order->unit_id, $reservedStaff)) {
                $errors[] = "{$ktv} không trống: " . implode(', ', $c);
            }
        }

        return $errors;
    }

    /** Cảnh báo không chặn việc áp dụng (người duyệt cần để ý) */
    public function warnings(object $order, array $plan, ?ScheduleBoard $board = null, array $reservedCars = [], array $reservedStaff = []): array
    {
        $board ??= $this->board;
        $warnings = [];
        $staff = $board->staff()->keyBy('staff_name');
        $ktvs = array_values(array_filter([$plan['ktv1'] ?? null, $plan['ktv2'] ?? null]));

        if (!$ktvs) {
            $warnings[] = 'Không có KTV đi theo';
        } elseif (ScheduleBoard::needsTwoKtv($order->order_quantity) && count($ktvs) < 2) {
            $warnings[] = "{$order->order_quantity} cas: nên có 2 KTV";
        }
        if (ScheduleBoard::isUltrasound($order->ord_select) && ($plan['car'] ?? null) !== config('schedule.ultrasound_car')) {
            $warnings[] = 'Đơn siêu âm nhưng không dùng Xe Siêu Âm';
        }
        foreach ($ktvs as $ktv) {
            if (!ScheduleBoard::isHiredPerson($ktv) && isset($staff[$ktv]) && !$staff[$ktv]->staff_certificate) {
                $warnings[] = "{$ktv} chưa ghi nhận chứng chỉ hành nghề";
            }
        }
        if (ScheduleBoard::isHiredCar($plan['car'] ?? null) || ($plan['driver'] ?? null) === config('schedule.hired_driver')
            || in_array(config('schedule.hired_ktv'), $ktvs, true)) {
            $warnings[] = 'Có dùng xe / người thuê ngoài: cần liên hệ thuê trước';
        }

        $travel = $this->travelProblems((string) ($plan['car'] ?? ''), true, $order, $reservedCars, $board);
        foreach (array_filter([$plan['driver'] ?? null, ...$ktvs]) as $person) {
            $travel = array_merge($travel, $this->travelProblems($person, false, $order, $reservedStaff, $board));
        }

        return array_values(array_unique(array_merge($warnings, $travel)));
    }

    /**
     * Xe / người có kịp từ điểm chụp liền trước tới đơn này, và từ đơn này tới điểm chụp liền sau không.
     * Ước tính theo tỉnh/thành trong địa chỉ; cùng đơn vị hoặc không nhận diện được địa chỉ thì bỏ qua.
     */
    public function travelProblems(string $resource, bool $isCar, object $order, array $extra = [], ?ScheduleBoard $board = null): array
    {
        $who = $isCar ? ScheduleBoard::carLabel($resource) : $resource;
        $problems = [];
        foreach ($this->legs($resource, $isCar, $order, $extra, $board) as $leg) {
            if ($leg['far']) {
                $r = $leg['route'];
                $problems[] = "{$who} {$leg['when']} ở {$leg['other']['unit']}" . ($r['from'] && $leg['side'] === 'prev' ? " ({$r['from']})" : '')
                    . ", cách khoảng {$r['km']} km (~" . $this->hours($r['minutes']) . ')' . $this->basisNote($r) . ', khó kịp';
            }
        }

        return $problems;
    }

    /** Km xa nhất trong các chặng không kịp (để hiện "⚠ xa 330 km"), null nếu không có */
    public function farKm(string $resource, bool $isCar, object $order): ?float
    {
        $far = array_filter($this->legs($resource, $isCar, $order), fn ($l) => $l['far']);

        return $far ? max(array_map(fn ($l) => $l['route']['km'], $far)) : null;
    }

    /**
     * Mô tả di chuyển của một xe cho đơn này (luôn hiện trên giao diện):
     * "Hôm trước: Cà Mau → Quận 1 ~332 km, 7h22 ⚠ · Hôm sau: trống"
     *
     * @return array{text: string, far: bool}
     */
    public function travelInfo(string $resource, bool $isCar, object $order): array
    {
        if (!$resource || ($isCar ? ScheduleBoard::isHiredCar($resource) : ScheduleBoard::isHiredPerson($resource))) {
            return ['text' => 'Xe thuê ngoài: không theo dõi di chuyển', 'far' => false];
        }
        $site = $order->site ?? $this->routes->site($order->customer_address, $order->accountant_distance ?? null);
        $here = $site['name'] ? $site['name'] . ($site['km_source'] === 'don' ? ' (' . round($site['km_base']) . ' km theo đơn)' : '')
            : ($site['km_base'] !== null ? '~' . round($site['km_base']) . ' km từ công ty (theo đơn), chưa rõ khu vực' : null);
        $legs = collect($this->legs($resource, $isCar, $order, [], null, true))->keyBy('side');
        $n = $this->board->neighbours($resource, $order->halves, $isCar);
        $parts = [];
        foreach (['prev' => $n['prev_same_day'] ? 'Buổi sáng' : 'Hôm trước', 'next' => $n['next_same_day'] ? 'Buổi chiều' : 'Hôm sau'] as $side => $label) {
            $leg = $legs->get($side);
            if (!empty($n[$side]['maintenance_id'])) {
                $parts[] = "{$label}: xe nghỉ bảo trì";
            } elseif (!$leg) {
                $parts[] = "{$label}: trống";
            } elseif ($leg['same_unit']) {
                $parts[] = "{$label}: cùng đơn vị {$leg['other']['unit']}";
            } elseif (!$leg['route']) {
                $parts[] = "{$label}: {$leg['other']['unit']} (chưa rõ khu vực)";
            } else {
                $r = $leg['route'];
                $a = $side === 'prev' ? ($r['from'] ?? $leg['other']['unit']) : ($r['from'] ?? 'đây');
                $b = $side === 'prev' ? ($r['to'] ?? 'đây') : ($r['to'] ?? $leg['other']['unit']);
                $parts[] = "{$label}: {$a} → {$b} ~{$r['km']} km, " . $this->hours($r['minutes']) . $this->basisNote($r) . ($leg['far'] ? ' ⚠ khó kịp' : ' ✓');
            }
        }

        return [
            'text' => ($here ? "Điểm chụp: {$here}" : 'Điểm chụp: chưa rõ khu vực') . ' · ' . implode(' · ', $parts),
            'far' => $legs->contains(fn ($l) => $l['far']),
        ];
    }

    /**
     * Các chặng di chuyển tới / từ điểm chụp liền trước, liền sau của xe hoặc người.
     * Mặc định bỏ chặng cùng đơn vị; $all = true thì giữ lại (để hiển thị).
     */
    private function legs(string $resource, bool $isCar, object $order, array $extra = [], ?ScheduleBoard $board = null, bool $all = false): array
    {
        $board ??= $this->board;
        if (!$resource || !$this->routes->enabled() || ($isCar ? ScheduleBoard::isHiredCar($resource) : ScheduleBoard::isHiredPerson($resource))) {
            return [];
        }
        // Đơn đã qua ngày (ghi bổ sung theo thực tế): không cảnh báo quãng đường
        if (self::isPast($order)) {
            return [];
        }

        $n = $board->neighbours($resource, $order->halves, $isCar, $extra);
        $legs = [];
        foreach ([['prev', 'prev_same_day'], ['next', 'next_same_day']] as [$side, $sameDayKey]) {
            $other = $n[$side];
            // Không có lịch, chính đơn này, hoặc xe nghỉ bảo trì (ở garage, không tính quãng đường)
            if (!$other || $other['order_id'] === $order->id || !empty($other['maintenance_id'])) {
                continue;
            }
            $sameUnit = $order->unit_id && $other['unit_id'] === $order->unit_id;
            if ($sameUnit && !$all) {
                continue;
            }
            $otherSite = $this->routes->site($other['address'], $other['km'] ?? null);
            $hereSite = $order->site ?? $this->routes->site($order->customer_address, $order->accountant_distance ?? null);
            [$from, $to] = $side === 'prev' ? [$otherSite, $hereSite] : [$hereSite, $otherSite];
            $route = $sameUnit ? null : $this->routes->betweenSites($from, $to);
            $limit = $n[$sameDayKey] ? (int) config('schedule.same_day_max_minutes', 90) : (int) config('schedule.next_day_max_minutes', 240);
            $legs[] = [
                'side' => $side,
                'when' => $side === 'prev' ? ($n[$sameDayKey] ? 'buổi sáng' : 'hôm trước') : ($n[$sameDayKey] ? 'buổi chiều' : 'hôm sau'),
                'other' => $other,
                'same_unit' => $sameUnit,
                'route' => $route,
                'far' => $route !== null && $route['minutes'] > $limit,
            ];
        }

        return $legs;
    }

    /**
     * Lựa chọn cho từng ô chọn của một đơn, kèm trạng thái để giao diện hiện "trống / bận / nghỉ",
     * số ca trong tháng và cảnh báo quãng đường.
     */
    public function options(object $order): array
    {
        $board = $this->board;
        $month = $this->casesFor($order);

        $cars = array_map(function ($car) use ($order, $board) {
            $why = $board->carConflicts($car, $order->halves, $order->unit_id);
            $shared = $why ? [] : array_filter($board->entriesFor($car, $order->halves, true), fn ($e) => $e['unit_id'] === $order->unit_id);
            $travel = $why ? [] : $this->travelProblems($car, true, $order);

            return [
                'value' => $car,
                'label' => ScheduleBoard::carLabel($car),
                'state' => $why ? 'busy' : ($travel ? 'far' : 'free'),
                'km' => $travel ? $this->farKm($car, true, $order) : null,
                'note' => $why ? 'bận: ' . implode(', ', $why) : ($travel ? implode('; ', $travel) : ($shared ? 'đang chạy cùng đơn vị' : '')),
                'travel' => $why ? null : $this->travelInfo($car, true, $order),
            ];
        }, $this->allCars());

        $people = function (string $role, string $hired) use ($order, $board, $month) {
            $list = $board->staff()->where('staff_role', $role)->reject(fn ($s) => ScheduleBoard::isHiredPerson($s->staff_name))
                ->map(function ($s) use ($order, $board, $month) {
                    $why = $board->staffConflicts($s->staff_name, $order->halves, $order->unit_id);
                    $travel = $why ? [] : $this->travelProblems($s->staff_name, false, $order);

                    return [
                        'value' => $s->staff_name,
                        'label' => $s->staff_name,
                        'cases' => $month[$s->staff_name] ?? 0,
                        'cchn' => (bool) $s->staff_certificate,
                        'state' => $why ? 'busy' : ($travel ? 'far' : 'free'),
                        'km' => $travel ? $this->farKm($s->staff_name, false, $order) : null,
                        'note' => $why ? implode(', ', $why) : implode('; ', $travel),
                    ];
                })
                ->sortBy(fn ($p) => [['free' => 0, 'far' => 1, 'busy' => 2][$p['state']], $p['cases']])
                ->values()->all();
            $list[] = ['value' => $hired, 'label' => $hired, 'cases' => null, 'cchn' => false, 'state' => 'free', 'note' => 'thuê ngoài'];

            return $list;
        };

        $driver = $people('TX', config('schedule.hired_driver'));
        $ktv = $people('KTV', config('schedule.hired_ktv'));
        // Sửa lịch: người đang xếp đã bị xóa khỏi Quản lý nhân viên vẫn hiện (đã nghỉ) để ô chọn không tự nhảy sang người khác
        $kept = self::retiredOnOrder($order, $board->staff()->keyBy('staff_name'));
        $retired = fn ($name) => ['value' => $name, 'label' => $name . ' (đã nghỉ)', 'cases' => null, 'cchn' => false, 'state' => 'free', 'note' => 'đã xóa khỏi Quản lý nhân viên'];
        if (!empty($kept['driver'])) {
            array_unshift($driver, $retired($kept['driver']));
        }
        foreach (array_reverse($kept['ktv'] ?? []) as $name) {
            array_unshift($ktv, $retired($name));
        }

        return ['car' => $cars, 'driver' => $driver, 'ktv' => $ktv];
    }

    /**
     * Người đang xếp trên lịch của đơn (chỉ có khi sửa lịch) mà không còn trong Quản lý nhân viên.
     *
     * @return array{driver?: string, ktv?: array<int,string>}
     */
    private static function retiredOnOrder(object $order, $staffByName): array
    {
        $missing = fn ($name) => $name && !ScheduleBoard::isHiredPerson($name) && !isset($staffByName[$name]);
        $driver = $order->car_driver_name ?? null;
        $ktvs = array_values(array_filter([$order->car_ktv_name_1 ?? null, $order->car_ktv_name_2 ?? null], $missing));

        return array_filter(['driver' => $missing($driver) ? $driver : null, 'ktv' => $ktvs]);
    }

    private function allCars(): array
    {
        return array_values(array_unique(array_filter(array_merge(
            config('schedule.own_cars', []), [config('schedule.ultrasound_car')], config('schedule.hired_cars', [])))));
    }

    private function loadDriverHistory(): array
    {
        $rows = DB::table('car_ktvs')
            ->where('car_active', 1)
            ->where('created_at', '>=', now()->subYear())
            ->whereNotNull('car_driver_name')
            ->groupBy('car_name', 'car_driver_name')
            ->select('car_name', 'car_driver_name', DB::raw('count(*) as n'))
            ->get();

        $history = [];
        foreach ($rows as $r) {
            $history[(string) $r->car_name][$r->car_driver_name] = (int) $r->n;
        }

        return $history;
    }

    /** Số ca (đã chụp + đã xếp) của mỗi người trong tháng của đơn; có cộng phần giữ chỗ trong lượt gợi ý */
    private function casesFor(object $order): array
    {
        $key = substr($order->ord_start_day, 0, 7);
        if (!isset($this->monthCases[$key])) {
            $this->monthCases[$key] = array_map(fn ($c) => $c['done'] + $c['planned'],
                ScheduleBoard::monthlyCases(Carbon::parse($order->ord_start_day)));
        }

        return $this->monthCases[$key];
    }

    /** Cộng dồn số ca dự kiến của đơn vừa giữ chỗ trong lượt gợi ý, theo cách tính chấm công */
    private function addCases(object $order, ?string $driver, array $ktvs): void
    {
        $key = substr($order->ord_start_day, 0, 7);
        $this->casesFor($order);
        $add = function (?string $person, float $value) use ($key) {
            if ($person && !ScheduleBoard::isHiredPerson($person)) {
                $this->monthCases[$key][$person] = ($this->monthCases[$key][$person] ?? 0) + $value;
            }
        };
        // Tài xế: số ca × hệ số bộ phận chụp; KTV: số ca
        $add($driver, $order->order_quantity * Timekeeping::multiplier($order->ord_select));
        foreach (array_filter($ktvs) as $ktv) {
            $add($ktv, (float) $order->order_quantity);
        }
    }

    /** Phương án theo quy tắc: lần lượt từng đơn, giữ chỗ để các đơn sau không trùng */
    private function rulePlan(Collection $orders): array
    {
        $board = $this->board;
        $reservedCars = [];
        $reservedStaff = [];
        $items = [];

        foreach ($orders as $order) {
            $halves = $order->halves;
            $session = trim((string) $order->ord_session) ?: 'Cả ngày';
            $cases = $this->casesFor($order);
            // Đơn nhiều cas (từ ngưỡng 2 KTV) chỉ CẢNH BÁO, không tự thêm KTV 2: người xếp lịch tự quyết
            $need = 1;

            // 0. Đơn vị đã giữ chỗ đúng ngày đó: xếp vào chỗ đã giữ
            $holdPlan = ($order->hold['same_dates'] ?? false) ? $this->planFromHold($order, $reservedStaff) : null;
            // 1. Cùng đơn vị đã có xe chạy cùng buổi (đã xếp hoặc vừa gợi ý): đi chung xe, chung đội
            $plan = $holdPlan ? null : $this->sameUnitPlan($order, $reservedCars);
            if ($holdPlan) {
                [$plan, $reasons] = $holdPlan;
            } elseif ($plan) {
                $reasons = [(!empty($order->group_code) ? "Nhóm đi chung {$order->group_code} với đơn #{$plan['with']}" : "Cùng đơn vị {$order->unit} với đơn #{$plan['with']}")
                    . ': đi chung ' . ScheduleBoard::carLabel($plan['car']) . ' và cùng đội'];
                unset($plan['with']);
            } else {
                // 2. Xe: trống, kịp di chuyển, ít việc
                $carChoices = ScheduleBoard::isUltrasound($order->ord_select) ? [config('schedule.ultrasound_car')] : config('schedule.own_cars', []);
                $usable = array_values(array_filter($carChoices, fn ($c) => !$board->carConflicts($c, $halves, $order->unit_id, $reservedCars)));
                $reachable = array_values(array_filter($usable, fn ($c) => !$this->travelProblems($c, true, $order, $reservedCars)));
                $pool = $reachable ?: $usable;
                usort($pool, fn ($a, $b) => $board->carLoad($a) <=> $board->carLoad($b));
                $car = $pool[0] ?? config('schedule.hired_cars')[0];
                $reasons = [isset($pool[0])
                    ? ScheduleBoard::carLabel($car) . " trống {$this->sessionText($session)}" . ($reachable ? '' : ' (quãng đường xa, cần xem lại)')
                    : 'Xe nhà đã kín, dùng xe thuê'];

                // 3. Tài xế: ít ca trong tháng nhất, cùng mức thì ưu tiên người quen xe
                $driver = $this->pickPeople('TX', $order, 1, $reservedStaff, [], fn ($s) => -($this->driverHistory[$car][$s->staff_name] ?? 0))[0]
                    ?? config('schedule.hired_driver');
                $reasons[] = ScheduleBoard::isHiredPerson($driver) ? 'Không còn tài xế nhà trống, dùng TX thuê'
                    : "{$driver} đang ít ca nhất tháng (" . number_format($cases[$driver] ?? 0, 0, ',', '.') . ' ca)';

                // 4. KTV: có CCHN trước, rồi ít ca trong tháng
                $ktvs = $this->pickKtvs($order, $need, $reservedStaff);
                while (count($ktvs) < $need) {
                    $ktvs[] = config('schedule.hired_ktv');
                }
                $reasons[] = 'Chọn KTV trống, ưu tiên có CCHN và ít ca trong tháng';
                $plan = ['car' => (string) $car, 'driver' => $driver, 'ktv1' => $ktvs[0] ?? null, 'ktv2' => $ktvs[1] ?? null];
            }

            $items[] = $this->item($order, $plan, $reasons, 'rule', $reservedCars, $reservedStaff);
            $this->reserve($order, $plan, $reservedCars, $reservedStaff);
        }

        return $items;
    }

    /** Thông tin lịch giữ chỗ gắn với một đơn (để hiện trên thẻ / form) */
    public static function holdInfo(ScheduleHold $h, object $order): array
    {
        $holdHalves = ScheduleBoard::halvesOf($h->hold_start->toDateString(), $h->hold_end->toDateString(), $h->hold_session);
        $days = [];
        for ($d = $h->hold_start->copy(); $d->lte($h->hold_end) && count($days) < 32; $d->addDay()) {
            $days[] = $d->toDateString();
        }

        return [
            'id' => $h->id,
            'start' => $h->hold_start->toDateString(),
            'end' => $h->hold_end->toDateString(),
            'session' => $h->hold_session,
            'car' => $h->car,
            'car_label' => ScheduleBoard::carLabel($h->car),
            'customer' => $h->customer,
            'driver' => $h->driver,
            'ktv1' => $h->ktv1,
            'ktv2' => $h->ktv2,
            'note' => $h->note,
            'days' => $days,
            // Đơn nằm trọn trong chỗ giữ (giữ nhiều ngày thì đơn chỉ chiếm ngày của nó)
            'same_dates' => $order->halves && !array_diff($order->halves, $holdHalves),
        ];
    }

    /**
     * Ngày / buổi mới của đơn khi đổi sang lịch giữ chỗ. Mỗi đơn chỉ chiếm ngày của nó (thường 1 ngày),
     * không lấy hết các ngày giữ chỗ: chọn $day trong khoảng giữ (mặc định ngày giữ gần ngày đơn nhất),
     * giữ nguyên số ngày của đơn; buổi theo chỗ giữ, chỗ giữ "Cả ngày" thì giữ buổi của đơn.
     *
     * @return array{start: string, end: string, session: string}
     */
    public static function moveTarget(object $order, array $info, ?string $day = null): array
    {
        $first = Carbon::parse($info['start']);
        $last = Carbon::parse($info['end']);
        $orderStart = Carbon::parse($order->ord_start_day);
        $length = $order->ord_end_day ? max(0, min(31, $orderStart->diffInDays(Carbon::parse($order->ord_end_day), false))) : 0;
        if ($day && in_array($day, $info['days'], true)) {
            $start = Carbon::parse($day);
        } elseif ($orderStart->lt($first)) {
            $start = $first;
        } elseif ($orderStart->gt($last)) {
            $start = $last;
        } else {
            $start = $orderStart;
        }
        $orderSession = trim((string) $order->ord_session) ?: 'Cả ngày';

        return [
            'start' => $start->toDateString(),
            'end' => $start->copy()->addDays($length)->toDateString(),
            'session' => $info['session'] === 'Cả ngày' ? $orderSession : $info['session'],
        ];
    }

    /** Phương án lấy từ lịch giữ chỗ; thiếu tài xế / KTV thì chọn thêm người trống */
    private function planFromHold(object $order, array $reservedStaff): array
    {
        $h = $order->hold;
        $need = 1; // 2 KTV chỉ cảnh báo, người xếp tự thêm
        $plan = ['car' => (string) $h['car'], 'driver' => $h['driver'], 'ktv1' => $h['ktv1'], 'ktv2' => $h['ktv2']];
        $reasons = ['Theo lịch giữ chỗ ' . ScheduleBoard::carLabel($h['car']) . ' ngày ' . Carbon::parse($order->ord_start_day)->format('d/m') . " ({$h['customer']})"];
        if (!$plan['driver']) {
            $plan['driver'] = $this->pickPeople('TX', $order, 1, $reservedStaff, [], fn ($s) => 0)[0] ?? config('schedule.hired_driver');
            $reasons[] = "Giữ chỗ chưa có tài xế: chọn {$plan['driver']} (ít ca)";
        }
        if (!$plan['ktv1']) {
            $picked = $this->pickKtvs($order, $need, $reservedStaff);
            [$plan['ktv1'], $plan['ktv2']] = [$picked[0] ?? null, $picked[1] ?? null];
            $reasons[] = 'Giữ chỗ chưa có KTV: chọn KTV trống, ít ca';
        }

        return [$plan, $reasons];
    }

    /**
     * Xếp một đơn vào lịch giữ chỗ (form thủ công). Nếu ngày đơn khác ngày giữ chỗ thì
     * tính theo ngày giữ chỗ (đơn sẽ được đổi ngày khi áp dụng, người dùng xác nhận trước).
     */
    public function holdPlan(object $order, ScheduleHold $hold, bool $move = true, ?string $day = null): array
    {
        $info = self::holdInfo($hold, $order);
        $original = ['date' => $order->ord_start_day, 'end' => $order->ord_end_day, 'session' => trim((string) $order->ord_session) ?: 'Cả ngày'];
        $target = self::moveTarget($order, $info, $day);
        // $move = false: giữ nguyên ngày của đơn, mọi kiểm tra trống / bận tính theo ngày của đơn.
        // $move = true: đơn chuyển sang MỘT ngày trong khoảng giữ chỗ ($day), không chiếm hết các ngày giữ
        if (!$info['same_dates'] && $move) {
            $order = clone $order;
            $order->ord_start_day = $target['start'];
            $order->ord_end_day = $target['end'];
            $order->ord_session = $target['session'];
            $order->halves = ScheduleBoard::halvesOf($target['start'], $target['end'], $target['session']);
        }
        $order->hold = $info;
        $this->prepare(collect([$order]), [], [$hold->id]);
        [$plan, $reasons] = $this->planFromHold($order, []);
        // Không đổi ngày mà xe / người của chỗ giữ lại bận ngày của đơn: đề xuất lại theo quy tắc
        if ($errors = $this->validate($order, $plan)) {
            $rule = $this->rulePlan(collect([$order]))[0];
            $plan = array_intersect_key($rule, array_flip(['car', 'driver', 'ktv1', 'ktv2']));
            $reasons = array_merge(['Xe / người của lịch giữ chỗ không trống ngày của đơn (' . implode('; ', $errors) . '), đề xuất lại'], $rule['reasons']);
        }

        $item = $this->item($order, $plan, $reasons, 'hold');
        $item['hold_id'] = $hold->id;
        $item['move_to_hold'] = !$info['same_dates'] && $move;
        $item['can_move'] = !$info['same_dates'];
        $item['hold_day'] = $target['start'];
        $item['original'] = $original;

        return ['items' => [$item], 'source' => 'hold', 'note' => null];
    }

    /** Xe + đội của đơn cùng đơn vị đang chạy cùng buổi (đã xếp, hoặc vừa giữ chỗ trong lượt này) */
    private function sameUnitPlan(object $order, array $reservedCars): ?array
    {
        if (!$order->unit_id) {
            return null;
        }
        // Ưu tiên đơn CÙNG NHÓM đi chung đã xếp (đi chung chuyến), rồi mới tới đơn cùng đơn vị cùng buổi
        if (!empty($order->order_group_id)) {
            foreach ($this->board->bookings() as $b) {
                if ((int) ($b->order_group_id ?? 0) === (int) $order->order_group_id && !ScheduleBoard::isHiredCar($b->car_name)) {
                    return ['car' => (string) $b->car_name, 'driver' => $b->car_driver_name, 'ktv1' => $b->car_ktv_name_1, 'ktv2' => $b->car_ktv_name_2, 'with' => $b->id];
                }
            }
            foreach ($reservedCars as $byKey) {
                foreach ($byKey as $entries) {
                    foreach ($entries as $e) {
                        if (($e['group_id'] ?? 0) === (int) $order->order_group_id && isset($e['plan'])) {
                            return $e['plan'] + ['with' => $e['order_id']];
                        }
                    }
                }
            }
        }
        foreach ($this->board->bookings() as $b) {
            if ((int) $b->unit_id === $order->unit_id && array_intersect($b->halves, $order->halves) && !ScheduleBoard::isHiredCar($b->car_name)) {
                return ['car' => (string) $b->car_name, 'driver' => $b->car_driver_name, 'ktv1' => $b->car_ktv_name_1, 'ktv2' => $b->car_ktv_name_2, 'with' => $b->id];
            }
        }
        foreach ($reservedCars as $car => $byKey) {
            foreach ($order->halves as $key) {
                foreach ($byKey[$key] ?? [] as $e) {
                    if ($e['unit_id'] === $order->unit_id && isset($e['plan'])) {
                        return $e['plan'] + ['with' => $e['order_id']];
                    }
                }
            }
        }

        return null;
    }

    private function pickKtvs(object $order, int $count, array $reserved, array $exclude = []): array
    {
        return $this->pickPeople('KTV', $order, $count, $reserved, $exclude, fn ($s) => $s->staff_certificate ? 0 : 1, true);
    }

    /**
     * Chọn người trống (không nghỉ, không có lịch đơn vị khác), ưu tiên kịp di chuyển,
     * rồi xếp theo số ca trong tháng (ít trước). $tieBreak dùng khi số ca gần bằng nhau.
     */
    private function pickPeople(string $role, object $order, int $count, array $reserved, array $exclude, callable $tieBreak, bool $tieFirst = false): array
    {
        $board = $this->board;
        $cases = $this->casesFor($order);
        $free = $board->staff()->where('staff_role', $role)
            ->reject(fn ($s) => ScheduleBoard::isHiredPerson($s->staff_name) || in_array($s->staff_name, $exclude, true))
            ->filter(fn ($s) => !$board->staffConflicts($s->staff_name, $order->halves, $order->unit_id, $reserved));
        $reachable = $free->filter(fn ($s) => !$this->travelProblems($s->staff_name, false, $order, $reserved));
        $pool = $reachable->isNotEmpty() ? $reachable : $free;

        // Số ca làm tròn theo bậc 100 để "gần bằng nhau" thì xét tiêu chí phụ
        return $pool->sortBy(fn ($s) => $tieFirst
                ? [$tieBreak($s), intdiv($cases[$s->staff_name] ?? 0, 100), $cases[$s->staff_name] ?? 0]
                : [intdiv($cases[$s->staff_name] ?? 0, 100), $tieBreak($s), $cases[$s->staff_name] ?? 0])
            ->take($count)->pluck('staff_name')->values()->all();
    }

    private function aiPlan(Collection $orders, array $rule): array
    {
        $board = $this->board;
        $staff = $board->staff()->reject(fn ($s) => ScheduleBoard::isHiredPerson($s->staff_name));
        $ruleById = collect($rule)->keyBy('order_id');

        $payload = [
            'quy_tac' => [
                'xe_nha' => config('schedule.own_cars'),
                'xe_sieu_am' => config('schedule.ultrasound_car') . ' (chỉ cho đơn siêu âm)',
                'xe_thue_khong_gioi_han' => config('schedule.hired_cars'),
                'tai_xe_thue' => config('schedule.hired_driver'),
                'ktv_thue' => config('schedule.hired_ktv'),
                'can_2_ktv_tu_so_cas' => (int) config('schedule.two_ktv_from_cas'),
            ],
            'nhan_su' => $staff->map(fn ($s) => [
                'ten' => $s->staff_name,
                'vai_tro' => $s->staff_role,
                'co_cchn' => (bool) $s->staff_certificate,
                'ghi_chu_cchn' => $s->staff_certificate_note,
            ])->values(),
            'tai_xe_quen_xe' => $this->driverHistory,
            // Chỉ gửi lựa chọn đã qua kiểm tra của code: trạng thái, số ca trong tháng, cảnh báo quãng đường
            'don_cho_xep' => $orders->map(fn ($o) => [
                'order_id' => $o->id,
                'don_vi' => $o->unit,
                'ma_don_vi' => $o->unit_id,
                'ngay' => $o->ord_start_day . ($o->ord_end_day && $o->ord_end_day !== $o->ord_start_day ? " đến {$o->ord_end_day}" : ''),
                'buoi' => $o->ord_session,
                'dich_vu' => $o->ord_select,
                'so_cas' => $o->order_quantity,
                'dia_chi' => $o->customer_address,
                'lua_chon' => $ruleById[$o->id]['options'],
            ])->values(),
            'phuong_an_theo_quy_tac' => array_map(fn ($i) => array_intersect_key($i, array_flip(['order_id', 'car', 'driver', 'ktv1', 'ktv2', 'reasons'])), $rule),
        ];

        $system = <<<'TXT'
Bạn là trợ lý xếp lịch xe chụp X-quang lưu động của công ty NKL. Với mỗi đơn chờ xếp, chọn xe, tài xế (TX) và KTV.
Ràng buộc bắt buộc:
- Chỉ chọn trong "lua_chon" của đơn đó, không chọn mục có state "busy". Mục "far" là khó kịp di chuyển giữa 2 điểm chụp (ước tính theo tỉnh/thành), chỉ dùng khi không còn lựa chọn khác.
- Một xe hoặc một người không chạy 2 đơn vị khác nhau trong cùng buổi (Cả ngày chiếm cả sáng và chiều). Nhiều đơn của CÙNG một đơn vị (cùng ma_don_vi) cùng buổi thì nên đi chung một xe và một đội.
- Đơn từ "can_2_ktv_tu_so_cas" cas trở lên: KHÔNG tự thêm KTV 2 (người xếp lịch tự quyết), chỉ ghi lý do nhắc là đơn nhiều cas. Đơn siêu âm dùng xe siêu âm nếu trống.
Ưu tiên: chia đều số ca trong tháng ("cases") giữa các tài xế và giữa các KTV (người ít ca hơn được ưu tiên, nhớ cộng dồn các đơn bạn vừa xếp trong lượt này); KTV có CCHN; tài xế quen xe khi số ca gần bằng nhau.
Bắt đầu từ "phuong_an_theo_quy_tac" và chỉ đổi khi có lý do rõ ràng. Lý do viết tiếng Việt, ngắn gọn (1-2 câu), nêu điểm chính.
TXT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'order_id' => ['type' => 'integer'],
                            'car' => ['type' => 'string'],
                            'driver' => ['type' => 'string'],
                            'ktv1' => ['type' => 'string', 'description' => 'Chuỗi rỗng nếu đơn không cần KTV'],
                            'ktv2' => ['type' => 'string', 'description' => 'Để chuỗi rỗng nếu chỉ cần 1 KTV'],
                            'reason' => ['type' => 'string'],
                        ],
                        'required' => ['order_id', 'car', 'driver', 'ktv1', 'ktv2', 'reason'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['items'],
            'additionalProperties' => false,
        ];

        $message = $this->client->messages->create(
            model: config('claude.model'),
            maxTokens: 8000,
            system: [['type' => 'text', 'text' => $system]],
            messages: [['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]],
            outputConfig: ['effort' => 'low', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
        );

        $json = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }
        $proposals = collect($json['items'] ?? [])->keyBy('order_id');

        // Kiểm tra lại từng đề xuất của Claude theo thứ tự, giữ chỗ để không trùng nhau
        $this->monthCases = [];
        $reservedCars = [];
        $reservedStaff = [];
        $items = [];
        foreach ($orders as $order) {
            $p = $proposals->get($order->id);
            $plan = $p ? ['car' => (string) $p['car'], 'driver' => $p['driver'], 'ktv1' => $p['ktv1'], 'ktv2' => $p['ktv2'] ?: null] : null;

            if ($plan && !$this->validate($order, $plan, $board, $reservedCars, $reservedStaff)) {
                $items[] = $this->item($order, $plan, [$p['reason']], 'ai', $reservedCars, $reservedStaff);
                $this->reserve($order, $plan, $reservedCars, $reservedStaff);
                continue;
            }

            // Đề xuất của Claude thiếu hoặc sai: dùng phương án theo quy tắc
            $fallback = $ruleById[$order->id];
            $plan = array_intersect_key($fallback, array_flip(['car', 'driver', 'ktv1', 'ktv2']));
            $errors = $this->validate($order, $plan, $board, $reservedCars, $reservedStaff);
            $item = $this->item($order, $plan, $fallback['reasons'], 'rule', $reservedCars, $reservedStaff);
            $item['warnings'] = array_merge(['AI đề xuất không hợp lệ cho đơn này, đang dùng gợi ý theo quy tắc'], $errors, $item['warnings']);
            $items[] = $item;
            $this->reserve($order, $plan, $reservedCars, $reservedStaff);
        }

        return $items;
    }

    private function reserve(object $order, array $plan, array &$cars, array &$staff): void
    {
        $entry = ScheduleBoard::entry($order, "{$order->unit} (đơn #{$order->id}, trong gợi ý)") + ['plan' => $plan, 'group_id' => (int) ($order->order_group_id ?? 0)];
        foreach ($order->halves as $key) {
            if (!ScheduleBoard::isHiredCar($plan['car'])) {
                $cars[$plan['car']][$key][] = $entry;
            }
            foreach ([$plan['driver'], $plan['ktv1'], $plan['ktv2']] as $person) {
                if ($person && !ScheduleBoard::isHiredPerson($person)) {
                    $staff[$person][$key][] = $entry;
                }
            }
        }
        $this->addCases($order, $plan['driver'], [$plan['ktv1'], $plan['ktv2']]);
    }

    private function item(object $order, array $plan, array $reasons, string $source, array $reservedCars = [], array $reservedStaff = []): array
    {
        return [
            'order_id' => $order->id,
            'unit' => $order->unit,
            'company' => $order->ord_cty_name ?? null,
            // Đơn phụ thu (thu thêm cho một đơn chính): người xếp lịch cần biết để xếp chung xe / không tính công
            'surcharge' => (int) ($order->order_surcharge ?? 0) === 1,
            // Nhóm đi chung (Sale chọn khi lên đơn): cả nhóm nên cùng xe, cùng đội
            'group' => $order->group_code ?? null,
            'hold' => $order->hold ?? null,
            'date' => $order->ord_start_day,
            'end_date' => $order->ord_end_day,
            'session' => trim((string) $order->ord_session) ?: 'Cả ngày',
            'service' => $order->ord_select,
            'quantity' => $order->order_quantity,
            'address' => $order->customer_address,
            'area' => $order->area ?? null,
            'from_base_km' => $order->from_base['km'] ?? null,
            'car' => (string) $plan['car'],
            'car_label' => ScheduleBoard::carLabel($plan['car']),
            'driver' => $plan['driver'],
            'ktv1' => $plan['ktv1'],
            'ktv2' => $plan['ktv2'],
            'reasons' => array_values(array_filter($reasons)),
            'warnings' => $this->warnings($order, $plan, $this->board, $reservedCars, $reservedStaff),
            'options' => $this->options($order),
            'source' => $source,
        ];
    }

    private function sessionText(string $session): string
    {
        return ['Sáng' => 'buổi sáng', 'Chiều' => 'buổi chiều'][$session] ?? 'cả ngày';
    }

    public static function isPast(object $order): bool
    {
        return ($order->ord_end_day ?: $order->ord_start_day) < Carbon::today()->toDateString();
    }

    private function basisNote(array $route): string
    {
        return $route['basis'] === 'qua_cong_ty' ? ' (chưa rõ hướng, tính như đi qua công ty)' : '';
    }

    private function hours(int $minutes): string
    {
        return $minutes >= 60 ? intdiv($minutes, 60) . 'h' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) : "{$minutes} phút";
    }
}
