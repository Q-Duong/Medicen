@switch($currentChange)
    @case('accountant_deadline')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_number')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_date')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ date('d/m/Y', strtotime($filter)) }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_vat')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_quantity')
        <option value="all">All</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_cost')
        <option value="all">All</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_price')
        <option value="all">All</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_day_payment')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ date('d/m/Y', strtotime($filter)) }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_amount_paid')
        <option value="all">All</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_owe')
        <option value="all">All</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_percent_discount')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_discount')
        <option value="all">All</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_discount_day')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ date('d/m/Y', strtotime($filter)) }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_profit')
        <option value="all">All</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_doctor_date_payment')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ date('d/m/Y', strtotime($filter)) }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_35X43')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_polime')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_8X10')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_10X12')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_film_bag')
        <option value="all">All</option>
        <option value="empty">Empty</option>
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @default
        
@endswitch