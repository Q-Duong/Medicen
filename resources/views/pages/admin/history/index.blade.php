@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Lịch sử chỉnh sửa
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light table-bordered">
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
                    @foreach ($getAllHistory as $key => $history)
                        <tr>
                            <td>{{ $history->id }}</td>
                            <td>{{ $history->order_id }}</td>
                            <td>{{ $history->user_name }}</td>
                            <td>{{ $history->history_action }}</td>
                            <td>{{ $history->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $getAllHistory->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection