<?php

namespace App\Services\Sales;

use App\Http\Middleware\PageAccess;
use App\Services\Claude\AssistantAccess;
use App\Models\SalesDigest;
use App\Models\SalesFollowup;
use App\Models\SalesLog;
use App\Models\User;
use Carbon\Carbon;

/** Tool "nhat_ky_sales" dùng chung cho Trợ lý Sales và Trợ lý Kế toán (giám đốc hỏi "hôm nay Sales làm gì") */
class SalesLogTool
{
    public static function definition(): array
    {
        return [
            'name' => 'nhat_ky_sales',
            'description' => 'NHẬT KÝ TRAO ĐỔI KHÁCH: những gì Sales đã trao đổi với khách (chat Zalo, cuộc gọi, gặp khách — Sales ghi trên web, AI tóm tắt), '
                . 'các việc cần làm với khách (quá hạn / hôm nay / sắp tới) và bản tóm tắt cuối ngày. Dùng khi hỏi "hôm nay Sales làm gì", '
                . '"khách X đã trao đổi gì", "còn việc gì phải làm / nhắc gì". Khi GIÁM ĐỐC / Admin hỏi, kết quả có thêm thao_tac_tren_web (Sales đã lên / sửa đơn… '
                . 'trên web) — chỉ nêu khi họ hỏi Sales làm gì; người khác không có phần này. Tự tổng hợp thành bản tóm tắt (tổng quan, theo khách, việc cần chú ý, việc ngày mai). hanh_dong: xem (mặc định); them_viec (Sales nhờ "nhắc tôi…": cần tieu_de, han);'
                . ' xong (đánh dấu việc đã làm: cần id). Nhật ký do Sales tự ghi nên có thể thiếu: nói rõ "theo nhật ký trao đổi khách đã ghi".',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'hanh_dong' => ['type' => 'string', 'enum' => ['xem', 'them_viec', 'xong']],
                    'tu_ngay' => ['type' => 'string', 'description' => 'YYYY-MM-DD, mặc định hôm nay'],
                    'den_ngay' => ['type' => 'string', 'description' => 'YYYY-MM-DD, mặc định bằng tu_ngay'],
                    'tu_khoa' => ['type' => 'string', 'description' => 'Lọc theo tên khách / đơn vị (bỏ trống = tất cả)'],
                    'tieu_de' => ['type' => 'string', 'description' => 'them_viec: việc cần làm, bắt đầu bằng động từ, có tên khách'],
                    'han' => ['type' => 'string', 'description' => 'them_viec: YYYY-MM-DD'],
                    'khach' => ['type' => 'string', 'description' => 'them_viec: tên khách (tùy chọn)'],
                    'id' => ['type' => 'integer', 'description' => 'xong: mã việc'],
                ],
                'additionalProperties' => false,
            ],
        ];
    }

    public function call(array $in, ?User $user): array
    {
        if (!$user || !PageAccess::allows($user, ['sales_log'])) {
            return ['loi' => 'Tài khoản này không xem được Nhật ký trao đổi khách.'];
        }
        $act = (string) ($in['hanh_dong'] ?? 'xem');
        if ($act !== 'xem' && !PageAccess::allows($user, ['sales_log_edit'])) {
            return ['loi' => 'Tài khoản này chỉ xem được Nhật ký trao đổi khách (Kế toán chỉ xem), không thêm / sửa việc.'];
        }
        if ($act === 'them_viec') {
            $title = trim((string) ($in['tieu_de'] ?? ''));
            if ($title === '') {
                return ['loi' => 'Cần tieu_de.'];
            }
            $due = $this->date($in['han'] ?? null);
            $f = SalesFollowup::create(['title' => mb_substr(SalesLogReader::maskContacts($title), 0, 500), 'due_on' => $due?->toDateString(),
                'customer' => trim((string) ($in['khach'] ?? '')) ?: null, 'user_id' => $user->id]);

            return ['da_them' => ['id' => $f->id, 'viec' => $f->title, 'han' => $due?->format('d/m/Y')], 'xem' => route('sales_logs.index')];
        }
        if ($act === 'xong') {
            $f = SalesFollowup::find((int) ($in['id'] ?? 0));
            if (!$f) {
                return ['loi' => 'Không thấy việc mã ' . ($in['id'] ?? '?') . '. Gọi xem để lấy mã.'];
            }
            $f->update(['done_at' => now(), 'done_by' => $user->id]);

            return ['ok' => true, 'id' => $f->id, 'viec' => $f->title];
        }

        $from = $this->date($in['tu_ngay'] ?? null) ?? Carbon::today();
        $to = $this->date($in['den_ngay'] ?? null) ?? $from->copy();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        $to = $to->min($from->copy()->addDays(31));
        $kw = trim((string) ($in['tu_khoa'] ?? ''));
        $match = fn ($q) => $q->where(fn ($w) => $w->where('customer', 'like', "%{$kw}%")->orWhere('title', 'like', "%{$kw}%"));
        $logs = SalesLog::with('followups', 'user:id,name', 'unit:id,unit_abbreviation')
            ->whereBetween('happened_on', [$from->toDateString(), $to->toDateString()])
            ->when($kw !== '', fn ($q) => $q->where(fn ($w) => $w->where('customer', 'like', "%{$kw}%")->orWhere('summary', 'like', "%{$kw}%")
                ->orWhereHas('unit', fn ($u) => $u->where('unit_abbreviation', 'like', "%{$kw}%")->orWhere('unit_name', 'like', "%{$kw}%"))))
            ->orderBy('happened_on')->orderBy('created_at')->limit(80)->get();
        $today = Carbon::today();
        $open = SalesFollowup::open()->when($kw !== '', fn ($q) => $q->where(fn ($w) => $w->where('customer', 'like', "%{$kw}%")->orWhere('title', 'like', "%{$kw}%")))
            ->orderByRaw('due_on IS NULL')->orderBy('due_on')->limit(60)->get();
        $director = AssistantAccess::isFullAdmin($user) || AssistantAccess::isSubAdmin($user);
        $fmt = fn ($f) => array_filter(['id' => $f->id, 'viec' => $f->title, 'han' => $f->due_on?->format('d/m/Y')]);

        return [
            'pham_vi' => $from->isSameDay($to) ? $from->format('d/m/Y') : $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y'),
            'ghi_chu' => 'Theo nhật ký trao đổi khách tự ghi trên web (Kinh doanh → Nhật ký trao đổi khách); trao đổi chưa ghi thì không có ở đây.',
            'nhat_ky' => $logs->map(fn ($l) => array_filter([
                'ngay' => $l->happened_on->format('d/m'), 'luc' => $l->created_at->format('H:i'), 'sales' => $l->user->name ?? null,
                'loai' => SalesLog::KINDS[$l->kind] ?? $l->kind, 'khach' => $l->customer, 'don_vi' => $l->unit->unit_abbreviation ?? null,
                'tom_tat' => $l->summary, 'dang_chu_y' => $l->important ?: null,
                'viec' => $l->followups->map(fn ($f) => $f->title . ($f->done_at ? ' (xong)' : ($f->due_on ? ' (hạn ' . $f->due_on->format('d/m') . ')' : '')))->all() ?: null,
            ]))->values()->all(),
            'viec_qua_han' => $open->filter(fn ($f) => $f->due_on && $f->due_on->lt($today))->map($fmt)->values()->all(),
            'viec_hom_nay' => $open->filter(fn ($f) => $f->due_on && $f->due_on->isSameDay($today))->map($fmt)->values()->all(),
            'viec_sap_toi' => $open->filter(fn ($f) => !$f->due_on || $f->due_on->gt($today))->take(20)->map($fmt)->values()->all(),
            // Thao tác của Sales trên web (lên / sửa đơn, giữ chỗ lịch…) từng ngày, tối đa 7 ngày gần nhất — CHỈ giám đốc (SubAdmin) / Admin hỏi
            'thao_tac_tren_web' => $director ? collect(range(0, min(6, $from->diffInDays($to))))->map(fn ($i) => $to->copy()->subDays($i))
                ->map(fn ($d) => ['ngay' => $d->format('d/m'), 'theo_sales' => app(SalesDay::class)->webActivity($d)])
                ->filter(fn ($d) => $d['theo_sales'])->values()->all() : null,
            'tom_tat_cuoi_ngay' => SalesDigest::whereBetween('day', [$from->toDateString(), $to->toDateString()])->orderBy('day')->get()
                ->map(fn ($d) => ['ngay' => $d->day->format('d/m'), 'noi_dung' => $d->content])->all(),
            'xem_tren_web' => route('sales_logs.index', ['ngay' => $to->toDateString()]),
        ];
    }

    private function date($v): ?Carbon
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v) : null;
    }
}
