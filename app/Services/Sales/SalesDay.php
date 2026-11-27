<?php

namespace App\Services\Sales;

use Anthropic\Client;
use Anthropic\Messages\TextBlock;
use App\Models\SalesDigest;
use App\Models\SalesFollowup;
use App\Models\SalesLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Một ngày làm việc của Sales: nhật ký trao đổi với khách + việc cần làm + thao tác trên web (đơn hàng, giữ chỗ lịch…),
 * và bản tóm tắt ngày do AI viết (nút trên trang hoặc lệnh sales:digest 17:00 gửi email nhắc việc; lưu sales_digests).
 */
class SalesDay
{
    public function __construct(private Client $client)
    {
    }

    /** Tài khoản Sales: phòng sales + tài khoản tên Sale + ai đã ghi nhật ký */
    public static function salesUsers(): Collection
    {
        $ids = SalesLog::distinct()->pluck('user_id')->filter()->all();

        return User::where(fn ($q) => $q->where('department', 'sales')->orWhere('name', 'Sale')->orWhereIn('id', $ids))
            ->orderBy('name')->get(['id', 'name', 'email', 'role', 'department']);
    }

    /** Số liệu thô của ngày (dùng cho trang, tool trợ lý và bản tóm tắt) */
    public function facts(Carbon $day): array
    {
        $users = self::salesUsers();
        $name = fn ($id) => optional($users->firstWhere('id', $id))->name ?? ('#' . $id);
        $logs = SalesLog::with('followups', 'unit:id,unit_abbreviation,unit_name')->whereDate('happened_on', $day)->orderBy('created_at')->get();
        $web = DB::table('activity_logs')->whereIn('user_id', $users->pluck('id'))
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->whereIn('action', ['them', 'sua', 'xoa', 'tai_len', 'nhap', 'thao_tac'])
            ->whereNotIn('area', ['nhat_ky_sales'])
            ->orderBy('created_at')->get(['user_id', 'summary', 'created_at']);

        return [
            'ngay' => $day->format('d/m/Y'),
            'nhat_ky' => $logs->map(fn ($l) => array_filter([
                'id' => $l->id, 'luc' => $l->created_at->format('H:i'), 'sales' => $name($l->user_id), 'loai' => SalesLog::KINDS[$l->kind] ?? $l->kind,
                'khach' => $l->customer, 'don_vi' => $l->unit->unit_abbreviation ?? null, 'tom_tat' => $l->summary,
                'dang_chu_y' => $l->important ?: null,
                'viec_tach_ra' => $l->followups->map(fn ($f) => $f->title . ($f->due_on ? ' (hạn ' . $f->due_on->format('d/m') . ')' : '') . ($f->done_at ? ' — đã xong' : ''))->all() ?: null,
            ], fn ($v) => $v !== null))->values()->all(),
            'viec_xong_trong_ngay' => SalesFollowup::whereBetween('done_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])->get()
                ->map(fn ($f) => $f->title)->all(),
            'viec_qua_han_chua_xong' => SalesFollowup::open()->whereNotNull('due_on')->where('due_on', '<', $day->toDateString())->orderBy('due_on')->limit(30)->get()
                ->map(fn ($f) => $f->title . ' (hạn ' . $f->due_on->format('d/m') . ')')->all(),
            'viec_ngay_mai' => SalesFollowup::open()->whereDate('due_on', $day->copy()->addDay()->isSunday() ? $day->copy()->addDays(2) : $day->copy()->addDay())->get()
                ->map(fn ($f) => $f->title)->all(),
            'thao_tac_tren_web' => $web->groupBy('user_id')->map(fn ($g, $uid) => [
                'sales' => $name($uid), 'so_thao_tac' => $g->count(),
                'viec' => $g->pluck('summary')->map(fn ($s) => mb_strimwidth((string) $s, 0, 90, '…'))->unique()->take(25)->values()->all(),
            ])->values()->all(),
        ];
    }

    /** AI viết tóm tắt ngày, lưu lại; null nếu ngày không có gì */
    public function summarize(Carbon $day): ?SalesDigest
    {
        $f = $this->facts($day);
        if (!$f['nhat_ky'] && !$f['thao_tac_tren_web'] && !$f['viec_xong_trong_ngay']) {
            SalesDigest::whereDate('day', $day)->delete(); // bản tóm tắt cũ (nhật ký đã bị xóa hết) không còn đúng

            return null;
        }
        $prompt = "Dữ liệu ngày {$f['ngay']} của phòng Sales (JSON):\n" . json_encode($f, JSON_UNESCAPED_UNICODE) . "\n\n" . self::INSTRUCTIONS;
        $message = $this->client->messages->create(
            maxTokens: 4000,
            messages: [['role' => 'user', 'content' => $prompt]],
            model: config('claude.model'),
            outputConfig: ['effort' => 'low'],
            requestOptions: ['timeout' => 120, 'maxRetries' => 1],
        );
        $text = '';
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                $text .= $block->text;
            }
        }
        $text = trim(SalesLogReader::maskContacts($text));
        if ($text === '') {
            return null;
        }

        return SalesDigest::updateOrCreate(['day' => $day->toDateString()], ['content' => $text, 'stats' => [
            'nhat_ky' => count($f['nhat_ky']), 'thao_tac_web' => array_sum(array_column($f['thao_tac_tren_web'], 'so_thao_tac')),
            'viec_xong' => count($f['viec_xong_trong_ngay']), 'qua_han' => count($f['viec_qua_han_chua_xong']),
        ]]);
    }

    private const INSTRUCTIONS = <<<'TXT'
Viết BẢN TÓM TẮT CUỐI NGÀY của phòng Sales gửi giám đốc Medicen (cho thuê xe X-quang khám sức khỏe lưu động). Tiếng Việt, markdown,
ngắn gọn để đọc trên điện thoại, chỉ dựa vào dữ liệu, không bịa. Bố cục:
## Tổng quan — 2–3 câu: hôm nay Sales làm việc với bao nhiêu khách, kết quả chính (đơn mới / báo giá / chốt lịch).
## Theo khách — mỗi khách một gạch đầu dòng: **khách**: đã trao đổi gì, kết quả, bước tiếp theo.
## Cần giám đốc chú ý — khiếu nại, hủy / dời lịch, giảm giá, việc quá hạn (bỏ mục nếu không có).
## Việc ngày mai — danh sách việc đến hạn ngày làm việc tới.
## Trên web — 1–2 dòng: số thao tác, việc chính (lên / sửa đơn…).
Nếu không có nhật ký trao đổi nào, ghi rõ "Hôm nay Sales chưa ghi nhật ký trao đổi với khách" ở Tổng quan. Không chép số điện thoại, email.
TXT;
}
