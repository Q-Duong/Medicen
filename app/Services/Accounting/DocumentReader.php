<?php

namespace App\Services\Accounting;

use Anthropic\Client;
use Anthropic\Messages\TextBlock;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use ZipArchive;

/**
 * Đọc tài liệu kế toán (BCTC, hợp đồng, hóa đơn…) bằng Claude để tự điền form "Thêm tài liệu":
 * loại, tên, các bên, kỳ, ngày ký, hiệu lực, giá trị, tóm tắt. Không lưu gì — người dùng xem lại rồi mới bấm Lưu.
 * Nhận PDF, ảnh (JPG / PNG), Excel, Word (.docx). File .doc cũ không đọc được.
 */
class DocumentReader
{
    /** PDF / ảnh gửi nguyên file (base64) — giới hạn để không vượt kích thước request của API */
    public const MAX_BYTES = 20 * 1024 * 1024;

    /** Ảnh: API Claude nhận tối đa 5 MB mỗi ảnh */
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    private const MAX_TEXT_CHARS = 150000;

    public function __construct(private Client $client)
    {
    }

    /**
     * @return array{type: string, title: string, parties: ?string, period: ?string, signed_on: ?string,
     *               valid_from: ?string, valid_to: ?string, value: ?int, summary: string, readable: bool}
     */
    public function read(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'pdf' && filesize($path) > self::MAX_BYTES) {
            throw new RuntimeException('File PDF lớn hơn 20 MB, AI không đọc được. Hãy tách nhỏ file (vd tách phụ lục riêng) hoặc nhập tay.');
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png'], true) && filesize($path) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Ảnh lớn hơn 5 MB, AI không đọc được. Chụp lại với độ phân giải thấp hơn hoặc gộp thành PDF.');
        }

        $content = [$this->fileBlock($path, $ext), ['type' => 'text', 'text' => "Tên file: {$originalName}\n\n" . $this->instructions()]];

        $message = $this->client->messages->create(
            maxTokens: 16000,
            messages: [['role' => 'user', 'content' => $content]],
            model: config('claude.model'),
            outputConfig: ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => $this->schema()]],
            thinking: ['type' => 'adaptive'],
            requestOptions: ['timeout' => 240, 'maxRetries' => 1],
        );

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('AI từ chối đọc tài liệu này.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new RuntimeException('Tài liệu quá dài, AI chưa tóm tắt xong. Hãy tách nhỏ file.');
        }
        $text = '';
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                $text .= $block->text;
            }
        }
        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new RuntimeException('AI trả kết quả không đúng định dạng, thử lại.');
        }

        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;

        return [
            'type' => in_array($data['type'] ?? '', ['bctc', 'hop_dong', 'khac'], true) ? $data['type'] : 'khac',
            'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 255),
            'parties' => ($p = trim((string) ($data['parties'] ?? ''))) !== '' ? mb_substr($p, 0, 255) : null,
            'period' => ($p = trim((string) ($data['period'] ?? ''))) !== '' ? mb_substr($p, 0, 50) : null,
            'signed_on' => $date($data['signed_on'] ?? null),
            'valid_from' => $date($data['valid_from'] ?? null),
            'valid_to' => $date($data['valid_to'] ?? null),
            'value' => isset($data['value']) && is_numeric($data['value']) && $data['value'] > 0 ? (int) $data['value'] : null,
            'summary' => trim((string) ($data['summary'] ?? '')),
            'readable' => (bool) ($data['readable'] ?? true),
        ];
    }

    /** Khối nội dung gửi Claude cho một file (PDF / ảnh nguyên file, Excel / Word đổi ra chữ). Dùng chung với ContractReader */
    public function fileBlock(string $path, string $ext): array
    {
        $b64 = fn () => base64_encode(file_get_contents($path));

        return match ($ext) {
            'pdf' => ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $b64()]],
            'jpg', 'jpeg' => ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $b64()]],
            'png' => ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => $b64()]],
            'xlsx', 'xls' => ['type' => 'text', 'text' => "Nội dung file Excel:\n" . $this->limit($this->excelText($path))],
            'docx' => ['type' => 'text', 'text' => "Nội dung file Word:\n" . $this->limit($this->docxText($path))],
            default => throw new RuntimeException('AI chỉ đọc được PDF, ảnh JPG / PNG, Excel và Word (.docx).'),
        };
    }

    private function excelText(string $path): string
    {
        $book = IOFactory::load($path);
        $out = [];
        foreach ($book->getAllSheets() as $sheet) {
            $out[] = '=== Sheet: ' . $sheet->getTitle();
            foreach ($sheet->toArray(null, true, true, false) as $row) {
                $cells = array_filter(array_map(fn ($c) => trim((string) $c), $row), fn ($c) => $c !== '');
                if ($cells) {
                    $out[] = implode(' | ', $cells);
                }
            }
        }

        return implode("\n", $out);
    }

    private function docxText(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true || ($xml = $zip->getFromName('word/document.xml')) === false) {
            throw new RuntimeException('Không mở được file Word.');
        }
        $zip->close();
        $xml = preg_replace(['#</w:p>#', '#<w:tab/>#', '#</w:tc>#'], ["\n", "\t", ' | '], $xml);

        return html_entity_decode(trim(strip_tags($xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function limit(string $text): string
    {
        return mb_strlen($text) > self::MAX_TEXT_CHARS ? mb_substr($text, 0, self::MAX_TEXT_CHARS) . "\n…(cắt bớt)" : $text;
    }

    private function instructions(): string
    {
        return <<<'TXT'
Bạn là kế toán của Công ty TNHH Đầu tư Trang thiết bị Y tế Nam Khánh Linh (NKL / Medicen, MST 0307659096).
Đọc tài liệu đính kèm và điền thông tin để lưu vào kho tài liệu kế toán. Trợ lý Kế toán sau này CHỈ đọc phần tóm tắt
(không đọc file gốc), nên tóm tắt phải đủ số liệu để trả lời câu hỏi mà không cần mở file.

- type: "bctc" (báo cáo tài chính, bảng cân đối, kết quả kinh doanh, lưu chuyển tiền tệ, thuyết minh),
  "hop_dong" (hợp đồng, phụ lục, thỏa thuận, biên bản thanh lý hợp đồng), "khac" (hóa đơn, biên bản, giấy tờ khác).
- title: tên ngắn gọn, rõ ràng, tiếng Việt. Vd "BCTC năm 2025 (bản nộp thuế)", "HĐ thuê máy CT với BV Lagi số 01/2024", "Phụ lục 02 HĐ liên kết Cà Mau".
- parties: các bên (tên viết gọn), cách nhau dấu phẩy; null nếu không có.
- period: kỳ báo cáo cho BCTC (vd "2025", "6T/2026", "Q2/2026"); null với loại khác nếu không có.
- signed_on, valid_from, valid_to: dạng YYYY-MM-DD; null nếu tài liệu không ghi. Hiệu lực tính từ thời hạn hợp đồng nếu ghi theo số tháng / năm.
- value: tổng giá trị hợp đồng / hóa đơn bằng đồng (số nguyên, không dấu chấm); null nếu không có hoặc không xác định được một con số tổng.
- summary: tiếng Việt, gạch đầu dòng, giữ nguyên con số (định dạng 1.234.567 đ).
  Không chép số tài khoản ngân hàng, số CCCD, địa chỉ chi tiết, số điện thoại (chỉ cần tên các bên, MST nếu có).
  * BCTC: ghi rõ bản nộp thuế hay bản nội bộ / gửi ngân hàng nếu nhận ra (có chữ ký, dấu nộp cơ quan thuế, ghi chú…);
    liệt kê các chỉ tiêu chính kèm mã số: Bảng cân đối (tiền, phải thu, hàng tồn kho, TSCĐ, tổng tài sản, nợ phải trả, vay,
    vốn chủ sở hữu, lợi nhuận chưa phân phối) và Kết quả kinh doanh (doanh thu, giá vốn, lợi nhuận gộp, doanh thu / chi phí
    tài chính, lãi vay, chi phí bán hàng, chi phí quản lý, thu nhập / chi phí khác, lợi nhuận trước thuế, thuế TNDN, lợi nhuận
    sau thuế) cho cả số kỳ này và kỳ trước nếu có.
  * Hợp đồng: các bên, nội dung (thuê / liên kết / mua bán thiết bị gì, đặt ở đâu), đơn giá và cách tính tiền (chia lợi nhuận,
    tỷ lệ góp vốn…), thời hạn, lịch và điều kiện thanh toán, bảo hành / bảo trì, phạt / chấm dứt, điều khoản đáng chú ý.
  * Hóa đơn / giấy tờ khác: số, ngày, bên bán / mua, nội dung, tiền trước thuế, thuế GTGT, tổng tiền.
- readable: false nếu phần lớn tài liệu mờ / không đọc được; khi đó vẫn điền những gì đọc được và ghi rõ trong summary phần nào
  không đọc được. Không bịa số liệu: chỗ nào không chắc thì ghi "(không đọc rõ)".
TXT;
    }

    private function schema(): array
    {
        $nullableString = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['bctc', 'hop_dong', 'khac']],
                'title' => ['type' => 'string'],
                'parties' => $nullableString,
                'period' => $nullableString,
                'signed_on' => $nullableString,
                'valid_from' => $nullableString,
                'valid_to' => $nullableString,
                'value' => ['type' => ['integer', 'null']],
                'summary' => ['type' => 'string'],
                'readable' => ['type' => 'boolean'],
            ],
            'required' => ['type', 'title', 'parties', 'period', 'signed_on', 'valid_from', 'valid_to', 'value', 'summary', 'readable'],
            'additionalProperties' => false,
        ];
    }
}
