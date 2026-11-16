<?php

namespace App\Console\Commands;

use App\Mail\ScheduleReminderMail;
use App\Models\ScheduleHold;
use App\Models\User;
use App\Services\Schedule\ScheduleSuggester;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Email nhắc người xếp lịch: đơn sắp chụp (hôm nay + vài ngày tới) chưa xếp xe, và lịch giữ chỗ sắp tới chưa lên đơn.
 * Không có gì cần nhắc thì không gửi.
 */
class ScheduleRemind extends Command
{
    protected $signature = 'schedule:remind {--dry-run : Chỉ in ra, không gửi email}';

    protected $description = 'Gửi email nhắc đơn sắp chạy chưa xếp xe và giữ chỗ chưa lên đơn';

    public function handle(ScheduleSuggester $suggester): int
    {
        $today = Carbon::today();
        $days = max(0, (int) config('schedule.remind_days', 2));
        $until = $today->copy()->addDays($days);

        $orders = $suggester->pendingOrders($days)
            ->filter(fn ($o) => $o->ord_start_day <= $until->toDateString())
            ->values();
        $holds = ScheduleHold::active()
            ->whereBetween('hold_start', [$today->toDateString(), $today->copy()->addDays((int) config('schedule.remind_hold_days', 7))->toDateString()])
            ->orderBy('hold_start')->get();

        $recipients = User::whereIn('department', config('schedule.user_departments', []))->whereNotNull('email')->pluck('email')
            ->merge(config('schedule.remind_to', []))
            ->map(fn ($e) => strtolower(trim($e)))->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->unique()->values();

        $this->info("Đơn chưa xếp xe đến {$until->format('d/m')}: {$orders->count()} · Giữ chỗ chưa lên đơn: {$holds->count()} · Người nhận: {$recipients->count()}");
        foreach ($orders as $o) {
            $this->line(" - #{$o->id} {$o->unit} {$o->ord_start_day} {$o->ord_session} {$o->order_quantity} cas");
        }
        if ($orders->isEmpty() && $holds->isEmpty()) {
            $this->info('Không có gì cần nhắc, không gửi email.');

            return self::SUCCESS;
        }
        if ($recipients->isEmpty()) {
            $this->warn('Không có người nhận (tài khoản phòng Điều phối xếp lịch chưa có email, SCHEDULE_REMIND_TO trống).');

            return self::SUCCESS;
        }
        if ($this->option('dry-run')) {
            $this->info('Chạy thử: không gửi email.');

            return self::SUCCESS;
        }

        $subject = '🚐 Nhắc xếp lịch: ' . ($orders->isNotEmpty() ? $orders->count() . ' đơn sắp chạy chưa xếp xe' : '')
            . ($orders->isNotEmpty() && $holds->isNotEmpty() ? ', ' : '')
            . ($holds->isNotEmpty() ? $holds->count() . ' giữ chỗ chưa lên đơn' : '');
        try {
            Mail::to($recipients->all())->send(new ScheduleReminderMail($subject, [
                'orders' => $orders->groupBy('ord_start_day'),
                'count' => $orders->count(),
                'holds' => $holds,
                'today' => $today,
                'until' => $until,
                'url' => route('dispatch.index'),
            ]));
        } catch (Throwable $e) {
            Log::error('Gửi email nhắc xếp lịch lỗi', ['error' => $e->getMessage()]);
            $this->error('Gửi email lỗi: ' . $e->getMessage());

            return self::FAILURE;
        }
        $this->info('Đã gửi email cho: ' . $recipients->join(', '));

        return self::SUCCESS;
    }
}
