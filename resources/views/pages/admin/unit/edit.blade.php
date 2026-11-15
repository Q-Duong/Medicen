@extends('layouts.default_auth')
@section('title', 'Sửa đơn vị - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Sửa đơn vị <span class="sf-muted">{{ $unit->unit_code }}</span></h1>
                <p>{{ $unit->unit_abbreviation }} · {{ $ordersCount > 0 ? number_format($ordersCount, 0, ',', '.') . ' đơn hàng' : 'chưa có đơn hàng' }}</p>
            </div>
            <a href="{{ route('unit.index') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-arrow-left"></i> Danh sách đơn vị</a>
        </div>
        @if ($ordersCount > 0)
            <div class="sf-notice"><i class="fas fa-info-circle"></i> Đơn vị đã có {{ number_format($ordersCount, 0, ',', '.') }} đơn hàng: đổi tên viết tắt thì các đơn, lịch xe, công nợ cũ cũng hiện theo tên mới. Không xóa được đơn vị này.</div>
        @endif
        <form action="{{ route('unit.update', $unit->id) }}" method="post" class="sf-card" id="unit-form">
            @csrf
            @method('patch')
            @include('pages.admin.unit._form', ['unit' => $unit])
        </form>
        <div class="sf-form-actions sf-form-actions--split">
            @if ($ordersCount === 0)
                <form action="{{ route('unit.destroy', $unit->id) }}" method="POST"
                    data-confirm="Xóa đơn vị {{ $unit->unit_abbreviation }} ({{ $unit->unit_code }})? Đơn vị này chưa có đơn hàng nào."
                    data-confirm-title="Xóa đơn vị" data-confirm-ok="Xóa" data-confirm-danger>
                    @method('DELETE')
                    @csrf
                    <button type="submit" class="sf-btn sf-btn--danger"><i class="far fa-trash-alt"></i> Xóa đơn vị</button>
                </form>
            @else
                <span></span>
            @endif
            <div class="sf-form-actions__right">
                <a href="{{ route('unit.index') }}" class="sf-btn sf-btn--ghost">Hủy</a>
                <button type="submit" form="unit-form" class="sf-btn sf-btn--primary"><i class="fas fa-check"></i> Lưu thay đổi</button>
            </div>
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
@endpush
