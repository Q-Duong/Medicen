<?php

namespace App\Services\Claude;

use App\Models\User;

/**
 * Ai được dùng trợ lý AI nào.
 * - Admin (role 0, 1): dùng tất cả, xem được lịch sử chat của mọi người.
 * - SubAdmin (role 0/1 nhưng tên tài khoản 'SubAdmin'): chỉ dùng các trợ lý trong config('claude.subadmin_assistants'),
 *   chỉ xem lịch sử chat của chính mình, không vào các nhóm trang trong config('access.subadmin_restricted').
 * - Tài khoản khác: theo phòng ban (users.department) khớp 'user_departments' của trợ lý trong config/claude.php.
 */
class AssistantAccess
{
    public static function isAdmin(?User $user): bool
    {
        return $user !== null && in_array((int) $user->role, [0, 1], true);
    }

    /** Tài khoản loại Admin nhưng là SubAdmin (bị giới hạn một số quyền) */
    public static function isSubAdmin(?User $user): bool
    {
        return self::isAdmin($user) && $user->name === 'SubAdmin';
    }

    /** Admin đầy đủ quyền (không phải SubAdmin) */
    public static function isFullAdmin(?User $user): bool
    {
        return self::isAdmin($user) && !self::isSubAdmin($user);
    }

    public static function exists(string $assistant): bool
    {
        return is_array(config("claude.departments.{$assistant}"));
    }

    public static function can(?User $user, string $assistant): bool
    {
        if (!$user || !self::exists($assistant)) {
            return false;
        }

        if (self::isSubAdmin($user)) {
            return in_array($assistant, config('claude.subadmin_assistants', []), true);
        }

        return self::isAdmin($user)
            || in_array($user->department, config("claude.departments.{$assistant}.user_departments", []), true);
    }

    /** @return array<string, string> key => tên hiển thị của các trợ lý người này được dùng */
    public static function assistantsFor(?User $user): array
    {
        $list = [];
        foreach (config('claude.departments', []) as $key => $assistant) {
            if (self::can($user, $key)) {
                $list[$key] = $assistant['title'] ?? $assistant['name'];
            }
        }

        return $list;
    }
}
