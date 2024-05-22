@extends('layouts.default')
@section('content')
@section('title', 'Log In Schedule - ')
    <section class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__text">
                        <h4>Đăng nhập</h4>
                        <div class="breadcrumb__links">
                            <a href="{{ URL::to('/') }}">Trang chủ</a>
                            <span>Đăng nhập</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="checkout spad">
        <div class="container">
            <div class="checkout__form">
                <form action="{{ URL::to('/login-schedule') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-5 col-md-5">
                            <h4 class="checkout__title">Đăng nhập</h4>
                            <div class="checkout__input">
                                <p>Tên đăng nhập hoặc email<span>*</span></p>
                                <input type="text" name="email" placeholder="Điền tên tài khoản hoặc Email" />
                            </div>
                            <div class="checkout__input">
                                <p>Mật khẩu<span>*</span></p>
                                <input type="password" name="password" placeholder="Điền mật khẩu" />
                            </div>
                            <div class="checkout__input">
                                <button type="submit" class="site-btn"><i class="fas fa-sign-in-alt"></i> Đăng
                                    nhập</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
