<?php

namespace App\Services\Schedule;

/**
 * Ước tính quãng đường và thời gian chạy xe giữa 2 điểm chụp, miễn phí, không gọi dịch vụ ngoài.
 *
 * Dữ liệu dùng:
 * - Số km Sale nhập khi lên đơn (accountants.accountant_distance): km cả đi lẫn về (chia đôi ra km một chiều
 *   từ công ty tới điểm khám), đáng tin nhất về độ xa, nhưng không cho biết hướng.
 * - Địa chỉ khách (customers.customer_address): nhận diện tỉnh/thành, quận (VietnamPlaces) để biết hướng.
 *   Địa chỉ đôi khi là trụ sở đơn vị chứ không phải nơi chụp, nên chỉ tin khi khớp với số km của đơn.
 *
 * Cách tính giữa 2 điểm A, B:
 * - Cả hai điểm đều "định vị được" (địa chỉ nhận diện được và khớp số km đơn): tính theo toạ độ.
 * - Không thì dùng km từ công ty: một điểm gần công ty (≤ near_base_km) thì lấy km của điểm xa hơn;
 *   cả hai đều xa và chưa rõ hướng thì tính như đi qua công ty (dA + dB) và ghi rõ là chưa rõ hướng.
 *
 * Sau này có dịch vụ bản đồ trong nước (Goong, VietMap...) thì chỉ cần thay phần tính ở đây.
 */
class RouteDistance
{
    /** @var array<string, ?array> */
    private array $memo = [];

    private ?array $base = null;

    public function enabled(): bool
    {
        return (bool) config('schedule.distance.enabled', true);
    }

    /** Toạ độ nơi xe xuất phát (config schedule.distance.base) */
    public function base(): ?array
    {
        return $this->base ??= VietnamPlaces::locate(config('schedule.distance.base'));
    }

    /** Số km Sale nhập (chuỗi tự do, cả đi lẫn về) => số km một chiều, null nếu không có / không hợp lệ */
    public static function parseKm($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!preg_match('/\d+(?:[.,]\d+)?/', (string) $value, $m)) {
            return null;
        }
        $km = (float) str_replace(',', '.', $m[0]);
        if (config('schedule.distance.order_km_round_trip', false)) {
            $km /= 2;
        }

        return $km > 0 && $km < 3000 ? $km : null;
    }

    /**
     * Thông tin một điểm chụp.
     *
     * @return array{name: ?string, place: ?array, km_base: ?float, km_source: ?string, located: bool}
     */
    public function site(?string $address, $orderKm = null): array
    {
        $place = VietnamPlaces::locate($address);
        $km = self::parseKm($orderKm);
        $estimate = $place && $this->base() ? $this->roadKm($this->base(), $place) : null;

        // Địa chỉ khớp số km đơn (hoặc đơn không có km) thì coi là vị trí chụp thật
        $located = $place !== null && ($km === null || abs($estimate - $km) <= max(30, 0.35 * $km));

        return [
            'name' => $located ? $place['name'] : null,
            'place' => $located ? $place : null,
            'km_base' => $km ?? $estimate,
            'km_source' => $km !== null ? 'don' : ($estimate !== null ? 'dia_chi' : null),
            'located' => $located,
            'address_area' => $place['name'] ?? null,
        ];
    }

    /** @return array{km: float, minutes: int, from: string, to: string}|null null nếu không nhận diện được địa chỉ */
    public function between(?string $from, ?string $to): ?array
    {
        return $this->betweenSites($this->site($from), $this->site($to));
    }

    /**
     * Quãng đường giữa 2 điểm chụp (xem cách tính ở đầu class).
     *
     * @return array{km: float, minutes: int, from: ?string, to: ?string, basis: string}|null
     */
    public function betweenSites(array $a, array $b): ?array
    {
        if (!$this->enabled()) {
            return null;
        }
        $key = json_encode([$a['place']['key'] ?? null, $a['km_base'], $b['place']['key'] ?? null, $b['km_base']]);
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        if ($a['located'] && $b['located']) {
            $km = $a['place']['key'] === $b['place']['key']
                ? (float) config('schedule.distance.same_place_km', 8)
                : $this->roadKm($a['place'], $b['place']);
            $basis = 'dia_chi';
        } elseif ($a['km_base'] !== null && $b['km_base'] !== null) {
            $near = (float) config('schedule.distance.near_base_km', 40);
            if (min($a['km_base'], $b['km_base']) <= $near) {
                $km = max($a['km_base'], $b['km_base']);
                $basis = 'km_don';
            } else {
                $km = $a['km_base'] + $b['km_base'];
                $basis = 'qua_cong_ty';
            }
        } else {
            return $this->memo[$key] = null;
        }

        $speed = max(10, (float) config('schedule.distance.avg_speed_kmh', 45));

        return $this->memo[$key] = [
            'km' => round($km, 0),
            'minutes' => (int) round($km / $speed * 60),
            'from' => $a['name'],
            'to' => $b['name'],
            'basis' => $basis,
        ];
    }

    private function roadKm(array $a, array $b): float
    {
        return VietnamPlaces::straightKm($a, $b) * (float) config('schedule.distance.road_factor', 1.35);
    }
}
