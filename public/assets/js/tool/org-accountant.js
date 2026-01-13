/**
 * GLOBAL VARIABLES
 */
var typingTimer;
var doneTypingInterval = 600;
var nextPageUrl = null;
var isLoading = false;
let originalParent = null;
let currentMenu = null;
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

// 10.
function updateDateDisplay(input) {
    let val = $(input).val(); // Giá trị thực: 2026-01-05
    if (val) {
        let date = new Date(val);
        if (!isNaN(date.getTime())) {
            // Cắt chuỗi cho chính xác (tránh bị lệch múi giờ)
            let parts = val.split("-"); // [2026, 01, 05]
            let formatted = `${parts[2]}/${parts[1]}/${parts[0]}`; // 05/01/2026
            $(input).attr("data-date", formatted);
        }
    } else {
        $(input).attr("data-date", ""); // Nếu rỗng thì ẩn
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
    initForceDateFormat();

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
        let dropdownContainer = $(this); // Đây là cái vỏ trong bảng (Wrapper)
        let dropdownMenu = dropdownContainer.find(".excel-dropdown"); // Menu cần bứng đi
        let button = dropdownContainer.find(".dropdown-toggle");

        // Nếu không tìm thấy menu (có thể do lỗi hoặc nó đã bị bứng đi rồi), return luôn
        if (dropdownMenu.length === 0) return;

        let container = dropdownMenu.find(".dropdown-list-container");
        let fieldName = container.data("field");
        let checkAllBox = dropdownMenu.find(".check-all");
        let btnApply = dropdownMenu.find(".btn-apply-filter");

        // -----------------------------------------------------------
        // [BƯỚC 1] GẮN THẺ ĐỊNH DANH (ID) ĐỂ TÌM ĐƯỜNG VỀ
        // -----------------------------------------------------------
        let parentId = dropdownContainer.attr("id");
        if (!parentId) {
            parentId =
                "dropdown-gen-" + Math.random().toString(36).substr(2, 9);
            dropdownContainer.attr("id", parentId);
        }
        // Gán ID wrapper vào menu
        dropdownMenu.attr("data-parent-id", parentId);

        // -----------------------------------------------------------
        // [BƯỚC 2] BỨNG RA BODY (QUAN TRỌNG: LÀM TRƯỚC KHI TÍNH TOÁN)
        // -----------------------------------------------------------
        originalParent = dropdownContainer;
        currentMenu = dropdownMenu;

        // Bứng menu ra khỏi bảng, gắn vào body
        $("body").append(dropdownMenu);

        // -----------------------------------------------------------
        // [BƯỚC 3] TÍNH TOÁN VỊ TRÍ & HIỂN THỊ
        // -----------------------------------------------------------
        let rect = button[0].getBoundingClientRect();
        let top = rect.bottom;
        let left = rect.left;

        // Chống tràn màn hình
        let menuWidth = 300; // Hoặc dropdownMenu.outerWidth()
        if (left + menuWidth > $(window).width()) left = rect.right - menuWidth;

        let menuHeight = 400; // Hoặc dropdownMenu.outerHeight()
        if (top + menuHeight > $(window).height()) top = rect.top - menuHeight;

        // Gán CSS để hiển thị ngay
        dropdownMenu.css({
            display: "block",
            position: "fixed",
            top: top + "px",
            left: left + "px",
            "z-index": "9999999",
            margin: 0,
        });

        // -----------------------------------------------------------
        // [BƯỚC 4] FOCUS VÀO Ô SEARCH (LÀM CUỐI CÙNG)
        // -----------------------------------------------------------
        // Lúc này menu đã yên vị ở Body, ta mới tìm ô input và focus

        let searchInput = dropdownMenu
            .find('input[type="text"], .search-in-dropdown')
            .first();

        if (searchInput.length > 0) {
            // Dùng setTimeout để đảm bảo trình duyệt đã render xong vị trí mới
            setTimeout(function () {
                searchInput.trigger("focus");
            }, 50);
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

    $(".dropdown").on("hidden.bs.dropdown", function () {
        if (currentMenu && originalParent) {
            currentMenu.css({
                display: "",
                top: "",
                left: "",
                position: "",
                "z-index": "",
            });
            originalParent.append(currentMenu);
            currentMenu = null;
            originalParent = null;
        }
    });

    // 6. Apply Filter
    $(document).on("click", ".btn-apply-filter", function (e) {
        e.preventDefault();
        e.stopPropagation();

        try {
            // ===========================================================
            // 1. [FIX QUAN TRỌNG] TÌM LẠI CHA CŨ (VÌ MENU ĐANG Ở BODY)
            // ===========================================================
            let dropdownMenu = $(this).closest(".excel-dropdown"); // Menu đang đứng (ở body)
            let container = dropdownMenu.find(".dropdown-list-container"); // Container dữ liệu

            // Lấy ID cha cũ đã lưu lúc mở (xem lại code show.bs.dropdown ở bước trước)
            let parentId = dropdownMenu.attr("data-parent-id");

            // Tìm lại cái vỏ dropdown cũ đang nằm trong bảng
            let dropdownWrapper = $("#" + parentId);

            // Fallback: Nếu không tìm thấy ID (phòng hờ), thử tìm theo cách cũ
            if (dropdownWrapper.length === 0)
                dropdownWrapper = $(this).closest(".dropdown");

            // Lấy nút Toggle (cái phễu) trong bảng
            let btnToggle = dropdownWrapper.find(".dropdown-toggle");

            if (container.length === 0) return;

            let currentField = container.data("field");

            // 2. KIỂM TRA KHÓA
            if ($(this).attr("data-locked") === "true") {
                dropdownWrapper
                    .removeClass("open show")
                    .find(".dropdown-toggle")
                    .attr("aria-expanded", "false");

                // Quan trọng: Gọi lệnh này để trả menu về chỗ cũ
                dropdownWrapper.trigger("hidden.bs.dropdown");
                return;
            }

            // ===========================================================
            // 3. LẤY DỮ LIỆU THÔNG MINH (LOGIC CỦA BẠN)
            // ===========================================================

            // Kiểm tra xem có đang tìm kiếm không?
            let searchInput = dropdownMenu.find("input[type='text']"); // Tìm trong menu (không phải wrapper)
            let isSearching =
                searchInput.val() && searchInput.val().trim() !== "";

            let allCheckboxes = container.find(".filter-checkbox");
            let totalCount = allCheckboxes.length; // Tổng số phần tử gốc
            let checkedCount = 0;
            let newValues = [];

            if (isSearching) {
                // [LOGIC SEARCH] CHỈ TÍNH NHỮNG CÁI ĐANG HIỆN (VISIBLE)
                let visibleCheckboxes = container.find(
                    ".filter-checkbox:visible:checked"
                );
                checkedCount = visibleCheckboxes.length;

                visibleCheckboxes.each(function () {
                    newValues.push(String($(this).val()));
                });
            } else {
                // [LOGIC THƯỜNG] LẤY TẤT CẢ (KỂ CẢ ẨN)
                let allChecked = container.find(".filter-checkbox:checked");
                checkedCount = allChecked.length;

                allChecked.each(function () {
                    newValues.push(String($(this).val()));
                });
            }

            // 4. QUYẾT ĐỊNH: RESET HAY FILTER?
            let isSelectAll = false;

            if (totalCount === 0) {
                isSelectAll = true;
            } else if (isSearching) {
                // Đang search -> Luôn là Lọc (trừ khi không chọn cái nào)
                if (checkedCount === 0) isSelectAll = true;
                else isSelectAll = false;
            } else {
                // Không search -> So sánh số lượng
                if (checkedCount === totalCount) {
                    isSelectAll = true;
                } else {
                    isSelectAll = false;
                }
            }

            // 5. KIỂM TRA THAY ĐỔI
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
                dropdownWrapper
                    .removeClass("open show")
                    .find(".dropdown-toggle")
                    .attr("aria-expanded", "false");
                dropdownWrapper.trigger("hidden.bs.dropdown"); // Trả về chỗ cũ
                return;
            }

            // 6. UPDATE & RELOAD
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

            // Xóa cache các cột khác
            let otherContainers = $(".dropdown-list-container").not(container);
            otherContainers.removeData("loaded");
            otherContainers.html(
                '<div class="text-center text-muted p-2">Loading...</div>'
            );

            // Đóng dropdown và trả về
            dropdownWrapper
                .removeClass("open show")
                .find(".dropdown-toggle")
                .attr("aria-expanded", "false");
            dropdownWrapper.trigger("hidden.bs.dropdown"); // QUAN TRỌNG NHẤT

            // Lưu và Reload
            localStorage.setItem(
                "accountant_filter_state",
                JSON.stringify(finalFilters)
            ); // Lưu ý key: params hay state tùy bạn thống nhất

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

        // 1. Tìm cái Menu đang chứa nút Close (đang nằm ở Body)
        let dropdownMenu = $(this).closest(".excel-dropdown");

        // 2. Tìm lại cái cha (Wrapper) đang nằm trong bảng thông qua ID đã lưu
        let parentId = dropdownMenu.attr("data-parent-id");
        let dropdownWrapper = $("#" + parentId);

        // Fallback: Nếu không tìm thấy ID (trường hợp chưa bị bứng đi), dùng cách cũ
        if (dropdownWrapper.length === 0) {
            dropdownWrapper = $(this).closest(".dropdown");
        }

        // 3. Thực hiện đóng
        // - Xóa class hiển thị của Bootstrap
        dropdownWrapper.removeClass("show open");
        dropdownWrapper.find(".dropdown-toggle").attr("aria-expanded", "false");

        // - [QUAN TRỌNG] Gọi sự kiện hidden.bs.dropdown
        // Lệnh này sẽ kích hoạt đoạn code "trả menu về bảng" mà ta đã viết ở bước trước.
        // Nếu thiếu dòng này, menu sẽ biến mất nhưng vẫn nằm lại ở Body -> Rác DOM.
        dropdownWrapper.trigger("hidden.bs.dropdown");
    });

    // 8. Hold Filter Dropdown
    let isDragging = false;
    let currentDropdown = null;
    let offsetX, offsetY;

    // 1. KHI NHẤN CHUỘT (MOUSEDOWN)
    $(document).on("mousedown", ".draggable-handle", function (e) {
        if (e.button !== 0) return; // Chỉ chuột trái
        e.preventDefault(); // Chống bôi đen

        currentDropdown = $(this).closest(".dropdown-menu");
        isDragging = true;

        // Tính khoảng cách từ con trỏ chuột đến góc trái trên của menu
        // Dùng getBoundingClientRect() vì nó chuẩn cho position: fixed
        let rect = currentDropdown[0].getBoundingClientRect();

        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;

        currentDropdown.css({
            cursor: "move",
        });
    });

    // 2. KHI DI CHUYỂN CHUỘT (MOUSEMOVE) - Gắn vào document
    $(document).on("mousemove", function (e) {
        if (isDragging && currentDropdown) {
            // Tính vị trí mới dựa trên toạ độ chuột hiện tại (e.clientX/Y)
            let newLeft = e.clientX - offsetX;
            let newTop = e.clientY - offsetY;

            // Cập nhật CSS
            currentDropdown.css({
                left: newLeft + "px",
                top: newTop + "px",
            });
        }
    });

    // 3. KHI NHẢ CHUỘT (MOUSEUP)
    $(document).on("mouseup", function () {
        if (isDragging && currentDropdown) {
            isDragging = false;
            currentDropdown.css({
                opacity: "1",
                cursor: "default",
            });
            currentDropdown = null;
        }
    });

    // 3. Infinite Scroll (Cuộn để tải thêm)
    $(".table-scroll").on("scroll", function () {
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
        $(".tbody-content").empty();
        resetAllColumnFilters();
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

    $(document).on('click', '.btn-export-excel', function(e) {
        e.preventDefault();
    
        // 1. Lấy dữ liệu Filter hiện tại từ LocalStorage
        let storageKey = 'accountant_filter_state';
        let rawParams = localStorage.getItem(storageKey);
        let paramsObj = {};
    
        if (rawParams) {
            paramsObj = JSON.parse(rawParams);
        }
    
        // 2. Bổ sung thêm Year/Type đang chọn trên giao diện (để chắc chắn đúng)
        paramsObj.year = $('#year-select').val();
        paramsObj.type = $('#type-select').val();
    
        // 3. Chuyển Object thành Query String (dạng ?year=2025&doctor=A...)
        // Dùng $.param của jQuery để tự động xử lý mảng
        let queryString = $.param(paramsObj);
    
        // 4. Tạo URL Export
        // Lưu ý: Thay '/accountant/export' bằng route thực tế của bạn
        let exportUrl = '/admin/export-excel?' + queryString;
    
        // 5. Mở tab mới để tải file (hoặc tải trực tiếp)
        window.location.href = exportUrl;
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
            initForceDateFormat();
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

function initForceDateFormat() {
    $(".force-date-format").each(function () {
        updateDateDisplay(this);
    });
}

function resetAllColumnFilters() {

    // -----------------------------------------------------------
    // PHẦN 1: XỬ LÝ DỮ LIỆU (LOCALSTORAGE)
    // -----------------------------------------------------------
    let storageKey = "accountant_filter_state"; // Key bạn đang dùng
    let currentParams = localStorage.getItem(storageKey);

    if (currentParams) {
        let paramsObj = JSON.parse(currentParams);
        let newParams = {};

        // Danh sách các Key "Bất tử" (Không được xóa)
        // Bạn kiểm tra xem trong code bạn đặt tên key là 'year' hay 'accountant_year' nhé
        const keysToKeep = [
            "year",
            "type",
        ];

        // Chỉ giữ lại những thằng nằm trong danh sách "Bất tử"
        keysToKeep.forEach((key) => {
            if (paramsObj.hasOwnProperty(key)) {
                newParams[key] = paramsObj[key];
            }
        });

        // Lưu lại object mới (đã loại bỏ các filter rác)
        localStorage.setItem(storageKey, JSON.stringify(newParams));
    }

    // -----------------------------------------------------------
    // PHẦN 2: XỬ LÝ GIAO DIỆN (UI)
    // -----------------------------------------------------------
    // Chỉ reset giao diện của các cột KHÔNG PHẢI là Year hoặc Type
    $(".dropdown-list-container").each(function () {
        let container = $(this);
        let fieldName = container.data("field"); // Lấy tên cột

        // [QUAN TRỌNG] Nếu cột này trùng tên với Year/Type thì BỎ QUA, không reset nó
        if (fieldName === "year" || fieldName === "type") {
            return; // Continue (Nhảy qua vòng lặp này)
        }

        // --- Tìm về cha (Logic hỗ trợ cả Mode Fixed Body) ---
        let dropdownMenu = container.closest(".excel-dropdown");
        let parentId = dropdownMenu.attr("data-parent-id");
        let dropdownWrapper;

        if (parentId) {
            dropdownWrapper = $("#" + parentId);
        } else {
            dropdownWrapper = container.closest(".dropdown");
        }

        let btnToggle = dropdownWrapper.find(".dropdown-toggle");

        // --- Reset về mặc định ---
        // 1. Xóa data đã chọn
        container.removeData("saved-values");

        // 2. Xóa cache đã load (để lần sau bấm vào nó tải list mới theo Năm mới)
        container.removeData("loaded");
        container.html(
            '<div class="text-center text-muted p-2">Loading...</div>'
        );

        // 3. Đổi icon về màu xám
        btnToggle.removeClass("btn-info");
        btnToggle
            .find(".selected-text")
            .html('<i class="fa-solid fa-filter"></i>');
    });
}

$(document).ready(function () {
    // Bắt sự kiện click vào phần tiêu đề
    $(".filter-title").on("click", function () {
        // 1. Tìm thẻ cha chứa nó (.filter-tiles)
        var $parentTile = $(this).closest(".filter-tiles");

        // 2. Tìm phần nội dung (.tile-content-wrapper) nằm ngay bên dưới tiêu đề và slide lên/xuống
        // stop() để ngăn việc animation bị lặp đi lặp lại nếu click liên tục
        $(this).next(".tile-content-wrapper").stop().slideToggle(300);

        // 3. Toggle class 'active' cho thẻ cha để xoay icon bằng CSS
        $parentTile.toggleClass("active");
    });
});

$(document).on("change", ".force-date-format", function () {
    updateDateDisplay(this);
});

// Popup thông báo
function successMsg(msg) {
    // Code hiển thị toast/popup của bạn
    alert(msg);
}
