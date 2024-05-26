<?php

namespace App\Http\Controllers;

use App\Models\HistoryEdit;
use App\Models\Post;

class HistoryController extends Controller
{
    public function index()
	{
		$getAllHistory = HistoryEdit::orderBy('id', 'DESC')->paginate(10);
		return view('pages.admin.history.index', compact('getAllHistory'));
	}
}
