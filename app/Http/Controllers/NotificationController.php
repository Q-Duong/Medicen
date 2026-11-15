<?php

namespace App\Http\Controllers;

use App\Services\Notifications\TodoList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/** Chuông thông báo trên header: việc cần làm của tài khoản đang đăng nhập (App\Services\Notifications\TodoList) */
class NotificationController extends Controller
{
    public function todo(Request $request, TodoList $todo)
    {
        $user = Auth::user();
        if ($request->boolean('fresh')) {
            Cache::forget('todo:' . $user->id . ':' . implode(',', $todo->groupsFor($user)));
        }

        return response()->json($todo->for($user))->header('Cache-Control', 'no-store');
    }
}
