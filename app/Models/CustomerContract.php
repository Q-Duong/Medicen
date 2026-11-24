<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Hợp đồng với khách hàng (đơn vị). Xem migration 2026_11_20_130000 và App\Services\Accounting\ContractPricing */
class CustomerContract extends Model
{
    use Concerns\RecordsActivity;

    public const TYPES = ['nguyen_tac' => 'Nguyên tắc', 'thoi_vu' => 'Thời vụ'];
    public const PRICE_MODES = ['theo_ca' => 'Theo số ca (bảng giá)', 'tron_goi_ngay' => 'Trọn gói theo ngày', 'tron_goi_dot' => 'Trọn gói theo đợt (báo giá từng đợt)'];

    protected $fillable = ['type', 'unit_id', 'contract_no', 'party_a', 'party_a_tax_code', 'end_company', 'service', 'signed_on', 'valid_from', 'valid_to',
        'auto_renew', 'service_date', 'service_location', 'price_mode', 'package_price', 'vat_included', 'price_tiers', 'averaging_rule', 'addons', 'surcharges',
        'service_radius_km', 'payment_days', 'result_delivery', 'notable_terms', 'note', 'order_id', 'file_path', 'file_name', 'created_by', 'updated_by'];

    protected $casts = [
        'signed_on' => 'date', 'valid_from' => 'date', 'valid_to' => 'date', 'service_date' => 'date',
        'auto_renew' => 'boolean', 'vat_included' => 'boolean',
        'price_tiers' => 'array', 'addons' => 'array', 'surcharges' => 'array',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** Còn hiệu lực vào ngày $d (nguyên tắc tự gia hạn thì coi như còn hiệu lực) */
    public function activeOn(Carbon $d): bool
    {
        $from = $this->valid_from ?? $this->signed_on;
        if ($from && $d->lt($from)) {
            return false;
        }

        return !$this->valid_to || $d->lte($this->valid_to) || $this->auto_renew;
    }

    /** Tóm tắt giá một dòng: "Trọn gói 13.700.000/đợt", "<50 ca: 2.000.000/ngày · 50+: 26.000/ca …" */
    public function priceSummary(string $glue = ' · '): string
    {
        $m = fn ($n) => number_format((int) $n, 0, ',', '.');
        if ($this->type === 'thoi_vu' || $this->price_mode === 'tron_goi_ngay') {
            return $this->package_price ? 'Trọn gói ' . $m($this->package_price) . ($this->type === 'thoi_vu' ? '/đợt' : '/ngày') : 'Chưa có giá';
        }
        if ($this->price_mode === 'tron_goi_dot') {
            return 'Báo giá từng đợt';
        }
        $tiers = collect($this->price_tiers ?? [])->sortBy(fn ($t) => (int) ($t['min_cases'] ?? 0));
        if ($tiers->isEmpty()) {
            return 'Chưa có bảng giá';
        }

        return $tiers->map(fn ($t) => (($t['min_cases'] ?? 0) ? $t['min_cases'] . (($t['max_cases'] ?? 0) ? '–' . $t['max_cases'] : '+') : '≤' . ($t['max_cases'] ?? 0))
            . ' ca: ' . $m($t['price']) . (($t['unit'] ?? 'ca') === 'ngay' ? '/ngày' : '/ca'))->implode($glue);
    }

    /** Lưu JSON giữ nguyên tiếng Việt */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // ---- Nhật ký thao tác ----
    public function activityArea(): string
    {
        return 'hop_dong';
    }

    public function activityTitle(): string
    {
        return 'hợp đồng ' . (self::TYPES[$this->type] ?? '') . ' ' . ($this->contract_no ?: '') . ' (' . ($this->party_a ?: 'chưa rõ đơn vị') . ')';
    }

    public function activityFields(): array
    {
        return ['contract_no' => 'Số HĐ', 'unit_id' => 'Đơn vị (mã)', 'type' => 'Loại', 'valid_to' => 'Hiệu lực đến', 'price_mode' => 'Cách tính giá',
            'package_price' => 'Giá trọn gói', 'payment_days' => 'Số ngày thanh toán', 'service_date' => 'Ngày chụp', 'order_id' => 'Đơn hàng'];
    }
}
