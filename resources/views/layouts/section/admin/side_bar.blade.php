<aside>
    <div id="sidebar" class="nav-collapse">
        <!-- sidebar menu start-->
        <div class="leftside-navigation">
            <ul class="sidebar-menu" id="nav-accordion">
                <li>
                    <a class="{{ request()->routeIs('dashboard.index') ? 'active' : '' }}"
                        href="{{ route('dashboard.index') }}">
                        <i class="far fa-chart-bar"></i>
                        <span>Thống kê doanh thu</span>
                    </a>
                </li>
                @if (Auth::user()->name != 'Office')
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('order.index') || request()->routeIs('order.create') || request()->routeIs('order.edit') || request()->routeIs('order.copy') || request()->routeIs('schedule.create') || request()->routeIs('schedule.edit') ? 'active' : '' }}"
                            href="javascript:;">
                            <i class="fas fa-file-alt"></i>
                            <span>Quản lý đơn hàng</span>
                        </a>
                        <ul class="sub">
                            <li>
                                <a class="{{ request()->routeIs('order.create') ? 'active' : '' }}"
                                    href="{{ route('order.create') }}">
                                    <i class="fas fa-user-plus"></i> Thêm đơn hàng
                                </a>
                            </li>
                            <li>
                                <a class="{{ request()->routeIs('order.index') || request()->routeIs('schedule.create') || request()->routeIs('schedule.edit') ? 'active' : '' }}"
                                    href="{{ route('order.index') }}">
                                    <i class="fas fa-list-ol"></i> Danh sách đơn hàng
                                </a>
                            </li>

                        </ul>
                    </li>
                    <li>
                        <a href="{{ route('schedule.show.technologist') }}" target="_blank">
                            <i class="far fa-calendar-alt"></i>
                            <span>Lịch KTV và Xe</span>
                        </a>
                    </li>

                    @if (Auth::user()->role == 0 || Auth::user()->role == 1 || Auth::user()->role == 3)
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('accountant.index') ? 'active' : '' }}"
                                href="{{ route('accountant.index') }}">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span>Quản lý công nợ</span>
                            </a>
                        </li>
                    @elseif (Auth::user()->role == 0 || Auth::user()->role == 1 || Auth::user()->role == 2)
                        <li class="sub-menu">
                            <a class="{{ request()->routeIs('accountant_sales.index') ? 'active' : '' }}"
                                href="{{ route('accountant_sales.index') }}">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span>Quản lý công nợ (Sales)</span>
                            </a>
                        </li>
                    @endif
                @endif
            </ul>
            <!-- sidebar menu end-->
        </div>
    </div>
</aside>
