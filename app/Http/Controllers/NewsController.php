<?php

namespace App\Http\Controllers;

use App\Http\Middleware\PageAccess;
use App\Models\News;
use App\Services\News\NewsImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Tin tức phía khách: /news (danh sách, lọc chuyên mục ?category=, tìm kiếm ?q=), /news/{slug} (chi tiết), ảnh /news/image/{file} */
class NewsController extends Controller
{
    public const PER_PAGE = 9;

    public function index(Request $request)
    {
        $category = trim((string) $request->query('category'));
        $q = trim((string) $request->query('q'));
        $filtered = $category !== '' || $q !== '';

        $featured = null;
        if (!$filtered && (int) $request->query('page', 1) <= 1) {
            $featured = News::published()->orderByDesc('is_featured')->orderByDesc('published_at')->first();
        }
        $news = News::published()
            ->when($category !== '', fn ($w) => $w->where('category', $category))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%' . $q . '%')->orWhere('summary', 'like', '%' . $q . '%')))
            ->when($featured, fn ($w) => $w->where('id', '!=', $featured->id))
            ->orderByDesc('published_at')
            ->paginate(self::PER_PAGE)->withQueryString();
        $categories = News::published()->whereNotNull('category')->where('category', '!=', '')
            ->selectRaw('category, count(*) n')->groupBy('category')->orderByDesc('n')->pluck('n', 'category');

        return view('pages.client.news.index', compact('news', 'featured', 'categories', 'category', 'q'));
    }

    public function show(Request $request, string $slug)
    {
        $item = News::where('slug', $slug)->firstOrFail();
        // Bài chưa đăng: chỉ người quản lý tin tức xem trước được
        $preview = !$item->isLive();
        abort_if($preview && !(Auth::check() && PageAccess::allows(Auth::user(), ['news'])), 404);

        if (!$preview) {
            // Mỗi phiên trình duyệt đếm 1 lượt xem / bài
            $seen = (array) $request->session()->get('news_seen', []);
            if (!in_array($item->id, $seen, true)) {
                News::whereKey($item->id)->increment('views');
                $request->session()->put('news_seen', array_slice(array_merge($seen, [$item->id]), -50));
            }
        }
        $related = News::published()->where('id', '!=', $item->id)
            ->orderByRaw('category <=> ? desc', [$item->category])->orderByDesc('published_at')->limit(3)->get();
        $prev = $preview ? null : News::published()->where('published_at', '<', $item->published_at)->orderByDesc('published_at')->first(['title', 'slug']);
        $next = $preview ? null : News::published()->where('published_at', '>', $item->published_at)->orderBy('published_at')->first(['title', 'slug']);

        return view('pages.client.news.show', compact('item', 'related', 'prev', 'next', 'preview'));
    }

    /** Video trong bài: BinaryFileResponse tự xử lý Range (tua video) */
    public function video(string $file, \App\Services\News\NewsVideos $videos)
    {
        $path = $videos->path($file);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=2592000, immutable']);
    }

    public function image(string $file, NewsImages $images)
    {
        $path = $images->path($file);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=2592000, immutable']);
    }
}
