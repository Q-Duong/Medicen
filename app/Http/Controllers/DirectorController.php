<?php

namespace App\Http\Controllers;

use App\Services\Finance\DirectorOverview;

/** Tổng quan điều hành: một trang cho giám đốc xem tiền, khoản phải chi, X-quang tháng này và việc cần xử lý */
class DirectorController extends Controller
{
    public function index(DirectorOverview $overview)
    {
        return view('pages.admin.director.index', ['o' => $overview->build()]);
    }
}
