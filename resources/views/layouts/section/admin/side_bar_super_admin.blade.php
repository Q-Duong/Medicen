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
                <li>
                    <a class="{{ request()->routeIs('contact.edit') ? 'active' : '' }}"
                        href="{{ route('contact.edit') }}">
                        <i class="fa fa-info-circle"></i>
                        <span>Thông tin Web NKL</span>
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
                <li>
                    <a href="{{ route('schedule.show.technologist') }}" target="_blank">
                        <i class="far fa-calendar-alt"></i>
                        <span>Lịch KTV và Xe</span>
                    </a>
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
                {{-- <li class="sub-menu">
                    <a class="{{ request()->routeIs('customer.index') ? 'active' : '' }}"
                        href="{{ route('customer.index') }}">
                        <i class="fas fa-users"></i>
                        <span>Quản lý khách hàng</span>
                    </a>
                </li> --}}

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

                <li class="sub-menu">
                    <a class="{{ request()->routeIs('service.index') || request()->routeIs('service.create') || request()->routeIs('service.edit') ? 'active' : '' }}"
                        href="javascript:;">
                        <i class="fas fa-server"></i>
                        <span>Quản lý dịch vụ</span>
                    </a>
                    <ul class="sub">
                        <li>
                            <a class="{{ request()->routeIs('service.create') ? 'active' : '' }}"
                                href="{{ route('service.create') }}">
                                <i class="fas fa-user-plus"></i> Thêm dịch vụ
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->routeIs('service.index') ? 'active' : '' }}"
                                href="{{ route('service.index') }}">
                                <i class="fas fa-list-ol"></i> Danh sách dịch vụ
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sub-menu">
                    <a class="{{ request()->routeIs('about.index') || request()->routeIs('about.create') || request()->routeIs('about.edit') ? 'active' : '' }}"
                        href="javascript:;">
                        <i class="fas fa-file-alt"></i>
                        <span>Quản lý giới thiệu</span>
                    </a>
                    <ul class="sub">
                        <li>
                            <a class="{{ request()->routeIs('about.create') ? 'active' : '' }}"
                                href="{{ route('about.create') }}">
                                <i class="fas fa-user-plus"></i> Thêm giới thiệu
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->routeIs('about.index') ? 'active' : '' }}"
                                href="{{ route('about.index') }}">
                                <i class="fas fa-list-ol"></i> Danh sách giới thiệu
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sub-menu">
                    <a class="{{ request()->routeIs('post_category.index') || request()->routeIs('post_category.create') || request()->routeIs('post_category.edit') ? 'active' : '' }}"
                        href="javascript:;">
                        <i class="fas fa-th"></i>
                        <span>Quản lý danh mục bài viết</span>
                    </a>
                    <ul class="sub">
                        <li>
                            <a class="{{ request()->routeIs('post_category.create') ? 'active' : '' }}"
                                href="{{ route('post_category.create') }}">
                                <i class="far fa-plus-square"></i> Thêm danh mục bài viết
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->routeIs('post_category.index') ? 'active' : '' }}"
                                href="{{ route('post_category.index') }}">
                                <i class="far fa-list-alt"></i> Danh mục bài viết
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sub-menu">
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
                </li>
                <li class="sub-menu">
                    <a class="{{ request()->routeIs('slider.index') || request()->routeIs('slider.create') || request()->routeIs('slider.edit') ? 'active' : '' }}"
                        href="javascript:;">
                        <i class="fa fa-picture-o"></i>
                        <span>Slider</span>
                    </a>
                    <ul class="sub">
                        <li>
                            <a class="{{ request()->routeIs('slider.create') ? 'active' : '' }}"
                                href="{{ route('slider.create') }}">
                                <i class="far fa-plus-square"></i> Thêm slider
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->routeIs('slider.index') ? 'active' : '' }}"
                                href="{{ route('slider.index') }}">
                                <i class="far fa-list-alt"></i> Quản lý slider
                            </a>
                        </li>
                    </ul>
                </li>
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
                    <a class="{{ request()->routeIs('history.index') ? 'active' : '' }}"
                        href="{{ route('history.index') }}">
                        <i class="fas fa-history"></i>
                        <span>Quản lý chỉnh sửa đơn hàng</span>
                    </a>
                </li>
            </ul>
            <!-- sidebar menu end-->
        </div>
    </div>
</aside>
