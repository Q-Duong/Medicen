@extends('admin_layout')
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
                    @foreach ($all_staff as $key => $staff)
                        <tr>
                            <td>{{ $staff->staff_id }}</td>
                            <td>{{ $staff->staff_name }}</td>
                            <td>{{ $staff->staff_phone }}</td>
                            <td>{{ $staff->staff_gender == 0 ? 'Nam' : 'Nữ' }}</td>
                            <td>{{ $staff->staff_birthday }}</td>
                            <td>{{ $staff->staff_role }}</td>
                            <td>
                                <a href="{{ route('edit-staff', $staff->staff_id) }}" class="active style-edit"
                                    ui-toggle-class=""><i class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <a onclick="return confirm('Bạn có chắc muốn xóa nhân viên?')"
                                    href="{{ route('delete-staff', $staff->staff_id) }}" class="active style-edit"
                                    ui-toggle-class="">
                                    <i class="fa fa-times text-danger text"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('backend/js/datatables/jquery.dataTables.min.js') }}" defer></script>
    <script type="text/javascript" defer>
        $(document).ready(function() {
            $('#myTable').DataTable();
        });
    </script>
@endpush
