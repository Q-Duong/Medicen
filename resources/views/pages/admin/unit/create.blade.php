@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Thêm đơn vị
                    <span class="tools pull-right">
                        <a href="{{ route('unit.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form action="{{ route('unit.store') }}" method="post">
                            @csrf
                            <div class="form-group @error('unit_code') has-error @enderror">
                                <label for="exampleInputEmail1">Mã đơn vị</label>
                                <input type="text" name="unit_code" class="input-control" placeholder="Điền mã đơn vị"
                                    value="{{ old('unit_code') }}">
                                @error('unit_code')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group @error('unit_name') has-error @enderror">
                                <label for="exampleInputEmail1">Tên đơn vị</label>
                                <input type="text" name="unit_name" class="input-control" placeholder="Điền mã đơn vị"
                                    value="{{ old('unit_name') }}">
                                @error('unit_name')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="primary-btn-submit">Thêm đơn vị</button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
