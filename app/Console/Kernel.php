<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('reports:remind')->dailyAt('10:00');
        $schedule->command('assistant:prune')->dailyAt('02:00');
        // Nhắc người xếp lịch: đơn sắp chạy chưa xếp xe, giữ chỗ chưa lên đơn
        $schedule->command('schedule:remind')->dailyAt(config('schedule.remind_at', '16:00'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
