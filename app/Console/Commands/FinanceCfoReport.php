<?php

namespace App\Console\Commands;

use App\Mail\FinanceMail;
use App\Models\AssistantConversation;
use App\Models\User;
use App\Services\Claude\DepartmentAgentRunner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Báo cáo CFO tự động đầu tháng (ngày 3, 08:00 — xem App\Console\Kernel):
 * Trợ lý Kế toán (vai CFO) lập báo cáo tháng trước, lưu thành một cuộc trò chuyện của tài khoản config('finance.cfo_user')
 * (mở lại trên web để xem biểu đồ, xuất PDF), rồi gửi email nội dung báo cáo cho người nhận Báo cáo thu chi.
 */
class FinanceCfoReport extends Command
{
    protected $signature = 'finance:cfo-report {--month= : Tháng cần báo cáo dạng YYYY-MM, mặc định tháng trước} {--no-mail : Lập báo cáo nhưng không gửi email} {--dry-run : Chỉ in câu lệnh gửi trợ lý và người nhận}';

    protected $description = 'Trợ lý CFO lập báo cáo tài chính tháng trước và gửi email cho giám đốc';

    private const DEPARTMENT = 'ke_toan';

    public function handle(DepartmentAgentRunner $runner): int
    {
        $month = $this->option('month') && preg_match('/^\d{4}-\d{2}$/', $this->option('month'))
            ? Carbon::createFromFormat('Y-m-d', $this->option('month') . '-01')
            : Carbon::today()->subMonthNoOverflow()->startOfMonth();
        $label = $month->month . '/' . $month->year;

        $email = config('finance.cfo_user');
        $user = ($email ? User::where('email', $email)->first() : null) ?? User::where('name', 'Admin')->first();
        if (!$user) {
            $this->error('Không tìm thấy tài khoản chạy báo cáo (FINANCE_CFO_USER hoặc tài khoản tên Admin).');

            return self::FAILURE;
        }

        $prompt = "Lập BÁO CÁO CFO THÁNG {$label} để gửi email cho giám đốc (báo cáo tự động đầu tháng).\n"
            . "- Kỳ: {$month->format('01/m/Y')} – {$month->copy()->endOfMonth()->format('d/m/Y')}, so với tháng trước và cùng kỳ năm trước nếu có số.\n"
            . "- Đủ 3 lớp số liệu: vận hành XQ và lãi ước tính (phan_tich_van_hanh), lãi lỗ theo sổ MISA nếu đã có sổ tháng này (ket_qua_kinh_doanh), "
            . "CT (cong_no_ct), tiền và nợ (du_bao_dong_tien, tuoi_no_tien_ket).\n"
            . "- Bố cục: Tóm tắt điều hành (3–5 ý) → Bảng chỉ số chính → Phân tích → Rủi ro → 3–5 việc cần làm tháng này (ai làm, hạn, tác động bằng tiền) → Dòng tiền 13 tuần tới.\n"
            . "- Kèm 2–3 biểu đồ bieu-do. Viết để giám đốc đọc trên điện thoại: ngắn, rõ số, không hỏi lại.";

        $to = FinanceAlerts::recipients();
        if ($this->option('dry-run')) {
            $this->line($prompt);
            $this->info("Tài khoản: {$user->email} · Người nhận: " . (implode(', ', $to) ?: '(chưa có)'));

            return self::SUCCESS;
        }

        // Báo cáo dài, gọi nhiều tool: cho thời gian chờ dài hơn khung chat
        config(['claude.reply_timeout' => max(600, (int) config('claude.reply_timeout', 240))]);
        $mode = config('claude.departments.' . self::DEPARTMENT . '.modes.cfo');
        $modeNote = $mode ? "{$mode['label']}. {$mode['instruction']}" : null;

        $conversation = AssistantConversation::create([
            'user_id' => $user->id,
            'department' => self::DEPARTMENT,
            'title' => "Báo cáo CFO tháng {$label} (tự động)",
            'last_message_at' => now(),
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => $prompt, 'mode' => 'cfo']);

        try {
            $sessionId = $runner->startSession(self::DEPARTMENT, "Tự động - {$conversation->title}", ['user_id' => $user->id, 'conversation_id' => $conversation->id, 'auto' => 'cfo-report']);
            $conversation->update(['claude_session_id' => $sessionId]);
            $result = $runner->ask(self::DEPARTMENT, $sessionId, $prompt, $user, $modeNote);
        } catch (Throwable $e) {
            $conversation->messages()->create(['role' => 'error', 'content' => 'Lập báo cáo tự động lỗi: ' . $e->getMessage()]);
            Log::error('Báo cáo CFO tự động lỗi', ['month' => $label, 'error' => $e->getMessage()]);
            $this->error('Trợ lý lỗi: ' . $e->getMessage());

            return self::FAILURE;
        }

        $tools = collect($result['tools'])->pluck('name')->unique()->values()->all();
        $conversation->messages()->create(['role' => 'assistant', 'content' => $result['reply'], 'tools' => $tools]);
        $conversation->update(['last_message_at' => now()]);
        $url = route('assistant.show', ['department' => self::DEPARTMENT, 'c' => $conversation->id]);
        $this->info("Đã lập báo cáo (cuộc trò chuyện #{$conversation->id}, tool: " . implode(', ', $tools) . "). Xem: {$url}");

        if ($this->option('no-mail') || !$to) {
            if (!$to) {
                $this->warn('Chưa có người nhận email (FINANCE_REPORT_TO / REPORT_REMIND_TO / REPORT_UPDATED_TO trống).');
            }

            return self::SUCCESS;
        }
        try {
            Mail::to($to)->send(new FinanceMail("📊 Báo cáo CFO tháng {$label}", 'emails.finance.cfo_report', [
                'label' => $label,
                'html' => self::emailHtml($result['reply']),
                'url' => $url,
                'user' => $user->display_name ?? $user->name,
            ]));
        } catch (Throwable $e) {
            Log::error('Gửi email báo cáo CFO lỗi', ['error' => $e->getMessage()]);
            $this->error('Gửi email lỗi: ' . $e->getMessage());

            return self::FAILURE;
        }
        $this->info('Đã gửi cho: ' . implode(', ', $to));

        return self::SUCCESS;
    }

    /** Markdown trả lời → HTML cho email: biểu đồ thay bằng ghi chú (email không chạy được JS), bảng / tiêu đề có style inline */
    public static function emailHtml(string $markdown): string
    {
        $md = preg_replace_callback('/^\s*(```|~~~)\s*(bieu-do|bieudo|bieu_do|chart)\s*\n([\s\S]*?)^\s*\1\s*$/mi', function ($m) {
            $title = '';
            if (($j = json_decode($m[3], true)) && is_array($j)) {
                $title = (string) ($j['tieu_de'] ?? $j['title'] ?? '');
            }

            return "\n> 📈 Biểu đồ" . ($title ? ": **{$title}**" : '') . " — xem trên web (nút bên dưới).\n";
        }, $markdown);
        $html = Str::markdown($md, ['html_input' => 'escape', 'allow_unsafe_links' => false]);

        return strtr($html, [
            '<table>' => '<table style="width:100%;border-collapse:collapse;margin:8px 0 14px;font-size:13.5px;">',
            '<th>' => '<th style="padding:7px 9px;border:1px solid #e2ded3;background:#f4f2eb;text-align:left;font-weight:600;">',
            '<th align="right">' => '<th style="padding:7px 9px;border:1px solid #e2ded3;background:#f4f2eb;text-align:right;font-weight:600;">',
            '<td>' => '<td style="padding:7px 9px;border:1px solid #e2ded3;vertical-align:top;">',
            '<td align="right">' => '<td style="padding:7px 9px;border:1px solid #e2ded3;text-align:right;white-space:nowrap;">',
            '<h2>' => '<h2 style="margin:20px 0 8px;font-size:18px;">',
            '<h3>' => '<h3 style="margin:18px 0 6px;font-size:16px;">',
            '<h4>' => '<h4 style="margin:16px 0 6px;font-size:15px;">',
            '<blockquote>' => '<blockquote style="margin:10px 0;padding:8px 12px;border-left:3px solid #c96442;background:#faf9f5;color:#4a4843;">',
            '<ul>' => '<ul style="margin:0 0 10px;padding-left:20px;">',
            '<ol>' => '<ol style="margin:0 0 10px;padding-left:20px;">',
            '<p>' => '<p style="margin:0 0 10px;">',
        ]);
    }
}
