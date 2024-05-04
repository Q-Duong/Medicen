@extends('layout_not_slider')
@section('content')
@section('title', 'Lịch Chi Tiết - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>LỊCH CHI TIẾT KTV VÀ TÀI XẾ X QUANG</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ URL::to('/') }}">Trang chủ</a>
                        <span>Lịch chi tiết KTV và Tài Xế X Quang</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="schedule-render"></div>
@endsection
@push('js')
<script type="text/javascript">
    var url_get_schedule_details = "{{ route('call-schedule-office') }}";
    var url_select_month_details = "{{ route('select_month_details') }}";
    var url_update_order_quantity_details = "{{ route('update_order_quantity_details', ':id') }}";
    var url_suggest_schedule_search = "{{ route('suggest-schedule-search') }}";
    var url_schedule_search = "{{ route('schedule-search') }}";
    var _token = "{{ csrf_token() }}";
</script>
<script src="{{ asset('frontend/js/schedule.min.js') }}" defer></script>
@endpush
