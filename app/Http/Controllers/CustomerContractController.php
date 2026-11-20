<?php

namespace App\Http\Controllers;

use App\Models\CustomerContract;
use App\Services\Accounting\ContractPricing;
use App\Services\Accounting\ContractReader;
use App\Services\Claude\AssistantAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Hợp đồng khách hàng (Hành chính → Hợp đồng, Sales + Kế toán; SubAdmin chỉ xem): tải nhiều file hợp đồng → AI đọc từng file → Sales xem lại, chọn đơn vị → Lưu.
 * File gốc: storage/app/hop-dong (file đang chờ lưu ở hop-dong/tmp, tự xóa sau 1 ngày). Dữ liệu dùng cho
 * App\Services\Accounting\ContractPricing (đối chiếu giá đơn, gợi ý báo giá), hạn thanh toán, cảnh báo hết hạn.
 */
class CustomerContractController extends Controller
{
    private const DIR = 'hop-dong';
    private const TMP = 'hop-dong/tmp';
    public const EXPIRING_DAYS = 30;

    public static function canEdit(): bool
    {
        return !AssistantAccess::isSubAdmin(Auth::user());
    }

    public function index(Request $request, ContractPricing $pricing)
    {
        $today = Carbon::today();
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('tt');
        $all = CustomerContract::with('unit:id,unit_code,unit_abbreviation,unit_name')->orderByDesc('signed_on')->orderByDesc('id')->get();
        $statusOf = function (CustomerContract $c) use ($today) {
            if ($c->type === 'thoi_vu') {
                return $c->service_date && $c->service_date->lt($today) ? 'da_chup' : 'sap_chup';
            }
            if (!$c->activeOn($today)) {
                return 'het_han';
            }

            return $c->valid_to && !$c->auto_renew && $c->valid_to->diffInDays($today) <= self::EXPIRING_DAYS ? 'sap_het_han' : 'hieu_luc';
        };
        $all->each(fn ($c) => $c->status = $statusOf($c));
        $list = $all
            ->when($request->query('loai'), fn ($x, $t) => $x->where('type', $t))
            ->when($request->query('don_vi'), fn ($x, $u) => $x->where('unit_id', (int) $u))
            ->when($status, fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->filter(fn ($c) => Str::contains(Str::lower(Str::ascii(implode(' ', [$c->contract_no, $c->party_a, $c->end_company,
                $c->unit->unit_abbreviation ?? '', $c->unit->unit_name ?? '']))), Str::lower(Str::ascii($q)))))
            ->values();

        // Đơn vị có đơn 6 tháng gần đây nhưng chưa có hợp đồng nguyên tắc còn hiệu lực
        $covered = $all->filter(fn ($c) => $c->type === 'nguyen_tac' && $c->status !== 'het_han')->pluck('unit_id')->filter()->unique();
        $missing = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')->join('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)->where('order_details.ord_start_day', '>=', $today->copy()->subMonths(6)->toDateString())
            ->whereNotIn('orders.unit_id', $covered->all() ?: [0])
            ->groupBy('units.id', 'units.unit_abbreviation', 'units.unit_name')
            ->selectRaw('units.id, units.unit_abbreviation, units.unit_name, COUNT(*) n, SUM(orders.order_price) revenue, MAX(order_details.ord_start_day) last_day')
            ->orderByDesc('revenue')->limit(30)->get();

        return view('pages.admin.contracts.index', [
            'list' => $list,
            'counts' => $all->countBy('status'),
            'total' => $all->count(),
            'underBilled' => $pricing->underBilled(),
            'missing' => $missing,
            'units' => $this->units(),
            'canEdit' => self::canEdit(),
        ]);
    }

    public function create()
    {
        return view('pages.admin.contracts.create', ['units' => $this->units()]);
    }

    /** AI đọc một file: lưu tạm, trả về form xem lại (HTML) đã điền sẵn */
    public function extract(Request $request, ContractReader $reader)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:pdf,docx,jpg,jpeg,png', 'max:30720']], [
            'file.required' => 'Chưa chọn file.',
            'file.mimes' => 'Chỉ đọc được PDF, Word (.docx) và ảnh JPG / PNG.',
            'file.max' => 'File tối đa 30 MB.',
        ]);
        @set_time_limit(300);
        $this->cleanTmp();
        $file = $request->file('file');
        $token = Str::uuid()->toString() . '.' . strtolower($file->getClientOriginalExtension());
        $file->storeAs(self::TMP, $token, 'local');
        try {
            $data = $reader->read(Storage::disk('local')->path(self::TMP . '/' . $token), $file->getClientOriginalName());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'form' => $this->formHtml(null, ['tmp' => $token, 'file_name' => $file->getClientOriginalName()])], 422);
        } catch (Throwable $e) {
            Log::warning('AI đọc hợp đồng lỗi', ['file' => $file->getClientOriginalName(), 'error' => $e->getMessage()]);

            return response()->json(['message' => 'AI chưa đọc được file (lỗi kết nối hoặc file hỏng). Có thể nhập tay bên dưới.',
                'form' => $this->formHtml(null, ['tmp' => $token, 'file_name' => $file->getClientOriginalName()])], 502);
        }
        Log::info('AI đọc hợp đồng khách hàng', ['file' => $file->getClientOriginalName(), 'by' => Auth::id()]);
        $dup = $data['contract_no'] ? CustomerContract::where('contract_no', $data['contract_no'])->first() : null;

        return response()->json([
            'form' => $this->formHtml(null, $data + ['tmp' => $token, 'file_name' => $file->getClientOriginalName()]),
            'readable' => $data['readable'],
            'duplicate' => $dup ? ['id' => $dup->id, 'url' => route('contracts.edit', $dup->id)] : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $prev = $this->previousContracts($data, null);
        if ($prev->isNotEmpty() && !$request->has('close_previous')) {
            return $this->askClosePrevious($prev, $data);
        }
        $tmp = $request->input('tmp');
        if ($tmp && preg_match('/^[0-9a-f-]{36}\.[a-z0-9]{2,5}$/', $tmp) && Storage::disk('local')->exists(self::TMP . '/' . $tmp)) {
            $name = now()->format('Ymd-His') . '-' . Str::slug(pathinfo((string) $request->input('file_name'), PATHINFO_FILENAME) ?: 'hop-dong') . '.' . pathinfo($tmp, PATHINFO_EXTENSION);
            Storage::disk('local')->move(self::TMP . '/' . $tmp, self::DIR . '/' . $name);
            $data['file_path'] = self::DIR . '/' . $name;
            $data['file_name'] = mb_substr((string) $request->input('file_name'), 0, 255) ?: $name;
        } elseif ($request->hasFile('file')) {
            [$data['file_path'], $data['file_name']] = $this->storeUpload($request);
        }
        $c = CustomerContract::create($data + ['created_by' => Auth::id(), 'updated_by' => Auth::id()]);
        $closed = $request->boolean('close_previous') ? $this->closePrevious($prev, $c) : '';

        return response()->json(['ok' => true, 'id' => $c->id, 'url' => route('contracts.edit', $c->id),
            'message' => 'Đã lưu hợp đồng ' . ($c->contract_no ?: '#' . $c->id) . ($c->unit ? ' — ' . ($c->unit->unit_abbreviation ?: $c->unit->unit_name) : '') . '.' . $closed]);
    }

    public function edit(CustomerContract $contract, ContractPricing $pricing)
    {
        $checks = $pricing->orders(fn ($q) => $q->where('orders.unit_id', $contract->unit_id ?: 0)
            ->when($contract->type === 'thoi_vu', fn ($q) => $q->where(fn ($q) => $q->where('orders.id', $contract->order_id ?: 0)
                ->orWhere('order_details.ord_start_day', optional($contract->service_date)->toDateString() ?? '1900-01-01')))
            ->when($contract->type === 'nguyen_tac', fn ($q) => $q->where('order_details.ord_start_day', '>=', ($contract->valid_from ?? $contract->signed_on ?? Carbon::today()->subYear())->toDateString()))
            ->orderByDesc('order_details.ord_start_day')->limit(40))
            ->map(fn ($o) => ($r = $pricing->checkOrder($o)) && $r['contract_id'] === $contract->id ? $r + ['order_id' => $o->id, 'date' => $o->ord_start_day, 'company' => $o->ord_cty_name, 'qty' => (int) $o->order_quantity] : null)
            ->filter()->values();

        return view('pages.admin.contracts.edit', [
            'contract' => $contract,
            'form' => $this->formHtml($contract, [], !self::canEdit()),
            'checks' => $checks,
            'canEdit' => self::canEdit(),
        ]);
    }

    public function update(Request $request, CustomerContract $contract)
    {
        $data = $this->validated($request);
        // Chỉ hỏi khi đổi đơn vị / loại / ngày bắt đầu (giữ cả hai hợp đồng rồi thì lần sửa sau không hỏi lại)
        $moved = (int) $contract->unit_id !== (int) $data['unit_id'] || $contract->type !== $data['type']
            || optional($contract->valid_from ?? $contract->signed_on)->toDateString() !== ($data['valid_from'] ?? $data['signed_on'] ?? null);
        $prev = $moved ? $this->previousContracts($data, $contract->id) : collect();
        if ($prev->isNotEmpty() && !$request->has('close_previous')) {
            return $this->askClosePrevious($prev, $data);
        }
        if ($request->hasFile('file')) {
            $old = $contract->file_path;
            [$data['file_path'], $data['file_name']] = $this->storeUpload($request);
            if ($old) {
                Storage::disk('local')->delete($old);
            }
        }
        $contract->update($data + ['updated_by' => Auth::id()]);
        $closed = $request->boolean('close_previous') ? $this->closePrevious($prev, $contract) : '';
        session()->flash('success', 'Đã cập nhật hợp đồng ' . ($contract->contract_no ?: '#' . $contract->id) . '.' . $closed);

        return response()->json(['ok' => true, 'id' => $contract->id, 'url' => route('contracts.index'), 'message' => 'Đã cập nhật hợp đồng.' . $closed]);
    }

    public function destroy(CustomerContract $contract)
    {
        if ($contract->file_path) {
            Storage::disk('local')->delete($contract->file_path);
        }
        $label = $contract->contract_no ?: '#' . $contract->id;
        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Đã xóa hợp đồng ' . $label . '.');
    }

    /** Tải file Word hợp đồng do trợ lý AI soạn */
    public function draftDownload(string $file)
    {
        $path = \App\Services\Contracts\ContractDrafter::DIR . '/' . basename($file);
        abort_unless(Storage::disk('local')->exists($path), 404, 'File đã hết hạn (giữ ' . config('contract_templates.keep_days') . ' ngày) — nhờ trợ lý soạn lại.');

        return Storage::disk('local')->download($path, 'Hop-dong-' . substr(basename($file), 16));
    }

    public function download(CustomerContract $contract)
    {
        abort_unless($contract->file_path && Storage::disk('local')->exists($contract->file_path), 404, 'Không còn file gốc.');

        return Storage::disk('local')->download($contract->file_path, $contract->file_name ?: basename($contract->file_path));
    }

    /** Đơn của đơn vị quanh ngày chụp (để gắn hợp đồng thời vụ với đơn) */
    public function orders(Request $request)
    {
        $unit = (int) $request->query('unit');
        $date = $request->query('date');
        if (!$unit || !$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['orders' => []]);
        }

        return response()->json(['orders' => $this->orderOptions($unit, $date)]);
    }

    /**
     * Form thêm / sửa / copy đơn: giá theo hợp đồng cho số đang nhập (đơn vị, ngày chụp, số ca, Tổng tiền, Tên Cty).
     * Chỉ để nhắc Sales, không chặn lưu đơn.
     */
    public function orderHint(Request $request, ContractPricing $pricing)
    {
        $unit = (int) $request->query('unit_id');
        $date = (string) $request->query('date');
        if (!$unit || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['contract' => null]);
        }
        $num = fn ($v) => (int) preg_replace('/\D/', '', (string) $v);
        $o = (object) ['id' => (int) $request->query('order_id'), 'unit_id' => $unit, 'order_quantity' => $num($request->query('qty')),
            'order_price' => $num($request->query('price')), 'ord_start_day' => $date, 'ord_end_day' => $date, 'ord_cty_name' => (string) $request->query('company')];
        // Các đơn đang tích "Đi chung với đơn khác" trên form (bậc giá theo tổng số ca của nhóm)
        if ($request->has('group')) {
            $o->group_ids = array_filter(array_map('intval', explode(',', (string) $request->query('group'))));
        }
        $r = $pricing->checkOrder($o);
        if (!$r) {
            return response()->json(['contract' => null]);
        }
        $c = CustomerContract::find($r['contract_id']);
        $history = $r['expected'] === null
            ? collect($pricing->suggest($unit, max(1, $o->order_quantity))['history'])->reject(fn ($h) => $h['ma_don'] === $o->id)->take(3)->values()->all() : [];

        // Các đơn khác trong nhóm đi chung lệch giá khi tính theo nhóm hiện tại (form hỏi có sửa luôn không)
        $groupUpdates = isset($o->group_ids) && $o->group_ids ? $pricing->groupRepricing($o, $o->group_ids) : [];

        return response()->json(['group_updates' => $groupUpdates, 'contract' => [
            'no' => $c->contract_no ?: '#' . $c->id,
            'type' => CustomerContract::TYPES[$c->type] ?? $c->type,
            'price' => $c->priceSummary(),
            'payment_days' => $c->payment_days,
            'url' => \App\Http\Middleware\PageAccess::allows(Auth::user(), ['customer_contracts']) ? route('contracts.edit', $c->id) : null,
        ], 'check' => $r, 'per_case' => $r['expected'] !== null && $o->order_quantity ? (int) round($r['expected'] / $o->order_quantity) : null, 'history' => $history]);
    }

    // ---------------------------------------------------------------

    /** Ngày hợp đồng nguyên tắc bắt đầu áp dụng */
    private static function startOf(array $data): Carbon
    {
        return Carbon::parse($data['valid_from'] ?? $data['signed_on'] ?? Carbon::today());
    }

    /**
     * Hợp đồng nguyên tắc cũ của cùng đơn vị, bắt đầu TRƯỚC hợp đồng mới và vẫn còn hiệu lực vào ngày hợp đồng mới bắt đầu
     * (ký lại năm sau: nên kết thúc hợp đồng cũ để danh sách không có hai hợp đồng "còn hiệu lực").
     */
    private function previousContracts(array $data, ?int $exceptId)
    {
        if ($data['type'] !== 'nguyen_tac') {
            return collect();
        }
        $start = self::startOf($data);

        return CustomerContract::where('type', 'nguyen_tac')->where('unit_id', $data['unit_id'])
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->get()
            ->filter(fn ($c) => ($c->valid_from ?? $c->signed_on ?? $start)->lt($start) && $c->activeOn($start))->values();
    }

    private function askClosePrevious($prev, array $data)
    {
        $start = self::startOf($data);

        return response()->json([
            'confirm_close' => true,
            'end_on' => $start->copy()->subDay()->format('d/m/Y'),
            'start_on' => $start->format('d/m/Y'),
            'contracts' => $prev->map(fn ($c) => 'HĐ ' . ($c->contract_no ?: '#' . $c->id) . ' (từ ' . optional($c->valid_from ?? $c->signed_on)->format('d/m/Y')
                . ', ' . ($c->auto_renew ? 'tự gia hạn' : ($c->valid_to ? 'đến ' . $c->valid_to->format('d/m/Y') : 'đến khi chấm dứt')) . ')')->all(),
        ], 409);
    }

    /** Kết thúc hợp đồng cũ vào ngày trước khi hợp đồng mới bắt đầu (ghi nhật ký qua model) */
    private function closePrevious($prev, CustomerContract $new): string
    {
        $end = ($new->valid_from ?? $new->signed_on ?? Carbon::today())->copy()->subDay();
        foreach ($prev as $c) {
            $c->update([
                'valid_to' => $end->toDateString(),
                'auto_renew' => false,
                'note' => trim(($c->note ? $c->note . "\n" : '') . 'Kết thúc ngày ' . $end->format('d/m/Y') . ' do ký hợp đồng mới ' . ($new->contract_no ?: '#' . $new->id) . '.'),
                'updated_by' => Auth::id(),
            ]);
        }

        return $prev->isEmpty() ? '' : ' Đã kết thúc ' . $prev->count() . ' hợp đồng cũ vào ngày ' . $end->format('d/m/Y') . '.';
    }

    private function orderOptions(int $unit, string $date): array
    {
        $d = Carbon::parse($date);

        return DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.unit_id', $unit)->where('orders.status_id', '!=', 0)
            ->whereBetween('order_details.ord_start_day', [$d->copy()->subDays(7)->toDateString(), $d->copy()->addDays(7)->toDateString()])
            ->orderByRaw('ABS(DATEDIFF(order_details.ord_start_day, ?))', [$date])->limit(20)
            ->get(['orders.id', 'order_details.ord_start_day', 'order_details.ord_cty_name', 'orders.order_quantity', 'orders.order_price', 'orders.order_surcharge'])
            ->map(fn ($o) => ['id' => $o->id, 'label' => '#' . $o->id . ' · ' . Carbon::parse($o->ord_start_day)->format('d/m') . ' · ' . ($o->ord_cty_name ?: '—')
                . ' · ' . $o->order_quantity . ' ca · ' . number_format($o->order_price, 0, ',', '.') . ' đ' . ($o->order_surcharge ? ' (phụ thu)' : '')])
            ->all();
    }

    /** Form xem lại / sửa (dùng chung cho thêm từ AI và trang sửa) */
    private function formHtml(?CustomerContract $c, array $ai = [], bool $readonly = false): string
    {
        $v = $c ? array_merge($c->toArray(), [
            'signed_on' => optional($c->signed_on)->toDateString(), 'valid_from' => optional($c->valid_from)->toDateString(),
            'valid_to' => optional($c->valid_to)->toDateString(), 'service_date' => optional($c->service_date)->toDateString(),
        ]) : $ai;
        $unitId = $v['unit_id'] ?? null;
        $orders = ($v['type'] ?? '') === 'thoi_vu' && $unitId && !empty($v['service_date']) ? $this->orderOptions((int) $unitId, $v['service_date']) : [];

        return view('pages.admin.contracts._form', [
            'c' => $c, 'v' => $v, 'units' => $this->units(), 'suggest' => $ai['unit_suggestions'] ?? [], 'orders' => $orders, 'readonly' => $readonly,
        ])->render();
    }

    private function units()
    {
        return DB::table('units')->orderBy('unit_abbreviation')->get(['id', 'unit_code', 'unit_abbreviation', 'unit_name']);
    }

    private function validated(Request $request): array
    {
        $num = fn ($v) => ($s = preg_replace('/[^\d]/', '', (string) $v)) === '' ? null : (int) $s;
        $request->merge([
            'package_price' => $num($request->input('package_price')),
            'tiers' => collect($request->input('tiers', []))->map(fn ($t) => ['min_cases' => $num($t['min_cases'] ?? null), 'max_cases' => $num($t['max_cases'] ?? null),
                'unit' => ($t['unit'] ?? 'ca') === 'ngay' ? 'ngay' : 'ca', 'price' => $num($t['price'] ?? null)])->filter(fn ($t) => $t['price'])->values()->all(),
            'addon_rows' => collect($request->input('addon_rows', []))->map(fn ($a) => ['name' => trim((string) ($a['name'] ?? '')), 'price_per_case' => $num($a['price_per_case'] ?? null)])
                ->filter(fn ($a) => $a['name'] !== '' && $a['price_per_case'] !== null)->values()->all(),
            'surcharge_rows' => collect($request->input('surcharge_rows', []))->map(fn ($s) => ['from_km' => $num($s['from_km'] ?? null), 'to_km' => $num($s['to_km'] ?? null),
                'min_amount' => $num($s['min_amount'] ?? null), 'max_amount' => $num($s['max_amount'] ?? null), 'vat_included' => !empty($s['vat_included'])])
                ->filter(fn ($s) => $s['min_amount'] || $s['max_amount'])->values()->all(),
        ]);
        $d = $request->validate([
            'type' => ['required', 'in:' . implode(',', array_keys(CustomerContract::TYPES))],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'contract_no' => ['nullable', 'string', 'max:120'],
            'party_a' => ['nullable', 'string', 'max:255'],
            'party_a_tax_code' => ['nullable', 'string', 'max:30'],
            'end_company' => ['nullable', 'string', 'max:255'],
            'service' => ['nullable', 'string', 'max:255'],
            'signed_on' => ['nullable', 'date'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'service_date' => ['nullable', 'required_if:type,thoi_vu', 'date'],
            'service_location' => ['nullable', 'string', 'max:255'],
            'price_mode' => ['required', 'in:' . implode(',', array_keys(CustomerContract::PRICE_MODES))],
            'package_price' => ['nullable', 'integer', 'min:0'],
            'tiers' => ['array'],
            'addon_rows' => ['array'],
            'surcharge_rows' => ['array'],
            'averaging_rule' => ['nullable', 'string', 'max:500'],
            'service_radius_km' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'payment_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'result_delivery' => ['nullable', 'string', 'max:2000'],
            'notable_terms' => ['nullable', 'string', 'max:3000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'file' => ['nullable', 'file', 'mimes:pdf,docx,doc,jpg,jpeg,png', 'max:30720'],
        ], [
            'unit_id.required' => 'Chọn đơn vị (Bên A) trên web.',
            'service_date.required_if' => 'Hợp đồng thời vụ cần ngày chụp.',
            'valid_to.after_or_equal' => 'Ngày hết hiệu lực phải sau ngày bắt đầu.',
            'file.max' => 'File tối đa 30 MB.',
        ]);
        if ($d['price_mode'] === 'theo_ca' && $d['type'] === 'nguyen_tac' && empty($d['tiers'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['tiers' => 'Cách tính "theo số ca" cần ít nhất một bậc giá (hoặc đổi cách tính).']);
        }

        return collect($d)->except(['tiers', 'addon_rows', 'surcharge_rows', 'file'])->all() + [
            'price_tiers' => $d['tiers'] ?: null,
            'addons' => $d['addon_rows'] ?: null,
            'surcharges' => $d['surcharge_rows'] ?: null,
            'auto_renew' => $request->boolean('auto_renew'),
            'vat_included' => $request->boolean('vat_included'),
            'order_id' => $d['type'] === 'thoi_vu' ? ($d['order_id'] ?? null) : null,
            'service_date' => $d['type'] === 'thoi_vu' ? ($d['service_date'] ?? null) : null,
            // Thời vụ luôn là trọn gói của đợt; hiệu lực tính theo ngày chụp
            'price_mode' => $d['type'] === 'thoi_vu' ? 'tron_goi_ngay' : $d['price_mode'],
            'valid_to' => $d['type'] === 'thoi_vu' ? null : ($d['valid_to'] ?? null),
        ];
    }

    private function storeUpload(Request $request): array
    {
        $file = $request->file('file');
        $name = now()->format('Ymd-His') . '-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . strtolower($file->getClientOriginalExtension());

        return [$file->storeAs(self::DIR, $name, 'local'), mb_substr($file->getClientOriginalName(), 0, 255)];
    }

    /** Xóa file tạm quá 1 ngày (đọc AI xong nhưng không bấm Lưu) */
    private function cleanTmp(): void
    {
        foreach (Storage::disk('local')->files(self::TMP) as $f) {
            if (Storage::disk('local')->lastModified($f) < now()->subDay()->getTimestamp()) {
                Storage::disk('local')->delete($f);
            }
        }
    }
}
