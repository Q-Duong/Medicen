@extends('layouts.default_auth')
@push('css')
    <style>
        .na-wrap {
            min-height: calc(100vh - 140px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .na-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border: 1px solid #ece9e2;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(31, 30, 29, .06);
            padding: 36px 32px 30px;
            text-align: center;
        }

        .na-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #fbeee8;
            color: #c96442;
            font-size: 26px;
        }

        .na-card h1 {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 650;
            color: #1f1e1d;
        }

        .na-card p {
            margin: 0 0 6px;
            color: #6b6966;
            font-size: 14px;
            line-height: 1.6;
        }

        .na-who {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 14px 0 22px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #f5f4ef;
            color: #4a4845;
            font-size: 13px;
        }

        .na-who b {
            color: #1f1e1d;
            font-weight: 600;
        }

        .na-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .na-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none !important;
            border: 1px solid #e3e0d8;
            background: #fff;
            color: #3d3b38 !important;
            cursor: pointer;
        }

        .na-btn:hover {
            background: #f5f4ef;
        }

        .na-btn--primary {
            background: #c96442;
            border-color: #c96442;
            color: #fff !important;
        }

        .na-btn--primary:hover {
            background: #b4573a;
        }

        @media (max-width: 480px) {
            .na-card {
                padding: 28px 20px 24px;
            }
        }
    </style>
@endpush
@section('admin_content')
    @php
        $me = Auth::user();
        $dept = $me && $me->department ? (config('departments')[$me->department] ?? $me->department) : null;
    @endphp
    <div class="na-wrap">
        <div class="na-card" role="alert">
            <div class="na-icon"><i class="fas fa-lock"></i></div>
            <h1>Không có quyền truy cập</h1>
            <p>Tài khoản của bạn chưa được cấp quyền vào trang này.</p>
            <p>Nếu cần dùng, hãy liên hệ Admin để được phân quyền phòng ban.</p>
            @if ($me)
                <div class="na-who">
                    <i class="far fa-user"></i>
                    <span><b>{{ $me->display_name }}</b>{{ $dept ? ' · ' . $dept : ' · chưa có phòng ban' }}</span>
                </div>
            @endif
            <div class="na-actions">
                <button type="button" class="na-btn" onclick="history.length > 1 ? history.back() : (location.href='{{ route('admin.home') }}')">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </button>
                <a href="{{ route('admin.home') }}" class="na-btn na-btn--primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>
        </div>
    </div>
@endsection
