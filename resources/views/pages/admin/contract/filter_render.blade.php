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

    @default
        
@endswitch