<div id="table-scroll" class="table-scroll">
    <table class="table">
        <thead>
            <tr class="section-title">
                <th class="sticky-col first-col">Mã ĐH</th>
                <th class="sticky-col second-col">Tháng</th>
                <th class="sticky-col third-col">Ngày chụp</th>
                <th class="sticky-col fourth-col">Xe</th>
                <th class="sticky-col fifth-col">Km</th>
                <th class="sticky-col six-col">Đơn vị hợp tác</th>
                <th class="sticky-col seven-col">Tên Cty</th>
                <th>THCN</th>
                <th>Số HĐ</th>
                <th>Ngày HĐ</th>
                <th>VAT</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
                <th>Trạng thái thanh toán</th>
                <th>Ngày TT</th>
                <th>Hình thức</th>
                <th>Số tiền đã thanh toán</th>
                <th>Còn nợ</th>
                <th>%CK</th>
                <th>Thành tiền CK</th>
                <th>Ngày trích CK</th>
                <th>Lợi nhuận</th>
                <th>BS đọc kq</th>
                <th>NTT</th>
                <th>HT in Phim</th>
                <th>35 X 43</th>
                <th>Polime</th>
                <th>8 X 10</th>
                <th>10 X 12</th>
                <th>Bao phim</th>
                <th>Ghi chú</th>
                <th>Ghi chú Sales</th>
                <th>Trạng thái</th>
                <th>Hoàn thành</th>
            </tr>
            <tr class="section-filter">
                <th class="sticky-col first-col">
                    <select class="order-id select-2" multiple="multiple">
                        @foreach ($orderId as $id)
                            <option value="{{ $id }}">
                                {{ $id }}
                            </option>
                        @endforeach
                    </select>
                </th>
                <th class="sticky-col second-col">
                    <select class="accountant-month select-2" multiple="multiple">
                        @foreach ($months as $month)
                            <option value="{{ $month }}">
                                {{ $month }}
                            </option>
                        @endforeach
                    </select>
                </th>
                <th class="sticky-col third-col">
                    <select class="ord-start-day select-2" multiple="multiple">
                        @foreach ($days as $day)
                            <option value="{{ $day }}">
                                {{ date('d/m/Y', strtotime($day)) }}
                            </option>
                        @endforeach
                    </select>
                </th>
                <th class="sticky-col fourth-col">
                    <select class="car-name select-2" multiple="multiple">
                        @foreach ($cars as $car)
                            <option value="{{ $car }}">
                                @if ($car == 6)
                                    Xe thuê
                                @elseif($car == 7)
                                    Xe tăng cường
                                @else
                                    {{ $car }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </th>
                <th class="sticky-col fifth-col">
                    <select class="accountant-distance select-2" multiple="multiple">
                        <option value="G">G</option>
                        <option value="X">X</option>
                    </select>
                </th>
                <th class="sticky-col six-col">
                    <select class="unit-name select-2" multiple="multiple">
                        @foreach ($unitNames as $name)
                            <option value="{{ $name }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </th>
                <th class="sticky-col seven-col">
                    <select class="ord-cty-name select-2" multiple="multiple">
                        @foreach ($ctyNames as $cty)
                            <option value="{{ $cty }}">
                                {{ capitalizeWordsExceptAbbreviations($cty) }}
                            </option>
                        @endforeach
                    </select>
                </th>
                <th>
                    <select class="accountant-deadline select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accDeadlines) && !empty($accDeadlines))
                            @foreach ($accDeadlines as $deadline)
                                <option value="{{ $deadline }}">
                                    {{ $deadline }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-number select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accNumbers) && !empty($accNumbers))
                            @foreach ($accNumbers as $number)
                                <option value="{{ $number }}">
                                    {{ $number }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-date select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accDates) && !empty($accDates))
                            @foreach ($accDates as $date)
                                <option value="{{ $date }}">
                                    {{ date('d/m/Y', strtotime($date)) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="order-vat select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($vats) && !empty($vats))
                            @foreach ($vats as $vat)
                                <option value="{{ $vat }}">
                                    {{ $vat }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="order-quantity select-2" multiple="multiple">
                        @if (isset($quantities) && !empty($quantities))
                            @foreach ($quantities as $quantity)
                                <option value="{{ $quantity }}">
                                    {{ $quantity }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="order-cost select-2" multiple="multiple">
                        @if (isset($costs) && !empty($costs))
                            @foreach ($costs as $cost)
                                <option value="{{ $cost }}">
                                    {{ number_format($cost, 0, ',', '.') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="order-price select-2" multiple="multiple">
                        @if (isset($prices) && !empty($prices))
                            @foreach ($prices as $price)
                                <option value="{{ $price }}">
                                    {{ number_format($price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-status select-2" multiple="multiple">
                        <option value="0">Chưa thanh toán</option>
                        <option value="1">Đã thanh toán</option>
                    </select>
                </th>
                <th>
                    <select class="accountant-day-payment select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accDayPayments) && !empty($accDayPayments))
                            @foreach ($accDayPayments as $dayPayment)
                                <option value="{{ $dayPayment }}">
                                    {{ date('d/m/Y', strtotime($dayPayment)) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-method select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        <option value="HDB">HDB</option>
                        <option value="AGRI">AGRI</option>
                        <option value="VCB">VCB</option>
                        <option value="TM">TM</option>
                    </select>
                </th>
                <th>
                    <select class="accountant-amount-paid select-2" multiple="multiple">
                        @if (isset($accAmountPaid) && !empty($accAmountPaid))
                            @foreach ($accAmountPaid as $paid)
                                <option value="{{ $paid }}">
                                    {{ number_format($paid, 0, ',', '.') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-owe select-2" multiple="multiple">
                        @if (isset($accOwes) && !empty($accOwes))
                            @foreach ($accOwes as $owe)
                                <option value="{{ $owe }}">
                                    {{ number_format($owe, 0, ',', '.') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="order-percent-discount select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($percentDiscounts) && !empty($percentDiscounts))
                            @foreach ($percentDiscounts as $perDiscount)
                                <option value="{{ $perDiscount }}">
                                    {{ $perDiscount }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="order-discount select-2" multiple="multiple">
                        @if (isset($discounts) && !empty($discounts))
                            @foreach ($discounts as $discount)
                                <option value="{{ $discount }}">
                                    {{ number_format($discount, 0, ',', '.') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-discount-day select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accDiscountDays) && !empty($accDiscountDays))
                            @foreach ($accDiscountDays as $discountDay)
                                <option value="{{ $discountDay }}">
                                    {{ date('d/m/Y', strtotime($discountDay)) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="order-profit select-2" multiple="multiple">
                        @if (isset($profits) && !empty($profits))
                            @foreach ($profits as $profit)
                                <option value="{{ $profit }}">
                                    {{ number_format($profit, 0, ',', '.') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-doctor-read select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        <option value="Không">Không</option>
                        <option value="Nhân">Nhân</option>
                        <option value="Trung">Trung</option>
                        <option value="Giang">Giang</option>
                    </select>
                </th>
                <th>
                    <select class="accountant-doctor-date-payment select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accDoctorDatePayments) && !empty($accDoctorDatePayments))
                            @foreach ($accDoctorDatePayments as $doctorPayment)
                                <option value="{{ $doctorPayment }}">
                                    {{ date('d/m/Y', strtotime($doctorPayment)) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="ord-form select-2" multiple="multiple">
                        @if (isset($ordForms) && !empty($ordForms))
                            @foreach ($ordForms as $ordForm)
                                <option value="{{ $ordForm }}">
                                    {{ $ordForm == 'PhimLon' ? 'IN35X43' : $ordForm }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-35X43 select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($acc35X43) && !empty($acc35X43))
                            @foreach ($acc35X43 as $acc3543)
                                <option value="{{ $acc3543 }}">
                                    {{ $acc3543 }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-polime select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accPolimes) && !empty($accPolimes))
                            @foreach ($accPolimes as $polime)
                                <option value="{{ $polime }}">
                                    {{ $polime }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-8X10 select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($acc8X10) && !empty($acc8X10))
                            @foreach ($acc8X10 as $acc810)
                                <option value="{{ $acc810 }}">
                                    {{ $acc810 }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-10X12 select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($acc10X12) && !empty($acc10X12))
                            @foreach ($acc10X12 as $acc1012)
                                <option value="{{ $acc1012 }}">
                                    {{ $acc1012 }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-film-bag select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accFilmBags) && !empty($accFilmBags))
                            @foreach ($accFilmBags as $filmBag)
                                <option value="{{ $filmBag }}">
                                    {{ $filmBag }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-note select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($accNote) && !empty($accNote))
                            @foreach ($accNote as $note)
                                <option value="{{ $note }}">
                                    {{ $note }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="ord-note select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($ordNote) && !empty($ordNote))
                            @foreach ($ordNote as $oNote)
                                <option value="{{ $oNote }}">
                                    {{ $oNote }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="status-id select-2" multiple="multiple">
                        <option value="1">Đang xử lý</option>
                        <option value="2">Đã cập nhật số Cas thực tế</option>
                        <option value="4">Đã cập nhật doanh thu</option>
                        <option value="3">Đã xử lý</option>
                    </select>
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody class="tbody-content">
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
                        <input type="hidden" name="accountant_id_{{ $accountant->order_id }}"
                            value="{{ $accountant->id }}">
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
                                class="textbox-accountant  width-accountant-min"
                                value="{{ $accountant->accountant_number }}">
                        </td>

                        <td>
                            <input type="text" name="accountant_date_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-day accountant_date_{{ $accountant->order_id }}"
                                value="{{ $accountant->accountant_date != null ? date('d/m/Y', strtotime($accountant->accountant_date)) : '' }}"
                                onclick="dateFunction(event)">
                        </td>

                        <td>
                            <input type="text" name="order_vat_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-price"
                                value="{{ $accountant->order_vat }}">
                        </td>

                        <td>
                            <input type="text" name="order_quantity_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-quantity order_quantity_{{ $accountant->order_id }}"
                                value="{{ $accountant->order_quantity }}" onclick="quantityFunction(event)">
                        </td>

                        <td>
                            <input type="text" name="order_cost_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-price order_cost_{{ $accountant->order_id }}"
                                value="{{ number_format($accountant->order_cost, 0, ',', '.') }}"
                                onclick="costFunction(event)">
                        </td>

                        <td>
                            <input type="text" name="order_price_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-price  order_price_{{ $accountant->order_id }}"
                                value="{{ number_format($accountant->order_price, 0, ',', '.') }}"
                                onclick="priceFunction(event)">
                        </td>

                        <td>
                            <select
                                class="selectbox-accountant select-update accountant_status accountant_status_{{ $accountant->order_id }} {{ $accountant->accountant_status == 0 ? 'acc-status-unpaid' : 'acc-status-paid' }}"
                                name="accountant_status_{{ $accountant->order_id }}">
                                <option value="0" {{ $accountant->accountant_status == 0 ? 'selected' : '' }}>
                                    Chưa thanh toán</option>
                                <option value="1" {{ $accountant->accountant_status == 1 ? 'selected' : '' }}>Đã
                                    thanh toán</option>
                            </select>
                        </td>

                        <td>
                            <input type="text" name="accountant_day_payment_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-day"
                                value="{{ $accountant->accountant_day_payment != null ? date('d/m/Y', strtotime($accountant->accountant_day_payment)) : '' }}">
                        </td>

                        <td>
                            <select
                                class="selectbox-accountant select-update accountant_method_{{ $accountant->order_id }}"
                                name="accountant_method_{{ $accountant->order_id }}">
                                <option value="" {{ $accountant->accountant_method == null ? 'selected' : '' }}>
                                </option>
                                <option value="HDB"
                                    {{ $accountant->accountant_method == 'HDB' ? 'selected' : '' }}>HDB</option>
                                <option value="AGRI"
                                    {{ $accountant->accountant_method == 'AGRI' ? 'selected' : '' }}>AGRI</option>
                                <option value="VCB"
                                    {{ $accountant->accountant_method == 'VCB' ? 'selected' : '' }}>VCB</option>
                                <option value="TM"
                                    {{ $accountant->accountant_method == 'TM' ? 'selected' : '' }}>TM</option>
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
                                value="{{ $accountant->accountant_35X43 }}"
                                onclick="accountant35X43Function(event)">
                        </td>

                        <td>
                            <input type="text" name="accountant_polime_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-quantity"
                                value="{{ $accountant->accountant_polime }}">
                        </td>

                        <td>
                            <input type="text" name="accountant_8X10_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-quantity"
                                value="{{ $accountant->accountant_8X10 }}">
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
                                class="textbox-accountant  width-accountant-note"
                                value="{{ $accountant->accountant_note }}">
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
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('.select-2').select2();
    });
</script>
