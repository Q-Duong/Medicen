@switch($currentChange)
    @case('accountant_deadline')
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
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ $filter }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_cost')
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_price')
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_day_payment')
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
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_owe')
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('order_percent_discount')
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
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_discount_day')
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
        @if (isset($filters) && !empty($filters))
            @foreach ($filters as $filter)
                <option value="{{ $filter }}">
                    {{ number_format($filter, 0, ',', '.') }}
                </option>
            @endforeach
        @endif
    @break

    @case('accountant_doctor_date_payment')
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