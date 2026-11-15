<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Đơn giá mặc định hiện hành của bảng tính lợi nhuận:
 * giá gốc ở config/profit.php, ghi đè bởi bảng profit_settings.
 * Mọi lần đổi giá đều ghi vào profit_setting_changes.
 */
class ProfitSettings
{
    /** Tên hiển thị của từng đơn giá (dùng cho tool AI và lịch sử) */
    public const LABELS = [
        'thu_xq_gia' => 'Đơn giá XQ phổi / cas',
        'thu_cstl_gia' => 'Đơn giá CSTL / cas',
        'thu_phuthu_gia' => 'Phụ thu / lần',
        'thu_bs_gia' => 'Thu thêm BS đọc trực tiếp / đợt',
        'chi_baophim_gia' => 'Bao phim / ca XQ',
        'chi_phim16_gia' => 'Phim in 16 (Canon) / ca XQ',
        'chi_phim810_gia' => 'Phim 8x10 / ca CSTL',
        'chi_phim1012_gia' => 'Phim 10x12 / tấm',
        'chi_chietkhau_gia' => 'Chiết khấu mặc định',
        'chi_bsdoc_gia' => 'BS đọc kết quả từ xa / unit',
        'chi_bstructiep_gia' => 'Chi BS đọc trực tiếp / ngày',
        'chi_taixe_gia' => 'Lương tài xế công ty / ngày',
        'chi_thue_tx_gia' => 'Thuê tài xế ngoài / ngày',
        'chi_ktv_gia_ca' => 'Lương KTV công ty / unit',
        'chi_ktv_gia_ngay' => 'Lương KTV công ty / ngày',
        'chi_thue_ktv_gia' => 'Thuê KTV ngoài / ngày',
        'chi_bpkq_gia' => 'Lương bộ phận kết quả / unit',
        'chi_phucap_xa_gia' => 'Phụ cấp >250km / lần / người',
        'chi_quanly_phantram' => 'Phí quản lý (% doanh thu)',
        'chi_cauduong_gia' => 'Phí cầu đường BOT / lượt',
        'chi_dau_gia' => 'Giá dầu / lít',
        'chi_dau_dinhmuc' => 'Định mức dầu (km / lít)',
        'chi_dau_phatsinh_gia' => 'Dầu phát sinh / lít',
        'chi_ks_gia' => 'Khách sạn / phòng / đêm',
        'chi_dangkiem_gia' => 'Đăng kiểm + đường bộ / ngày',
        'chi_dem_gia' => 'Phụ cấp ở lại đêm / đêm / người',
        'chi_vat_phantram' => 'Thuế VAT (%)',
    ];

    private ?array $cache = null;

    /** Giá hiện hành (gốc + ghi đè) */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $values = array_map(fn ($v) => $v + 0, config('profit.defaults'));

        if (Schema::hasTable('profit_settings')) {
            foreach (DB::table('profit_settings')->pluck('value', 'key') as $key => $value) {
                if (array_key_exists($key, $values)) {
                    $values[$key] = (float) $value + 0;
                }
            }
        }

        return $this->cache = $values;
    }

    public function get(string $key): float
    {
        return (float) ($this->all()[$key] ?? 0);
    }

    /**
     * @return array{key: string, label: string, old: float, new: float}
     */
    public function update(string $key, float $value, ?User $user, string $source, ?string $reason = null): array
    {
        if (!array_key_exists($key, config('profit.defaults'))) {
            throw new InvalidArgumentException("Không có đơn giá '{$key}'.");
        }
        if ($value < 0 || ($key === 'chi_dau_dinhmuc' && $value <= 0)) {
            throw new InvalidArgumentException('Giá trị không hợp lệ.');
        }

        $old = $this->get($key);

        DB::transaction(function () use ($key, $value, $old, $user, $source, $reason) {
            DB::table('profit_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_by' => $user?->id, 'updated_at' => now(), 'created_at' => now()],
            );
            DB::table('profit_setting_changes')->insert([
                'key' => $key,
                'old_value' => $old,
                'new_value' => $value,
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'source' => $source,
                'reason' => $reason,
                'created_at' => now(),
            ]);
        });

        $this->cache = null;

        return ['key' => $key, 'label' => self::LABELS[$key] ?? $key, 'old' => $old, 'new' => $value];
    }

    /** Ai được đổi giá mặc định: Admin / SubAdmin (role 0) */
    public function canUpdate(?User $user): bool
    {
        return $user !== null && (int) $user->role === 0;
    }
}
