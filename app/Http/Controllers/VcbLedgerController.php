<?php

namespace App\Http\Controllers;

use App\Models\Accounting\AccMemo;
use App\Models\Accounting\VcbEntry;
use App\Models\Accounting\VcbRecurring;
use App\Services\Accounting\VcbBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sổ TK VCB chú Lộc — thay file "Các khoản DT ck VCB Mr Lộc".
 * Số dư đầu do kế toán chốt (một ngày + số tiền); số dư các tháng sau tự cộng dồn.
 * Dòng thu CT (L-VCB) sinh từ Công nợ CT, dòng chi cố định sinh từ danh sách khoản cố định.
 */
class VcbLedgerController extends Controller
{
    public function __construct(private VcbBook $book)
    {
    }

    private function authorizeEdit(): void
    {
        abort_unless(ReportController::canEdit(Auth::user()), 403, 'Chỉ phòng Kế toán được sửa sổ VCB.');
    }

    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $month = max(1, min(12, (int) $request->query('month', now()->month)));
        $data = $this->book->month($year, $month);

        return view('pages.admin.vcb.index', $data + [
            'year' => $year,
            'month' => $month,
            'recurrings' => VcbRecurring::orderBy('day')->get(),
            'memos' => AccMemo::where('scope', 'vcb')->orderBy('sort')->orderBy('id')->get(),
            'canEdit' => ReportController::canEdit(Auth::user()),
            'yearBalances' => $data['opening'] ? $this->book->yearBalances($year) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();
        $e = VcbEntry::create($this->validated($request) + ['source' => 'manual', 'user_id' => Auth::id()]);

        return $this->back($e, 'Đã thêm dòng "' . $e->description . '".');
    }

    public function update(Request $request, VcbEntry $entry)
    {
        $this->authorizeEdit();
        abort_if($entry->isLinked(), 422, 'Dòng thu CT tự sinh: sửa ở trang Công nợ CT.');
        abort_if($entry->source === 'opening', 422);
        $entry->update($this->validated($request));

        return $this->back($entry, 'Đã sửa dòng "' . $entry->description . '".');
    }

    public function destroy(VcbEntry $entry)
    {
        $this->authorizeEdit();
        abort_if($entry->isLinked(), 422, 'Dòng thu CT tự sinh: xóa lần thanh toán ở trang Công nợ CT.');
        $entry->delete();

        return $this->back($entry, 'Đã xóa dòng "' . $entry->description . '".');
    }

    /** Chốt số dư đầu (thay số chốt cũ) */
    public function opening(Request $request)
    {
        $this->authorizeEdit();
        $request->merge(['amount' => (int) preg_replace('/[^\d-]/', '', (string) $request->input('amount'))]);
        $data = $request->validate(['date' => ['required', 'date'], 'amount' => ['required', 'integer'], 'note' => ['nullable', 'string', 'max:255']],
            ['date.required' => 'Nhập ngày chốt số dư.']);
        VcbEntry::where('source', 'opening')->delete();
        $e = VcbEntry::create(['entry_date' => $data['date'], 'description' => 'Số dư chốt cuối ngày', 'thu' => $data['amount'], 'chi' => 0,
            'source' => 'opening', 'note' => $data['note'] ?? null, 'user_id' => Auth::id()]);
        $this->book->ensureRecurring();

        return $this->back($e, 'Đã chốt số dư ' . number_format($data['amount'], 0, ',', '.') . ' đ cuối ngày ' . $e->entry_date->format('d/m/Y') . '.');
    }

    public function saveRecurring(Request $request, ?VcbRecurring $recurring = null)
    {
        $this->authorizeEdit();
        $request->merge(['chi' => (int) preg_replace('/[^\d]/', '', (string) $request->input('chi'))]);
        $data = $request->validate(['description' => ['required', 'string', 'max:255'], 'chi' => ['required', 'integer', 'min:1'],
            'day' => ['required', 'integer', 'between:1,31']]);
        $data['active'] = $request->boolean('active', true);
        $recurring && $recurring->exists ? $recurring->update($data) : VcbRecurring::create($data);
        $this->book->ensureRecurring();

        return back()->with('bk_success', 'Đã lưu khoản cố định "' . $data['description'] . '".');
    }

    private function validated(Request $request): array
    {
        $money = fn ($k) => (int) preg_replace('/[^\d]/', '', (string) $request->input($k, '0'));
        $request->merge(['thu' => $money('thu'), 'chi' => $money('chi')]);
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'thu' => ['required', 'integer', 'min:0'],
            'chi' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], ['description.required' => 'Nhập nội dung.', 'entry_date.required' => 'Nhập ngày.']);
        if (!$data['thu'] && !$data['chi']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['thu' => 'Nhập số tiền thu hoặc chi.']);
        }

        return $data;
    }

    private function back(VcbEntry $e, string $msg)
    {
        return redirect()->route('vcb.index', ['year' => $e->entry_date->year, 'month' => $e->entry_date->month])->with('bk_success', $msg);
    }
}
