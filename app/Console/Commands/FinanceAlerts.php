<?php

namespace App\Console\Commands;

use App\Mail\FinanceMail;
use App\Services\Finance\EarlyWarnings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Email cảnh báo sớm tài chính hằng tuần (thứ Hai 07:30, xem App\Console\Kernel). Luôn gửi, kể cả khi không có cảnh báo. */
class FinanceAlerts extends Command
{
    protected $signature = 'finance:alerts {--dry-run : Chỉ in ra, không gửi email, không lưu mốc so sánh}';

    protected $description = 'Gửi email cảnh báo sớm tài chính (thiếu tiền, nợ quá hạn tăng, tiền kẹt, khách ngưng, xe nằm không, hợp đồng hết hạn)';

    public function handle(EarlyWarnings $warnings): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = $warnings->check(!$dry);
        foreach ($result['alerts'] as $a) {
            $this->line("[{$a['level']}] {$a['title']} — {$a['detail']}");
            foreach ($a['lines'] as $l) {
                $this->line("    · {$l}");
            }
        }
        if (!$result['alerts']) {
            $this->info('Không có cảnh báo.');
        }

        $to = self::recipients();
        if (!$to) {
            $this->warn('Chưa có người nhận (FINANCE_REPORT_TO / REPORT_REMIND_TO / REPORT_UPDATED_TO trống).');

            return self::SUCCESS;
        }
        if ($dry) {
            $this->info('Chạy thử: không gửi. Người nhận sẽ là: ' . implode(', ', $to));

            return self::SUCCESS;
        }

        $n = count($result['alerts']);
        $subject = ($n ? '⚠️ ' . $n . ' cảnh báo tài chính' : '✅ Tài chính tuần này ổn') . ' · tuần ' . $result['today']->weekOfYear . ' (' . $result['today']->format('d/m') . ')';
        try {
            Mail::to($to)->send(new FinanceMail($subject, 'emails.finance.alerts', $result + ['url' => route('finance.index', ['tab' => 'dong-tien'])]));
        } catch (Throwable $e) {
            Log::error('Gửi email cảnh báo tài chính lỗi', ['error' => $e->getMessage()]);
            $this->error('Gửi email lỗi: ' . $e->getMessage());

            return self::FAILURE;
        }
        $this->info('Đã gửi cho: ' . implode(', ', $to));

        return self::SUCCESS;
    }

    /** FINANCE_REPORT_TO, để trống thì dùng chung email Báo cáo thu chi */
    public static function recipients(): array
    {
        $list = config('finance.report_to') ?: array_merge(config('reports.remind_to', []), config('reports.updated_to', []));

        return array_values(array_unique(array_filter(array_map(fn ($e) => strtolower(trim($e)), $list), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
    }
}
