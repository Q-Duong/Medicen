<?php

namespace App\Services\News;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Lọc HTML nội dung tin tức (soạn bằng CKEditor) trước khi lưu: bỏ script, sự kiện on*, link javascript:;
 * giữ định dạng bài viết, ảnh, bảng, video tải lên web (thẻ video). Không nhúng iframe (YouTube, Facebook…).
 */
class NewsHtml
{
    public static function clean(?string $html): string
    {
        static $purifier = null;
        if ($purifier === null) {
            $cache = storage_path('framework/cache/htmlpurifier');
            if (!is_dir($cache)) {
                @mkdir($cache, 0775, true);
            }
            $c = HTMLPurifier_Config::createDefault();
            $c->set('Cache.SerializerPath', is_writable($cache) ? $cache : null);
            $c->set('HTML.Doctype', 'HTML 4.01 Transitional');
            $c->set('HTML.DefinitionID', 'medicen-news');
            $c->set('HTML.DefinitionRev', 4);
            $c->set('Attr.AllowedFrameTargets', ['_blank']);
            $c->set('HTML.TargetNoopener', true);
            $c->set('CSS.AllowTricky', true);
            $c->set('AutoFormat.RemoveEmpty', false);
            $c->set('Attr.DefaultImageAlt', ''); // không tự điền tên file vào alt
            if ($def = $c->maybeGetRawHTMLDefinition()) {
                // Thẻ HTML5 CKEditor dùng
                $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
                $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
                $def->addElement('mark', 'Inline', 'Inline', 'Common');
                $def->addAttribute('img', 'loading', 'Enum#lazy,eager');
                // Video tải lên web (NewsVideos); src được kiểm tra lại sau khi lọc
                $def->addElement('video', 'Block', 'Empty', 'Common', ['src' => 'URI', 'controls' => 'Bool', 'playsinline' => 'Bool', 'preload' => 'Enum#none,metadata,auto']);
            }
            $purifier = new HTMLPurifier($c);
        }

        $out = $purifier->purify(self::normalizeMedia((string) $html));
        // Chỉ giữ video của chính web (/news/video/<tên hợp lệ>)
        $out = preg_replace_callback('#<video\b[^>]*>(?:</video>)?#i', function ($m) {
            return preg_match('#src="/news/video/([^"/]+)"#', $m[0], $x) && NewsVideos::valid($x[1])
                ? '<video controls preload="metadata" playsinline src="/news/video/' . $x[1] . '"></video>' : '';
        }, $out);

        // Ảnh trong bài: tải chậm cho trang nhẹ
        return preg_replace('/<img(?![^>]*\bloading=)/i', '<img loading="lazy"', $out);
    }

    /**
     * Khối <figure class="media"> do nút "Chèn video" của CKEditor tạo (data-oembed-url / oembed / video / iframe):
     * video tải lên web → <video> gọn (CSS lo khung); link khác (YouTube, Facebook…) không nhúng, giữ thành liên kết thường.
     */
    public static function normalizeMedia(string $html): string
    {
        return preg_replace_callback('#<figure[^>]*class="[^"]*\bmedia\b[^"]*"[^>]*>(.*?)</figure>#is', function ($m) {
            $url = null;
            foreach (['#data-oembed-url="([^"]+)"#i', '#<oembed[^>]*url="([^"]+)"#i', '#<video[^>]*src="([^"]+)"#i', '#<iframe[^>]*src="([^"]+)"#i'] as $re) {
                if (preg_match($re, $m[1], $x)) {
                    $url = html_entity_decode($x[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    break;
                }
            }
            if (!$url) {
                return '';
            }
            if (preg_match('#/news/video/([^/?\#"]+)$#', $url, $x) && NewsVideos::valid($x[1])) {
                return '<figure class="media media-video"><video controls preload="metadata" playsinline src="/news/video/' . $x[1] . '"></video></figure>';
            }
            $safe = e($url);

            return preg_match('#^https?://#i', $url) ? '<p><a href="' . $safe . '" target="_blank">' . $safe . '</a></p>' : '';
        }, $html) ?? $html;
    }
}
