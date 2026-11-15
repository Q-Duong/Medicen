<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReportReminders extends Command
{
    protected $signature = 'reports:remind';
    protected $description = 'Gửi email nhắc nhở các khoản thu/chi sắp đến hạn hoặc quá hạn';

    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $in5Days = Carbon::now()->addDays(5)->endOfDay();


        $items = ReportItem::with('report')
            ->where('actual_amount', 0)
            ->whereNotNull('expected_date')
            ->where('expected_date', '<=', $in5Days)
            ->get();

        if ($items->count() > 0) {
            // Người nhận đặt trong .env: REPORT_REMIND_TO (xem config/reports.php)
            $to = config('reports.remind_to');
            if (!$to) {
                Log::warning('reports:remind: chưa cấu hình REPORT_REMIND_TO trong .env, không gửi email', ['items' => $items->count()]);
                $this->warn('Chưa cấu hình REPORT_REMIND_TO trong .env, không gửi email (' . $items->count() . ' mục cần nhắc).');

                return;
            }
            Mail::send('emails.report_reminder', ['items' => $items, 'today' => $today], function ($message) use ($to) {
                $message->to($to)
                    ->subject('⏳ Cảnh báo Thu/Chi cần xử lý hôm nay!');
            });

            $this->info("Đã gửi email nhắc nhở " . $items->count() . " mục tới " . implode(', ', $to) . ".");
        } else {
            $this->info("Không có mục nào cần nhắc nhở hôm nay.");
        }
    }
}
