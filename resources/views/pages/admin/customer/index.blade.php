@extends('layouts.default_auth')
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách khách hàng
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light" id="myTable">
                <thead>
                    <tr>
                        <th>Mã khách hàng</th>
                        <th>Họ tên khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Địa chỉ</th>
                        <th>Địa chỉ khác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getAllCustomer as $key => $customer)
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>{{ $customer->customer_name }}</td>
                            <td>{{ $customer->customer_phone }}</td>
                            <td>{{ $customer->customer_address }}</td>
                            <td>{{ $customer->customer_note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $getAllCustomer->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
