/**
 * GLOBAL VARIABLES
 */
var typingTimer;
var doneTypingInterval = 600;
var nextPageUrl = null;
var isLoading = false;
const STORAGE_KEY = "accountant_filter_state";

/**
 * HELPER FUNCTIONS
 */

// 1. Format tiền tệ (VD: 100000 -> 100.000)
function formatCurrency(number) {
    if (number === null || number === "" || isNaN(number)) return number;
    return new Intl.NumberFormat("vi-VN").format(number);
}

// 2. Chuyển string tiền tệ thành số nguyên để tính toán (VD: "100.000" -> 100000)
const parseNumber = (value) => {
    if (!value) return 0;
    return parseInt(value.toString().replace(/\D/g, "")) || 0;
};

// 3. Format input ngay khi gõ (Chỉ cho phép nhập số)
const formatInputOnKeyup = (element) => {
    let val = parseNumber(element.val());
    element.val(new Intl.NumberFormat("vi-VN").format(val));
};

// 4. Lấy ID của dòng hiện tại (Dựa vào tr data-id hoặc name input)
const getRowId = (element) => {
    // Ưu tiên lấy từ data-id của tr cha
    let trId = element.closest("tr").data("id");
    if (trId) return trId;

    // Fallback: Lấy từ name="..._123"
    let name = element.attr("name");
    if (name) {
        let parts = name.split("_");
        return parts[parts.length - 1]; // Lấy phần tử cuối cùng
    }
    return null;
};

// 5. Hàm bật/tắt nút OK dựa trên số lượng checkbox được tick
function toggleApplyButton(container) {
    let checkedCount = container.find(".filter-checkbox:checked").length;
    let btnApply = container
        .closest(".dropdown-menu")
        .find(".btn-apply-filter");

    // Nếu không chọn gì (0) -> Disable nút OK. Ngược lại -> Enable.
    if (checkedCount === 0) {
        btnApply.prop("disabled", true);
    } else {
        btnApply.prop("disabled", false);
    }
}

// 6. Hàm format ngày (yyyy-mm-dd -> dd/mm/yyyy)
function formatDate(dateString) {
    if (!dateString) return "";
    try {
        let parts = dateString.split("-");
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return dateString;
    } catch (e) {
        return dateString;
    }
}

// 7. Hàm format tên xe
function formatCarName(car) {
    if (!car) return "";
    const carNames = {
        6: "Xe Thuê",
        7: "Xe Tăng Cường",
        8: "Siêu Âm",
    };

    if (carNames.hasOwnProperty(car)) {
        return carNames[car];
    }

    return car;
}

// 8. Hàm format trạng thái thanh toán
function formatAccountantStatus(status) {
    if (status == 0) return "Chưa thanh toán";
    if (status == 1) return "Đã thanh toán";
    return status;
}

// 9.
function formatOrderStatus(status_id) {
    let id = parseInt(status_id);
    switch (id) {
        case 0:
            return '<span class="badge badge-primary text-white" style="background-color: #27c24c">Đơn hàng mới</span>';
        case 1:
            return '<span class="badge badge-primary text-white" style="background-color: #FCB322">Đang xử lý</span>';
        case 2:
            return '<span class="badge badge-primary text-white" style="background-color: #c037df">Đã cập nhật số Cas thực tế</span>';
        case 3:
            return '<span class="badge badge-primary text-white" style="background-color: #007bff">Đã xử lý</span>';
        case 4:
            return '<span class="badge badge-primary text-white" style="background-color: #00d0e3">Đã cập nhật doanh thu</span>';
        default:
            return '<span class="badge badge-secondary">Chưa xác định</span>';
    }
}

/**
 * DOCUMENT READY & EVENTS
 */
$(document).ready(function () {
    // // --- BƯỚC 1: KHÔI PHỤC STATE KHI VỪA VÀO TRANG ---
    // let savedFilters = restoreFilterState();

    // // --- BƯỚC 2: GỌI AJAX LOAD BẢNG ---
    // // Nếu có savedFilters thì dùng nó, không thì để hàm getListAccountant tự lấy mặc định
    // // Lưu ý: Hàm getListAccountant cần sửa nhẹ để nhận tham số filter đầu vào (xem mục 4 bên dưới)
    // getListAccountant(false, savedFilters);

    restoreFilterState();
    getListAccountant();

    // ----------------------------------------------------------------
    // A. XỬ LÝ TÍNH TOÁN TRÊN GIAO DIỆN (EVENT DELEGATION)
    // Thay vì bind từng ID, ta bind theo Class chung
    // ----------------------------------------------------------------

    // 1. Tự động format tiền khi gõ
    $(document).on("keyup", ".calc-inputs", function () {
        formatInputOnKeyup($(this));
    });

    // ----------------------------------------------------------------
    // B. XỬ LÝ AJAX FILTER & INFINITE SCROLL
    // ----------------------------------------------------------------

    // 1. Ngăn Dropdown bị đóng
    $(document).on("click", ".excel-dropdown", function (e) {
        e.stopPropagation();
    });

    // 2. Sự kiện khi Bấm nút "Chọn tất cả"
    $(document).on("change", ".check-all", function () {
        let isChecked = $(this).is(":checked");
        let container = $(this)
            .closest(".dropdown-menu")
            .find(".dropdown-list-container");

        // Toggle tất cả checkbox con
        container.find(".filter-checkbox").prop("checked", isChecked);

        // Cập nhật nút Apply
        toggleApplyButton(container);
    });

    // 3. Sự kiện khi Bấm checkbox con -> Cập nhật nút "Chọn tất cả"
    $(document).on("change", ".filter-checkbox", function () {
        let container = $(this).closest(".dropdown-list-container");
        let total = container.find(".filter-checkbox").length;
        let checked = container.find(".filter-checkbox:checked").length;
        let checkAllBox = container
            .closest(".dropdown-menu")
            .find(".check-all");

        // LOGIC EXCEL:
        // - Nếu bỏ tick 1 cái -> Check All TẮT.
        // - Nếu tick đủ hết -> Check All BẬT SÁNG.

        if (checked === total) {
            checkAllBox.prop("checked", true); // <--- CHO PHÉP SÁNG LÊN
        } else {
            checkAllBox.prop("checked", false);
        }

        toggleApplyButton(container);
    });

    // 4. Tìm kiếm trong dropdown
    $(document).on("keyup input", ".search-in-dropdown", function () {
        let value = $(this).val().toLowerCase();
        let dropdownMenu = $(this).closest(".dropdown-menu");
        let container = dropdownMenu.find(".dropdown-list-container");
        let checkAllBox = dropdownMenu.find(".check-all");
        let checkAllWrapper = checkAllBox.closest("div, label");

        // 1. Ẩn/Hiện item theo từ khóa
        let items = container.find(".dropdown-item-checkbox");
        items.each(function () {
            let text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(value) > -1);
        });

        // 2. Đếm số lượng item ĐANG HIỂN THỊ
        let visibleItems = items.filter(":visible");
        let visibleCount = visibleItems.length;

        // 3. Xử lý hiển thị Check All
        container.find(".empty-search-msg").remove();

        if (visibleCount === 0) {
            checkAllWrapper.hide();
            container.append(
                '<div class="empty-search-msg text-center text-muted p-2">No items match your search.</div>'
            );
            checkAllBox.prop("checked", false);
        } else {
            checkAllWrapper.show();

            // 4. Logic bật tắt Check All khi Search
            // Nếu số lượng ĐÃ TICK trong các dòng ĐANG HIỆN == Tổng số dòng ĐANG HIỆN
            // -> Bật Check All (Để người dùng biết là đã chọn hết kết quả tìm kiếm)
            let visibleChecked = visibleItems.find(
                ".filter-checkbox:checked"
            ).length;

            if (visibleCount > 0 && visibleCount === visibleChecked) {
                checkAllBox.prop("checked", true);
            } else {
                checkAllBox.prop("checked", false);
            }
        }
        toggleApplyButton(container);
    });

    $(document).on("shown.bs.dropdown", ".dropdown", function () {
        let container = $(this).find(".dropdown-list-container");
        let fieldName = container.data("field");
        let dropdownMenu = container.closest(".dropdown-menu");
        let checkAllBox = dropdownMenu.find(".check-all");
        let btnApply = dropdownMenu.find(".btn-apply-filter"); // Tìm nút Áp dụng
        let searchInput = $(this)
            .find('input[type="text"], .search-in-dropdown')
            .first();

        if (searchInput.length > 0) {
            searchInput.focus();
        }

        // Lấy dữ liệu đã lưu
        let savedValues = container.data("saved-values");
        let isFiltering = savedValues !== undefined && savedValues !== null;

        let savedMap = [];
        if (isFiltering && Array.isArray(savedValues)) {
            savedMap = savedValues.map(String);
        }

        // ... (Đoạn định nghĩa format moneyFields, dateFields giữ nguyên) ...
        const moneyFields = [
            "order_price",
            "order_cost",
            "order_quantity",
            "order_discount",
            "order_percent_discount",
            "order_profit",
            "accountant_amount_paid",
            "accountant_owe",
            "accountant_35X43",
            "accountant_polime",
            "accountant_8X10",
            "accountant_10X12",
            "accountant_film_bag",
        ];
        const dateFields = [
            "ord_start_day",
            "accountant_date",
            "accountant_day_payment",
            "accountant_discount_day",
            "accountant_doctor_date_payment",
        ];

        // --- Hàm render ---
        const renderList = (list) => {
            let html = "";
            let hasData = false;

            let totalCountRender = 0;
            let checkedCountRender = 0;

            list.forEach((val) => {
                let rawValue = val;
                let displayValue = val;
                let isNullData = val === null || val === "";

                // ... (Logic format giữ nguyên) ...
                if (isNullData) {
                    displayValue = "(Blanks)";
                    rawValue = "NULL_EMPTY";
                } else {
                    if (moneyFields.includes(fieldName))
                        displayValue = formatCurrency(val);
                    else if (dateFields.includes(fieldName))
                        displayValue = formatDate(val);
                    else if (fieldName === "car_name")
                        displayValue = formatCarName(val);
                    else if (fieldName === "accountant_status")
                        displayValue = formatAccountantStatus(val);
                    else if (fieldName === "status_id")
                        displayValue = formatOrderStatus(val);
                }

                hasData = true;
                totalCountRender++;

                let valStr = String(rawValue);
                let shouldCheck = !isFiltering || savedMap.includes(valStr);
                if (shouldCheck) checkedCountRender++;

                // Render Checkbox
                // Lưu ý: Ta cứ render bình thường, việc disable sẽ xử lý bên dưới
                html += `<label class="dropdown-item-checkbox" style="display:flex; cursor:pointer;">
                        <input type="checkbox" value="${rawValue}" class="filter-checkbox" ${
                    shouldCheck ? "checked" : ""
                }> 
                        ${displayValue}
                     </label>`;
            });

            if (!hasData)
                html = '<div class="text-center text-muted p-2">Trống</div>';

            container.html(html);
            container.data("loaded", true);

            // ===========================================================
            // [LOGIC KHÓA CHẶT - SỬA LẠI]
            // Bỏ điều kiện !isFiltering.
            // Cứ hễ danh sách <= 1 dòng -> KHÓA VĨNH VIỄN.
            // ===========================================================

            if (totalCountRender <= 1) {
                // 1. Khóa nút Apply -> Đổi thành "Mặc định"
                btnApply.prop("disabled", true);
                btnApply.attr("data-locked", "true"); // Gắn cờ khóa
                btnApply
                    .removeClass("btn-primary btn-info")
                    .addClass("btn-secondary");
                btnApply.html("Apply Filter");

                // 2. Khóa Check All & Checkbox con (Chỉ cho nhìn)
                checkAllBox.prop("disabled", true).prop("checked", true);
                container.find(".filter-checkbox").prop("disabled", true);

                // Đảm bảo checkbox con duy nhất đó cũng được tick để user hiểu là "Đang chọn nó"
                container.find(".filter-checkbox").prop("checked", true);
            } else {
                // MỞ LẠI BÌNH THƯỜNG (Nếu danh sách > 1)

                // Reset nút Apply
                btnApply.prop("disabled", false);
                btnApply.removeAttr("data-locked");
                btnApply.removeClass("btn-secondary").addClass("btn-primary");
                btnApply.html("Apply Filter");

                // Reset Checkbox
                checkAllBox.prop("disabled", false);
                container.find(".filter-checkbox").prop("disabled", false);

                // Logic Check All tự động sáng (Giống Excel)
                if (
                    totalCountRender > 0 &&
                    checkedCountRender === totalCountRender
                ) {
                    checkAllBox.prop("checked", true);
                } else {
                    checkAllBox.prop("checked", false);
                }

                toggleApplyButton(container);
            }
        };

        // --- Case A: Cache ---
        if (container.data("loaded")) {
            // Với Case Cache, ta cũng phải đếm lại để quyết định Khóa hay Mở
            let total = container.find(".filter-checkbox").length;
            let checked = container.find(".filter-checkbox:checked").length;

            // ... (Logic tick lại checkbox theo savedMap giữ nguyên) ...
            container.find(".filter-checkbox").each(function () {
                let valStr = String($(this).val());
                let shouldCheck = !isFiltering || savedMap.includes(valStr);
                $(this).prop("checked", shouldCheck);
            });

            // --- LOGIC KHÓA CHO CASE CACHE ---
            if (total <= 1) {
                btnApply
                    .prop("disabled", true)
                    .attr("data-locked", "true")
                    .addClass("btn-secondary")
                    .removeClass("btn-primary")
                    .html("Apply Filter");
                checkAllBox.prop("disabled", true).prop("checked", true);
                container
                    .find(".filter-checkbox")
                    .prop("disabled", true)
                    .prop("checked", true);
            } else {
                btnApply
                    .prop("disabled", false)
                    .removeAttr("data-locked")
                    .addClass("btn-primary")
                    .removeClass("btn-secondary")
                    .html("Apply Filter");
                checkAllBox.prop("disabled", false);
                container.find(".filter-checkbox").prop("disabled", false);

                // Logic Check All
                if (total > 0 && checked === total)
                    checkAllBox.prop("checked", true);
                else checkAllBox.prop("checked", false);

                toggleApplyButton(container);
            }
            return;
        }

        // --- Case B: Ajax (Giữ nguyên) ---
        let currentFilters = { ...getValuesFilterObj() };
        if (currentFilters.hasOwnProperty(fieldName))
            delete currentFilters[fieldName];
        currentFilters.field = fieldName;
        currentFilters.year = $(".year-filter").val();
        currentFilters.type = $(".type-filter").val();

        $.ajax({
            url: url_filter_options,
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: currentFilters,
            beforeSend: function () {
                container.html(
                    '<div class="text-center text-muted p-2">Loading...</div>'
                );
            },
        })
            .done(function (response) {
                renderList(response);
            })
            .fail(function (jqXHR) {
                if (jqXHR.status === 401 || jqXHR.status === 419)
                    popupNotificationSessionExpired();
                else
                    container.html(
                        '<div class="text-danger p-2">Lỗi tải dữ liệu</div>'
                    );
            });
    });

    // 6. Xử lý nút OK (Apply Filter)
    $(document).on("click", ".btn-apply-filter", function (e) {
        e.preventDefault();
        e.stopPropagation();

        try {
            let dropdown = $(this).closest(".dropdown");
            let container = dropdown.find(".dropdown-list-container");
            let btnToggle = dropdown.find(".dropdown-toggle");

            if (container.length === 0) return;

            let currentField = container.data("field");

            // 1. KIỂM TRA KHÓA
            if ($(this).attr("data-locked") === "true") {
                dropdown
                    .removeClass("open show")
                    .find(".dropdown-toggle")
                    .attr("aria-expanded", "false");
                return;
            }

            // ===========================================================
            // 2. [FIX QUAN TRỌNG] LẤY DỮ LIỆU THÔNG MINH
            // ===========================================================

            // Kiểm tra xem có đang tìm kiếm không?
            let searchInput = dropdown.find("input[type='text']");
            let isSearching =
                searchInput.val() && searchInput.val().trim() !== "";

            let allCheckboxes = container.find(".filter-checkbox");
            let totalCount = allCheckboxes.length; // Tổng số phần tử gốc
            let checkedCount = 0;
            let newValues = [];

            if (isSearching) {
                // [LOGIC MỚI] NẾU ĐANG TÌM KIẾM -> CHỈ TÍNH NHỮNG CÁI ĐANG HIỆN (VISIBLE)
                // Bỏ qua những cái đang ẩn (dù nó có đang tick hay không)

                let visibleCheckboxes = container.find(
                    ".filter-checkbox:visible:checked"
                );
                checkedCount = visibleCheckboxes.length;

                visibleCheckboxes.each(function () {
                    newValues.push(String($(this).val()));
                });
            } else {
                // NẾU KHÔNG TÌM KIẾM -> LẤY TẤT CẢ NHỮNG CÁI ĐANG TICK (KỂ CẢ ẨN)
                let allChecked = container.find(".filter-checkbox:checked");
                checkedCount = allChecked.length;

                allChecked.each(function () {
                    newValues.push(String($(this).val()));
                });
            }
            // 3. QUYẾT ĐỊNH: RESET HAY FILTER?
            let isSelectAll = false;

            if (totalCount === 0) {
                isSelectAll = true;
            } else if (isSearching) {
                // Nếu đang search -> Luôn là Lọc (trừ khi không chọn cái nào)
                if (checkedCount === 0)
                    isSelectAll = true; // Không chọn gì = All (Tùy logic)
                else isSelectAll = false; // Có chọn -> Lọc cứng
            } else {
                // Không search -> So sánh số lượng
                if (checkedCount === totalCount) {
                    isSelectAll = true;
                } else {
                    isSelectAll = false;
                }
            }

            // 4. KIỂM TRA THAY ĐỔI
            let oldSaved = container.data("saved-values");
            let isChanged = false;

            if (isSelectAll) {
                if (oldSaved !== undefined && oldSaved !== null)
                    isChanged = true;
            } else {
                if (oldSaved === undefined || oldSaved === null)
                    isChanged = true;
                else {
                    let oldStr = JSON.stringify([...oldSaved].sort());
                    let newStr = JSON.stringify([...newValues].sort());
                    if (oldStr !== newStr) isChanged = true;
                }
            }

            if (!isChanged) {
                dropdown
                    .removeClass("open show")
                    .find(".dropdown-toggle")
                    .attr("aria-expanded", "false");
                return;
            }

            // 5. UPDATE & RELOAD
            let finalFilters = getValuesFilterObj();

            if (isSelectAll) {
                // RESET
                container.removeData("saved-values");
                btnToggle
                    .find(".selected-text")
                    .html('<i class="fa-solid fa-filter"></i>');
                btnToggle.removeClass("btn-info");
                if (finalFilters.hasOwnProperty(currentField))
                    delete finalFilters[currentField];
            } else {
                // FILTER
                container.data("saved-values", newValues);
                btnToggle
                    .find(".selected-text")
                    .html(`(${checkedCount}) <span class="caret"></span>`);
                btnToggle.addClass("btn-info");
                finalFilters[currentField] = newValues;
            }

            let otherContainers = $(".dropdown-list-container").not(container);
            otherContainers.removeData("loaded");
            otherContainers.html(
                '<div class="text-center text-muted p-2">Loading...</div>'
            );

            dropdown
                .removeClass("open show")
                .find(".dropdown-toggle")
                .attr("aria-expanded", "false");
            localStorage.setItem(
                "accountant_filter_state",
                JSON.stringify(finalFilters)
            );

            $(".tbody-content").empty();
            if (typeof getListAccountant === "function") getListAccountant();
        } catch (err) {
            console.error(err);
            alert("Lỗi: " + err.message);
        }
    });

    // 7. Close
    $(document).on("click", ".close-dropdown-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();

        let dropdown = $(this).closest(".dropdown");

        dropdown.removeClass("show open");
        dropdown.find(".dropdown-menu").removeClass("show");
        dropdown.find(".dropdown-toggle").attr("aria-expanded", "false");
    });

    // 3. Infinite Scroll (Cuộn để tải thêm)
    $('.table-scroll').on('scroll', function () {
        let container = $(this);
        if (
            container.scrollTop() + container.innerHeight() >= 
            container[0].scrollHeight - 20
        ) {
            if (nextPageUrl && !isLoading) {
                getListAccountant(true);
            }
        }
    });

    $(document).on("change", ".year-filter, .type-filter", function () {
        saveFilterState();
        $(".dropdown-list-container").removeData("loaded");
        $(".dropdown-list-container").html(
            '<div class="text-center text-muted p-2">Loading...</div>'
        );
        nextPageUrl = url_filter_accountant;
        $(".tbody-content").empty();
        getListAccountant();
    });

    $(document).on("change", ".accountant_status", function () {
        var value = $(this).val();
        if (value == 0) {
            $(this)
                .removeClass("acc-status-paid")
                .addClass("acc-status-unpaid");
        } else {
            $(this)
                .removeClass("acc-status-unpaid")
                .addClass("acc-status-paid");
            $(this).closest("tr").removeClass("debt-overrated debt-warning");
        }
    });

    // ----------------------------------------------------------------
    // C. XỬ LÝ CẬP NHẬT DB (AUTO SAVE)
    // ----------------------------------------------------------------
    function debounce(func, wait) {
        let timeout;
        return function () {
            const context = this,
                args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    const getNumber = (val) => {
        if (!val) return 0;
        return parseFloat(val.replace(/\./g, "").replace(/,/g, "")) || 0;
    };

    // Hàm lấy "Chữ ký" dữ liệu của dòng (Dùng để so sánh)
    // Nó sẽ biến toàn bộ dữ liệu dòng thành 1 chuỗi JSON string
    const getRowSnapshot = (row) => {
        let data = row.find("input, select").serializeArray();
        return JSON.stringify(data);
    };

    // --- 2. KHỞI TẠO DỮ LIỆU GỐC (SNAPSHOT) ---
    $("tr[data-id]").each(function () {
        let row = $(this);
        row.data("original", getRowSnapshot(row));
    });

    // --- 3. TÍNH TOÁN (CALCULATE) ---
    function calculateRow(row, sourceName) {
        // ---------------------------------------------------------
        // A. LẤY DỮ LIỆU
        // ---------------------------------------------------------
        let qty = getNumber(row.find(".order_quantity").val());
        let cost = getNumber(row.find(".order_cost").val());
        let paid = getNumber(row.find(".accountant_amount_paid").val());

        let currentTotalInput = getNumber(row.find(".order_price").val());
        let currentDiscountMoney = getNumber(row.find(".order_discount").val());

        let vatStr = row.find(".order_vat").val() || "";
        let vatClean = vatStr.toLowerCase().trim();

        // ---------------------------------------------------------
        // B. TÍNH THÀNH TIỀN GỐC (Base Price)
        // ---------------------------------------------------------
        let basePrice = 0;
        if (sourceName === "order_quantity" || sourceName === "order_cost") {
            basePrice = qty * cost;
            row.find(".order_price").val(formatCurrency(basePrice));
        } else if (sourceName === "order_price") {
            basePrice = currentTotalInput;
        } else {
            basePrice = currentTotalInput;
            if (basePrice === 0 && qty * cost > 0) {
                basePrice = qty * cost;
                row.find(".order_price").val(formatCurrency(basePrice));
            }
        }

        // ---------------------------------------------------------
        // C. TÍNH CHIẾT KHẤU (Giữ nguyên logic %, /ca, số thường)
        // ---------------------------------------------------------
        let discountAmount = 0;

        if (
            sourceName === "order_percent_discount" ||
            sourceName === "order_vat" ||
            sourceName === "order_quantity" ||
            sourceName === "order_cost" ||
            sourceName === "order_price"
        ) {
            let rawDiscountStr =
                row.find(".order_percent_discount").val() || "";
            let lowerStr = rawDiscountStr.toLowerCase();
            let cleanStr = rawDiscountStr
                .toString()
                .replace(/\./g, "")
                .replace(/,/g, ".");

            if (lowerStr.includes("%")) {
                let rate = parseFloat(cleanStr.replace("%", "")) || 0;
                let divisor = 1.1;
                if (vatClean.includes("không") || vatClean.includes("khong")) {
                    divisor = 1;
                }
                discountAmount = (basePrice / divisor) * (rate / 100);
            } else if (lowerStr.includes("/ca")) {
                let unitVal =
                    parseFloat(cleanStr.toLowerCase().replace("/ca", "")) || 0;
                discountAmount = unitVal * qty;
            } else if (!isNaN(parseFloat(cleanStr)) && cleanStr.trim() !== "") {
                discountAmount = parseFloat(cleanStr);
            } else {
                discountAmount = 0;
            }

            discountAmount = Math.round(discountAmount);
            row.find(".order_discount").val(formatCurrency(discountAmount));
        } else if (sourceName === "order_discount") {
            discountAmount = currentDiscountMoney;
        } else {
            discountAmount = currentDiscountMoney;
        }

        // ---------------------------------------------------------
        // D. TÍNH KẾT QUẢ CUỐI (THAY ĐỔI Ở ĐÂY)
        // ---------------------------------------------------------

        // 1. Cột Lợi nhuận (order_profit): Vẫn TRỪ chiết khấu
        let finalTotal = basePrice - discountAmount;

        // 2. Cột Còn nợ (accountant_owe): KHÔNG TRỪ chiết khấu
        // Logic mới: Nợ = Thành tiền gốc - Đã thanh toán
        let owe = basePrice - paid;

        // ---------------------------------------------------------
        // E. CẬP NHẬT GIAO DIỆN
        // ---------------------------------------------------------
        row.find(".order_profit").val(formatCurrency(finalTotal));
        row.find(".accountant_owe").val(formatCurrency(owe));

        // ---------------------------------------------------------
        // F. NGÀY THÁNG (Giữ nguyên)
        // ---------------------------------------------------------
        if (
            sourceName === "accountant_deadline" ||
            sourceName === "accountant_date"
        ) {
            let deadline =
                parseInt(row.find(".accountant_deadline").val()) || 0;
            let dateStr = row.find(".accountant_date").val();
            if (dateStr) {
                try {
                    let parts = dateStr.split("/");
                    if (parts.length === 3) {
                        let d = new Date(parts[2], parts[1] - 1, parts[0]);
                        d.setDate(d.getDate() + deadline);
                        let res =
                            ("0" + d.getDate()).slice(-2) +
                            "/" +
                            ("0" + (d.getMonth() + 1)).slice(-2) +
                            "/" +
                            d.getFullYear();
                        row.find(".accountant_day_payment").val(res);
                    }
                } catch (e) {}
            } else {
                row.find(".accountant_day_payment").val("");
            }
        }
    }

    // --- HÀM 3: LƯU SERVER (SYNC) ---
    const saveRowToServer = debounce(function (row) {
        let rowId = row.data("id");
        let currentSnapshot = getRowSnapshot(row);
        let originalSnapshot = row.data("original");

        if (currentSnapshot === originalSnapshot) {
            row.find("input, select").css("border-color", "");
            return;
        }
        row.find("input, select").css("border-color", "#ffc107");

        let rowData = row.find("input, select").serializeArray();
        let storedFilter = localStorage.getItem("accountant_filter_state");
        let filterArray = [];

        if (storedFilter) {
            try {
                let filterObj = JSON.parse(storedFilter);
                Object.keys(filterObj).forEach((key) => {
                    filterArray.push({ name: key, value: filterObj[key] });
                });
            } catch (e) {}
        }

        $.ajax({
            url: url_update_accountant,
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: {
                id: rowId,
                row_data: rowData,
                filter_data: filterArray,
            },
            success: function (res) {
                if (res.success) {
                    row.data("original", currentSnapshot);
                    row.find(".status-id").html(
                        '<span class="badge badge-primary text-white" style="background-color: #00d0e3">Đã cập nhật doanh thu</span>'
                    );
                    row.find("input, select").css("border-color", "#28a745");
                    setTimeout(
                        () => row.find("input, select").css("border-color", ""),
                        1000
                    );

                    if (res.totals) updateTotalsUI(res.totals);

                    $(".dropdown-list-container").removeData("loaded");
                    $(".filter-box").removeData("loaded");
                } else {
                    row.find("input, select").css("border-color", "red");
                    // if (res.message) alert(res.message);
                }
            },
            error: function (xhr) {
                row.find("input, select").css("border-color", "red");
                console.log("Lỗi update:", xhr.responseText);
            },
        });
    }, 800);

    // --- SỰ KIỆN CHÍNH ---
    $(document).on(
        "input change",
        ".input-accountant, .calc-inputs, .selectbox-accountant",
        function () {
            let input = $(this);
            let row = input.closest("tr");

            if (row.hasClass("row-locked")) {
                return false;
            }

            let rawName = input.attr("name");
            let sourceName = rawName;
            if (rawName.includes("_")) {
                let parts = rawName.split("_");
                if (!isNaN(parts[parts.length - 1])) {
                    parts.pop();
                    sourceName = parts.join("_");
                }
            }

            calculateRow(row, sourceName);
            saveRowToServer(row);
        }
    );

    // Nút hoàn thành
    $(document).on("click", ".btn-complete-account", function (e) {
        e.preventDefault();
        let btn = $(this);
        let order_id = btn.data("id");

        $.ajax({
            url: url_complete_accountant,
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: { order_id: order_id },
            beforeSend: function () {
                btn.prop("disabled", true).text("Đang xử lý...");
            },
            success: function (data) {
                if (data.success) {
                    $(".status_id_" + order_id).html(
                        '<span class="badge badge-primary text-white" style="background-color: #007bff">Đã xử lý</span>'
                    );

                    btn.hide();

                    let row = btn.closest("tr");
                    row.addClass("row-locked");
                    row.find("input, select").prop("disabled", true);
                    row.css("background-color", "#f8f9fa");
                    $(".dropdown-list-container").removeData("loaded");
                } else {
                    alert(data.message);
                    btn.prop("disabled", false).text("Hoàn thành");
                }
            },
            error: function (err) {
                console.log(err);
                alert("Có lỗi xảy ra, vui lòng thử lại!");
                btn.prop("disabled", false).text("Hoàn thành");
            },
        });
    });
}); // End Document Ready

/**
 * CÁC HÀM AJAX CHÍNH
 */

function getListAccountant(isAppend = false) {
    if (!nextPageUrl && isAppend) return;

    isLoading = true;
    let currentCount = $(".tbody-content tr").length;
    let filterData = getValuesFilterObj();
    filterData.current_count = currentCount;
    let url = isAppend ? nextPageUrl : url_get_accountant;
    $.ajax({
        url: url,
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        cache: false,
        data: filterData,
        beforeSend: function () {
            // if (!isAppend) $(".loader-over").fadeIn();
        },
    })
        .done(function (data) {
            if (isAppend) {
                $(".tbody-content").append(data.html);
            } else {
                $(".tbody-content").html(data.html);
            }

            if (data.totals !== undefined) {
                updateTotalsUI(data.totals);
            }

            nextPageUrl = data.next_page_url;

            if (!data.html && !isAppend) {
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            popupNotificationSessionExpired();
        })
        .complete(function () {
            isLoading = false;
            // $(".loader-over").fadeOut();
        });
}

function updateTotalsUI(data) {
    $("#total-price").text(formatCurrency(data.totalPrice));
    $("#total-owe").text(formatCurrency(data.totalOwe));
    $("#total-amount-paid").text(formatCurrency(data.totalAmountPaid));
    $("#total-quantity").text(formatCurrency(data.totalQuantity));
    $("#total-discount").text(formatCurrency(data.totalDiscount));
    $("#total-35").text(data.total35);
    $("#total-polime").text(data.totalPolime);
    $("#total-8").text(data.total8);
    $("#total-10").text(data.total10);
    $("#total-pack").text(data.totalPack);
}

// Hàm gom dữ liệu gửi lên Server
function getValuesFilterObj() {
    let data = {};

    $(".year-filter, .type-filter").each(function () {
        data[$(this).attr("name")] = $(this).val();
    });

    $(".dropdown-list-container").each(function () {
        let field = $(this).data("field");
        let saved = $(this).data("saved-values");

        if (saved && Array.isArray(saved)) {
            data[field] = saved;
        }
    });

    return data;
}

// Hàm lưu State vào LocalStorage
function saveFilterState() {
    let filterData = getValuesFilterObj();
    localStorage.setItem("accountant_filter_state", JSON.stringify(filterData));
}

// Hàm khôi phục State khi F5
function restoreFilterState() {
    let savedState = localStorage.getItem("accountant_filter_state");
    if (!savedState) return;

    let filterData = JSON.parse(savedState);

    // Loop qua từng key đã lưu
    for (const [key, value] of Object.entries(filterData)) {
        // 1. Input thường
        let input = $(`[name="${key}"]`);
        if (input.length) input.val(value);

        // 2. Dropdown Excel
        let container = $(`.dropdown-list-container[data-field="${key}"]`);
        if (container.length && Array.isArray(value)) {
            // Gán dữ liệu vào data-attribute chờ sẵn
            container.data("saved-values", value);

            // Cập nhật giao diện nút ngay lập tức
            let btn = container.closest(".dropdown").find(".dropdown-toggle");
            btn.find(".selected-text").html(
                `(${value.length}) <span class="caret"></span>`
            );
            btn.addClass("btn-info");
        }
    }
}

// Hàm lấy value của 1 dòng (Row) để update
function getValuesRow(order_id) {
    // Yêu cầu: Tr bao dòng đó phải có data-id="order_id"
    let inputs = $(`tr[data-id="${order_id}"]`).find(":input").serializeArray();
    return inputs;
}

// Popup thông báo
function successMsg(msg) {
    // Code hiển thị toast/popup của bạn
    alert(msg);
}
