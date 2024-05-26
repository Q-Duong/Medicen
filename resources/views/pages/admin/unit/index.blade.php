@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/pagination.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Liệt kê Đơn vị
        </div>
        <div class="table-responsive table-content">
            <table class="table table-striped b-t b-light table-bordered">
                <thead>
                    <tr>
                        <th>Mã đơn vị</th>
                        <th>Đơn vị</th>
                        <th style="width:60px;">Quản lý</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getAllUnit as $key => $unit)
                        <tr>
                            <td>{{ $unit->unit_code }}</td>
                            <td>{{ $unit->unit_name }}</td>
                            <td class="management">
                                <a href="{{ route('unit.edit', $unit->id) }}" class="management-btn"><i
                                        class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <form action="{{ route('unit.destroy', $unit->id) }}" method="POST">
                                    @method('DELETE')
                                    @csrf
                                    @if (Auth::user()->role == 0)
                                        <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa đơn vị?')"
                                            href="{{ route('unit.destroy', $unit->id) }}" class="management-btn button-submit">
                                            <i class="fa fa-times text-danger text"></i>
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $getAllUnit->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ versionResource('assets/js/support/essential.js') }}" defer></script>
@endpush