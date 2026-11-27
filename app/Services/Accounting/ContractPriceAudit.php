<?php

namespace App\Services\Accounting;

use App\Builders\AccountantBuilder;
use App\Http\Controllers\AccountantController;
use App\Http\Middleware\PageAccess;
use App\Models\Accountant;
use App\Models\HistoryEdit;
use App\Models\User;
use App\Services\Claude\AssistantAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Kế toán nhờ trợ lý: "các đơn đang lọc ở trang Công nợ đã đúng giá hợp đồng chưa, chưa đúng thì sửa".
 *
 *  - rememberFilter(): khung chat nổi gửi bộ lọc trang Công nợ lúc hỏi; lấy ĐÚNG danh sách đơn đang hiện (cùng hàm lọc của trang)
 *    và nhớ 3 giờ theo người dùng, để tool đối chiếu "các đơn đang lọc".
 *  - check(): so Thành tiền từng đơn với giá hợp đồng (ContractPricing::checkOrder — cùng cách tính với gợi ý trên form đơn hàng).
 *  - apply(): sửa giá theo hợp đồng hoặc theo số kế toán đưa, đi qua đúng đường lưu của trang Công nợ
 *    (tính lại Chiết khấu, Lợi nhuận, Còn nợ; đơn chuyển "Đã cập nhật doanh thu"; đơn đã chốt thì không sửa), ghi nhật ký.
 */
class ContractPriceAudit
{
    public const MAX_ORDERS = 400;

    public function __construct(private ContractPricing $pricing)
    {
    }

    // ------------------------------------------------------------------
    // Bộ lọc trang Công nợ
    // ------------------------------------------------------------------

    private static function key(int $userId): string
    {
        return "assistant:debt_filter:{$userId}";
    }

    /** Lưu danh sách đơn đang hiện ở trang Công nợ theo bộ lọc trang gửi lên; trả về dòng mô tả cho trợ lý */
    public function rememberFilter(User $user, array $filters): ?string
    {
        $filters = array_filter($filters, fn ($v, $k) => is_string($k) && (is_scalar($v) || is_array($v)) && !in_array($k, ['current_count', 'cursor', 'page'], true), ARRAY_FILTER_USE_BOTH);
        $filters['year'] = (string) ($filters['year'] ?? now()->year);
        $filters['type'] = (string) ($filters['type'] ?? 'all');
        try {
            $ids = AccountantBuilder::getAccountantByFilter($filters)->orderBy('ord_start_day')->limit(self::MAX_ORDERS + 1)->pluck('orders.id')->map(fn ($i) => (int) $i)->unique()->values()->all();
        } catch (\Throwable) {
            return null;
        }
        $desc = $this->describeFilter($filters);
        Cache::put(self::key($user->id), ['ids' => array_slice($ids, 0, self::MAX_ORDERS), 'more' => count($ids) > self::MAX_ORDERS, 'desc' => $desc, 'at' => now()->toDateTimeString()], now()->addHours(3));

        return 'trang Công nợ đang lọc ' . ($desc ?: 'không có bộ lọc nào (cả năm ' . $filters['year'] . ')') . ' → ' . (count($ids) > self::MAX_ORDERS ? 'hơn ' . self::MAX_ORDERS : count($ids)) . ' đơn đang hiện'
            . ' (đối chiếu giá hợp đồng các đơn này: tool kiem_tra_gia_hop_dong, pham_vi "trang_dang_loc")';
    }

    private function describeFilter(array $f): string
    {
        $labels = ['unit_abbreviation' => 'đơn vị', 'unit_name' => 'tên đơn vị', 'ord_start_day' => 'ngày chụp', 'ord_cty_name' => 'công ty', 'order_id' => 'mã đơn',
            'status_id' => 'trạng thái', 'accountant_number' => 'số HĐ', 'accountant_month' => 'tháng', 'order_group' => 'nhóm đi chung', 'car_name' => 'xe',
            'accountant_status' => 'đã thanh toán', 'order_vat' => 'VAT', 'ord_type' => 'loại'];
        $parts = [];
        if (($f['year'] ?? 'all') !== 'all') {
            $parts[] = 'năm ' . $f['year'];
        }
        foreach ($f as $k => $v) {
            if (in_array($k, ['year', 'type'], true) || $v === '' || $v === []) {
                continue;
            }
            $vals = array_map('strval', (array) $v);
            $parts[] = ($labels[$k] ?? $k) . ': ' . (count($vals) > 6 ? implode(', ', array_slice($vals, 0, 6)) . '… (' . count($vals) . ' giá trị)' : implode(', ', $vals));
        }

        return mb_substr(implode('; ', $parts), 0, 300);
    }

    // ------------------------------------------------------------------
    // Đối chiếu
    // ------------------------------------------------------------------

    /**
     * @param array{pham_vi?:string, ma_don?:array, don_vi?:string, tu_ngay?:string, den_ngay?:string} $in
     */
    public function check(array $in, User $user): array
    {
        $scope = $this->resolve($in, $user);
        if (isset($scope['loi'])) {
            return $scope;
        }
        $orders = $this->pricing->orders(fn ($q) => $q->whereIn('orders.id', $scope['ids']))->keyBy('id');
        $extra = DB::table('orders')->leftJoin('accountants', 'accountants.order_id', '=', 'orders.id')->whereIn('orders.id', $orders->keys())
            ->get(['orders.id', 'orders.status_id', 'orders.order_cost', 'orders.order_all_in_one', 'accountants.accountant_number', 'accountants.accountant_amount_paid'])->keyBy('id');

        $groups = ['thieu' => [], 'du' => [], 'khong_co_hop_dong' => [], 'khong_tinh' => []];
        $ok = 0;
        $noContractUnits = [];
        foreach ($orders as $o) {
            $r = $this->pricing->checkOrder($o);
            $x = $extra[$o->id] ?? null;
            $row = [
                'ma_don' => (int) $o->id, 'don_vi' => $o->unit_abbreviation, 'ngay_chup' => substr((string) $o->ord_start_day, 0, 10),
                'cong_ty' => $o->ord_cty_name ?: null, 'so_ca' => (int) $o->order_quantity, 'thanh_tien_hien_tai' => (int) $o->order_price,
                'don_gia_hien_tai' => $x && !(int) $x->order_all_in_one ? (int) $x->order_cost : null,
            ];
            if (!$r) {
                $groups['khong_co_hop_dong'][] = $row;
                $noContractUnits[$o->unit_abbreviation ?: '?'] = ($noContractUnits[$o->unit_abbreviation ?: '?'] ?? 0) + 1;
                continue;
            }
            if ($r['status'] === 'khop') {
                $ok++;
                continue;
            }
            $row += array_filter([
                'hop_dong' => $r['contract_no'] ?: '#' . $r['contract_id'],
                'gia_theo_hop_dong' => $r['expected'],
                'chenh_lech' => $r['diff'],
                'don_gia_ca_theo_hop_dong' => $this->perCase($r, (int) $o->order_quantity),
                'can_cu' => $r['basis'],
                'nhom_di_chung' => $r['group'],
                'da_xuat_hoa_don' => $x && $x->accountant_number ? 'Số ' . $x->accountant_number . ' — sửa giá thì phải điều chỉnh hóa đơn' : null,
                'da_thu_tien' => $x && (int) $x->accountant_amount_paid ? (int) $x->accountant_amount_paid : null,
                'khong_sua_duoc' => $x && (int) $x->status_id === 3 ? 'Đơn đã chốt (Hoàn thành)' : null,
            ], fn ($v) => $v !== null);
            $groups[$r['status']][] = $row;
        }
        $skipped = count($scope['ids']) - $orders->count();

        return array_filter([
            'pham_vi' => $scope['desc'],
            'tong_don_kiem_tra' => $orders->count(),
            'bo_qua' => $skipped > 0 ? $skipped . ' dòng phụ thu / 0 ca / đơn hủy (không có giá hợp đồng để so)' : null,
            'con_nhieu_hon' => !empty($scope['more']) ? 'Bộ lọc có hơn ' . self::MAX_ORDERS . ' đơn, chỉ kiểm tra ' . self::MAX_ORDERS . ' đơn đầu (theo ngày chụp) — nên lọc hẹp hơn' : null,
            'dung_gia' => $ok,
            'thu_thieu' => array_slice($groups['thieu'], 0, 80),
            'so_don_thu_thieu' => count($groups['thieu']),
            'tong_thu_thieu' => -array_sum(array_column($groups['thieu'], 'chenh_lech')),
            'thu_nhieu_hon' => array_slice($groups['du'], 0, 40),
            'so_don_thu_nhieu_hon' => count($groups['du']),
            'khong_tinh_duoc' => array_slice($groups['khong_tinh'], 0, 20),
            'chua_co_hop_dong' => $noContractUnits ? collect($noContractUnits)->map(fn ($n, $u) => "{$u} ({$n} đơn)")->values()->all() : null,
            'quy_uoc' => 'So Thành tiền (đã gồm VAT) với giá hợp đồng, lệch dưới ' . (ContractPricing::TOLERANCE * 100) . '% coi là đúng. '
                . 'Thu NHIỀU hơn có thể do cộng in phim / phụ thu khoảng cách — chỉ sửa khi kế toán nói rõ. Hợp đồng trọn gói theo đợt không ghi giá → không tính được. '
                . 'Nhóm đi chung: bậc giá theo tổng số ca cả nhóm, chia theo số ca từng đơn.',
            'co_the_sua' => $this->canEdit($user) ? 'Muốn sửa: liệt kê thay đổi, chờ kế toán đồng ý rồi gọi sua_gia_don.' : 'Tài khoản này chỉ xem, không sửa giá được.',
        ], fn ($v) => $v !== null && $v !== []);
    }

    private function perCase(array $r, int $qty): ?int
    {
        return $r['per_case'] && $qty && $r['per_case'] * $qty === $r['expected'] ? (int) $r['per_case'] : null;
    }

    /** Danh sách mã đơn theo phạm vi tool nhận */
    private function resolve(array $in, User $user): array
    {
        $mode = (string) ($in['pham_vi'] ?? '');
        if ($mode === 'ma_don' || (!$mode && !empty($in['ma_don']))) {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($in['ma_don'] ?? [])))));
            if (!$ids) {
                return ['loi' => 'Chưa có mã đơn nào.'];
            }

            return ['ids' => array_slice($ids, 0, self::MAX_ORDERS), 'desc' => 'Các đơn ' . implode(', ', array_map(fn ($i) => "#{$i}", array_slice($ids, 0, 20))) . (count($ids) > 20 ? '…' : '')];
        }
        if ($mode === 'don_vi' || (!$mode && (!empty($in['don_vi']) || !empty($in['tu_ngay'])))) {
            $from = $this->date($in['tu_ngay'] ?? null);
            $to = $this->date($in['den_ngay'] ?? null);
            $units = !empty($in['don_vi']) ? $this->pricing->findUnits((string) $in['don_vi'], 5) : collect();
            if (!empty($in['don_vi']) && $units->isEmpty()) {
                return ['loi' => 'Không tìm thấy đơn vị "' . $in['don_vi'] . '".'];
            }
            if ($units->count() > 1 && !$units->contains(fn ($u) => mb_strtolower((string) $u->unit_abbreviation) === mb_strtolower(trim((string) $in['don_vi'])))) {
                return ['loi' => 'Có nhiều đơn vị khớp "' . $in['don_vi'] . '", hỏi lại kế toán chọn đơn vị nào.', 'cac_don_vi' => $units->map(fn ($u) => $u->unit_abbreviation . ' — ' . $u->unit_name)->all()];
            }
            if ($units->count() > 1) {
                $units = $units->filter(fn ($u) => mb_strtolower((string) $u->unit_abbreviation) === mb_strtolower(trim((string) $in['don_vi'])));
            }
            if ($units->isEmpty() && !$from) {
                return ['loi' => 'Cần đơn vị hoặc khoảng ngày chụp.'];
            }
            $from ??= Carbon::today()->startOfYear();
            $to ??= Carbon::today()->addDays(60);
            $ids = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')->join('accountants', 'accountants.order_id', '=', 'orders.id')
                ->when($units->isNotEmpty(), fn ($q) => $q->whereIn('orders.unit_id', $units->pluck('id')))
                ->whereBetween('order_details.ord_start_day', [$from->toDateString(), $to->toDateString()])
                ->orderBy('order_details.ord_start_day')->limit(self::MAX_ORDERS + 1)->pluck('orders.id')->map(fn ($i) => (int) $i)->unique()->values()->all();

            return ['ids' => array_slice($ids, 0, self::MAX_ORDERS), 'more' => count($ids) > self::MAX_ORDERS,
                'desc' => ($units->isNotEmpty() ? 'Đơn vị ' . $units->pluck('unit_abbreviation')->implode(', ') . ', ' : '') . 'ngày chụp ' . $from->format('d/m/Y') . ' → ' . $to->format('d/m/Y')];
        }
        $saved = Cache::get(self::key($user->id));
        if (!$saved) {
            return ['loi' => 'Chưa biết trang Công nợ đang lọc gì (chỉ biết khi hỏi từ khung chat nổi ngay trên trang Công nợ). Hỏi kế toán đơn vị / khoảng ngày, hoặc bảo họ lọc rồi hỏi lại trong khung chat.'];
        }

        return ['ids' => $saved['ids'], 'more' => $saved['more'], 'desc' => 'Các đơn đang lọc ở trang Công nợ' . ($saved['desc'] ? ' (' . $saved['desc'] . ')' : '') . ', lúc ' . substr($saved['at'], 11, 5)];
    }

    private function date($v): ?Carbon
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        try {
            return preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $v) ? Carbon::createFromFormat('d/m/Y', $v)->startOfDay() : Carbon::parse($v)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Sửa giá
    // ------------------------------------------------------------------

    /** Kế toán (và Admin) sửa được; giám đốc (SubAdmin) và Sales chỉ xem trang Công nợ kế toán */
    public function canEdit(?User $user): bool
    {
        return $user && (AssistantAccess::isFullAdmin($user) || (!AssistantAccess::isSubAdmin($user) && PageAccess::allows($user, ['debt_accountant'])));
    }

    /**
     * @param list<array{ma_don:int, theo?:string, don_gia_ca?:int, thanh_tien?:int}> $items
     */
    public function apply(array $items, User $user): array
    {
        if (!$this->canEdit($user)) {
            return ['loi' => 'Tài khoản này không được sửa giá ở trang Công nợ (chỉ Kế toán / Admin).'];
        }
        $items = array_slice($items, 0, 100);
        $done = [];
        $failed = [];
        $by = $user->display_name ?? $user->name;
        foreach ($items as $it) {
            $id = (int) ($it['ma_don'] ?? 0);
            $o = $this->pricing->orders(fn ($q) => $q->where('orders.id', $id))->first();
            $acc = Accountant::where('order_id', $id)->first();
            if (!$o || !$acc) {
                $failed[] = ['ma_don' => $id, 'ly_do' => 'Không có đơn này ở trang Công nợ (hoặc là dòng phụ thu / 0 ca / đơn hủy).'];
                continue;
            }
            $qty = (int) $o->order_quantity;
            $theo = (string) ($it['theo'] ?? 'hop_dong');
            if ($theo === 'hop_dong') {
                $r = $this->pricing->checkOrder($o);
                if (!$r || $r['expected'] === null) {
                    $failed[] = ['ma_don' => $id, 'ly_do' => $r ? 'Hợp đồng không ghi giá tính được cho đơn này.' : 'Đơn vị chưa có hợp đồng áp dụng vào ngày chụp.'];
                    continue;
                }
                $perCase = $this->perCase($r, $qty);
                $price = (int) $r['expected'];
                $why = 'theo hợp đồng ' . ($r['contract_no'] ?: '#' . $r['contract_id']) . ($r['basis'] ? ' (' . $r['basis'] . ')' : '');
            } elseif (!empty($it['don_gia_ca'])) {
                $perCase = (int) $it['don_gia_ca'];
                $price = $perCase * $qty;
                $why = 'theo đơn giá kế toán đưa';
            } elseif (!empty($it['thanh_tien'])) {
                $perCase = null;
                $price = (int) $it['thanh_tien'];
                $why = 'theo thành tiền kế toán đưa (trọn gói)';
            } else {
                $failed[] = ['ma_don' => $id, 'ly_do' => 'Thiếu đơn giá / ca hoặc thành tiền mới.'];
                continue;
            }
            if ($price <= 0) {
                $failed[] = ['ma_don' => $id, 'ly_do' => 'Giá mới phải lớn hơn 0.'];
                continue;
            }
            $old = (int) $o->order_price;
            if ($old === $price && (!$perCase || (int) $acc->order?->order_cost === $perCase)) {
                $failed[] = ['ma_don' => $id, 'ly_do' => 'Giá đã đúng, không cần sửa.'];
                continue;
            }

            $res = app(AccountantController::class)->saveRowValues($acc, ['order_cost' => $perCase ?? 0, 'order_price' => $price]);
            if (empty($res['success'])) {
                $failed[] = ['ma_don' => $id, 'ly_do' => $res['message'] ?? 'Không lưu được.'];
                continue;
            }
            $order = $acc->order()->first();
            if ($order && (int) $order->order_all_in_one !== ($perCase ? 0 : 1)) {
                $order->forceFill(['order_all_in_one' => $perCase ? 0 : 1])->save();
            }
            $line = '#' . $id . ' (' . $o->unit_abbreviation . ', ' . Carbon::parse($o->ord_start_day)->format('d/m') . ', ' . $qty . ' ca): '
                . number_format($old, 0, ',', '.') . ' → ' . number_format($price, 0, ',', '.') . ' đ' . ($perCase ? ' (' . number_format($perCase, 0, ',', '.') . ' đ/ca)' : ' (trọn gói)');
            // Cột history_action tối đa 100 ký tự: ghi gọn (chi tiết đủ ở Nhật ký hoạt động)
            HistoryEdit::create(['order_id' => $id, 'user_name' => $by, 'history_action' => mb_substr('Trợ lý AI sửa giá ' . ($theo === 'hop_dong' ? 'theo HĐ' : 'theo KT nhập') . ': '
                . number_format($old, 0, ',', '.') . ' → ' . number_format($price, 0, ',', '.') . ($perCase ? ' (' . number_format($perCase, 0, ',', '.') . '/ca)' : ' (trọn gói)'), 0, 100)]);
            $done[] = array_filter(['ma_don' => $id, 'thay_doi' => $line, 'can_cu' => $why,
                'chiet_khau_moi' => $res['computed']['order_discount'] ?? null, 'con_no_moi' => $res['computed']['accountant_owe'] ?? null,
                'luu_y' => $acc->accountant_number ? 'Đã xuất hóa đơn số ' . $acc->accountant_number . ' — cần điều chỉnh hóa đơn' : null]);
        }

        return array_filter([
            'da_sua' => $done,
            'so_don_da_sua' => count($done),
            'khong_sua' => $failed ?: null,
            'ghi_chu' => $done ? 'Đã lưu như kế toán sửa tay ở trang Công nợ: tính lại Chiết khấu, Lợi nhuận, Còn nợ; đơn chuyển "Đã cập nhật doanh thu"; ghi Lịch sử chỉnh sửa và Nhật ký hoạt động. Tải lại trang Công nợ để thấy số mới.' : null,
        ], fn ($v) => $v !== null);
    }
}
