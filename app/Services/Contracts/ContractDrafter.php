<?php

namespace App\Services\Contracts;

use App\Models\CustomerContract;
use App\Services\Accounting\ContractPricing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Soạn hợp đồng khách hàng ra file Word theo mẫu của công ty (dựng lại từ HĐNT Phan Rang 2026 / PK 115 và HĐ thời vụ TTC 2026):
 *  - "nguyen_tac": bảng giá theo số ca / ngày, trọn gói theo ngày hoặc trọn gói theo đợt; cộng thêm in phim, phụ thu khoảng cách.
 *  - "thoi_vu": một đợt chụp, giá trọn gói.
 * Phần chữ cố định giữ đúng như hợp đồng đã ký; chỉ điền thông tin Bên A, số / ngày hợp đồng, giá, số ngày thanh toán, thời hạn.
 * Bên A thiếu thông tin (đại diện, điện thoại, tài khoản…) thì để dòng chấm để điền tay. File lưu ở storage/app/hop-dong/soan (tự xóa sau vài ngày).
 *
 * $d (đã chuẩn hóa ở draft()): type, contract_no, signed_on, party_a{name, address, phone, fax, account, bank, tax_code, representative, title},
 * service_date, service_location, end_company, price_mode, package_price, tiers[], averaging_rule, addons[], surcharges[], radius_km,
 * result_delivery[], payment_days, valid_months (0 = đến khi chấm dứt), auto_renew.
 */
class ContractDrafter
{
    public const DIR = 'hop-dong/soan';
    private const DOTS = '……………………………………';

    public function __construct(private ContractPricing $pricing)
    {
    }

    /** Tạo file Word; trả về ['file' => tên file trong DIR, 'canh_bao' => [...], 'tom_tat' => [...]] */
    public function draft(array $d): array
    {
        $d = $this->normalize($d);
        $doc = $d['type'] === 'thoi_vu' ? $this->seasonal($d) : $this->framework($d);
        Storage::disk('local')->makeDirectory(self::DIR);
        $this->cleanup();
        $name = now()->format('Ymd-His') . '-' . (Str::slug($d['party_a']['name'] ?: 'hop-dong') ?: 'hop-dong') . '-' . Str::lower(Str::random(6)) . '.docx';
        $doc->save(Storage::disk('local')->path(self::DIR . '/' . $name), 'Hợp đồng ' . $d['contract_no']);

        return ['file' => $name, 'so_hd' => $d['contract_no'], 'canh_bao' => $this->warnings($d), 'tom_tat' => $this->summary($d)];
    }

    // ------------------------------------------------------------------
    // Chuẩn hóa dữ liệu
    // ------------------------------------------------------------------

    public function normalize(array $d): array
    {
        $type = ($d['type'] ?? 'nguyen_tac') === 'thoi_vu' ? 'thoi_vu' : 'nguyen_tac';
        $signed = !empty($d['signed_on']) ? Carbon::parse($d['signed_on']) : Carbon::today();
        $a = $d['party_a'] ?? [];
        $int = fn ($v) => ($s = preg_replace('/\D/', '', (string) $v)) === '' ? null : (int) $s;
        $mode = in_array($d['price_mode'] ?? '', ['theo_ca', 'tron_goi_ngay', 'tron_goi_dot'], true) ? $d['price_mode'] : (!empty($d['tiers']) ? 'theo_ca' : 'tron_goi_ngay');
        $tiers = collect($d['tiers'] ?? [])->map(fn ($t) => ['min_cases' => $int($t['min_cases'] ?? null), 'max_cases' => $int($t['max_cases'] ?? null),
            'unit' => ($t['unit'] ?? 'ca') === 'ngay' ? 'ngay' : 'ca', 'price' => $int($t['price'] ?? null)])->filter(fn ($t) => $t['price'])
            ->sortBy(fn ($t) => $t['min_cases'] ?? 0)->values()->all();
        if ($type === 'nguyen_tac' && $mode === 'theo_ca' && !$tiers) {
            throw new InvalidArgumentException('Hợp đồng tính theo số ca cần ít nhất một bậc giá.');
        }
        if (($type === 'thoi_vu' || $mode === 'tron_goi_ngay') && !$int($d['package_price'] ?? null)) {
            throw new InvalidArgumentException('Cần giá trọn gói.');
        }
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii((string) ($d['code'] ?? $a['abbreviation'] ?? '')))) ?: 'KH';

        return [
            'type' => $type,
            'signed_on' => $signed,
            'contract_no' => trim((string) ($d['contract_no'] ?? '')) ?: $signed->format('d.m.Y') . '/' . config('contract_templates.ben_b.contract_code') . '-' . substr($code, 0, 14)
                . '/' . ($type === 'thoi_vu' ? 'HĐXQ' : 'HĐNT'),
            'party_a' => [
                'name' => mb_strtoupper(trim((string) ($a['name'] ?? ''))),
                'address' => trim((string) ($a['address'] ?? '')), 'phone' => trim((string) ($a['phone'] ?? '')), 'fax' => trim((string) ($a['fax'] ?? '')),
                'account' => trim((string) ($a['account'] ?? '')), 'bank' => trim((string) ($a['bank'] ?? '')), 'tax_code' => trim((string) ($a['tax_code'] ?? '')),
                'representative' => trim((string) ($a['representative'] ?? '')), 'title' => trim((string) ($a['title'] ?? '')),
                'authorization' => trim((string) ($a['authorization'] ?? '')),
            ],
            'end_company' => mb_strtoupper(trim((string) ($d['end_company'] ?? ''))),
            'service_date' => !empty($d['service_date']) ? Carbon::parse($d['service_date']) : null,
            'service_location' => trim((string) ($d['service_location'] ?? '')),
            'price_mode' => $type === 'thoi_vu' ? 'tron_goi_ngay' : $mode,
            'package_price' => $int($d['package_price'] ?? null),
            'tiers' => $tiers,
            'averaging_rule' => trim((string) ($d['averaging_rule'] ?? '')),
            'addons' => collect($d['addons'] ?? [])->map(fn ($x) => ['name' => trim((string) ($x['name'] ?? '')), 'price_per_case' => $int($x['price_per_case'] ?? null)])
                ->filter(fn ($x) => $x['name'] !== '' && $x['price_per_case'])->values()->all(),
            'surcharges' => collect($d['surcharges'] ?? [])->map(fn ($x) => ['from_km' => $int($x['from_km'] ?? null), 'to_km' => $int($x['to_km'] ?? null),
                'min_amount' => $int($x['min_amount'] ?? null), 'max_amount' => $int($x['max_amount'] ?? null), 'vat_included' => !empty($x['vat_included'])])
                ->filter(fn ($x) => $x['min_amount'] || $x['max_amount'])->values()->all(),
            'radius_km' => $int($d['radius_km'] ?? null),
            'result_delivery' => array_values(array_filter(array_map('trim', (array) ($d['result_delivery'] ?? [])))) ?: ($type === 'thoi_vu'
                ? ['Gửi bảng tổng kết toàn bộ kết quả qua Email.', 'Chỉ in film bệnh lý.'] : config('contract_templates.default_result_delivery')),
            'payment_days' => $int($d['payment_days'] ?? null) ?: 15,
            'valid_months' => (int) ($d['valid_months'] ?? 0),
            'auto_renew' => !empty($d['auto_renew']),
            'km' => $int($d['km'] ?? null) ?: 0,
        ];
    }

    /** Lấy dữ liệu hợp đồng đã lưu làm điểm xuất phát (soạn lại / làm cho đơn vị khác) */
    public static function fromContract(CustomerContract $c): array
    {
        $months = $c->valid_to && ($c->valid_from ?? $c->signed_on) ? (int) round(($c->valid_from ?? $c->signed_on)->diffInMonths($c->valid_to)) : 0;

        return [
            'type' => $c->type, 'price_mode' => $c->price_mode, 'package_price' => $c->package_price, 'tiers' => $c->price_tiers ?? [],
            'averaging_rule' => $c->averaging_rule, 'addons' => $c->addons ?? [], 'surcharges' => $c->surcharges ?? [], 'radius_km' => $c->service_radius_km,
            'result_delivery' => array_values(array_filter(array_map(fn ($l) => trim(ltrim(trim($l), '-•* ')), preg_split('/\r?\n/', (string) $c->result_delivery)))),
            'payment_days' => $c->payment_days, 'valid_months' => $months, 'auto_renew' => $c->auto_renew,
        ];
    }

    // ------------------------------------------------------------------
    // Hợp đồng nguyên tắc
    // ------------------------------------------------------------------

    private function framework(array $d): DocxWriter
    {
        $w = $this->opening($d, 'HỢP ĐỒNG CHO THUÊ THIẾT BỊ', null, true);
        $a = $d['party_a'];
        $w->p('Sau khi trao đổi, thoả thuận, hai bên thống nhất ký kết hợp đồng hợp tác sử dụng xe X-Quang KTS lưu động để chụp X-Quang cho **'
            . ($a['name'] ?: self::DOTS) . '** như sau:', ['first' => 567]);
        $w->p('Với các điều khoản như sau:', ['first' => 567]);

        // Điều 1
        $w->p('**ĐIỀU 1: NỘI DUNG.**', ['keep' => true, 'before' => 4]);
        $m = fn ($n) => number_format((int) $n, 0, ',', '.');
        if ($d['price_mode'] === 'tron_goi_dot') {
            $w->dash('Hai bên thống nhất áp dụng hình thức Đơn giá dịch vụ là đơn giá trọn gói, được áp dụng cố định cho từng đợt tổ chức khám.');
            $w->dash('Hình thức trả kết quả:');
            foreach ($d['result_delivery'] as $line) {
                $w->bullet($line);
            }
        } else {
            if ($d['price_mode'] === 'tron_goi_ngay') {
                $priceCell = $m($d['package_price']) . 'đ/Trọn gói/Ngày';
            } else {
                $lines = ['Đơn giá/ca chụp sẽ tính theo ngày:'];
                foreach (array_values($d['tiers']) as $i => $t) {
                    $lines[] = ($i + 1) . '. ' . $this->tierLabel($t) . ': ' . ($t['unit'] === 'ngay' ? 'Trọn gói ' . $m($t['price']) . ' đồng/ngày' : $m($t['price']) . ' đồng/ca');
                }
                $lines[] = $d['averaging_rule'] !== '' ? $d['averaging_rule']
                    : 'Trường hợp chụp nhiều ngày, mỗi ngày số lượng chụp khác nhau → đơn giá xác định bằng bình quân số lượng ca chụp của các ngày, số lượng bình quân thuộc khung giá nào sẽ tính đơn giá theo khung đó.';
                $priceCell = implode("\n", $lines);
            }
            $w->table([
                ['Nội dung', 'Đơn giá bao gồm VAT (đồng)', 'Hình thức trả kết quả'],
                [['text' => 'Cho thuê thiết bị chụp X-Quang Phổi', 'align' => 'center'], $priceCell, implode("\n", array_map(fn ($l) => '- ' . $l, $d['result_delivery']))],
            ], [1900, 4513, 3000]);
            $w->p('Giá trên đã bao gồm thuế GTGT (VAT theo quy định tại thời điểm xuất hóa đơn), chi phí vận chuyển, khấu hao máy, bảo trì, kỹ sư vận hành và các chi phí hành chính khác.',
                ['italic' => true, 'before' => 4]);
            if ($d['radius_km']) {
                $w->p('**Đơn giá trên áp dụng cho khu vực Tp. Hồ Chí Minh và các vùng lân cận có bán kính dưới ' . $d['radius_km'] . 'km.**', ['italic' => true]);
            }
        }
        if ($d['addons']) {
            $w->p('**Trường hợp in phim:**', ['underline' => true, 'keep' => true]);
            foreach ($d['addons'] as $x) {
                $w->dash($x['name'] . ': cộng thêm ' . $m($x['price_per_case']) . 'đ/ca.');
            }
        }
        if ($d['surcharges']) {
            $w->p('**Phụ thu khoảng cách' . ($d['radius_km'] ? ' (ngoài bán kính ' . $d['radius_km'] . 'km)' : '') . ':**', ['underline' => true, 'keep' => true]);
            foreach ($d['surcharges'] as $s) {
                $range = $s['from_km'] && $s['to_km'] ? 'Từ ' . $s['from_km'] . ' – ' . $s['to_km'] . 'km' : ($s['to_km'] ? 'Dưới ' . $s['to_km'] . 'km' : 'Trên ' . $s['from_km'] . 'km');
                $amount = $s['min_amount'] && $s['max_amount'] && $s['min_amount'] !== $s['max_amount'] ? $m($s['min_amount']) . 'đ – ' . $m($s['max_amount']) . 'đ' : $m($s['max_amount'] ?: $s['min_amount']) . 'đ';
                $w->dash($range . ': ' . $amount . '/ngày' . ($s['vat_included'] ? ' (đã gồm VAT)' : ' (chưa gồm VAT)') . '.');
            }
            $w->p('Mức phụ thu cụ thể hai bên thống nhất trước mỗi đợt khám căn cứ địa điểm thực tế.', ['italic' => true]);
        }

        // Điều 2
        $w->p('**ĐIỀU 2: CHI PHÍ, THANH TOÁN VÀ PHƯƠNG THỨC THANH TOÁN.**', ['keep' => true, 'before' => 4]);
        $w->p($d['price_mode'] === 'tron_goi_dot'
            ? '**2.1 Thanh toán**: Việc thanh toán sẽ được thực hiện dựa trên cơ sở **đơn giá trọn gói tính theo từng đợt khám thực tế**. Sau khi kết thúc mỗi đợt khám, hai bên sẽ tiến hành lập Biên bản nghiệm thu và thanh toán tổng chi phí của đợt đó căn cứ vào mức đơn giá trọn gói đã thỏa thuận.'
            : '**2.1 Thanh toán**: Bên A thanh toán cho Bên B tổng chi phí theo căn cứ vào số lượng thực tế mà Bên B thực hiện ở Điều 1 của Hợp đồng này.', ['first' => 567]);
        $w->p('**2.2 Phương thức thanh toán**: Chuyển khoản trong vòng ' . $this->num2($d['payment_days'])
            . ' ngày sau khi nhận được Biên bản nghiệm thu, kết quả phim X-quang và hoá đơn giá trị gia tăng cho từng đợt khám.', ['first' => 567]);

        // Điều 3
        $this->duties($w, true);

        // Điều 4
        $w->p('**ĐIỀU 4: ĐIỀU KHOẢN CHUNG.**', ['keep' => true, 'before' => 4]);
        $w->p($d['valid_months'] > 0
            ? 'Hợp đồng này có hiệu lực ' . $this->num2($d['valid_months']) . ' tháng kể từ ngày ký' . ($d['auto_renew']
                ? ', hết thời hạn mà hai bên không có ý kiến khác thì Hợp đồng tự động gia hạn thêm ' . $this->num2($d['valid_months']) . ' tháng.' : '.')
            : 'Hợp đồng này có hiệu lực kể từ ngày ký cho đến khi một trong hai bên không có nhu cầu sẽ thanh lý Hợp đồng bằng Biên bản thanh lý.', ['first' => 567]);
        $this->closing($w, $d);

        return $w;
    }

    // ------------------------------------------------------------------
    // Hợp đồng thời vụ
    // ------------------------------------------------------------------

    private function seasonal(array $d): DocxWriter
    {
        $w = $this->opening($d, 'HỢP ĐỒNG CHO THUÊ THIẾT BỊ', 'XE X-QUANG (KTS) LƯU ĐỘNG', false);
        $target = $d['end_company'] ?: $d['party_a']['name'] ?: self::DOTS;
        $w->p('Sau khi trao đổi, thoả thuận, hai bên thống nhất ký kết hợp đồng hợp tác sử dụng xe X-Quang KTS lưu động để chụp X-Quang cho **' . $target . '** như sau:', ['first' => 567]);
        $w->dash('Thời gian chụp: **Ngày ' . ($d['service_date'] ? $d['service_date']->format('d/m/Y') : '…/…/……') . '** Thời gian khám sáng từ 7h00 đến 11h30 chiều từ 13h00 đến 16h00.');
        $w->dash('Địa điểm: ' . ($d['service_location'] ?: self::DOTS) . '.');
        $w->p('Với các điều khoản như sau:', ['first' => 567]);
        $w->p('**ĐIỀU 1: NỘI DUNG.**', ['keep' => true, 'before' => 4]);
        $w->table([
            ['Nội dung', "Đơn giá (VNĐ)\n(Bao gồm VAT)", 'Hình thức trả kết quả'],
            [['text' => 'Cho thuê thiết bị chụp X-quang Phổi', 'align' => 'center'], ['text' => number_format($d['package_price'], 0, ',', '.') . 'đ/Trọn gói/Ngày', 'align' => 'center'],
                implode("\n", array_map(fn ($l) => '- ' . $l, $d['result_delivery']))],
        ], [2600, 3013, 3800]);
        $w->p('**ĐIỀU 2: CHI PHÍ, THANH TOÁN VÀ PHƯƠNG THỨC THANH TOÁN.**', ['keep' => true, 'before' => 6]);
        $w->p('**2.1 Thanh toán**: Bên A thanh toán cho Bên B tổng chi phí theo căn cứ vào số lượng thực tế mà Bên B thực hiện.', ['first' => 567]);
        $w->p('**2.2 Phương thức thanh toán**: Chuyển khoản trong vòng ' . $this->num2($d['payment_days'])
            . ' ngày sau khi nhận được Biên bản thanh lý hợp đồng, phim X-quang và Hóa đơn đỏ.', ['first' => 567]);
        $this->duties($w, false, $d['payment_days']);
        $w->p('**ĐIỀU 4: ĐIỀU KHOẢN CHUNG.**', ['keep' => true, 'before' => 4]);
        $w->p('Hợp đồng này có hiệu lực kể từ ngày ký, hai bên cam kết cùng nhau thực hiện nghiêm túc hợp đồng. Nếu có bất kỳ thay đổi nào phải được thể hiện bằng văn bản và được hai bên cùng xác nhận.', ['first' => 567]);
        $w->p('Trong thời gian hợp đồng có hiệu lực nếu xảy ra bất đồng hai bên sẽ cùng nhau thương lượng, giải quyết trên tinh thần hợp tác và tôn trọng.', ['first' => 567]);
        $w->p('Hợp đồng này được lập thành 04 (bốn) bản, mỗi bên giữ 02 (hai) bản có giá trị ngang nhau.', ['first' => 567, 'after' => 12]);
        $w->signatures(mb_strtoupper($this->repName($d['party_a']['representative'])), config('contract_templates.ben_b.sign_name'));

        return $w;
    }

    // ------------------------------------------------------------------
    // Phần chung
    // ------------------------------------------------------------------

    private function opening(array $d, string $title, ?string $title2, bool $legalBasis): DocxWriter
    {
        $w = new DocxWriter();
        $w->p('**CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM**', ['align' => 'center', 'size' => 14, 'after' => 0]);
        $w->p('**Độc lập – Tự do – Hạnh phúc**', ['align' => 'center', 'after' => 0]);
        $w->rule();
        $w->p('**' . $title . '**', ['align' => 'center', 'size' => 14, 'after' => 0]);
        if ($title2) {
            $w->p('**' . $title2 . '**', ['align' => 'center', 'size' => 14, 'after' => 0]);
        }
        $w->p('Số: ' . $d['contract_no'], ['align' => 'center', 'after' => 10]);
        if ($legalBasis) {
            $w->dash('Căn cứ Bộ Luật Dân sự số 91/2015/QH13 của Quốc Hội nước Cộng Hoà Xã Hội chủ nghĩa Việt Nam ban hành ngày 24 tháng 11 năm 2015 có hiệu lực ngày 01 tháng 01 năm 2017;', ['italic' => true]);
            $w->dash('Căn cứ Luật thương mại số 36/2005/QH11 của Quốc Hội nước Cộng Hoà Xã Hội chủ nghĩa Việt Nam ban hành ngày 14 tháng 06 năm 2005 có hiệu lực ngày 01 tháng 01 năm 2006;', ['italic' => true]);
        }
        $w->dash('Căn cứ thỏa thuận, nhu cầu sử dụng và khả năng thực tế của hai bên;', ['italic' => true]);
        $s = $d['signed_on'];
        $w->p('Hôm nay, ngày ' . $s->format('d') . ' tháng ' . $s->format('m') . ' năm ' . $s->format('Y') . ', chúng tôi gồm có:', ['italic' => true, 'indent' => 567, 'after' => 8]);

        $a = $d['party_a'];
        $w->p('**BÊN A (BÊN THUÊ): ' . ($a['name'] ?: self::DOTS) . '**', ['keep' => true]);
        $w->dash('Địa chỉ: ' . ($a['address'] ?: self::DOTS));
        $w->dash('Điện thoại: ' . ($a['phone'] ?: self::DOTS) . ($a['fax'] ? "\tFax: " . $a['fax'] : ''));
        if ($legalBasis) {
            $w->dash('Tài khoản: ' . ($a['account'] ?: self::DOTS));
            $w->dash('Ngân hàng: ' . ($a['bank'] ?: self::DOTS));
        }
        $w->dash('Mã số thuế: ' . ($a['tax_code'] ?: self::DOTS));
        $w->dash('Đại diện: **' . ($a['representative'] ?: self::DOTS) . "**\tChức vụ: **" . ($a['title'] ?: '…………………') . '**', ['tab' => 5670]);
        if ($a['authorization'] !== '') {
            $w->p('(' . trim($a['authorization'], '()') . ')', ['italic' => true, 'indent' => 567]);
        }

        $b = config('contract_templates.ben_b');
        $w->p('**BÊN B (BÊN CHO THUÊ): ' . $b['name'] . '**', ['keep' => true, 'before' => 4]);
        $w->dash('Địa chỉ: ' . $b['address']);
        $w->dash('Điện thoại: ' . $b['phone']);
        foreach ($b['accounts'] as $acc) {
            $w->dash('Tài khoản: ' . $acc['no']);
            $w->dash('Ngân hàng: ' . $acc['bank']);
        }
        $w->dash('Mã số thuế: ' . $b['tax_code']);
        $w->dash('Đại diện: **' . $b['representative'] . "**\tChức vụ: **" . $b['title'] . '**', ['tab' => 5670, 'after' => 8]);

        return $w;
    }

    /** Điều 3 (theo HĐNT Phan Rang 2026 / PK 115; thời vụ theo TTC) */
    private function duties(DocxWriter $w, bool $framework, int $payDays = 15): void
    {
        $w->p('**ĐIỀU 3: TRÁCH NHIỆM CỦA CÁC BÊN.**', ['keep' => true, 'before' => 4]);
        $w->p('**3.1 Trách nhiệm của Bên A:**', ['indent' => 284, 'keep' => true]);
        if ($framework) {
            $w->dash('Lập và gửi danh sách người lao động, địa chỉ công ty có yêu cầu đến khám sức khỏe cho Bên B từ 07 (bảy) đến 10 (mười) ngày trước khi khám.');
            $w->dash('Thông báo cho tất cả nhân viên nữ nghi ngờ có thai hoặc có thai không được chụp X-quang (Những trường hợp nhân viên nữ nghi ngờ có thai hoặc có thai mà không trình báo trước khi tham gia chụp X-quang, Bên B hoàn toàn không chịu trách nhiệm).');
            $w->dash('Bên A có trách nhiệm bồi thường 70% giá trị hợp đồng dự kiến nếu như tự ý thay đổi thời gian thực hiện mà không thông báo trước cho Bên B trước 03 (ba) ngày bằng văn bản hoặc Email.');
            $w->dash('Bên A có trách nhiệm sau khi Hợp đồng có hiệu lực chỉ liên kết về mảng X-quang khám ngoại viện với Bên B, mà không ký Hợp đồng song song với bất kỳ đơn vị khác có cùng chức năng với điều kiện Bên B phải đáp ứng đủ yêu cầu xe cho Bên A.');
            $w->dash('Thanh toán chi phí cho Bên B theo Điều 2.');
        } else {
            $w->dash('Lập và gửi danh sách người lao động, địa chỉ công ty có yêu cầu đến khám sức khỏe cho Bên B từ 05 (năm) đến 07 (bảy) ngày trước khi khám.');
            $w->dash('Nếu có gì thay đổi sẽ thông báo trước cho Bên B trước 07 (bảy) ngày bằng văn bản.');
            $w->dash('Thông báo cho tất cả nhân viên nữ nghi ngờ có thai hoặc có thai không được chụp X-quang (Những trường hợp nhân viên nữ nghi ngờ có thai hoặc có thai mà không trình báo trước khi tham gia chụp X-quang, Bên B hoàn toàn không chịu trách nhiệm).');
            $w->dash('Sau khi Bên A nhận được kết quả (theo Điều 1) Thanh lý hợp đồng phim X-quang và hóa đơn đỏ (theo Điều 2), thì trong vòng ' . $this->num2($payDays)
                . ' ngày phải thanh toán nếu quá ' . $this->num2($payDays + 5) . ' ngày vi phạm nghĩa vụ thanh toán sẽ phải trả thêm tiền phạt bằng 10% mức lãi suất cơ bản, do Ngân hàng Nhà nước công bố trên tổng giá trị thanh toán chậm tính từ ngày hết hạn thanh toán đến ngày thanh toán.');
        }
        $w->p('**3.2 Trách nhiệm của Bên B:**', ['indent' => 284, 'keep' => true, 'before' => 4]);
        $w->dash($framework ? 'Điều phối xe, kỹ thuật viên, tài xế theo đúng lịch và thời gian đã định.' : 'Điều phối xe theo đúng lịch và thời gian đã định.');
        $w->dash('Đảm bảo chất lượng xe, các trang thiết bị trên xe luôn ở trong tình trạng tốt và sẽ khắc phục kịp thời các sự cố xảy ra trong thời gian sớm nhất.');
        if ($framework) {
            $w->dash('Luôn đảm bảo cung cấp xe và nhân sự đảm bảo theo đúng lịch và thời gian đã thoả thuận với Bên A qua Email trong thời gian Hợp đồng.');
            $w->dash('Trả kết quả và phim theo đúng quy cách ở Điều 1.');
            $w->dash('Thanh toán chi phí cho Bác sĩ đọc kết quả, Kỹ thuật viên, tài xế.');
        } else {
            $w->dash('Chịu trách nhiệm chuyên môn, kỹ thuật, chi phí, nhân sự, kỹ thuật viên, Bác sĩ đọc kết quả phải có Chứng chỉ hành nghề theo đúng qui định của Luật khám chữa bệnh.');
            $w->dash('Trả kết quả và phim theo đúng quy cách ở Điều 1.');
        }
        $w->dash('Xuất hóa đơn tài chính cho Bên A.');
    }

    private function closing(DocxWriter $w, array $d): void
    {
        $w->p('Hai bên cam kết cùng nhau thực hiện nghiêm túc Hợp đồng này. Nếu có bất kỳ thay đổi nào phải được thể hiện bằng văn bản và được hai bên cùng xác nhận.', ['first' => 567]);
        $w->p('Trong thời gian Hợp đồng có hiệu lực nếu xảy ra bất đồng hai bên sẽ cùng nhau thương lượng, giải quyết trên tinh thần hợp tác và tôn trọng.', ['first' => 567]);
        $w->p('Hợp đồng này được lập thành 04 (bốn) bản, mỗi bên giữ 02 (hai) bản có giá trị ngang nhau.', ['first' => 567, 'after' => 12]);
        $w->signatures(mb_strtoupper($this->repName($d['party_a']['representative'])), config('contract_templates.ben_b.sign_name'));
    }

    private function repName(string $rep): string
    {
        return trim(preg_replace('/^(ông|bà|ong|ba)\s+/iu', '', $rep));
    }

    private function tierLabel(array $t): string
    {
        $min = $t['min_cases'];
        $max = $t['max_cases'];
        if (!$min) {
            // "dưới 50" lưu là 0–49 → "< 50 ca"; "dưới 100" lưu là 0–100 → "Đến 100 ca"
            return !$max ? 'Mọi số ca' : (($max + 1) % 10 === 0 ? '< ' . ($max + 1) . ' ca' : 'Đến ' . $max . ' ca');
        }

        return $max ? 'Từ ' . $min . ' ca – ' . $max . ' ca' : 'Từ ' . $min . ' ca trở lên';
    }

    /** "15 (mười lăm)" — số ngày / tháng trong hợp đồng */
    public function num2(int $n): string
    {
        return str_pad((string) $n, 2, '0', STR_PAD_LEFT) . ' (' . $this->words($n) . ')';
    }

    private function words(int $n): string
    {
        $u = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        if ($n < 10) {
            return $u[$n];
        }
        if ($n < 100) {
            $t = intdiv($n, 10);
            $r = $n % 10;

            return ($t === 1 ? 'mười' : $u[$t] . ' mươi') . ($r === 0 ? '' : ' ' . ($r === 5 ? 'lăm' : ($r === 1 && $t > 1 ? 'mốt' : ($r === 4 && $t > 1 ? 'tư' : $u[$r]))));
        }
        $h = intdiv($n, 100);
        $r = $n % 100;

        return $u[$h] . ' trăm' . ($r === 0 ? '' : ($r < 10 ? ' lẻ ' . $u[$r] : ' ' . $this->words($r)));
    }

    // ------------------------------------------------------------------
    // Kiểm tra giá so với giá sàn (chỉ cảnh báo)
    // ------------------------------------------------------------------

    private function warnings(array $d): array
    {
        $out = [];
        $m = fn ($n) => number_format((int) $n, 0, ',', '.');
        $floor = function (int $cases) use ($d) {
            $f = $this->pricing->suggest(0, max(1, $cases), 1, $d['km'])['floor'] ?? null;

            return $f['gia_san_mot_ca'] ?? null;
        };
        if ($d['price_mode'] === 'theo_ca') {
            foreach ($d['tiers'] as $t) {
                $cases = $t['unit'] === 'ngay' ? max(1, ($t['max_cases'] ?? 50)) : max(1, $t['min_cases'] ?? 1);
                $perCase = $t['unit'] === 'ngay' ? (int) round($t['price'] / $cases) : $t['price'];
                $f = $floor($cases);
                if ($f && $perCase < $f) {
                    $out[] = 'Bậc ' . mb_strtolower(mb_substr($this->tierLabel($t), 0, 1)) . mb_substr($this->tierLabel($t), 1) . ': ' . $m($perCase) . ' đ/ca ở mức ' . $cases . ' ca thấp hơn giá sàn ước tính ' . $m($f) . ' đ/ca'
                        . ($d['km'] ? ' (' . $d['km'] . ' km khứ hồi)' : ' (chưa tính dầu)') . '.';
                }
            }
        } elseif ($d['package_price']) {
            $f = $floor(100);
            if ($f && $d['package_price'] < $f * 100 && $d['type'] === 'nguyen_tac') {
                $out[] = 'Giá trọn gói ' . $m($d['package_price']) . ' đ/ngày chỉ có lời nếu từ khoảng ' . (int) ceil($d['package_price'] / max(1, $f)) . ' ca trở xuống — kiểm tra số ca dự kiến.';
            }
        }
        foreach (['representative' => 'người đại diện', 'title' => 'chức vụ', 'phone' => 'điện thoại', 'address' => 'địa chỉ', 'tax_code' => 'mã số thuế'] as $k => $label) {
            if ($d['party_a'][$k] === '') {
                $out[] = 'Chưa có ' . $label . ' Bên A — để dòng chấm, điền tay trong Word.';
            }
        }

        return $out;
    }

    private function summary(array $d): array
    {
        return array_filter([
            'loai' => $d['type'] === 'thoi_vu' ? 'Thời vụ' : 'Nguyên tắc',
            'so_hd' => $d['contract_no'],
            'ngay_ky' => $d['signed_on']->format('d/m/Y'),
            'ben_a' => $d['party_a']['name'],
            'cach_tinh' => CustomerContract::PRICE_MODES[$d['price_mode']] ?? $d['price_mode'],
            'gia_tron_goi' => $d['package_price'],
            'bac_gia' => $d['tiers'],
            'cong_them' => $d['addons'],
            'phu_thu' => $d['surcharges'],
            'thanh_toan_ngay' => $d['payment_days'],
            'thoi_han' => $d['type'] === 'thoi_vu' ? null : ($d['valid_months'] ? $d['valid_months'] . ' tháng' . ($d['auto_renew'] ? ', tự gia hạn' : '') : 'đến khi chấm dứt'),
            'ngay_chup' => optional($d['service_date'])->format('d/m/Y'),
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /** Xóa file soạn cũ */
    private function cleanup(): void
    {
        $limit = now()->subDays((int) config('contract_templates.keep_days', 7))->getTimestamp();
        foreach (Storage::disk('local')->files(self::DIR) as $f) {
            if (Storage::disk('local')->lastModified($f) < $limit) {
                Storage::disk('local')->delete($f);
            }
        }
    }
}
