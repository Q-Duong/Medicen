<?php

namespace App\Http\Controllers;

use App\Models\Accounting\AccMemo;
use App\Models\Accounting\CtPartner;
use App\Models\Accounting\CtPayment;
use App\Models\Accounting\CtReceivable;
use App\Services\Accounting\CtBook;
use App\Services\Accounting\VcbBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Công nợ CT (máy CT đặt tại bệnh viện hợp tác) — thay sheet "CT 2026" / "Tong CT 2026".
 * Kế toán nhập mỗi bệnh viện mỗi tháng một dòng và các lần thanh toán; thanh toán qua VCB chú Lộc (L-VCB)
 * tự sinh dòng thu ở Sổ VCB. Các tài khoản khác (Admin…) chỉ xem.
 */
class CtReceivableController extends Controller
{
    public function __construct(private CtBook $book, private VcbBook $vcb)
    {
    }

    private function authorizeEdit(): void
    {
        abort_unless(ReportController::canEdit(Auth::user()), 403, 'Chỉ phòng Kế toán được sửa công nợ CT.');
    }

    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $partnerId = $request->integer('partner') ?: null;
        $tab = in_array($request->query('tab'), ['tong-hop', 'ghi-nho', 'don-vi'], true) ? $request->query('tab') : 'chi-tiet';
        $kind = in_array($request->query('loai'), ['thuong', 'bhyt'], true) ? $request->query('loai') : null;

        // Mở từ dòng "CT …" của Báo cáo thu chi tháng X (?bao_cao=X): đánh dấu các kỳ tạo nên Dự thu / Đã thu tháng đó
        $focus = null;
        if ($m = $request->integer('bao_cao')) {
            $start = \Carbon\Carbon::create($year, max(1, min(12, $m)), 1)->startOfDay();
            $end = $start->copy()->endOfMonth();
            $focus = [
                'month' => $start->month, 'start' => $start, 'end' => $end, 'kind' => $kind,
                'partner' => $partnerId ? CtPartner::find($partnerId) : null,
                'est' => max(0, $this->book->outstandingAsOf($start, $partnerId, $kind)),
                'paid' => $this->book->paidBetween($start, $end, $partnerId, $kind),
            ];
        }

        return view('pages.admin.ct.index', [
            'year' => $year,
            'tab' => $tab,
            'partnerId' => $partnerId,
            'kind' => $kind,
            'focus' => $focus,
            'partners' => CtPartner::withCount('receivables')->orderBy('sort')->orderBy('name')->get(),
            'rows' => $this->book->rows($year, $partnerId)->when($kind, fn ($c) => $c->where('kind', $kind)->values()),
            'summary' => $this->book->summary($year),
            'totalOutstanding' => $this->book->totalOutstanding(),
            'memos' => AccMemo::where('scope', 'ct')->orderBy('sort')->orderBy('id')->get(),
            'canEdit' => ReportController::canEdit(Auth::user()),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeEdit();
        $row = new CtReceivable([
            'year' => (int) $request->query('year', now()->year),
            'month' => (int) $request->query('month', now()->subMonth()->month),
            'kind' => 'thuong',
            'partner_id' => $request->integer('partner') ?: null,
        ]);

        return view('pages.admin.ct.form', ['row' => $row, 'partners' => CtPartner::where('active', true)->orderBy('sort')->get()]);
    }

    public function edit(CtReceivable $receivable)
    {
        $receivable->load(['partner', 'payments']);

        return view('pages.admin.ct.form', [
            'row' => $receivable,
            'partners' => CtPartner::where('active', true)->orWhere('id', $receivable->partner_id)->orderBy('sort')->get(),
            'canEdit' => ReportController::canEdit(Auth::user()),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();
        $data = $this->validated($request);
        $row = CtReceivable::create($data + ['user_id' => Auth::id()]);

        return redirect()->route('ct.edit', $row)->with('bk_success', 'Đã thêm công nợ CT ' . $row->title . '. Nhập các lần thanh toán bên dưới khi bệnh viện trả.');
    }

    public function update(Request $request, CtReceivable $receivable)
    {
        $this->authorizeEdit();
        $receivable->update($this->validated($request, $receivable->id));
        // Đổi đơn vị / tháng: cập nhật lại nội dung các dòng VCB tự sinh
        $receivable->load('partner');
        foreach ($receivable->payments as $p) {
            $this->vcb->syncCtPayment($p);
        }

        return redirect()->route('ct.index', ['year' => $receivable->year, 'partner' => request('back_partner')])
            ->with('bk_success', 'Đã lưu công nợ CT ' . $receivable->title . '.');
    }

    public function destroy(CtReceivable $receivable)
    {
        $this->authorizeEdit();
        $title = $receivable->title;
        $year = $receivable->year;
        DB::transaction(function () use ($receivable) {
            foreach ($receivable->payments as $p) {
                $this->vcb->deleteForPayment($p->id);
            }
            $receivable->delete();
        });

        return redirect()->route('ct.index', ['year' => $year])->with('bk_success', 'Đã xóa công nợ CT ' . $title . '.');
    }

    // ---------- Thanh toán ----------

    public function storePayment(Request $request, CtReceivable $receivable)
    {
        $this->authorizeEdit();
        $data = $this->validatedPayment($request);
        DB::transaction(function () use ($receivable, $data) {
            $p = $receivable->payments()->create($data + ['user_id' => Auth::id()]);
            $this->vcb->syncCtPayment($p);
        });

        return redirect()->route('ct.edit', $receivable)->with('bk_success', 'Đã ghi nhận thanh toán '
            . number_format($data['amount'], 0, ',', '.') . ' đ' . ($data['method'] === 'L-VCB' ? ' (đã thêm dòng thu vào Sổ VCB chú Lộc)' : '') . '.');
    }

    public function updatePayment(Request $request, CtPayment $payment)
    {
        $this->authorizeEdit();
        $payment->update($this->validatedPayment($request, $payment));
        $this->vcb->syncCtPayment($payment->fresh());

        return redirect()->route('ct.edit', $payment->receivable_id)->with('bk_success', 'Đã sửa lần thanh toán.');
    }

    public function destroyPayment(CtPayment $payment)
    {
        $this->authorizeEdit();
        $rid = $payment->receivable_id;
        DB::transaction(function () use ($payment) {
            $this->vcb->deleteForPayment($payment->id);
            $payment->delete();
        });

        return redirect()->route('ct.edit', $rid)->with('bk_success', 'Đã xóa lần thanh toán.');
    }

    // ---------- Đơn vị hợp tác ----------

    public function storePartner(Request $request)
    {
        $this->authorizeEdit();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', 'alpha_dash', 'unique:ct_partners,code'],
        ], ['code.unique' => 'Mã này đã có.', 'code.alpha_dash' => 'Mã chỉ gồm chữ, số, gạch ngang.']);
        CtPartner::create(['name' => trim($data['name']), 'code' => strtoupper($data['code']), 'sort' => (int) CtPartner::max('sort') + 1]);

        return redirect()->route('ct.index', ['tab' => 'don-vi'])->with('bk_success', 'Đã thêm đơn vị ' . $data['name'] . '.');
    }

    public function updatePartner(Request $request, CtPartner $partner)
    {
        $this->authorizeEdit();
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'active' => ['nullable', 'boolean']]);
        $partner->update(['name' => trim($data['name']), 'active' => $request->boolean('active')]);

        return redirect()->route('ct.index', ['tab' => 'don-vi'])->with('bk_success', 'Đã lưu đơn vị ' . $partner->name . '.');
    }

    /** Xóa bệnh viện: chỉ khi chưa có kỳ công nợ nào (đã có thì bỏ "Đang hợp tác" để ngừng, giữ lịch sử) */
    public function destroyPartner(CtPartner $partner)
    {
        $this->authorizeEdit();
        if ($partner->receivables()->exists()) {
            return redirect()->route('ct.index', ['tab' => 'don-vi'])
                ->withErrors(['partner' => 'Bệnh viện "' . $partner->name . '" đã có kỳ công nợ, không xóa được. Bỏ chọn "Đang hợp tác" để ngừng.']);
        }
        // Dòng tự động "CT …" của báo cáo thu chi gắn với mã này (nếu có) thành dòng thường
        \App\Models\ReportItem::where('auto_key', 'like', 'ct:' . $partner->code . '%')->update(['auto_key' => null]);
        $partner->delete();

        return redirect()->route('ct.index', ['tab' => 'don-vi'])->with('bk_success', 'Đã xóa bệnh viện "' . $partner->name . '".');
    }

    // ---------- Ghi nhớ công nợ cũ ----------

    public function saveMemo(Request $request, ?AccMemo $memo = null)
    {
        $this->authorizeEdit();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:50'],
            'amount' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:5000'],
            'scope' => ['required', 'in:ct,vcb'],
        ]);
        $amount = preg_replace('/[^\d-]/', '', (string) ($data['amount'] ?? ''));
        $data['amount'] = $amount === '' ? null : (int) $amount;
        $memo && $memo->exists ? $memo->update($data) : AccMemo::create($data + ['sort' => (int) AccMemo::max('sort') + 1]);

        return back()->with('bk_success', 'Đã lưu ghi nhớ "' . $data['title'] . '".');
    }

    public function destroyMemo(AccMemo $memo)
    {
        $this->authorizeEdit();
        $memo->delete();

        return back()->with('bk_success', 'Đã xóa ghi nhớ "' . $memo->title . '".');
    }

    // ---------- Kiểm tra dữ liệu ----------

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $money = fn ($k) => (int) preg_replace('/[^\d-]/', '', (string) $request->input($k, '0'));
        $request->merge(collect(['revenue', 'cost', 'hospital_share', 'medicen_share', 'hospital_collect', 'medicen_collect'])
            ->mapWithKeys(fn ($k) => [$k => $money($k)])->all());
        $data = $request->validate([
            'partner_id' => ['required', 'exists:ct_partners,id'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'kind' => ['required', 'in:thuong,bhyt'],
            'label' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'revenue' => ['required', 'integer'],
            'cost' => ['required', 'integer'],
            'hospital_share' => ['required', 'integer'],
            'medicen_share' => ['required', 'integer'],
            'hospital_collect' => ['required', 'integer'],
            'medicen_collect' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], ['partner_id.required' => 'Chọn bệnh viện.']);
        // Mỗi bệnh viện / tháng / loại chỉ một dòng (BHYT có thể nhiều đợt, phân biệt bằng ghi chú đợt)
        $dup = CtReceivable::where('partner_id', $data['partner_id'])->where('year', $data['year'])->where('month', $data['month'])
            ->where('kind', $data['kind'])->where('label', $data['label'] ?? null)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($dup) {
            throw \Illuminate\Validation\ValidationException::withMessages(['month' => 'Bệnh viện này đã có công nợ tháng ' . $data['month'] . '/' . $data['year'] . '.']);
        }

        return $data;
    }

    private function validatedPayment(Request $request, ?CtPayment $payment = null): array
    {
        $request->merge(['amount' => (int) preg_replace('/[^\d-]/', '', (string) $request->input('amount'))]);
        $methods = array_keys(CtPayment::METHODS);
        if ($payment && !in_array($payment->method, $methods, true)) {
            $methods[] = $payment->method; // giữ hình thức cũ (M-VCB) khi sửa dòng cũ
        }

        return $request->validate([
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::in($methods)],
            'amount' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['amount.not_in' => 'Nhập số tiền thanh toán.', 'paid_on.required' => 'Nhập ngày thanh toán.']);
    }
}
