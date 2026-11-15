@extends('layouts.default_auth')
@section('title', 'Tính KPI - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/dashboard.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    @php
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $nowMonth = now()->format('F');
        $nowYear = (int) now()->format('Y');
    @endphp
    <div class="ds">
        <div class="ds-head">
            <div class="ds-head__title">
                <h1>Tính KPI</h1>
                <p>Báo cáo hiệu suất (đúng hạn / trễ hạn) theo tháng của từng bộ phận, xuất ra file PDF.</p>
            </div>
        </div>

        <div class="ds-kpi">
            <form role="form" action="{{ route('statistics.performance_analysis') }}" method="post" class="ds-card ds-kpi__form">
                @csrf
                <h2 class="ds-card__title">Chọn tháng</h2>
                <div class="ds-kpi__fields">
                    <label class="ds-field"><span>Năm</span>
                        <select name="year" class="select-year">
                            @for ($y = $nowYear; $y >= 2023; $y--)
                                <option value="{{ $y }}" @selected($y === $nowYear)>{{ $y }}</option>
                            @endfor
                        </select>
                    </label>
                    <label class="ds-field"><span>Tháng</span>
                        <select name="month" class="select-month-details">
                            @foreach ($months as $i => $m)
                                <option value="{{ $m }}" @selected($m === $nowMonth)>Tháng {{ $i + 1 }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <button type="submit" class="ds-btn ds-btn--primary ds-btn--block"><i class="fas fa-file-pdf"></i> Xuất báo cáo KPI (PDF)</button>
                <p class="ds-hint">Tháng chưa có dữ liệu thì trang báo "Không có dữ liệu".</p>
            </form>

            <section class="ds-card">
                <h2 class="ds-card__title">Báo cáo gồm</h2>
                <ul class="ds-parts">
                    <li><span class="ds-part ds-part--1">1</span><div><b>Sales</b><p>Hiện hệ thống không còn ghi nhận KPI phần này (mục Sales trong báo cáo sẽ trống).</p></div></li>
                    <li><span class="ds-part ds-part--2">2</span><div><b>Kỹ thuật viên</b><p>Nhập số ca chụp (chấm công) chậm nhất trong ngày chụp, tính theo từng người.</p></div></li>
                    <li><span class="ds-part ds-part--3">3</span><div><b>Kết quả</b><p>Ngày trả kết quả so với thời hạn giao kết quả của đơn.</p></div></li>
                </ul>
                <p class="ds-hint">Điểm: đúng hạn 100, trễ hạn 0. Chỉ tính lần nhập đầu tiên của người không phải Admin.</p>
            </section>
        </div>
    </div>
@endsection
