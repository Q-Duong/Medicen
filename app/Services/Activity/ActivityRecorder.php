<?php

namespace App\Services\Activity;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gom thay đổi "cũ → mới" của các model trong một request (ModelChangeObserver) để RecordActivity ghi MỘT dòng nhật ký
 * cho cả thao tác (vd "Sửa đơn #2350: Số ca 300 → 320, Ngày chụp 24/11 → 25/11"). Singleton theo request.
 * Nếu trong request đã có trang tự ghi nhật ký chi tiết (ActivityLog::record — trang kế toán, hợp đồng) thì không ghi thêm dòng chung.
 */
class ActivityRecorder
{
    /** @var array<string, array{model:Model, action:string, fields:array}> theo model (class#id); tên đối tượng tính lúc ghi */
    private array $items = [];

    private int $explicit = 0;

    public function markExplicit(): void
    {
        $this->explicit++;
    }

    public function hasExplicit(): bool
    {
        return $this->explicit > 0;
    }

    public function add(Model $m, string $action): void
    {
        $cfg = TrackedModels::config()[get_class($m)] ?? null;
        if (!$cfg) {
            return;
        }
        $fields = [];
        $sensitive = $cfg['sensitive'] ?? [];
        if ($action === 'sua') {
            foreach ($m->getChanges() as $col => $new) {
                if (!isset($cfg['fields'][$col])) {
                    continue;
                }
                $old = $m->getOriginal($col);
                $kind = $cfg['format'][$col] ?? null;
                [$o, $n] = [TrackedModels::format($kind, $old), TrackedModels::format($kind, $m->getAttribute($col))];
                if ((string) $o === (string) $n || (is_numeric($o) && is_numeric($n) && (float) $o == (float) $n)) {
                    continue;
                }
                $fields[$cfg['fields'][$col]] = in_array($col, $sensitive, true) ? ['(đã đổi)', ''] : [$o, $n];
            }
            if (!$fields) {
                return;
            }
        } elseif ($action === 'them') {
            foreach ($cfg['fields'] as $col => $label) {
                $v = TrackedModels::format($cfg['format'][$col] ?? null, $m->getAttribute($col));
                // Bỏ ô trống / bằng 0 / "Không" để dòng "Thêm" chỉ nêu thông tin chính
                if ($v !== null && $v !== '' && $v !== 0 && $v !== '0' && $v !== 'Không' && !in_array($col, $sensitive, true)) {
                    $fields[$label] = [null, $v];
                }
            }
        }
        $key = get_class($m) . '#' . $m->getKey();
        $prev = $this->items[$key] ?? null;
        $this->items[$key] = [
            'model' => $m,
            // Thêm rồi sửa trong cùng request vẫn là "thêm"; xóa thắng tất cả
            'action' => $action === 'xoa' ? 'xoa' : ($prev['action'] ?? $action),
            'fields' => array_merge($prev['fields'] ?? [], $fields),
        ];
    }

    /**
     * Thay đổi theo tên đối tượng ("Đơn #2350"), tính ở cuối request (lúc này đơn mới đã có mã, chi tiết / người liên hệ đã gắn vào đơn).
     *
     * @return array<string, array{action:string, fields:array}>
     */
    public function subjects(): array
    {
        $out = [];
        foreach ($this->items as $it) {
            $m = $it['model'];
            try {
                $subject = (TrackedModels::config()[get_class($m)]['subject'])($m);
            } catch (Throwable $e) {
                $subject = class_basename($m) . ' #' . $m->getKey();
            }
            $prev = $out[$subject] ?? null;
            $out[$subject] = [
                'action' => $prev ? (in_array('xoa', [$prev['action'], $it['action']], true) ? 'xoa' : ($prev['action'] === 'them' || $it['action'] === 'them' ? 'them' : 'sua')) : $it['action'],
                'fields' => array_merge($prev['fields'] ?? [], $it['fields']),
            ];
        }

        return $out;
    }

    /** Thay đổi gộp cho cột changes: "Đơn #2350 · Số ca" => [cũ, mới] (một đối tượng thì bỏ tiền tố) */
    public function changes(): array
    {
        $out = [];
        $subjects = $this->subjects();
        // Cùng một đối tượng gốc (vd "Đơn #2350" và "Đơn #2350 (công nợ)") thì không lặp tên, chỉ ghi phần trong ngoặc sau nhãn
        $bases = array_unique(array_map(fn ($s) => preg_replace('/ \(.*\)$/u', '', $s), array_keys($subjects)));
        $multi = count($bases) > 1;
        foreach ($subjects as $subject => $s) {
            $suffix = preg_match('/ \((.*)\)$/u', $subject, $mm) ? ' (' . $mm[1] . ')' : '';
            if ($s['action'] === 'xoa') {
                $out[$multi ? $subject : 'Đối tượng' . $suffix] = [$subject, '(đã xóa)'];
                continue;
            }
            foreach ($s['fields'] as $label => $pair) {
                $out[$multi ? $subject . ' · ' . $label : $label . $suffix] = $pair;
            }
        }

        return $out;
    }

    public function reset(): void
    {
        $this->items = [];
        $this->explicit = 0;
    }

    /** Ghi trực tiếp một dòng (đăng nhập / đăng xuất / thao tác chung) */
    public static function write(array $row): void
    {
        try {
            $user = Auth::user();
            ActivityLog::create($row + [
                'user_id' => $user?->id,
                'user_name' => $user ? ($user->display_name ?? $user->name) : null,
                'ip' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Không ghi được nhật ký hoạt động', ['error' => $e->getMessage()]);
        }
    }
}
