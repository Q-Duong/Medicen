<?php

namespace App\Services;

use DateTime;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Đọc file Excel theo mẫu mau-don-hang-nkl.xlsx và trả về mảng
 * [tên input form tạo đơn => giá trị] để điền sẵn bằng old().
 *
 * Ô để trống không được gửi → form giữ lựa chọn mặc định (Trống / Không / Không in).
 *
 * Kết quả: ['data' => [...], 'errors' => [...], 'warnings' => [...]]
 *  - errors: không đọc được / không đúng mẫu → không chuyển sang trang tạo đơn
 *  - warnings: thiếu trường bắt buộc, giá trị lạ → vẫn điền sẵn, hiển thị để người dùng sửa tay
 */
class OrderExcelReader
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('order_import');
    }

    public function read(string $path): array
    {
        $book  = IOFactory::load($path);
        $sheet = $book->getSheetByName($this->cfg['sheet']) ?? $book->getSheet(0);

        $fields     = $this->cfg['fields'];
        $labelIndex = [];
        foreach ($fields as $key => $f) {
            $labelIndex[$this->norm($f['label'])] = $key;
        }

        $cells        = [];
        $templateCode = null;
        $keyCol   = $this->cfg['key_column'];
        $labelCol = $this->cfg['label_column'];
        $valueCol = $this->cfg['value_column'];

        for ($r = 1, $last = $sheet->getHighestDataRow(); $r <= $last; $r++) {
            $key = trim((string) $sheet->getCell($keyCol.$r)->getValue());

            if ($key === '_template') {
                $templateCode = trim((string) $sheet->getCell($valueCol.$r)->getValue());
                continue;
            }

            // Ưu tiên mã trường; nếu cột A bị xoá thì thử khớp theo nhãn cột B
            if (! isset($fields[$key])) {
                $label = $this->norm(rtrim((string) $sheet->getCell($labelCol.$r)->getValue(), ' *'));
                $key   = $labelIndex[$label] ?? null;
            }

            if ($key && ! isset($cells[$key])) {
                $cells[$key] = $sheet->getCell($valueCol.$r);
            }
        }

        if (! $cells) {
            return [
                'data'     => [],
                'errors'   => ['File không theo mẫu đơn hàng NKL (không tìm thấy cột Mã trường). Tải file mẫu, điền lại rồi tải lên.'],
                'warnings' => [],
            ];
        }

        $data = [];
        $warnings = [];

        if ($templateCode && $templateCode !== $this->cfg['template_code']) {
            $warnings[] = "File dùng mẫu {$templateCode}, mẫu hiện tại là {$this->cfg['template_code']}. Kiểm tra kỹ các trường.";
        }

        foreach ($fields as $key => $f) {
            if (! isset($cells[$key])) {
                $value = null;
            } elseif ($f['type'] === 'discount') {
                $value = $this->discount($cells[$key], $warnings);
            } else {
                $value = $this->convert($cells[$key], $f, $warnings);
            }

            if ($value === null || $value === '') {
                if (! empty($f['required'])) {
                    $warnings[] = "Thiếu “{$f['label']}”.";
                }
                continue;
            }

            $data[$key] = $value;
        }

        return ['data' => $data, 'errors' => [], 'warnings' => $warnings];
    }

    private function convert(Cell $cell, array $f, array &$warnings): mixed
    {
        $v = $this->cellValue($cell);
        if ($v === null || $v === '') {
            return null;
        }

        $label = $f['label'];

        switch ($f['type']) {
            case 'date':
                if (is_numeric($v)) {
                    return ExcelDate::excelToDateTimeObject($v)->format('Y-m-d');
                }
                foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'j/n/Y'] as $fmt) {
                    $d = DateTime::createFromFormat('!'.$fmt, $v);
                    if ($d && $d->format($fmt) === $v) {
                        return $d->format('Y-m-d');
                    }
                }
                $warnings[] = "“{$label}” không đọc được ngày: {$v}. Dùng dạng 25/11/2026.";
                return null;

            case 'time':
                if (is_numeric($v)) {
                    return ExcelDate::excelToDateTimeObject($v)->format('H:i');
                }
                if (preg_match('/^(\d{1,2})\s*[:hHgG]\s*(\d{1,2})?/u', $v, $m)) {
                    return sprintf('%02d:%02d', $m[1], $m[2] ?? 0);
                }
                $warnings[] = "“{$label}” không đọc được giờ: {$v}.";
                return null;

            case 'int':
                $n = is_numeric($v) ? (int) round($v) : (int) preg_replace('/\D/', '', $v);
                return $n > 0 ? $n : null;

            case 'money':
                $n = is_numeric($v) ? (float) $v : (float) preg_replace('/\D/', '', $v);
                // formatPrice() trong OrderController sẽ bỏ dấu chấm khi lưu
                return number_format($n, 0, ',', '.');

            case 'percent':
                $n = $this->decimal($v);
                if ($n === null) return null;
                if ($this->isPercentCell($cell, $v)) {
                    $n *= 100; // gõ "8%" → Excel lưu 0.08
                }
                return $this->num($n);

            case 'number':
                $n = $this->decimal($v);
                return $n === null ? null : $this->num($n);

            case 'phone':
                $s = preg_replace('/[^\d+]/', '', is_float($v) ? number_format($v, 0, '', '') : (string) $v);
                if (strlen($s) === 9 && $s[0] !== '0') {
                    $s = '0'.$s; // Excel làm mất số 0 đầu
                }
                return $s;

            case 'email':
                $s = mb_strtolower((string) $v);
                if (! filter_var($s, FILTER_VALIDATE_EMAIL)) {
                    $warnings[] = "“{$label}” có vẻ chưa đúng: {$s}.";
                }
                return $s;

            case 'upper':
                return mb_strtoupper((string) $v, 'UTF-8');

            case 'enum':
                // options: 'Nhãn trong Excel' => 'value trên form'
                $options = $this->cfg['options'][$f['options']] ?? [];
                $needle  = $this->norm((string) $v);
                foreach ($options as $optLabel => $optValue) {
                    if ($needle === $this->norm((string) $optLabel) || $needle === $this->norm((string) $optValue)) {
                        return (string) $optValue;
                    }
                }
                $warnings[] = "“{$label}”: “{$v}” không có trong danh sách, chọn lại trên form.";
                return null;

            default:
                return is_float($v) ? $this->num($v) : (string) $v;
        }
    }

    /**
     * Chiết khấu (1 ô):
     *  - "1000/ca", chữ khác số thuần → giữ nguyên
     *  - có dấu % hoặc số < threshold (100) → phần trăm, VD 5 → "5%"
     *  - số ≥ threshold → số tiền, VD 10000 → "10000"
     *  - 0, "Không" → bỏ trống
     */
    private function discount(Cell $cell, array &$warnings): ?string
    {
        $fmt = $this->cfg['discount_format'];
        $v   = $this->cellValue($cell);

        if ($v === null || $v === '') {
            return null;
        }

        $hasPercent = $this->isPercentCell($cell, $v);

        if (is_string($v)) {
            if (in_array(mb_strtolower($v, 'UTF-8'), ['không', 'khong', '0'], true)) {
                return null;
            }
            // Không phải số thuần (VD: 1000/ca) → giữ nguyên
            if (! preg_match('/^[\d\s.,]+%?$/u', $v)) {
                return $v;
            }
            $s = trim(str_replace('%', '', $v));
            $n = preg_match('/^\d{1,3}([.,]\d{3})+$/', $s)
                ? (float) preg_replace('/\D/', '', $s)          // 10.000 / 10,000
                : (float) str_replace(',', '.', $s);             // 5,5
        } else {
            $n = (float) $v;
            if ($hasPercent) {
                $n *= 100; // ô định dạng %: 5% lưu là 0.05
            }
        }

        if ($n <= 0) {
            return null;
        }

        if ($hasPercent || $n < $fmt['threshold']) {
            if ($n > 100) {
                $warnings[] = "Chiết khấu {$this->num($n)}% lớn hơn 100%, kiểm tra lại.";
            }
            return str_replace('{n}', $this->num($n), $fmt['percent']);
        }

        return str_replace('{n}', $this->num(round($n)), $fmt['amount']);
    }

    /** Giá trị ô; ô công thức lấy kết quả đã tính */
    private function cellValue(Cell $cell): mixed
    {
        if ($cell->isFormula()) {
            try {
                $v = $cell->getCalculatedValue();
            } catch (\Throwable) {
                $v = $cell->getOldCalculatedValue();
            }
        } else {
            $v = $cell->getValue();
        }

        if ($v instanceof RichText) {
            $v = $v->getPlainText();
        }
        if (is_string($v)) {
            $v = trim(preg_replace('/\s+/u', ' ', $v));
        }

        return $v;
    }

    /** Ô đang định dạng % trong Excel, hoặc là chữ có dấu % */
    private function isPercentCell(Cell $cell, mixed $v): bool
    {
        if (is_string($v)) {
            return str_contains($v, '%');
        }
        return str_contains($cell->getStyle()->getNumberFormat()->getFormatCode(), '%');
    }

    /** "5,5%" / "5.5" / 5.5 → 5.5 */
    private function decimal(mixed $v): ?float
    {
        if (is_numeric($v)) {
            return (float) $v;
        }
        $s = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', (string) $v));
        return is_numeric($s) ? (float) $s : null;
    }

    private function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /** So khớp lỏng: bỏ hoa thường, khoảng trắng, dấu ×/x, chấp nhận cả “flim” lẫn “film” */
    private function norm(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        return str_replace(['×', ' ', 'flim'], ['x', '', 'film'], $s);
    }
}
