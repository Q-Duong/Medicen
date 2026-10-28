<?php

namespace App\Enums;

/**
 * Luồng trạng thái:
 *   Chờ tiếp nhận ──(bấm Tiếp nhận)──► Đã tiếp nhận ──► mở trang tạo đơn
 *   Lưu đơn "chưa hoàn thành" (complete = 0) ──► Đang xử lý
 *   Lưu đơn "hoàn thành"      (complete = 1) ──► Hoàn thành
 *
 * Chỉ "Tiếp nhận" là bấm tay; hai trạng thái sau chạy theo đơn hàng.
 * Chỉ sửa / xoá file khi đang "Chờ tiếp nhận".
 */
enum UploadStatus: string
{
    case ChoTiepNhan = 'cho_tiep_nhan';
    case DaTiepNhan  = 'da_tiep_nhan';
    case DangXuLy    = 'dang_xu_ly';
    case HoanThanh   = 'hoan_thanh';

    public function label(): string
    {
        return match ($this) {
            self::ChoTiepNhan => 'Chờ tiếp nhận',
            self::DaTiepNhan  => 'Đã tiếp nhận',
            self::DangXuLy    => 'Đang xử lý',
            self::HoanThanh   => 'Hoàn thành',
        };
    }

    /** Tên nút bấm để chuyển SANG trạng thái này */
    public function actionLabel(): string
    {
        return match ($this) {
            self::DaTiepNhan => 'Tiếp nhận và tạo đơn',
            default          => $this->label(),
        };
    }

    /** Hậu tố class CSS */
    public function css(): string
    {
        return match ($this) {
            self::ChoTiepNhan => 'pending',
            self::DaTiepNhan  => 'received',
            self::DangXuLy    => 'processing',
            self::HoanThanh   => 'done',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::ChoTiepNhan;
    }

    /** @return UploadStatus[] trạng thái được chuyển TAY (còn lại chạy theo đơn hàng) */
    public function nextSteps(): array
    {
        return match ($this) {
            self::ChoTiepNhan => [self::DaTiepNhan],
            default           => [],
        };
    }

    public static function flow(): array
    {
        return [self::ChoTiepNhan, self::DaTiepNhan, self::DangXuLy, self::HoanThanh];
    }
}
