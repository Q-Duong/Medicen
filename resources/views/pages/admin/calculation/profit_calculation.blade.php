@extends('layouts.default_auth')
@section('title', 'Tính lợi nhuận X-Quang - Medicen')
@push('css')
    {{-- Giao diện ở assets/css/support/org-profit.css (bản nạp: profit.css). Bỏ Bootstrap 5 tải riêng (lệch giao diện chung) --}}
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/profit.css') }}" type="text/css" as="style" />
@endpush
@section('admin_content')
    <div class="pf">
        <div class="pf-head">
            <div class="pf-head__title">
                <h1>Tính lợi nhuận X-Quang</h1>
                <p>Bảng tính hiệu quả kinh doanh một đợt chụp: nhập số ca, ngày công tác, km… để ra doanh thu, chi phí, lợi nhuận.</p>
            </div>
            <a href="#pf-history" class="pf-btn pf-btn--ghost"><i class="fas fa-history"></i> Lịch sử tính ({{ count($reports) }})</a>
        </div>

        <div id="viewModeAlert" class="pf-viewmode" style="display:none">
            <div><i class="fas fa-eye"></i> <b>Đang xem lại bản ghi:</b> <span id="viewModeTitle">...</span></div>
            <button type="button" class="pf-btn pf-btn--ghost pf-btn--sm" id="btnExitViewMode"><i class="fas fa-times"></i> Thoát / Nhập mới</button>
        </div>

        <div class="pf-grid">
            <section class="pf-card">
                <h2 class="pf-card__title">Đơn vị</h2>
                <div class="pf-fields">
                    <label class="pf-field">
                        <span>Tên đơn vị / đối tác</span>
                        <select id="unit_id" name="unit_id" class="select-2 unit-id">
                            @foreach ($getAllUnit as $key => $unit)
                                <option value="{{ $unit->id }}">{{ $unit->unit_abbreviation }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="pf-field">
                        <span>Ý kiến / ghi chú</span>
                        <textarea id="comments" rows="1" placeholder="Nhập ghi chú"></textarea>
                    </label>
                </div>
            </section>
            <section class="pf-card">
                <h2 class="pf-card__title">Thông tin chuyến đi</h2>
                <div class="pf-trip">
                    <label class="pf-field"><span>Số ngày công tác</span>
                        <input type="text" id="inp_so_ngay" class="header-input calc-trigger number-only" value="" inputmode="numeric"></label>
                    <label class="pf-field"><span>Số đêm lưu trú</span>
                        <input type="text" id="inp_so_dem" class="header-input calc-trigger number-only" value="" inputmode="numeric"></label>
                    <label class="pf-field"><span>Tổng số Km</span>
                        <input type="text" id="inp_so_km" class="header-input calc-trigger number-only" value="" inputmode="numeric"></label>
                    <label class="pf-field"><span>Số nhân sự đi</span>
                        <input type="text" id="inp_so_nguoi" class="header-input calc-trigger number-only" value="" inputmode="numeric"></label>
                </div>
            </section>
        </div>

        <form id="profitForm">
            <div class="pf-card pf-calc">
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th style="width: 25%">Nội dung</th>
                            <th style="width: 17%">Đơn giá (VNĐ)</th>
                            <th style="width: 12%">Số lượng</th>
                            <th style="width: 16%" class="text-money">Thành tiền</th>
                            <th style="width: 30%">Ghi chú / công thức</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="section-header text-success">
                            <td colspan="5">I. DOANH THU</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">XQ Phổi</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_xq_gia"
                                    value="{{ $profitDefaults['thu_xq_gia'] }}"></td>
                            <td><input type="text"
                                    class="table-input calc-trigger number-only bg-warning-subtle fw-bold text-dark"
                                    id="thu_xq_sl" value=""></td>
                            <td class="text-money fw-bold-num" id="thu_xq_total">0</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">CSTL</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="thu_cstl_gia"
                                    value="{{ $profitDefaults['thu_cstl_gia'] }}">
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
                                    value="{{ $profitDefaults['thu_phuthu_gia'] }}">
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
                                    value="{{ $profitDefaults['thu_bs_gia'] }}">
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
                                    value="{{ $profitDefaults['chi_baophim_gia'] }}"></td>
                            <td><input type="text" class="table-input" id="chi_baophim_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_baophim_total">0</td>
                            <td class="sub-label">= Số ca XQ</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phim in (16/Canon)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim16_gia"
                                    value="{{ $profitDefaults['chi_phim16_gia'] }}"></td>
                            <td><input type="text" class="table-input" id="chi_phim16_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_phim16_total">0</td>
                            <td class="sub-label">= Số ca XQ</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phim 8x10</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim810_gia"
                                    value="{{ $profitDefaults['chi_phim810_gia'] }}"></td>
                            <td><input type="text" class="table-input" id="chi_phim810_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_phim810_total">0</td>
                            <td class="sub-label">= Số ca CSTL</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phim 10x12</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim1012_gia"
                                    value="{{ $profitDefaults['chi_phim1012_gia'] }}"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phim1012_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_phim1012_total">0</td>
                            <td class="sub-label">Nhập tay</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-primary">Chiết khấu</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_chietkhau_gia"
                                    value="{{ $profitDefaults['chi_chietkhau_gia'] }}"></td>
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
                                    value="{{ $profitDefaults['chi_bsdoc_gia'] }}"></td>
                            <td><input type="text" class="table-input" id="chi_bsdoc_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_bsdoc_total">0</td>
                            <td class="sub-label">= Unit</td>
                        </tr>
                        <tr>
                            <td class="ps-4">BS đọc trực tiếp</td>
                            <td><input type="text" class="table-input calc-trigger number-only"
                                    id="chi_bstructiep_gia" value="{{ $profitDefaults['chi_bstructiep_gia'] }}"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_bstructiep_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_bstructiep_total">0</td>
                            <td class="sub-label">Khoán ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Lương Tài xế (Cty)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_taixe_gia"
                                    value="{{ $profitDefaults['chi_taixe_gia'] }}"></td>
                            <td><input type="text" class="table-input" id="chi_taixe_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_taixe_total">0</td>
                            <td class="sub-label">= Ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Thuê Tài xế (Ngoài)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_thue_tx_gia"
                                    value="{{ $profitDefaults['chi_thue_tx_gia'] }}"></td>
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
                                            value="{{ $profitDefaults['chi_ktv_gia_ca'] }}" title="Giá ca"></div>
                                    <div class="col-6"><input type="text"
                                            class="table-input calc-trigger number-only" id="chi_ktv_gia_ngay"
                                            value="{{ $profitDefaults['chi_ktv_gia_ngay'] }}" title="Giá ngày"></div>
                                </div>
                            </td>
                            <td><input type="text" class="table-input" id="chi_ktv_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_ktv_total">0</td>
                            <td class="sub-label">(Ca x Unit) + (Ngày x Ngày)</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Thuê KTV (Ngoài)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_thue_ktv_gia"
                                    value="{{ $profitDefaults['chi_thue_ktv_gia'] }}"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_thue_ktv_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_thue_ktv_total">0</td>
                            <td class="sub-label">Ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Lương BPKQ</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_bpkq_gia"
                                    value="{{ $profitDefaults['chi_bpkq_gia'] }}">
                            </td>
                            <td><input type="text" class="table-input" id="chi_bpkq_sl" readonly></td>
                            <td class="text-money fw-bold-num" id="chi_bpkq_total">0</td>
                            <td class="sub-label">= Unit</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phụ cấp TX+KTV >250Km</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_phucap_xa_gia"
                                    value="{{ $profitDefaults['chi_phucap_xa_gia'] }}"></td>
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
                                    id="chi_quanly_phantram" value="{{ $profitDefaults['chi_quanly_phantram'] }}"></td>
                            <td class="text-center">%</td>
                            <td class="text-money fw-bold-num" id="chi_quanly_total">0</td>
                            <td class="sub-label"></td>
                        </tr>
                        <tr>
                            <td class="ps-4">Phí cầu đường (BOT)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_cauduong_gia"
                                    value="{{ $profitDefaults['chi_cauduong_gia'] }}"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_cauduong_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_cauduong_total">0</td>
                            <td class="sub-label">Số lượt</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Dầu (Diesel)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dau_gia"
                                    value="{{ $profitDefaults['chi_dau_gia'] }}">
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">ĐM</span>
                                    <input type="text" class="form-control calc-trigger number-only border-start-0"
                                        id="chi_dau_dinhmuc" value="{{ $profitDefaults['chi_dau_dinhmuc'] }}">
                                </div>
                            </td>
                            <td class="text-money fw-bold-num" id="chi_dau_total">0</td>
                            <td class="sub-label">= (Km / ĐM) * Giá</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Dầu di chuyển (Phát sinh)</td>
                            <td><input type="text" class="table-input calc-trigger number-only"
                                    id="chi_dau_phatsinh_gia" value="{{ $profitDefaults['chi_dau_phatsinh_gia'] }}"></td>
                            <td><input type="text" class="table-input calc-trigger number-only"
                                    id="chi_dau_phatsinh_sl" value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_dau_phatsinh_total">0</td>
                            <td class="sub-label"></td>
                        </tr>
                        <tr>
                            <td class="ps-4">Khách sạn</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_ks_gia"
                                    value="{{ $profitDefaults['chi_ks_gia'] }}">
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
                                    value="{{ $profitDefaults['chi_dangkiem_gia'] }}"></td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dangkiem_sl"
                                    value="0"></td>
                            <td class="text-money fw-bold-num" id="chi_dangkiem_total">0</td>
                            <td class="sub-label">Số ngày</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Ở lại đêm (Phụ cấp)</td>
                            <td><input type="text" class="table-input calc-trigger number-only" id="chi_dem_gia"
                                    value="{{ $profitDefaults['chi_dem_gia'] }}">
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
                                    value="{{ $profitDefaults['chi_vat_phantram'] }}"></td>
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

        <div class="pf-save">
            <button type="button" class="pf-btn pf-btn--primary pf-btn--lg" id="btnSave">
                <i class="fas fa-save"></i> <span id="btnText">Lưu kết quả</span>
                <span id="btnSpinner" class="pf-spinner" role="status" style="display:none;"></span>
            </button>
        </div>

        <section class="pf-card pf-history" id="pf-history">
            <div class="pf-history__head">
                <h2 class="pf-card__title"><i class="fas fa-history"></i> Lịch sử tính toán</h2>
                <span class="pf-pill">{{ count($reports) }} bản ghi</span>
            </div>
            <div class="pf-table-wrap">
                <table class="pf-table pf-table--list">
                    <thead>
                        <tr>
                            <th>Ngày tạo</th>
                            <th>Đơn vị / đối tác</th>
                            <th class="text-money">Doanh thu</th>
                            <th class="text-money">Chi phí</th>
                            <th class="text-money">Lợi nhuận</th>
                            <th class="text-center">% Lãi</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-money">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $item)
                            <tr id="row-{{ $item->id }}">
                                <td class="pf-muted">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td><b>{{ $item->unit->unit_abbreviation ?? '—' }}</b></td>
                                <td class="text-money">{{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                                <td class="text-money text-danger">{{ number_format($item->total_cost, 0, ',', '.') }}</td>
                                <td class="text-money fw-bold {{ $item->net_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($item->net_profit, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $item->profit_margin >= 30 ? 'bg-success' : ($item->profit_margin > 0 ? 'bg-warning' : 'bg-danger') }}">{{ $item->profit_margin }}%</span>
                                </td>
                                <td class="text-center" id="status-cell-{{ $item->id }}">
                                    @if ($item->status == 1)
                                        <span class="badge bg-success">Đã duyệt</span>
                                    @else
                                        <span class="badge bg-warning">Chờ duyệt</span>
                                    @endif
                                </td>
                                <td class="pf-actions">
                                    @if (Auth::user()->name == 'Admin' || Auth::user()->name == 'SubAdmin' || Auth::user()->name == 'Accountant')
                                        @if ($item->status == 0)
                                            <button class="pf-btn pf-btn--ok pf-btn--sm btn-approve" data-id="{{ $item->id }}" title="Duyệt phiếu này"><i class="fas fa-check"></i> Duyệt</button>
                                        @endif
                                    @endif
                                    <button class="pf-btn pf-btn--ghost pf-btn--sm btn-load-report" data-id="{{ $item->id }}"><i class="far fa-eye"></i> Xem</button>
                                    <button class="pf-btn pf-btn--danger pf-btn--sm btn-delete-report" data-id="{{ $item->id }}" title="Xóa"><i class="far fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="pf-empty">Chưa có bản ghi nào được lưu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
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
