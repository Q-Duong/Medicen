<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Nhật ký hoạt động: đăng nhập / đăng xuất / đăng nhập sai (không lưu mật khẩu)
        $ua = fn () => mb_substr((string) request()?->userAgent(), 0, 190);
        Event::listen(\Illuminate\Auth\Events\Login::class, function ($e) use ($ua) {
            \App\Models\ActivityLog::create(['user_id' => $e->user->id, 'user_name' => $e->user->display_name ?? $e->user->name, 'area' => 'dang_nhap',
                'action' => 'dang_nhap', 'route' => request()?->route()?->getName(), 'summary' => 'Đăng nhập' . ($e->remember ? ' (ghi nhớ đăng nhập)' : ''),
                'ip' => request()?->ip(), 'user_agent' => $ua()]);
        });
        Event::listen(\Illuminate\Auth\Events\Logout::class, function ($e) use ($ua) {
            if ($e->user) {
                \App\Models\ActivityLog::create(['user_id' => $e->user->id, 'user_name' => $e->user->display_name ?? $e->user->name, 'area' => 'dang_nhap',
                    'action' => 'dang_xuat', 'route' => request()?->route()?->getName(), 'summary' => 'Đăng xuất', 'ip' => request()?->ip(), 'user_agent' => $ua()]);
            }
        });
        Event::listen(\Illuminate\Auth\Events\Failed::class, function ($e) use ($ua) {
            $email = mb_substr((string) ($e->credentials['email'] ?? $e->credentials['name'] ?? ''), 0, 100);
            \App\Models\ActivityLog::create(['user_id' => $e->user?->id, 'user_name' => $e->user ? ($e->user->display_name ?? $e->user->name) : $email,
                'area' => 'dang_nhap', 'action' => 'dang_nhap_sai', 'route' => request()?->route()?->getName(),
                'summary' => 'Đăng nhập sai' . ($email !== '' ? ' (' . $email . ')' : ''), 'ip' => request()?->ip(), 'user_agent' => $ua()]);
        });

        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
