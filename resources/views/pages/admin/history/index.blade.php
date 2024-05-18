@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Lịch sử chỉnh sửa
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
                <thead>
                    <tr>
                        <th>Mã lịch sử</th>
                        <th>Mã đơn hàng</th>
                        <th>Tên user</th>
                        <th>Hành động</th>
                        <th>Thời gian </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($all_history as $key => $his)
                        <tr>
                            <td>{{ $his->id }}</td>
                            <td>{{ $his->order_id }}</td>
                            <td>{{ $his->user_name }}</td>
                            <td>{{ $his->history_action }}</td>
                            <td>{{ $his->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $all_history->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection

