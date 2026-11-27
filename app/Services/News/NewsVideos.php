<?php

namespace App\Services\News;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Video trong bài tin tức: tải lên theo từng phần nhỏ (CHUNK byte / lần) để không vướng giới hạn upload_max_filesize của hosting,
 * ghép lại ở storage/app/news-video, phát qua route news.video (hỗ trợ tua: HTTP Range).
 */
class NewsVideos
{
    public const DIR = 'news-video';

    public const TMP = 'news-video-tmp';

    public const CHUNK = 1048576; // 1 MB: dưới mức upload_max_filesize thấp nhất thường gặp (2 MB)

    public const MAX_MB = 300;

    public const MIMES = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov', 'video/x-m4v' => 'm4v'];

    /**
     * Nhận một phần; trả tên file khi đã đủ các phần (null nếu còn chờ).
     */
    public function receive(string $uploadId, int $index, int $total, UploadedFile $chunk): ?string
    {
        if (!preg_match('/^[a-z0-9]{16,40}$/', $uploadId) || $index < 0 || $total < 1 || $index >= $total
            || $total > (int) ceil(self::MAX_MB * 1048576 / self::CHUNK) || $chunk->getSize() > self::CHUNK + 1024) {
            throw new RuntimeException('Dữ liệu tải lên không hợp lệ.');
        }
        $disk = Storage::disk('local');
        if ($index === 0) {
            $this->pruneTmp();
        }
        $dir = self::TMP . '/' . $uploadId;
        $disk->putFileAs($dir, $chunk, sprintf('%05d', $index));

        if (count($disk->files($dir)) < $total) {
            return null;
        }

        // Đủ phần: ghép, kiểm tra loại file thật, chuyển vào thư mục video
        $tmp = $disk->path($dir . '/joined');
        $out = fopen($tmp, 'wb');
        for ($i = 0; $i < $total; $i++) {
            $part = $disk->path($dir . '/' . sprintf('%05d', $i));
            if (!is_file($part)) {
                fclose($out);
                $disk->deleteDirectory($dir);
                throw new RuntimeException('Thiếu một phần video, hãy tải lại.');
            }
            $in = fopen($part, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $size = filesize($tmp);
        if (!isset(self::MIMES[$mime]) || $size > self::MAX_MB * 1048576) {
            $disk->deleteDirectory($dir);
            throw new RuntimeException(isset(self::MIMES[$mime]) ? 'Video tối đa ' . self::MAX_MB . ' MB.' : 'File không phải video MP4 / WEBM / MOV.');
        }
        $name = date('Ym') . '-' . Str::lower(Str::random(16)) . '.' . self::MIMES[$mime];
        $disk->makeDirectory(self::DIR);
        rename($tmp, $disk->path(self::DIR . '/' . $name));
        $disk->deleteDirectory($dir);

        return $name;
    }

    public function path(string $name): ?string
    {
        $p = self::DIR . '/' . $name;

        return self::valid($name) && Storage::disk('local')->exists($p) ? Storage::disk('local')->path($p) : null;
    }

    public static function valid(string $name): bool
    {
        return (bool) preg_match('/^\d{6}-[a-z0-9]{16}\.(mp4|webm|mov|m4v)$/', $name);
    }

    /** Bỏ các lần tải dở quá 1 ngày */
    private function pruneTmp(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->directories(self::TMP) as $d) {
            if (time() - filemtime($disk->path($d)) > 86400) {
                $disk->deleteDirectory($d);
            }
        }
    }
}
