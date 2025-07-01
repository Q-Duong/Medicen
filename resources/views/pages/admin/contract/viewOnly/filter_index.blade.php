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
