<?php

namespace App\Console\Commands;

use App\Console\Commands\FinanceCfoReport;
use App\Mail\FinanceMail;
use App\Services\Sales\SalesDay;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Email nhắc việc Sales cuối ngày (17:00 thứ 2 – thứ 7, App\Console\Kernel): AI tóm tắt nhật ký trao đổi với khách, việc đã xong /
 * quá hạn / ngày mai và thao tác của Sales trên web, lưu vào Nhật ký trao đổi khách rồi gửi tới SALES_DIGEST_TO (.env).
 * Ngày không có gì thì không gửi.
 */
class SalesDigestCommand extends Command
{
    protected $signature = 'sales:digest {--date= : Ngày YYYY-MM-DD, mặc định hôm nay} {--no-mail : Chỉ tóm tắt, không gửi email}';

    protected $description = 'Tóm tắt ngày làm việc của Sales bằng AI và gửi email nhắc việc';

    public function handle(SalesDay $sales): int
    {
        $day = $this->option('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->option('date')) ? Carbon::parse($this->option('date')) : Carbon::today();
        $to = config('sales_log.digest_to');
        if (!$to && !$this->option('no-mail')) {
            $this->warn('Chưa có người nhận: thêm SALES_DIGEST_TO=email1,email2 vào .env rồi chạy php artisan config:cache.');

            return self::SUCCESS;
        }
        try {
            $digest = $sales->summarize($day);
        } catch (Throwable $e) {
            Log::error('Tóm tắt ngày Sales lỗi', ['error' => $e->getMessage()]);
            $this->error('AI lỗi: ' . $e->getMessage());

            return self::FAILURE;
        }
        if (!$digest) {
            $this->info('Ngày ' . $day->format('d/m/Y') . ' không có nhật ký / việc / thao tác nào của Sales, không gửi.');

            return self::SUCCESS;
        }
        $this->line($digest->content);
        if ($this->option('no-mail')) {
            return self::SUCCESS;
        }
        try {
            Mail::to($to)->send(new FinanceMail('📋 Trao đổi khách ngày ' . $day->format('d/m') . ': việc cần nhắc', 'emails.sales.digest', [
                'day' => $day, 'html' => FinanceCfoReport::emailHtml($digest->content), 'stats' => $digest->stats,
                'url' => route('sales_logs.index', ['ngay' => $day->toDateString()]),
            ]));
            $digest->update(['emailed_at' => now()]);
        } catch (Throwable $e) {
            Log::error('Gửi email nhắc việc Sales lỗi', ['error' => $e->getMessage()]);
            $this->error('Gửi email lỗi: ' . $e->getMessage());

            return self::FAILURE;
        }
        $this->info('Đã gửi cho: ' . implode(', ', $to));

        return self::SUCCESS;
    }
}
