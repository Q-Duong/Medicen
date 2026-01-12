@extends('layouts.default_auth')
@push('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-input {
            width: 100%;
            border: 1px solid #ced4da;
            padding: 4px 8px;
            text-align: right;
            border-radius: 4px;
            font-weight: 500;
        }

        .table-input:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, .25);
        }

        .table-input[readonly] {
            background-color: #e9ecef;
            color: #6c757d;
        }

        .fw-bold-num {
            font-weight: 700;
            font-family: 'Consolas', monospace;
        }

        .section-header {
            background-color: #dfe6ed;
            font-weight: bold;
            color: #495057;
        }

        .text-money {
            text-align: right;
        }

        .input-group {
            margin-bottom: 0;
        }

        /* Ẩn mũi tên input number */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .sub-label {
            font-size: 0.85em;
            color: #6c757d;
            font-style: italic;
        }

        .alert-warning {
            display: flex
        }
    </style>
@endpush
@section('admin_content')
    <div>
        <h3 class="text-center mb-3 text-primary fw-bold text-uppercase">Bảng Tính Hiệu Quả Kinh Doanh</h3>

        <div id="viewModeAlert" class="alert alert-warning shadow-sm justify-content-between align-items-center mb-3"
            style="display:none; border-left: 5px solid #ffc107;">
            <div>
                <i class="bi bi-eye-fill fs-5 me-2"></i>
                <span class="fw-bold">ĐANG XEM LẠI BẢN GHI:</span>
                <span id="viewModeTitle" class="ms-2 text-primary fw-bold text-uppercase">...</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark" id="btnExitViewMode">
                <i class="bi bi-x-lg"></i> Thoát / Nhập mới
            </button>
        </div>

        <div class="card mb-3 shadow-sm border-0 border-top border-primary border-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tên đơn vị / Đối tác:</label>
                        <div class="form-group">
                            <select id="unit_id" name="unit_id" class="select-2 unit-id">
                                @foreach ($getAllUnit as $key => $unit)
                                    <option value="{{ $unit->id }}">
                                        {{ $unit->unit_abbreviation }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ý kiến / Ghi chú:</label>
                        <textarea id="comments" class="form-control" rows="1" placeholder="Nhập ghi chú"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3 shadow-sm border-0 border-top border-primary border-4">
            <div class="card-body py-2">
                <div class="row g-3 align-items-center justify-content-center text-center">
                    <div class="col-md-2">
                        <label class="fw-bold text-secondary d-block">Số ngày công tác</label>
                        <input type="text" id="inp_so_ngay"
                            class="form-control text-center header-input calc-trigger number-only" value="">
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold text-secondary d-block">Số đêm lưu trú</label>
                        <input type="text" id="inp_so_dem"
                            class="form-control text-center header-input calc-trigger number-only" value="">
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold text-secondary d-block">Tổng số Km</label>
                        <input type="text" id="inp_so_km"
                            class="form-control text-center header-input text-danger calc-trigger number-only"
                            value="">
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold text-secondary d-block">Số nhân sự đi</label>
                        <input type="text" id="inp_so_nguoi"
                            class="form-control text-center header-input text-success calc-trigger number-only"
                            value="">
                    </div>
                </div>
            </div>
        </div>

        <form id="profitForm">
            <div class="card shadow-sm border-0 overflow-hidden mb-4">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width: 25%">Nội dung</th>
                            <th style="width: 15%">Đơn giá (VNĐ)</th>
                            <th style="width: 10%">Số lượng</th>
                            <th style="width: 15%">Thành tiền</th>
                            <th style="width: 35%">Ghi chú / Công thức</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="section-header text-success">
                            <td colspan="5">I. DOANH THU</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">XQ Phổi</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_xq_gia"
                                    value="28000"></td>
                            <td><input type="text"
                                    class="table-input calc-trigger number-only bg-warning-subtle fw-bold text-dark"
                                    id="thu_xq_sl" value=""></td>
                            <td class="text-money fw-bold-num" id="thu_xq_total">0</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">CSTL</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_cstl_gia"
                                    value="80000">
                            </td>
                            <td><input type="text"
                                    class="table-input calc-trigger number-only bg-warning-subtle fw-bold text-dark"
                                    id="thu_cstl_sl" value=""></td>
                            <td class="text-money fw-bold-num" id="thu_cstl_total">0</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Phụ thu</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_phuthu_gia"
                                    value="2000000">
                            </td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_phuthu_sl"
                                    value="">
                            </td>
                            <td class="text-money fw-bold-num" id="thu_phuthu_total">0</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Thu thêm BS đọc trực tiếp</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_bs_gia"
                                    value="500000">
                            </td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_bs_sl"
                                    value="">
                            </td>
                            <td class="text-money fw-bold-num" id="thu_bs_total">0</td>
                            <td class="sub-label">BS đọc trực tiếp 500k/đợt (nếu có)</td>
                        </tr>
                        <tr class="table-success">
                            <td colspan="3" class="text-end fw-bold">TỔNG THU:</td>
                            <td class="text-money fw-bold fs-5" id="tong_thu">0</td>
                            <td></td>
                        </tr>

                        <tr class="section-header text-danger">
                            <td colspan="5">II. CHI PHÍ</td>
                        </tr>

                        <tr>
                            <td colspan="5" class="bg-light fw-bold text-secondary ps-3">1. Vật tư & Chiết khấu</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Bao phim</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_baophim_gia"
                                    value="5500"></td>
                            <td><input type="text" class="table-input" id="chi_baophim_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_baophim_total">0</td>
                            <td class="sub-label">= Số ca XQ</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phim in (16/Canon)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim16_gia"
                                    value="1125"></td>
                            <td><input type="text" class="table-input" id="chi_phim16_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_phim16_total">0</td>
                            <td class="sub-label">= Số ca XQ</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phim 8x10</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim810_gia"
                                    value="7500"></td>
                            <td><input type="text" class="table-input" id="chi_phim810_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_phim810_total">0</td>
                            <td class="sub-label">= Số ca CSTL</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phim 10x12</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim1012_gia"
                                    value="12500"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim1012_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_phim1012_total">0</td>
                            <td class="sub-label">Nhập tay</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-primary">Chiết khấu</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_chietkhau_gia"
                                    value="0"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_chietkhau_sl"
                                    value="1" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_chietkhau_total">0</td>
                            <td class="sub-label">Tổng tiền chiết khấu</td>
                        </tr>

                        <tr>
                            <td colspan="5" class="bg-light fw-bold text-secondary ps-3">2. Nhân sự</td>
                        </tr>
                        <tr>
                            <td class="ps-4">BS đọc kết quả (từ xa)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_bsdoc_gia"
                                    value="1500"></td>
                            <td><input type="text" class="table-input" id="chi_bsdoc_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_bsdoc_total">0</td>
                            <td class="sub-label">= Unit</td>
                        </tr>
                        <tr>
                            <td class="ps-4">BS đọc trực tiếp</td>
                            <td><input type="text" class="table-input calc-trigger number-only"
                                    id="chi_bstructiep_gia" value="300000"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_bstructiep_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_bstructiep_total">0</td>
                            <td class="sub-label">Khoán ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Lương Tài xế (Cty)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_taixe_gia"
                                    value="342000"></td>
                            <td><input type="text" class="table-input" id="chi_taixe_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_taixe_total">0</td>
                            <td class="sub-label">= Ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Thuê Tài xế (Ngoài)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_thue_tx_gia"
                                    value="800000"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_thue_tx_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_thue_tx_total">0</td>
                            <td class="sub-label">Ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Lương KTV (Cty)</td>
                            <td>
                                <div class="row g-1">
                                    <div class="col-6"><input type="text"
                                            class="table-input calc-trigger number-only" id="chi_ktv_gia_ca"
                                            value="1500" title="Giá ca"></div>
                                    <div class="col-6"><input type="text"
                                            class="table-input calc-trigger number-only" id="chi_ktv_gia_ngay"
                                            value="323000" title="Giá ngày"></div>
                                </div>
                            </td>
                            <td><input type="text" class="table-input" id="chi_ktv_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_ktv_total">0</td>
                            <td class="sub-label">(Ca x Unit) + (Ngày x Ngày)</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Thuê KTV (Ngoài)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_thue_ktv_gia"
                                    value="400000"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_thue_ktv_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_thue_ktv_total">0</td>
                            <td class="sub-label">Ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Lương BPKQ</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_bpkq_gia"
                                    value="650">
                            </td>
                            <td><input type="text" class="table-input" id="chi_bpkq_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_bpkq_total">0</td>
                            <td class="sub-label">= Unit</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phụ cấp TX+KTV >250Km</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phucap_xa_gia"
                                    value="150000"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phucap_xa_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_phucap_xa_total">0</td>
                            <td class="sub-label">= Giá x Lần x <span class="text-success fw-bold">Số người</span></td>
                        </tr>

                        <tr>
                            <td colspan="5" class="bg-light fw-bold text-secondary ps-3">3. Vận hành & Khác</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phí quản lý (% Doanh thu)</td>
                            <td><input type="text" class="table-input calc-trigger number-only"
                                    id="chi_quanly_phantram" value="3.2"></td>
                            <td class="text-center">%</td>
                            <td class="text-money fw-bold-num" id="chi_quanly_total">0</td>
                            <td class="sub-label"></td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phí cầu đường (BOT)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_cauduong_gia"
                                    value="3500"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_cauduong_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_cauduong_total">0</td>
                            <td class="sub-label">Số lượt</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Dầu (Diesel)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dau_gia"
                                    value="20000">
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">ĐM</span>
                                    <input type="text" class="form-control calc-trigger number-only border-start-0"
                                        id="chi_dau_dinhmuc" value="5">
                                </div>
                            </td>
                            <td class="text-money fw-bold-num" id="chi_dau_total">0</td>
                            <td class="sub-label">= (Km / ĐM) * Giá</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Dầu di chuyển (Phát sinh)</td>
                            <td><input type="text" class="table-input calc-trigger number-only"
                                    id="chi_dau_phatsinh_gia" value="20000"></td>
                            <td><input type="text" class="table-input calc-trigger number-only"
                                    id="chi_dau_phatsinh_sl" value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_dau_phatsinh_total">0</td>
                            <td class="sub-label"></td>
                        </tr>
                        <tr>
                            <td class="ps-4">Khách sạn</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_ks_gia"
                                    value="600000">
                            </td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_ks_sl"
                                    value="0" readonly>
                            </td>
                            <td class="text-money fw-bold-num" id="chi_ks_total">0</td>
                            <td class="sub-label">Phòng/Đêm</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Đăng kiểm + Đường bộ</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dangkiem_gia"
                                    value="40000"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dangkiem_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_dangkiem_total">0</td>
                            <td class="sub-label">Số ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Ở lại đêm (Phụ cấp)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dem_gia"
                                    value="100000">
                            </td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dem_sl"
                                    value="0">
                            </td>
                            <td class="text-money fw-bold-num" id="chi_dem_total">0</td>
                            <td class="sub-label">= Giá x Đêm x <span class="text-success fw-bold">Số người</span></td>
                        </tr>
                        <tr>
                            <td class="ps-4 fw-bold">Thuế VAT (Phải nộp)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_vat_phantram"
                                    value="8"></td>
                            <td class="text-center">%</td>
                            <td class="text-money fw-bold-num" id="chi_vat_total">0</td>
                            <td class="sub-label">= Thu / 1.08 * 8%</td>
                        </tr>

                        <tr class="table-danger fw-bold">
                            <td colspan="3" class="text-end">TỔNG CHI:</td>
                            <td class="text-money fs-5" id="tong_chi">0</td>
                            <td></td>
                        </tr>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <td colspan="3" class="text-end fw-bold fs-4">LỢI NHUẬN RÒNG:</td>
                            <td class="text-money fw-bold fs-3" id="loi_nhuan">0</td>
                            <td class="fw-bold fs-5 align-middle text-center" id="ty_suat"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>

        <div class="d-grid gap-2 col-md-4 mx-auto pb-5">
            <button type="button" class="btn btn-primary btn-lg shadow" id="btnSave">
                <span id="btnText">LƯU KẾT QUẢ</span>
                <div id="btnSpinner" class="spinner-border spinner-border-sm ms-2" role="status" style="display:none;">
                </div>
            </button>
        </div>


        <div class="card col-md-12 shadow-sm border-0 mb-3">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history"></i> LỊCH SỬ TÍNH TOÁN</span>
                <span class="badge bg-primary">{{ count($reports) }} bản ghi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-primary text-secondary small">
                            <tr>
                                <th>Ngày tạo</th>
                                <th>Đơn vị / Đối tác</th>
                                <th class="text-end">Doanh thu</th>
                                <th class="text-end">Chi phí</th>
                                <th class="text-end">Lợi nhuận</th>
                                <th class="text-center">% Lãi</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $item)
                                <tr id="row-{{ $item->id }}">
                                    <td class="small text-muted">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="fw-bold text-primary">{{ $item->unit->unit_abbreviation }}</td>
                                    <td class="text-end">{{ number_format($item->total_revenue) }}</td>
                                    <td class="text-end text-danger">{{ number_format($item->total_cost) }}</td>
                                    <td
                                        class="text-end fw-bold {{ $item->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($item->net_profit) }}
                                    </td>
                                    <td class="text-center fw-bold">
                                        <span
                                            class="badge {{ $item->profit_margin >= 30 ? 'bg-success' : ($item->profit_margin > 0 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                            {{ $item->profit_margin }}%
                                        </span>
                                    </td>
                                    <td class="text-center" id="status-cell-{{ $item->id }}">
                                        @if ($item->status == 1)
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Đã
                                                duyệt</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        @if (Auth::user()->name == 'Admin' || Auth::user()->name == 'SubAdmin' || Auth::user()->name == 'Accountant')
                                            @if ($item->status == 0)
                                                <button class="btn btn-sm btn-outline-success btn-approve"
                                                    data-id="{{ $item->id }}" title="Duyệt phiếu này">
                                                    <i class="bi bi-check-lg"></i> Duyệt
                                                </button>
                                            @endif
                                        @endif

                                        <button class="btn btn-sm btn-outline-primary btn-load-report"
                                            data-id="{{ $item->id }}">Xem</button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-report"
                                            data-id="{{ $item->id }}">Xóa</button>
                                    </td>
                                </tr>
                            @endforeach

                            @if ($reports->isEmpty())
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Chưa có dữ liệu nào được lưu.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        var profitConfig = {
            url_load: "{{ route('profit.show', ':id') }}",
            url_delete: "{{ route('profit.delete', ':id') }}",
            url_save: "{{ route('profit.save') }}",
            url_approve: "{{ route('profit.approve', ':id') }}",
            csrf_token: "{{ csrf_token() }}"
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ versionResource('assets/js/tool/calculation.js') }}"></script>
@endpush
