@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/accountant.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="table-agile-info">
        <div class="panel-heading">
            Danh sách hợp đồng
        </div>
        <div class="alert alert-success" style="display:none"></div>
        <div class="filter-accountant">
            <div class="filter-accountant-title">
                <p class="filter-accountant-title-text">
                    Filter
                </p>
            </div>
            <div class="filter-accountant-content">
                <div class="row">
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="cd-intro-month">
                                <label for="schedule-label">Year</label>
                                <select id="schedule-label" data-select="year" class="input-control year-filter">
                                    <option value="all" {{ Session::get('year') == 'all' ? 'selected' : '' }}>All </option>
                                    @for ($i = 0; $i <= 10; $i++)
                                        <option value="{{ $i + 2023 }}" {{ Session::get('year') == $i + 2023 ? 'selected' : '' }}>
                                            {{ $i + 2023 }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-accountant-content-block">
                            <div class="clear-filter hidden">
                                <label for="schedule-label">Filter</label>
                                <button type="submit" class="primary-btn-filter btn-clear-filter">Clear filter</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive table-content">
        </div>
    </div>
@endsection
@push('js')
    <script type="">
    var url_get_contract = "{{ route('contract.view_only.get') }}";
    var url_filter_contract = "{{ route('contract.view_only.filter') }}";
</script>
    <script src="{{ versionResource('assets/js/support/essential.js') }}"></script>
    <script src="{{ versionResource('backend/js/tool/select2.min.js') }}"></script>
    <script src="{{ versionResource('assets/js/tool/contract.js') }}"></script>
@endpush
