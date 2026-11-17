<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Nhập sổ sách kế toán từ file Excel xuất từ MISA (và mẫu tự lập năm 2023) vào các bảng acc_*.
 *
 * Tự nhận loại sổ của từng sheet:
 *  - Sổ nhật ký chung (NKC)           → acc_journal_entries
 *  - Sổ quỹ tiền mặt (111)            → acc_cash_entries (book TIEN_MAT)
 *  - Sổ tiền gửi ngân hàng (112)      → acc_cash_entries (book = tên ngân hàng: HDB / VCB / AGRI…)
 *  - Tổng hợp công nợ phải thu / trả  → acc_party_balances
 *  - Lương thực trả (sheet theo năm)  → acc_salaries
 *
 * Kỳ của sổ đọc ở tiêu đề ("Năm 2024", "Từ ngày 01/01/2025 đến ngày 30/06/2026"). Chỉ lấy dòng trong kỳ đó, và
 * XÓA dữ liệu cũ của cùng sổ trong kỳ đó trước khi ghi: nhập lại file nhiều lần không bị trùng, file xuất lại
 * (đã sửa) thay cho bản cũ. File lẫn dữ liệu năm khác (vd CHI TIẾT 2024 có dòng 2025, 2026) chỉ lấy đúng năm tiêu đề.
 */
class MisaImporter
{
    /** @var array<int, array{sheet:string, book:string, rows:int, from:?string, to:?string, note:?string}> */
    private array $report = [];

    /** Kỳ chung của cả file (đọc ở sheet có tiêu đề kỳ, vd NKC) cho sheet không ghi kỳ (sổ quỹ, công nợ của MISA) */
    private array $filePeriod = [null, null];

    public function import(string $path, ?int $userId = null, ?string $displayName = null): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("Không thấy file: {$path}");
        }
        $this->report = [];
        $fileName = $displayName ?? basename($path);

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $sheets = [];
        $this->filePeriod = [null, null];
        foreach ($book->getWorksheetIterator() as $sheet) {
            $sheets[] = [$sheet->getTitle(), $sheet->toArray(null, true, false, false)];
            if (!$this->filePeriod[0]) {
                $this->filePeriod = $this->period(end($sheets)[1]);
            }
        }

        foreach ($sheets as [$title, $rows]) {
            $kind = $this->detect($title, $rows);
            if (!$kind) {
                $this->report[] = ['sheet' => $title, 'book' => '-', 'rows' => 0, 'from' => null, 'to' => null, 'note' => 'Không nhận ra loại sổ, bỏ qua'];
                continue;
            }
            DB::transaction(function () use ($kind, $rows, $title, $fileName, $userId) {
                match ($kind) {
                    'nkc' => $this->importJournal($rows, $title, $fileName, $userId),
                    'cash', 'bank' => $this->importCash($kind, $rows, $title, $fileName, $userId),
                    'receivable', 'payable' => $this->importParties($kind, $rows, $title, $fileName, $userId),
                    'salary' => $this->importSalary($rows, $title, $fileName, $userId),
                };
            });
        }
        $book->disconnectWorksheets();

        return $this->report;
    }

    // ------------------------------------------------------------------
    // Nhận loại sổ
    // ------------------------------------------------------------------

    private function detect(string $title, array $rows): ?string
    {
        $head = $this->norm(implode(' ', array_map(fn ($r) => implode(' ', array_map('strval', array_filter($r, fn ($c) => $c !== null))), array_slice($rows, 0, 10))));
        $t = $this->norm($title);

        if (str_contains($head, 'nhat ky chung')) {
            return 'nkc';
        }
        if (str_contains($head, 'phai thu') || in_array($t, ['phai thu'], true)) {
            return 'receivable';
        }
        if (str_contains($head, 'phai tra') || in_array($t, ['phai tra'], true)) {
            return 'payable';
        }
        if (str_contains($head, 'bao cao tong hop cong no')) {
            return str_contains($t, 'tra') ? 'payable' : 'receivable';
        }
        if (str_contains($head, 'so tien gui ngan hang')) {
            return 'bank';
        }
        if (str_contains($head, 'so chi tiet')) {
            // Mẫu 2023: dòng "SỐ HIỆU" 111 = tiền mặt, 1121x = ngân hàng
            if (preg_match('/so hieu\s+(\d+)/', $head, $m)) {
                return str_starts_with($m[1], '112') ? 'bank' : 'cash';
            }
            return str_contains($head, '112') && !str_contains($head, 'tai khoan: 111') ? 'bank' : 'cash';
        }
        if (preg_match('/^(19|20)\d{2}$/', trim($title)) && str_contains($head, 'thang') && str_contains($head, 'ct') && str_contains($head, 'xq')) {
            return 'salary';
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Sổ nhật ký chung
    // ------------------------------------------------------------------

    private function importJournal(array $rows, string $sheet, string $file, ?int $userId): void
    {
        [$from, $to] = $this->sheetPeriod($rows);
        $h = $this->headerRow($rows, ['dien giai']);
        $cols = $this->columns($rows[$h], $rows[$h + 1] ?? []);

        $legacy = !isset($cols['tk doi ung']);   // mẫu 2023: Số | Ngày | Diễn giải | Nợ | Có | Số tiền | Khách hàng
        $out = [];
        foreach (array_slice($rows, $h + 1) as $r) {
            if ($legacy) {
                $date = $this->date($r[2] ?? null);
                $debit = trim((string) ($r[4] ?? ''));
                $credit = trim((string) ($r[5] ?? ''));
                $amount = $this->num($r[6] ?? null);
                $rec = ['doc_no' => $this->str($r[1] ?? null), 'doc_date' => $date, 'invoice_no' => null,
                    'description' => $this->str($r[3] ?? null), 'party_code' => $this->str($r[7] ?? null)];
            } else {
                $date = $this->date($this->pick($r, $cols, ['ngay hach toan', 'ngay chung tu']));
                $debit = trim((string) $this->pick($r, $cols, ['tai khoan']));
                $credit = trim((string) $this->pick($r, $cols, ['tk doi ung']));
                $amount = $this->num($this->pick($r, $cols, ['phat sinh', 'so tien']));
                $rec = ['doc_no' => $this->str($this->pick($r, $cols, ['so chung tu'])), 'doc_date' => $this->date($this->pick($r, $cols, ['ngay chung tu'])),
                    'invoice_no' => $this->str($this->pick($r, $cols, ['so hoa don'])), 'description' => $this->str($this->pick($r, $cols, ['dien giai'])), 'party_code' => null];
            }
            if (!$date || $debit === '' || $credit === '' || !$amount || !$this->inPeriod($date, $from, $to)) {
                continue;
            }
            $out[] = $rec + ['posted_on' => $date, 'debit_account' => mb_substr($debit, 0, 20), 'credit_account' => mb_substr($credit, 0, 20), 'amount' => $amount];
        }
        [$from, $to] = $this->resolvePeriod($from, $to, array_column($out, 'posted_on'));

        $importId = $this->logImport($file, 'nkc', $sheet, $from, $to, count($out), $userId);
        DB::table('acc_journal_entries')->whereBetween('posted_on', [$from, $to])->delete();
        foreach (array_chunk($out, 500) as $chunk) {
            DB::table('acc_journal_entries')->insert(array_map(fn ($x) => $x + ['import_id' => $importId], $chunk));
        }
        $this->report[] = ['sheet' => $sheet, 'book' => 'Nhật ký chung', 'rows' => count($out), 'from' => $from, 'to' => $to, 'note' => null];
    }

    // ------------------------------------------------------------------
    // Sổ quỹ tiền mặt / sổ tiền gửi ngân hàng
    // ------------------------------------------------------------------

    private function importCash(string $kind, array $rows, string $sheet, string $file, ?int $userId): void
    {
        [$from, $to] = $this->sheetPeriod($rows);
        $book = $kind === 'cash' ? 'TIEN_MAT' : $this->bankName($sheet, $rows);
        $h = $this->headerRow($rows, ['dien giai']);
        $cols = $this->columns($rows[$h], $rows[$h + 1] ?? []);
        $legacy = isset($cols['so hieu tk']);   // mẫu 2023: Ngày ghi sổ | Số | Ngày | Diễn giải | Số hiệu TK | Nợ | Có

        $out = [];
        foreach (array_slice($rows, $h + 1) as $r) {
            $desc = $this->str($legacy ? ($r[3] ?? null) : $this->pick($r, $cols, ['dien giai']));
            if ($desc !== null && $this->norm($desc) === 'so du dau ky') {
                $bal = $legacy ? null : $this->num($this->pick($r, $cols, ['ton', 'du no']));
                $out[] = ['posted_on' => null, 'doc_no' => null, 'description' => 'Số dư đầu kỳ', 'contra_account' => null, 'receipt' => 0, 'payment' => 0,
                    'balance' => $bal, 'is_opening' => true, 'party_code' => null, 'segment' => null, 'car' => null, 'item_name' => null];
                continue;
            }
            if ($legacy) {
                $date = $this->date($r[0] ?? null) ?? $this->date($r[2] ?? null);
                $rec = ['doc_no' => $this->str($r[1] ?? null), 'contra_account' => $this->str($r[4] ?? null),
                    'receipt' => $this->num($r[5] ?? null), 'payment' => $this->num($r[6] ?? null), 'balance' => null,
                    'party_code' => null, 'segment' => null, 'car' => null, 'item_name' => null];
            } else {
                $date = $this->date($this->pick($r, $cols, ['ngay hach toan', 'ngay chung tu']));
                $rec = ['doc_no' => $this->str($this->pick($r, $cols, ['so chung tu'])), 'contra_account' => $this->str($this->pick($r, $cols, ['tk doi ung'])),
                    'receipt' => $this->num($this->pick($r, $cols, ['thu', 'phat sinh no'])), 'payment' => $this->num($this->pick($r, $cols, ['chi', 'phat sinh co'])),
                    'balance' => $this->num($this->pick($r, $cols, ['ton', 'du no']), true),
                    'party_code' => $this->str($this->pick($r, $cols, ['ma doi tuong'])), 'segment' => $this->str($this->pick($r, $cols, ['ma khoan muc chi phi'])),
                    'car' => $this->str($this->pick($r, $cols, ['ma cong trinh'])), 'item_name' => $this->str($this->pick($r, $cols, ['ten muc thu/chi']))];
            }
            if (!$date || (!$rec['receipt'] && !$rec['payment']) || !$this->inPeriod($date, $from, $to)) {
                continue;
            }
            $out[] = $rec + ['posted_on' => $date, 'description' => $desc, 'is_opening' => false];
        }
        [$from, $to] = $this->resolvePeriod($from, $to, array_filter(array_column($out, 'posted_on')));

        $importId = $this->logImport($file, $kind, $sheet, $from, $to, count($out), $userId);
        // Xóa dòng cũ của sổ này trong kỳ + dòng số dư đầu kỳ của lần nhập cùng kỳ trước đó
        $oldOpening = DB::table('acc_imports')->whereIn('book', ['cash', 'bank'])->where('period_from', $from)->where('id', '!=', $importId)->pluck('id');
        DB::table('acc_cash_entries')->where('book', $book)
            ->where(fn ($q) => $q->whereBetween('posted_on', [$from, $to])->orWhere(fn ($q) => $q->where('is_opening', true)->whereIn('import_id', $oldOpening)))
            ->delete();
        foreach (array_chunk($out, 500) as $chunk) {
            DB::table('acc_cash_entries')->insert(array_map(fn ($x) => $x + ['book' => $book, 'import_id' => $importId], $chunk));
        }
        $this->report[] = ['sheet' => $sheet, 'book' => $book === 'TIEN_MAT' ? 'Sổ quỹ tiền mặt' : "Sổ ngân hàng {$book}", 'rows' => count($out), 'from' => $from, 'to' => $to, 'note' => null];
    }

    private function bankName(string $sheet, array $rows): string
    {
        $t = strtoupper(Str::ascii(trim($sheet)));
        foreach (['HDB', 'VCB', 'AGRI', 'BIDV', 'ACB', 'TCB', 'VTB', 'MB'] as $b) {
            if (str_contains($t, $b)) {
                return $b;
            }
        }

        return mb_substr($t, 0, 20);
    }

    // ------------------------------------------------------------------
    // Tổng hợp công nợ phải thu / phải trả
    // ------------------------------------------------------------------

    private function importParties(string $kind, array $rows, string $sheet, string $file, ?int $userId): void
    {
        [$from, $to] = $this->sheetPeriod($rows);
        if (!$from) {
            throw new RuntimeException("Sheet {$sheet}: không đọc được kỳ (Năm … / Từ ngày … đến ngày …) ở tiêu đề");
        }
        $h = $this->headerRow($rows, ['so du dau']);
        $out = [];
        foreach (array_slice($rows, $h + 2) as $r) {
            $code = $this->str($r[0] ?? null);
            $name = $this->str($r[1] ?? null);
            if (!$code || !$name) {
                continue;
            }
            // Mẫu MISA có thêm cột "TK công nợ" ở vị trí 3; mẫu 2023 thì không
            $o = is_numeric($r[2] ?? null) && strlen((string) (int) $r[2]) === 3 && count(array_filter($r, fn ($c) => $c !== null)) >= 9 ? 3 : 2;
            $vals = array_map(fn ($i) => $this->num($r[$o + $i] ?? null), range(0, 5));
            if (!array_filter($vals)) {
                continue;
            }
            $out[] = ['kind' => $kind, 'period_from' => $from, 'period_to' => $to, 'code' => mb_substr($code, 0, 50), 'name' => mb_substr($name, 0, 255),
                'opening_debit' => $vals[0], 'opening_credit' => $vals[1], 'debit' => $vals[2], 'credit' => $vals[3], 'closing_debit' => $vals[4], 'closing_credit' => $vals[5]];
        }
        $importId = $this->logImport($file, $kind, $sheet, $from, $to, count($out), $userId);
        DB::table('acc_party_balances')->where('kind', $kind)->where('period_from', $from)->where('period_to', $to)->delete();
        foreach (array_chunk($out, 500) as $chunk) {
            DB::table('acc_party_balances')->insert(array_map(fn ($x) => $x + ['import_id' => $importId], $chunk));
        }
        $this->report[] = ['sheet' => $sheet, 'book' => $kind === 'receivable' ? 'Công nợ phải thu' : 'Công nợ phải trả', 'rows' => count($out), 'from' => $from, 'to' => $to, 'note' => null];
    }

    // ------------------------------------------------------------------
    // Lương thực trả (sheet = năm; cột Tháng | CT | XQ)
    // ------------------------------------------------------------------

    private function importSalary(array $rows, string $sheet, string $file, ?int $userId): void
    {
        $year = (int) trim($sheet);
        $out = [];
        foreach ($rows as $r) {
            $label = $this->norm((string) ($r[0] ?? ''));
            $ct = $this->num($r[1] ?? null);
            $xq = $this->num($r[2] ?? null);
            if (preg_match('/^\d{1,2}$/', $label) && (int) $label >= 1 && (int) $label <= 12) {
                $kind = 'luong';
                $month = (int) $label;
            } elseif (str_contains($label, 'thuong le')) {
                [$kind, $month] = ['thuong_le', null];
            } elseif (str_contains($label, 'thuong tet')) {
                [$kind, $month] = ['thuong_tet', null];
            } else {
                continue;
            }
            if (!$ct && !$xq) {
                continue;
            }
            $out[] = ['year' => $year, 'month' => $month, 'kind' => $kind, 'ct' => $ct, 'xq' => $xq];
        }
        $importId = $this->logImport($file, 'salary', $sheet, "{$year}-01-01", "{$year}-12-31", count($out), $userId);
        DB::table('acc_salaries')->where('year', $year)->delete();
        if ($out) {
            DB::table('acc_salaries')->insert(array_map(fn ($x) => $x + ['import_id' => $importId], $out));
        }
        $this->report[] = ['sheet' => $sheet, 'book' => 'Lương thực trả', 'rows' => count($out), 'from' => "{$year}-01-01", 'to' => "{$year}-12-31", 'note' => null];
    }

    // ------------------------------------------------------------------
    // Hỗ trợ
    // ------------------------------------------------------------------

    /** Kỳ ở tiêu đề: "Từ ngày dd/mm/yyyy đến ngày dd/mm/yyyy" hoặc "Năm yyyy" */
    private function sheetPeriod(array $rows): array
    {
        $p = $this->period($rows);

        return $p[0] ? $p : $this->filePeriod;
    }

    private function period(array $rows): array
    {
        foreach (array_slice($rows, 0, 8) as $r) {
            $line = $this->norm(implode(' ', array_map('strval', array_filter($r, fn ($c) => $c !== null))));
            if (preg_match('/tu ngay (\d{1,2}\/\d{1,2}\/\d{4}) den ngay (\d{1,2}\/\d{1,2}\/\d{4})/', $line, $m)) {
                return [Carbon::createFromFormat('d/m/Y', $m[1])->toDateString(), Carbon::createFromFormat('d/m/Y', $m[2])->toDateString()];
            }
            if (preg_match('/^nam (\d{4})\b/', trim($line), $m) || preg_match('/\bnam (\d{4})$/', trim($line), $m)) {
                return ["{$m[1]}-01-01", "{$m[1]}-12-31"];
            }
        }

        return [null, null];
    }

    /** Không đọc được kỳ ở tiêu đề thì lấy theo ngày nhỏ / lớn nhất trong sổ */
    private function resolvePeriod(?string $from, ?string $to, array $dates): array
    {
        if ($from && $to) {
            return [$from, $to];
        }
        if (!$dates) {
            throw new RuntimeException('Không xác định được kỳ của sổ (tiêu đề không có năm / khoảng ngày và sổ không có dòng nào).');
        }

        return [min($dates), max($dates)];
    }

    private function inPeriod(string $date, ?string $from, ?string $to): bool
    {
        return (!$from || $date >= $from) && (!$to || $date <= $to);
    }

    private function headerRow(array $rows, array $needles): int
    {
        foreach (array_slice($rows, 0, 15, true) as $i => $r) {
            $line = $this->norm(implode(' | ', array_map('strval', array_filter($r, fn ($c) => $c !== null))));
            foreach ($needles as $n) {
                if (str_contains($line, $n)) {
                    return $i;
                }
            }
        }
        throw new RuntimeException('Không tìm thấy dòng tiêu đề cột (Diễn giải …).');
    }

    /** Tên cột (đã bỏ dấu) → vị trí. Dòng tiêu đề phụ (Nợ / Có, Số / Ngày) gắn vào cột trống phía trên. */
    private function columns(array $header, array $sub): array
    {
        $cols = [];
        foreach ($header as $i => $v) {
            $n = $this->norm((string) $v);
            if ($n !== '' && !isset($cols[$n])) {
                $cols[$n] = $i;
            }
        }

        return $cols;
    }

    private function pick(array $row, array $cols, array $names)
    {
        foreach ($names as $n) {
            if (isset($cols[$n]) && ($row[$cols[$n]] ?? null) !== null && $row[$cols[$n]] !== '') {
                return $row[$cols[$n]];
            }
        }

        return null;
    }

    private function date($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        if (is_numeric($v) && $v > 20000 && $v < 80000) {
            return ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
        }
        $s = trim((string) $v);
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return null;
    }

    private function num($v, bool $nullable = false): ?float
    {
        if ($v === null || $v === '') {
            return $nullable ? null : 0.0;
        }
        if (is_numeric($v)) {
            return round((float) $v, 2);
        }
        $s = str_replace([',', ' '], ['', ''], (string) $v);

        return is_numeric($s) ? round((float) $s, 2) : ($nullable ? null : 0.0);
    }

    private function str($v): ?string
    {
        $s = trim((string) ($v ?? ''));

        return $s === '' ? null : $s;
    }

    private function norm(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower(Str::ascii($s))));
    }

    private function logImport(string $file, string $book, string $sheet, ?string $from, ?string $to, int $rows, ?int $userId): int
    {
        return DB::table('acc_imports')->insertGetId(['file_name' => mb_substr($file, 0, 255), 'book' => $book, 'sheet' => mb_substr($sheet, 0, 100),
            'period_from' => $from, 'period_to' => $to, 'rows' => $rows, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]);
    }
}
