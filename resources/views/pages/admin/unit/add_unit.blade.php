@extends('admin_layout')
@section('admin_content')
<div class="row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                Thêm đơn vị
                <span class="tools pull-right">
                    <a href="{{route('list-unit')}}" class="primary-btn-submit">Quản lý</a>
                    <a class="fa fa-chevron-down" href="javascript:;"></a>
                </span>
            </header>
            <div class="panel-body">
                <div class="position-center">
                    <form action="{{route('save-unit')}}" method="post">
                        @csrf
                        <div class="form-group {{ $errors->has('unit_code') ? 'has-error' : ''}}">
                            <label for="exampleInputEmail1">Mã đơn vị</label>
                            <input type="text" name="unit_code" class="input-control" placeholder="Điền mã đơn vị" value="{{ old('unit_code') }}">
                                {!! $errors->first('unit_code', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <div class="form-group {{ $errors->has('unit_name') ? 'has-error' : ''}}">
                            <label for="exampleInputEmail1">Tên đơn vị</label>
                            <input type="text" name="unit_name" class="input-control" placeholder="Điền mã đơn vị"
                                value="{{ old('unit_name') }}">
                                {!! $errors->first('unit_name', '<div class="alert-error"><i class="fa fa-exclamation-circle"></i> :message</div>') !!}
                        </div>
                        <button type="submit" class="primary-btn-submit">Thêm đơn vị</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection