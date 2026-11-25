<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Báo cáo quản trị lãi / lỗ theo tháng — MỘT công thức cố định để giám đốc hỏi lúc nào cũng ra cùng số
 * (trợ lý không tự ghép). Mỗi dòng mỗi tháng có cờ "thật / ước tính" và nguồn.
 *
 *  Doanh thu XQ (giá trên đơn)    = Thành tiền − chiết khấu các đơn trên web (theo ngày chụp), chia (1 + VAT%) chỉ khi đơn ghi % VAT (hiện đơn chỉ ghi có / không → giữ nguyên)  [đơn web — thật]
 *  Medicen thu về từ CT           = Σ medicen_collect (trang Công nợ CT)                                                    [thật; tháng chưa nhập → bình quân 3 tháng]
 *  Chi phí CT Medicen chịu        = Σ (medicen_collect − medicen_share)  (≈ lương CT; nên KHÔNG cộng lương CT lần nữa)       [như trên]
 *  Lương XQ thực trả              = file lương (cột XQ)                                                                    [thật; thiếu → bình quân 3 tháng]
 *  Vật tư, giá vốn khác           = TK 632 (sổ MISA)                                                                       [sổ; thiếu → bình quân 3 tháng có sổ]
 *  Lương văn phòng / khác         = TK 6421 − lương XQ − lương CT (không âm)                                               [sổ]
 *  Khấu hao                       = TK 6424 (+ 6274)                                                                      [sổ]
 *  Chi phí quản lý khác           = TK 6422, 6423, 6425, 6426, 6427, 6428 (+ 627 trừ 6274)                                 [sổ]
 *  Lãi vay, chi phí tài chính     = TK 635 − TK 515                                                                       [sổ]
 *  ⇒ Lãi / lỗ quản trị (trước thu nhập / chi phí khác và thuế); thu nhập − chi phí khác (711 − 811, thanh lý tài sản) để riêng.
 */
class ManagementReport
{
    public const LINES = [
        'dt_xq' => 'Doanh thu XQ (giá trên đơn)',
        'dt_ct' => 'Medicen thu về từ CT',
        'cp_ct' => 'Chi phí CT Medicen chịu (≈ lương CT)',
        'luong_xq' => 'Lương XQ thực trả',
        'vat_tu' => 'Vật tư, giá vốn khác (TK 632)',
        'luong_vp' => 'Lương văn phòng / khác (TK 6421 − lương XQ − lương CT)',
        'khau_hao' => 'Khấu hao (TK 6424)',
        'ql_khac' => 'Chi phí quản lý khác (TK 6422–6428)',
        'tai_chinh' => 'Lãi vay, chi phí tài chính (TK 635 − 515)',
    ];

    /** @return array{ky:string, thang:list<array>, tong:array, cach_tinh:array, so_uoc_tinh:list<string>} */
    public function build(Carbon $from, Carbon $to): array
    {
        $to = $to->copy()->min(Carbon::today())->endOfDay();
        $from = $from->copy()->startOfMonth();
        $misaEnd = DB::table('acc_journal_entries')->max('posted_on');
        $misaEndAt = $misaEnd ? Carbon::parse($misaEnd) : null;
        // Tháng có đủ sổ MISA = tháng kết thúc trước / bằng ngày cuối có sổ
        $hasBooks = fn (Carbon $m) => $misaEndAt && $m->copy()->endOfMonth()->lte($misaEndAt->copy()->endOfDay());

        $months = [];
        for ($m = $from->copy(); $m->lte($to); $m->addMonthNoOverflow()) {
            $months[] = $m->copy();
        }
        $books = $this->books($months[0]->copy()->subMonths(3), $to);
        $salary = DB::table('acc_salaries')->where('kind', 'luong')->get()->keyBy(fn ($r) => sprintf('%04d-%02d', $r->year, $r->month));
        $ct = DB::table('ct_receivables')->selectRaw("CONCAT(year,'-',LPAD(month,2,'0')) ym, SUM(medicen_collect) collect, SUM(medicen_collect - medicen_share) cost, SUM(medicen_share) share")
            ->groupBy('ym')->get()->keyBy('ym');

        // Bình quân 3 tháng gần nhất có dữ liệu (trước tháng cần ước tính)
        $avg = function (callable $has, callable $val, Carbon $before) {
            $vals = [];
            for ($m = $before->copy()->subMonthNoOverflow()->startOfMonth(), $i = 0; $i < 24 && count($vals) < 3; $i++, $m->subMonthNoOverflow()) {
                if ($has($m)) {
                    $vals[] = $val($m);
                }
            }

            return $vals ? [array_sum($vals) / count($vals), count($vals)] : [null, 0];
        };

        $out = [];
        $estimated = [];
        foreach ($months as $m) {
            $ym = $m->format('Y-m');
            $end = $m->copy()->endOfMonth()->min($to);
            $partial = !$end->isSameDay($m->copy()->endOfMonth());
            $frac = $partial ? $end->day / $m->daysInMonth : 1;
            $row = ['thang' => $m->format('m/Y') . ($partial ? ' (đến ' . $end->format('d/m') . ')' : ''), 'dong' => []];
            $put = function (string $k, ?float $v, string $src, bool $est = false) use (&$row, &$estimated, $m) {
                $row['dong'][$k] = ['so_tien' => $v === null ? null : (int) round($v), 'nguon' => $src, 'uoc_tinh' => $est];
                if ($est) {
                    $estimated[] = $m->format('m/Y') . ': ' . self::LINES[$k];
                }
            };

            // Doanh thu XQ từ đơn web
            $put('dt_xq', $this->xqRevenue($m, $end), 'Đơn hàng trên web (Thành tiền − chiết khấu; chưa tách VAT vì cột VAT của đơn chỉ ghi có / không)' . ($partial ? ', đến ' . $end->format('d/m') : ''));

            // CT
            if (isset($ct[$ym])) {
                $put('dt_ct', (float) $ct[$ym]->collect, 'Công nợ CT tháng ' . $m->format('m/Y'));
                $put('cp_ct', (float) $ct[$ym]->cost, 'Công nợ CT (thu về − phần được chia)');
            } else {
                [$a, $n] = $avg(fn ($x) => isset($ct[$x->format('Y-m')]), fn ($x) => (float) $ct[$x->format('Y-m')]->collect, $m);
                [$b] = $avg(fn ($x) => isset($ct[$x->format('Y-m')]), fn ($x) => (float) $ct[$x->format('Y-m')]->cost, $m);
                $put('dt_ct', $a === null ? null : $a * $frac, 'Ước tính: bình quân ' . $n . ' tháng CT gần nhất (chưa nhập CT tháng này)', true);
                $put('cp_ct', $b === null ? null : $b * $frac, 'Ước tính: bình quân ' . $n . ' tháng CT gần nhất', true);
            }

            // Lương XQ
            if (isset($salary[$ym])) {
                $put('luong_xq', (float) $salary[$ym]->xq, 'File lương thực trả tháng ' . $m->format('m/Y'));
            } else {
                [$a, $n] = $avg(fn ($x) => isset($salary[$x->format('Y-m')]), fn ($x) => (float) $salary[$x->format('Y-m')]->xq, $m);
                $put('luong_xq', $a === null ? null : $a * $frac, 'Ước tính: bình quân lương XQ ' . $n . ' tháng gần nhất có file lương', true);
            }

            // Các khoản theo sổ MISA
            $bookLine = function (string $k, callable $calc, string $label) use ($m, $books, $hasBooks, $avg, $frac, $put) {
                if ($hasBooks($m)) {
                    $put($k, $calc($books[$m->format('Y-m')] ?? [], $m), 'Sổ MISA ' . $label . ' tháng ' . $m->format('m/Y'));
                } else {
                    [$a, $n] = $avg($hasBooks, fn ($x) => $calc($books[$x->format('Y-m')] ?? [], $x), $m);
                    $put($k, $a === null ? null : $a * $frac, 'Ước tính: bình quân ' . $label . ' ' . $n . ' tháng gần nhất có sổ MISA', true);
                }
            };
            $acc = fn (array $b, array $codes) => array_sum(array_map(fn ($c) => $b[$c] ?? 0, $codes));
            $bookLine('vat_tu', fn ($b) => $acc($b, ['632']), 'TK 632');
            $bookLine('luong_vp', function ($b, Carbon $x) use ($acc, $salary, $ct) {
                $k = $x->format('Y-m');
                $sal = isset($salary[$k]) ? (float) $salary[$k]->xq + (float) $salary[$k]->ct : 0;

                return max(0, $acc($b, ['6421']) - $sal);
            }, 'TK 6421 trừ lương XQ, CT');
            $bookLine('khau_hao', fn ($b) => $acc($b, ['6424', '6274']), 'TK 6424');
            $bookLine('ql_khac', fn ($b) => $acc($b, ['6422', '6423', '6425', '6426', '6427', '6428', '6271', '6272', '6273', '6277', '6278']), 'TK 6422–6428');
            $bookLine('tai_chinh', fn ($b) => $acc($b, ['635']) - $acc($b, ['515']), 'TK 635 − 515');

            $v = fn ($k) => (float) ($row['dong'][$k]['so_tien'] ?? 0);
            $row['tong_doanh_thu'] = (int) round($v('dt_xq') + $v('dt_ct'));
            $row['tong_chi_phi'] = (int) round($v('cp_ct') + $v('luong_xq') + $v('vat_tu') + $v('luong_vp') + $v('khau_hao') + $v('ql_khac') + $v('tai_chinh'));
            $row['lai_lo_quan_tri'] = $row['tong_doanh_thu'] - $row['tong_chi_phi'];
            $row['lai_ct'] = (int) round($v('dt_ct') - $v('cp_ct'));
            $row['co_so_uoc_tinh'] = collect($row['dong'])->contains(fn ($d) => $d['uoc_tinh']);
            // Thu nhập / chi phí khác (thanh lý tài sản…) chỉ khi có sổ, để riêng
            $row['thu_nhap_tru_chi_phi_khac_theo_so'] = $hasBooks($m) ? (int) round($acc($books[$ym] ?? [], ['711']) - $acc($books[$ym] ?? [], ['811'])) : null;
            $out[] = $row;
        }

        $sum = fn ($k) => collect($out)->sum(fn ($r) => (int) ($r['dong'][$k]['so_tien'] ?? 0));
        $total = ['dong' => collect(self::LINES)->map(fn ($label, $k) => $sum($k))->all(),
            'tong_doanh_thu' => collect($out)->sum('tong_doanh_thu'), 'tong_chi_phi' => collect($out)->sum('tong_chi_phi'),
            'lai_lo_quan_tri' => collect($out)->sum('lai_lo_quan_tri'), 'lai_ct' => collect($out)->sum('lai_ct'),
            'thu_nhap_tru_chi_phi_khac_theo_so' => collect($out)->sum(fn ($r) => (int) ($r['thu_nhap_tru_chi_phi_khac_theo_so'] ?? 0))];

        return [
            'ky' => $from->format('m/Y') . ' – ' . $to->format('d/m/Y'),
            'nhan' => self::LINES,
            'thang' => $out,
            'tong' => $total,
            'so_uoc_tinh' => $estimated,
            'du_lieu' => ['so_misa_den' => $misaEnd, 'luong_den' => $salary->keys()->max(), 'ct_den' => $ct->keys()->max()],
            'cach_tinh' => [
                'Doanh thu XQ = Thành tiền − chiết khấu các đơn trên web theo ngày chụp, chia (1 + VAT của từng đơn).',
                'CT = số Medicen thu về (trang Công nợ CT); chi phí CT Medicen chịu = thu về − phần được chia (≈ lương CT), nên lương CT không cộng lần nữa.',
                'Lương XQ = file lương thực trả. Lương văn phòng = TK 6421 trên sổ trừ lương XQ và CT.',
                'Vật tư, khấu hao, chi phí quản lý khác, lãi vay = sổ MISA theo tài khoản.',
                'Tháng thiếu dữ liệu: bình quân 3 tháng gần nhất có dữ liệu (tháng chưa hết tính theo số ngày đã qua); các dòng này có uoc_tinh = true.',
                'Lãi / lỗ quản trị chưa gồm thu nhập / chi phí khác (thanh lý tài sản — xem thu_nhap_tru_chi_phi_khac_theo_so) và thuế TNDN.',
            ],
        ];
    }

    /** Doanh thu XQ theo đơn trên web; chỉ tách VAT khi cột VAT của đơn ghi % (hiện chỉ ghi có / không) */
    public function xqRevenue(Carbon $from, Carbon $to): float
    {
        return (float) DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->where('orders.status_id', '!=', 0)
            ->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()])
            ->get(['orders.order_price', 'orders.order_discount', 'orders.order_vat'])
            ->sum(function ($o) {
                $vat = (float) preg_replace('/[^\d.]/', '', (string) $o->order_vat);

                return ((float) $o->order_price - (float) $o->order_discount) / (1 + ($vat > 0 && $vat < 100 ? $vat / 100 : 0));
            });
    }

    /** Phát sinh theo tài khoản 3–4 số, theo tháng: ['2026-05' => ['632' => …, '6421' => …, '635' => …]] */
    private function books(Carbon $from, Carbon $to): array
    {
        $rows = DB::select("
            SELECT DATE_FORMAT(posted_on, '%Y-%m') ym, acc, SUM(no) no, SUM(co) co FROM (
                SELECT posted_on, debit_account acc, amount no, 0 co FROM acc_journal_entries
                    WHERE posted_on BETWEEN ? AND ? AND credit_account NOT LIKE '911%' AND debit_account REGEXP '^(515|6|711|811)'
                UNION ALL
                SELECT posted_on, credit_account acc, 0 no, amount co FROM acc_journal_entries
                    WHERE posted_on BETWEEN ? AND ? AND debit_account NOT LIKE '911%' AND credit_account REGEXP '^(515|6|711|811)'
            ) x GROUP BY ym, acc", [$from->toDateString(), $to->toDateString(), $from->toDateString(), $to->toDateString()]);
        $out = [];
        foreach ($rows as $r) {
            $income = str_starts_with($r->acc, '515') || str_starts_with($r->acc, '711');
            $val = $income ? $r->co - $r->no : $r->no - $r->co;
            // Ghi cho cả mã 3 số và 4 số (vd 6421 → '642' và '6421')
            foreach (array_unique([substr($r->acc, 0, 3), substr($r->acc, 0, 4)]) as $code) {
                $out[$r->ym][$code] = ($out[$r->ym][$code] ?? 0) + $val;
            }
        }

        return $out;
    }
}
