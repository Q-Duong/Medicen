<?php

namespace App\Services\Claude;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Ngữ cảnh "người dùng đang xem trang nào" cho khung chat trợ lý nổi: server tự nhận trang từ đường dẫn (không tin chữ trình duyệt gửi),
 * tra thêm vài thông tin chính (mã đơn, đơn vị, ngày chụp…) để trợ lý hiểu "đơn này", "khách này".
 */
class PageContext
{
    public static function describe(?string $url, ?string $title = null): ?string
    {
        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        if ($path === '' || strlen($path) > 500) {
            return null;
        }
        parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);
        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (Throwable) {
            return null;
        }
        $name = (string) $route->getName();
        $p = $route->parameters();
        $title = trim(mb_substr(strip_tags((string) $title), 0, 120));
        $base = 'Trang "' . (preg_replace('/\s*-\s*Medicen$/u', '', $title) ?: $name) . '"';

        $detail = match (true) {
            in_array($name, ['order.edit', 'order.copy', 'order.print'], true) => self::order((int) ($p['id'] ?? 0), $name === 'order.copy' ? 'đang COPY từ đơn' : 'đang xem / sửa đơn'),
            $name === 'unit.edit' => self::unit((int) ($p['id'] ?? 0)),
            $name === 'contracts.edit' => self::contract((int) ($p['contract'] ?? $p['id'] ?? 0)),
            $name === 'sales_logs.index' => 'Nhật ký Sales ngày ' . (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($query['ngay'] ?? '')) ? date('d/m/Y', strtotime($query['ngay'])) : 'hôm nay'),
            $name === 'finance.index' => 'Phân tích tài chính, tab ' . (string) ($query['tab'] ?? 'mặc định'),
            $name === 'accountant.index' => null, // bộ lọc trang Công nợ gửi riêng (page_filters), xem AssistantController::ask
            str_starts_with($name, 'order.') || str_starts_with($name, 'accountant') => self::filters($query),
            default => null,
        };

        return mb_substr($base . ($detail ? ' — ' . $detail : '') . ' (đường dẫn ' . $path . ')', 0, 600);
    }

    /** Tên route của đường dẫn trang (null nếu không khớp) */
    public static function routeName(?string $url): ?string
    {
        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        try {
            return $path !== '' ? app('router')->getRoutes()->match(Request::create($path, 'GET'))->getName() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function order(int $id, string $verb): ?string
    {
        $o = DB::table('orders')->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')->where('orders.id', $id)
            ->first(['orders.id', 'units.unit_abbreviation', 'order_details.ord_start_day', 'order_details.ord_cty_name', 'orders.order_quantity']);

        return $o ? "{$verb} #{$o->id} · đơn vị " . ($o->unit_abbreviation ?: '?') . ($o->ord_cty_name ? " · công ty {$o->ord_cty_name}" : '')
            . ' · ngày chụp ' . ($o->ord_start_day ? date('d/m/Y', strtotime($o->ord_start_day)) : '?') . " · {$o->order_quantity} ca" : null;
    }

    private static function unit(int $id): ?string
    {
        $u = DB::table('units')->where('id', $id)->first(['unit_abbreviation', 'unit_name']);

        return $u ? 'đang xem đơn vị ' . ($u->unit_abbreviation ?: '') . ' — ' . $u->unit_name : null;
    }

    private static function contract(int $id): ?string
    {
        $c = DB::table('customer_contracts')->leftJoin('units', 'units.id', '=', 'customer_contracts.unit_id')->where('customer_contracts.id', $id)
            ->first(['customer_contracts.contract_no', 'units.unit_abbreviation']);

        return $c ? 'đang xem hợp đồng ' . ($c->contract_no ?: '#' . $id) . ' của ' . ($c->unit_abbreviation ?: '?') : null;
    }

    /** Bộ lọc đang chọn trên trang danh sách (đơn hàng, công nợ) */
    private static function filters(array $q): ?string
    {
        $q = array_filter($q, fn ($v, $k) => is_scalar($v) && $v !== '' && $k !== 'page', ARRAY_FILTER_USE_BOTH);

        return $q ? 'bộ lọc: ' . mb_substr(http_build_query($q, '', ', '), 0, 200) : null;
    }
}
