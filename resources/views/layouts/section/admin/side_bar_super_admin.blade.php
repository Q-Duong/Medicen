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
                @if (Auth::user()->name == 'Admin')
                    <li>
                        <a class="{{ request()->routeIs('profit.index') ? 'active' : '' }}"
                            href="{{ route('profit.index') }}">
                            <i class="far fa-chart-bar"></i>
                            <span>Tính lợi nhuận X-Quang</span>
                        </a>
                    </li>
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
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('unit.index') || request()->routeIs('unit.create') || request()->routeIs('unit.edit') ? 'active' : '' }}"
                            href="javascript:;">
                            <i class="fas fa-file-alt"></i>
                            <span>Quản lý đơn vị</span>
                        </a>
                        <ul class="sub">
                            <li>
                                <a class="{{ request()->routeIs('unit.create') ? 'active' : '' }}"
                                    href="{{ route('unit.create') }}">
                                    <i class="fas fa-user-plus"></i> Thêm đơn vị
                                </a>
                            </li>
                            <li>
                                <a class="{{ request()->routeIs('unit.index') ? 'active' : '' }}"
                                    href="{{ route('unit.index') }}">
                                    <i class="fas fa-list-ol"></i> Danh sách đơn vị
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('staff.index') || request()->routeIs('staff.create') || request()->routeIs('staff.edit') ? 'active' : '' }}"
                            href="javascript:;">
                            <i class="fas fa-user"></i>
                            <span>Quản lý nhân viên</span>
                        </a>
                        <ul class="sub">
                            <li>
                                <a class="{{ request()->routeIs('staff.create') ? 'active' : '' }}"
                                    href="{{ route('staff.create') }}">
                                    <i class="fas fa-user-plus"></i> Thêm nhân viên
                                </a>
                            </li>
                            <li>
                                <a class="{{ request()->routeIs('staff.index') ? 'active' : '' }}"
                                    href="{{ route('staff.index') }}">
                                    <i class="fas fa-list-ol"></i> Danh sách nhân viên
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- <li class="sub-menu">
                        <a class="{{ request()->routeIs('post.index') || request()->routeIs('post.create') || request()->routeIs('post.edit') ? 'active' : '' }}"
                            href="javascript:;">
                            <i class="fab fa-blogger-b"></i>
                            <span>Quản lý bài viết</span>
                        </a>
                        <ul class="sub">
                            <li>
                                <a class="{{ request()->routeIs('post.create') ? 'active' : '' }}"
                                    href="{{ route('post.create') }}">
                                    <i class="far fa-plus-square"></i> Thêm bài viết
                                </a>
                            </li>
                            <li>
                                <a class="{{ request()->routeIs('post.index') ? 'active' : '' }}"
                                    href="{{ route('post.index') }}">
                                    <i class="far fa-list-alt"></i> Danh sách bài viết
                                </a>
                            </li>
                        </ul>
                    </li> --}}

                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('accountant.index') ? 'active' : '' }}"
                            href="{{ route('accountant.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Quản lý công nợ</span>
                        </a>
                    </li>
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('contract.index') ? 'active' : '' }}"
                            href="{{ route('contract.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Quản lý hợp đồng</span>
                        </a>
                    </li>
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('statistics.index') ? 'active' : '' }}"
                            href="{{ route('statistics.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Tính KPI</span>
                        </a>
                    </li>
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('employee_timekeeping.index') ? 'active' : '' }}"
                            href="{{ route('employee_timekeeping.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Chấm công</span>
                        </a>
                    </li>
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('reports.index') ? 'active' : '' }}"
                            href="{{ route('reports.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Báo cáo thu chi</span>
                        </a>
                    </li>
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('history.index') ? 'active' : '' }}"
                            href="{{ route('history.index') }}">
                            <i class="fas fa-history"></i>
                            <span>Quản lý chỉnh sửa đơn hàng</span>
                        </a>
                    </li>
                @elseif (Auth::user()->name == 'SubAdmin')
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('accountant.index') ? 'active' : '' }}"
                            href="{{ route('accountant.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Quản lý công nợ</span>
                        </a>
                    </li>
                    <li class="sub-menu">
                        <a class="{{ request()->routeIs('reports.index') ? 'active' : '' }}"
                            href="{{ route('reports.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Báo cáo thu chi</span>
                        </a>
                    </li>
                @endif
            </ul>
            <!-- sidebar menu end-->
        </div>
    </div>
</aside>
