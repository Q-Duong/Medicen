<?php

namespace App\Http\Controllers;

use App\Models\CarKTV;
use App\Models\CarMaintenance;
use App\Models\HistoryEdit;
use App\Models\Order;
use App\Models\ScheduleHold;
use App\Models\Staff;
use App\Models\StaffLeave;
use App\Services\Schedule\ScheduleBoard;
use App\Services\Schedule\ScheduleCancel;
use App\Services\Schedule\ScheduleHistory;
use App\Services\Schedule\ScheduleSuggester;
use App\Services\Schedule\Timekeeping;
use App\Services\Schedule\ActualCases;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Trang Xếp lịch xe: Technician xem xe / nhân sự trống theo buổi, xin gợi ý (AI) và duyệt áp dụng.
 * Quyền: phòng ban trong config('schedule.user_departments') và Admin.
 */
class DispatchController extends Controller
{
    public function __construct(private ScheduleSuggester $suggester)
    {
    }

    public function index(Request $request)
    {
        $monday = $this->monday($request->query('tuan'));
        $sunday = $monday->copy()->addDays(6);
        $board = ScheduleBoard::forRange($monday, $sunday);

        return view('pages.admin.dispatch.index', $this->pendingData() + [
            'monday' => $monday,
            'timekeeping' => $this->timekeepingData($monday->copy()->addDays(3)),
            'grid' => $board->grid($monday, $sunday),
            'leaves' => StaffLeave::with('staff:id,staff_name,staff_role')
                ->where('leave_end', '>=', Carbon::today()->subDays(7))
                ->orderBy('leave_start')->get(),
            'staffList' => Staff::orderBy('staff_role')->orderBy('staff_name')->get(['id', 'staff_name', 'staff_role'])
                ->reject(fn ($s) => ScheduleBoard::isHiredPerson($s->staff_name))->values(),
            'sessions' => StaffLeave::SESSIONS,
            'maintenances' => CarMaintenance::where('maintenance_end', '>=', Carbon::today()->subDays(7))->orderBy('maintenance_start')->get(),
            'carList' => collect(array_merge(config('schedule.own_cars', []), [config('schedule.ultrasound_car')]))
                ->filter()->unique()->mapWithKeys(fn ($c) => [(string) $c => ScheduleBoard::carLabel($c)]),
            'units' => DB::table('units')->orderBy('unit_abbreviation')->pluck('unit_abbreviation')->filter()->unique()->values(),
        ]);
    }

    /** HTML danh sách đơn chờ (để làm mới sau khi giữ chỗ / xếp lịch, không tải lại trang) */
    public function pending()
    {
        return response(view('pages.admin.dispatch._pending', $this->pendingData())->render());
    }

    private function pendingData(): array
    {
        return [
            'pending' => $this->suggester->pendingOrders(),
            // Đã qua ngày mà chưa xếp xe (ghi bổ sung) và đơn xa hơn khoảng gợi ý (xếp thủ công)
            'pendingPast' => $this->suggester->pendingOrders(-1, null, (int) config('schedule.past_days', 60)),
            'pendingLater' => $this->suggester->pendingOrders((int) config('schedule.future_days', 365))
                ->filter(fn ($o) => $o->ord_start_day > today()->addDays((int) config('schedule.suggest_days', 30))->toDateString())->values(),
        ];
    }

    /** HTML bảng chấm công một tháng (?thang=YYYY-MM) */
    public function timekeeping(Request $request)
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('thang'))
            ? Carbon::createFromFormat('!Y-m', $request->query('thang')) : Carbon::today();

        return response(view('pages.admin.dispatch._timekeeping', ['timekeeping' => $this->timekeepingData($month)])->render());
    }

    private function timekeepingData(Carbon $month): array
    {
        $month = $month->copy()->startOfMonth();
        $service = app(Timekeeping::class);
        $orders = $service->orders($month);

        return [
            'month' => $month,
            'rows' => $service->month($month, $orders),
            'missing' => $service->missingActual($month, $orders),
            'orders' => $orders->count(),
        ];
    }

    /** HTML lịch xe + nhân sự của một tuần (để đổi tuần không cần tải lại trang) */
    public function week(Request $request)
    {
        $monday = $this->monday($request->query('tuan'));
        $sunday = $monday->copy()->addDays(6);

        return response(view('pages.admin.dispatch._week', [
            'monday' => $monday,
            'grid' => ScheduleBoard::forRange($monday, $sunday)->grid($monday, $sunday),
        ])->render());
    }

    /**
     * Bảng lịch xe cả tuần để xuất ảnh (?tuan=): mỗi xe một hàng, mỗi ngày một cột, ghi đủ đơn vị, buổi,
     * số cas, TX, KTV; kèm giữ chỗ, xe bảo trì và lịch nghỉ trong tuần. Trình duyệt chụp thành ảnh PNG.
     */
    public function weekSheet(Request $request)
    {
        $monday = $this->monday($request->query('tuan'));
        $sunday = $monday->copy()->addDays(6);
        $grid = ScheduleBoard::forRange($monday, $sunday)->grid($monday, $sunday);

        $rows = [];
        foreach ($grid['cars'] as $car) {
            $days = [];
            $any = false;
            foreach ($grid['days'] as $day) {
                // Gộp 2 nửa buổi thành từng mục: S + C = Cả ngày
                $entries = [];
                foreach (['S', 'C'] as $half) {
                    foreach ($car['cells'][$day['date'] . '|' . $half] ?? [] as $item) {
                        // Ảnh tuần chỉ hiện đơn bình thường, bỏ đơn phụ thu
                        if (!empty($item['surcharge'])) {
                            continue;
                        }
                        $key = $item['order_id'];
                        $entries[$key] ??= $item + ['halves' => []];
                        $entries[$key]['halves'][] = $half;
                    }
                }
                $entries = array_values(array_map(fn ($e) => $e + ['session' => count($e['halves']) === 2 ? 'Cả ngày' : ($e['halves'][0] === 'S' ? 'Sáng' : 'Chiều')], $entries));
                usort($entries, fn ($a, $b) => [$a['halves'][0] === 'C', $a['unit']] <=> [$b['halves'][0] === 'C', $b['unit']]);
                $any = $any || $entries;
                $days[$day['date']] = $entries;
            }
            // Xe thuê chỉ hiện khi có lịch
            if (!$car['hired'] || $any) {
                $rows[] = ['label' => $car['label'], 'hired' => $car['hired'], 'days' => $days];
            }
        }
        $leaves = StaffLeave::with('staff:id,staff_name,staff_role')
            ->where('leave_start', '<=', $sunday)->where('leave_end', '>=', $monday)->orderBy('leave_start')->get();

        return response(view('pages.admin.dispatch._week_sheet', [
            'monday' => $monday, 'sunday' => $sunday, 'days' => $grid['days'], 'rows' => $rows, 'leaves' => $leaves,
        ])->render());
    }

    public function suggest()
    {
        set_time_limit(180);

        return response()->json($this->suggester->suggest());
    }

    /** Sửa lịch đã xếp (?ids=1,2): điền sẵn xe / người hiện tại, kèm lựa chọn và trạng thái */
    public function editOrders(Request $request)
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))));
        abort_if(!$ids || count($ids) > 20, 422, 'Thiếu mã đơn.');

        return response()->json($this->suggester->editPlans($ids));
    }

    /** Xếp thủ công một đơn: lựa chọn kèm trạng thái + phương án theo quy tắc làm sẵn (không gọi AI) */
    public function order(Request $request, int $order)
    {
        $orders = $this->suggester->pendingOrders((int) config('schedule.future_days', 365), $order, (int) config('schedule.past_days', 60));
        abort_if($orders->isEmpty(), 404, 'Đơn không còn ở trạng thái chờ xếp lịch.');

        // Xếp vào một lịch giữ chỗ cụ thể (?hold=ID)
        if ($holdId = $request->integer('hold')) {
            $hold = ScheduleHold::active()->findOrFail($holdId);

            $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('day')) ? $request->query('day') : null;

            return response()->json($this->suggester->holdPlan($orders->first(), $hold, $request->query('move', '1') !== '0', $day));
        }

        // Đơn thuộc nhóm đi chung: mở cả nhóm (các đơn còn chờ) để xếp cùng xe, cùng đội một lần
        if ($groupId = $orders->first()->order_group_id) {
            $orders = $this->suggester->pendingOrders((int) config('schedule.future_days', 365), null, (int) config('schedule.past_days', 60))
                ->where('order_group_id', $groupId)->values();
        }

        return response()->json($this->suggester->suggest($orders, useAi: false));
    }

    /**
     * Làm mới phương án của các đơn còn lại trong khung sau khi vừa lưu một phần (vd nhóm 3 đơn, đã xếp 2):
     * tính lại trạng thái trống / bận, số ca tháng của TX / KTV theo dữ liệu mới nhất.
     * ?ids= đơn chờ xếp, ?edit= đơn đang sửa lịch. Đơn không còn chờ (đã được xếp ở nơi khác) trả về trong "gone".
     */
    public function refresh(Request $request)
    {
        $parse = fn ($v) => array_slice(array_values(array_filter(array_map('intval', explode(',', (string) $v)))), 0, 50);
        $ids = $parse($request->query('ids'));
        $editIds = $parse($request->query('edit'));
        $items = [];
        if ($ids) {
            $orders = $this->suggester->pendingOrders((int) config('schedule.future_days', 365), null, (int) config('schedule.past_days', 60))
                ->whereIn('id', $ids)->values();
            if ($orders->isNotEmpty()) {
                $items = $this->suggester->suggest($orders, useAi: false)['items'];
            }
        }
        if ($editIds) {
            $items = array_merge($items, $this->suggester->editPlans($editIds)['items']);
        }
        $found = array_column($items, 'order_id');

        return response()->json(['items' => $items, 'gone' => array_values(array_diff(array_merge($ids, $editIds), $found))]);
    }

    /** Áp dụng các phương án đã duyệt. Mỗi đơn được kiểm tra trùng lịch lại ngay trước khi ghi. */
    public function apply(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.order_id' => ['required', 'integer'],
            'items.*.car' => ['required', 'string', 'max:5'],
            'items.*.driver' => ['required', 'string', 'max:100'],
            'items.*.ktv1' => ['nullable', 'string', 'max:255'],
            'items.*.ktv2' => ['nullable', 'string', 'max:255'],
            'items.*.edit' => ['nullable', 'boolean'],
            'items.*.hold_id' => ['nullable', 'integer'],
            'items.*.move_to_hold' => ['nullable', 'boolean'],
            'items.*.hold_day' => ['nullable', 'date_format:Y-m-d'],
            // Chấm công sau khi chụp gửi kèm (khi người dùng có sửa khối này)
            'items.*.actual' => ['nullable', 'array'],
            'items.*.actual.order_quantity_draft' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'items.*.actual.driver_assistance' => ['nullable', 'boolean'],
            'items.*.actual.work_over_250' => ['nullable', 'boolean'],
            'items.*.actual.order_note_ktv' => ['nullable', 'string', 'max:2000'],
        ]);

        $pending = $this->suggester->pendingOrders((int) config('schedule.future_days', 365), null, (int) config('schedule.past_days', 60))->keyBy('id');
        $phones = Staff::pluck('staff_phone', 'staff_name');
        $results = [];

        foreach ($data['items'] as $plan) {
            // KTV không bắt buộc (đơn chỉ thuê xe); chỉ chọn KTV 2 thì coi như KTV 1
            $plan['ktv1'] = ($plan['ktv1'] ?? null) ?: null;
            $plan['ktv2'] = ($plan['ktv2'] ?? null) ?: null;
            if (!$plan['ktv1'] && $plan['ktv2']) {
                [$plan['ktv1'], $plan['ktv2']] = [$plan['ktv2'], null];
            }
            $editing = !empty($plan['edit']);
            if ($editing && ($closed = Order::closedMessage($plan['order_id']))) {
                $results[] = ['order_id' => $plan['order_id'], 'ok' => false, 'errors' => [$closed]];
                continue;
            }
            $order = $editing
                ? $this->suggester->scheduledOrders([$plan['order_id']])->first()
                : $pending->get($plan['order_id']);
            if (!$order) {
                $results[] = ['order_id' => $plan['order_id'], 'ok' => false, 'errors' => [$editing
                    ? 'Đơn này không còn lịch đang xếp (có thể đã bị hủy)'
                    : 'Đơn không còn ở trạng thái chờ xếp lịch']];
                continue;
            }

            // Xếp vào lịch giữ chỗ: chỗ đó không tính là bận; nếu ngày đơn khác ngày giữ chỗ thì đổi ngày đơn
            $hold = null;
            if (!$editing && !empty($plan['hold_id'])) {
                $hold = ScheduleHold::active()->find($plan['hold_id']);
                if (!$hold) {
                    $results[] = ['order_id' => $order->id, 'ok' => false, 'errors' => ['Lịch giữ chỗ không còn (đã dùng hoặc đã xóa)']];
                    continue;
                }
                if (!empty($plan['move_to_hold'])) {
                    $order = $this->moveOrderToHold($order, $hold, $plan['hold_day'] ?? null);
                }
            }

            // Tải lại tình trạng mới nhất (gồm các đơn vừa áp dụng ở vòng trước);
            // khi sửa thì lịch hiện tại của chính đơn này không tính là bận
            $board = $this->suggester->prepare(collect([$order]), $editing ? [$order->id] : [], $hold ? [$hold->id] : []);
            $errors = $this->suggester->validate($order, $plan, $board);
            if ($errors) {
                $results[] = ['order_id' => $order->id, 'ok' => false, 'errors' => $errors];
                continue;
            }

            try {
                $car = DB::transaction(function () use ($order, $plan, $phones, $editing) {
                    // Sửa: cập nhật dòng lịch đang chạy. Xếp mới: đơn từng bị hủy lịch thì dùng lại dòng cũ
                    $car = $editing
                        ? CarKTV::where('order_id', $order->id)->where('car_active', 1)->firstOrFail()
                        : CarKTV::firstOrNew(['order_id' => $order->id]);
                    // SĐT theo Quản lý nhân viên; người đã bị xóa (giữ lại khi sửa lịch) thì giữ SĐT cũ trên lịch
                    $phone = fn ($name) => $phones[$name] ?? collect([
                        $car->car_driver_name => $car->car_driver_phone,
                        $car->car_ktv_name_1 => $car->car_ktv_phone_1,
                        $car->car_ktv_name_2 => $car->car_ktv_phone_2,
                    ])->filter(fn ($v, $k) => $k !== '')->get($name);
                    $car->fill([
                        'car_name' => $plan['car'],
                        'car_active' => 1,
                        'car_driver_name' => $plan['driver'],
                        'car_driver_phone' => $phone($plan['driver']),
                        'car_ktv_name_1' => $plan['ktv1'],
                        'car_ktv_phone_1' => $plan['ktv1'] ? $phone($plan['ktv1']) : null,
                        'car_ktv_name_2' => $plan['ktv2'] ?: null,
                        'car_ktv_phone_2' => $plan['ktv2'] ? $phone($plan['ktv2']) : null,
                    ])->save();

                    if (!$editing) {
                        // Đơn phụ thu giữ "Đã cập nhật số ca thực tế" (2), xem Order::booted
                        Order::whereKey($order->id)->update(['schedule_status' => 1,
                            'status_id' => DB::raw('CASE WHEN order_surcharge = 1 THEN 2 ELSE 1 END')]);
                    }

                    return $car;
                });
            } catch (Throwable $e) {
                Log::error('Áp dụng lịch xe lỗi', ['order' => $order->id, 'error' => $e->getMessage()]);
                $results[] = ['order_id' => $order->id, 'ok' => false, 'errors' => ['Lỗi khi lưu: ' . $e->getMessage()]];
                continue;
            }

            // Chấm công gửi kèm: lưu cùng lúc với lịch (cùng logic + KPI với trang lịch KTV)
            if (!empty($plan['actual'])) {
                $a = $plan['actual'];
                app(ActualCases::class)->save(Order::findOrFail($order->id), [
                    'order_quantity_draft' => $a['order_quantity_draft'] ?? 0,
                    'order_note_ktv' => $a['order_note_ktv'] ?? null,
                    'driver_assistance' => (int) ($a['driver_assistance'] ?? 0),
                    'work_over_250' => (int) ($a['work_over_250'] ?? 0),
                ], Auth::user());
            }

            // Lịch giữ chỗ: đơn chỉ dùng những ngày của nó, các ngày giữ còn lại vẫn giữ.
            // (chỗ được chọn, hoặc chỗ cùng đơn vị, cùng xe, trùng ngày). Không đổi ngày mà đơn nằm ngoài
            // khoảng giữ thì chỗ giữ để nguyên (đơn khác của đơn vị còn xếp vào được)
            $usedHold = $hold ?? ($editing ? null : $this->matchingHold($order, $plan['car']));
            $usedHold = $usedHold ? $this->consumeHold($usedHold, $order) : null;

            // Lịch sử đơn: ai xếp / sửa, xe và người nào (sửa mà không đổi gì thì không ghi)
            ScheduleHistory::record($order->id, $editing ? 'Sửa lịch' : 'Xếp lịch', $plan, 'Xếp lịch xe',
                $editing ? ScheduleHistory::describe(['car' => $order->car_name, 'driver' => $order->car_driver_name, 'ktv1' => $order->car_ktv_name_1, 'ktv2' => $order->car_ktv_name_2]) : null);

            Log::info($editing ? 'Sửa lịch xe từ trang Xếp lịch' : 'Áp dụng lịch xe từ trang Xếp lịch', [
                'order' => $order->id, 'plan' => $plan, 'by' => Auth::id(),
                'before' => $editing ? [$order->car_name, $order->car_driver_name, $order->car_ktv_name_1, $order->car_ktv_name_2] : null,
            ]);
            $results[] = [
                'order_id' => $order->id,
                'ok' => true,
                'edit' => $editing,
                'hold_used' => $usedHold?->id,
                'actual_saved' => !empty($plan['actual']),
                'warnings' => $this->suggester->warnings($order, $plan, $board),
            ];
        }

        return response()->json(['results' => $results]);
    }

    /** Hủy lịch một đơn từ form Sửa lịch: đơn về "Chưa lên lịch", hiện lại ở Đơn chờ xếp lịch (không gửi Zalo) */
    public function cancelSchedule(Order $order)
    {
        abort_if($order->isClosed(), 422, Order::closedMessage($order->id));
        $before = app(ScheduleCancel::class)->cancel($order, 'Xếp lịch xe');
        abort_if($before === null, 422, 'Đơn này chưa có lịch xe.');

        return response()->json(['success' => true, 'before' => $before]);
    }

    /** Tạo lịch giữ chỗ (khách đặt trước, chưa lên đơn) */
    public function storeHold(Request $request)
    {
        $data = $this->holdData($request);
        if ($error = $this->holdConflicts($data)) {
            return response()->json(['message' => $error], 422);
        }

        $hold = ScheduleHold::create($data + ['status' => 'active', 'created_by' => Auth::id()]);
        Log::info('Tạo lịch giữ chỗ', ['hold' => $hold->id, 'by' => Auth::id()]);

        return response()->json(['hold' => $hold, 'matched_unit' => (bool) $data['unit_id']]);
    }

    /** Sửa lịch giữ chỗ: kiểm tra trùng lịch như tạo mới, không tính chính chỗ đang sửa */
    public function updateHold(Request $request, ScheduleHold $hold)
    {
        abort_unless($hold->status === 'active', 404, 'Lịch giữ chỗ không còn (đã dùng hoặc đã xóa).');
        $data = $this->holdData($request);
        if ($error = $this->holdConflicts($data, $hold->id)) {
            return response()->json(['message' => $error], 422);
        }

        $before = $hold->only(['hold_start', 'hold_end', 'hold_session', 'car', 'driver', 'ktv1', 'ktv2', 'customer']);
        $hold->update($data);
        Log::info('Sửa lịch giữ chỗ', ['hold' => $hold->id, 'before' => $before, 'by' => Auth::id()]);

        return response()->json(['hold' => $hold, 'matched_unit' => (bool) $data['unit_id']]);
    }

    private function holdData(Request $request): array
    {
        $data = $request->validate([
            'hold_start' => ['required', 'date'],
            'hold_end' => ['nullable', 'date', 'after_or_equal:hold_start'],
            'hold_session' => ['required', Rule::in(StaffLeave::SESSIONS)],
            'car' => ['required', 'string', 'max:5'],
            'customer' => ['required', 'string', 'max:255'],
            'driver' => ['nullable', 'string', 'max:100'],
            'ktv1' => ['nullable', 'string', 'max:255'],
            'ktv2' => ['nullable', 'string', 'max:255'],
            'expected_cas' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'customer.required' => 'Nhập tên khách / đơn vị đặt trước',
            'hold_end.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi',
        ]);
        $data['hold_end'] = $data['hold_end'] ?? $data['hold_start'];
        $data['customer'] = trim($data['customer']);
        foreach (['driver', 'ktv1', 'ktv2', 'note', 'expected_cas'] as $k) {
            $data[$k] = ($data[$k] ?? null) ?: null;
        }
        if (!$data['ktv1'] && $data['ktv2']) {
            [$data['ktv1'], $data['ktv2']] = [$data['ktv2'], null];
        }
        // Khớp đơn vị có sẵn (viết tắt hoặc tên đầy đủ) để sau này tự gợi ý xếp đơn của đơn vị đó vào đây
        $data['unit_id'] = DB::table('units')->where('unit_abbreviation', $data['customer'])
            ->orWhere('unit_name', $data['customer'])->value('id');

        return $data;
    }

    /** Xe / người của giữ chỗ có bị trùng lịch không (bỏ qua chỗ giữ $ignoreHold khi đang sửa) */
    private function holdConflicts(array $data, ?int $ignoreHold = null): ?string
    {
        $halves = ScheduleBoard::halvesOf($data['hold_start'], $data['hold_end'], $data['hold_session']);
        $unitId = (int) $data['unit_id'];
        $board = ScheduleBoard::forRange(Carbon::parse($data['hold_start']), Carbon::parse($data['hold_end']), [], $ignoreHold ? [$ignoreHold] : []);
        $errors = [];
        if ($c = $board->carConflicts($data['car'], $halves, $unitId)) {
            $errors[] = ScheduleBoard::carLabel($data['car']) . ' đã có lịch: ' . implode(', ', $c);
        }
        if ($data['ktv1'] && $data['ktv1'] === $data['ktv2']) {
            $errors[] = 'KTV 1 và KTV 2 trùng người';
        }
        foreach (array_filter([$data['driver'], $data['ktv1'], $data['ktv2']]) as $person) {
            if ($c = $board->staffConflicts($person, $halves, $unitId)) {
                $errors[] = "{$person} không trống: " . implode(', ', $c);
            }
        }

        return $errors ? implode('; ', $errors) : null;
    }

    /** Chi tiết lịch giữ chỗ + các đơn chờ có thể xếp vào (cùng đơn vị trước, rồi cùng ngày, rồi gần ngày) */
    public function showHold(ScheduleHold $hold)
    {
        abort_unless($hold->status === 'active', 404, 'Lịch giữ chỗ không còn.');
        $start = $hold->hold_start;
        $candidates = $this->suggester->pendingOrders(366)
            ->map(function ($o) use ($hold, $start) {
                $days = abs(Carbon::parse($o->ord_start_day)->diffInDays($start, false));
                $o->match = $hold->unit_id && $o->unit_id === (int) $hold->unit_id ? 'unit' : ($days === 0 ? 'date' : null);
                $o->rank = [$o->match === 'unit' ? 0 : ($o->match === 'date' ? 1 : 2), $days];

                return $o;
            })
            ->filter(fn ($o) => $o->match || $o->rank[1] <= 14)
            ->sortBy('rank')
            ->values()
            ->map(fn ($o) => [
                'id' => $o->id, 'unit' => $o->unit, 'company' => $o->ord_cty_name, 'date' => $o->ord_start_day,
                'session' => $o->ord_session ?: 'Cả ngày', 'quantity' => $o->order_quantity, 'match' => $o->match,
                'surcharge' => (int) ($o->order_surcharge ?? 0) === 1,
            ]);

        return response()->json([
            'hold' => [
                'id' => $hold->id, 'start' => $hold->hold_start->toDateString(), 'end' => $hold->hold_end->toDateString(),
                'session' => $hold->hold_session, 'car' => $hold->car, 'car_label' => ScheduleBoard::carLabel($hold->car),
                'customer' => $hold->customer, 'driver' => $hold->driver, 'ktv' => array_values(array_filter([$hold->ktv1, $hold->ktv2])),
                'ktv1' => $hold->ktv1, 'ktv2' => $hold->ktv2,
                'expected_cas' => $hold->expected_cas, 'note' => $hold->note,
            ],
            'candidates' => $candidates,
        ]);
    }

    public function destroyHold(ScheduleHold $hold)
    {
        $hold->update(['status' => 'cancelled']);
        Log::info('Xóa lịch giữ chỗ', ['hold' => $hold->id, 'by' => Auth::id()]);

        return response()->json(['success' => true]);
    }

    /** Đổi ngày / buổi chụp của đơn sang ngày của lịch giữ chỗ (người dùng đã xác nhận trên giao diện) */
    private function moveOrderToHold(object $order, ScheduleHold $hold, ?string $day = null): object
    {
        // Chỉ chuyển sang MỘT ngày trong khoảng giữ (ngày người dùng chọn), giữ nguyên số ngày của đơn
        $target = ScheduleSuggester::moveTarget($order, ScheduleSuggester::holdInfo($hold, $order), $day);
        $old = "{$order->ord_start_day}" . ($order->ord_end_day && $order->ord_end_day !== $order->ord_start_day ? " - {$order->ord_end_day}" : '') . " ({$order->ord_session})";
        $new = $target['start'] . ($target['end'] !== $target['start'] ? " - {$target['end']}" : '') . " ({$target['session']})";
        $detailId = DB::table('orders')->where('id', $order->id)->value('order_detail_id');
        DB::table('order_details')->where('id', $detailId)->update([
            'ord_start_day' => $target['start'],
            'ord_end_day' => $target['end'],
            'ord_session' => $target['session'],
            'updated_at' => now(),
        ]);
        HistoryEdit::create([
            'order_id' => $order->id,
            'user_name' => Auth::user()->name,
            'history_action' => mb_substr("Đổi ngày chụp {$old} sang {$new} (xếp vào lịch giữ chỗ #{$hold->id})", 0, 100),
        ]);
        Log::info('Đổi ngày đơn theo lịch giữ chỗ', ['order' => $order->id, 'from' => $old, 'to' => $new, 'by' => Auth::id()]);

        return $this->suggester->pendingOrders(366, $order->id)->first() ?? $order;
    }

    /**
     * Đơn dùng lịch giữ chỗ: chỉ những ngày của đơn bị trừ khỏi chỗ giữ. Phần đơn dùng thành một bản ghi
     * "used" (gắn order_id, để tra lại), các ngày còn lại vẫn "active" (giữ nhiều ngày thì có thể tách làm 2).
     * Trả về chỗ giữ đã dùng, hoặc null nếu đơn không nằm trong khoảng giữ.
     */
    private function consumeHold(ScheduleHold $hold, object $order): ?ScheduleHold
    {
        $start = Carbon::parse($order->ord_start_day);
        $end = Carbon::parse($order->ord_end_day ?: $order->ord_start_day);
        $from = $start->max($hold->hold_start)->copy();
        $to = $end->min($hold->hold_end)->copy();
        if ($from->gt($to)) {
            return null;
        }

        return DB::transaction(function () use ($hold, $order, $from, $to) {
            $left = $hold->hold_start->lt($from) ? [$hold->hold_start->copy(), $from->copy()->subDay()] : null;
            $right = $hold->hold_end->gt($to) ? [$to->copy()->addDay(), $hold->hold_end->copy()] : null;
            if (!$left && !$right) {
                $hold->update(['status' => 'used', 'order_id' => $order->id]);

                return $hold;
            }
            $used = $hold->replicate()->fill(['hold_start' => $from, 'hold_end' => $to, 'status' => 'used', 'order_id' => $order->id]);
            $used->save();
            [$keepStart, $keepEnd] = $left ?? $right;
            if ($left && $right) {
                $hold->replicate()->fill(['hold_start' => $right[0], 'hold_end' => $right[1]])->save();
            }
            $hold->update(['hold_start' => $keepStart, 'hold_end' => $keepEnd]);
            Log::info('Đơn dùng một phần lịch giữ chỗ', ['hold' => $hold->id, 'used' => $used->id, 'order' => $order->id,
                'days' => [$from->toDateString(), $to->toDateString()], 'by' => Auth::id()]);

            return $used;
        });
    }

    /** Lịch giữ chỗ cùng đơn vị, cùng xe, trùng buổi với đơn (để tự đánh dấu đã dùng) */
    private function matchingHold(object $order, string $car): ?ScheduleHold
    {
        if (!$order->unit_id) {
            return null;
        }

        return ScheduleHold::active()->where('unit_id', $order->unit_id)->where('car', $car)
            ->where('hold_start', '<=', $order->ord_end_day ?: $order->ord_start_day)
            ->where('hold_end', '>=', $order->ord_start_day)
            ->first();
    }

    /**
     * Chấm công sau khi chụp: số ca thực tế, tài xế phụ KTV (vd đi cùng KTV thuê), công tác trên 250km, ghi chú KTV.
     * Ghi giống hệt trang lịch KTV (cùng service ActualCases, có KPI phần 2).
     */
    public function saveActual(Request $request, int $order)
    {
        $data = $request->validate([
            'order_quantity_draft' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'driver_assistance' => ['required', 'boolean'],
            'work_over_250' => ['required', 'boolean'],
            'order_note_ktv' => ['nullable', 'string', 'max:2000'],
        ], ['order_quantity_draft.integer' => 'Số ca thực tế phải là số']);

        $model = Order::findOrFail($order);
        abort_if($model->isClosed(), 422, Order::closedMessage($model->id));
        abort_unless(CarKTV::where('order_id', $model->id)->where('car_active', 1)->exists(), 422, 'Đơn này chưa được xếp xe.');

        app(ActualCases::class)->save($model, [
            'order_quantity_draft' => $data['order_quantity_draft'] ?? 0,
            'order_note_ktv' => $data['order_note_ktv'] ?? null,
            'driver_assistance' => (int) $data['driver_assistance'],
            'work_over_250' => (int) $data['work_over_250'],
        ], Auth::user());
        Log::info('Chấm công từ trang Xếp lịch', ['order' => $model->id, 'data' => $data, 'by' => Auth::id()]);

        return response()->json(['success' => true]);
    }

    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staffs,id'],
            'leave_start' => ['required', 'date'],
            'leave_end' => ['required', 'date', 'after_or_equal:leave_start'],
            'leave_session' => ['required', Rule::in(StaffLeave::SESSIONS)],
            'leave_note' => ['nullable', 'string', 'max:255'],
        ], [
            'leave_end.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi',
        ]);

        StaffLeave::create($data + ['created_by' => Auth::id()]);

        return back()->with('success', 'Đã ghi lịch nghỉ.');
    }

    public function destroyLeave(StaffLeave $leave)
    {
        $leave->delete();

        return back()->with('success', 'Đã xóa lịch nghỉ.');
    }

    /**
     * Xe nghỉ bảo trì / đăng kiểm. Vẫn cho lưu khi xe đang có lịch (xe hỏng đột xuất),
     * nhưng báo các đơn / giữ chỗ cần chuyển sang xe khác.
     */
    public function storeMaintenance(Request $request)
    {
        $cars = array_map('strval', array_filter(array_merge(config('schedule.own_cars', []), [config('schedule.ultrasound_car')])));
        $data = $request->validate([
            'car' => ['required', Rule::in($cars)],
            'maintenance_start' => ['required', 'date'],
            'maintenance_end' => ['required', 'date', 'after_or_equal:maintenance_start'],
            'maintenance_session' => ['required', Rule::in(StaffLeave::SESSIONS)],
            'maintenance_note' => ['nullable', 'string', 'max:255'],
        ], [
            'car.in' => 'Chỉ ghi bảo trì cho xe nhà',
            'maintenance_end.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi',
        ]);

        $halves = ScheduleBoard::halvesOf($data['maintenance_start'], $data['maintenance_end'], $data['maintenance_session']);
        $board = ScheduleBoard::forRange(Carbon::parse($data['maintenance_start']), Carbon::parse($data['maintenance_end']));
        $busy = collect($board->entriesFor($data['car'], $halves, true))->filter(fn ($e) => empty($e['maintenance_id']))->pluck('label')->unique();

        $m = CarMaintenance::create($data + ['created_by' => Auth::id()]);
        Log::info('Ghi lịch bảo trì xe', ['maintenance' => $m->id, 'car' => $m->car, 'by' => Auth::id()]);

        return back()->with('success', 'Đã ghi lịch bảo trì ' . ScheduleBoard::carLabel($m->car) . '.'
            . ($busy->isNotEmpty() ? ' Lưu ý: xe đang có lịch ' . $busy->take(5)->join(', ') . ($busy->count() > 5 ? '…' : '') . ', cần chuyển sang xe khác.' : ''));
    }

    public function destroyMaintenance(CarMaintenance $maintenance)
    {
        $maintenance->delete();
        Log::info('Xóa lịch bảo trì xe', ['maintenance' => $maintenance->id, 'by' => Auth::id()]);

        return back()->with('success', 'Đã xóa lịch bảo trì ' . ScheduleBoard::carLabel($maintenance->car) . '.');
    }

    private function monday(?string $value): Carbon
    {
        $date = ScheduleBoard::date($value) ?? Carbon::today();

        return $date->copy()->startOfWeek(Carbon::MONDAY);
    }
}
