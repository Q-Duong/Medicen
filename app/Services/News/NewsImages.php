<?php

namespace App\Services\News;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ảnh tin tức: lưu ở storage/app/news (disk local), phục vụ qua route news.image nên không phụ thuộc
 * thư mục public trên hosting (public_html là bản chép). Ảnh rộng hơn 1600px được thu nhỏ (cần GD).
 */
class NewsImages
{
    public const DIR = 'news';

    public const MAX_WIDTH = 1600;

    public function store(UploadedFile $file): string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $name = date('Ym') . '-' . Str::lower(Str::random(16)) . '.' . $ext;
        $bytes = $this->shrink($file->getRealPath(), $ext) ?? file_get_contents($file->getRealPath());
        Storage::disk('local')->put(self::DIR . '/' . $name, $bytes);

        return $name;
    }

    public function delete(?string $name): void
    {
        if ($name && self::valid($name)) {
            Storage::disk('local')->delete(self::DIR . '/' . $name);
        }
    }

    public function path(string $name): ?string
    {
        $p = self::DIR . '/' . $name;

        return self::valid($name) && Storage::disk('local')->exists($p) ? Storage::disk('local')->path($p) : null;
    }

    /** Tên file do store() sinh ra: chặn đường dẫn lạ (../) */
    public static function valid(string $name): bool
    {
        return (bool) preg_match('/^\d{6}-[a-z0-9]{16}\.(jpg|png|webp|gif)$/', $name);
    }

    /** Thu nhỏ ảnh quá rộng; trả null nếu không cần / không làm được (giữ nguyên file gốc) */
    private function shrink(string $path, string $ext): ?string
    {
        if (!function_exists('imagecreatefromstring') || $ext === 'gif') {
            return null;
        }
        [$w, $h] = @getimagesize($path) ?: [0, 0];
        if ($w <= self::MAX_WIDTH || !$h) {
            return null;
        }
        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (!$src) {
            return null;
        }
        $nw = self::MAX_WIDTH;
        $nh = (int) round($h * $nw / $w);
        $dst = imagecreatetruecolor($nw, $nh);
        if ($ext !== 'jpg') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start();
        match ($ext) {
            'png' => imagepng($dst, null, 7),
            'webp' => imagewebp($dst, null, 82),
            default => imagejpeg($dst, null, 84),
        };
        $out = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        return $out ?: null;
    }
}
