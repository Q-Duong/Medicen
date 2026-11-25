<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Accounting\CashForecast;
use App\Services\Accounting\CfoAnalytics;
use App\Services\Accounting\CreditPolicy;
use App\Services\Accounting\DebtCollection;
use App\Services\Accounting\ManagementReport;
use App\Services\Accounting\Receivables;
use App\Services\Claude\Tools\AccountantTools;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Bộ kiểm tra cố định cho trợ lý CFO: các nguồn số phải khớp nhau (công nợ, thu nợ, hạn mức, doanh thu, dòng tiền)
 * và mọi tool của Trợ lý Kế toán chạy được, đủ nhanh. Chạy sau mỗi lần sửa phần tài chính / trước khi deploy:
 *   php artisan cfo:check
 * Kết quả PASS / WARN / FAIL; có FAIL thì trả mã lỗi 1. Chỉ đọc dữ liệu (ghi_nho chỉ gọi "xem").
 */
class CfoCheck extends Command
{
    protected $signature = 'cfo:check {--slow=2 : Tool chạy quá số giây này = WARN}';

    protected $description = 'Kiểm tra số liệu trợ lý CFO khớp nhau và các tool chạy được';

    private array $rows = [];

    public function handle(Receivables $rec, CreditPolicy $credit, DebtCollection $collect, CashForecast $cash, CfoAnalytics $cfo, ManagementReport $mr, AccountantTools $tools): int
    {
        $user = User::where('name', 'Admin')->first();
        $res = [];

        // ---- Công nợ: một định nghĩa nợ dùng ở mọi nơi ----
        $s = $rec->summary();
        $this->eq('Nợ phải thu = tổng theo đơn vị', $s['total'], $s['by_unit']->sum('total'));
        $this->eq('Nợ phải thu = tổng hạn mức (CreditPolicy)', $s['total'], $credit->all()->sum('owe'));
        $this->eq('Nợ quá hạn = tổng Thu nợ (DebtCollection)', $s['overdue'], $collect->list()->sum('overdue'));
        $this->check('Nợ quá hạn ≤ nợ phải thu', $s['overdue'] <= $s['total'], number_format($s['overdue']) . ' / ' . number_format($s['total']));
        $this->eq('Tuổi nợ: các nhóm + chưa xuất HĐ = toàn bộ còn phải thu', $s['buckets']->sum('amount'), $rec->rows()->sum('owe'));
        $this->eq('Chưa xuất HĐ không nằm trong nợ', $rec->rows()->where('is_debt', true)->where('bucket', 'chua_xuat_hd')->count(), 0);

        foreach (['tuoi_no_tien_ket' => ['no_phai_thu', $s['total']], 'cong_no_web' => ['tong_con_no', $s['total']], 'theo_doi_thu_no' => ['tong_can_thu_no_qua_han', $s['overdue']]] as $t => [$key, $want]) {
            $res[$t] = $this->tool($tools, $t, [], $user);
            if ($res[$t] !== null) {
                $this->eq("Tool {$t}.{$key} khớp", $want, $res[$t][$key] ?? null);
            }
        }

        // ---- Doanh thu: báo cáo quản trị ↔ phân tích vận hành (tháng trước, đủ tháng) ----
        $from = Carbon::today()->subMonthNoOverflow()->startOfMonth();
        $to = $from->copy()->endOfMonth();
        // Mỗi đơn bỏ VAT theo đúng % VAT của đơn (đơn không VAT giữ nguyên): kiểm tra hai nơi dùng cùng tập đơn, không trùng đơn
        $o = $cfo->orderRows($from, $to);
        $exVat = $o->sum(function ($r) {
            $vat = (float) preg_replace('/[^\d.]/', '', (string) $r->order_vat);

            return $r->net / (1 + ($vat > 0 && $vat < 100 ? $vat / 100 : 0));
        });
        $xq = $mr->xqRevenue($from, $to);
        $diff = $exVat ? abs($xq - $exVat) / $exVat * 100 : 0;
        $this->check("Doanh thu XQ tháng {$from->format('m/Y')}: báo cáo quản trị = phân tích vận hành (bỏ VAT)", $diff <= 0.5,
            number_format($xq, 0) . ' vs ' . number_format($exVat, 0) . ' (lệch ' . round($diff, 2) . '%) · ' . $o->count() . ' đơn, '
            . round($o->filter(fn ($r) => (float) preg_replace('/[^\d.]/', '', (string) $r->order_vat) <= 0)->sum('net') / max(1, $o->sum('net')) * 100) . '% doanh thu không VAT');

        $report = $mr->build(Carbon::today()->startOfYear(), Carbon::today());
        $bad = collect($report['thang'])->filter(fn ($m) => abs($m['lai_lo_quan_tri'] - ($m['tong_doanh_thu'] - $m['tong_chi_phi'])) > 1)->pluck('thang')->all();
        $this->check('Báo cáo quản trị: lãi lỗ = doanh thu − chi phí từng tháng', !$bad, $bad ? 'lệch: ' . implode(', ', $bad) : count($report['thang']) . ' tháng');
        $this->check('Báo cáo quản trị: tháng ước tính có ghi nguồn', collect($report['thang'])->flatMap(fn ($m) => $m['dong'])->every(fn ($d) => !empty($d['nguon'])), count($report['so_uoc_tinh']) . ' dòng ước tính');

        // ---- Dòng tiền 13 tuần: cộng trừ đúng, nối tuần ----
        $f = $cash->build([]);
        $prev = $f['tien_dau_ky'];
        $errs = [];
        foreach ($f['tuan'] as $w) {
            if (abs($w['dau_ky'] - $prev) > 1 || abs($w['dau_ky'] + $w['tong_thu'] - $w['tong_chi'] - $w['cuoi_ky']) > 1
                || abs(array_sum($w['thu']) - $w['tong_thu']) > count($w['thu']) || abs(array_sum($w['chi']) - $w['tong_chi']) > count($w['chi'])) {
                $errs[] = 'tuần ' . $w['tuan'];
            }
            $prev = $w['cuoi_ky'];
        }
        $this->check('Dòng tiền: đầu kỳ + thu − chi = cuối kỳ, các tuần nối nhau', !$errs, $errs ? implode(', ', $errs) : count($f['tuan']) . ' tuần');
        $this->check('Dòng tiền: có nguồn tiền đầu kỳ', !empty($f['nguon_tien_dau_ky']), (string) $f['nguon_tien_dau_ky'], 'WARN');
        $g = $cash->build(['khoan_them' => [['loai' => 'chi', 'so_tien' => 100000000, 'ten' => 'kiểm tra']]]);
        $this->eq('Giả định chi thêm 100 tr làm cuối 13 tuần giảm đúng 100 tr', $f['tien_cuoi_13_tuan'] - 100000000, $g['tien_cuoi_13_tuan']);

        // ---- Mọi tool chạy được, đủ nhanh ----
        $samples = [
            'tong_quan_du_lieu' => ['tu_ngay' => Carbon::today()->startOfYear()->toDateString(), 'den_ngay' => Carbon::today()->toDateString()],
            'ghi_nho' => ['hanh_dong' => 'xem'],
            'gia_dinh_neu' => ['loai' => 'tang_gia', 'them_moi_ca' => 5000],
            'ket_qua_kinh_doanh' => ['tu_ngay' => Carbon::today()->startOfYear()->toDateString(), 'den_ngay' => Carbon::today()->toDateString(), 'chia_theo' => 'thang'],
            'phat_sinh_tai_khoan' => ['tai_khoan' => '642', 'tu_ngay' => Carbon::today()->startOfYear()->toDateString(), 'den_ngay' => Carbon::today()->toDateString()],
            'so_quy_ngan_hang' => ['tu_ngay' => Carbon::today()->startOfYear()->toDateString(), 'den_ngay' => Carbon::today()->toDateString()],
            'cong_no_doi_tac' => ['loai' => 'phai_thu'],
            'luong_thuc_tra' => ['tu_nam' => (int) date('Y') - 1, 'den_nam' => (int) date('Y')],
            'tim_but_toan' => ['tu_khoa' => 'lãi vay'],
        ];
        foreach ($tools->definitions() as $d) {
            if (!array_key_exists($d['name'], $res)) {
                $this->tool($tools, $d['name'], $samples[$d['name']] ?? [], $user);
            }
        }

        $this->table(['', 'Kiểm tra', 'Chi tiết'], $this->rows);
        $count = collect($this->rows)->countBy(fn ($r) => strip_tags($r[0]));
        $this->line(sprintf('PASS %d · WARN %d · FAIL %d', $count['PASS'] ?? 0, $count['WARN'] ?? 0, $count['FAIL'] ?? 0));

        return ($count['FAIL'] ?? 0) ? self::FAILURE : self::SUCCESS;
    }

    private function tool(AccountantTools $tools, string $name, array $in, ?User $user): ?array
    {
        $t = microtime(true);
        try {
            $r = $tools->call($name, $in, $user);
        } catch (Throwable $e) {
            $this->add('FAIL', "Tool {$name} chạy được", get_class($e) . ': ' . mb_strimwidth($e->getMessage(), 0, 120, '…'));

            return null;
        }
        $sec = microtime(true) - $t;
        $size = strlen(json_encode($r, JSON_UNESCAPED_UNICODE) ?: '');
        $level = $sec > (float) $this->option('slow') ? 'WARN' : ($size > 60000 ? 'WARN' : 'PASS');
        $this->add($level, "Tool {$name} chạy được", round($sec, 2) . ' s · ' . round($size / 1024, 1) . ' KB' . ($size > 60000 ? ' (kết quả lớn, tốn token)' : ''));

        return $r;
    }

    private function eq(string $label, $want, $got): void
    {
        $ok = $got !== null && abs((float) $want - (float) $got) <= 1;
        $this->add($ok ? 'PASS' : 'FAIL', $label, $ok ? number_format((float) $got) : 'cần ' . number_format((float) $want) . ', được ' . ($got === null ? 'null' : number_format((float) $got)));
    }

    private function check(string $label, bool $ok, string $detail = '', string $failLevel = 'FAIL'): void
    {
        $this->add($ok ? 'PASS' : $failLevel, $label, $detail);
    }

    private function add(string $level, string $label, string $detail): void
    {
        $color = ['PASS' => 'info', 'WARN' => 'comment', 'FAIL' => 'error'][$level];
        $this->rows[] = ["<{$color}>{$level}</{$color}>", $label, $detail];
    }
}
