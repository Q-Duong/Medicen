<?php

namespace App\Services\Sales;

use Anthropic\Client;
use Anthropic\Messages\TextBlock;
use App\Services\Accounting\ContractReader;
use Carbon\Carbon;
use RuntimeException;

/**
 * AI đọc một mục nhật ký trao đổi khách (đoạn chat Zalo dán vào, ảnh chụp màn hình, ghi chú sau cuộc gọi) và trả:
 * khách / đơn vị, tóm tắt, việc cần làm kèm hạn. Số điện thoại / email trong chữ được che trước khi gửi AI
 * (quy định: không gửi SĐT khách cho Claude); ảnh không che được nên dặn AI không chép lại.
 */
class SalesLogReader
{
    public const MAX_IMAGES = 6;

    public function __construct(private Client $client, private ContractReader $units)
    {
    }

    /**
     * @param array<int, string> $imagePaths đường dẫn file ảnh (jpg / png / webp)
     * @return array{kind: string, customer: ?string, unit_id: ?int, unit_hint: ?string, summary: string, important: bool, important_reason: ?string, followups: list<array{title: string, due_on: ?string}>, readable: bool}
     */
    public function read(?string $text, array $imagePaths, string $kindHint, Carbon $day): array
    {
        $content = [];
        foreach (array_slice($imagePaths, 0, self::MAX_IMAGES) as $p) {
            $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
            $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? null;
            if ($mime) {
                $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode((string) file_get_contents($p))]];
            }
        }
        $clean = self::maskContacts(trim((string) $text));
        if ($clean === '' && !$content) {
            throw new RuntimeException('Chưa có nội dung để đọc.');
        }
        $wd = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'][$day->dayOfWeek];
        $content[] = ['type' => 'text', 'text' => "Ngày diễn ra: {$wd}, " . $day->format('d/m/Y') . " ({$day->toDateString()}).\n"
            . 'Loại Sales chọn: ' . (\App\Models\SalesLog::KINDS[$kindHint] ?? 'không chọn') . "\n"
            . ($clean !== '' ? "Nội dung Sales dán / ghi:\n<<<\n" . mb_substr($clean, 0, 30000) . "\n>>>\n" : '(Chỉ có ảnh chụp màn hình.)\n')
            . "\n" . self::INSTRUCTIONS];

        $message = $this->client->messages->create(
            maxTokens: 4000,
            messages: [['role' => 'user', 'content' => $content]],
            model: config('claude.model'),
            outputConfig: ['effort' => 'low', 'format' => ['type' => 'json_schema', 'schema' => $this->schema()]],
            requestOptions: ['timeout' => 120, 'maxRetries' => 1],
        );
        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('AI từ chối đọc nội dung này.');
        }
        $out = '';
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                $out .= $block->text;
            }
        }
        $d = json_decode($out, true);
        if (!is_array($d)) {
            throw new RuntimeException('AI trả kết quả không đúng định dạng.');
        }

        return $this->normalize($d, $day);
    }

    public function normalize(array $d, Carbon $day): array
    {
        $str = fn ($v, $n = 191) => ($s = trim(self::maskContacts((string) $v))) !== '' ? mb_substr($s, 0, $n) : null;
        $hint = $str($d['unit_hint'] ?? null);
        $match = $hint ? ($this->units->matchUnits(null, $hint)[0] ?? null) : null;
        $followups = [];
        foreach ((array) ($d['followups'] ?? []) as $f) {
            $title = $str($f['title'] ?? null, 500);
            if (!$title) {
                continue;
            }
            $due = is_string($f['due_on'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['due_on']) ? $f['due_on'] : null;
            // Hạn trước ngày diễn ra là AI hiểu sai năm / tháng: bỏ, để Sales tự đặt
            if ($due && Carbon::parse($due)->lt($day)) {
                $due = null;
            }
            $followups[] = ['title' => $title, 'due_on' => $due];
        }

        return [
            'kind' => in_array($d['kind'] ?? '', array_keys(\App\Models\SalesLog::KINDS), true) ? $d['kind'] : 'chat',
            'customer' => $str($d['customer'] ?? null),
            'unit_hint' => $hint,
            'unit_id' => $match && $match['score'] >= 70 ? $match['id'] : null,
            'summary' => $str($d['summary'] ?? null, 3000) ?? '',
            'important' => (bool) ($d['important'] ?? false),
            'important_reason' => $str($d['important_reason'] ?? null, 300),
            'followups' => array_slice($followups, 0, 8),
            'readable' => (bool) ($d['readable'] ?? true),
        ];
    }

    /** Che số điện thoại / email trước khi gửi AI và trước khi lưu tóm tắt */
    public static function maskContacts(string $s): string
    {
        $s = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/u', '[email]', $s);

        return preg_replace('/(?<!\d)(?:\+?84|0)(?:[\s.\-]?\d){8,10}(?!\d)/', '[SĐT]', $s);
    }

    private const INSTRUCTIONS = <<<'TXT'
Bạn là trợ lý của phòng Sales, Công ty Nam Khánh Linh (Medicen) — cho thuê xe X-quang / siêu âm khám sức khỏe lưu động cho
công ty, trường học, bệnh viện, phòng khám. Đọc trao đổi giữa Sales và khách (đoạn chat Zalo, ảnh chụp màn hình chat, hoặc ghi chú
sau cuộc gọi) và điền các trường. Không bịa; chỉ dựa vào nội dung có.
- kind: "chat" (tin nhắn), "call" (cuộc gọi), "meeting" (gặp trực tiếp), "note" (ghi chú khác). Ưu tiên loại Sales chọn nếu hợp lý.
- customer: tên người liên hệ và/hoặc đơn vị khách (vd "Chị Lan - BV Quốc Ánh"); "" nếu không rõ.
- unit_hint: tên công ty / bệnh viện / phòng khám của khách để tìm trên hệ thống; "" nếu không rõ.
- summary: 2–4 câu tiếng Việt, ngắn: khách cần gì (dịch vụ, số ca, ngày chụp, địa điểm, giá…), Sales đã trả lời / hứa gì, kết quả hiện tại.
  Ghi số liệu quan trọng (số ca, ngày, giá). KHÔNG chép số điện thoại, email, số tài khoản, CCCD.
- important: true nếu khách khiếu nại, hủy / dời lịch, chê giá, đòi giảm giá lớn, chậm thanh toán, hoặc việc rất gấp; important_reason: 1 câu.
- followups: các việc cụ thể Sales phải làm tiếp (gửi báo giá, gửi hợp đồng, gọi lại, chốt lịch / số ca, xác nhận địa điểm, nhắc công nợ,
  gửi kết quả…), mỗi việc {title, due_on}. title bắt đầu bằng động từ và có tên khách, vd "Gửi báo giá 300 ca cho chị Lan - BV Quốc Ánh".
  Khách hẹn trả lời vào ngày nào → việc "Gọi lại hỏi kết quả…" đúng ngày đó. due_on: YYYY-MM-DD; đổi "mai", "thứ 5 tuần sau", "cuối tháng"
  theo ngày diễn ra; không nêu hạn thì lấy ngày làm việc kế tiếp (bỏ Chủ nhật). Không có việc gì thì mảng rỗng. Tối đa 5 việc.
- readable: false nếu ảnh mờ / không phải trao đổi với khách.
TXT;

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kind' => ['type' => 'string', 'enum' => ['chat', 'call', 'meeting', 'note']],
                'customer' => ['type' => 'string'],
                'unit_hint' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'important' => ['type' => 'boolean'],
                'important_reason' => ['type' => 'string'],
                'followups' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'title' => ['type' => 'string'], 'due_on' => ['type' => 'string'],
                ], 'required' => ['title', 'due_on'], 'additionalProperties' => false]],
                'readable' => ['type' => 'boolean'],
            ],
            'required' => ['kind', 'customer', 'unit_hint', 'summary', 'important', 'important_reason', 'followups', 'readable'],
            'additionalProperties' => false,
        ];
    }
}
