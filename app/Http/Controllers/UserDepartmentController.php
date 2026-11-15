<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Claude\AssistantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Admin cấp phòng ban cho từng tài khoản (users.department).
 * Phòng ban quyết định tài khoản được dùng trợ lý AI nào.
 */
class UserDepartmentController extends Controller
{
    public function index()
    {
        $users = User::orderBy('role')->orderBy('name')->orderBy('id')->get(['id', 'name', 'email', 'role', 'department']);

        return view('pages.admin.user_departments.index', [
            'users' => $users,
            'departments' => config('departments', []),
            'assistants' => collect(config('claude.departments', []))->map(fn ($a) => [
                'title' => $a['title'] ?? $a['name'],
                'user_departments' => $a['user_departments'] ?? [],
            ]),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'department' => ['nullable', 'string', Rule::in(array_keys(config('departments', [])))],
        ]);

        $old = $user->department;
        $user->forceFill(['department' => $data['department'] ?: null])->save();

        Log::info('Đổi phòng ban tài khoản', [
            'user_id' => $user->id, 'from' => $old, 'to' => $user->department, 'by' => Auth::id(),
        ]);

        $assistants = implode(', ', AssistantAccess::assistantsFor($user)) ?: 'không có';

        return back()->with('success', "Đã cập nhật phòng ban cho {$user->name} ({$user->email}). Trợ lý được dùng: {$assistants}.");
    }
}
