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
                    @elseif($car == 8)
                        Siêu âm
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