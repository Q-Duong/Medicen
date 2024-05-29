@switch($subCurrentChange)
    @case('order_price')
        <option value="all">All</option>
        @if (isset($subFilters) && !empty($subFilters))
            @foreach ($subFilters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_owe')
        <option value="all">All</option>
        @if (isset($subFilters) && !empty($subFilters))
            @foreach ($subFilters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_profit')
        <option value="all">All</option>
        @if (isset($subFilters) && !empty($subFilters))
            @foreach ($subFilters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @default
        
@endswitch