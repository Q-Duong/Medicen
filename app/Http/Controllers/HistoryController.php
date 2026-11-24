<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Trang "Lịch sử chỉnh sửa" cũ đã gộp vào Nhật ký hoạt động (ActivityController): giữ đường dẫn cũ, chuyển sang trang mới.
 * ?order=2350 → ?don=2350 (lịch sử một đơn); ?user / ?from / ?to → lọc tương ứng.
 */
class HistoryController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('activity.index', array_filter([
            'don' => $request->query('order'),
            'tu' => $request->query('from'),
            'den' => $request->query('to'),
            'q' => $request->query('q'),
        ]));
    }
}
