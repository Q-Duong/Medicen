<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportItem;
use Carbon\Carbon;
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
            Mail::send('emails.report_reminder', ['items' => $items, 'today' => $today], function ($message) {
                $message->to('quocduong081000@gmail.com')
                    ->subject('⏳ Cảnh báo Thu/Chi cần xử lý hôm nay!');
            });

            $this->info("Đã gửi email nhắc nhở " . $items->count() . " mục.");
        } else {
            $this->info("Không có mục nào cần nhắc nhở hôm nay.");
        }
    }
}
