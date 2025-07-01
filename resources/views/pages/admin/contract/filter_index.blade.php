@foreach ($getAllContract as $key => $accountant)
    <tr>
        <form class="updateAccountant_">
            @csrf
            <input type="hidden" name="accountant_id_{{ $accountant->id }}" value="{{ $accountant->id }}">
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
                <input type="text" name="liquidation_number_{{ $accountant->id }}"
                    class="textbox-accountant  width-accountant-day liquidation_number_{{ $accountant->id }}"
                    value="{{ $accountant->liquidation_number }}">
            </td>
            <td>
                <select class="selectbox-accountant select-update contract_type_{{ $accountant->id }}"
                    name="contract_type_{{ $accountant->id }}">
                    <option {{ $accountant->contract_type == null ? 'selected' : '' }}>
                        Trống</option>
                    <option value="Nghiệm thu" {{ $accountant->contract_type == 'Nghiệm thu' ? 'selected' : '' }}>
                        Nghiệm thu</option>
                    <option value="Thanh lý" {{ $accountant->contract_type == 'Thanh lý' ? 'selected' : '' }}>Thanh lý
                    </option>
                    <option value="Khác" {{ $accountant->contract_type == 'Khác' ? 'selected' : '' }}>
                        Khác</option>

                </select>
            </td>
            <td>
                <input type="text" name="contract_date_{{ $accountant->id }}"
                    class="textbox-accountant  width-accountant-price contract_date_{{ $accountant->id }}"
                    value="{{ $accountant->contract_date }}">
            </td>
            <td>
                <select
                    class="selectbox-accountant select-update contract_status contract-status-{{ $accountant->contract_status }} contract_status_{{ $accountant->id }}"
                    name="contract_status_{{ $accountant->id }}">
                    <option value="1" {{ $accountant->contract_status == '1' ? 'selected' : '' }}>
                        Đang soạn</option>
                    <option value="2" {{ $accountant->contract_status == '2' ? 'selected' : '' }}>Đã
                        gửi khách hàng</option>
                    <option value="3" {{ $accountant->contract_status == '3' ? 'selected' : '' }}>
                        Đã có bản lưu</option>
                </select>
            </td>

        </form>
    </tr>
@endforeach
