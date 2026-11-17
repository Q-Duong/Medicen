{{-- Sidebar chia nhóm cho mọi tài khoản; mục nào hiện theo quyền: App\Support\AdminMenu --}}
<aside>
    <div id="sidebar" class="nav-collapse">
        <div class="leftside-navigation">
            <ul class="sidebar-menu sidebar-menu--grouped" id="nav-accordion">
                @foreach (\App\Support\AdminMenu::sections(Auth::user()) as $section)
                    <li class="sidebar-group" aria-hidden="true">{{ $section['title'] }}</li>
                    @foreach ($section['items'] as $menuItem)
                        <li>
                            <a class="{{ $menuItem['active'] ? 'active' : '' }}" href="{{ $menuItem['url'] }}">
                                <i class="{{ $menuItem['icon'] }}"></i>
                                <span>{{ $menuItem['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    </div>
</aside>
