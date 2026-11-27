<?php

namespace App\Console\Commands;

use App\Http\Controllers\SalesLogController;
use App\Models\SalesLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Nhật ký Sales: xóa ảnh chụp màn hình chat cũ hơn config('sales_log.image_days') ngày (tóm tắt và việc cần làm vẫn giữ). Chạy 02:15 hằng ngày. */
class SalesPruneImages extends Command
{
    protected $signature = 'sales:prune-images';

    protected $description = 'Xóa ảnh chụp chat cũ trong Nhật ký Sales';

    public function handle(): int
    {
        $cut = Carbon::today()->subDays((int) config('sales_log.image_days', 90));
        $n = 0;
        SalesLog::whereNotNull('images')->whereDate('happened_on', '<', $cut)->get()->each(function ($l) use (&$n) {
            foreach ((array) $l->images as $img) {
                Storage::disk('local')->delete(SalesLogController::IMG_DIR . '/' . $img);
                $n++;
            }
            $l->forceFill(['images' => null])->saveQuietly();
        });
        $this->info("Đã xóa {$n} ảnh cũ hơn " . $cut->format('d/m/Y') . '.');

        return self::SUCCESS;
    }
}
