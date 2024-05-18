@extends('layouts.default_auth')
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
                    @foreach ($getAllSlider as $key => $slider)
                        <tr>
                            <td>{{ $slider->id }}</td>
                            <td>{{ $slider->slider_name }}</td>
                            <td><img src="{{ asset('uploads/slider/' . $slider->slider_image) }}" height="150"
                                    width="520">
                            </td>
                            <td>
                                <textarea rows="4" cols="10">
                            {{ $slider->slider_desc }}
                            </textarea>
                            </td>
                            <td>
                                <a href="{{ route('slider.edit', $slider->id) }}" class="active style-edit"
                                    ui-toggle-class=""><i class="fa fa-pencil-square-o text-success text-active"></i>
                                </a>
                                <form action="{{ route('slider.destroy', $slider->id) }}" method="POST">
                                    @method('delete')
                                    @csrf
                                    <button onclick="return confirm('Bạn có chắc là muốn xóa slide này ko?')" href=""
                                        class="active styling-edit" ui-toggle-class="">
                                        <i class="fa fa-times text-danger text"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $getAllSlider->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
