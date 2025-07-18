<div id="table-scroll" class="table-scroll">
    <table class="table">
        <thead>
            <tr class="section-title">
                <th>Đơn vị hợp tác</th>
                <th>Số hoá đơn</th>
                <th>Ngày hoá đơn</th>
                <th>Số nghiệm thu / thanh lý</th>
                <th>Loại hợp đồng</th>
                <th>Ngày gửi</th>
                <th>Trạng thái</th>
            </tr>
            <tr class="section-filter">
                <th>
                    <select class="unit-name select-2" multiple="multiple">
                        @if (isset($unitNames) && !empty($unitNames))
                            @foreach ($unitNames as $unit)
                                <option value="{{ $unit }}">
                                    {{ $unit }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-number select-2" multiple="multiple">
                        @if (isset($accountantNumbers) && !empty($accountantNumbers))
                            @foreach ($accountantNumbers as $number)
                                <option value="{{ $number }}">
                                    {{ $number }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="accountant-date select-2" multiple="multiple">
                        @if (isset($accountantDates) && !empty($accountantDates))
                            @foreach ($accountantDates as $date)
                                <option value="{{ $date }}">
                                    {{ date('d/m/Y', strtotime($date)) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="liquidation-number select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($liquidationNumbers) && !empty($liquidationNumbers))
                            @foreach ($liquidationNumbers as $liquidation)
                                <option value="{{ $liquidation }}">
                                    {{ $liquidation }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="contract-type select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($contractTypes) && !empty($contractTypes))
                            @foreach ($contractTypes as $type)
                                <option value="{{ $type }}">
                                    {{ $type }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="contract-date select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        @if (isset($contractDates) && !empty($contractDates))
                            @foreach ($contractDates as $contractDate)
                                <option value="{{ $contractDate }}">
                                    {{ $contractDate }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </th>
                <th>
                    <select class="contract-status select-2" multiple="multiple">
                        <option value="1">Đang soạn</option>
                        <option value="2">Đã gửi khách hàng</option>
                        <option value="3">Đã có bản lưu</option>
                    </select>
                </th>
            </tr>
        </thead>
        <tbody class="tbody-content">
            @foreach ($getAllContract as $key => $accountant)
                <tr>
                    <form class="updateAccountant_">
                        @csrf
                        <input type="hidden" name="accountant_id_{{ $accountant->id }}"
                            value="{{ $accountant->id }}">
                        <input type="hidden" name="accountant_number_{{ $accountant->id }}"
                            value="{{ $accountant->accountant_number }}">
                        <td>{{ $accountant->unit_name }}</td>
                        <td>
                            {{ $accountant->accountant_number }}
                        </td>
                        <td>
                            {{ $accountant->accountant_date != null ? date('d/m/Y', strtotime($accountant->accountant_date)) : '' }}
                        </td>

                        <td>
                            {{ $accountant->liquidation_number }}
                        </td>
                        <td>
                            {{ $accountant->contract_type }}
                        </td>
                        <td>
                            {{ $accountant->contract_date }}
                        </td>
                        <td>
                            @if ($accountant->contract_status == 1)
                                <span style="color: #FCB322;">Đang soạn</span>
                            @elseif($accountant->contract_status == 2)
                                <span style="color: #27c24c;">Đã gửi khách hàng</span>
                            @elseif($accountant->contract_status == 3)
                                <span style="color: #0071e3;">Đã có bản lưu</span>
                            @endif
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
