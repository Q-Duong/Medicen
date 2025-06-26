<div id="table-scroll" class="table-scroll">
    <table class="table">
        <thead>
            <tr class="section-title">
                <th>Đơn vị hợp tác</th>
                <th>Số hoá đơn</th>
                <th>Ngày hoá đơn</th>
                <th>Số nghiệm thu / thanh lý</th>
                <th>Loại hợp đồng</th>
                <th>Trạng thái</th>
            </tr>
            <tr class="section-filter">
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
                    <select class="status-id select-2" multiple="multiple">
                        <option value="1">Đang soạn</option>
                        <option value="2">Đã gửi khách hàng</option>
                        <option value="4">Đã có bản lưu</option>
                    </select>
                </th>
            </tr>
        </thead>
        <tbody class="tbody-content">
            @foreach ($getAllContract as $key => $accountant)
                <tr>
                    <form class="updateAccountant_">
                        @csrf
                        <input type="hidden" name="accountant_id_{{ $accountant->order_id }}"
                            value="{{ $accountant->id }}">

                        <td>{{ $accountant->unit_name }}</td>
                        <td>
                            {{ $accountant->accountant_number }}
                        </td>

                        <td>
                            {{ $accountant->accountant_date != null ? date('d/m/Y', strtotime($accountant->accountant_date)) : '' }}
                        </td>

                        <td>
                            <input type="text" name="order_vat_{{ $accountant->order_id }}"
                                class="textbox-accountant  width-accountant-price"
                                value="{{ $accountant->order_vat }}">
                        </td>
                        <td>
                            <select
                                class="selectbox-accountant select-update accountant_method_{{ $accountant->order_id }}"
                                name="accountant_method_{{ $accountant->order_id }}">
                                <option value="" {{ $accountant->accountant_method == null ? 'selected' : '' }}>
                                </option>
                                <option value="HDB" {{ $accountant->accountant_method == 'HDB' ? 'selected' : '' }}>
                                    HDB</option>
                                <option value="AGRI"
                                    {{ $accountant->accountant_method == 'AGRI' ? 'selected' : '' }}>AGRI</option>
                                <option value="VCB" {{ $accountant->accountant_method == 'VCB' ? 'selected' : '' }}>
                                    VCB</option>
                                <option value="TM" {{ $accountant->accountant_method == 'TM' ? 'selected' : '' }}>
                                    TM</option>
                            </select>
                        </td>

                        <td>
                            <select
                                class="selectbox-accountant select-update accountant_method_{{ $accountant->order_id }}"
                                name="accountant_method_{{ $accountant->order_id }}">
                                <option value="" {{ $accountant->accountant_method == null ? 'selected' : '' }}>
                                </option>
                                <option value="HDB" {{ $accountant->accountant_method == 'HDB' ? 'selected' : '' }}>
                                    HDB</option>
                                <option value="AGRI"
                                    {{ $accountant->accountant_method == 'AGRI' ? 'selected' : '' }}>AGRI</option>
                                <option value="VCB" {{ $accountant->accountant_method == 'VCB' ? 'selected' : '' }}>
                                    VCB</option>
                                <option value="TM" {{ $accountant->accountant_method == 'TM' ? 'selected' : '' }}>
                                    TM</option>
                            </select>
                        </td>


                        {{-- <td class="status_id_{{ $accountant->order_id }} white-col">
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
                        </td> --}}
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