@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách nhân viên
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
                <thead>
                    <tr>
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
                            <td>
                                <a href="{{ route('staff.edit', $staff->id) }}" class="active style-edit"><i
                                        class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <form action="{{ route('staff.destroy', $staff->id) }}" method="POST">
                                    @method('DELETE')
                                    @csrf
                                    <button tyle="submit" onclick="return confirm('Bạn có chắc muốn xóa nhân viên?')"
                                        class="active style-edit" \>
                                        <i class="fa fa-times text-danger text"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $getAllStaff->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection

