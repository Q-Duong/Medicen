<?php

namespace App\Models;

use App\Enums\UploadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelUpload extends Model
{
    protected $table = 'excel_uploads';

    protected $fillable = [
        'unit_id', 'ngay', 'ten_file', 'duong_dan', 'kich_thuoc', 'ghi_chu',
        'trang_thai', 'ly_do_tu_choi', 'nguoi_tao_id', 'nguoi_tiep_nhan_id',
        'tiep_nhan_luc', 'hoan_thanh_luc', 'order_id',
    ];

    protected $casts = [
        'ngay'           => 'date',
        'trang_thai'     => UploadStatus::class,
        'tiep_nhan_luc'  => 'datetime',
        'hoan_thanh_luc' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Có thể tạo đơn: đã tiếp nhận / đang xử lý và chưa có đơn */
    public function canCreateOrder(): bool
    {
        return ! $this->order_id
            && in_array($this->trang_thai, [UploadStatus::DaTiepNhan, UploadStatus::DangXuLy], true);
    }
 
    /**
     * Đồng bộ trạng thái file theo đơn hàng (gọi trong OrderController store/update).
     * complete = 1 → Hoàn thành, complete = 0 → Đang xử lý.
     */
    public function syncWithOrder(int $orderId, bool $complete): void
    {
        $this->order_id = $orderId;

        if ($complete) {
            $this->trang_thai     = UploadStatus::HoanThanh;
            $this->hoan_thanh_luc = $this->hoan_thanh_luc ?? now();
        } else {
            $this->trang_thai     = UploadStatus::DangXuLy;
            $this->hoan_thanh_luc = null;
        }

        $this->save();
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tao_id');
    }

    public function nguoiTiepNhan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tiep_nhan_id');
    }

    /** Lọc theo đơn vị (unit), khoảng ngày, trạng thái, tên file */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['unit_id'] ?? null,    fn ($q, $v) => $q->where('unit_id', $v))
            ->when($f['tu_ngay'] ?? null,    fn ($q, $v) => $q->whereDate('ngay', '>=', $v))
            ->when($f['den_ngay'] ?? null,   fn ($q, $v) => $q->whereDate('ngay', '<=', $v))
            ->when($f['trang_thai'] ?? null, fn ($q, $v) => $q->where('trang_thai', $v))
            ->when($f['q'] ?? null,          fn ($q, $v) => $q->where('ten_file', 'like', '%'.$v.'%'));
    }

    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->ten_file, PATHINFO_EXTENSION));
    }

    public function getKichThuocDepAttribute(): string
    {
        $bytes = (int) $this->kich_thuoc;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1, ',', '.').' MB';
        if ($bytes >= 1024)    return number_format($bytes / 1024, 0, ',', '.').' KB';
        return $bytes.' B';
    }
}