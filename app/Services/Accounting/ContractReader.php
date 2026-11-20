<?php

namespace App\Services\Accounting;

use Anthropic\Client;
use Anthropic\Messages\TextBlock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Đọc hợp đồng khách hàng (Word / PDF scan / ảnh) bằng Claude để điền sẵn form Hợp đồng: loại (nguyên tắc / thời vụ),
 * Bên A, công ty được chụp, hiệu lực, bảng giá theo số ca, phần cộng thêm, phụ thu khoảng cách, số ngày thanh toán…
 * Không lưu gì — kế toán xem lại rồi bấm Lưu. Tự gợi ý đơn vị trên web (khớp MST hoặc tên).
 */
class ContractReader
{
    public function __construct(private Client $client, private DocumentReader $documents)
    {
    }

    public function read(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'pdf' && filesize($path) > DocumentReader::MAX_BYTES) {
            throw new RuntimeException('File PDF lớn hơn 20 MB, AI không đọc được.');
        }
        $content = [$this->documents->fileBlock($path, $ext), ['type' => 'text', 'text' => "Tên file: {$originalName}\n\n" . self::INSTRUCTIONS]];

        $message = $this->client->messages->create(
            maxTokens: 12000,
            messages: [['role' => 'user', 'content' => $content]],
            model: config('claude.model'),
            outputConfig: ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => $this->schema()]],
            thinking: ['type' => 'adaptive'],
            requestOptions: ['timeout' => 240, 'maxRetries' => 1],
        );
        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('AI từ chối đọc tài liệu này.');
        }
        $text = '';
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                $text .= $block->text;
            }
        }
        $d = json_decode($text, true);
        if (!is_array($d)) {
            throw new RuntimeException('AI trả kết quả không đúng định dạng, thử lại.');
        }

        return $this->normalize($d);
    }

    /** Làm sạch kết quả AI + gợi ý đơn vị */
    public function normalize(array $d): array
    {
        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
        $int = fn ($v) => is_numeric($v) && (int) $v !== 0 ? (int) $v : null;
        $str = fn ($v, $n = 255) => ($s = trim((string) $v)) !== '' ? mb_substr($s, 0, $n) : null;

        $out = [
            'type' => in_array($d['contract_type'] ?? '', ['nguyen_tac', 'thoi_vu'], true) ? $d['contract_type'] : 'nguyen_tac',
            'contract_no' => $str($d['contract_no'] ?? null, 120),
            'party_a' => $str($d['party_a_name'] ?? null),
            'party_a_tax_code' => $str(preg_replace('/[^\d-]/', '', (string) ($d['party_a_tax_code'] ?? '')), 30),
            'end_company' => $str($d['end_company'] ?? null),
            'service' => $str($d['service'] ?? null),
            'signed_on' => $date($d['signed_on'] ?? null),
            'valid_from' => $date($d['valid_from'] ?? null),
            'valid_to' => $date($d['valid_to'] ?? null),
            'auto_renew' => (bool) ($d['auto_renew'] ?? false),
            'service_date' => $date($d['service_date'] ?? null),
            'service_location' => $str($d['service_location'] ?? null),
            'price_mode' => in_array($d['price_mode'] ?? '', ['theo_ca', 'tron_goi_ngay', 'tron_goi_dot'], true) ? $d['price_mode'] : 'theo_ca',
            'package_price' => $int($d['package_price'] ?? null),
            'vat_included' => (bool) ($d['vat_included'] ?? true),
            'price_tiers' => array_values(array_filter(array_map(fn ($t) => [
                'min_cases' => $int($t['min_cases'] ?? null), 'max_cases' => $int($t['max_cases'] ?? null),
                'unit' => ($t['unit'] ?? 'ca') === 'ngay' ? 'ngay' : 'ca', 'price' => $int($t['price'] ?? null),
            ], (array) ($d['price_tiers'] ?? [])), fn ($t) => $t['price'])),
            'averaging_rule' => $str($d['averaging_rule'] ?? null, 500),
            'addons' => array_values(array_filter(array_map(fn ($a) => ['name' => $str($a['name'] ?? null, 120), 'price_per_case' => $int($a['price_per_case'] ?? null)],
                (array) ($d['addons'] ?? [])), fn ($a) => $a['name'] && $a['price_per_case'] !== null)),
            'surcharges' => array_values(array_map(fn ($s) => ['from_km' => $int($s['from_km'] ?? null), 'to_km' => $int($s['to_km'] ?? null),
                'min_amount' => $int($s['min_amount'] ?? null), 'max_amount' => $int($s['max_amount'] ?? null), 'vat_included' => (bool) ($s['vat_included'] ?? false)],
                (array) ($d['surcharges'] ?? []))),
            'service_radius_km' => $int($d['service_radius_km'] ?? null),
            'payment_days' => $int($d['payment_days'] ?? null),
            'result_delivery' => $str($d['result_delivery'] ?? null, 2000),
            'notable_terms' => $str($d['notable_terms'] ?? null, 3000),
            'readable' => (bool) ($d['readable'] ?? true),
        ];
        $out['unit_suggestions'] = $this->matchUnits($out['party_a_tax_code'], $out['party_a']);
        $out['unit_id'] = $out['unit_suggestions'][0]['id'] ?? null;

        return $out;
    }

    /** Gợi ý đơn vị trên web: khớp MST trước, rồi đến tên (bỏ dấu, bỏ chữ "công ty / bệnh viện…") */
    public function matchUnits(?string $tax, ?string $name): array
    {
        $units = DB::table('units')->get(['id', 'unit_code', 'unit_abbreviation', 'unit_name', 'unit_tax_code']);
        $norm = fn ($s) => trim(preg_replace('/\s+/', ' ', preg_replace('/\b(cong ty|cty|tnhh|co phan|cp|benh vien|bv|phong kham|pk|da khoa|dk|chi nhanh|mtv)\b/', ' ',
            mb_strtolower(\Illuminate\Support\Str::ascii((string) $s)))));
        $target = $norm($name);
        $scored = $units->map(function ($u) use ($tax, $target, $norm) {
            $score = 0;
            if ($tax && $u->unit_tax_code && preg_replace('/\D/', '', $u->unit_tax_code) === preg_replace('/\D/', '', $tax)) {
                $score = 100;
            } elseif ($target !== '') {
                foreach ([$u->unit_name, $u->unit_abbreviation] as $cand) {
                    $c = $norm($cand);
                    if ($c === '') {
                        continue;
                    }
                    if ($c === $target) {
                        $score = max($score, 90);
                    } elseif (str_contains($target, $c) || str_contains($c, $target)) {
                        $score = max($score, 70);
                    } else {
                        similar_text($c, $target, $pct);
                        $score = max($score, (int) $pct - 20);
                    }
                }
            }

            return ['id' => $u->id, 'label' => trim(($u->unit_abbreviation ?: $u->unit_code) . ' — ' . $u->unit_name), 'score' => $score];
        })->filter(fn ($u) => $u['score'] >= 50)->sortByDesc('score')->take(5)->values()->all();

        return $scored;
    }

    private const INSTRUCTIONS = <<<'TXT'
Bạn là kế toán Công ty TNHH Đầu tư Trang thiết bị Y tế Nam Khánh Linh (NKL / Medicen, MST 0307659096, là BÊN B — bên cho thuê
xe X-quang lưu động). Đọc hợp đồng đính kèm và điền các trường để lưu vào hệ thống. Không bịa: trường chữ nào hợp đồng không ghi thì
để chuỗi rỗng "", trường số không có thì 0, danh sách không có thì mảng rỗng.

- contract_type: "nguyen_tac" (hợp đồng nguyên tắc / dài hạn, áp dụng cho nhiều đợt, thường có số HĐ đuôi HĐNT) hoặc "thoi_vu"
  (một đợt / một ngày chụp cụ thể, thường đuôi HĐXQ, có "Thời gian chụp" / "Thời gian thực hiện" là một ngày).
- contract_no: số hợp đồng. party_a_name, party_a_tax_code: tên và MST của BÊN A (bên thuê) — KHÔNG phải Nam Khánh Linh.
- end_company: công ty / đơn vị được chụp nếu khác Bên A (câu "để chụp X-Quang cho …"); null nếu chính là Bên A.
- service: dịch vụ (vd "X-quang phổi"). signed_on: ngày ký (YYYY-MM-DD). valid_from / valid_to: hiệu lực; "có hiệu lực từ ngày ký đến khi
  hai bên không có nhu cầu" → valid_from = ngày ký, valid_to = "". "thời hạn 12 tháng" → valid_to = ngày ký + 12 tháng.
  auto_renew: true nếu có điều khoản tự động gia hạn.
- service_date, service_location: ngày và địa điểm chụp (thời vụ).
- price_mode: "theo_ca" (bảng giá theo số ca), "tron_goi_ngay" (một giá trọn gói mỗi ngày), "tron_goi_dot" (trọn gói từng đợt nhưng hợp đồng KHÔNG ghi số tiền).
- package_price: giá trọn gói (đồng, số nguyên) nếu có một giá trọn gói duy nhất; 0 nếu theo bảng.
- vat_included: giá đã gồm VAT chưa.
- price_tiers: bảng giá theo số ca MỖI NGÀY, mỗi bậc {min_cases, max_cases, unit, price}: unit "ca" = đồng/ca, "ngay" = trọn gói đồng/ngày.
  "< 50 ca: trọn gói 2.000.000/ngày" → {min_cases: 0, max_cases: 49, unit: "ngay", price: 2000000};
  "Từ 50 ca < 100 ca: 26.000/ca" → {min_cases: 50, max_cases: 99, unit: "ca", price: 26000}; "từ 200 ca trở lên" → max_cases 0 (không giới hạn).
- averaging_rule: cách tính khi chụp nhiều ngày (vd "đơn giá theo số ca bình quân các ngày"); "" nếu không có.
- addons: các khoản cộng thêm theo ca (in phim / giấy theo cỡ…): {name, price_per_case}.
- surcharges: phụ thu khoảng cách theo km: {from_km, to_km, min_amount, max_amount, vat_included}; "500.000 → 1.500.000/ngày dưới 100km" →
  {from_km: 0, to_km: 100, min_amount: 500000, max_amount: 1500000}.
- service_radius_km: bán kính áp dụng giá gốc (vd "dưới 50km" → 50).
- payment_days: số ngày phải thanh toán (vd "trong vòng 15 ngày" → 15).
- result_delivery: hình thức trả kết quả, cỡ phim, gạch đầu dòng ngắn.
- notable_terms: điều khoản đáng chú ý ngắn gọn (phạt chậm trả, bồi thường khi đổi lịch, độc quyền, chấm dứt…).
  Không chép số tài khoản ngân hàng, số điện thoại, địa chỉ chi tiết.
- readable: false nếu phần lớn file mờ / không đọc được.
TXT;

    private function schema(): array
    {
        // Không dùng kiểu null (API giới hạn số trường union): chuỗi rỗng / số 0 = không có
        $ns = ['type' => 'string'];
        $ni = ['type' => 'integer'];

        return [
            'type' => 'object',
            'properties' => [
                'contract_type' => ['type' => 'string', 'enum' => ['nguyen_tac', 'thoi_vu']],
                'contract_no' => $ns, 'party_a_name' => $ns, 'party_a_tax_code' => $ns, 'end_company' => $ns, 'service' => $ns,
                'signed_on' => $ns, 'valid_from' => $ns, 'valid_to' => $ns, 'auto_renew' => ['type' => 'boolean'],
                'service_date' => $ns, 'service_location' => $ns,
                'price_mode' => ['type' => 'string', 'enum' => ['theo_ca', 'tron_goi_ngay', 'tron_goi_dot']],
                'package_price' => $ni, 'vat_included' => ['type' => 'boolean'],
                'price_tiers' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'min_cases' => $ni, 'max_cases' => $ni, 'unit' => ['type' => 'string', 'enum' => ['ca', 'ngay']], 'price' => ['type' => 'integer'],
                ], 'required' => ['min_cases', 'max_cases', 'unit', 'price'], 'additionalProperties' => false]],
                'averaging_rule' => $ns,
                'addons' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string'], 'price_per_case' => ['type' => 'integer']],
                    'required' => ['name', 'price_per_case'], 'additionalProperties' => false]],
                'surcharges' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'from_km' => $ni, 'to_km' => $ni, 'min_amount' => $ni, 'max_amount' => $ni, 'vat_included' => ['type' => 'boolean'],
                ], 'required' => ['from_km', 'to_km', 'min_amount', 'max_amount', 'vat_included'], 'additionalProperties' => false]],
                'service_radius_km' => $ni, 'payment_days' => $ni, 'result_delivery' => $ns, 'notable_terms' => $ns, 'readable' => ['type' => 'boolean'],
            ],
            'required' => ['contract_type', 'contract_no', 'party_a_name', 'party_a_tax_code', 'end_company', 'service', 'signed_on', 'valid_from', 'valid_to',
                'auto_renew', 'service_date', 'service_location', 'price_mode', 'package_price', 'vat_included', 'price_tiers', 'averaging_rule', 'addons',
                'surcharges', 'service_radius_km', 'payment_days', 'result_delivery', 'notable_terms', 'readable'],
            'additionalProperties' => false,
        ];
    }
}
