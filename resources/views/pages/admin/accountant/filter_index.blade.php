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
        <form class="updateAccountant_{{ $accountant->order_id }}">
            @csrf
            <input type="hidden" name="accountant_id_{{ $accountant->order_id }}" value="{{ $accountant->id }}">
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
                <input type="text" name="accountant_deadline_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-min accountant_deadline_{{ $accountant->order_id }}"
                    value="{{ $accountant->accountant_deadline }}" onclick="deadlineFunction(event)">
            </td>

            <td>
                <input type="text" name="accountant_number_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-min" value="{{ $accountant->accountant_number }}">
            </td>

            <td>
                <input type="text" name="accountant_date_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-day accountant_date_{{ $accountant->order_id }}"
                    value="{{ $accountant->accountant_date != null ? date('d/m/Y', strtotime($accountant->accountant_date)) : '' }}"
                    onclick="dateFunction(event)">
            </td>

            <td>
                <input type="text" name="order_vat_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price" value="{{ $accountant->order_vat }}">
            </td>

            <td>
                <input type="text" name="order_quantity_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-quantity order_quantity_{{ $accountant->order_id }}"
                    value="{{ $accountant->order_quantity }}" onclick="quantityFunction(event)">
            </td>

            <td>
                <input type="text" name="order_cost_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price order_cost_{{ $accountant->order_id }}"
                    value="{{ number_format($accountant->order_cost, 0, ',', '.') }}" onclick="costFunction(event)">
            </td>

            <td>
                <input type="text" name="order_price_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price  order_price_{{ $accountant->order_id }}"
                    value="{{ number_format($accountant->order_price, 0, ',', '.') }}" onclick="priceFunction(event)">
            </td>

            <td>
                <select
                    class="selectbox-accountant select-update accountant_status accountant_status_{{ $accountant->order_id }} {{ $accountant->accountant_status == 0 ? 'acc-status-unpaid' : 'acc-status-paid' }}"
                    name="accountant_status_{{ $accountant->order_id }}">
                    <option value="0" {{ $accountant->accountant_status == 0 ? 'selected' : '' }}>Chưa thanh toán
                    </option>
                    <option value="1" {{ $accountant->accountant_status == 1 ? 'selected' : '' }}>Đã thanh toán
                    </option>
                </select>
            </td>

            <td>
                <input type="text" name="accountant_day_payment_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-day"
                    value="{{ $accountant->accountant_day_payment != null ? date('d/m/Y', strtotime($accountant->accountant_day_payment)) : '' }}">
            </td>

            <td>
                <select class="selectbox-accountant select-update accountant_method_{{ $accountant->order_id }}"
                    name="accountant_method_{{ $accountant->order_id }}">
                    <option value="" {{ $accountant->accountant_method == null ? 'selected' : '' }}></option>
                    <option value="HDB" {{ $accountant->accountant_method == 'HDB' ? 'selected' : '' }}>HDB</option>
                    <option value="AGRI" {{ $accountant->accountant_method == 'AGRI' ? 'selected' : '' }}>AGRI
                    </option>
                    <option value="VCB" {{ $accountant->accountant_method == 'VCB' ? 'selected' : '' }}>VCB</option>
                    <option value="TM" {{ $accountant->accountant_method == 'TM' ? 'selected' : '' }}>TM</option>
                </select>
            </td>

            <td>
                <input type="text" name="accountant_amount_paid_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price accountant_amount_paid_{{ $accountant->order_id }}"
                    value="{{ number_format($accountant->accountant_amount_paid, 0, ',', '.') }}"
                    onclick="amountPaidFunction(event)">
            </td>

            <td>
                <input type="text" name="accountant_owe_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price accountant_owe_{{ $accountant->order_id }}"
                    value="{{ number_format($accountant->accountant_owe, 0, ',', '.') }}">
            </td>

            <td>
                <input type="text" name="order_percent_discount_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price order_percent_discount_{{ $accountant->order_id }}"
                    value="{{ $accountant->order_percent_discount }}">
            </td>

            <td>
                <input type="text" name="order_discount_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price order_discount_{{ $accountant->order_id }}"
                    value="{{ number_format($accountant->order_discount, 0, ',', '.') }}"
                    onclick="discountFunction(event)">
            </td>

            <td>
                <input type="text" name="accountant_discount_day_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-day"
                    value="{{ $accountant->accountant_discount_day != null ? date('d/m/Y', strtotime($accountant->accountant_discount_day)) : '' }}">
            </td>

            <td>
                <input type="text" name="order_profit_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price order_profit_{{ $accountant->order_id }}"
                    value="{{ number_format($accountant->order_profit, 0, ',', '.') }}">
            </td>

            <td>
                <input type="text" name="accountant_doctor_read_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-price "
                    value="{{ $accountant->accountant_doctor_read }}">
            </td>

            <td>
                <input type="text" name="accountant_doctor_date_payment_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-day"
                    value="{{ $accountant->accountant_doctor_date_payment != null ? date('d/m/Y', strtotime($accountant->accountant_doctor_date_payment)) : '' }}">
            </td>

            <td>
                <input type="text" name="ord_form_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-quantity ord_form_{{ $accountant->order_id }}"
                    value="{{ $accountant->ord_form == 'PhimLon' ? 'IN35X43' : $accountant->ord_form }}"
                    onclick="ordFormFunction(event)">
            </td>

            <td>
                <input type="text" name="accountant_35X43_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-quantity accountant_35X43_{{ $accountant->order_id }}"
                    value="{{ $accountant->accountant_35X43 }}" onclick="accountant35X43Function(event)">
            </td>

            <td>
                <input type="text" name="accountant_polime_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-quantity"
                    value="{{ $accountant->accountant_polime }}">
            </td>

            <td>
                <input type="text" name="accountant_8X10_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-quantity" value="{{ $accountant->accountant_8X10 }}">
            </td>

            <td>
                <input type="text" name="accountant_10X12_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-quantity"
                    value="{{ $accountant->accountant_10X12 }}">
            </td>

            <td>
                <input type="text" name="accountant_film_bag_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-quantity accountant_film_bag_{{ $accountant->order_id }}"
                    value="{{ $accountant->accountant_film_bag }}">
            </td>

            <td>
                <input type="text" name="accountant_note_{{ $accountant->order_id }}"
                    class="textbox-accountant  width-accountant-note" value="{{ $accountant->accountant_note }}">
            </td>
            <td>
                <input type="text" class="textbox-accountant  width-accountant-note"
                    value="{{ $accountant->ord_note }}">
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
            <td class="white-col">
                <a data-id="{{ $accountant->order_id }}" class="management-btn completeAccount">
                    <i class="fa fa-clipboard-check"></i>
                </a>
            </td>
        </form>
    </tr>
@endforeach
