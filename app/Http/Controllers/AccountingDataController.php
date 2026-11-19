<?php

namespace App\Http\Controllers;

use App\Services\Accounting\DocumentReader;
use App\Services\Accounting\MisaImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Trang "Dữ liệu kế toán" (phòng Kế toán + Admin): nguồn số liệu cho Trợ lý Kế toán.
 * - Tải file sổ sách xuất từ MISA (nhật ký chung, sổ quỹ, ngân hàng, công nợ, lương) → MisaImporter
 *   (nhập lại cùng kỳ thì thay dữ liệu cũ).
 * - Lưu tài liệu (báo cáo tài chính, hợp đồng…) kèm tóm tắt để trợ lý tra cứu; file gốc ở storage/app/ke-toan/tai-lieu.
 */
class AccountingDataController extends Controller
{
    private const DOC_TYPES = ['bctc' => 'Báo cáo tài chính', 'hop_dong' => 'Hợp đồng / phụ lục', 'khac' => 'Tài liệu khác (hóa đơn, biên bản…)'];

    private const BOOKS = ['nkc' => 'Nhật ký chung', 'cash' => 'Sổ quỹ tiền mặt', 'bank' => 'Sổ ngân hàng', 'receivable' => 'Công nợ phải thu',
        'payable' => 'Công nợ phải trả', 'salary' => 'Lương thực trả'];

    public function index(Request $request)
    {
        $coverage = [
            'nkc' => DB::table('acc_journal_entries')->selectRaw('MIN(posted_on) tu, MAX(posted_on) den, COUNT(*) n')->first(),
            'cash' => DB::table('acc_cash_entries')->whereNotNull('posted_on')->groupBy('book')->orderBy('book')
                ->selectRaw('book, MIN(posted_on) tu, MAX(posted_on) den, COUNT(*) n')->get(),
            'parties' => DB::table('acc_party_balances')->groupBy('kind', 'period_from', 'period_to')->orderByDesc('period_to')
                ->selectRaw('kind, period_from, period_to, COUNT(*) n')->get(),
            'salary' => DB::table('acc_salaries')->where('kind', 'luong')->selectRaw("MIN(CONCAT(year,'-',LPAD(month,2,'0'))) tu, MAX(CONCAT(year,'-',LPAD(month,2,'0'))) den")->first(),
        ];
        $imports = DB::table('acc_imports')->leftJoin('users', 'users.id', '=', 'acc_imports.user_id')
            ->orderByDesc('acc_imports.id')->limit(40)->get(['acc_imports.*', 'users.email']);
        $q = trim((string) $request->query('q', ''));
        $documents = DB::table('acc_documents')
            ->when($request->query('type'), fn ($x, $t) => $x->where('type', $t))
            ->when($q !== '', fn ($x) => $x->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('parties', 'like', "%{$q}%")->orWhere('summary', 'like', "%{$q}%")))
            ->orderBy('type')->orderByDesc('signed_on')->orderBy('title')->get();

        return view('pages.admin.accounting.index', [
            'coverage' => $coverage, 'imports' => $imports, 'documents' => $documents,
            'docTypes' => self::DOC_TYPES, 'books' => self::BOOKS,
        ]);
    }

    /** Tải file sổ sách MISA (.xlsx / .xls) */
    public function importBooks(Request $request, MisaImporter $importer)
    {
        $request->validate(['files' => ['required', 'array', 'max:10'], 'files.*' => ['file', 'mimes:xlsx,xls', 'max:30720']],
            ['files.required' => 'Chọn ít nhất một file Excel.', 'files.*.mimes' => 'Chỉ nhận file Excel (.xlsx, .xls).', 'files.*.max' => 'Mỗi file tối đa 30 MB.']);

        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);
        $results = [];
        foreach ($request->file('files') as $file) {
            try {
                $rows = $importer->import($file->getRealPath(), Auth::id(), $file->getClientOriginalName());
                $results[] = ['file' => $file->getClientOriginalName(), 'ok' => true, 'rows' => $rows];
                \App\Models\ActivityLog::record('du_lieu_ke_toan', 'nhap', 'Nhập sổ MISA từ file "' . $file->getClientOriginalName() . '"'
                    . ': ' . collect($rows)->filter(fn ($r) => ($r['rows'] ?? 0) > 0)->map(fn ($r) => $r['book'] . ' ' . number_format($r['rows'], 0, ',', '.') . ' dòng')->implode(', '));
            } catch (Throwable $e) {
                Log::warning('Nhập sổ kế toán lỗi', ['file' => $file->getClientOriginalName(), 'error' => $e->getMessage()]);
                $results[] = ['file' => $file->getClientOriginalName(), 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        return redirect()->route('accounting.index')->with('import_results', $results);
    }

    /** "Đọc bằng AI": Claude đọc file và trả về các trường để điền vào form (không lưu) */
    public function extractDocument(Request $request, DocumentReader $reader)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:pdf,xlsx,xls,docx,jpg,jpeg,png', 'max:30720']], [
            'file.required' => 'Chọn file trước khi bấm Đọc bằng AI.',
            'file.mimes' => 'AI chỉ đọc được PDF, ảnh JPG / PNG, Excel và Word (.docx).',
            'file.max' => 'File tối đa 30 MB.',
        ]);
        @set_time_limit(300);
        $file = $request->file('file');
        try {
            $data = $reader->read($file->getRealPath(), $file->getClientOriginalName());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::warning('AI đọc tài liệu kế toán lỗi', ['file' => $file->getClientOriginalName(), 'error' => $e->getMessage()]);

            return response()->json(['message' => 'AI chưa đọc được file (lỗi kết nối hoặc file hỏng). Thử lại sau ít phút hoặc nhập tay.'], 502);
        }
        Log::info('AI đọc tài liệu kế toán', ['file' => $file->getClientOriginalName(), 'by' => Auth::id()]);

        return response()->json(['data' => $data]);
    }

    public function createDocument()
    {
        return view('pages.admin.accounting.document', ['doc' => null, 'docTypes' => self::DOC_TYPES]);
    }

    public function editDocument(int $id)
    {
        return view('pages.admin.accounting.document', ['doc' => DB::table('acc_documents')->find($id) ?? abort(404), 'docTypes' => self::DOC_TYPES]);
    }

    public function storeDocument(Request $request)
    {
        $data = $this->validateDocument($request, true);
        $data['file_path'] = $this->storeFile($request);
        DB::table('acc_documents')->insert($data + ['user_id' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);
        \App\Models\ActivityLog::record('du_lieu_ke_toan', 'tai_len', 'Thêm tài liệu "' . $data['title'] . '"' . ($data['file_path'] ? ' (có file)' : ''));

        return redirect()->route('accounting.index', ['tab' => 'tai-lieu'])->with('success', 'Đã lưu tài liệu "' . $data['title'] . '".');
    }

    public function updateDocument(Request $request, int $id)
    {
        $doc = DB::table('acc_documents')->find($id) ?? abort(404);
        $data = $this->validateDocument($request, false);
        if ($request->hasFile('file')) {
            $data['file_path'] = $this->storeFile($request);
            if ($doc->file_path) {
                Storage::disk('local')->delete($doc->file_path);
            }
        }
        DB::table('acc_documents')->where('id', $id)->update($data + ['updated_at' => now()]);
        $changed = collect($data)->except('file_path')->filter(fn ($v, $k) => (string) ($doc->{$k} ?? '') !== (string) $v)
            ->mapWithKeys(fn ($v, $k) => [$k => [$doc->{$k} ?? null, $v]])->all();
        \App\Models\ActivityLog::record('du_lieu_ke_toan', 'sua', 'Sửa tài liệu "' . $data['title'] . '"' . ($request->hasFile('file') ? ' (thay file)' : ''), null, $changed ?: null);

        return redirect()->route('accounting.index', ['tab' => 'tai-lieu'])->with('success', 'Đã cập nhật tài liệu "' . $data['title'] . '".');
    }

    public function destroyDocument(int $id)
    {
        $doc = DB::table('acc_documents')->find($id) ?? abort(404);
        if ($doc->file_path) {
            Storage::disk('local')->delete($doc->file_path);
        }
        DB::table('acc_documents')->where('id', $id)->delete();
        Log::info('Xóa tài liệu kế toán', ['title' => $doc->title, 'by' => Auth::id()]);
        \App\Models\ActivityLog::record('du_lieu_ke_toan', 'xoa', 'Xóa tài liệu "' . $doc->title . '"');

        return redirect()->route('accounting.index', ['tab' => 'tai-lieu'])->with('success', 'Đã xóa tài liệu "' . $doc->title . '".');
    }

    public function download(int $id)
    {
        $doc = DB::table('acc_documents')->find($id) ?? abort(404);
        abort_unless($doc->file_path && Storage::disk('local')->exists($doc->file_path), 404, 'Không còn file gốc.');

        return Storage::disk('local')->download($doc->file_path, Str::slug($doc->title) . '.' . pathinfo($doc->file_path, PATHINFO_EXTENSION));
    }

    private function validateDocument(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', array_keys(self::DOC_TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'parties' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:50'],
            'signed_on' => ['nullable', 'date'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'value' => ['nullable', 'string', 'max:30'],
            'summary' => ['required', 'string', 'max:60000'],
            'file' => [$creating ? 'nullable' : 'nullable', 'file', 'mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png', 'max:30720'],
        ], [
            'title.required' => 'Nhập tên tài liệu.',
            'summary.required' => 'Nhập tóm tắt / số liệu chính để trợ lý tra cứu được.',
            'valid_to.after_or_equal' => 'Ngày hết hiệu lực phải sau ngày bắt đầu.',
            'file.max' => 'File tối đa 30 MB.',
        ]);
        $value = preg_replace('/[^\d]/', '', (string) ($data['value'] ?? ''));
        unset($data['file']);
        $data['value'] = $value === '' ? null : (int) $value;

        return $data;
    }

    private function storeFile(Request $request): ?string
    {
        if (!$request->hasFile('file')) {
            return null;
        }
        $file = $request->file('file');
        $name = now()->format('Ymd-His') . '-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . strtolower($file->getClientOriginalExtension());

        return $file->storeAs('ke-toan/tai-lieu', $name, 'local');
    }
}
