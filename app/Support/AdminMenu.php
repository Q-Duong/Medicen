<?php

namespace App\Support;

use App\Http\Middleware\PageAccess;
use App\Models\User;
use App\Services\Claude\AssistantAccess;

/**
 * Menu trang quản trị (sidebar + lối tắt ở trang chào), chia nhóm.
 * Mục nào hiện theo đúng quyền ở server (PageAccess / config), nên link nào thấy là vào được.
 * Admin: gom các trợ lý AI thành 1 mục "Trợ lý AI" (trong trang có ô chuyển phòng ban);
 * tài khoản khác: mỗi trợ lý được dùng là 1 mục.
 */
class AdminMenu
{
    /**
     * @return array<int, array{title: string, items: array<int, array{label: string, icon: string, url: string, active: bool, hint: ?string}>}>
     */
    public static function sections(?User $user): array
    {
        if (!$user) {
            return [];
        }
        $admin = AssistantAccess::isAdmin($user);
        $fullAdmin = AssistantAccess::isFullAdmin($user);
        $can = fn (string ...$groups) => PageAccess::allows($user, $groups);
        $name = (string) $user->name;
        $dept = (string) $user->department;

        $subAdmin = AssistantAccess::isSubAdmin($user);
        $item = fn (string $label, string $icon, string $route, array $params = [], ?string $active = null, ?string $hint = null) => [
            'label' => $label, 'icon' => $icon, 'route' => $route, 'url' => route($route, $params), 'hint' => $hint,
            'active' => request()->routeIs($active ?? $route) && (!$params || collect($params)->every(fn ($v, $k) => (string) request()->route($k) === (string) $v || request()->query($k) == $v)),
        ];

        $sections = [];

        // Tổng quan
        $sections[] = ['title' => 'Tổng quan', 'items' => array_filter([
            $item('Trang chủ', 'fas fa-home', 'admin.home'),
            $can('dashboard') ? $item('Thống kê doanh thu', 'fas fa-chart-line', 'dashboard.index', [], null, 'Doanh thu theo ngày, tháng, đơn vị') : null,
            $can('kpi') ? $item('Tính KPI', 'fas fa-bullseye', 'statistics.index', [], null, 'Điểm hiệu suất nhân viên') : null,
        ])];

        // Kinh doanh
        $sections[] = ['title' => 'Kinh doanh', 'items' => array_filter([
            $can('orders') ? $item('Đơn hàng', 'fas fa-clipboard-list', 'order.index', [], 'order.*', 'Lên đơn, sửa đơn, nhóm đi chung') : null,
            ($admin || in_array($dept, config('profit.user_departments', []), true)) ? $item('Tính lợi nhuận X-Quang', 'fas fa-calculator', 'profit.index', [], 'profit.*', 'Lãi / lỗ trước khi chốt đơn') : null,
            $can('units') ? $item('Đơn vị (khách hàng)', 'fas fa-building', 'unit.index', [], 'unit.*', 'Danh sách đơn vị thuê xe') : null,
            (!$fullAdmin && $can('debt_sales')) ? $item('Công nợ (Sales)', 'fas fa-file-invoice-dollar', 'accountant_sales.index', [], null, 'Xem công nợ khách') : null,
        ])];

        // Lịch xe
        $sections[] = ['title' => 'Lịch xe', 'items' => array_filter([
            ($admin || in_array($dept, config('schedule.user_departments', []), true)) ? $item('Xếp lịch xe', 'fas fa-shuttle-van', 'dispatch.index', [], 'dispatch.*', 'Xếp xe, tài xế, KTV; chấm công') : null,
            in_array($name, ['Admin', 'Sale'], true) ? $item('Lịch xe Sales', 'far fa-calendar-alt', 'schedules.sales.index') : null,
            ($admin || in_array($name, ['Sale', 'SubAdmin'], true) || in_array($dept, config('schedule.user_departments', []), true)) ? $item('Lịch xe KTV', 'fas fa-calendar-check', 'schedules.technicians.index') : null,
        ])];

        // Kế toán
        $sections[] = ['title' => 'Kế toán', 'items' => array_filter([
            $can('debt_accountant') ? $item('Công nợ', 'fas fa-wallet', 'accountant.index', [], 'accountant.*', 'Doanh thu, thanh toán, hoàn thành đơn') : null,
            (!$admin && $can('debt_result')) ? $item('Công nợ (Kết quả)', 'fas fa-wallet', 'accountant_result.index') : null,
            $can('debt_accountant') ? $item('Báo cáo thu chi', 'fas fa-receipt', 'reports.index', [], 'reports.*', 'Kế hoạch thu chi theo tháng') : null,
            $can('debt_accountant') ? $item('Phân tích tài chính', 'fas fa-chart-pie', 'finance.index', [], 'finance.*', 'Tiền kẹt, lãi ước tính, dòng tiền 13 tuần') : null,
            $can('debt_accountant') ? $item('Công nợ CT', 'fas fa-x-ray', 'ct.index', [], 'ct.*', 'Máy CT tại bệnh viện hợp tác') : null,
            $can('debt_accountant') ? $item('Sổ VCB chú Lộc', 'fas fa-university', 'vcb.index', [], 'vcb.*', 'Tiền công ty qua TK VCB chú') : null,
            $can('debt_accountant') ? $item('Dữ liệu kế toán', 'fas fa-database', 'accounting.index', [], 'accounting.*', 'Sổ sách MISA, BCTC, hợp đồng') : null,
        ])];

        // Hành chính
        $sections[] = ['title' => 'Hành chính', 'items' => array_filter([
            $can('contract_edit') ? $item('Hợp đồng', 'fas fa-file-contract', 'contract.index', [], 'contract.*', 'Hợp đồng lao động, hợp đồng khách')
                : ($can('contract_view') ? $item('Hợp đồng', 'fas fa-file-contract', 'contract.view_only.index', [], 'contract.*', 'Xem hợp đồng') : null),
        ])];

        // Trợ lý AI
        $assistants = AssistantAccess::assistantsFor($user);
        $ai = [];
        if ($admin && count($assistants) > 1) {
            // Admin: 1 mục, vào trang trợ lý (có ô chuyển Sales / Kế toán ở đầu trang)
            $ai[] = $item('Trợ lý AI', 'fas fa-robot', 'assistant.show', ['department' => array_key_first($assistants)], 'assistant.*', 'Sales, Kế toán');
            $ai[0]['active'] = request()->routeIs('assistant.*');
        } else {
            foreach ($assistants as $key => $label) {
                $ai[] = $item($label, 'fas fa-robot', 'assistant.show', ['department' => $key], 'assistant.*', 'Hỏi đáp bằng AI');
            }
        }
        $sections[] = ['title' => 'Trợ lý AI', 'items' => $ai];

        // Quản trị
        if ($admin) {
            $sections[] = ['title' => 'Quản trị', 'items' => [
                $item('Nhân viên (TX, KTV)', 'fas fa-id-badge', 'staff.index', [], 'staff.*'),
                $item('Tài khoản', 'fas fa-users-cog', 'accounts.index', [], 'accounts.*'),
                $item('Phân quyền phòng ban', 'fas fa-user-shield', 'user-departments.index', [], 'user-departments.*'),
                $item('Lịch sử chỉnh sửa', 'fas fa-history', 'history.index', [], 'history.*'),
            ]];
        }

        if ($subAdmin) {
            // SubAdmin loại Admin: chỉ giữ mục có route được phép (config access.subadmin_routes)
            $sections = array_map(fn ($s) => array_merge($s, ['items' => array_filter($s['items'], fn ($i) => $i && \App\Http\Middleware\SubAdminScope::allowsRoute($i['route']))]), $sections);
        }

        return array_values(array_filter(array_map(fn ($s) => array_merge($s, ['items' => array_values($s['items'])]), $sections), fn ($s) => $s['items']));
    }
}
