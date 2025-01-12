@extends('layouts.default')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/schedule.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/org-form.built.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/file.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/filepond.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/filepond-preview.css') }}" type="text/css"
        as="style" />
@endpush
@section('content')
@section('title', 'Lịch xe chi tiết - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>LỊCH XE CHI TIẾT</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ URL::to('/') }}">Trang chủ</a>
                        <span>Lịch xe chi tiết</span>
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
    var url_get_schedule = "{{ route('schedule.get.details') }}";
    var url_select_details = "{{ route('schedule.select.details') }}";
    var url_update_details = "{{ route('schedule.update.details') }}";
    var url_search_suggest = "{{ route('schedule.suggest.details') }}";
    var url_schedule_search = "{{ route('schedule.search.details') }}";
    var url_file_process = "{{ route('file.process') }}";
    var url_file_revert = "{{ route('file.revert') }}";
    var url_file_delete_total = "{{ route('file.delete_file_total') }}";
</script>
<script src="{{ versionResource('assets/js/support/file/filepond.js') }}"></script>
<script src="{{ versionResource('assets/js/support/file/filepond-preview.js') }}"></script>
<script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
<script src="{{ versionResource('assets/js/tool/schedule/details/schedule.js') }}"></script>
@endpush