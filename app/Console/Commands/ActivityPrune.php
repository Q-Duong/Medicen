<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Xóa nhật ký hoạt động cũ hơn config('activity.keep_months') tháng (chạy hằng tháng, xem Console\Kernel) */
class ActivityPrune extends Command
{
    protected $signature = 'activity:prune';

    protected $description = 'Xóa nhật ký hoạt động và lượt mở trang cũ hơn số tháng cấu hình (mặc định 12)';

    public function handle(): int
    {
        $before = now()->subMonths((int) config('activity.keep_months', 12));
        $logs = DB::table('activity_logs')->where('created_at', '<', $before)->delete();
        $visits = DB::table('activity_visits')->where('created_at', '<', $before)->delete();
        $this->info("Đã xóa {$logs} dòng nhật ký và {$visits} lượt mở trang trước " . $before->format('d/m/Y') . '.');

        return self::SUCCESS;
    }
}
