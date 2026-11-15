<?php

namespace App\Services\Schedule;

use App\Models\CarMaintenance;
use App\Models\Staff;
use App\Models\ScheduleHold;
use App\Models\StaffLeave;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tình trạng xe và nhân sự theo từng buổi (sáng / chiều), tính từ lịch đã xếp (car_ktvs)
 * và lịch nghỉ (staff_leaves). Đơn "Cả ngày" chiếm cả 2 buổi; đơn nhiều ngày chiếm mọi ngày.
 *
 * Nhiều đơn của CÙNG một đơn vị trong cùng buổi được chạy chung một xe / một đội (chụp tại cùng
 * địa điểm), nên không tính là trùng lịch. Chỉ khác đơn vị mới là trùng.
 *
 * Mọi kiểm tra trùng lịch đều làm ở đây bằng code; AI chỉ chọn trong danh sách đang trống.
 */
class ScheduleBoard
{
    public const HALVES = ['S' => 'Sáng', 'C' => 'Chiều'];

    /** @var array<string, array<string, list<array>>> xe => [ "Y-m-d|S" => [mục lịch] ] */
    private array $carBusy = [];

    /** @var array<string, array<string, list<array>>> tên nhân sự => [ "Y-m-d|S" => [mục lịch] ] */
    private array $staffBusy = [];

    /** @var array<string, array<string, string>> tên nhân sự => [ "Y-m-d|S" => ghi chú nghỉ ] */
    private array $staffLeave = [];

    private Collection $bookings;

    private Collection $staff;

    private Collection $holds;

    private Collection $maintenances;

    /**
     * @param list<int> $ignoreOrders đơn đang được sửa: lịch hiện tại của chúng không tính là bận
     * @param list<int> $ignoreHolds lịch giữ chỗ đang được dùng để xếp đơn: không tính là bận
     */
    public function __construct(private Carbon $from, private Carbon $to, private array $ignoreOrders = [], private array $ignoreHolds = [])
    {
        $this->load();
    }

    /** Khoảng xem thêm 1 ngày mỗi phía để biết xe / người ở đâu hôm trước, hôm sau */
    public static function forRange(Carbon $from, Carbon $to, array $ignoreOrders = [], array $ignoreHolds = []): self
    {
        return new self($from->copy()->startOfDay()->subDay(), $to->copy()->startOfDay()->addDay(), $ignoreOrders, $ignoreHolds);
    }

    /** Các nửa buổi mà một đơn chiếm, dạng "Y-m-d|S" / "Y-m-d|C" */
    public static function halvesOf(?string $start, ?string $end, ?string $session): array
    {
        $startDate = self::date($start);
        if (!$startDate) {
            return [];
        }
        $endDate = self::date($end) ?? $startDate;
        if ($endDate->lt($startDate) || $startDate->diffInDays($endDate) > 31) {
            $endDate = $startDate->copy();
        }
        $halves = match (trim((string) $session)) {
            'Sáng' => ['S'],
            'Chiều' => ['C'],
            default => ['S', 'C'],
        };

        $keys = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $day) {
            foreach ($halves as $h) {
                $keys[] = $day->format('Y-m-d') . '|' . $h;
            }
        }

        return $keys;
    }

    public static function date(?string $value): ?Carbon
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('!Y-m-d', $value);
    }

    public static function carLabel(?string $car): string
    {
        $car = trim((string) $car);

        return config("schedule.car_labels.{$car}") ?? "Xe {$car}";
    }

    public static function isHiredCar(?string $car): bool
    {
        return in_array((string) $car, config('schedule.hired_cars', []), true);
    }

    public static function isHiredPerson(?string $name): bool
    {
        return in_array($name, [config('schedule.hired_driver'), config('schedule.hired_ktv')], true);
    }

    public static function isUltrasound(?string $service): bool
    {
        return str_contains(mb_strtolower((string) $service), 'siêu âm');
    }

    public static function needsTwoKtv(int $quantity): bool
    {
        return $quantity >= (int) config('schedule.two_ktv_from_cas', 250);
    }

    /** Mục lịch dùng để giữ chỗ / so trùng */
    public static function entry(object $order, string $label): array
    {
        return [
            'order_id' => (int) $order->id,
            'unit_id' => (int) ($order->unit_id ?? 0),
            'unit' => $order->unit ?? '',
            'address' => $order->customer_address ?? null,
            'km' => $order->accountant_distance ?? null,
            'label' => $label,
        ];
    }

    private function load(): void
    {
        $from = $this->from->toDateString();
        $to = $this->to->toDateString();

        // Đơn đã xếp xe (car_active = 1) có ngày chụp giao với khoảng đang xem
        $this->bookings = DB::table('car_ktvs')
            ->join('orders', 'orders.id', '=', 'car_ktvs.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('accountants', 'accountants.order_id', '=', 'orders.id')
            ->leftJoin('order_groups', 'order_groups.id', '=', 'orders.order_group_id')
            ->where('car_ktvs.car_active', 1)
            ->whereNotNull('car_ktvs.car_name')
            ->when($this->ignoreOrders, fn ($q) => $q->whereNotIn('car_ktvs.order_id', $this->ignoreOrders))
            ->where('order_details.ord_start_day', '<=', $to)
            ->where(fn ($q) => $q->where('order_details.ord_end_day', '>=', $from)
                ->orWhere(fn ($q) => $q->whereNull('order_details.ord_end_day')->where('order_details.ord_start_day', '>=', $from)))
            ->select('car_ktvs.id as car_ktv_id', 'car_ktvs.order_id as id', 'car_ktvs.car_name', 'car_ktvs.car_driver_name',
                'car_ktvs.car_ktv_name_1', 'car_ktvs.car_ktv_name_2', 'order_details.ord_start_day',
                'order_details.ord_end_day', 'order_details.ord_session', 'order_details.ord_select',
                'orders.order_quantity', 'orders.order_surcharge', 'orders.status_id as order_status', 'orders.order_group_id', 'order_groups.code as group_code', 'orders.unit_id', 'customers.customer_address', 'accountants.accountant_distance',
                DB::raw("COALESCE(NULLIF(units.unit_abbreviation, ''), order_details.ord_cty_name) as unit"))
            ->orderBy('order_details.ord_start_day')
            ->get()
            ->map(function ($b) {
                $b->halves = self::halvesOf($b->ord_start_day, $b->ord_end_day, $b->ord_session);

                return $b;
            });

        foreach ($this->bookings as $b) {
            $entry = self::entry($b, "{$b->unit} (đơn #{$b->id})");
            foreach ($b->halves as $key) {
                if (!self::isHiredCar($b->car_name)) {
                    $this->carBusy[(string) $b->car_name][$key][] = $entry;
                }
                foreach ([$b->car_driver_name, $b->car_ktv_name_1, $b->car_ktv_name_2] as $person) {
                    if ($person && !self::isHiredPerson($person)) {
                        $this->staffBusy[$person][$key][] = $entry;
                    }
                }
            }
        }

        // Lịch giữ chỗ (khách đặt trước, chưa lên đơn): xe / người cũng tính là bận.
        // Đơn của đúng đơn vị đã giữ chỗ thì không bị coi là trùng (cùng unit_id).
        $this->holds = ScheduleHold::active()
            ->where('hold_start', '<=', $to)->where('hold_end', '>=', $from)
            ->when($this->ignoreHolds, fn ($q) => $q->whereNotIn('id', $this->ignoreHolds))
            ->get()
            ->map(function (ScheduleHold $h) {
                $h->halves = self::halvesOf($h->hold_start->toDateString(), $h->hold_end->toDateString(), $h->hold_session);

                return $h;
            });
        foreach ($this->holds as $h) {
            $entry = [
                'order_id' => -$h->id,
                'hold_id' => $h->id,
                'unit_id' => (int) $h->unit_id,
                'unit' => $h->customer,
                'address' => null,
                'label' => "Giữ chỗ: {$h->customer}",
            ];
            foreach ($h->halves as $key) {
                if (!self::isHiredCar($h->car)) {
                    $this->carBusy[(string) $h->car][$key][] = $entry;
                }
                foreach ([$h->driver, $h->ktv1, $h->ktv2] as $person) {
                    if ($person && !self::isHiredPerson($person)) {
                        $this->staffBusy[$person][$key][] = $entry;
                    }
                }
            }
        }

        // Xe nghỉ bảo trì / đăng kiểm: bận với MỌI đơn (unit_id -1 không trùng đơn vị nào)
        $this->maintenances = CarMaintenance::where('maintenance_start', '<=', $to)->where('maintenance_end', '>=', $from)
            ->orderBy('maintenance_start')->get()
            ->map(function (CarMaintenance $m) {
                $m->halves = self::halvesOf($m->maintenance_start->toDateString(), $m->maintenance_end->toDateString(), $m->maintenance_session);

                return $m;
            });
        foreach ($this->maintenances as $m) {
            $entry = [
                'order_id' => -1000000 - $m->id,
                'maintenance_id' => $m->id,
                'unit_id' => -1,
                'unit' => 'Bảo trì',
                'address' => null,
                'label' => $m->label(),
            ];
            foreach ($m->halves as $key) {
                $this->carBusy[(string) $m->car][$key][] = $entry;
            }
        }

        $this->staff = Staff::orderBy('staff_role')->orderBy('staff_name')->get();
        $names = $this->staff->pluck('staff_name', 'id');

        StaffLeave::where('leave_start', '<=', $to)->where('leave_end', '>=', $from)->get()
            ->each(function (StaffLeave $leave) use ($names) {
                $name = $names[$leave->staff_id] ?? null;
                if (!$name) {
                    return;
                }
                $note = 'Nghỉ' . ($leave->leave_note ? ": {$leave->leave_note}" : '');
                foreach (self::halvesOf($leave->leave_start->toDateString(), $leave->leave_end->toDateString(), $leave->leave_session) as $key) {
                    $this->staffLeave[$name][$key] = $note;
                }
            });
    }

    public function staff(): Collection
    {
        return $this->staff;
    }

    public function bookings(): Collection
    {
        return $this->bookings;
    }

    public function holds(): Collection
    {
        return $this->holds;
    }

    public function maintenances(): Collection
    {
        return $this->maintenances;
    }

    /**
     * Xe đã có lịch của ĐƠN VỊ KHÁC ở các nửa buổi (rỗng = dùng được).
     *
     * @param array<string, array<string, list<array>>> $extra giữ chỗ thêm (các đơn khác trong cùng lượt gợi ý)
     */
    public function carConflicts(string $car, array $halves, int $unitId = 0, array $extra = []): array
    {
        if (self::isHiredCar($car)) {
            return [];
        }

        return $this->clash(array_merge_recursive($this->carBusy[$car] ?? [], $extra[$car] ?? []), $halves, $unitId);
    }

    /** Nhân sự nghỉ, hoặc đã có lịch của đơn vị khác ở các nửa buổi (rỗng = dùng được) */
    public function staffConflicts(string $name, array $halves, int $unitId = 0, array $extra = []): array
    {
        if (self::isHiredPerson($name)) {
            return [];
        }
        $leave = array_values(array_unique(array_intersect_key($this->staffLeave[$name] ?? [], array_flip($halves))));

        return array_merge($leave, $this->clash(array_merge_recursive($this->staffBusy[$name] ?? [], $extra[$name] ?? []), $halves, $unitId));
    }

    private function clash(array $busy, array $halves, int $unitId): array
    {
        $out = [];
        foreach ($halves as $key) {
            foreach ($busy[$key] ?? [] as $entry) {
                if (!$unitId || $entry['unit_id'] !== $unitId) {
                    $out[$entry['label']] = true;
                }
            }
        }

        return array_keys($out);
    }

    /** Lịch đã có (của đơn vị bất kỳ) của xe / người ở các nửa buổi */
    public function entriesFor(string $resource, array $halves, bool $isCar): array
    {
        $busy = $isCar ? ($this->carBusy[$resource] ?? []) : ($this->staffBusy[$resource] ?? []);
        $out = [];
        foreach ($halves as $key) {
            foreach ($busy[$key] ?? [] as $entry) {
                $out[$entry['order_id']] = $entry;
            }
        }

        return array_values($out);
    }

    /**
     * Điểm chụp liền trước và liền sau một đơn của xe / người (để kiểm tra có kịp di chuyển):
     * - "prev": nửa buổi ngay trước buổi đầu của đơn (chiều hôm trước, hoặc sáng cùng ngày)
     * - "next": nửa buổi ngay sau buổi cuối của đơn (sáng hôm sau, hoặc chiều cùng ngày)
     *
     * @return array{prev: ?array, next: ?array, prev_same_day: bool, next_same_day: bool}
     */
    public function neighbours(string $resource, array $halves, bool $isCar, array $extra = []): array
    {
        $busy = $isCar ? ($this->carBusy[$resource] ?? []) : ($this->staffBusy[$resource] ?? []);
        $busy = array_merge_recursive($busy, $extra[$resource] ?? []);
        // Sắp theo thời gian: ngày, rồi sáng (S) trước chiều (C)
        usort($halves, fn ($a, $b) => strtr($a, ['|S' => '|0', '|C' => '|1']) <=> strtr($b, ['|S' => '|0', '|C' => '|1']));
        $first = $halves[0];
        $last = end($halves);

        $before = self::shift($first, -1);
        $after = self::shift($last, 1);

        return [
            'prev' => $busy[$before][0] ?? null,
            'next' => $busy[$after][0] ?? null,
            'prev_same_day' => substr($before, 0, 10) === substr($first, 0, 10),
            'next_same_day' => substr($after, 0, 10) === substr($last, 0, 10),
        ];
    }

    /** Nửa buổi liền trước (-1) / liền sau (+1) */
    public static function shift(string $key, int $dir): string
    {
        [$date, $half] = explode('|', $key);
        if ($dir < 0) {
            return $half === 'C' ? "{$date}|S" : Carbon::parse($date)->subDay()->format('Y-m-d') . '|C';
        }

        return $half === 'S' ? "{$date}|C" : Carbon::parse($date)->addDay()->format('Y-m-d') . '|S';
    }

    /** Trạng thái một nửa buổi của nhân sự: free | busy | leave, kèm mô tả */
    public function staffState(string $name, string $key): array
    {
        if (isset($this->staffLeave[$name][$key])) {
            return ['leave', $this->staffLeave[$name][$key]];
        }
        if (!empty($this->staffBusy[$name][$key])) {
            return ['busy', implode(', ', array_unique(array_column($this->staffBusy[$name][$key], 'label')))];
        }

        return ['free', ''];
    }

    public function carLoad(string $car): int
    {
        return count($this->carBusy[$car] ?? []);
    }

    /**
     * Số ca mỗi người trong tháng, đúng theo bảng Chấm công (Timekeeping):
     * - "done": TX = tổng số ca, KTV = số ca KTV (số liệu tính lương)
     * - "planned": đơn đã xếp nhưng chưa đủ điều kiện tính công (chưa chụp / chưa nhập số ca thực tế)
     *
     * @return array<string, array{done: float, planned: float}>
     */
    public static function monthlyCases(Carbon $month): array
    {
        return app(Timekeeping::class)->workload($month);
    }

    /** Dữ liệu lưới tuần cho giao diện ($days: các ngày hiển thị, không gồm ngày đệm 2 đầu) */
    public function grid(Carbon $from, Carbon $to): array
    {
        $days = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'label' => ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][$day->dayOfWeek] . ' ' . $day->format('d/m'),
                'weekend' => $day->isSunday(),
            ];
        }

        $carKeys = array_merge(config('schedule.own_cars', []), [config('schedule.ultrasound_car')], config('schedule.hired_cars', []));
        $cars = [];
        foreach (array_unique(array_filter($carKeys)) as $car) {
            $cells = [];
            foreach ($this->bookings->where('car_name', $car) as $b) {
                foreach ($b->halves as $key) {
                    $cells[$key][] = [
                        'order_id' => $b->id,
                        'unit_id' => (int) $b->unit_id,
                        'unit' => $b->unit,
                        'quantity' => (int) $b->order_quantity,
                        'surcharge' => (int) $b->order_surcharge === 1,
                        // Đơn đã hoàn thành (kế toán đã chốt): ô màu xanh, chỉ xem
                        'closed' => (int) ($b->order_status ?? 0) === \App\Models\Order::STATUS_CLOSED,
                        'group' => $b->group_code,
                        'driver' => $b->car_driver_name,
                        'ktv' => array_values(array_filter([$b->car_ktv_name_1, $b->car_ktv_name_2])),
                    ];
                }
            }
            foreach ($this->holds->where('car', $car) as $h) {
                foreach ($h->halves as $key) {
                    $cells[$key][] = [
                        'order_id' => -$h->id,
                        'hold_id' => $h->id,
                        'unit_id' => (int) $h->unit_id ?: -$h->id,
                        'unit' => $h->customer,
                        'quantity' => (int) $h->expected_cas,
                        'driver' => $h->driver,
                        'ktv' => array_values(array_filter([$h->ktv1, $h->ktv2])),
                        'note' => $h->note,
                    ];
                }
            }
            foreach ($this->maintenances->where('car', (string) $car) as $m) {
                foreach ($m->halves as $key) {
                    $cells[$key][] = [
                        'order_id' => -1000000 - $m->id,
                        'maintenance_id' => $m->id,
                        'unit_id' => -1,
                        'unit' => 'Bảo trì',
                        'quantity' => 0,
                        'driver' => null,
                        'ktv' => [],
                        'note' => $m->maintenance_note,
                    ];
                }
            }
            $cars[] = ['key' => (string) $car, 'label' => self::carLabel($car), 'hired' => self::isHiredCar($car), 'cells' => $cells];
        }

        // Tháng tính số ca: tháng chứa phần lớn tuần đang xem (ngày thứ 5)
        $month = $from->copy()->addDays(3);
        $cases = self::monthlyCases($month);

        $people = $this->staff->reject(fn ($s) => self::isHiredPerson($s->staff_name))->map(function ($s) use ($days, $cases) {
            $cells = [];
            $weekHalves = 0;
            foreach ($days as $day) {
                foreach (array_keys(self::HALVES) as $h) {
                    $cells[$day['date'] . '|' . $h] = $state = $this->staffState($s->staff_name, $day['date'] . '|' . $h);
                    $weekHalves += $state[0] === 'busy' ? 1 : 0;
                }
            }

            return [
                'id' => $s->id,
                'name' => $s->staff_name,
                'role' => $s->staff_role,
                'certificate' => (bool) $s->staff_certificate,
                'certificate_note' => $s->staff_certificate_note,
                'cells' => $cells,
                'week_halves' => $weekHalves,
                'cases' => $cases[$s->staff_name] ?? ['done' => 0, 'planned' => 0],
            ];
        })->values();

        return ['days' => $days, 'cars' => $cars, 'staff' => $people->all(), 'month' => $month];
    }
}
