@extends('layouts.default_auth')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/order.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/file.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/filepond.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/filepond-preview.css') }}" type="text/css"
        as="style" />
@endpush
@section('admin_content')
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    Cập nhật thông tin đơn hàng
                    <span class="tools pull-right">
                        <a href="{{ route('order.index') }}" class="primary-btn-submit">Danh sách đơn</a>
                        <a class="fa fa-chevron-down" href="javascript:;"></a>
                    </span>
                </header>
                <div class="panel-body">
                    @if (!empty($priceCheck['flags']))
                        <div style="margin:0 0 16px;padding:10px 14px;border:1px solid {{ $priceCheck['approved'] ? '#b8e2cd' : '#f3c6bf' }};border-radius:12px;background:{{ $priceCheck['approved'] ? '#effaf3' : '#fdf1ef' }};color:{{ $priceCheck['approved'] ? '#18794e' : '#7a1d14' }};font-size:14px;line-height:1.5;">
                            <i class="fas fa-tags"></i>
                            @if ($priceCheck['approved'])
                                <b>Giá đã được duyệt</b> bởi {{ $priceCheck['approved_by'] }}{{ $priceCheck['approval_note'] ? ' (' . $priceCheck['approval_note'] . ')' : '' }}.
                            @else
                                <b>Đơn cần kế toán duyệt giá:</b>
                            @endif
                            {{ implode(' · ', $priceCheck['flags']) }}.
                            Giá {{ $priceCheck['per_case'] ? number_format($priceCheck['per_case'], 0, ',', '.') . ' đ/ca' : '—' }}, giá sàn ước tính {{ $priceCheck['floor_per_case'] ? number_format($priceCheck['floor_per_case'], 0, ',', '.') . ' đ/ca' : '—' }}
                            (chi phí chuyến ước tính {{ number_format($priceCheck['cost'], 0, ',', '.') }} đ cho {{ $priceCheck['qty'] }} ca, {{ $priceCheck['km'] ?: '?' }} km khứ hồi).
                            @if (!$priceCheck['approved'] && \App\Http\Middleware\PageAccess::allows(auth()->user(), ['debt_accountant']))
                                <a href="{{ route('finance.index', ['tab' => 'duyet-gia']) }}" style="font-weight:600;color:inherit;text-decoration:underline">Mở trang Duyệt giá</a>
                            @endif
                        </div>
                    @endif
                    <div class="position-center">
                        <form role="form" class="order-form" data-customer-url="{{ route('order.customer_suggestions') }}" action="{{ route('order.update', $order->order_id) }}" method="post"
                            enctype="multipart/form-data" id="order-edit-form"
                            @if ($schedule && !$scheduleKept) data-scheduled="1" data-old-date="{{ $order->ord_start_day }}" data-old-session="{{ trim((string) $order->ord_session) ?: 'Cả ngày' }}"
                                data-schedule-text="{{ \App\Services\Schedule\ScheduleBoard::carLabel($schedule->car_name) }}, TX {{ $schedule->car_driver_name ?: '—' }}{{ $schedule->car_ktv_name_1 ? ', KTV ' . collect([$schedule->car_ktv_name_1, $schedule->car_ktv_name_2])->filter()->join(', ') : '' }}" @endif>
                            @csrf
                            @method('patch')
                            @if ($orderClosed)
                                {{-- Đơn đã hoàn thành (kế toán đã chốt): chỉ xem, khóa toàn bộ form --}}
                                <div class="order-closed-note">
                                    <i class="fa fa-lock"></i>
                                    <div><b>Đơn đã hoàn thành</b> (kế toán đã chốt). Đơn chỉ còn để xem: không sửa thông tin, lịch xe,
                                        chấm công, kết quả hay công nợ được nữa. Cần lên đơn tương tự thì dùng <b>Copy đơn</b>.</div>
                                </div>
                                <fieldset class="order-closed-fs" disabled>
                            @endif
                            @if ($qtyLocked && !$orderClosed)
                                <div class="order-qty-locked-note">
                                    <i class="fa fa-lock"></i>
                                    <div>Bộ phận Kết quả đã cập nhật <b>số ca thực tế</b> cho đơn này: không sửa <b>số lượng</b> và cả phần <b>bác sĩ đọc phim, in phim, in phiếu, phim &amp; phiếu, cảnh báo đơn hàng</b> ở đây nữa. Các thông tin khác vẫn sửa bình thường.</div>
                                </div>
                            @endif
                            <input type="hidden" name="order_detail_id" value="{{ $order->order_detail_id }}">
                            @if ($schedule)
                                <div class="order-sched-note" id="order-sched-note">
                                    <i class="fas fa-truck"></i> Đơn này đã được xếp lịch:
                                    <b>{{ \App\Services\Schedule\ScheduleBoard::carLabel($schedule->car_name) }}</b>, TX {{ $schedule->car_driver_name ?: '—' }}{{ $schedule->car_ktv_name_1 ? ', KTV ' . collect([$schedule->car_ktv_name_1, $schedule->car_ktv_name_2])->filter()->join(', ') : '' }}.
                                    @if ($orderClosed)
                                        {{-- Đơn đã hoàn thành: chỉ xem lịch, không cần nói chuyện đổi ngày --}}
                                    @elseif ($scheduleKept)
                                        <span>Đơn đã chụp xong nên đổi <b>ngày</b> hoặc <b>buổi chụp</b> vẫn giữ nguyên lịch xe này (dữ liệu chấm công).</span>
                                    @else
                                        <span class="order-sched-note__warn">Nếu đổi <b>ngày</b> hoặc <b>buổi chụp</b>, lịch xe này sẽ bị hủy và đơn quay về <b>Chưa lên lịch</b> để xếp lại.</span>
                                    @endif
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="exampleInputPassword1">Đơn vị thuê xe</label>
                                <select name="unit_id" class="select-2 unit-id">
                                    @foreach ($getAllUnit as $key => $unit)
                                        <option {{ $unit->id == $order->unit_id ? 'selected' : '' }}
                                            value="{{ $unit->id }}">{{ $unit->unit_abbreviation }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="suggest hidden">
                                <img src="{{ asset('assets/images/suggest.jpg') }}" class="suggest-img">
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group @error('customer_name') has-error @enderror">
                                        <label for="exampleInputEmail1">Họ tên khách hàng</label>
                                        <input type="text" name="customer_name" class="input-control"
                                            placeholder="Điền họ tên khách hàng" value="{{ $order->customer_name }}">
                                        @error('customer_name')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group @error('customer_phone') has-error @enderror">
                                        <label for="exampleInputEmail1">Số điện thoại</label>
                                        <input type="text" name="customer_phone" class="input-control"
                                            placeholder="Điền số điện thoại" value="{{ $order->customer_phone }}">
                                        @error('customer_phone')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                {{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('ord_cty_name') has-error @enderror">
                                <label for="exampleInputPassword1">Tên Cty</label>
                                <input type="text" name="ord_cty_name" class="input-control" placeholder="Điền tên cty"
                                    value="{{ $order->ord_cty_name }}">
                                @error('ord_cty_name')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group @error('customer_address') has-error @enderror">
                                <label for="exampleInputPassword1">Địa chỉ chụp</label>
                                <input type="text" name="customer_address" class="input-control"
                                    placeholder="Điền địa chỉ chụp" value="{{ $order->customer_address }}">
                                @error('customer_address')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Toạ độ</label>
                                <input type="text" name="customer_location" class="input-control"
                                    placeholder="Điền toạ độ" value="{{ $order->customer_location }}">
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputPassword1">Số Km đi</label>
                                        <input type="text" name="accountant_distance" class="input-control"
                                            placeholder="Số Km đi" value="{{ $order->accountant_distance }}">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label>Ở lại đêm</label>
                                        <select name="overnight" class="input-control">
                                            <option value="0" {{ $order->overnight == 0 ? 'selected' : '' }}>
                                                Không
                                            </option>
                                            <option value="1" {{ $order->overnight == 1 ? 'selected' : '' }}>
                                                Có
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4 col-md-4 centered">
                                    <div class="form-group">
                                        <label for="exampleInputPassword1">Ngày chụp</label>
                                        <div class="checkout__input @error('ord_start_day') has-error @enderror">
                                            <input type="date" class="input-control" name="ord_start_day"
                                                value="{{ $order->ord_start_day }}"
                                                @if (Auth::user()->role == 0) @else min="<?= date('Y-m-d') ?>" onkeydown="return false" @endif>
                                            @error('ord_start_day')
                                                <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                    {{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-4 centered">
                                    <div class="form-group">
                                        <label>Ca chụp</label>
                                        <select name="ord_session" class="input-control">
                                            <option value="Cả ngày"
                                                {{ $order->ord_session == 'Cả ngày' ? 'selected' : '' }}>
                                                Cả ngày
                                            </option>
                                            <option value="Sáng" {{ $order->ord_session == 'Sáng' ? 'selected' : '' }}>
                                                Sáng
                                            </option>
                                            <option value="Chiều" {{ $order->ord_session == 'Chiều' ? 'selected' : '' }}>
                                                Chiều
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-4 centered">
                                    <div class="form-group @error('ord_time') has-error @enderror">
                                        <label for="exampleInputPassword1">Giờ chụp</label>
                                        <input type="text" name="ord_time" class="input-control"
                                            placeholder="Điền giờ chụp" value="{{ $order->ord_time }}">
                                        @error('ord_time')
                                            <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            @include('pages.admin.order._group', ['groupExclude' => $order->order_id, 'groupPreset' => $groupMembers, 'groupCode' => $groupCode])

                            <div class="form-group">
                                <label for="exampleInputPassword1">Ghi chú</label>
                                <textarea type="text" name="ord_note" class="textarea-control" rows="4" cols="50"
                                    placeholder="Điền ghi chú">
                                {{ $order->ord_note }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputEmail1">Danh sách chụp</label>
                                <input type="file" name="ord_list_file[]" class="filepond" value="" multiple>
                                @if ($order->ord_list_file != null)
                                    <div class="section-file">
                                        @foreach ($files as $key => $file)
                                            <div class="main-file">
                                                <div class="file-content">
                                                    <div class="file-name">
                                                        {{ $key }}
                                                    </div>
                                                    <div class="file-action">
                                                        <a href="https://drive.google.com/file/d/{{ $file }}/view"
                                                            target="_blank" class="download-file">
                                                            <i class="far fa-eye"></i>
                                                        </a>
                                                        <button class="delete-file " type="button"
                                                            onclick="deleteFileOrder('{{ $key }}', '{{ $file }}', '{{ $order->order_detail_id }}')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="form-group @error('ord_deliver_results') has-error @enderror">
                                <label for="exampleInputPassword1">Địa chỉ & sđt giao kết quả</label>
                                <input type="text" name="ord_deliver_results" class="input-control"
                                    placeholder="Điền Địa chỉ & sđt giao kết quả"
                                    value="{{ $order->ord_deliver_results }}">
                                @error('ord_deliver_results')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Địa chỉ email khách hàng</label>
                                <input type="text" name="ord_email" class="input-control"
                                    placeholder="Điền Địa chỉ email khách hàng" value="{{ $order->ord_email }}">
                            </div>

                            <div class="form-group">
                                <label for="exampleInputPassword1">Bộ phận chụp</label>
                                <select name="ord_select" class="input-control">
                                    <option value="Phổi (1 Tư thế)"
                                        {{ $order->ord_select == 'Phổi (1 Tư thế)' ? 'selected' : '' }}>
                                        Phổi (1 Tư thế)</option>
                                    <option value="Phổi (2 Tư thế)"
                                        {{ $order->ord_select == 'Phổi (2 Tư thế)' ? 'selected' : '' }}>
                                        Phổi (2 Tư thế)</option>
                                    <option value="Cột sống thắt lưng (1 Tư thế)"
                                        {{ $order->ord_select == 'Cột sống thắt lưng (1 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống thắt lưng (1 Tư thế)</option>
                                    <option value="Cột sống thắt lưng (2 Tư thế)"
                                        {{ $order->ord_select == 'Cột sống thắt lưng (2 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống thắt lưng (2 Tư thế)</option>
                                    <option value="Cột sống cổ (1 Tư thế)"
                                        {{ $order->ord_select == 'Cột sống cổ (1 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống cổ (1 Tư thế)</option>
                                    <option value="Cột sống cổ (2 Tư thế)"
                                        {{ $order->ord_select == 'Cột sống cổ (2 Tư thế)' ? 'selected' : '' }}>
                                        Cột sống cổ (2 Tư thế)</option>
                                    <option value="Vai (1 Tư thế)"
                                        {{ $order->ord_select == 'Vai (1 Tư thế)' ? 'selected' : '' }}>
                                        Vai (1 Tư thế)</option>
                                    <option value="Vai (2 Tư thế)2"
                                        {{ $order->ord_select == 'Vai (2 Tư thế)' ? 'selected' : '' }}>
                                        Vai (2 Tư thế)</option>
                                    <option value="Gối (1 Tư thế)"
                                        {{ $order->ord_select == 'Gối (1 Tư thế)' ? 'selected' : '' }}>
                                        Gối (1 Tư thế)</option>
                                    <option
                                        value="Gối (2 Tư thế)"{{ $order->ord_select == 'Gối (2 Tư thế)' ? 'selected' : '' }}>
                                        Gối (2 Tư thế)</option>
                                    <option value="Cổ tay (2 Tư thế)"
                                        {{ $order->ord_select == 'Cổ tay (2 Tư thế)' ? 'selected' : '' }}>Cổ tay (2 Tư thế)
                                    </option>
                                    <option value="Khuỷu tay (2 Tư thế)"
                                        {{ $order->ord_select == 'Khuỷu tay (2 Tư thế)' ? 'selected' : '' }}>Khuỷu tay (2
                                        Tư
                                        thế)
                                    </option>
                                    <option value="Bàn tay (2 Tư thế)"
                                        {{ $order->ord_select == 'Bàn tay (2 Tư thế)' ? 'selected' : '' }}>Bàn tay (2 Tư
                                        thế)
                                    </option>
                                    <option value="Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng"
                                        {{ $order->ord_select == 'Siêu âm Bụng, Giáp, Vú, Tử Cung, Buồng trứng' ? 'selected' : '' }}>
                                        Siêu âm Bụng, Giáp, Vú, Tử
                                        Cung, Buồng trứng</option>
                                    <option value="Siêu âm Tim"
                                        {{ $order->ord_select == 'Siêu âm Tim' ? 'selected' : '' }}>Siêu âm Tim</option>
                                    <option value="Siêu âm ĐMC, Mạch Máu Chi Dưới"
                                        {{ $order->ord_select == 'Siêu âm ĐMC, Mạch Máu Chi Dưới' ? 'selected' : '' }}>Siêu
                                        âm ĐMC, Mạch Máu Chi Dưới</option>
                                    <option value="Đo loãng xương"
                                        {{ $order->ord_select == 'Đo loãng xương' ? 'selected' : '' }}>Đo loãng xương
                                    </option>
                                    <option value="Nhũ Ảnh" {{ $order->ord_select == 'Nhũ Ảnh' ? 'selected' : '' }}>Nhũ
                                        Ảnh
                                    </option>
                                    <option value="Khác" {{ $order->ord_select == 'Khác' ? 'selected' : '' }}>Khác
                                    </option>
                                </select>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Bác sĩ đọc phim
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" @disabled($qtyLocked) value="" id="doctor1"
                                                class="accent" checked
                                                {{ $order->ord_doctor_read == '' ? 'checked' : '' }}>
                                            <label for="doctor1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" @disabled($qtyLocked) value="Có" id="doctor2"
                                                class="accent" {{ $order->ord_doctor_read == 'Có' ? 'checked' : '' }}>
                                            <label for="doctor2" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" @disabled($qtyLocked) value="Không" id="doctor3"
                                                class="accent" {{ $order->ord_doctor_read == 'Không' ? 'checked' : '' }}>
                                            <label for="doctor3" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_doctor_read" @disabled($qtyLocked) value="Tại chỗ" id="doctor4"
                                                class="accent"
                                                {{ $order->ord_doctor_read == 'Tại chỗ' ? 'checked' : '' }}>
                                            <label for="doctor4" class="radio-title">Tại chỗ</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phim
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" @disabled($qtyLocked) value="" id="film1"
                                                class="accent" {{ $order->ord_film == '' ? 'checked' : '' }}>
                                            <label for="film1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" @disabled($qtyLocked) value="Bình thường" id="film2"
                                                class="accent" {{ $order->ord_film == 'Bình thường' ? 'checked' : '' }}>
                                            <label for="film2" class="radio-title">Bình thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" @disabled($qtyLocked) value="Bất thường" id="film3"
                                                class="accent" {{ $order->ord_film == 'Bất thường' ? 'checked' : '' }}>
                                            <label for="film3" class="radio-title">Bất thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film" @disabled($qtyLocked) value="Cả 2" id="film4"
                                                class="accent" {{ $order->ord_film == 'Cả 2' ? 'checked' : '' }}>
                                            <label for="film4" class="radio-title">Cả 2</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Hình thức in phim
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-4 col-md-12 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="ko in" id="form1"
                                                class="accent" {{ $order->ord_form == 'ko in' ? 'checked' : '' }}>
                                            <label for="form1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="IN4" id="form2"
                                                class="accent" {{ $order->ord_form == 'IN4' ? 'checked' : '' }}>
                                            <label for="form2" class="radio-title">16,5 x 21,5(IN4)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="IN6" id="form10"
                                                class="accent" {{ $order->ord_form == 'IN6' ? 'checked' : '' }}>
                                            <label for="form10" class="radio-title">IN6</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="IN12" id="form3"
                                                class="accent" {{ $order->ord_form == 'IN12' ? 'checked' : '' }}>
                                            <label for="form3" class="radio-title">11 x 10,5(IN12)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="IN16" id="form4"
                                                class="accent" {{ $order->ord_form == 'IN16' ? 'checked' : '' }}>
                                            <label for="form4" class="radio-title">8,5 x 10,5(IN16)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="IN8X10" id="form5"
                                                class="accent" {{ $order->ord_form == 'IN8X10' ? 'checked' : '' }}>
                                            <label for="form5" class="radio-title">20,5 x 25,5(IN8X10)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="IN10X12" id="form6"
                                                class="accent" {{ $order->ord_form == 'IN10X12' ? 'checked' : '' }}>
                                            <label for="form6" class="radio-title">25,5 x 30,5(IN10X12)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="PhimLon" id="form7"
                                                class="accent" {{ $order->ord_form == 'PhimLon' ? 'checked' : '' }}>
                                            <label for="form7" class="radio-title">35 x 43(Phim Lớn)</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="Bệnh lý" id="form8"
                                                class="accent" {{ $order->ord_form == 'Bệnh lý' ? 'checked' : '' }}>
                                            <label for="form8" class="radio-title">Bệnh lý</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-4 col-md-4 ">
                                        <section>
                                            <input type="radio" name="ord_form" @disabled($qtyLocked) value="Giấy đặc biệt" id="form9"
                                                class="accent" {{ $order->ord_form == 'Giấy đặc biệt' ? 'checked' : '' }}>
                                            <label for="form9" class="radio-title">Giấy đặc biệt</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phiếu
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" @disabled($qtyLocked) value="" id="print1"
                                                class="accent" {{ $order->ord_print == '' ? 'checked' : '' }}>
                                            <label for="print1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" @disabled($qtyLocked) value="Bình thường" id="print2"
                                                class="accent" {{ $order->ord_print == 'Bình thường' ? 'checked' : '' }}>
                                            <label for="print2" class="radio-title">Bình thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" @disabled($qtyLocked) value="Bất thường" id="print3"
                                                class="accent" {{ $order->ord_print == 'Bất thường' ? 'checked' : '' }}>
                                            <label for="print3" class="radio-title">Bất thường</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print" @disabled($qtyLocked) value="Cả 2" id="print4"
                                                class="accent" {{ $order->ord_print == 'Cả 2' ? 'checked' : '' }}>
                                            <label for="print4" class="radio-title">Cả 2</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Hình thức in phiếu
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" @disabled($qtyLocked) value="" id="form_print1"
                                                class="accent" {{ $order->ord_form_print == '' ? 'checked' : '' }}>
                                            <label for="form_print1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" @disabled($qtyLocked) value="A4" id="form_print2"
                                                class="accent" {{ $order->ord_form_print == 'A4' ? 'checked' : '' }}>
                                            <label for="form_print2" class="radio-title">A4</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_form_print" @disabled($qtyLocked) value="A5" id="form_print3"
                                                class="accent" {{ $order->ord_form_print == 'A5' ? 'checked' : '' }}>
                                            <label for="form_print3" class="radio-title">A5</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">In phiếu kết quả theo mẫu đơn vị
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" @disabled($qtyLocked) value=""
                                                id="print_result1" class="accent"
                                                {{ $order->ord_print_result == '' ? 'checked' : '' }}>
                                            <label for="print_result1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" @disabled($qtyLocked) value="Có"
                                                id="print_result2" class="accent"
                                                {{ $order->ord_print_result == 'Có' ? 'checked' : '' }}>
                                            <label for="print_result2" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_print_result" @disabled($qtyLocked) value="Không"
                                                id="print_result3" class="accent"
                                                {{ $order->ord_print_result == 'Không' ? 'checked' : '' }}>
                                            <label for="print_result3" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-3 col-md-12 centered"></div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Phim & Phiếu
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" @disabled($qtyLocked) value="" id="film_sheet1"
                                                class="accent" {{ $order->ord_film_sheet == '' ? 'checked' : '' }}>
                                            <label for="film_sheet1" class="radio-title">Trống</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" @disabled($qtyLocked) value="Bấm flim vào phiếu"
                                                id="film_sheet2" class="accent"
                                                {{ $order->ord_film_sheet == 'Bấm flim vào phiếu' ? 'checked' : '' }}>
                                            <label for="film_sheet2" class="radio-title">Bấm flim vào phiếu</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" @disabled($qtyLocked)
                                                value="Bỏ flim và phiếu vào bao thư" id="film_sheet3" class="accent"
                                                {{ $order->ord_film_sheet == 'Bỏ flim và phiếu vào bao thư' ? 'checked' : '' }}>
                                            <label for="film_sheet3" class="radio-title">Bỏ flim và phiếu vào bao
                                                thư</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="ord_film_sheet" @disabled($qtyLocked)
                                                value="Bỏ flim và phiếu vào bao vàng" id="film_sheet4" class="accent"
                                                {{ $order->ord_film_sheet == 'Bỏ flim và phiếu vào bao vàng' ? 'checked' : '' }}>
                                            <label for="film_sheet4" class="radio-title">Bỏ flim và phiếu vào bao
                                                vàng</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            <div class="radio-group">
                                <label for="exampleInputPassword1">Cảnh báo đơn hàng
                                    @if ($qtyLocked || $orderClosed)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_warning" @disabled($qtyLocked) value="Không" id="warning1"
                                                class="accent" {{ $order->order_warning == 'Không' ? 'checked' : '' }}>
                                            <label for="warning1" class="radio-title">Không</label>
                                        </section>
                                    </div>
                                    <div class="col-lg-6 col-md-12 centered">
                                        <section>
                                            <input type="radio" name="order_warning" @disabled($qtyLocked) value="Có" id="warning2"
                                                class="accent" {{ $order->order_warning == 'Có' ? 'checked' : '' }}>
                                            <label for="warning2" class="radio-title">Có</label>
                                        </section>
                                    </div>
                                </div>
                            </div>

                            @if (Auth::user()->role == 0)
                                <div class="form-group @error('ord_deadline') has-error @enderror">
                                    <label for="exampleInputPassword1">Thời hạn giao kết quả</label>
                                    <input type="text" name="ord_deadline" class="input-control"
                                        placeholder="Điền thời hạn giao kết quả" value="{{ $order->ord_deadline }}">
                                    @error('ord_deadline')
                                        <div class="alert-error"><i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            @endif

                            @if ($adminOverride)
                                {{-- Admin sửa được các ô đang bị khóa với người khác --}}
                                <div class="order-admin-note">
                                    <i class="fa fa-user-shield"></i>
                                    <div><b>Quyền Admin:</b> đơn đang ở trạng thái
                                        <b>{{ (int) $order->status_id === 4 ? 'Đã cập nhật doanh thu' : 'Đã cập nhật số ca thực tế' }}</b>,
                                        các ô số lượng, tiền và phần in phim / in phiếu đang khóa với người khác nhưng Admin vẫn sửa được.
                                        Đổi số lượng / tiền thì chiết khấu, lợi nhuận, còn nợ tự tính lại.</div>
                                </div>
                            @endif
                            @if ($moneyLocked && !$orderClosed)
                                {{-- Đơn đã cập nhật doanh thu / đã chốt ở Công nợ: khóa các ô ảnh hưởng đến tiền (máy chủ cũng không cho sửa) --}}
                                <div class="order-money-locked" data-money-locked="1">
                                    <i class="fas fa-lock"></i>
                                    Đơn đã <b>{{ (int) $order->status_id === 3 ? 'hoàn thành (đã chốt)' : 'cập nhật doanh thu' }}</b> ở trang Công nợ:
                                    không sửa được số lượng, đơn giá, trọn gói, VAT, chiết khấu, tổng tiền.
                                    Cần đổi thì báo bộ phận Kế toán.
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label>Đơn phụ thu</label>
                                        <select name="order_surcharge" class="input-control">
                                            <option value="0" {{ $order->order_surcharge == 0 ? 'selected' : '' }}>
                                                Không
                                            </option>
                                            <option value="1" {{ $order->order_surcharge == 1 ? 'selected' : '' }}>
                                                Có
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label>Trọn gói
                                        @if ($moneyLocked)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                    </label>
                                        <select name="order_all_in_one" class="input-control order_all_in_one">
                                            <option value="0" {{ $order->order_all_in_one == 0 ? 'selected' : '' }}>
                                                Không
                                            </option>
                                            <option value="1" {{ $order->order_all_in_one == 1 ? 'selected' : '' }}>
                                                Có
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('order_quantity') has-error @enderror">
                                <label for="exampleInputPassword1">Số lượng
                                    @if ($qtyLocked || $moneyLocked)<span class="order-field-lock" title="Đã có số ca thực tế / đã cập nhật doanh thu"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                </label>
                                <input type="text" name="order_quantity" class="input-control order_quantity {{ $qtyLocked ? 'is-field-locked' : '' }}"
                                    placeholder="Đièn số lượng" value="{{ $order->order_quantity }}" @if ($qtyLocked) readonly tabindex="-1" @endif>
                                @error('order_quantity')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group block-order-cost {{ $order->order_all_in_one == 0 ? '' : 'hidden' }}">
                                <label for="exampleInputPassword1">Đơn giá
                                        @if ($moneyLocked)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                    </label>
                                <input type="text" name="order_cost" class="input-control order_cost"
                                    value="{{ number_format($order->order_cost, 0, ',', '.') }}"
                                    placeholder="Điền đơn giá" >
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label for="exampleInputPassword1">VAT
                                        @if ($moneyLocked)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                    </label>
                                        <input type="text" name="order_vat" class="input-control"
                                            placeholder="Điền VAT" value="{{ $order->order_vat }}">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group ">
                                        <label for="exampleInputPassword1">Phần trăm chiết khấu
                                        @if ($moneyLocked)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                    </label>
                                        <input type="text" name="order_percent_discount" class="input-control"
                                            value="{{ $order->order_percent_discount }}"
                                            placeholder="Điền phần trăm chiết khấu">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('order_price') has-error @enderror">
                                <label for="exampleInputPassword1">Tổng tiền
                                        @if ($moneyLocked)<span class="order-field-lock"><i class="fa fa-lock"></i> đã khóa</span>@endif
                                    </label>
                                <input type="text" name="order_price" class="input-control order_price"
                                    placeholder="Điền tổng tiền"
                                    value="{{ number_format($order->order_price, 0, ',', '.') }}">
                                @error('order_price')
                                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            @include('pages.admin.order._contract_hint', ['orderId' => $order->order_id])

                            {{-- Tự tính theo cùng công thức trang Công nợ (máy chủ tính lại khi lưu) --}}
                            <div class="row order-money-calc">
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label>Thành tiền chiết khấu <small class="om-hint">tự tính từ % chiết khấu</small></label>
                                        <input type="text" class="input-control om-discount" readonly tabindex="-1"
                                            value="{{ number_format((int) $order->order_discount, 0, ',', '.') }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 centered">
                                    <div class="form-group">
                                        <label>Lợi nhuận (tổng tiền − chiết khấu)</label>
                                        <input type="text" class="input-control om-profit" readonly tabindex="-1"
                                            value="{{ number_format((int) $order->order_profit, 0, ',', '.') }}">
                                    </div>
                                </div>
                            </div>
                            @unless ($orderClosed)
                                {{-- Mọi người đều cập nhật được (không còn xét quyền / ngày chụp đã qua); đơn đã hoàn thành thì không có nút --}}
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 centered">
                                        <button type="submit" name="complete" value="0"
                                            class="primary-btn-submit button-submit">
                                            Cập nhật chưa hoàn thành
                                        </button>
                                    </div>
                                    <div class="col-lg-6 col-md-6 centered">
                                        <button type="submit" name="complete" value="1"
                                            class="primary-btn-submit button-submit">
                                            Cập nhật
                                        </button>
                                    </div>
                                </div>
                            @endunless
                            @if ($orderClosed)
                                </fieldset>
                            @endif
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('js')
    @include('pages.admin.partials.dialog')
    <script src="{{ versionResource('assets/js/support/file/filepond.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/support/file/filepond-preview.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/tool/order/order.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/support/essential.js') }}" defer></script>
    <script src="{{ versionResource('assets/js/support/file/org-handle-file.js') }}" defer></script>
    <script defer>
        var url_file_process = "{{ route('file.process') }}";
        var url_file_revert = "{{ route('file.revert') }}";
        var url_file_delete_order = "{{ route('file.delete_file_order') }}";
        var files = [];
        @foreach (old('ord_list_file', []) as $file)
            files.push({
                source: '{{ $file }}',
                options: {
                    type: 'local'
                }
            });
        @endforeach
    </script>
@endpush
