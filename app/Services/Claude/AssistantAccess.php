<?php

namespace App\Services\Claude;

use App\Models\User;

/**
 * Ai được dùng trợ lý AI nào.
 * - Admin (role 0, 1): dùng tất cả, xem được lịch sử chat của mọi người.
 * - Tài khoản khác: theo phòng ban (users.department) khớp 'user_departments' của trợ lý trong config/claude.php.
 */
class AssistantAccess
{
    public static function isAdmin(?User $user): bool
    {
        return $user !== null && in_array((int) $user->role, [0, 1], true);
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
