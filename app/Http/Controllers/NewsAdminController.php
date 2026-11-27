<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Services\News\NewsHtml;
use App\Services\News\NewsImages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Quản lý tin tức (Website → Tin tức): Admin và Sales (config access.news) */
class NewsAdminController extends Controller
{
    public function __construct(private NewsImages $images)
    {
    }

    public function index(Request $request)
    {
        $state = (string) $request->query('tt');
        $q = trim((string) $request->query('q'));
        $category = (string) $request->query('chuyen_muc');
        $news = News::with('author:id,name')
            ->when($state === 'da_xoa', fn ($w) => $w->onlyTrashed())
            ->when($state === 'nhap', fn ($w) => $w->where('status', 'draft'))
            ->when($state === 'da_dang', fn ($w) => $w->published())
            ->when($state === 'hen_gio', fn ($w) => $w->where('status', 'published')->where('published_at', '>', now()))
            ->when($category !== '', fn ($w) => $w->where('category', $category))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('summary', 'like', "%{$q}%")))
            ->orderByRaw("status = 'draft' desc")->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();
        $counts = [
            'tat_ca' => News::count(),
            'da_dang' => News::published()->count(),
            'hen_gio' => News::where('status', 'published')->where('published_at', '>', now())->count(),
            'nhap' => News::where('status', 'draft')->count(),
            'da_xoa' => News::onlyTrashed()->count(),
        ];

        return view('pages.admin.news.index', ['news' => $news, 'counts' => $counts, 'categories' => $this->categories(), 'state' => $state]);
    }

    public function create()
    {
        return view('pages.admin.news.form', ['item' => new News(['status' => 'draft']), 'categories' => $this->categories()]);
    }

    public function store(Request $request)
    {
        $item = new News(['created_by' => Auth::id()]);
        $this->fill($item, $request);
        $item->save();

        return $this->redirectAfterSave($request, $item, 'Đã thêm bài “' . e($item->title) . '”.');
    }

    public function edit(int $id)
    {
        return view('pages.admin.news.form', ['item' => News::findOrFail($id), 'categories' => $this->categories()]);
    }

    public function update(Request $request, int $id)
    {
        $item = News::findOrFail($id);
        $this->fill($item, $request);
        $item->save();

        return $this->redirectAfterSave($request, $item, 'Đã lưu bài “' . e($item->title) . '”.');
    }

    public function destroy(int $id)
    {
        $item = News::findOrFail($id);
        $item->delete(); // xóa mềm, giữ ảnh để khôi phục được

        return redirect()->route('news_admin.index')->with('success', 'Đã xóa bài "' . e($item->title) . '". Khôi phục được ở mục "Đã xóa".');
    }

    /** Khôi phục bài đã xóa (về bản nháp để xem lại trước khi đăng) */
    public function restore(int $id)
    {
        $item = News::onlyTrashed()->findOrFail($id);
        $item->restore();
        $item->update(['status' => 'draft', 'updated_by' => Auth::id()]);

        return redirect()->route('news_admin.index', ['tt' => 'nhap'])->with('success', 'Đã khôi phục bài "' . e($item->title) . '" (đang là bản nháp).');
    }

    /** Đăng ngay / gỡ xuống từ danh sách */
    public function toggle(int $id)
    {
        $item = News::findOrFail($id);
        if ($item->isLive()) {
            $item->update(['status' => 'draft', 'updated_by' => Auth::id()]);
            $msg = 'Đã gỡ bài xuống (bản nháp).';
        } else {
            $item->update(['status' => 'published', 'published_at' => $item->published_at && $item->published_at->lte(now()) ? $item->published_at : now(), 'updated_by' => Auth::id()]);
            $msg = 'Đã đăng bài.';
        }

        return back()->with('success', $msg);
    }

    /** Tải ảnh trong nội dung (CKEditor SimpleUploadAdapter): trả {url} */
    public function upload(Request $request)
    {
        $v = validator($request->all(), ['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192']],
            ['upload.max' => 'Ảnh tối đa 8 MB.', 'upload.image' => 'File không phải ảnh.', 'upload.mimes' => 'Chỉ nhận ảnh JPG, PNG, WEBP, GIF.']);
        if ($v->fails()) {
            return response()->json(['error' => ['message' => $v->errors()->first()]], 422);
        }
        $name = $this->images->store($request->file('upload'));

        return response()->json(['url' => route('news.image', $name, false)]);
    }

    /** Tải video trong bài theo từng phần (App\Services\News\NewsVideos::CHUNK): trả {done:false} hoặc {done:true, url} */
    public function uploadVideo(Request $request, \App\Services\News\NewsVideos $videos)
    {
        $request->validate(['upload_id' => ['required', 'string'], 'index' => ['required', 'integer'], 'total' => ['required', 'integer'], 'chunk' => ['required', 'file']]);
        try {
            $name = $videos->receive((string) $request->input('upload_id'), (int) $request->input('index'), (int) $request->input('total'), $request->file('chunk'));
        } catch (\RuntimeException $e) {
            return response()->json(['error' => ['message' => $e->getMessage()]], 422);
        }

        return response()->json($name ? ['done' => true, 'url' => route('news.video', $name)] : ['done' => false]);
    }

    private function fill(News $item, Request $request): void
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(array_keys(News::STATUSES))],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:300'],
        ], [
            'title.required' => 'Nhập tiêu đề bài viết.',
            'content.required' => 'Nhập nội dung bài viết.',
            'image.image' => 'Ảnh đại diện phải là file ảnh.',
            'image.max' => 'Ảnh đại diện tối đa 8 MB.',
            'summary.max' => 'Mô tả ngắn tối đa 500 ký tự.',
        ]);

        $content = NewsHtml::clean($data['content']);
        if (trim(strip_tags($content, '<img><iframe>')) === '') {
            back()->withInput()->withErrors(['content' => 'Nhập nội dung bài viết.'])->throwResponse();
        }
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $slug = $slugInput !== '' ? News::uniqueSlug($slugInput, $item->id) : ($item->slug ?: News::uniqueSlug($data['title'], $item->id));

        $publishedAt = !empty($data['published_at']) ? Carbon::parse($data['published_at']) : null;
        if ($data['status'] === 'published' && !$publishedAt) {
            $publishedAt = $item->published_at ?? now();
        }

        $item->fill([
            'title' => trim($data['title']),
            'slug' => $slug,
            'category' => trim((string) ($data['category'] ?? '')) ?: null,
            'summary' => trim((string) ($data['summary'] ?? '')) ?: null,
            'content' => $content,
            'status' => $data['status'],
            'published_at' => $publishedAt,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'seo_title' => trim((string) ($data['seo_title'] ?? '')) ?: null,
            'seo_description' => trim((string) ($data['seo_description'] ?? '')) ?: null,
            'updated_by' => Auth::id(),
        ]);

        if ($request->hasFile('image')) {
            $this->images->delete($item->getOriginal('image'));
            $item->image = $this->images->store($request->file('image'));
        } elseif (!empty($data['remove_image']) && $item->image) {
            $this->images->delete($item->image);
            $item->image = null;
        }
        // Chỉ 1 bài nổi bật (hiện lớn đầu trang Tin tức)
        if ($item->is_featured) {
            News::where('is_featured', true)->when($item->id, fn ($w) => $w->where('id', '!=', $item->id))->update(['is_featured' => false]);
        }
    }

    private function redirectAfterSave(Request $request, News $item, string $msg)
    {
        if ($request->input('after') === 'xem') {
            return redirect()->route('news.show', $item->slug);
        }

        return redirect()->route('news_admin.index')->with('success', $msg . ($item->isLive() ? '' : ($item->status === 'published' ? ' Bài sẽ tự hiện lúc ' . $item->published_at->format('H:i d/m/Y') . '.' : ' Bài đang là bản nháp, khách chưa thấy.')));
    }

    private function categories(): array
    {
        return collect(News::CATEGORIES)->merge(News::withTrashed()->whereNotNull('category')->distinct()->pluck('category'))->unique()->values()->all();
    }
}
