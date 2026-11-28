<?php

namespace App\Services\Activity;

use App\Http\Middleware\RecordActivity;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Claude\AssistantAccess;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dòng thời gian hoạt động của nhân viên (trang Nhật ký hoạt động + tool trợ lý hoat_dong_nhan_vien):
 * giờ vào / thao tác cuối / thời gian hoạt động mỗi ngày, trang đã mở, từng thao tác kèm thay đổi cũ → mới, điểm đáng chú ý.
 *
 * Quyền xem: chỉ Admin (role 0 / 1). SubAdmin KHÔNG xem được hoạt động của tài khoản Admin đầy đủ quyền (hiddenUsers()).
 * Nguồn: activity_logs, activity_visits; ngày trước khi có nhật ký mới (activity_visits) thì lấy thêm history_edits (lịch sử đơn).
 */
class ActivityTimeline
{
    public const TYPES = [
        'xoa' => 'Xóa dữ liệu', 'sua_tien' => 'Sửa tiền', 'them' => 'Thêm', 'sua' => 'Sửa', 'tai_ve' => 'Tải / xuất file', 'dang_nhap' => 'Đăng nhập / đăng xuất',
        'bat_thuong' => 'Chỉ việc đáng chú ý',
    ];

    /** Nhãn thay đổi liên quan tới tiền (để nêu "sửa tiền lớn") */
    private const MONEY = ['Đơn giá', 'Thành tiền', 'Chiết khấu', 'Đã thanh toán', 'Còn nợ', 'Thành tiền CK', 'Lợi nhuận'];

    private Collection $users;

    public function __construct()
    {
        $this->users = User::with('profile:id,profile_firstname,profile_lastname')->get(['id', 'name', 'email', 'role', 'department', 'profile_id'])
            ->keyBy('id');
    }

    /** Tài khoản người xem KHÔNG được xem: SubAdmin không xem Admin đầy đủ quyền; Admin xem tất cả */
    public function hiddenUsers(?User $viewer): Collection
    {
        if (AssistantAccess::isFullAdmin($viewer)) {
            return collect();
        }

        return $this->users->filter(fn ($u) => AssistantAccess::isFullAdmin($u));
    }

    public function canView(?User $viewer): bool
    {
        return AssistantAccess::isAdmin($viewer);
    }

    /** Người được chọn trong ô lọc (đã bỏ tài khoản bị ẩn) */
    public function people(?User $viewer): Collection
    {
        $hidden = $this->hiddenUsers($viewer)->keys()->all();

        return $this->users->reject(fn ($u) => in_array($u->id, $hidden, true))
            ->map(fn ($u) => (object) ['id' => $u->id, 'name' => $this->nameOf($u), 'email' => $u->email])->sortBy('name')->values();
    }

    public function nameOf($u): string
    {
        $dept = ['sales' => 'Sales', 'ke_toan' => 'Kế toán', 'ket_qua' => 'Kết quả', 'ky_thuat' => 'Kỹ thuật', 'dieu_phoi' => 'Điều phối', 'nhan_su' => 'Nhân sự'][$u->department] ?? null;
        $base = $u->display_name ?? $u->name;
        $role = AssistantAccess::isSubAdmin($u) ? 'SubAdmin' : (AssistantAccess::isAdmin($u) ? 'Admin' : $dept);

        return $base . ($role && !str_contains($base, $role) ? ' (' . $role . ')' : '') . ($u->email && $base === $u->name ? ' · ' . $u->email : '');
    }

    /**
     * @param array{nguoi?:?string, user_id?:?int, trang?:?string, loai?:?string, don?:?int} $f
     *   don: chỉ các việc liên quan một đơn hàng (nhật ký mới + toàn bộ lịch sử chỉnh sửa đơn cũ)
     * @return array{pham_vi:string, nguoi:list<array>, bi_an:int}
     */
    public function build(?User $viewer, Carbon $from, Carbon $to, array $f = [], int $maxItems = 150): array
    {
        $hidden = $this->hiddenUsers($viewer);
        $hiddenIds = $hidden->keys()->all();
        $hiddenEmails = $hidden->pluck('email')->filter()->map(fn ($e) => mb_strtolower($e))->all();
        $who = trim((string) ($f['nguoi'] ?? ''));
        $onlyUser = !empty($f['user_id']) ? (int) $f['user_id'] : null;
        $area = trim((string) ($f['trang'] ?? ''));
        $type = trim((string) ($f['loai'] ?? ''));
        $order = (int) ($f['don'] ?? 0) ?: null;
        $aboutOrder = fn (string $text) => (bool) preg_match('/#' . $order . '(?!\d)/u', $text);
        $cfg = config('activity');

        $matchPerson = function (?int $uid, ?string $name, ?string $email = null) use ($who, $onlyUser, $hiddenIds, $hiddenEmails): bool {
            if ($uid && in_array($uid, $hiddenIds, true)) {
                return false;
            }
            if ($email && in_array(mb_strtolower($email), $hiddenEmails, true)) {
                return false;
            }
            if ($onlyUser && $uid !== $onlyUser) {
                return false;
            }
            if ($who === '') {
                return true;
            }
            $u = $uid ? $this->users[$uid] ?? null : null;
            $hay = mb_strtolower(implode(' ', [$name, $email, $u?->email, $u ? $this->nameOf($u) : '']));

            return str_contains($hay, mb_strtolower($who));
        };

        $people = [];
        $key = fn (?int $uid, ?string $name) => $uid ? 'u' . $uid : 'n' . mb_strtolower((string) $name);
        $label = fn (?int $uid, ?string $name) => $uid && isset($this->users[$uid]) ? $this->nameOf($this->users[$uid]) : ($name ?: 'Không rõ');
        $stamp = function (string $k, Carbon $t) use (&$people) {
            $d = $t->format('Y-m-d');
            $people[$k]['_times'][$d][] = $t->getTimestamp();
        };

        // 1. Thao tác (activity_logs)
        $logs = ActivityLog::whereBetween('created_at', [$from, $to])->orderBy('created_at')->orderBy('id')->get();
        $prevIps = DB::table('activity_logs')->where('area', 'dang_nhap')->where('created_at', '<', $from)->where('created_at', '>=', $from->copy()->subDays(60))
            ->whereNotNull('ip')->get(['user_id', 'ip'])->groupBy('user_id')->map(fn ($g) => $g->pluck('ip')->unique()->all());
        foreach ($logs as $log) {
            $uid = $log->user_id ? (int) $log->user_id : null;
            $email = $uid ? null : (str_contains((string) $log->summary, '@') ? trim(preg_replace('/^.*\((.*)\).*$/u', '$1', $log->summary)) : null);
            if (!$matchPerson($uid, $log->user_name, $email)) {
                continue;
            }
            $k = $key($uid, $log->user_name ?? $email);
            $people[$k]['nguoi'] ??= $label($uid, $log->user_name ?? $email);
            $stamp($k, $log->created_at);
            $areaLabel = $log->areaLabel();
            $lines = $log->changeLines();
            $flags = $this->flags($log, $lines, $prevIps[$uid] ?? null, $cfg);
            if ($area !== '' && !in_array(mb_strtolower($area), [mb_strtolower($log->area), mb_strtolower($areaLabel)], true)) {
                continue;
            }
            if ($type !== '' && !$this->isType($log, $flags, $type)) {
                continue;
            }
            if ($order && !$aboutOrder($log->summary . ' ' . implode(' ', array_keys((array) $log->changes)))) {
                continue;
            }
            $people[$k]['thao_tac'][] = array_filter([
                'ts' => $log->created_at->getTimestamp(),
                'luc' => $log->created_at->format('d/m H:i'),
                'trang' => $areaLabel,
                'viec' => $log->summary,
                'thay_doi' => array_slice($lines, 0, 10) ?: null,
                'ip' => $log->area === 'dang_nhap' ? $log->ip : null,
                'thiet_bi' => $log->area === 'dang_nhap' ? $this->device((string) $log->user_agent) : null,
                'chu_y' => $flags ?: null,
            ]);
            $people[$k]['_counts'][$areaLabel] = ($people[$k]['_counts'][$areaLabel] ?? 0) + 1;
            foreach ($flags as $fl) {
                $people[$k]['_flags'][] = $log->created_at->format('d/m H:i') . ' · ' . $fl . ' — ' . $log->summary;
            }
        }

        // 2. Lượt mở trang (giờ vào / ra, trang đã mở)
        // (xem theo mã đơn thì không cần lượt mở trang)
        $visits = $order ? collect() : DB::table('activity_visits')->whereBetween('created_at', [$from, $to])->orderBy('created_at')->get(['user_id', 'route', 'created_at']);
        foreach ($visits as $v) {
            $uid = (int) $v->user_id;
            if (!$matchPerson($uid, null)) {
                continue;
            }
            $k = $key($uid, null);
            $people[$k]['nguoi'] ??= $label($uid, null);
            $stamp($k, Carbon::parse($v->created_at));
            [, $pageLabel] = RecordActivity::area($v->route);
            $people[$k]['_pages'][$pageLabel] = ($people[$k]['_pages'][$pageLabel] ?? 0) + 1;
        }

        // 3. Lịch sử chỉnh sửa đơn (bảng cũ history_edits):
        //    - xem theo mã đơn: toàn bộ lịch sử đơn đó (sau khi có nhật ký mới thì bỏ dòng chung "Sửa / Thêm đơn hàng" vì đã có bản chi tiết);
        //    - xem chung: chỉ những ngày trước khi có nhật ký mới.
        $since = DB::table('activity_visits')->min('created_at');
        $sinceAt = $since ? Carbon::parse($since) : Carbon::now()->addDay();
        $typeOk = $type === '' || in_array($type, ['them', 'sua', 'xoa'], true);
        $areaOk = $area === '' || in_array(mb_strtolower($area), ['don_hang', 'đơn hàng'], true);
        if ($typeOk && $areaOk && ($order || $from->lt($sinceAt))) {
            $byEmail = $this->users->keyBy(fn ($u) => mb_strtolower((string) $u->email));
            DB::table('history_edits')->where('created_at', '>=', $from)
                ->when($order, fn ($q) => $q->where('order_id', $order)->where('created_at', '<=', $to)
                    ->where(fn ($q) => $q->where('created_at', '<', $sinceAt)->orWhereNotIn('history_action', ['Sửa đơn hàng', 'Thêm đơn hàng'])),
                    fn ($q) => $q->where('created_at', $to->lt($sinceAt) ? '<=' : '<', $to->lt($sinceAt) ? $to : $sinceAt))
                ->orderBy('created_at')
                ->get(['order_id', 'user_name', 'history_action', 'created_at'])
                ->each(function ($h) use (&$people, $byEmail, $matchPerson, $key, $label, $stamp, $order) {
                    $u = $byEmail[mb_strtolower((string) $h->user_name)] ?? null;
                    if (!$matchPerson($u?->id, $h->user_name, $h->user_name)) {
                        return;
                    }
                    $k = $key($u?->id, $h->user_name);
                    $people[$k]['nguoi'] ??= $label($u?->id, $h->user_name);
                    $t = Carbon::parse($h->created_at);
                    if (!$order) {
                        $stamp($k, $t);
                    }
                    $people[$k]['thao_tac'][] = ['ts' => $t->getTimestamp(), 'luc' => $t->format('d/m H:i'), 'trang' => 'Đơn hàng',
                        'viec' => 'Đơn #' . $h->order_id . ': ' . $h->history_action . ' (lịch sử đơn)'];
                    $people[$k]['_counts']['Đơn hàng'] = ($people[$k]['_counts']['Đơn hàng'] ?? 0) + 1;
                });
        }

        // 4. Câu hỏi trợ lý AI (chỉ số câu)
        if (!$order) DB::table('assistant_messages')->join('assistant_conversations', 'assistant_conversations.id', '=', 'assistant_messages.conversation_id')
            ->where('assistant_messages.role', 'user')->whereBetween('assistant_messages.created_at', [$from, $to])
            ->groupBy('assistant_conversations.user_id', 'assistant_conversations.department')
            ->get([DB::raw('assistant_conversations.user_id as uid'), 'assistant_conversations.department', DB::raw('count(*) as n')])
            ->each(function ($r) use (&$people, $matchPerson, $key, $label) {
                if (!$matchPerson((int) $r->uid, null)) {
                    return;
                }
                $k = $key((int) $r->uid, null);
                $people[$k]['nguoi'] ??= $label((int) $r->uid, null);
                $people[$k]['cau_hoi_tro_ly'][config("claude.departments.{$r->department}.title") ?? $r->department] = (int) $r->n;
            });

        // Tổng hợp từng người
        $idle = (int) ($cfg['idle_minutes'] ?? 15) * 60;
        $list = collect($people)->map(function ($p) use ($idle, $maxItems, $area, $type, $order) {
            $days = [];
            foreach ($p['_times'] ?? [] as $d => $ts) {
                sort($ts);
                $active = 0;
                for ($i = 1; $i < count($ts); $i++) {
                    $gap = $ts[$i] - $ts[$i - 1];
                    $active += $gap <= $idle ? $gap : 0;
                }
                $days[] = ['ngay' => Carbon::parse($d)->format('d/m/Y'), 'vao_luc' => date('H:i', $ts[0]), 'thao_tac_cuoi' => date('H:i', end($ts)),
                    'hoat_dong' => self::duration($active + 60 * min(count($ts), 1))];
            }
            $items = collect($p['thao_tac'] ?? [])->sortBy('ts')->values()->all();

            return array_filter([
                'nguoi' => $p['nguoi'],
                'theo_ngay' => $days,
                'so_thao_tac' => count($items),
                'thao_tac_theo_trang' => $p['_counts'] ?? null,
                'trang_da_mo' => isset($p['_pages']) ? collect($p['_pages'])->sortDesc()->all() : null,
                'dang_chu_y' => $p['_flags'] ?? null,
                'cau_hoi_tro_ly' => $p['cau_hoi_tro_ly'] ?? null,
                'dong_thoi_gian' => count($items) > $maxItems ? array_slice($items, -$maxItems) : $items,
                'bi_cat_bot' => count($items) > $maxItems ? 'Chỉ hiện ' . $maxItems . ' thao tác gần nhất / ' . count($items) . ' — thu hẹp khoảng ngày hoặc lọc theo trang / loại' : null,
            ], fn ($v) => $v !== null && $v !== []);
        })->filter(fn ($p) => ($p['so_thao_tac'] ?? 0) > 0 || ($area === '' && $type === '' && !$order && (!empty($p['theo_ngay']) || !empty($p['cau_hoi_tro_ly']))))
            ->filter(fn ($p) => !$order || ($p['so_thao_tac'] ?? 0) > 0)
            ->sortByDesc(fn ($p) => $p['so_thao_tac'] ?? 0)->values();

        return [
            'pham_vi' => $from->isSameDay($to) ? 'Ngày ' . $from->format('d/m/Y') : 'Từ ' . $from->format('d/m/Y') . ' đến ' . $to->format('d/m/Y'),
            'nguoi' => $list->all(),
            'bi_an' => $hidden->count(),
            'nhat_ky_tu' => $since ? Carbon::parse($since)->format('d/m/Y H:i') : null,
        ];
    }

    /** Điểm đáng chú ý của một dòng */
    private function flags(ActivityLog $log, array $lines, ?array $knownIps, array $cfg): array
    {
        $out = [];
        if ($log->action === 'xoa') {
            $out[] = 'Xóa dữ liệu';
        }
        if ($log->action === 'dang_nhap_sai') {
            $out[] = 'Đăng nhập sai';
        }
        if ($log->action === 'dang_nhap' && $knownIps !== null && $log->ip && !in_array($log->ip, $knownIps, true)) {
            $out[] = 'Đăng nhập từ IP mới (' . $log->ip . ')';
        }
        foreach ((array) $log->changes as $field => $pair) {
            $label = trim(preg_replace('/^.*· /u', '', (string) $field));
            [$old, $new] = array_pad((array) $pair, 2, null);
            if (in_array($label, self::MONEY, true) && is_numeric(str_replace('.', '', (string) $old)) && is_numeric(str_replace('.', '', (string) $new))
                && abs((float) str_replace('.', '', (string) $new) - (float) str_replace('.', '', (string) $old)) >= ($cfg['big_money'] ?? 5000000)) {
                $out[] = 'Sửa tiền lớn (' . $label . ')';
                break;
            }
        }
        [$h1, $h2] = $cfg['work_hours'] ?? [7, 19];
        $t = $log->created_at;
        // Xem lịch buổi tối / Chủ nhật là bình thường (KTV, tài xế xem lịch hôm sau): không nêu "ngoài giờ"
        if (!in_array($log->action, ['dang_xuat', 'xem'], true) && ($t->isSunday() || $t->hour < $h1 || $t->hour >= $h2)) {
            $out[] = 'Ngoài giờ (' . ($t->isSunday() ? 'Chủ nhật ' : '') . $t->format('H:i') . ')';
        }

        return $out;
    }

    private function isType(ActivityLog $log, array $flags, string $type): bool
    {
        return match ($type) {
            'xoa' => $log->action === 'xoa',
            'them' => $log->action === 'them',
            'sua' => $log->action === 'sua',
            'tai_ve' => $log->action === 'tai_ve',
            'dang_nhap' => $log->area === 'dang_nhap',
            'sua_tien' => collect((array) $log->changes)->keys()->contains(fn ($k) => in_array(trim(preg_replace('/^.*· /u', '', (string) $k)), self::MONEY, true)),
            'bat_thuong' => (bool) $flags,
            default => true,
        };
    }

    private function device(string $ua): ?string
    {
        if ($ua === '') {
            return null;
        }
        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone', str_contains($ua, 'iPad') => 'iPad', str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows', str_contains($ua, 'Mac OS') => 'Mac', default => 'Khác',
        };
        $br = match (true) {
            str_contains($ua, 'Zalo') => 'Zalo', str_contains($ua, 'Edg/') => 'Edge', str_contains($ua, 'Chrome/') => 'Chrome', str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari', default => 'trình duyệt khác',
        };

        return $os . ' · ' . $br;
    }

    private static function duration(int $sec): string
    {
        $m = (int) round($sec / 60);

        return $m >= 60 ? intdiv($m, 60) . ' giờ ' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT) . ' phút' : $m . ' phút';
    }
}
