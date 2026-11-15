<?php

namespace App\Console\Commands;

use App\Services\Schedule\RouteDistance;
use App\Services\Schedule\VietnamPlaces;
use Illuminate\Console\Command;

/** Xem hệ thống nhận diện địa chỉ và ước tính quãng đường thế nào (dùng khi chỉnh bảng địa danh) */
class CheckDistance extends Command
{
    protected $signature = 'schedule:distance {from} {to}';

    protected $description = 'Ước tính quãng đường giữa 2 địa chỉ theo bảng tỉnh/thành (dùng cho gợi ý xếp lịch)';

    public function handle(RouteDistance $routes): int
    {
        foreach (['from' => 'Điểm đi', 'to' => 'Điểm đến'] as $arg => $label) {
            $place = VietnamPlaces::locate($this->argument($arg));
            $this->line("{$label}: " . ($place ? "{$place['name']} ({$place['lat']}, {$place['lng']})" : 'không nhận diện được'));
        }

        $r = $routes->between($this->argument('from'), $this->argument('to'));
        if (!$r) {
            $this->warn('Không ước tính được (thiếu tỉnh/thành hoặc quận trong địa chỉ).');

            return self::FAILURE;
        }
        $this->info("Ước tính: khoảng {$r['km']} km, {$r['minutes']} phút chạy xe");

        return self::SUCCESS;
    }
}
