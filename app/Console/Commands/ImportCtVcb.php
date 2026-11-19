<?php

namespace App\Console\Commands;

use App\Models\Accounting\AccMemo;
use App\Models\Accounting\CtPartner;
use App\Models\Accounting\CtPayment;
use App\Models\Accounting\CtReceivable;
use App\Models\Accounting\VcbEntry;
use App\Models\Accounting\VcbRecurring;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Nhập MỘT LẦN dữ liệu cũ từ Excel của kế toán:
 *  --ct  : "CÔNG NỢ PHẢI THU CT 2025+2026 (Tu).xls"  -> sheet "CT 2026" vào Công nợ CT (bệnh viện, kỳ, thanh toán)
 *  --vcb : "Cac khoan DT ck VCB Mr Loc.xlsx"          -> các sheet tháng (04.25 … 11.26) vào Sổ VCB chú Lộc (dòng "Từ file cũ"),
 *          sheet "so lieu cu" thành ghi nhớ; khoản trừ cố định (căn A44, xe VF7) thành khoản cố định hằng tháng.
 * Thanh toán CT nhập từ file KHÔNG sinh dòng VCB (sổ VCB cũ đã có các dòng đó).
 * Kế toán chốt số dư VCB trên web sau khi nhập.
 */
class ImportCtVcb extends Command
{
    protected $signature = 'accounting:import-ct-vcb {--ct= : File công nợ CT (.xls)} {--vcb= : File VCB chú Lộc (.xlsx)} {--replace : Xóa dữ liệu CT / VCB đã nhập trước đó}';

    protected $description = 'Nhập dữ liệu cũ Công nợ CT 2026 và Sổ VCB chú Lộc từ file Excel của kế toán';

    private const PARTNERS = ['AC' => 'Á Châu', 'BL' => 'Bảo Lộc', 'CM' => 'Cà Mau', 'GC' => 'Gò Công', 'LG' => 'Lagi-YSG'];

    public function handle(): int
    {
        $ct = $this->option('ct');
        $vcb = $this->option('vcb');
        if (!$ct && !$vcb) {
            $this->error('Cần --ct=đường_dẫn và / hoặc --vcb=đường_dẫn.');

            return self::FAILURE;
        }
        foreach (array_filter([$ct, $vcb]) as $f) {
            if (!is_file($f)) {
                $this->error("Không thấy file: {$f}");

                return self::FAILURE;
            }
        }
        @ini_set('memory_limit', '1024M');

        try {
            DB::transaction(function () use ($ct, $vcb) {
                if ($ct) {
                    $this->importCt($ct);
                }
                if ($vcb) {
                    $this->importVcb($vcb);
                }
            });
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ CT

    private function importCt(string $file): void
    {
        if (CtReceivable::exists()) {
            if (!$this->option('replace')) {
                throw new \RuntimeException('Công nợ CT đã có dữ liệu. Thêm --replace để xóa và nhập lại.');
            }
            VcbEntry::where('source', 'ct')->delete();
            CtPayment::query()->delete();
            CtReceivable::query()->delete();
            AccMemo::where('scope', 'ct')->delete();
        }

        $sort = 0;
        foreach (self::PARTNERS as $code => $name) {
            CtPartner::updateOrCreate(['code' => $code], ['name' => $name, 'active' => true, 'sort' => ++$sort]);
        }
        $partners = CtPartner::pluck('id', 'code');

        $rows = $this->sheetRows($file, 'CT 2026');
        $n = $pays = 0;
        $outstanding = 0;
        foreach ($rows as $r) {
            $code = strtoupper(trim((string) ($r['C'] ?? '')));
            $month = (int) ($r['B'] ?? 0);
            if (!$code || !$month || !isset($partners[$code])) {
                continue;
            }
            $qtyRaw = trim((string) ($r['E'] ?? ''));
            $isBhyt = stripos($qtyRaw, 'bhyt') !== false;
            $rec = CtReceivable::create([
                'partner_id' => $partners[$code],
                'year' => 2026,
                'month' => $month,
                'kind' => $isBhyt ? 'bhyt' : 'thuong',
                'label' => $isBhyt ? (trim(preg_replace('/bhyt/i', '', $qtyRaw)) ?: null) : null,
                'quantity' => is_numeric($qtyRaw) ? (int) $qtyRaw : null,
                'revenue' => $this->money($r['F'] ?? 0),
                'cost' => $this->money($r['G'] ?? 0),
                'hospital_share' => $this->money($r['I'] ?? 0),
                'medicen_share' => $this->money($r['J'] ?? 0),
                'hospital_collect' => $this->money($r['K'] ?? 0),
                'medicen_collect' => $this->money($r['L'] ?? 0),
                'note' => trim((string) ($r['T'] ?? '')) ?: null,
            ]);
            $n++;
            $paid = 0;
            foreach ([['M', 'N', 'O'], ['P', 'Q', 'R']] as [$dc, $mc, $ac]) {
                $amount = $this->money($r[$ac] ?? 0);
                if (!$amount) {
                    continue;
                }
                $date = $this->date($r[$dc] ?? null) ?? Carbon::create(2026, $month, 1)->addMonth();
                CtPayment::create([
                    'receivable_id' => $rec->id,
                    'paid_on' => $date->toDateString(),
                    'method' => strtoupper(trim((string) ($r[$mc] ?? 'HDB'))) ?: 'HDB',
                    'amount' => $amount,
                    'note' => 'Nhập từ file Excel',
                ]);
                $paid += $amount;
                $pays++;
            }
            $outstanding += $rec->medicen_collect - $paid;
        }
        $this->info("Công nợ CT 2026: {$n} kỳ, {$pays} lần thanh toán, còn phải thu " . number_format($outstanding, 0, ',', '.'));

        $memos = [
            ['Thiên Phú — công nợ CT năm 2023', '2023', 1211585460, 'Bệnh viện không còn hợp tác. Số theo sheet "Tong CT 2023" (còn phải thu).'],
            ['Thiên Phú — công nợ CT năm 2024', '2024', 557267365, 'Bệnh viện không còn hợp tác. Số theo sheet "Tong CT 2024" (còn phải thu).'],
            ['Còn phải thu phát sinh 2025', '2025', null, 'Trong file Excel ô này bị lỗi #REF!, kế toán bổ sung số tiền và đơn vị.'],
        ];
        foreach ($memos as $i => [$title, $period, $amount, $note]) {
            AccMemo::create(['scope' => 'ct', 'title' => $title, 'period' => $period, 'amount' => $amount, 'note' => $note, 'sort' => $i + 1]);
        }
    }

    // ------------------------------------------------------------------ VCB

    private function importVcb(string $file): void
    {
        if (VcbEntry::where('source', 'import')->exists()) {
            if (!$this->option('replace')) {
                throw new \RuntimeException('Sổ VCB đã có dữ liệu nhập từ file. Thêm --replace để xóa và nhập lại.');
            }
            VcbEntry::whereIn('source', ['import', 'recurring'])->delete();
            AccMemo::where('scope', 'vcb')->delete();
        }

        $a44 = VcbRecurring::firstOrCreate(['description' => 'Trừ gốc lãi VCB căn A44'], ['chi' => 35000000, 'day' => 26, 'active' => true]);
        $vf7 = VcbRecurring::firstOrCreate(['description' => 'Trừ gốc lãi VP Bank (xe VF7)'], ['chi' => 13700000, 'day' => 15, 'active' => true]);

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $book = $reader->load($file);
        $count = 0;
        $lastNotes = [];
        foreach ($book->getAllSheets() as $sheet) {
            $title = trim($sheet->getTitle());
            if (!preg_match('/^(\d{1,2})\.(\d{2})$/', $title, $mm)) {
                continue;
            }
            $month = (int) $mm[1];
            $year = 2000 + (int) $mm[2];
            $rows = $sheet->toArray(null, true, false, true);
            $afterTotal = false;
            $notes = [];
            foreach ($rows as $r) {
                $desc = trim(preg_replace('/\s+/u', ' ', (string) ($r['A'] ?? '')));
                if ($desc === '') {
                    continue;
                }
                if (preg_match('/^(TC|Còn lại|Số dư tháng trước|TC TK VCB)/u', $desc)) {
                    $afterTotal = $afterTotal || str_starts_with($desc, 'TC');
                    continue;
                }
                if (preg_match('/^(Bắt đầu theo dõi|Từ \d|Chi tiết thu theo tháng)/u', $desc)) {
                    continue;
                }
                if ($afterTotal) {
                    // Phần ghi chú dưới bảng (khoản mượn, cấn trừ…): lặp lại mỗi tháng, giữ bản của tháng cuối
                    $b = $this->money($r['B'] ?? 0);
                    $c = trim((string) ($r['C'] ?? ''));
                    $notes[] = $desc . ($b ? ': ' . number_format($b, 0, ',', '.') : '') . ($c !== '' && !is_numeric($c) ? ' (' . $c . ')' : '');
                    continue;
                }
                $thu = is_numeric($r['B'] ?? null) || preg_match('/^[\d,.()\-\s]+$/', (string) ($r['B'] ?? '')) ? $this->money($r['B'] ?? 0) : 0;
                $chi = is_numeric($r['C'] ?? null) || preg_match('/^[\d,.()\-\s]+$/', (string) ($r['C'] ?? '')) ? $this->money($r['C'] ?? 0) : 0;
                if (!$thu && !$chi) {
                    continue;
                }
                $rawDate = $r['D'] ?? null;
                $date = $this->dateInMonth($rawDate, $year, $month);
                $rec = null;
                if (stripos($desc, 'căn A44') !== false) {
                    $rec = $a44;
                } elseif (stripos($desc, 'VF7') !== false && stripos($desc, 'VP bank') !== false) {
                    $rec = $vf7;
                }
                if ($rec) {
                    $date = Carbon::create($year, $month, min($rec->day, Carbon::create($year, $month, 1)->daysInMonth));
                }
                $note = null;
                if (!$rec && !$this->dateOk($rawDate, $year, $month)) {
                    $note = 'Ngày trong file: ' . (is_numeric($rawDate) ? ExcelDate::excelToDateTimeObject($rawDate)->format('d/m/Y') : (trim((string) $rawDate) ?: 'không ghi'));
                }
                VcbEntry::create([
                    'entry_date' => $date->toDateString(),
                    'description' => mb_substr($desc, 0, 255),
                    'thu' => max(0, $thu) + max(0, -$chi),
                    'chi' => max(0, $chi) + max(0, -$thu),
                    'source' => 'import',
                    'recurring_id' => $rec?->id,
                    'period' => $rec ? sprintf('%04d-%02d', $year, $month) : null,
                    'note' => $note,
                ]);
                $count++;
            }
            if ($notes) {
                $lastNotes = [$title, $notes];
            }
        }
        $this->info("Sổ VCB chú Lộc: {$count} dòng từ các sheet tháng.");

        // Ghi nhớ số cũ
        $old = $this->sheetRows($file, 'so lieu cu');
        $sumThu = $sumChi = 0;
        $gop = [];
        foreach ($old as $r) {
            $label = trim((string) ($r['B'] ?? ''));
            if ($label === 'TC') {
                $sumThu = $this->money($r['C'] ?? 0);
                $sumChi = $this->money($r['D'] ?? 0);
            }
            if (preg_match('/^CT-.*%$/u', $label)) {
                $gop[] = $label . ': góp ' . number_format($this->money($r['C'] ?? 0), 0, ',', '.') . ', đã CK ' . number_format($this->money($r['D'] ?? 0), 0, ',', '.') . ' (' . trim((string) ($r['F'] ?? '')) . ')';
            }
        }
        AccMemo::create(['scope' => 'vcb', 'sort' => 1, 'title' => 'Số liệu cũ đến 21/04/2025 (sheet "so lieu cu")', 'period' => '2023 – 04/2025',
            'amount' => $sumThu - $sumChi,
            'note' => 'Tổng thu ' . number_format($sumThu, 0, ',', '.') . ' − tổng chi ' . number_format($sumChi, 0, ',', '.')
                . ". Số liệu cũ chưa đối chiếu được, chốt đến đây, sau đó theo dõi từng tháng.\n" . implode("\n", array_map(fn ($g) => 'Góp vốn BS Hồng ' . $g, $gop))]);
        if ($lastNotes) {
            AccMemo::create(['scope' => 'vcb', 'sort' => 2, 'title' => 'Ghi chú cuối sổ (sheet ' . $lastNotes[0] . ')', 'period' => null, 'amount' => null,
                'note' => implode("\n", $lastNotes[1])]);
        }
    }

    // ------------------------------------------------------------------ đọc file

    private function sheetRows(string $file, string $sheet): array
    {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheet]);
        $book = $reader->load($file);
        $ws = $book->getSheetByName($sheet);
        if (!$ws) {
            throw new \RuntimeException("Không thấy sheet \"{$sheet}\" trong file.");
        }

        return $ws->toArray(null, true, false, true);
    }

    private function money($v): int
    {
        if (is_numeric($v)) {
            return (int) round((float) $v);
        }
        $s = trim((string) $v);
        if ($s === '' || $s === '-') {
            return 0;
        }
        $neg = str_starts_with($s, '(') || str_starts_with($s, '-');

        return (int) (($neg ? -1 : 1) * (int) preg_replace('/\D/', '', $s));
    }

    private function date($v): ?Carbon
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($v))->startOfDay();
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', trim((string) $v), $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? Carbon::create((int) $m[3], (int) $m[2], (int) $m[1]) : null;
        }

        return null;
    }

    /** Ngày trong file VCB có lẫn d/m và m/d: chọn cách đọc rơi vào đúng tháng của sheet, không được thì ngày cuối tháng */
    private function dateInMonth($v, int $year, int $month): Carbon
    {
        foreach ($this->dateCandidates($v) as $d) {
            if ($d->year === $year && $d->month === $month) {
                return $d;
            }
        }
        $c = $this->dateCandidates($v);

        return $c ? $c[0] : Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();
    }

    private function dateOk($v, int $year, int $month): bool
    {
        foreach ($this->dateCandidates($v) as $d) {
            if ($d->year === $year && $d->month === $month) {
                return true;
            }
        }

        return false;
    }

    private function dateCandidates($v): array
    {
        $out = [];
        if (is_numeric($v)) {
            $d = Carbon::instance(ExcelDate::excelToDateTimeObject($v))->startOfDay();
            $out[] = $d;
            if ($d->day <= 12) {
                $out[] = Carbon::create($d->year, $d->day, $d->month);
            }
        } elseif (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', (string) $v, $m)) {
            [$a, $b, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if (checkdate($b, $a, $y)) {
                $out[] = Carbon::create($y, $b, $a);
            }
            if (checkdate($a, $b, $y)) {
                $out[] = Carbon::create($y, $a, $b);
            }
        }

        return $out;
    }
}
