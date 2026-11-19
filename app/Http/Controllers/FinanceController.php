<?php

namespace App\Http\Controllers;

use App\Services\Accounting\CashForecast;
use App\Services\Accounting\CfoAnalytics;
use App\Services\Accounting\Receivables;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Phân tích tài chính (Admin, Kế toán, SubAdmin chỉ xem):
 *  - Tiền kẹt & tuổi nợ: phải thu XQ theo hạn thanh toán, đơn đã chụp chưa xuất hóa đơn.
 *  - Lãi ước tính: doanh thu − giá vốn ước tính từng đơn, theo tháng / khách / xe.
 *  - Dòng tiền 13 tuần: dự báo số dư tiền từng tuần từ hóa đơn, Báo cáo thu chi, CT, đơn mới.
 * Cùng số liệu với các tool của Trợ lý Kế toán (tuoi_no_tien_ket, phan_tich_van_hanh, du_bao_dong_tien).
 */
class FinanceController extends Controller
{
    public const TABS = ['tien-ket' => 'Tiền kẹt & tuổi nợ', 'lai-uoc-tinh' => 'Lãi ước tính', 'dong-tien' => 'Dòng tiền 13 tuần'];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'tien-ket';
        $data = ['tab' => $tab, 'tabs' => self::TABS];

        if ($tab === 'tien-ket') {
            $data['r'] = app(Receivables::class)->summary();
        } elseif ($tab === 'lai-uoc-tinh') {
            $to = $this->date($request->query('den')) ?? Carbon::today();
            $from = $this->date($request->query('tu')) ?? $to->copy()->subMonthsNoOverflow(3)->addDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
            $data += ['from' => $from, 'to' => $to, 'a' => app(CfoAnalytics::class)->analyze($from, $to, true)];
        } else {
            $opt = [
                'so_du' => $request->query('so_du'),
                'qua_han_pct' => $request->query('qua_han', 50),
                'chua_hd_pct' => $request->query('chua_hd', 80),
                'don_moi' => $request->query('don_moi', '1') === '1',
                'nguong' => (int) preg_replace('/\D/', '', (string) $request->query('nguong', '100000000')) ?: 100000000,
            ];
            $data += ['f' => app(CashForecast::class)->build($opt), 'opt' => $opt];
        }

        return view('pages.admin.finance.index', $data);
    }

    private function date($v): ?Carbon
    {
        try {
            return $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
