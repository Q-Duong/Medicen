@extends('admin_layout')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Liệt kê Banner
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
                <thead>
                    <tr>
                        <th>Mã slider</th>
                        <th>Tên slide</th>
                        <th>Hình ảnh</th>
                        <th>Mô tả</th>
                        <th style="width:60px;">Quản lý</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($all_slide as $key => $slider)
                        <tr>
                            <td>{{ $slider->slider_id }}</td>
                            <td>{{ $slider->slider_name }}</td>
                            <td><img src="uploads/slider/{{ $slider->slider_image }}" height="150" width="520"></td>
                            <td>
                                <textarea rows="4" cols="10">
                            {{ $slider->slider_desc }}
                            </textarea>
                            </td>
                            <td>
                                <a href="{{ route('edit-slider', $slider->slider_id) }}" class="active style-edit"
                                    ui-toggle-class=""><i class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <a onclick="return confirm('Bạn có chắc là muốn xóa slide này ko?')"
                                    href="{{ route('delete-slider', $slider->slider_id) }}" class="active styling-edit"
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
