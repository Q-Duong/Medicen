<table class="table  display nowrap example" style="width:100%" id="myTableScroll">
    <thead>
        <tr>
            <th class="sticky-col first-col">Mã ĐH</th>
            <th class="sticky-col second-col">Tháng</th>
            <th class="sticky-col third-col">Ngày chụp</th>
            <th class="sticky-col fourth-col">Xe</th>
            <th class="sticky-col fifth-col">Km</th>
            <th class="sticky-col seven-col">Đơn vị hợp tác</th>
            <th class="sticky-col eight-col">Tên Cty</th>
            <th>THCN</th>
            <th>Số HH</th>
            <th>Ngày HĐ</th>
            <th>VAT</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
            <th>Thời hạn thanh toán</th>
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
            <th>Cập nhật</th>
            <th>Hoàn thành</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($getAllAccountant as $key => $accountant)
            <tr>
                <form class="updateAccountant_{{ $accountant->order_id }}">
                    @csrf
                    <input type="hidden" name="accountant_id_{{ $accountant->order_id }}"
                        value="{{ $accountant->id }}">
                    <td class="sticky-col first-col order_id">{{ $accountant->order_id }}</td>

                    <td class="sticky-col second-col">{{ $accountant->accountant_month }}</td>

                    <td class="sticky-col third-col">
                        {{ date('Y/m/d', strtotime($accountant->ord_start_day)) }}</td>
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
                    <td title="{{ $accountant->unit_name }}" class="sticky-col seven-col">
                        {{ $accountant->unit_name }}</td>
                    <td title="{{ $accountant->ord_cty_name }}" class="sticky-col eight-col">
                        {{ $accountant->ord_cty_name }}</td>

                    <td>
                        <input type="text" name="accountant_deadline_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-min accountant_deadline_{{ $accountant->order_id }}"
                            value="{{ $accountant->accountant_deadline }}" onclick="deadlineFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="accountant_number_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-min"
                            value="{{ $accountant->accountant_number }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_date_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-day accountant_date_{{ $accountant->order_id }}"
                            value="{{ $accountant->accountant_date != null ? date('d/m/Y', strtotime($accountant->accountant_date)) : '' }}"
                            onclick="dateFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="order_vat_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price"
                            value="{{ $accountant->order_vat }}">
                    </td>

                    <td>
                        <input type="text" name="order_quantity_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-quantity order_quantity_{{ $accountant->order_id }}"
                            value="{{ $accountant->order_quantity }}" onclick="quantityFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="order_cost_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price order_cost_{{ $accountant->order_id }}"
                            value="{{ number_format($accountant->order_cost, 0, ',', '.') }}"
                            onclick="costFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="order_price_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price  order_price_{{ $accountant->order_id }}"
                            value="{{ number_format($accountant->order_price, 0, ',', '.') }}"
                            onclick="priceFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="accountant_payment_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-day accountant_payment_{{ $accountant->order_id }}"
                            value="{{ $accountant->accountant_payment != null ? date('d/m/Y', strtotime($accountant->accountant_payment)) : '' }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_day_payment_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-day"
                            value="{{ $accountant->accountant_day_payment != null ? date('d/m/Y', strtotime($accountant->accountant_day_payment)) : '' }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_method_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price"
                            value="{{ $accountant->accountant_method }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_amount_paid_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price accountant_amount_paid_{{ $accountant->order_id }}"
                            value="{{ number_format($accountant->accountant_amount_paid, 0, ',', '.') }}"
                            onclick="amountPaidFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="accountant_owe_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price accountant_owe_{{ $accountant->order_id }}"
                            value="{{ number_format($accountant->accountant_owe, 0, ',', '.') }}">
                    </td>

                    <td>
                        <input type="text" name="order_percent_discount_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price order_percent_discount_{{ $accountant->order_id }}"
                            value="{{ $accountant->order_percent_discount }}">
                    </td>

                    <td>
                        <input type="text" name="order_discount_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price order_discount_{{ $accountant->order_id }}"
                            value="{{ number_format($accountant->order_discount, 0, ',', '.') }}"
                            onclick="discountFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="accountant_discount_day_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-day"
                            value="{{ $accountant->accountant_discount_day != null ? date('d/m/Y', strtotime($accountant->accountant_discount_day)) : '' }}">
                    </td>

                    <td>
                        <input type="text" name="order_profit_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price order_profit_{{ $accountant->order_id }}"
                            value="{{ number_format($accountant->order_profit, 0, ',', '.') }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_doctor_read_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-price "
                            value="{{ $accountant->accountant_doctor_read }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_doctor_date_payment_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-day"
                            value="{{ $accountant->accountant_doctor_date_payment != null ? date('d/m/Y', strtotime($accountant->accountant_doctor_date_payment)) : '' }}">
                    </td>

                    <td>
                        <input type="text" name="ord_form_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-quantity ord_form_{{ $accountant->order_id }}"
                            value="{{ $accountant->ord_form }}" onclick="ordFormFunction(event)">
                    </td>

                    <td>
                        <input type="text" name="accountant_35X43_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-quantity accountant_35X43_{{ $accountant->order_id }}"
                            value="{{ $accountant->accountant_35X43 }}" onclick="accountant35X43Function(event)">
                    </td>

                    <td>
                        <input type="text" name="accountant_polime_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-quantity"
                            value="{{ $accountant->accountant_polime }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_8X10_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-quantity"
                            value="{{ $accountant->accountant_8X10 }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_10X12_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-quantity"
                            value="{{ $accountant->accountant_10X12 }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_film_bag_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-quantity accountant_film_bag_{{ $accountant->order_id }}"
                            value="{{ $accountant->accountant_film_bag }}">
                    </td>

                    <td>
                        <input type="text" name="accountant_note_{{ $accountant->order_id }}"
                            class="input-control-accountant width-accountant-note"
                            value="{{ $accountant->accountant_note }}">
                    </td>
                    <td>
                        <input type="text" class="input-control-accountant width-accountant-note"
                            name="accountant_note" value="{{ $accountant->ord_note }}">
                    </td>
                    <td class="status_id_{{ $accountant->order_id }}">
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
                    <td class="update-account-{{ $accountant->order_id }}">
                        @if ($accountant->status_id != 3)
                            <a data-id="{{ $accountant->order_id }}" class="active styling-edit updateAccount">
                                <i class="fas fa-edit"></i>
                            </a>
                        @endif
                    </td>
                    <td>
                        <a data-id="{{ $accountant->order_id }}" class="active styling-edit completeAccount">
                            <i class="fa fa-clipboard-check"></i>
                        </a>
                    </td>
                </form>
            </tr>
        @endforeach
</table>
<script src="{{ versionResource('assets/js/support/datatables/dataTables-exec.js') }}"></script>
