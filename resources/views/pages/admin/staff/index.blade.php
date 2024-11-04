@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách nhân viên
        </div>
        <div class="table-responsive table-content">
            <div id="table-scroll" class="table-scroll">
                <table class="table">
                    <thead>
                        <tr class="section-title">
                            <th>Mã nhân viên</th>
                            <th>Họ tên nhân viên</th>
                            <th>Số điện thoại</th>
                            <th>Giới tính</th>
                            <th>Ngày sinh</th>
                            <th>Vai trò</th>
                            <th>Quản lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($getAllStaff as $key => $staff)
                            <tr>
                                <td>{{ $staff->id }}</td>
                                <td>{{ $staff->staff_name }}</td>
                                <td>{{ $staff->staff_phone }}</td>
                                <td>{{ $staff->staff_gender == 0 ? 'Nam' : 'Nữ' }}</td>
                                <td>{{ $staff->staff_birthday }}</td>
                                <td>{{ $staff->staff_role }}</td>
                                <td class="management">
                                    <a href="{{ route('staff.edit', $staff->id) }}" class="management-btn"><i
                                            class="fa fa-pencil-square-o text-success text-active"></i>
                                    </a>
                                    <form action="{{ route('staff.destroy', $staff->id) }}" method="POST">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa nhân viên?')"
                                            class="management-btn button-submit">
                                            <i class="fa fa-times text-danger text"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @include('layouts.section.admin.pagination_showing_current', [
            'items' => $getAllStaff,
        ])
        {{ $getAllStaff->links('pagination::custom') }}
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
@endpush
