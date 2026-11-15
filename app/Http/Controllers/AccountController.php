<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

/**
 * Quản lý tài khoản đăng nhập (chỉ Admin): xem, thêm, sửa mọi thông tin (tên, email, loại, phòng ban, mật khẩu,
 * thông tin cá nhân ở bảng profiles), xóa.
 *
 * Lưu ý: một số trang phân quyền theo TÊN tài khoản (Admin, Sale, Office, Technician, Accountant: middleware lịch
 * xe, sidebar Kế toán, đăng nhập trang lịch) — form ghi rõ để không đổi tên nhầm.
 * Bảo vệ: không tự xóa / tự hạ quyền chính mình, luôn còn ít nhất 1 Admin.
 */
class AccountController extends Controller
{
    public const ROLES = [0 => 'Admin', 2 => 'Nhân viên', 3 => 'Kế toán'];

    /** Tên tài khoản đang dùng để phân quyền một số trang */
    public const SPECIAL_NAMES = [
        'Admin' => 'Toàn quyền, trang lịch xe (Kết quả, Sales)',
        'Sale' => 'Trang lịch xe Sales',
        'Office' => 'Trang lịch xe Kết quả',
        'Technician' => 'Trang lịch xe KTV',
        'Accountant' => 'Menu Kế toán',
    ];

    public function index(Request $request)
    {
        $query = User::query()->leftJoin('profiles', 'profiles.id', '=', 'users.profile_id')
            ->select('users.*', 'profiles.profile_firstname', 'profiles.profile_lastname', 'profiles.profile_phone');
        if ($request->filled('role') && array_key_exists((int) $request->role, self::ROLES + [1 => 'Quản lý'])) {
            $query->where('users.role', (int) $request->role);
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(fn ($w) => $w->where('users.name', 'LIKE', "%{$q}%")->orWhere('users.email', 'LIKE', "%{$q}%")
                ->orWhere('profiles.profile_phone', 'LIKE', "%{$q}%")
                ->orWhereRaw("CONCAT(COALESCE(profiles.profile_firstname,''),' ',COALESCE(profiles.profile_lastname,'')) LIKE ?", ["%{$q}%"]));
        }
        $accounts = $query->orderBy('users.role')->orderBy('users.name')->orderBy('users.id')->paginate(15)->withQueryString();
        $counts = ['all' => User::count()] + collect(self::ROLES)->mapWithKeys(fn ($l, $r) => [$r => User::where('role', $r)->count()])->all();

        return view('pages.admin.accounts.index', [
            'accounts' => $accounts,
            'counts' => $counts,
            'roles' => self::ROLES,
            'departments' => config('departments', []),
        ]);
    }

    public function create()
    {
        return view('pages.admin.accounts.create', $this->formData(null));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        DB::transaction(function () use ($data, &$user) {
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->forceFill(['role' => (int) $data['role'], 'department' => ($data['department'] ?? null) ?: null]);
            $this->saveProfile($user, $data);
            $user->save();
        });
        Log::info('Thêm tài khoản', ['user_id' => $user->id, 'by' => Auth::id()]);

        return Redirect::route('accounts.index')->with('success', "Đã thêm tài khoản {$user->name} ({$user->email}).");
    }

    public function edit(User $user)
    {
        return view('pages.admin.accounts.edit', $this->formData($user));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $me = Auth::id() === $user->id;

        // Không tự hạ quyền Admin của chính mình; luôn còn ít nhất một Admin
        if ((int) $user->role === 0 && (int) $data['role'] !== 0) {
            if ($me) {
                return back()->withInput()->withErrors(['role' => 'Không tự bỏ quyền Admin của chính tài khoản đang đăng nhập.']);
            }
            if (User::where('role', 0)->count() <= 1) {
                return back()->withInput()->withErrors(['role' => 'Phải còn ít nhất một tài khoản Admin.']);
            }
        }

        $changedPassword = false;
        DB::transaction(function () use ($user, $data, &$changedPassword) {
            $user->name = $data['name'];
            $user->email = $data['email'];
            // Ô không gửi lên thì giữ giá trị cũ
            $user->forceFill(['role' => (int) $data['role'], 'department' => array_key_exists('department', $data) ? ($data['department'] ?: null) : $user->department]);
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                // Đổi mật khẩu: bỏ "ghi nhớ đăng nhập" cũ của tài khoản này
                $user->setRememberToken(null);
                $changedPassword = true;
            }
            $this->saveProfile($user, $data);
            $user->save();
        });
        Log::info('Sửa tài khoản', ['user_id' => $user->id, 'password_changed' => $changedPassword, 'by' => Auth::id()]);

        return Redirect::route('accounts.index')->with('success', "Đã cập nhật tài khoản {$user->name}" . ($changedPassword ? ' (đã đổi mật khẩu)' : '') . '.');
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return Redirect::route('accounts.index')->with('error', 'Không xóa được chính tài khoản đang đăng nhập.');
        }
        if ((int) $user->role === 0 && User::where('role', 0)->count() <= 1) {
            return Redirect::route('accounts.index')->with('error', 'Phải còn ít nhất một tài khoản Admin.');
        }
        $label = "{$user->name} ({$user->email})";
        DB::transaction(function () use ($user) {
            $profileId = $user->profile_id;
            $user->delete();
            // Thông tin cá nhân chỉ của tài khoản này thì xóa theo
            if ($profileId && !User::where('profile_id', $profileId)->exists()) {
                Profile::whereKey($profileId)->delete();
            }
        });
        Log::info('Xóa tài khoản', ['account' => $label, 'by' => Auth::id()]);

        return Redirect::route('accounts.index')->with('success', "Đã xóa tài khoản {$label}.");
    }

    private function formData(?User $user): array
    {
        return [
            'account' => $user,
            'profile' => $user && $user->profile_id ? Profile::find($user->profile_id) : null,
            'roles' => self::ROLES + ($user && !array_key_exists((int) $user->role, self::ROLES) ? [(int) $user->role => 'Role ' . $user->role] : []),
            'departments' => config('departments', []),
            'specialNames' => self::SPECIAL_NAMES,
            'isMe' => $user && Auth::id() === $user->id,
        ];
    }

    private function validated(Request $request, ?User $user): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'integer', Rule::in(array_merge(array_keys(self::ROLES), $user ? [(int) $user->role] : []))],
            'department' => ['nullable', 'string', Rule::in(array_keys(config('departments', [])))],
            'profile_lastname' => ['nullable', 'string', 'max:70'],
            'profile_firstname' => ['nullable', 'string', 'max:70'],
            'profile_phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'profile_gender' => ['nullable', 'in:0,1'],
            'day_of_birth' => ['nullable', 'date'],
        ], [
            'name.required' => 'Vui lòng nhập tên tài khoản.',
            'email.required' => 'Vui lòng nhập email đăng nhập.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã có tài khoản khác dùng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu tối thiểu 6 ký tự.',
            'password.confirmed' => 'Nhập lại mật khẩu chưa khớp.',
            'profile_phone.regex' => 'Số điện thoại 10 số, bắt đầu bằng 0.',
        ]);
    }

    /** Thông tin cá nhân (bảng profiles): có sẵn thì sửa; chưa có mà có nhập thì tạo mới */
    private function saveProfile(User $user, array $data): void
    {
        $fields = ['profile_lastname', 'profile_firstname', 'profile_phone', 'profile_gender', 'day_of_birth'];
        $filled = collect($fields)->contains(fn ($f) => ($data[$f] ?? '') !== '' && ($data[$f] ?? null) !== null && $f !== 'profile_gender');
        $profile = $user->profile_id ? Profile::find($user->profile_id) : null;
        if (!$profile && !$filled) {
            return;
        }
        $profile ??= new Profile();
        // Ô không gửi lên thì giữ giá trị cũ
        $keep = fn ($f, $d) => array_key_exists($f, $data) ? $data[$f] : ($profile->{$f} ?? $d);
        $profile->forceFill([
            'profile_lastname' => (string) ($keep('profile_lastname', '') ?? ''),
            'profile_firstname' => (string) ($keep('profile_firstname', '') ?? ''),
            'profile_phone' => (string) ($keep('profile_phone', '') ?? ''),
            'profile_email' => $data['email'],
            'profile_gender' => (int) ($keep('profile_gender', 0) ?? 0),
            'day_of_birth' => $keep('day_of_birth', null) ?: null,
        ])->save();
        $user->profile_id = $profile->id;
    }
}
