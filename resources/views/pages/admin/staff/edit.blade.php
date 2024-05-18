@extends('layouts.default_auth')
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Cập nhật thông tin nhân viên
                    <span class="tools pull-right">
                        <a href="{{ route('staff.index') }}" class="primary-btn-submit">Quản lý</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    <div class="position-center">
                        <form action="{{ route('staff.update', $staff->id) }}" method="post">
                            @csrf
                            @method('patch')
                            <div class="form-group @error('staff_name') has-error @enderror">
                                <label for="exampleInputEmail1">Họ tên nhân viên</label>
                                <input type="text" name="staff_name" class="input-control"
                                    placeholder="Điền họ tên nhân viên" value="{{ $staff->staff_name }}">
                                @error('staff_name')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group @error('staff_phone') has-error @enderror">
                                <label for="exampleInputEmail1">Số điện thoại</label>
                                <input type="text" name="staff_phone" class="input-control"
                                    placeholder="Điền số điện thoại" value="{{ $staff->staff_phone }}">
                                @error('staff_phone')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Giới tính</label>
                                <select name="staff_gender" class="input-control">
                                    <option {{ $staff->staff_gender == 0 ? 'selected' : '' }} value="0">Nam</option>
                                    <option {{ $staff->staff_gender == 1 ? 'selected' : '' }} value="1">Nữ</option>
                                </select>
                            </div>
                            <div class="form-group @error('staff_birthday') has-error @enderror">
                                <label for="exampleInputPassword1">Ngày sinh</label>
                                <input type="date" class="input-control" name="staff_birthday"
                                    value="{{ $staff->staff_birthday }}">
                                @error('staff_birthday')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Vai trò nhân viên</label>
                                <select name="staff_role" class="input-control">
                                    <option {{ $staff->staff_role == 'KTV' ? 'selected' : '' }} value="KTV">Kỹ thuật
                                        viên
                                    </option>
                                    <option {{ $staff->staff_role == 'TX' ? 'selected' : '' }} value="TX">Tài xế
                                    </option>
                                </select>
                            </div>
                            <button type="submit" class="primary-btn-submit">Cập nhật thông tin nhân viên
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
