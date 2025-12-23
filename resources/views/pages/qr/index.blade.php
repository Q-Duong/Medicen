@extends('layouts.default')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/qrcode.css') }}" type="text/css" as="style" />
@endpush
@section('content')
@section('title', 'Tạo QR Code - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Tạo QR Code</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home.index') }}">Trang chủ</a>
                        <span>Tạo QR Code</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="qr-section">
    <div class="qr-card">
        <h2> Tạo QR Code</h2>
        <p>Hỗ trợ file Excel, chèn Logo công ty.</p>
    
        <div class="qr-input-group">
            <label class="input-label">1. Chọn danh sách (Excel)</label>
            <label for="excelFile" class="qr-custom-file">
                <span class="file-icon">📂</span>
                <span class="file-name" id="excelName">Chưa chọn file...</span>
            </label>
            <input type="file" id="excelFile" accept=".xlsx, .xls, .csv" onclick="this.value=null" onchange="updateName('excelFile', 'excelName')">
        </div>
    
        <div class="qr-input-group">
            <label class="input-label">2. Chọn Logo công ty (Tùy chọn)</label>
            <label for="logoFile" class="qr-custom-file">
                <span class="file-icon"><img src="{{ asset('assets/images/logo.png') }}" class="globalnav-link-image"
                    alt="Medicen"></span>
                <span class="file-name" id="logoName">Không có logo (Mặc định)</span>
            </label>
            <input type="file" id="logoFile" accept="image/*" onclick="this.value=null" onchange="updateName('logoFile', 'logoName')">
        </div>
    
        <button id="btnProcess" class="btn-main" onclick="processExcel()">TẢI XUỐNG</button>
        <button id="btnReset" class="btn-reset" onclick="resetAll()">🔄 Làm mới</button>
        <div id="status"></div>
    </div>
    
    <div id="hidden-area"></div>
</div>
@endsection
@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="{{ asset('assets/js/tool/qrcode/handle.js') }}"></script>
@endpush
