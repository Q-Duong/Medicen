$(document).ready(function() {
    
    // SETUP CSRF
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': profitConfig.csrf_token } });

    let isLoading = false; 
    let finalData = { total_revenue: 0, total_cost: 0, net_profit: 0, profit_margin: 0 };

    // --- MỚI: CHẶN NHẬP CHỮ VÀ KÝ TỰ ĐẶC BIỆT ---
    // Chỉ cho phép số 0-9 và dấu chấm (.)
    $('body').on('input', '.number-only', function() {
        // Thay thế mọi ký tự KHÔNG PHẢI là số hoặc dấu chấm bằng rỗng
        let val = $(this).val();
        let cleanVal = val.replace(/[^0-9.]/g, '');
        
        // Chặn nhập nhiều dấu chấm (ví dụ 10.5.2)
        if ((cleanVal.match(/\./g) || []).length > 1) {
             cleanVal = cleanVal.replace(/\.+$/, "");
        }

        if(val !== cleanVal) {
            $(this).val(cleanVal);
        }
    });

    // --- CÁC HÀM TIỆN ÍCH ---
    function formatMoney(num) { return new Intl.NumberFormat('vi-VN').format(Math.round(num)); }
    function getVal(selector) {
        let val = $(selector).val();
        if (val) val = val.toString().replace(/,/g, '');
        return val ? parseFloat(val) : 0;
    }

    // --- HÀM TÍNH TOÁN ---
    function calculate() {
        let soNgay = getVal('#inp_so_ngay');
        let soKm = getVal('#inp_so_km');
        let soNguoi = getVal('#inp_so_nguoi'); 
        let soDem = getVal('#inp_so_dem');

        let xqQty = getVal('#thu_xq_sl');
        let cstlQty = getVal('#thu_cstl_sl');
        let readingUnits = xqQty + (cstlQty * 2);

        // Auto update
        $('#chi_baophim_sl').val(xqQty);
        $('#chi_phim16_sl').val(xqQty);
        $('#chi_phim810_sl').val(cstlQty);
        $('#chi_bsdoc_sl').val(readingUnits);
        $('#chi_bpkq_sl').val(readingUnits);
        $('#chi_taixe_sl').val(soNgay);
        $('#chi_ktv_sl').val(soNgay);
        $('#chi_ks_sl').val(soDem); 
        $('#chi_dem_sl').val(soDem);

        // 1. Doanh thu
        let thuXQ = getVal('#thu_xq_gia') * xqQty;
        let thuCSTL = getVal('#thu_cstl_gia') * cstlQty;
        let thuPhuThu = getVal('#thu_phuthu_gia') * getVal('#thu_phuthu_sl');
        let thuBS = getVal('#thu_bs_gia') * getVal('#thu_bs_sl');
        let tongThu = thuXQ + thuCSTL + thuPhuThu + thuBS;

        $('#thu_xq_total').text(formatMoney(thuXQ));
        $('#thu_cstl_total').text(formatMoney(thuCSTL));
        $('#thu_phuthu_total').text(formatMoney(thuPhuThu));
        $('#thu_bs_total').text(formatMoney(thuBS));
        $('#tong_thu').text(formatMoney(tongThu));

        // 2. Chi phí
        let chiBaoPhim = getVal('#chi_baophim_gia') * getVal('#chi_baophim_sl');
        let chiPhim16 = getVal('#chi_phim16_gia') * getVal('#chi_phim16_sl');
        let chiPhim810 = getVal('#chi_phim810_gia') * getVal('#chi_phim810_sl');
        let chiPhim1012 = getVal('#chi_phim1012_gia') * getVal('#chi_phim1012_sl');
        let chiChietKhau = getVal('#chi_chietkhau_gia') * getVal('#chi_chietkhau_sl');

        $('#chi_baophim_total').text(formatMoney(chiBaoPhim));
        $('#chi_phim16_total').text(formatMoney(chiPhim16));
        $('#chi_phim810_total').text(formatMoney(chiPhim810));
        $('#chi_phim1012_total').text(formatMoney(chiPhim1012));
        $('#chi_chietkhau_total').text(formatMoney(chiChietKhau));

        let chiBSDoc = getVal('#chi_bsdoc_gia') * readingUnits;
        let chiBSTrucTiep = getVal('#chi_bstructiep_gia') * getVal('#chi_bstructiep_sl');
        let chiBPKQ = getVal('#chi_bpkq_gia') * readingUnits;
        let chiPhuCapXa = getVal('#chi_phucap_xa_gia') * getVal('#chi_phucap_xa_sl') * soNguoi;
        
        let chiTaiXe = getVal('#chi_taixe_gia') * soNgay;
        let chiKTV = (getVal('#chi_ktv_gia_ca') * readingUnits) + (getVal('#chi_ktv_gia_ngay') * soNgay);
        let chiThueTX = getVal('#chi_thue_tx_gia') * getVal('#chi_thue_tx_sl');
        let chiThueKTV = getVal('#chi_thue_ktv_gia') * getVal('#chi_thue_ktv_sl');

        $('#chi_bsdoc_total').text(formatMoney(chiBSDoc));
        $('#chi_bstructiep_total').text(formatMoney(chiBSTrucTiep));
        $('#chi_taixe_total').text(formatMoney(chiTaiXe));
        $('#chi_bpkq_total').text(formatMoney(chiBPKQ));
        $('#chi_ktv_total').text(formatMoney(chiKTV));
        $('#chi_phucap_xa_total').text(formatMoney(chiPhuCapXa));
        $('#chi_thue_tx_total').text(formatMoney(chiThueTX));
        $('#chi_thue_ktv_total').text(formatMoney(chiThueKTV));

        let chiQuanLy = tongThu * (getVal('#chi_quanly_phantram') / 100);
        let dinhMuc = getVal('#chi_dau_dinhmuc');
        let chiDau = (dinhMuc > 0) ? (soKm / dinhMuc) * getVal('#chi_dau_gia') : 0;
        let chiDauPhatSinh = getVal('#chi_dau_phatsinh_gia') * getVal('#chi_dau_phatsinh_sl');
        let chiCauDuong = getVal('#chi_cauduong_gia') * getVal('#chi_cauduong_sl');
        let chiDangKiem = getVal('#chi_dangkiem_gia') * getVal('#chi_dangkiem_sl');
        let chiKS = getVal('#chi_ks_gia') * getVal('#chi_ks_sl');
        let chiDem = getVal('#chi_dem_gia') * getVal('#chi_dem_sl') * soNguoi;
        let vatRate = getVal('#chi_vat_phantram') / 100;
        let chiVAT = (tongThu / (1 + vatRate)) * vatRate;

        $('#chi_quanly_total').text(formatMoney(chiQuanLy));
        $('#chi_dau_total').text(formatMoney(chiDau));
        $('#chi_dau_phatsinh_total').text(formatMoney(chiDauPhatSinh));
        $('#chi_cauduong_total').text(formatMoney(chiCauDuong));
        $('#chi_dangkiem_total').text(formatMoney(chiDangKiem));
        $('#chi_ks_total').text(formatMoney(chiKS));
        $('#chi_dem_total').text(formatMoney(chiDem));
        $('#chi_vat_total').text(formatMoney(chiVAT));

        let tongChi = chiBaoPhim + chiPhim16 + chiPhim810 + chiPhim1012 + chiChietKhau +
                      chiBSDoc + chiBSTrucTiep + chiTaiXe + chiThueTX + chiKTV + chiThueKTV + chiBPKQ + chiPhuCapXa +
                      chiQuanLy + chiDau + chiDauPhatSinh + chiCauDuong + chiDangKiem + chiKS + chiDem + chiVAT;

        $('#tong_chi').text(formatMoney(tongChi));
        let loiNhuan = tongThu - tongChi;
        $('#loi_nhuan').text(formatMoney(loiNhuan));
        
        if(loiNhuan < 0) $('#loi_nhuan').addClass('text-danger').removeClass('text-success');
        else $('#loi_nhuan').addClass('text-success').removeClass('text-danger');

        let tySuat = (tongThu > 0) ? (loiNhuan / tongThu) * 100 : 0;
        $('#ty_suat').text(tySuat.toFixed(2) + '%');

        finalData.total_revenue = tongThu;
        finalData.total_cost = tongChi;
        finalData.net_profit = loiNhuan;
        finalData.profit_margin = tySuat.toFixed(2);
    }

    // Trigger events
    $('#profitForm').on('input change keyup', 'input, select, textarea', function() {
        calculate();
        if (isLoading === false) { $('#btnSave').fadeIn(); }
    });
    
    $('#unit_id, #comments, #inp_so_ngay, #inp_so_km, #inp_so_dem, #inp_so_nguoi').on('input change', function() {
        calculate();
        if (!isLoading) $('#btnSave').fadeIn();
    });

    calculate(); // Run on load

    // SAVE
    $('#btnSave').click(function() {
        let unitId = $('#unit_id').val();
        if(!unitId) { Swal.fire('Lỗi', 'Chưa chọn Đơn vị!', 'error'); return; }

        let inputData = {};
        $('input, textarea, select').each(function() {
            let id = $(this).attr('id');
            if(id) inputData[id] = $(this).val();
        });

        $(this).prop('disabled', true);
        $('#btnText').text('ĐANG LƯU...');
        $('#btnSpinner').show();

        $.ajax({
            url: profitConfig.url_save, method: "POST",
            data: {
                unit_id: unitId, comments: $('#comments').val(),
                total_revenue: finalData.total_revenue, total_cost: finalData.total_cost,
                net_profit: finalData.net_profit, profit_margin: finalData.profit_margin,
                input_data: inputData
            },
            success: function() {
                Swal.fire('Thành công', 'Đã lưu báo cáo!', 'success').then(() => { location.reload(); });
            },
            error: function() {
                Swal.fire('Lỗi', 'Có lỗi khi lưu!', 'error');
                $('#btnSave').prop('disabled', false); $('#btnText').text('LƯU KẾT QUẢ'); $('#btnSpinner').hide();
            }
        });
    });

    // LOAD
    $('.btn-load-report').click(function() {
        let reportId = $(this).data('id');
        let btn = $(this); let originalText = btn.text();
        btn.text('Loading...').prop('disabled', true);

        isLoading = true; $('#btnSave').hide();
        let url = profitConfig.url_load.replace(':id', reportId);

        $.ajax({
            url: url, method: 'GET',
            success: function(response) {
                if(response.input_data) {
                    $.each(response.input_data, function(key, value) {
                        if(key !== 'unit_id') $('#' + key).val(value);
                    });
                }
                if(response.unit_id) { $('#unit_id').val(String(response.unit_id)).change(); }
                $('#comments').val(response.comments);
                calculate(); 

                // Title bar
                let unitText = $('#unit_id option:selected').text();
                let dateObj = new Date(response.created_at);
                let dateStr = dateObj.toLocaleDateString('vi-VN') + ' ' + dateObj.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
                $('#viewModeTitle').text(`${unitText} (Ngày: ${dateStr})`);
                $('#viewModeAlert').slideDown();

                $('html, body').animate({ scrollTop: 0 }, 'fast');
                setTimeout(function() { isLoading = false; }, 200);
            },
            error: function() {
                Swal.fire('Lỗi', 'Không tải được dữ liệu', 'error'); isLoading = false; $('#btnSave').show();
            },
            complete: function() { btn.text(originalText).prop('disabled', false); }
        });
    });

    // EXIT VIEW MODE
    $('#btnExitViewMode').click(function() {
        // 1. Ẩn thanh thông báo
        $('#viewModeAlert').slideUp();

        // 2. Reset form chính về trắng
        $('#profitForm')[0].reset();
        
        // 3. Reset các ô chọn đơn vị & ghi chú
        $('#unit_name').val('').change();
        $('#comments').val('');

        // 4. RESET CÁC Ô NHẬP LIỆU CHUNG VỀ 0 (Theo yêu cầu của bạn)
        $('#inp_so_ngay').val(0);
        $('#inp_so_dem').val(0);
        $('#inp_so_km').val(0);
        $('#inp_so_nguoi').val(0);

        // 5. Tính toán lại toàn bộ (Lúc này kết quả sẽ về 0 hết)
        calculate();

        // 6. Hiện lại nút Save & Tắt cờ loading
        $('#btnSave').show();
        isLoading = false;

        Swal.fire({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, 
            icon: 'info', title: 'Đã trở về chế độ nhập mới'
        });
    });

    // APPROVE & DELETE
    $(document).on('click', '.btn-approve', function() {
        let btn = $(this);
        Swal.fire({
            title: 'Duyệt báo cáo?', icon: 'question', showCancelButton: true, confirmButtonText: 'Duyệt', confirmButtonColor: '#198754'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: profitConfig.url_approve.replace(':id', btn.data('id')), method: 'POST',
                    success: function() {
                        Swal.fire('Đã duyệt!', '', 'success');
                        $('#status-cell-' + btn.data('id')).html('<span class="badge bg-success">Đã duyệt</span>'); btn.fadeOut();
                    },
                    error: function() { Swal.fire('Lỗi', 'Không thể duyệt', 'error'); }
                });
            }
        });
    });

    $('.btn-delete-report').click(function() {
        let id = $(this).data('id');
        Swal.fire({ title: 'Xóa bản ghi?', showCancelButton: true, confirmButtonText: 'Xóa', confirmButtonColor: '#d33' }).then((res) => {
            if(res.isConfirmed) {
                $.ajax({
                    url: profitConfig.url_delete.replace(':id', id), method: 'DELETE',
                    success: function() { $('#row-' + id).fadeOut(); Swal.fire('Đã xóa', '', 'success'); }
                });
            }
        });
    });

});