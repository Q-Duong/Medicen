<?php

namespace App\Console\Commands;

use App\Mail\FinanceMail;
use App\Services\Accounting\CashForecast;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Cảnh báo TIỀN GẤP hằng ngày (07:00, xem App\Console\Kernel): theo dự báo dòng tiền 13 tuần, nếu số dư trong 14 ngày tới
 * xuống dưới ngưỡng (FINANCE_CASH_THRESHOLD) thì gửi email ngay. Không gấp thì không gửi (đã có cảnh báo tuần thứ Hai).
 */
class FinanceCashAlert extends Command
{
    protected $signature = 'finance:cash-alert {--days=14 : Số ngày tới cần xét} {--dry-run : Chỉ in ra, không gửi email}';

    protected $description = 'Email cảnh báo khi số dư tiền dự báo trong 14 ngày tới dưới ngưỡng';

    public function handle(CashForecast $forecast): int
    {
        $days = max(7, min(28, (int) $this->option('days')));
        $f = $forecast->build([]);
        $limit = Carbon::today()->addDays($days)->toDateString();
        $weeks = collect($f['tuan'])->filter(fn ($w) => $w['tu'] <= $limit)->values();
        $urgent = $weeks->where('canh_bao', true)->values();

        foreach ($weeks as $w) {
            $this->line(sprintf('Tuần %d (%s → %s): cuối kỳ %s%s', $w['tuan'], $w['tu'], $w['den'], number_format($w['cuoi_ky'], 0, ',', '.'), $w['canh_bao'] ? '  ⚠️ dưới ngưỡng' : ''));
        }
        if ($urgent->isEmpty()) {
            $this->info("Không gấp: {$days} ngày tới số dư dự báo đều trên ngưỡng " . number_format($f['nguong_canh_bao'], 0, ',', '.') . '.');

            return self::SUCCESS;
        }

        $to = FinanceAlerts::recipients();
        if (!$to || $this->option('dry-run')) {
            $this->warn($to ? 'Chạy thử: không gửi. Người nhận: ' . implode(', ', $to) : 'Chưa có người nhận (FINANCE_REPORT_TO / REPORT_REMIND_TO / REPORT_UPDATED_TO trống).');

            return self::SUCCESS;
        }

        $first = $urgent->first();
        $low = $weeks->sortBy('cuoi_ky')->first();
        $outs = $weeks->take($urgent->keys()->last() + 1)->flatMap(fn ($w) => $w['khoan_lon'])
            ->filter(fn ($k) => ($k['dir'] ?? 'out') === 'out')->sortByDesc('amount')->take(8)->values()->all();
        $subject = '🔴 Tiền gấp: số dư dự báo ' . number_format($low['cuoi_ky'] / 1e6, 0, ',', '.') . ' tr tuần ' . Carbon::parse($low['tu'])->format('d/m');
        try {
            Mail::to($to)->send(new FinanceMail($subject, 'emails.finance.cash_alert', [
                'f' => $f, 'weeks' => $weeks->all(), 'first' => $first, 'low' => $low, 'outs' => $outs, 'days' => $days,
                'url' => route('finance.index', ['tab' => 'dong-tien']),
            ]));
        } catch (Throwable $e) {
            Log::error('Gửi email cảnh báo tiền gấp lỗi', ['error' => $e->getMessage()]);
            $this->error('Gửi email lỗi: ' . $e->getMessage());

            return self::FAILURE;
        }
        $this->info('Đã gửi cảnh báo tiền gấp cho: ' . implode(', ', $to));

        return self::SUCCESS;
    }
}
