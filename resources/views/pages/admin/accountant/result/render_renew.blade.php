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
                <th>Số lượng</th>
                <th>BS đọc kq</th>
                <th>HT in Phim</th>
                <th>35 X 43</th>
                <th>Polime</th>
                <th>8 X 10</th>
                <th>10 X 12</th>
                <th>Bao phim</th>
                <th>Ghi chú</th>
                <th>Ghi chú Sales</th>
                <th>Trạng thái</th>
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
                                {{ $cty }}
                            </option>
                        @endforeach
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
                    <select class="accountant-doctor-read select-2" multiple="multiple">
                        <option value="empty">Empty</option>
                        <option value="Không">Không</option>
                        <option value="Nhân">Nhân</option>
                        <option value="Trung">Trung</option>
                        <option value="Giang">Giang</option>
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
                    <td title="{{ capitalizeWordsExceptAbbreviations($accountant->ord_cty_name) }}"
                        class="sticky-col seven-col">
                        {{ capitalizeWordsExceptAbbreviations($accountant->ord_cty_name) }}</td>

                    <td>
                        {{ $accountant->order_quantity }}
                    </td>
                    <td>
                        {{ $accountant->accountant_doctor_read }}
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
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('.select-2').select2();
    });
</script>
