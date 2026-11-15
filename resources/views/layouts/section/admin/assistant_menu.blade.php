{{-- Trợ lý AI và Xếp lịch xe theo phòng ban của tài khoản (Admin thấy tất cả) --}}
@foreach (\App\Services\Claude\AssistantAccess::assistantsFor(Auth::user()) as $assistantKey => $assistantLabel)
    <li>
        <a class="{{ request()->routeIs('assistant.*') && request()->route('department') === $assistantKey ? 'active' : '' }}"
            href="{{ route('assistant.show', $assistantKey) }}">
            <i class="fas fa-robot"></i>
            <span>{{ $assistantLabel }} (AI)</span>
        </a>
    </li>
@endforeach
@if (\App\Services\Claude\AssistantAccess::isAdmin(Auth::user()) || in_array(Auth::user()->department, config('schedule.user_departments', []), true))
    <li>
        <a class="{{ request()->routeIs('dispatch.*') ? 'active' : '' }}" href="{{ route('dispatch.index') }}">
            <i class="fas fa-shuttle-van"></i>
            <span>Xếp lịch xe (AI gợi ý)</span>
        </a>
    </li>
@endif
@if (\App\Services\Claude\AssistantAccess::isAdmin(Auth::user()))
    <li>
        <a class="{{ request()->routeIs('user-departments.*') ? 'active' : '' }}"
            href="{{ route('user-departments.index') }}">
            <i class="fas fa-user-shield"></i>
            <span>Phân quyền phòng ban</span>
        </a>
    </li>
    <li>
        <a class="{{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}">
            <i class="fas fa-users-cog"></i>
            <span>Quản lý tài khoản</span>
        </a>
    </li>
@endif
