@props([
    'field',
    'label' => '',
    'id' => null,
])

<div class="filter-accountant-content-block">
    <div class="dropdown" id="{{ $id ?? 'dropdown-' . $field }}">
        <label for="year-select">{{ $label }}</label>

        <button class="btn btn-default dropdown-toggle btn-icon-filter" type="button" data-toggle="dropdown">
            <span class="selected-text"><i class="fa-solid fa-filter"></i></span>
        </button>

        <div class="dropdown-menu excel-dropdown">
            @if ($label)
                <div class="dropdown-header-row d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                    <label class="dropdown-label mb-0 font-weight-bold">{{ $label }}</label>
                    <button type="button" class="btn btn-sm btn-link text-dark p-0 close-dropdown-btn" style="text-decoration: none;">
                        <i class="fa-solid fa-xmark" style="font-size: 11px;"></i>
                    </button>
                </div>
            @endif

            <div class="dropdown-detail">
                <label class="sub-label">Filter</label>
                <div class="search-wrapper mb-2">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" class="form-control form-control-sm search-in-dropdown" placeholder="Search">
                </div>

                <div class="dropdown-list">
                    <div class=" border-bottom bg-light">
                        <label class="mb-0 font-weight-bold p-2" style="cursor: pointer; display:block;">
                            <input type="checkbox" class="check-all" checked> (Select All)
                        </label>
                    </div>

                    <div class="dropdown-list-container" data-field="{{ $field }}">
                        <div class="text-center text-muted p-2">Loading...</div>
                    </div>
                </div>

                <div class="p-2 border-top text-right bg-light mt-2">
                    <button type="button" class="btn btn-primary btn-sm btn-apply-filter">
                        Apply Filter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>