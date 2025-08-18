@foreach ($getAllAccountant as $key => $accountant)
    @php
        $class = '';
        if ($accountant->accountant_status == 0) {
            if (now()->diffInDays($accountant->ord_start_day) >= 60) {
                $class = 'debt-overrated';
            } elseif (now()->diffInDays($accountant->ord_start_day) >= 45) {
                $class = 'debt-warning';
            }
        }
    @endphp
    <tr class="{{ $class }}">
        <td class="sticky-col first-col order_id">{{ $accountant->order_id }}</td>

        <td class="sticky-col second-col">{{ $accountant->accountant_month }}</td>

        <td class="sticky-col third-col">
            {{ date('d/m/Y', strtotime($accountant->ord_start_day)) }}</td>
        <td class="sticky-col fourth-col">
            @if ($accountant->car_name == 6)
                Xe thuê
            @elseif($accountant->car_name == 7)
                Xe tăng cường
            @else
                {{ $accountant->car_name }}
            @endif
        </td>
        <td class="sticky-col fifth-col">
            {{ $accountant->accountant_distance }}
        </td>
        <td title="{{ $accountant->unit_name }}" class="sticky-col six-col">
            {{ $accountant->unit_name }}</td>
        <td title="{{ capitalizeWordsExceptAbbreviations($accountant->ord_cty_name) }}" class="sticky-col seven-col">
            {{ capitalizeWordsExceptAbbreviations($accountant->ord_cty_name) }}</td>

        <td>
            {{ $accountant->accountant_deadline }}
        </td>

        <td>
            {{ $accountant->accountant_number }}
        </td>

        <td>
            {{ $accountant->accountant_date != null ? date('d/m/Y', strtotime($accountant->accountant_date)) : '' }}
        </td>

        <td>
            {{ $accountant->order_vat }}
        </td>

        <td>
            {{ $accountant->order_quantity }}
        </td>

        <td>
            {{ number_format($accountant->order_cost, 0, ',', '.') }}
        </td>

        <td>
            {{ number_format($accountant->order_price, 0, ',', '.') }}
        </td>

        <td>
            <select
                class="selectbox-accountant {{ $accountant->accountant_status == 0 ? 'acc-status-unpaid' : 'acc-status-paid' }}">
                <option value="0" {{ $accountant->accountant_status == 0 ? 'selected' : '' }}>
                    Chưa thanh toán</option>
                <option value="1" {{ $accountant->accountant_status == 1 ? 'selected' : '' }}>Đã
                    thanh toán</option>
            </select>
        </td>

        <td>
            {{ $accountant->accountant_day_payment != null ? date('d/m/Y', strtotime($accountant->accountant_day_payment)) : '' }}
        </td>

        <td>
            {{ $accountant->accountant_method }}
        </td>

        <td>
            {{ number_format($accountant->accountant_amount_paid, 0, ',', '.') }}
        </td>

        <td>
            {{ number_format($accountant->accountant_owe, 0, ',', '.') }}
        </td>

        <td>
            {{ $accountant->order_percent_discount }}
        </td>

        <td>
            {{ number_format($accountant->order_discount, 0, ',', '.') }}
        </td>

        <td>
            {{ $accountant->accountant_discount_day != null ? date('d/m/Y', strtotime($accountant->accountant_discount_day)) : '' }}
        </td>

        <td>
            {{ number_format($accountant->order_profit, 0, ',', '.') }}
        </td>

        <td>
            {{ $accountant->accountant_doctor_read }}
        </td>

        <td>
            {{ $accountant->accountant_doctor_date_payment != null ? date('d/m/Y', strtotime($accountant->accountant_doctor_date_payment)) : '' }}
        </td>

        <td>
            {{ $accountant->ord_form == 'PhimLon' ? 'IN35X43' : $accountant->ord_form }}
        </td>

        <td>
            {{ $accountant->accountant_35X43 }}
        </td>

        <td>
            {{ $accountant->accountant_polime }}
        </td>

        <td>
            {{ $accountant->accountant_8X10 }}
        </td>

        <td>
            {{ $accountant->accountant_10X12 }}
        </td>

        <td>
            {{ $accountant->accountant_film_bag }}
        </td>

        <td>
            {{ $accountant->accountant_note }}
        </td>
        <td>
            {{ $accountant->ord_note }}
        </td>
        <td class="status_id_{{ $accountant->order_id }} white-col">
            @if ($accountant->status_id == 0)
                <span style="color: #27c24c;">Đơn hàng mới</span>
            @elseif($accountant->status_id == 1)
                <span style="color: #FCB322;">Đang xử lý</span>
            @elseif($accountant->status_id == 2)
                <span style="color: #c037df;">Đã cập nhật số Cas thực tế</span>
            @elseif($accountant->status_id == 3)
                <span style="color: #0071e3;">Đã xử lý</span>
            @elseif($accountant->status_id == 4)
                <span style="color: #00d0e3;">Đã cập nhật doanh thu</span>
            @else
                <span style="color: #e53637;">Hủy đơn hàng</span>
            @endif
        </td>
    </tr>
@endforeach
