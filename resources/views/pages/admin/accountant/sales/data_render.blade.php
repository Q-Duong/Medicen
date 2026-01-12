@foreach ($accountantData as $key => $accountant)
    @php
        $class = '';
        if ($accountant->accountant_status == 0) {
            if ($accountant->ord_start_day && now()->diffInDays($accountant->ord_start_day) >= 60) {
                $class = 'debt-overrated';
            } elseif ($accountant->ord_start_day && now()->diffInDays($accountant->ord_start_day) >= 45) {
                $class = 'debt-warning';
            }
        }
        $isLocked = $accountant->status_id == 3;
    @endphp
    <tr class="{{ $class }} {{ $accountant->status_id == 3 ? 'row-locked' : '' }}"
        data-id="{{ $accountant->id }}">
        <td class="sticky-col first-col">{{ (isset($sttStart) ? $sttStart : 0) + $loop->iteration }}</td>
        <td class="sticky-col second-col order_id">{{ $accountant->order_id }}</td>

        <td class="sticky-col third-col">{{ $accountant->accountant_month }}</td>

        <td class="sticky-col fourth-col">
            {{ smartFormatDate($accountant->ord_start_day) }}</td>
        <td class="sticky-col fifth-col">
            {{ carRenameFunction($accountant->car_name) }}
        </td>
        <td class="sticky-col six-col">
            {{ $accountant->accountant_distance }}
        </td>
        <td title="{{ $accountant->unit_name }}" class="sticky-col seven-col">
            {{ $accountant->unit_name }}</td>
        <td title="{{ capitalizeWordsExceptAbbreviations($accountant->ord_cty_name) }}" class="sticky-col eighth-col">
            {{ capitalizeWordsExceptAbbreviations($accountant->ord_cty_name) }}</td>

        <td>
            <input type="text" name="accountant_deadline"
                class="textbox-accountant  width-accountant-min accountant_deadline input-accountant"
                value="{{ $accountant->accountant_deadline }}" disabled>
        </td>

        <td>
            <input type="text" name="accountant_number"
                class="textbox-accountant width-accountant-min accountant_number input-accountant"
                value="{{ $accountant->accountant_number }}" disabled>
        </td>

        <td>
            <input type="date" name="accountant_date"
                class="textbox-accountant  width-accountant-day accountant_date input-accountant force-date-format"
                value="{{ $accountant->accountant_date }}" disabled>
        </td>

        <td>
            <input type="text" name="order_vat"
                class="textbox-accountant width-accountant-price order_vat input-accountant"
                value="{{ $accountant->order_vat }}" disabled>
        </td>

        <td>
            <input type="text" name="order_quantity"
                class="textbox-accountant  width-accountant-quantity order_quantity calc-quantity calc-inputs"
                value="{{ formatCurrency($accountant->order_quantity) }}" disabled>
        </td>

        <td>
            <input type="text" name="order_cost"
                class="textbox-accountant  width-accountant-price order_cost calc-cost calc-inputs"
                value="{{ formatCurrency($accountant->order_cost) }}" disabled>
        </td>

        <td>
            <input type="text" name="order_price"
                class="textbox-accountant width-accountant-price order_price calc-price calc-inputs"
                value="{{ formatCurrency($accountant->order_price) }}" disabled>
        </td>

        <td>
            <select
                class="selectbox-accountant select-update accountant_status {{ $accountant->accountant_status == 0 ? 'acc-status-unpaid' : 'acc-status-paid' }}"
                name="accountant_status" disabled>
                <option value="0" {{ $accountant->accountant_status == 0 ? 'selected' : '' }}>Chưa thanh toán
                </option>
                <option value="1" {{ $accountant->accountant_status == 1 ? 'selected' : '' }}>Đã
                    thanh toán</option>
            </select>
        </td>

        <td>
            <input type="text" name="accountant_day_payment"
                class="textbox-accountant width-accountant-day accountant_day_payment input-accountant"
                value="{{ smartFormatDate($accountant->accountant_day_payment) }}" disabled>
        </td>

        <td>
            <select class="selectbox-accountant select-update accountant_method" name="accountant_method" disabled>
                <option value="" {{ $accountant->accountant_method == null ? 'selected' : '' }}>
                </option>
                <option value="HDB" {{ $accountant->accountant_method == 'HDB' ? 'selected' : '' }}>HDB</option>
                <option value="AGRI" {{ $accountant->accountant_method == 'AGRI' ? 'selected' : '' }}>AGRI
                </option>
                <option value="VCB" {{ $accountant->accountant_method == 'VCB' ? 'selected' : '' }}>VCB</option>
                <option value="TM" {{ $accountant->accountant_method == 'TM' ? 'selected' : '' }}>TM</option>
            </select>
        </td>

        <td>
            <input type="text" name="accountant_amount_paid"
                class="textbox-accountant  width-accountant-price accountant_amount_paid calc-paid calc-inputs"
                value="{{ formatCurrency($accountant->accountant_amount_paid) }}" disabled>
        </td>

        <td>
            <input type="text" name="accountant_owe"
                class="textbox-accountant  width-accountant-price accountant_owe calc-inputs"
                value="{{ formatCurrency($accountant->accountant_owe) }}" disabled>
        </td>

        <td>
            <input type="text" name="order_percent_discount"
                class="textbox-accountant  width-accountant-price order_percent_discount input-accountant"
                value="{{ $accountant->order_percent_discount }}" disabled>
        </td>

        <td>
            <input type="text" name="order_discount"
                class="textbox-accountant  width-accountant-price order_discount calc-inputs"
                value="{{ formatCurrency($accountant->order_discount) }}" disabled>
        </td>

        <td>
            <input type="date" name="accountant_discount_day"
                class="textbox-accountant  width-accountant-day accountant_discount_day input-accountant force-date-format"
                value="{{ $accountant->accountant_discount_day }}" disabled>
        </td>

        <td>
            <input type="text" name="order_profit"
                class="textbox-accountant  width-accountant-price order_profit calc-inputs"
                value="{{ formatCurrency($accountant->order_profit) }}" disabled>
        </td>

        <td>
            {{ $accountant->accountant_doctor_read }}
        </td>

        <td>
            <input type="date" name="accountant_doctor_date_payment"
                class="textbox-accountant  width-accountant-day accountant_doctor_date_payment input-accountant force-date-format"
                value="{{ $accountant->accountant_doctor_date_payment }}" disabled>
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
            <input type="text" name="accountant_note" class="textbox-accountant width-accountant-note"
                value="{{ $accountant->accountant_note }}" disabled>
        </td>
        <td>
            <input type="text" class="textbox-accountant width-accountant-note" value="{{ $accountant->ord_note }}"
                disabled>
        </td>
        <td class="status_id_{{ $accountant->order_id }} status-id white-col">
            @if ($accountant->status_id == 0)
                <span class="badge badge-primary text-white" style="background-color: #27c24c">Đơn hàng mới</span>
            @elseif($accountant->status_id == 1)
                <span class="badge badge-primary text-white" style="background-color: #FCB322">Đang xử lý</span>
            @elseif($accountant->status_id == 2)
                <span class="badge badge-primary text-white" style="background-color: #c037df">Đã cập nhật số Cas thực
                    tế</span>
            @elseif($accountant->status_id == 3)
                <span class="badge badge-primary text-white" style="background-color: #007bff">Đã xử lý</span>
            @elseif($accountant->status_id == 4)
                <span class="badge badge-primary text-white" style="background-color: #00d0e3">Đã cập nhật doanh
                    thu</span>
            @endif
        </td>
    </tr>
@endforeach
