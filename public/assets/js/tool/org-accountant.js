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
        currentFilters.type = $("#type-select").val();

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

        // F. Ngày TT (accountant_day_payment) là ngày khách thanh toán thật, chỉ đổi khi người dùng nhập.
        // Trước đây tính "Ngày HĐ + THCN" nhưng ô Ngày HĐ là ô chọn ngày (Y-m-d) nên tách theo "/" không bao giờ
        // ra kết quả, chỉ còn tác dụng phụ xóa Ngày TT khi Ngày HĐ trống. Đã bỏ (máy chủ cũng không tính nữa).
    }

    // --- HÀM 3: LƯU SERVER (SYNC) ---
    // Mỗi dòng lưu riêng (trước đây dùng chung một bộ đếm cho cả bảng nên sửa nhanh 2 dòng có thể mất lần lưu của dòng trước).
    // Chỉ gửi các ô ĐÃ ĐỔI so với lúc mở, kèm giá trị cũ để máy chủ phát hiện người khác vừa sửa cùng ô (không ghi đè).
    // Lưu lỗi (mất mạng, hết phiên đăng nhập, lỗi dữ liệu) thì dòng viền đỏ + báo rõ + nút "Lưu lại".
    const saveTimers = new Map();
    const unsavedRows = new Map(); // id dòng -> lý do lỗi gần nhất

    const rowValues = (row) => {
        const out = {};
        row.find("input, select").serializeArray().forEach((i) => (out[i.name] = i.value));
        return out;
    };
    const ensureOriginal = (row) => {
        if (row.data("originalValues") === undefined) {
            row.data("originalValues", rowValues(row));
        }
    };

    // Thanh nổi "N dòng chưa lưu · Lưu lại" + toast báo lỗi
    const saveBar = $(
        '<div class="acc-savebar" hidden><span class="acc-savebar__text"></span>' +
            '<button type="button" class="acc-savebar__btn">Lưu lại</button></div>'
    ).appendTo("body");
    const toastBox = $('<div class="acc-toast" role="status" aria-live="polite" hidden><div class="acc-toast__body"></div><button type="button" class="acc-toast__close" aria-label="Đóng">×</button></div>').appendTo("body");
    let toastTimer = null;
    // msg: chuỗi, hoặc mảng dòng (dòng đầu là tóm tắt, các dòng sau là chi tiết từng đơn lỗi)
    function accToast(msg, type) {
        const lines = Array.isArray(msg) ? msg : [msg];
        const box = toastBox.find(".acc-toast__body").empty();
        lines.forEach((l, i) => $(i ? "<div class='acc-toast__item'>" : "<div class='acc-toast__main'>").text(l).appendTo(box));
        toastBox
            .removeClass("is-ok is-bad is-warn")
            .addClass(type === "ok" ? "is-ok" : type === "warn" ? "is-warn" : "is-bad")
            .prop("hidden", false);
        clearTimeout(toastTimer);
        // Báo lỗi để lâu hơn (đọc kịp lý do); bấm × để đóng
        toastTimer = setTimeout(() => toastBox.prop("hidden", true), type === "ok" ? 2500 : type === "warn" ? 8000 : 15000);
    }
    toastBox.on("click", ".acc-toast__close", () => {
        clearTimeout(toastTimer);
        toastBox.prop("hidden", true);
    });
    function refreshSaveBar() {
        const n = unsavedRows.size;
        saveBar.prop("hidden", n === 0);
        saveBar.find(".acc-savebar__text").text(n + " dòng chưa lưu được");
    }
    saveBar.on("click", ".acc-savebar__btn", async function () {
        // Lấy mã bảo mật mới trước (trường hợp vừa đăng nhập lại ở tab khác)
        try { await refreshCsrf(); } catch (e) {}
        unsavedRows.forEach((_, id) => {
            const row = $('tr[data-id="' + id + '"]');
            if (row.length) enqueueSave(row);
            else unsavedRows.delete(id);
        });
        refreshSaveBar();
    });
    // Còn dòng chưa lưu / đang lưu mà đóng trang: hỏi lại
    window.addEventListener("beforeunload", function (e) {
        if (unsavedRows.size || saveTimers.size || saveQueue.length || queueBusy) {
            e.preventDefault();
            e.returnValue = "";
        }
    });

    function markRow(row, state) {
        row.removeClass("acc-row-saving acc-row-saved acc-row-unsaved");
        if (state) row.addClass("acc-row-" + state);
        if (state === "saved") setTimeout(() => row.removeClass("acc-row-saved"), 1200);
    }

    function failRow(row, msg, quiet) {
        unsavedRows.set(String(row.data("id")), msg);
        markRow(row, "unsaved");
        row.attr("title", msg);
        refreshSaveBar();
        if (!quiet) accToast(msg, "bad");
    }

    // ---- Hàng đợi lưu: mỗi lúc chỉ lưu MỘT dòng, lưu xong dòng trước mới tới dòng sau.
    // Trong lúc đang lưu, khóa cả bảng (không gõ / chọn được) để không có dòng nào lưu chen hay đè dòng khác.
    const saveQueue = [];
    let queueBusy = false;
    const lockPill = $('<div class="acc-lockpill" hidden>Đang lưu…</div>').appendTo("body");
    function lockTable(on) {
        const body = $(".tbody-content");
        body.toggleClass("acc-locked", on);
        lockPill.prop("hidden", !on);
        body.find("input").each(function () {
            const el = $(this);
            if (on) {
                if (!el.prop("readOnly")) el.prop("readOnly", true).attr("data-acc-lock", "1");
            } else if (el.attr("data-acc-lock")) {
                el.prop("readOnly", false).removeAttr("data-acc-lock");
            }
        });
    }
    // Đang khóa: chặn đổi ô chọn bằng bàn phím / chuột
    $(document).on("keydown mousedown", ".tbody-content.acc-locked select", function (e) {
        e.preventDefault();
    });
    function enqueueSave(row) {
        enqueueMany([row]);
    }
    // Nhiều dòng cùng lúc (dán từ Excel, xóa cả vùng): xếp hết vào hàng đợi rồi mới lưu, để lưu chung một lần
    function enqueueMany(rows) {
        rows.forEach((row) => {
            const id = String(row.data("id"));
            if (!saveQueue.some((r) => String(r.data("id")) === id)) saveQueue.push(row);
        });
        processQueue();
    }
    const canBatch = typeof url_update_rows !== "undefined";
    const BATCH_SIZE = 200;
    function processQueue() {
        if (queueBusy) return;
        if (!saveQueue.length) {
            lockTable(false);
            return;
        }
        queueBusy = true;
        lockTable(true);
        const next = () => {
            queueBusy = false;
            processQueue();
        };
        if (canBatch && saveQueue.length > 1) {
            doSaveBatch(saveQueue.splice(0, BATCH_SIZE), next);
        } else {
            doSaveRow(saveQueue.shift(), next);
        }
    }

    // Lấy mã bảo mật (CSRF) mới của phiên hiện tại. Hết phiên thì đăng nhập lại ở tab khác,
    // rồi trang này lấy mã mới để lưu tiếp, không cần tải lại trang (không mất số đang nhập).
    function refreshCsrf() {
        return $.ajax({ url: typeof url_csrf_token !== "undefined" ? url_csrf_token : "/csrf-token", method: "GET", cache: false, headers: { Accept: "application/json" } }).then((res) => {
            if (res && res.token) $('meta[name="csrf-token"]').attr("content", res.token);
            return res || {};
        });
    }

    function doSaveRow(row, done, retried) {
        done = done || function () {};
        const rowId = row.data("id");
        ensureOriginal(row);
        const original = row.data("originalValues");
        const current = rowValues(row);
        const changed = Object.keys(current).filter((k) => current[k] !== original[k]);
        if (!changed.length) {
            unsavedRows.delete(String(rowId));
            refreshSaveBar();
            markRow(row, null);
            done();
            return;
        }

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

        const edited = Object.keys(row.data("edited") || {});
        markRow(row, "saving");

        $.ajax({
            url: url_update_accountant,
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            },
            data: {
                id: rowId,
                row_data: changed.map((k) => ({ name: k, value: current[k] })),
                original: changed.map((k) => ({ name: k, value: original[k] })),
                edited: edited,
                // client=2: máy chủ tự tính lại các ô liên kết từ số mới nhất trong database
                client: 2,
                filter_data: filterArray,
            },
            success: function (res) {
                applyRowResult(row, res, original, current, changed);
                if (res && res.totals) updateTotalsUI(res.totals);
            },
            error: function (xhr) {
                const code = xhr.status;
                // Hết phiên / mã bảo mật cũ: lấy mã mới rồi thử lại một lần
                if ((code === 419 || code === 401) && !retried) {
                    retryAfterCsrf(() => doSaveRow(row, done, true), [row], done);
                    xhr.__accRetrying = true;
                    return;
                }
                console.log("Lỗi update:", xhr.responseText);
                const serverMsg = xhr.responseJSON && xhr.responseJSON.message;
                failRow(row, serverMsg && code !== 419 ? serverMsg : "Chưa lưu được đơn #" + orderNo(row) + ": " + httpErrorText(code) + ".");
            },
            complete: function (xhr) {
                // Đang thử lại sau khi lấy mã bảo mật mới thì lượt lưu mới sẽ tự báo xong
                if (!xhr.__accRetrying) done();
            },
        });
    }

    const orderNo = (row) => row.find(".order_id").text().trim() || row.data("id");
    const SESSION_MSG = "phiên đăng nhập đã hết hạn. Mở trang đăng nhập ở tab khác, đăng nhập lại, rồi quay lại bấm \"Lưu lại\" (không cần tải lại trang này)";
    function httpErrorText(code) {
        return code === 419 || code === 401
            ? SESSION_MSG
            : code === 0
            ? "mất kết nối mạng, kiểm tra mạng rồi bấm Lưu lại"
            : "lỗi máy chủ (" + code + "), bấm Lưu lại để thử lần nữa";
    }
    // Lấy mã bảo mật mới rồi chạy lại lượt lưu; đã bị đăng xuất thật thì đánh dấu các dòng chưa lưu
    function retryAfterCsrf(retry, rows, done) {
        refreshCsrf()
            .then((res) => {
                if (res.auth) {
                    rows.forEach((r) => markRow(r, null));
                    retry();
                } else {
                    rows.forEach((r) => failRow(r, "Chưa lưu được đơn #" + orderNo(r) + ": " + SESSION_MSG + "."));
                    done();
                }
            })
            .catch(() => {
                rows.forEach((r) => failRow(r, "Chưa lưu được đơn #" + orderNo(r) + ": mất kết nối, bấm Lưu lại để thử lần nữa."));
                done();
            });
    }

    // Các ô đã đổi của một dòng so với lúc mở (null = không có gì để lưu)
    function rowPayload(row) {
        ensureOriginal(row);
        const original = row.data("originalValues");
        const current = rowValues(row);
        const changed = Object.keys(current).filter((k) => current[k] !== original[k]);
        if (!changed.length) return null;
        return {
            original,
            current,
            changed,
            data: {
                id: row.data("id"),
                row_data: changed.map((k) => ({ name: k, value: current[k] })),
                original: changed.map((k) => ({ name: k, value: original[k] })),
                edited: Object.keys(row.data("edited") || {}),
            },
        };
    }

    // Lưu nhiều dòng trong MỘT lần gửi (trang Kế toán). Máy chủ lưu và kiểm tra từng dòng riêng,
    // trả kết quả theo từng dòng; dòng nào lỗi / bị người khác sửa thì chỉ dòng đó bị đánh dấu.
    function doSaveBatch(rows, done, retried) {
        const items = [];
        rows.forEach((row) => {
            const p = rowPayload(row);
            if (!p) {
                unsavedRows.delete(String(row.data("id")));
                markRow(row, null);
                return;
            }
            markRow(row, "saving");
            items.push({ row, p });
        });
        refreshSaveBar();
        if (!items.length) {
            done();
            return;
        }
        $.ajax({
            url: url_update_rows,
            method: "PATCH",
            contentType: "application/json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), Accept: "application/json" },
            data: JSON.stringify({ rows: items.map((i) => i.p.data), client: 2, filter_data: currentFilterArray() }),
            success: function (res) {
                if (!res || !res.results) {
                    items.forEach((i) => failRow(i.row, res && res.message ? res.message : "Chưa lưu được đơn #" + orderNo(i.row) + ": " + SESSION_MSG + "."));
                    return;
                }
                let ok = 0, conflict = 0, bad = 0;
                const details = [];
                items.forEach((i) => {
                    const r = res.results[String(i.row.data("id"))];
                    if (!r) {
                        bad++;
                        failRow(i.row, "Chưa lưu được đơn #" + orderNo(i.row) + ", bấm Lưu lại để thử lần nữa.");
                        return;
                    }
                    applyRowResult(i.row, r, i.p.original, i.p.current, i.p.changed, true);
                    if (r.success) ok++;
                    else if (r.conflict) { conflict++; details.push("Đơn #" + orderNo(i.row) + ": người khác vừa sửa, đã hiện lại số mới nhất, nhập lại nếu cần."); }
                    else { bad++; details.push(r.message || "Đơn #" + orderNo(i.row) + ": chưa lưu được."); }
                });
                if (res.totals) updateTotalsUI(res.totals);
                const parts = ["Đã lưu " + ok + "/" + items.length + " dòng"];
                if (conflict) parts.push(conflict + " dòng vừa bị người khác sửa (đã hiện lại số mới)");
                if (bad) parts.push(bad + " dòng chưa lưu được (viền đỏ, bấm Lưu lại)");
                // Ghi chú của lần dán / xóa (ô bị khóa, ô sai định dạng) để người dùng vẫn thấy sau khi lưu xong
                if (window.accPasteNote) {
                    parts.push(window.accPasteNote);
                    window.accPasteNote = "";
                }
                // Chi tiết từng đơn lỗi (tối đa 4 dòng; còn lại rê chuột vào dòng viền đỏ để xem)
                const shown = details.slice(0, 4);
                if (details.length > 4) shown.push("… và " + (details.length - 4) + " đơn khác (rê chuột vào dòng viền đỏ để xem lý do)");
                accToast([parts.join(" · ")].concat(shown), bad ? "bad" : conflict || parts.length > 1 ? "warn" : "ok");
            },
            error: function (xhr) {
                const code = xhr.status;
                if ((code === 419 || code === 401) && !retried) {
                    retryAfterCsrf(() => doSaveBatch(rows, done, true), items.map((i) => i.row), done);
                    xhr.__accRetrying = true;
                    return;
                }
                console.log("Lỗi lưu nhiều dòng:", xhr.responseText);
                const msg = (xhr.responseJSON && xhr.responseJSON.message) || httpErrorText(code);
                items.forEach((i) => failRow(i.row, "Chưa lưu được " + items.length + " dòng: " + msg + "."));
            },
            complete: function (xhr) {
                if (!xhr.__accRetrying) done();
            },
        });
    }

    function currentFilterArray() {
        const out = [];
        try {
            const obj = JSON.parse(localStorage.getItem("accountant_filter_state") || "{}");
            Object.keys(obj).forEach((key) => out.push({ name: key, value: obj[key] }));
        } catch (e) {}
        return out;
    }

    // Áp kết quả lưu của một dòng (dùng chung cho lưu từng dòng và lưu nhiều dòng)
    function applyRowResult(row, res, original, current, changed, quiet) {
        const rowId = row.data("id");
                if (res && res.success) {
                    // Đã lưu: các giá trị vừa gửi thành "giá trị gốc" mới
                    changed.forEach((k) => (original[k] = current[k]));
                    // Ô liên kết (Thành tiền, CK, Lợi nhuận, Còn nợ, Ngày TT, Trạng thái TT) theo số máy chủ vừa tính
                    if (res.computed) {
                        Object.keys(res.computed).forEach((k) => {
                            const input = row.find('[name="' + k + '"]');
                            if (!input.length) return;
                            const val = String(res.computed[k] ?? "");
                            // Người dùng đang gõ tiếp ô này thì không ghi đè (lượt lưu sau sẽ cập nhật)
                            if (input.is(":focus") && input.val() !== original[k]) return;
                            input.val(val);
                            original[k] = val;
                            // Số máy chủ tính lại không tính là thao tác của người dùng (Ctrl+Z không trả về số cũ)
                            const undoSnap = row.data("accUndoBefore");
                            if (undoSnap && undoSnap[k] !== undefined) undoSnap[k] = val;
                            if (k === "accountant_status") {
                                input.toggleClass("acc-status-paid", val === "1").toggleClass("acc-status-unpaid", val !== "1");
                            }
                        });
                    }
                    row.data("edited", {});
                    unsavedRows.delete(String(rowId));
                    row.removeAttr("title");
                    // Kế toán tự sửa số ca / đơn giá / thành tiền = máy chủ đã bỏ cảnh báo "số ca đổi sau khi cập nhật doanh thu"
                    if (changed.some((k) => ["order_quantity", "order_price", "order_cost"].includes(k)) && row.find(".acc-qtyflag").length) {
                        row.find(".acc-qtyflag").remove();
                        if (window.accSetQtyCount && window.accQtyCountNow) window.accSetQtyCount(Math.max(0, window.accQtyCountNow() - 1));
                    }
                    refreshSaveBar();
                    markRow(row, "saved");
                    // Máy chủ chỉ chuyển "Đã cập nhật doanh thu" khi sửa ô doanh thu; trang cũ thì luôn đổi như trước
                    if (res.order_status === undefined || Number(res.order_status) === 4) {
                        row.find(".status-id").html(
                            '<span class="badge badge-primary text-white" style="background-color: #00d0e3">Đã cập nhật doanh thu</span>'
                        );
                    }
                    $(".dropdown-list-container").removeData("loaded");
                    $(".filter-box").removeData("loaded");
                } else if (res && res.conflict) {
                    // Người khác vừa sửa: hiện lại dữ liệu mới nhất, không ghi đè
                    (res.fresh || []).forEach((f) => {
                        row.find('[name="' + f.name + '"]').val(f.value);
                        original[f.name] = f.value;
                    });
                    row.data("edited", {});
                    unsavedRows.delete(String(rowId));
                    refreshSaveBar();
                    markRow(row, "unsaved");
                    setTimeout(() => markRow(row, null), 4000);
                    if (!quiet) accToast(res.message, "warn");
                } else if (res && res.message) {
                    failRow(row, res.message, quiet);
                } else {
                    // Trả về trang HTML (thường là trang đăng nhập): phiên đã hết hạn
                    failRow(row, "Chưa lưu được đơn #" + orderNo(row) + ": " + SESSION_MSG + ".", quiet);
                }
    }

    let lastEditedRow = null;
    function saveRowToServer(row) {
        const id = String(row.data("id"));
        lastEditedRow = row;
        clearTimeout(saveTimers.get(id));
        saveTimers.set(
            id,
            setTimeout(() => {
                saveTimers.delete(id);
                enqueueSave(row);
            }, 400)
        );
    }
    // Chuyển sang dòng khác khi dòng trước còn đang chờ lưu: lưu dòng trước ngay
    function flushRow(row) {
        const id = String(row.data("id"));
        if (saveTimers.has(id)) {
            clearTimeout(saveTimers.get(id));
            saveTimers.delete(id);
            enqueueSave(row);
        }
    }

    // Cho phần tải lại bảng (lọc, đổi năm) biết còn dòng chưa lưu / đang chờ lưu
    window.accPendingSaves = () => unsavedRows.size + saveTimers.size + saveQueue.length + (queueBusy ? 1 : 0);
    window.accToast = accToast;

    // Ghi lại giá trị gốc ngay khi bắt đầu sửa một dòng (các dòng được tải thêm bằng AJAX cũng có)
    $(document).on("focusin", ".tbody-content tr[data-id] input, .tbody-content tr[data-id] select", function () {
        const row = $(this).closest("tr");
        if (lastEditedRow && lastEditedRow[0] !== row[0]) flushRow(lastEditedRow);
        ensureOriginal(row);
    });

    // --- SỰ KIỆN CHÍNH ---
    $(document).on(
        "input change",
        ".input-accountant, .calc-inputs, .selectbox-accountant",
        function () {
            const row = $(this).closest("tr");
            if (row.hasClass("row-locked")) {
                return false;
            }
            applyCellEdit($(this));
            saveRowToServer(row);
        }
    );

    // Ghi nhận một ô vừa đổi: tự chọn trạng thái thanh toán, đánh dấu ô người dùng sửa, tính lại các ô liên kết.
    // Dùng cho nhập tay lẫn dán / xóa nhiều ô (khi đó lưu chung một lần).
    function applyCellEdit(input) {
            let row = input.closest("tr");

            let rawName = input.attr("name");
            let sourceName = rawName;
            if (rawName.includes("_")) {
                let parts = rawName.split("_");
                if (!isNaN(parts[parts.length - 1])) {
                    parts.pop();
                    sourceName = parts.join("_");
                }
            }

            // Trạng thái thanh toán:
            // - Người dùng tự chọn ở ô Trạng thái thì giữ đúng lựa chọn đó (trước đây bị ép theo Ngày TT + Hình thức nên tự nhảy về).
            // - Sửa Ngày TT / Hình thức: đủ cả hai thì tự chuyển "Đã thanh toán"; xóa một trong hai thì về "Chưa thanh toán".
            if (['accountant_day_payment', 'accountant_method', 'accountant_status'].includes(sourceName)) {
                let statusSelect = row.find('.accountant_status');
                if (sourceName !== 'accountant_status') {
                    let dayPayment = (row.find('.accountant_day_payment').val() || "").trim();
                    let method = (row.find('.accountant_method').val() || "").trim();
                    statusSelect.val(dayPayment !== '' && method !== '' ? '1' : '0');
                }
                const paid = statusSelect.val() === '1';
                statusSelect.toggleClass('acc-status-paid', paid).toggleClass('acc-status-unpaid', !paid);
                if (paid) {
                    row.removeClass('debt-overrated debt-warning');
                } else {
                    let originalDebtClass = row.attr('data-debt-class');
                    if (originalDebtClass) row.addClass(originalDebtClass);
                }
            }

            // Ghi nhận ô người dùng tự sửa (để máy chủ kiểm tra trùng sửa đúng ô này)
            ensureOriginal(row);
            const edited = row.data("edited") || {};
            edited[rawName] = true;
            row.data("edited", edited);

            calculateRow(row, sourceName);
    }
    window.accApplyCellEdit = applyCellEdit;
    // Dòng còn số chưa lưu xong (đang chờ, trong hàng đợi, đang lưu, lưu lỗi) hoặc bảng đang lưu
    window.accRowPending = (row) => {
        const id = String(row.data("id"));
        return saveTimers.has(id) || saveQueue.some((r) => String(r.data("id")) === id) || row.hasClass("acc-row-saving") || unsavedRows.has(id) || queueBusy;
    };
    window.accEnsureOriginal = ensureOriginal;
    window.accRowValues = rowValues;
    window.accEnqueueMany = enqueueMany;
    window.accIsSaving = () => $(".tbody-content").hasClass("acc-locked");

    // Nút hoàn thành (chốt đơn): không làm lại được, nên
    // - chặn khi dòng còn số chưa lưu xong (đang chờ lưu, đang nằm trong hàng đợi, đang lưu, lưu lỗi) hoặc bảng đang lưu:
    //   chốt lúc đó thì đơn bị chốt theo số CŨ và số vừa nhập bị máy chủ từ chối (mất dữ liệu)
    // - hỏi lại trước khi chốt
    $(document).on("click", ".btn-complete-account", async function (e) {
        e.preventDefault();
        let btn = $(this);
        let order_id = btn.data("id");
        const row = btn.closest("tr");
        const id = String(row.data("id"));
        if (btn.prop("disabled")) return;

        const inQueue = saveQueue.some((r) => String(r.data("id")) === id);
        if (saveTimers.has(id) || inQueue || row.hasClass("acc-row-saving") || unsavedRows.has(id)) {
            flushRow(row);
            accToast(
                unsavedRows.has(id)
                    ? "Đơn #" + order_id + " còn số chưa lưu được (viền đỏ). Bấm \"Lưu lại\" cho lưu xong rồi mới Hoàn thành."
                    : "Đơn #" + order_id + " còn số vừa nhập đang lưu. Đợi lưu xong (viền xanh) rồi bấm Hoàn thành lại.",
                "warn"
            );
            return;
        }
        if (queueBusy) {
            accToast("Đang lưu các dòng khác, đợi lưu xong rồi bấm Hoàn thành lại.", "warn");
            return;
        }

        const flag = row.find(".acc-qtyflag");
        const msg = (flag.length
            ? "Lưu ý: số ca đã đổi " + flag.data("from") + " → " + flag.data("to") + " sau khi cập nhật doanh thu nhưng tiền vẫn tính theo " + flag.data("from") + " ca (chưa bấm Tính lại / Giữ tiền cũ). "
            : "") + "Hoàn thành đơn #" + order_id + "? Doanh thu của đơn được cộng vào thống kê và dòng này bị khóa, không sửa được nữa.";
        const ok = window.uiConfirm ? await window.uiConfirm(msg, { title: flag.length ? "Số ca chưa khớp tiền" : "Hoàn thành đơn", ok: flag.length ? "Vẫn hoàn thành" : "Hoàn thành", cancel: "Hủy", danger: !!flag.length }) : window.confirm(msg);
        if (!ok) return;
        // Trong lúc hỏi, người dùng có thể đã sửa tiếp dòng này
        if (saveTimers.has(id) || saveQueue.some((r) => String(r.data("id")) === id) || row.hasClass("acc-row-saving") || unsavedRows.has(id) || queueBusy) {
            flushRow(row);
            accToast("Đơn #" + order_id + " vừa có số mới đang lưu. Đợi lưu xong rồi bấm Hoàn thành lại.", "warn");
            return;
        }

        const lockRow = () => {
            row.addClass("row-locked");
            row.find("input, select").prop("disabled", true).trigger("blur");
            row.css("background-color", "#f8f9fa");
            btn.replaceWith('<button class="btn btn-secondary" disabled><i class="fa fa-lock"></i> Đã hoàn thành</button>');
            $(".status_id_" + order_id).html(
                '<span class="badge badge-primary text-white" style="background-color: #007bff">Đã xử lý</span>'
            );
            $(".dropdown-list-container").removeData("loaded");
            $(window).trigger("scroll"); // cập nhật nút kéo điền (dòng đã khóa)
        };

        $.ajax({
            url: url_complete_accountant,
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            },
            data: { order_id: order_id },
            beforeSend: function () {
                btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Đang xử lý...');
            },
            success: function (data) {
                if (data && data.success) {
                    lockRow();
                    accToast("Đã hoàn thành đơn #" + order_id + ".", "ok");
                } else if (data && data.already) {
                    // Đã có người chốt trước (tab khác): khóa dòng cho đúng thực tế
                    lockRow();
                    accToast(data.message, "warn");
                } else {
                    accToast((data && data.message) || "Chưa hoàn thành được đơn #" + order_id + ", thử lại.", "bad");
                    btn.prop("disabled", false).html('<i class="fa fa-check-circle"></i> Hoàn thành');
                }
            },
            error: function (xhr) {
                console.log(xhr);
                const code = xhr.status;
                accToast("Chưa hoàn thành được đơn #" + order_id + ": " +
                    (code === 419 || code === 401 ? "phiên đăng nhập đã hết hạn, đăng nhập lại ở tab khác rồi bấm lại" : code === 0 ? "mất kết nối mạng" : "lỗi máy chủ (" + code + ")") + ".", "bad");
                btn.prop("disabled", false).html('<i class="fa fa-check-circle"></i> Hoàn thành');
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
        // Lọc đơn phụ thu (trang Công nợ / Công nợ Sales)
        if ($('#surcharge-select').length) paramsObj.surcharge = $('#surcharge-select').val();
    
        // 3. Chuyển Object thành Query String (dạng ?year=2025&doctor=A...)
        // Dùng $.param của jQuery để tự động xử lý mảng
        let queryString = $.param(paramsObj);
    
        // 4. Tạo URL Export
        // Lưu ý: Thay '/accountant/export' bằng route thực tế của bạn
        let exportUrl = '/admin/export-excel?' + queryString;
    
        // 5. Mở tab mới để tải file (hoặc tải trực tiếp)
        window.location.href = exportUrl;
    });

    $(document).on('click', '.btn-export-misa', function(e) {
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
        // Lọc đơn phụ thu (trang Công nợ / Công nợ Sales)
        if ($('#surcharge-select').length) paramsObj.surcharge = $('#surcharge-select').val();
    
        // 3. Chuyển Object thành Query String (dạng ?year=2025&doctor=A...)
        // Dùng $.param của jQuery để tự động xử lý mảng
        let queryString = $.param(paramsObj);
    
        // 4. Tạo URL Export
        // Lưu ý: Thay '/accountant/export' bằng route thực tế của bạn
        let exportUrl = '/admin/export-misa?' + queryString;
    
        // 5. Mở tab mới để tải file (hoặc tải trực tiếp)
        window.location.href = exportUrl;
    });
}); // End Document Ready

/**
 * CÁC HÀM AJAX CHÍNH
 */

function getListAccountant(isAppend = false) {
    if (!nextPageUrl && isAppend) return;
    // Tải lại cả bảng (lọc / đổi năm) khi còn dòng chưa lưu được thì các ô vừa nhập sẽ mất: dừng lại, báo người dùng
    if (!isAppend && window.accPendingSaves && window.accPendingSaves() > 0) {
        if (window.accToast) window.accToast("Còn dòng đang lưu hoặc chưa lưu được. Bấm \"Lưu lại\" (góc dưới) rồi hãy lọc / tải lại bảng.", "warn");
        return;
    }

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
            if (data.qty_changed_count !== undefined && window.accSetQtyCount) window.accSetQtyCount(data.qty_changed_count);

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
    $("#total-rows").text(formatCurrency(data.totalRows));
    $("#total-price").text(formatCurrency(data.totalPrice));
    $("#total-owe").text(formatCurrency(data.totalOwe));
    $("#total-amount-paid").text(formatCurrency(data.totalAmountPaid));
    $("#total-quantity").text(formatCurrency(data.totalQuantity));
    $("#total-quantity-draft").text(formatCurrency(data.totalQuantityDraft));
    $("#total-driver-assistance").text(formatCurrency(data.totalDriverAssistance));
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
    if (window.accQtyChangedOnly) data.qty_changed = 1;
    if (window.accTodo) data.todo = window.accTodo;

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

// Khung chat trợ lý nổi lấy bộ lọc đang chọn (để trợ lý đối chiếu giá hợp đồng đúng các đơn đang hiện)
window.assistantPageFilters = getValuesFilterObj;

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


/* ==========================================================================
 * BẢNG KIỂU EXCEL (chỉ trang công nợ Kế toán — trang có biến url_update_rows)
 * - Phím: ↑ ↓ (và Enter / Shift+Enter) đi lên xuống cùng cột; ← → sang ô bên cạnh khi con trỏ ở đầu / cuối chữ;
 *   Esc trả ô về giá trị lúc vừa vào ô.
 * - Chọn vùng: kéo chuột qua nhiều ô, hoặc Shift + bấm chuột / Shift + phím mũi tên. Delete xóa cả vùng,
 *   Ctrl+C chép vùng (dán được sang Excel).
 * - Dán từ Excel: dán một vùng nhiều ô vào ô đang chọn (đổ xuống / sang phải theo đúng vị trí cột);
 *   dán MỘT giá trị khi đang chọn vùng thì điền giá trị đó cho cả vùng.
 * - Mọi dòng bị đổi được lưu chung MỘT lần (accEnqueueMany → lưu nhiều dòng), máy chủ vẫn tính lại và kiểm tra từng dòng.
 * ========================================================================== */
(function ($) {
    if (typeof url_update_rows === "undefined") return;

    const EDITABLE = ".input-accountant, .calc-inputs, .selectbox-accountant";
    const MONEY = ["order_quantity", "order_cost", "order_price", "accountant_amount_paid", "accountant_owe", "order_discount", "order_profit"];
    // Ô người dùng dán thẳng vào thì giữ đúng số dán (không để ô khác tính đè)
    const KEEP_PASTED = ["order_price", "order_discount", "accountant_status"];

    const body = () => $(".tbody-content");
    const rows = () => body().children("tr[data-id]");
    // Tắt gợi ý của trình duyệt (danh sách các số đã nhập trước đây, tự điền) vì che mất ô, nút kéo điền, bảng chọn cột.
    // Chrome chỉ bỏ danh sách đã nhập khi autocomplete="off" (mã lạ như "acc-no-fill" thì vẫn hiện).
    const noFill = (el) => {
        if (el.getAttribute("autocomplete") !== "off") el.setAttribute("autocomplete", "off");
        el.setAttribute("aria-autocomplete", "none");
    };
    $(".acc-page input").each(function () { noFill(this); });
    $(document).on("focusin", ".acc-page input", function () { noFill(this); });
    const cellEl = (td) => $(td).find(EDITABLE).first();
    // Cột đang ẩn (nút "Cột" / chuột phải vào tiêu đề) thì bỏ qua khi di chuyển, dán, xóa, chép
    const colVisible = (c) => {
        const th = $("#table-scroll thead tr.section-title > th").eq(c);
        return !th.length || th.css("display") !== "none";
    };
    const isEditable = (el) => el && el.length && !el.prop("disabled") && !el.closest("tr").hasClass("row-locked") && colVisible(posOf(el).c);
    const posOf = (el) => {
        const td = el.closest("td");
        const tr = td.closest("tr");
        return { r: rows().index(tr), c: tr.children("td").index(td) };
    };
    const elAt = (r, c) => {
        const tr = rows().eq(r);
        if (!tr.length) return null;
        const el = cellEl(tr.children("td").eq(c));
        return el.length ? el : null;
    };

    // ---------- Vùng chọn ----------
    let anchor = null; // {r,c}
    let focusPos = null; // {r,c}
    let dragging = false;

    function rangeBox() {
        if (!anchor || !focusPos) return null;
        return {
            r1: Math.min(anchor.r, focusPos.r), r2: Math.max(anchor.r, focusPos.r),
            c1: Math.min(anchor.c, focusPos.c), c2: Math.max(anchor.c, focusPos.c),
        };
    }
    function rangeSize() {
        const b = rangeBox();
        return b ? (b.r2 - b.r1 + 1) * (b.c2 - b.c1 + 1) : 0;
    }
    function paintRange() {
        body().find("td.acc-sel").removeClass("acc-sel acc-sel-t acc-sel-b acc-sel-l acc-sel-r");
        scheduleHandle();
        const b = rangeBox();
        if (!b || rangeSize() < 2) return;
        const all = rows();
        for (let r = b.r1; r <= b.r2; r++) {
            const tds = all.eq(r).children("td");
            for (let c = b.c1; c <= b.c2; c++) {
                const td = tds.eq(c);
                td.addClass("acc-sel");
                if (r === b.r1) td.addClass("acc-sel-t");
                if (r === b.r2) td.addClass("acc-sel-b");
                if (c === b.c1) td.addClass("acc-sel-l");
                if (c === b.c2) td.addClass("acc-sel-r");
            }
        }
    }
    function clearRange() {
        anchor = focusPos ? { ...focusPos } : null;
        paintRange();
    }
    // Các ô sửa được trong vùng (theo thứ tự dòng, cột)
    function rangeCells() {
        const b = rangeBox();
        const out = [];
        if (!b) return out;
        for (let r = b.r1; r <= b.r2; r++) {
            for (let c = b.c1; c <= b.c2; c++) {
                if (!colVisible(c)) continue;
                const el = elAt(r, c);
                if (el) out.push({ r, c, el });
            }
        }
        return out;
    }

    function focusCell(el) {
        el.trigger("focus");
        if (el.is("input[type=text]")) {
            try { el[0].select(); } catch (e) {}
        }
        // Cuộn cho ô hiện trong khung bảng, kể cả khi bị các cột cố định bên trái che mất
        try {
            // Cuộn cho thấy TRỌN ô (cả góc dưới phải có nút kéo điền), không chỉ ô nhập bên trong
            const td = el.closest("td")[0];
            td.scrollIntoView({ block: "nearest", inline: "nearest" });
            let sc = td.parentElement;
            while (sc && sc !== document.body && sc.scrollWidth <= sc.clientWidth) sc = sc.parentElement;
            if (sc && sc !== document.body) {
                let stickyRight = window.accFrozenRight ? window.accFrozenRight() : 0;
                if (stickyRight === null) {
                    stickyRight = 0;
                    $(td).siblings("td.sticky-col").each(function () {
                        stickyRight = Math.max(stickyRight, this.getBoundingClientRect().right);
                    });
                }
                const left = td.getBoundingClientRect().left;
                if (stickyRight && left < stickyRight) sc.scrollLeft -= stickyRight - left + 4;
            }
        } catch (e) {}
    }

    // Tìm ô sửa được kế tiếp theo hướng (bỏ qua dòng đã chốt / cột không sửa được)
    function step(pos, dr, dc) {
        let { r, c } = pos;
        const maxR = rows().length - 1;
        const maxC = rows().eq(r).children("td").length - 1;
        for (let i = 0; i < 400; i++) {
            r += dr;
            c += dc;
            if (r < 0 || r > maxR || c < 0 || c > maxC) return null;
            const el = elAt(r, c);
            if (isEditable(el)) return { r, c, el };
        }
        return null;
    }
    // Shift + mũi tên: mở rộng vùng theo từng ô (kể cả ô không sửa được)
    function extend(dr, dc) {
        if (!focusPos) return;
        const maxR = rows().length - 1;
        const maxC = rows().eq(focusPos.r).children("td").length - 1;
        focusPos = { r: Math.min(maxR, Math.max(0, focusPos.r + dr)), c: Math.min(maxC, Math.max(0, focusPos.c + dc)) };
        paintRange();
    }

    // ---------- Bàn phím ----------
    $(document).on("focusin", ".tbody-content " + EDITABLE.split(", ").join(", .tbody-content "), function () {
        const el = $(this);
        el.data("accEnterValue", el.val());
        const p = posOf(el);
        if (!dragging && !el.data("accKeepRange")) {
            anchor = p;
            focusPos = p;
            paintRange();
        }
        el.removeData("accKeepRange");
    });

    $(document).on("keydown", ".tbody-content " + EDITABLE.split(", ").join(", .tbody-content "), function (e) {
        const el = $(this);
        const key = e.key;
        const p = posOf(el);
        const isSelect = el.is("select");
        const isDate = el.is("input[type=date]");
        let caretStart = true, caretEnd = true;
        if (el.is("input[type=text]")) {
            const s = el[0].selectionStart, en = el[0].selectionEnd, len = el.val().length;
            const all = s === 0 && en === len;
            caretStart = all || (s === 0 && en === 0);
            caretEnd = all || (s === len && en === len);
        }

        // Ctrl/Cmd + C khi đang chọn vùng: chép cả vùng dạng bảng (dán được sang Excel)
        if ((e.ctrlKey || e.metaKey) && (key === "c" || key === "C") && rangeSize() > 1) {
            e.preventDefault();
            copyRange();
            return;
        }

        if (e.shiftKey && ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"].includes(key)
            && (key === "ArrowUp" || key === "ArrowDown" || isSelect || isDate || (key === "ArrowLeft" ? caretStart : caretEnd))) {
            e.preventDefault();
            extend(key === "ArrowUp" ? -1 : key === "ArrowDown" ? 1 : 0, key === "ArrowLeft" ? -1 : key === "ArrowRight" ? 1 : 0);
            return;
        }

        let move = null;
        if (key === "ArrowUp" && !e.altKey) move = [-1, 0];
        else if (key === "ArrowDown" && !e.altKey) move = [1, 0];
        else if (key === "Enter") move = e.shiftKey ? [-1, 0] : [1, 0];
        else if (key === "ArrowLeft" && !e.shiftKey && (isSelect || isDate || caretStart)) move = [0, -1];
        else if (key === "ArrowRight" && !e.shiftKey && (isSelect || isDate || caretEnd)) move = [0, 1];

        if (move) {
            e.preventDefault();
            const n = step(p, move[0], move[1]);
            if (n) focusCell(n.el);
            return;
        }

        if (key === "Escape") {
            const before = el.data("accEnterValue");
            if (before !== undefined && el.val() !== before) {
                e.preventDefault();
                el.val(before).trigger("change");
            }
            clearRange();
            return;
        }

        if ((key === "Delete" || key === "Backspace") && rangeSize() > 1) {
            e.preventDefault();
            const cells = rangeCells().map((x) => ({ el: x.el, value: "" }));
            writeCells(cells, "Đã xóa", "", "Xóa vùng " + cells.length + " ô");
        }
    });

    // Bấm chuột vào ô chữ lần đầu: chọn hết chữ để gõ là thay luôn (như Excel); bấm thêm lần nữa mới đặt con trỏ để sửa
    $(document).on("mousedown", ".tbody-content input[type=text]", function () {
        if (document.activeElement !== this) $(this).data("accClickFocus", true);
    });
    $(document).on("mouseup", ".tbody-content input[type=text]", function (e) {
        const el = $(this);
        if (!el.data("accClickFocus")) return;
        el.removeData("accClickFocus");
        if (this.selectionStart === this.selectionEnd && !el.prop("readOnly")) {
            e.preventDefault();
            this.select();
        }
    });

    // ---------- Chuột: kéo để chọn vùng, Shift + bấm để mở rộng ----------
    $(document).on("mousedown", ".tbody-content tr[data-id] > td", function (e) {
        if (e.button !== 0) return;
        const td = $(this);
        const tr = td.closest("tr");
        const p = { r: rows().index(tr), c: tr.children("td").index(td) };
        if (e.shiftKey && anchor) {
            e.preventDefault();
            focusPos = p;
            paintRange();
            const el = cellEl(td);
            if (isEditable(el)) {
                el.data("accKeepRange", true);
                el.trigger("focus");
            }
            return;
        }
        anchor = p;
        focusPos = p;
        dragging = true;
        paintRange();
    });
    $(document).on("mouseover", ".tbody-content tr[data-id] > td", function () {
        if (!dragging) return;
        const td = $(this);
        const tr = td.closest("tr");
        const p = { r: rows().index(tr), c: tr.children("td").index(td) };
        if (focusPos && p.r === focusPos.r && p.c === focusPos.c) return;
        focusPos = p;
        paintRange();
        // Đang kéo qua nhiều ô: bỏ bôi đen chữ trong ô
        try { window.getSelection().removeAllRanges(); } catch (e) {}
    });
    $(document).on("mouseup", function () {
        dragging = false;
    });
    // Bấm ra ngoài bảng thì bỏ vùng chọn
    $(document).on("mousedown", function (e) {
        if (!$(e.target).closest(".tbody-content").length && !$(e.target).closest(".acc-toast, .acc-savebar").length) {
            anchor = focusPos = null;
            paintRange();
        }
    });

    // ---------- Chép vùng ----------
    function cellText(td) {
        const el = cellEl(td);
        if (el.length) return el.is("select") ? el.find("option:selected").text().trim() : String(el.val() || "");
        return td.text().trim();
    }
    function copyRange() {
        const b = rangeBox();
        const lines = [];
        for (let r = b.r1; r <= b.r2; r++) {
            const tds = rows().eq(r).children("td");
            const cols = [];
            for (let c = b.c1; c <= b.c2; c++) if (colVisible(c)) cols.push(cellText(tds.eq(c)).replace(/[\t\n\r]+/g, " "));
            lines.push(cols.join("\t"));
        }
        const text = lines.join("\n");
        const done = () => window.accToast && window.accToast("Đã chép " + (b.r2 - b.r1 + 1) + " dòng × " + (b.c2 - b.c1 + 1) + " cột", "ok");
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, () => fallbackCopy(text, done));
        } else {
            fallbackCopy(text, done);
        }
    }
    function fallbackCopy(text, done) {
        const ta = $("<textarea>").css({ position: "fixed", left: -9999, top: 0 }).val(text).appendTo("body");
        ta[0].select();
        try { document.execCommand("copy"); done(); } catch (e) {}
        ta.remove();
    }

    // ---------- Dán ----------
    // Đọc dữ liệu dạng bảng của Excel (tab giữa các cột, xuống dòng giữa các dòng, ô có xuống dòng nằm trong "...")
    function parseTsv(text) {
        text = String(text).replace(/\r\n?/g, "\n");
        const out = [];
        let row = [], cell = "", q = false;
        for (let i = 0; i < text.length; i++) {
            const ch = text[i];
            if (q) {
                if (ch === '"' && text[i + 1] === '"') { cell += '"'; i++; }
                else if (ch === '"') q = false;
                else cell += ch;
            } else if (ch === '"' && cell === "") q = true;
            else if (ch === "\t") { row.push(cell); cell = ""; }
            else if (ch === "\n") { row.push(cell); out.push(row); row = []; cell = ""; }
            else cell += ch;
        }
        if (cell !== "" || row.length) { row.push(cell); out.push(row); }
        return out;
    }

    const strip = (s) => String(s).normalize("NFD").replace(/[̀-ͯ]/g, "").replace(/đ/g, "d").replace(/Đ/g, "D").toLowerCase().trim();

    // Chuyển giá trị dán cho đúng kiểu ô. Trả về {ok, value} — ok=false: giá trị không hợp lệ, bỏ qua ô đó
    function normalize(el, raw) {
        const name = el.attr("name");
        const v = String(raw == null ? "" : raw).trim();
        if (el.is("select")) {
            if (v === "") return { ok: true, value: name === "accountant_status" ? "0" : "" };
            const opts = el.find("option");
            let hit = opts.filter((_, o) => o.value === v);
            if (!hit.length) hit = opts.filter((_, o) => strip($(o).text()) === strip(v));
            if (!hit.length && name === "accountant_status") {
                const t = strip(v);
                if (/^(1|da|da tt|da thanh toan|x|yes|co)$/.test(t)) hit = opts.filter((_, o) => o.value === "1");
                else if (/^(0|chua|chua tt|chua thanh toan|no|khong)$/.test(t)) hit = opts.filter((_, o) => o.value === "0");
            }
            return hit.length ? { ok: true, value: hit.first().val() } : { ok: false };
        }
        if (MONEY.includes(name)) {
            if (v === "") return { ok: true, value: "" };
            let t = v.replace(/\s|đ|vnd|vnđ/gi, "");
            let n;
            if (/^-?\d{1,3}([.,]\d{3})+$/.test(t)) n = parseInt(t.replace(/[.,]/g, ""), 10);
            else if (/^-?\d+([.,]\d+)?$/.test(t)) n = Math.round(parseFloat(t.replace(",", ".")));
            else return { ok: false };
            return { ok: true, value: typeof formatCurrency === "function" ? formatCurrency(n) : String(n) };
        }
        if (el.is("input[type=date]")) {
            if (v === "") return { ok: true, value: "" };
            let m;
            let y, mo, d;
            if ((m = v.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/))) [y, mo, d] = [+m[1], +m[2], +m[3]];
            else if ((m = v.match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/))) [d, mo, y] = [+m[1], +m[2], +m[3]];
            else return { ok: false };
            const dt = new Date(y, mo - 1, d);
            if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return { ok: false };
            return { ok: true, value: y + "-" + String(mo).padStart(2, "0") + "-" + String(d).padStart(2, "0") };
        }
        // Ngày TT (ô chữ, cho ghi gộp kiểu "05+25/04/2025"): giống máy chủ, chặn ô có chữ cái hoặc một ngày không có thật
        if (name === "accountant_day_payment" && v !== "") {
            if (/\p{L}/u.test(v)) return { ok: false };
            let m = v.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
            let y, mo, d;
            if (m) [y, mo, d] = [+m[1], +m[2], +m[3]];
            else if ((m = v.match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/))) [d, mo, y] = [+m[1], +m[2], +m[3]];
            if (m) {
                const dt = new Date(y, mo - 1, d);
                if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return { ok: false };
                return { ok: true, value: String(d).padStart(2, "0") + "/" + String(mo).padStart(2, "0") + "/" + y };
            }
        }
        return { ok: true, value: v };
    }

    $(document).on("paste", ".tbody-content " + EDITABLE.split(", ").join(", .tbody-content "), function (e) {
        const cd = (e.originalEvent || e).clipboardData;
        if (!cd) return;
        const text = cd.getData("text/plain");
        if (text == null) return;
        const grid = parseTsv(text);
        const multi = grid.length > 1 || (grid[0] && grid[0].length > 1);
        const el = $(this);

        if (!multi && rangeSize() > 1) {
            // Một giá trị + đang chọn vùng: điền cho cả vùng
            e.preventDefault();
            const value = grid.length ? grid[0][0] : "";
            writeCells(rangeCells().map((x) => ({ el: x.el, value, raw: true })), "Đã điền");
            return;
        }
        if (!multi) return; // dán một ô: để trình duyệt dán bình thường

        e.preventDefault();
        const b = rangeBox();
        const start = b && rangeSize() > 1 ? { r: b.r1, c: b.c1 } : posOf(el);
        const cells = [];
        let outside = 0;
        // Cột dán lần lượt vào các cột ĐANG HIỆN tính từ ô bắt đầu (cột ẩn bỏ qua)
        const targetCols = [];
        const width = Math.max(...grid.map((g) => g.length));
        const maxC = rows().eq(start.r).children("td").length - 1;
        for (let c = start.c; c <= maxC && targetCols.length < width; c++) if (colVisible(c)) targetCols.push(c);
        grid.forEach((cols, i) => {
            cols.forEach((value, k) => {
                const r = start.r + i, c = targetCols[k];
                if (c === undefined) { outside++; return; }
                if (r >= rows().length) { outside++; return; }
                const target = elAt(r, c);
                if (target) cells.push({ el: target, value, raw: true });
            });
        });
        writeCells(cells, "Đã dán", outside ? outside + " ô nằm ngoài bảng đang hiện (cuộn xuống tải thêm dòng rồi dán tiếp)" : "");
        // Tô vùng vừa dán
        anchor = start;
        focusPos = { r: Math.min(rows().length - 1, start.r + grid.length - 1), c: targetCols.length ? targetCols[targetCols.length - 1] : start.c };
        paintRange();
    });

    // Ghi nhiều ô rồi lưu chung một lần. cells: [{el, value, raw}] (raw = giá trị dán, cần chuyển kiểu)
    function writeCells(cells, verb, extra, undoLabel) {
        if (window.accIsSaving && window.accIsSaving()) {
            window.accToast && window.accToast("Đang lưu, đợi lưu xong rồi thao tác lại.", "warn");
            return;
        }
        let locked = 0, invalid = 0, written = 0;
        const touched = new Map(); // id dòng -> {row, pasted: {name: value}}
        cells.forEach(({ el, value }) => {
            if (!isEditable(el)) { locked++; return; }
            const n = normalize(el, value);
            if (!n.ok) { invalid++; el.closest("td").addClass("acc-cell-bad"); setTimeout(() => el.closest("td").removeClass("acc-cell-bad"), 3000); return; }
            const row = el.closest("tr");
            const id = String(row.data("id"));
            if (!touched.has(id)) {
                window.accEnsureOriginal(row);
                touched.set(id, { row, pasted: {}, before: window.accRowValues(row) });
            }
            if (String(el.val()) === n.value) { written++; return; }
            el.val(n.value);
            window.accApplyCellEdit(el);
            touched.get(id).pasted[el.attr("name")] = { el, value: n.value };
            written++;
        });
        // Ô dán thẳng Thành tiền / Thành tiền CK / Trạng thái: giữ đúng số dán (ô khác dán sau có thể đã tính đè)
        touched.forEach(({ pasted }) => {
            KEEP_PASTED.forEach((name) => {
                const p = pasted[name];
                if (p && String(p.el.val()) !== p.value) {
                    p.el.val(p.value);
                    window.accApplyCellEdit(p.el);
                }
            });
        });
        const changedRows = [...touched.values()].filter((t) => Object.keys(t.pasted).length).map((t) => t.row);
        if (changedRows.length) window.accEnqueueMany(changedRows);
        // Ghi lại để Ctrl+Z hoàn tác (cả các ô tự tính lại như Thành tiền, Còn nợ)
        recordOp(undoLabel || verb.replace(/^Đã (.)/, (m, c) => c.toUpperCase()) + " " + written + " ô", [...touched.values()].map((t) => ({ row: t.row, before: t.before })));
        touched.forEach((t) => t.row.data("accUndoBefore", window.accRowValues(t.row)));

        const notes = [];
        if (locked) notes.push(locked + " ô bị khóa (đơn đã chốt) bỏ qua");
        if (invalid) notes.push(invalid + " ô sai định dạng bỏ qua (tô đỏ)");
        if (extra) notes.push(extra);
        window.accPasteNote = changedRows.length > 1 ? notes.join(" · ") : "";
        const msg = [verb + " " + written + " ô" + (changedRows.length ? " (" + changedRows.length + " dòng), đang lưu…" : "")].concat(notes).join(" · ");
        window.accToast && window.accToast(msg, notes.length ? "warn" : "ok");
        scheduleHandle();
    }

    // ---------- Hoàn tác (Ctrl+Z) / làm lại (Ctrl+Y) ----------
    // Mỗi thao tác (sửa một ô, dán, điền, xóa vùng) ghi lại giá trị trước / sau của mọi ô đổi trong các dòng đó
    // (kể cả ô tự tính lại). Hoàn tác chỉ trả lại những ô CHƯA bị đổi tiếp sau thao tác đó, rồi lưu như bình thường.
    // Thao tác nhiều ô hỏi lại trước khi hoàn tác (vì sẽ ghi lại nhiều dòng đã lưu); một ô thì hoàn tác ngay.
    const undoStack = [];
    const redoStack = [];
    const UNDO_MAX = 50;
    function diffRow(row, before) {
        const after = window.accRowValues(row);
        return Object.keys(after)
            .filter((k) => before[k] !== undefined && before[k] !== after[k])
            .map((name) => ({ name, before: before[name], after: after[name] }));
    }
    window.accRecordOp = (label, rowsBefore) => recordOp(label, rowsBefore);
    function recordOp(label, rowsBefore) {
        const rows = rowsBefore
            .map(({ row, before }) => ({ id: String(row.data("id")), changes: diffRow(row, before) }))
            .filter((r) => r.changes.length);
        if (!rows.length) return;
        undoStack.push({ label, rows });
        if (undoStack.length > UNDO_MAX) undoStack.shift();
        redoStack.length = 0;
    }
    const cellCount = (op) => op.rows.reduce((n, r) => n + r.changes.length, 0);

    // Sửa tay một ô: ghi lại khi ô đổi xong (rời ô / chọn xong)
    $(document).on("focusin", ".tbody-content tr[data-id] " + EDITABLE.split(", ").join(", .tbody-content tr[data-id] "), function () {
        const row = $(this).closest("tr");
        row.data("accUndoBefore", window.accRowValues(row));
    });
    $(document).on("change", ".tbody-content tr[data-id] " + EDITABLE.split(", ").join(", .tbody-content tr[data-id] "), function () {
        const el = $(this);
        const row = el.closest("tr");
        const before = row.data("accUndoBefore");
        if (!before) return;
        const th = $("#table-scroll thead tr.section-title > th").eq(posOf(el).c);
        const label = "Sửa ô " + (th.find(".dropdown > label").first().text().trim() || el.attr("name")) + " đơn #" + row.find(".order_id").text().trim();
        recordOp(label, [{ row, before }]);
        row.data("accUndoBefore", window.accRowValues(row));
    });

    // Áp giá trị (before khi hoàn tác, after khi làm lại) cho các ô còn đúng giá trị mong đợi
    function applyOp(op, dir) {
        const from = dir === "undo" ? "after" : "before";
        const to = dir === "undo" ? "before" : "after";
        let done = 0, skipped = 0;
        const rows = [];
        op.rows.forEach((r) => {
            const row = body().children('tr[data-id="' + r.id + '"]');
            if (!row.length) { skipped += r.changes.length; return; }
            window.accEnsureOriginal(row);
            const edited = row.data("edited") || {};
            let any = false;
            r.changes.forEach((c) => {
                const el = row.find('[name="' + c.name + '"]');
                if (!el.length || el.prop("disabled") || String(el.val()) !== String(c[from])) { skipped++; return; }
                el.val(c[to]);
                edited[c.name] = true;
                if (c.name === "accountant_status") {
                    el.toggleClass("acc-status-paid", c[to] === "1").toggleClass("acc-status-unpaid", c[to] !== "1");
                }
                done++;
                any = true;
            });
            row.data("edited", edited);
            if (any) {
                rows.push(row);
                row.data("accUndoBefore", window.accRowValues(row));
            }
        });
        if (rows.length) window.accEnqueueMany(rows);
        return { done, skipped, rows: rows.length };
    }
    async function runUndo(dir) {
        const stack = dir === "undo" ? undoStack : redoStack;
        const other = dir === "undo" ? redoStack : undoStack;
        const verb = dir === "undo" ? "Hoàn tác" : "Làm lại";
        if (!stack.length) {
            window.accToast && window.accToast(dir === "undo" ? "Không còn thao tác nào để hoàn tác." : "Không còn thao tác nào để làm lại.", "warn");
            return;
        }
        if (window.accIsSaving && window.accIsSaving()) {
            window.accToast && window.accToast("Đang lưu, đợi lưu xong rồi " + verb.toLowerCase() + ".", "warn");
            return;
        }
        const op = stack[stack.length - 1];
        const n = cellCount(op);
        if (n > 1) {
            const msg = verb + " “" + op.label + "”? " + n + " ô ở " + op.rows.length + " dòng sẽ " +
                (dir === "undo" ? "trở lại giá trị trước đó" : "được điền lại") + " và lưu ngay.";
            const ok = window.uiConfirm ? await window.uiConfirm(msg, { title: verb, ok: verb, cancel: "Hủy" }) : window.confirm(msg);
            if (!ok) return;
        }
        stack.pop();
        const res = applyOp(op, dir);
        other.push(op);
        let msg = (dir === "undo" ? "Đã hoàn tác: " : "Đã làm lại: ") + op.label;
        if (res.skipped) msg += " · " + res.skipped + " ô đã bị sửa tiếp sau đó (hoặc đơn đã chốt) nên giữ nguyên";
        msg += dir === "undo" ? " · Ctrl+Y để làm lại" : "";
        // Lưu nhiều dòng thì thông báo "Đã lưu x/y dòng" hiện sau, kèm luôn câu này
        if (res.rows > 1) window.accPasteNote = msg;
        window.accToast && window.accToast(msg, res.skipped ? "warn" : "ok");
        scheduleHandle();
    }
    $(document).on("keydown", function (e) {
        if (!(e.ctrlKey || e.metaKey) || e.altKey) return;
        const k = String(e.key).toLowerCase();
        const isUndo = k === "z" && !e.shiftKey;
        const isRedo = k === "y" || (k === "z" && e.shiftKey);
        if (!isUndo && !isRedo) return;
        const t = $(e.target);
        const inTable = t.closest(".tbody-content").length > 0;
        // Ô chữ ngoài bảng (tìm kiếm, bộ lọc) hoặc đang gõ dở trong ô của bảng: để trình duyệt hoàn tác chữ như thường
        if (!inTable && t.is("input, textarea, select, [contenteditable]")) return;
        if (inTable && t.is("input[type=text]") && t.val() !== t.data("accEnterValue")) return;
        if ($(".uidlg:visible").length) return;
        e.preventDefault();
        runUndo(isUndo ? "undo" : "redo");
    });

    // ---------- Nút kéo điền ở góc ô (fill handle) ----------
    // Ô / vùng đang chọn có dữ liệu thì hiện ô vuông nhỏ ở góc dưới phải:
    // - Kéo xuống (hoặc lên) tới dòng nào thì điền dữ liệu của ô / vùng đó tới dòng đó (vùng nhiều dòng thì lặp lại theo thứ tự).
    // - Bấm đúp: điền xuống hết các dòng đang hiện bên dưới (hỏi lại khi nhiều dòng).
    const scrollBox = () => $("#table-scroll")[0];
    const handle = $('<div class="acc-fill" title="Kéo để điền (ô trống thì xóa trống) xuống các dòng, bấm đúp để điền hết cột" hidden></div>').appendTo("body");
    let handleRaf = 0;
    function scheduleHandle() {
        cancelAnimationFrame(handleRaf);
        handleRaf = requestAnimationFrame(updateHandle);
    }
    function sourceBox() {
        const b = rangeBox();
        return b || null;
    }
    function sourceHasData(b) {
        for (let r = b.r1; r <= b.r2; r++) {
            for (let c = b.c1; c <= b.c2; c++) {
                if (!colVisible(c)) continue;
                const el = elAt(r, c);
                if (el && String(el.val() || "").trim() !== "") return true;
            }
        }
        return false;
    }
    function updateHandle() {
        const b = sourceBox();
        const sc = scrollBox();
        if (!b || !sc || fillDrag) {
            if (!fillDrag) handle.prop("hidden", true);
            return;
        }
        // Góc dưới phải: ô cuối của vùng (cột hiện cuối cùng)
        let c2 = b.c2;
        while (c2 > b.c1 && !colVisible(c2)) c2--;
        const td = rows().eq(b.r2).children("td").eq(c2)[0];
        const anyEditable = rangeCellsOf(b).some((x) => isEditable(x.el));
        // Ô trống cũng có nút kéo: kéo ô trống xuống thì xóa trống các ô đó (như Excel)
        if (!td || !anyEditable) {
            handle.prop("hidden", true);
            return;
        }
        const r = td.getBoundingClientRect();
        const box = sc.getBoundingClientRect();
        const headH = $(sc).find("thead").outerHeight() || 0;
        const x = r.right, y = r.bottom;
        const frozenRight = window.accFrozenRight ? window.accFrozenRight() || box.left : box.left;
        // Vùng nhìn thấy của khung bảng (trừ thanh cuộn)
        const right = box.left + sc.clientLeft + sc.clientWidth + 2;
        const bottom = box.top + sc.clientTop + sc.clientHeight + 2;
        const inside = x > Math.max(box.left, c2Frozen(c2) ? box.left : frozenRight) && x <= right && y > box.top + headH && y <= bottom;
        handle.prop("hidden", !inside);
        if (inside) handle.css({ left: x - 4, top: y - 4 });
    }
    function c2Frozen(c) {
        const th = $("#table-scroll thead tr.section-title > th").eq(c);
        return th.css("position") === "sticky" && th.css("left") !== "auto";
    }
    function rangeCellsOf(b) {
        const out = [];
        for (let r = b.r1; r <= b.r2; r++) {
            for (let c = b.c1; c <= b.c2; c++) {
                if (!colVisible(c)) continue;
                const el = elAt(r, c);
                if (el) out.push({ r, c, el });
            }
        }
        return out;
    }
    $(function () {
        const sc = scrollBox();
        if (sc) sc.addEventListener("scroll", scheduleHandle, { passive: true });
    });
    $(window).on("scroll resize", scheduleHandle);
    $(document).on("focusin input change", ".tbody-content", scheduleHandle);

    // Các ô sẽ điền cho dòng r1..r2 (ngoài vùng nguồn), lặp lại dữ liệu nguồn theo thứ tự dòng
    function fillCells(src, from, to) {
        const h = src.r2 - src.r1 + 1;
        const cells = [];
        for (let r = from; r <= to; r++) {
            const k = (((r - src.r1) % h) + h) % h;
            for (let c = src.c1; c <= src.c2; c++) {
                if (!colVisible(c)) continue;
                const s = elAt(src.r1 + k, c);
                const t = elAt(r, c);
                if (s && t) cells.push({ el: t, value: String(s.val() == null ? "" : s.val()) });
            }
        }
        return cells;
    }

    let fillDrag = null; // {src, target}
    function paintFillPreview() {
        body().find("td.acc-fill-pre").removeClass("acc-fill-pre");
        if (!fillDrag) return;
        const { src, target } = fillDrag;
        const from = target > src.r2 ? src.r2 + 1 : target;
        const to = target > src.r2 ? target : src.r1 - 1;
        const all = rows();
        for (let r = from; r <= to; r++) {
            const tds = all.eq(r).children("td");
            for (let c = src.c1; c <= src.c2; c++) tds.eq(c).addClass("acc-fill-pre");
        }
    }
    handle.on("mousedown", function (e) {
        if (e.button !== 0) return;
        e.preventDefault();
        e.stopPropagation();
        if (window.accIsSaving && window.accIsSaving()) {
            window.accToast && window.accToast("Đang lưu, đợi lưu xong rồi thao tác lại.", "warn");
            return;
        }
        const b = sourceBox();
        if (!b) return;
        fillDrag = { src: { ...b }, target: b.r2 };
        $("body").addClass("acc-filling");
    });
    $(document).on("mousemove", function (e) {
        if (!fillDrag) return;
        const sc = scrollBox();
        const box = sc.getBoundingClientRect();
        // Kéo sát mép trên / dưới khung bảng thì tự cuộn
        if (e.clientY > box.bottom - 24) sc.scrollTop += 18;
        else if (e.clientY < box.top + ($(sc).find("thead").outerHeight() || 0) + 16) sc.scrollTop -= 18;
        const el = document.elementFromPoint(Math.min(Math.max(e.clientX, box.left + 2), box.right - 2), e.clientY);
        const tr = el ? $(el).closest(".tbody-content tr[data-id]") : $();
        if (!tr.length) return;
        const r = rows().index(tr);
        if (r === fillDrag.target) return;
        fillDrag.target = r;
        handle.css({ top: tr[0].getBoundingClientRect().bottom - 4 });
        paintFillPreview();
    });
    $(document).on("mouseup", function () {
        if (!fillDrag) return;
        const { src, target } = fillDrag;
        fillDrag = null;
        $("body").removeClass("acc-filling");
        paintFillPreview();
        let cells = [];
        if (target > src.r2) cells = fillCells(src, src.r2 + 1, target);
        else if (target < src.r1) cells = fillCells(src, target, src.r1 - 1);
        if (cells.length) {
            writeCells(cells, sourceHasData(src) ? "Đã điền" : "Đã xóa");
            anchor = { r: Math.min(src.r1, target), c: src.c1 };
            focusPos = { r: Math.max(src.r2, target), c: src.c2 };
            paintRange();
        }
        scheduleHandle();
    });
    handle.on("dblclick", async function (e) {
        e.preventDefault();
        const b = sourceBox();
        if (!b) return;
        const last = rows().length - 1;
        if (b.r2 >= last) {
            window.accToast && window.accToast("Không còn dòng nào bên dưới để điền.", "warn");
            return;
        }
        const n = last - b.r2;
        const empty = !sourceHasData(b);
        const label = $("#table-scroll thead tr.section-title > th").eq(b.c1).find(".dropdown > label").first().text().trim() || "cột này";
        if (empty) {
            // Bấm đúp ô trống = xóa trống cả cột bên dưới: luôn hỏi lại
            const msg = "Xóa trống cột " + label + " của " + n + " dòng bên dưới? Các dòng đang hiện trên bảng sẽ bị xóa dữ liệu ở cột này.";
            const ok = window.uiConfirm ? await window.uiConfirm(msg, { title: "Xóa trống " + n + " dòng", ok: "Xóa", cancel: "Hủy", danger: true }) : window.confirm(msg);
            if (!ok) return;
        } else if (n > 10) {
            const msg = "Điền dữ liệu " + (b.r2 > b.r1 || b.c2 > b.c1 ? "của vùng đang chọn" : "“" + String(elAt(b.r1, b.c1).val()) + "”") +
                " cho " + n + " dòng bên dưới (cột " + label + ")? Các dòng đang hiện trên bảng sẽ bị ghi đè ở cột này.";
            const ok = window.uiConfirm ? await window.uiConfirm(msg, { title: "Điền xuống " + n + " dòng", ok: "Điền", cancel: "Hủy" }) : window.confirm(msg);
            if (!ok) return;
        }
        writeCells(fillCells(b, b.r2 + 1, last), empty ? "Đã xóa" : "Đã điền");
        anchor = { r: b.r1, c: b.c1 };
        focusPos = { r: last, c: b.c2 };
        paintRange();
    });
})(jQuery);


/* ==========================================================================
 * ẨN / HIỆN CỘT VÀ CỐ ĐỊNH CỘT (chỉ trang công nợ Kế toán)
 * - Nút "Cột" trên bảng: tick để ẩn / hiện từng cột, ghim để cố định từ cột đầu tới cột đó (như Freeze Panes của Excel).
 * - Bấm chuột phải vào tiêu đề cột: Ẩn cột / Cố định tới cột này / Bỏ cố định / Hiện tất cả cột.
 * - Lưu theo từng máy (localStorage). Vị trí cột cố định tính theo bề rộng thật của cột (trước đây đặt số cứng nên
 *   các cột cố định chồng lên nhau), tự tính lại khi tải thêm dòng hoặc đổi cỡ cửa sổ.
 * ========================================================================== */
(function ($) {
    if (typeof url_update_rows === "undefined") return;

    const KEY = "acc_columns_v1";
    const DEFAULT_FREEZE = 7; // tới cột "Công ty" như trước
    const scroll = $("#table-scroll");
    const headRow = scroll.find("thead tr.section-title").first();
    if (!scroll.length || !headRow.length) return;
    const ths = headRow.children("th");

    const cols = ths
        .map(function (i) {
            const th = $(this);
            const field = th.find("[id^='dropdown-']").attr("id");
            const label = (th.find(".dropdown > label").first().text() || th.text()).replace(/\s+/g, " ").trim();
            return { i, key: field ? field.replace("dropdown-", "") : "col-" + label, label: label || "Cột " + (i + 1) };
        })
        .get();

    let state = { hidden: [], freeze: DEFAULT_FREEZE };
    try {
        const saved = JSON.parse(localStorage.getItem(KEY) || "null");
        if (saved && Array.isArray(saved.hidden)) {
            // Lưu theo tên cột (không theo vị trí) để thêm / bớt cột sau này không lệch
            const freezeIdx = cols.findIndex((c) => c.key === saved.freezeKey);
            state = { hidden: saved.hidden, freeze: saved.freezeKey === null ? -1 : freezeIdx >= 0 ? freezeIdx : DEFAULT_FREEZE };
        }
    } catch (e) {}
    function save() {
        try {
            localStorage.setItem(KEY, JSON.stringify({ hidden: state.hidden, freezeKey: state.freeze >= 0 ? cols[state.freeze].key : null }));
        } catch (e) {}
    }
    const isHidden = (c) => state.hidden.includes(c.key);

    const styleEl = $('<style id="acc-col-style"></style>').appendTo("head");
    scroll.addClass("acc-frz");

    function apply() {
        const sel = (k) => "#table-scroll table tr > :nth-child(" + (k + 1) + ")";
        let css = "";
        cols.forEach((c) => {
            if (isHidden(c)) css += sel(c.i) + "{display:none}";
        });
        // Bề rộng thật của các cột (sau khi ẩn) để đặt vị trí cột cố định
        styleEl.text(css);
        let left = 0;
        let frz = "";
        const visibleFrozen = cols.filter((c) => c.i <= state.freeze && !isHidden(c));
        visibleFrozen.forEach((c, n) => {
            const k = c.i + 1;
            const last = n === visibleFrozen.length - 1;
            frz +=
                "#table-scroll.acc-frz thead th:nth-child(" + k + "){left:" + left + "px!important;z-index:" + (90 - n) + "!important}" +
                "#table-scroll.acc-frz tbody td:nth-child(" + k + "){position:sticky!important;left:" + left + "px!important;z-index:" + (30 - Math.min(n, 20)) + "!important}" +
                ":where(#table-scroll.acc-frz) tbody td:nth-child(" + k + "){background-color:#fff}" +
                (last ? "#table-scroll.acc-frz tr > :nth-child(" + k + "){border-right:2px solid #c3c9d2!important}" : "");
            left += ths[c.i].getBoundingClientRect().width;
        });
        css += "@media only screen and (min-width: 1023px){" + frz + "}";
        styleEl.text(css);
        renderPanel();
    }
    let raf = 0;
    const schedule = () => {
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(apply);
    };

    // Mép phải của vùng cố định (để phím mũi tên cuộn ô ra khỏi chỗ bị che)
    window.accFrozenRight = function () {
        if (state.freeze < 0 || window.innerWidth < 1023) return 0;
        let right = 0;
        cols.forEach((c) => {
            if (c.i <= state.freeze && !isHidden(c)) right = Math.max(right, ths[c.i].getBoundingClientRect().right);
        });
        return right;
    };

    // ---------- Thanh công cụ + bảng chọn cột ----------
    const bar = $(
        '<div class="acc-colbar">' +
            '<button type="button" class="acc-colbar__btn"><i class="fa-solid fa-table-columns"></i> Cột</button>' +
            '<span class="acc-colbar__info"></span>' +
            '<span class="acc-colbar__hint">Mẹo: bấm chuột phải vào tiêu đề cột để ẩn hoặc cố định nhanh</span>' +
        "</div>"
    ).insertBefore(scroll);
    const panel = $('<div class="acc-colpanel" hidden></div>').appendTo("body");

    function renderPanel() {
        const hiddenCount = cols.filter(isHidden).length;
        const info = [];
        if (hiddenCount) info.push("Đang ẩn " + hiddenCount + " cột");
        info.push(state.freeze >= 0 ? "Cố định tới cột “" + cols[state.freeze].label + "”" : "Không cố định cột");
        bar.find(".acc-colbar__info").text(info.join(" · "));
        if (panel.prop("hidden")) return;
        let html =
            '<div class="acc-colpanel__head"><b>Cột hiển thị</b>' +
            '<button type="button" class="acc-colpanel__close" aria-label="Đóng">×</button></div>' +
            '<div class="acc-colpanel__actions">' +
            '<button type="button" data-act="show-all">Hiện tất cả</button>' +
            '<button type="button" data-act="unfreeze">Bỏ cố định</button>' +
            '<button type="button" data-act="reset">Về mặc định</button></div>' +
            '<div class="acc-colpanel__list">';
        cols.forEach((c) => {
            const frozen = c.i <= state.freeze;
            html +=
                '<div class="acc-colpanel__row' + (frozen ? " is-frozen" : "") + '">' +
                '<label><input type="checkbox" data-i="' + c.i + '"' + (isHidden(c) ? "" : " checked") + "> " + $("<span>").text(c.label).html() + "</label>" +
                '<button type="button" class="acc-colpanel__pin' + (c.i === state.freeze ? " is-on" : "") + '" data-pin="' + c.i + '" title="Cố định từ cột đầu tới cột này">' +
                '<i class="fa-solid fa-thumbtack"></i></button></div>';
        });
        html += "</div>";
        panel.html(html);
    }
    function openPanel() {
        panel.prop("hidden", false);
        renderPanel();
        const r = bar.find(".acc-colbar__btn")[0].getBoundingClientRect();
        panel.css({ top: r.bottom + 6, left: Math.max(8, Math.min(r.left, window.innerWidth - panel.outerWidth() - 8)) });
    }
    bar.on("click", ".acc-colbar__btn", () => (panel.prop("hidden") ? openPanel() : panel.prop("hidden", true)));
    panel.on("click", ".acc-colpanel__close", () => panel.prop("hidden", true));
    panel.on("change", "input[type=checkbox]", function () {
        const c = cols[+$(this).data("i")];
        if (this.checked) state.hidden = state.hidden.filter((k) => k !== c.key);
        else if (cols.filter((x) => !isHidden(x)).length > 1) state.hidden.push(c.key);
        else this.checked = true;
        save();
        apply();
    });
    panel.on("click", "[data-pin]", function () {
        const i = +$(this).data("pin");
        state.freeze = state.freeze === i ? -1 : i;
        save();
        apply();
    });
    panel.on("click", "[data-act]", function () {
        const act = $(this).data("act");
        if (act === "show-all") state.hidden = [];
        if (act === "unfreeze") state.freeze = -1;
        if (act === "reset") state = { hidden: [], freeze: DEFAULT_FREEZE };
        save();
        apply();
    });
    $(document).on("mousedown", function (e) {
        if (!panel.prop("hidden") && !$(e.target).closest(".acc-colpanel, .acc-colbar").length) panel.prop("hidden", true);
        if (!$(e.target).closest(".acc-colmenu").length) menu.prop("hidden", true);
    });

    // ---------- Chuột phải vào tiêu đề cột ----------
    const menu = $('<div class="acc-colmenu" hidden></div>').appendTo("body");
    headRow.on("contextmenu", "> th", function (e) {
        e.preventDefault();
        const c = cols[ths.index(this)];
        // Cột đã nằm trong vùng cố định thì không cho "Cố định tới cột này" nữa: hiện trạng thái đang cố định;
        // cột nằm giữa vùng cố định thì cho thu hẹp vùng về tới cột đó
        const frozen = state.freeze >= 0 && c.i <= state.freeze;
        menu.html(
            '<div class="acc-colmenu__title"></div>' +
            (frozen ? '<div class="acc-colmenu__state"><i class="fa-solid fa-thumbtack"></i> Đang cố định' +
                (c.i < state.freeze ? ' (tới cột “' + $("<span>").text(cols[state.freeze].label).html() + '”)' : "") + "</div>" : "") +
            '<button type="button" data-m="hide">Ẩn cột này</button>' +
            (!frozen ? '<button type="button" data-m="freeze">Cố định tới cột này</button>' : "") +
            (frozen && c.i < state.freeze ? '<button type="button" data-m="freeze">Chỉ cố định tới cột này</button>' : "") +
            (state.freeze >= 0 ? '<button type="button" data-m="unfreeze">Bỏ cố định cột</button>' : "") +
            (state.hidden.length ? '<button type="button" data-m="show-all">Hiện tất cả cột (' + state.hidden.length + ")</button>" : "")
        );
        menu.find(".acc-colmenu__title").text(c.label);
        menu.data("i", c.i).prop("hidden", false);
        menu.css({ top: Math.min(e.clientY, window.innerHeight - menu.outerHeight() - 8), left: Math.min(e.clientX, window.innerWidth - menu.outerWidth() - 8) });
    });
    menu.on("click", "[data-m]", function () {
        const c = cols[+menu.data("i")];
        const m = $(this).data("m");
        if (m === "hide" && cols.filter((x) => !isHidden(x)).length > 1) state.hidden.push(c.key);
        if (m === "freeze") state.freeze = c.i;
        if (m === "unfreeze") state.freeze = -1;
        if (m === "show-all") state.hidden = [];
        menu.prop("hidden", true);
        save();
        apply();
    });

    // Bề rộng cột đổi khi tải thêm dòng / đổi cỡ cửa sổ: tính lại vị trí cột cố định
    const tbody = scroll.find("tbody")[0];
    if (tbody && window.MutationObserver) new MutationObserver(schedule).observe(tbody, { childList: true });
    $(window).on("resize", schedule);
    apply();
})(jQuery);


/* ==========================================================================
 * SỐ CA ĐỔI SAU KHI CẬP NHẬT DOANH THU (chỉ trang Kế toán)
 * Bộ phận Kết quả nhập số ca thực tế khác khi đơn đã "Cập nhật doanh thu": số ca lưu nhưng tiền giữ nguyên, ô số ca có
 * nhãn "130 → 140". Kế toán bấm nhãn: "Tính lại tiền theo số ca mới" (Thành tiền, CK, Lợi nhuận, Còn nợ) hoặc
 * "Giữ tiền cũ". Nút lọc "Số ca thay đổi" trên thanh Cột để tìm nhanh. Tính lại có thể Ctrl+Z (trả lại số tiền cũ).
 * ========================================================================== */
(function ($) {
    if (typeof url_qty_change === "undefined") return;
    const toast = (m, t) => window.accToast && window.accToast(m, t);
    // Mở từ chuông thông báo (?qty_changed=1): bật sẵn bộ lọc "Số ca thay đổi" trước khi bảng tải lần đầu
    try {
        if (new URLSearchParams(location.search).get("qty_changed") === "1") window.accQtyChangedOnly = true;
    } catch (e) {}

    // ---------- Nút lọc ----------
    let count = typeof acc_qty_changed_count !== "undefined" ? +acc_qty_changed_count : 0;
    const chip = $('<button type="button" class="acc-qtychip" hidden></button>');
    $(function () {
        const bar = $(".acc-colbar");
        if (bar.length) bar.find(".acc-colbar__info").after(chip);
        else chip.insertBefore("#table-scroll");
        render();
    });
    function render() {
        const on = !!window.accQtyChangedOnly;
        chip.toggleClass("is-on", on).prop("hidden", !on && count === 0);
        chip.html(on
            ? '<i class="fa-solid fa-filter"></i> Đang lọc đơn có số ca thay đổi (' + count + ") · <u>Bỏ lọc</u>"
            : '<i class="fa-solid fa-triangle-exclamation"></i> ' + count + " đơn có số ca thay đổi sau khi cập nhật doanh thu");
    }
    window.accSetQtyCount = (n) => { count = +n || 0; render(); };
    window.accQtyCountNow = () => count;
    chip.on("click", function () {
        if (window.accPendingSaves && window.accPendingSaves() > 0) {
            toast("Còn dòng đang lưu hoặc chưa lưu được. Bấm \"Lưu lại\" rồi hãy lọc.", "warn");
            return;
        }
        window.accQtyChangedOnly = !window.accQtyChangedOnly;
        render();
        $(".tbody-content").empty();
        if (typeof getListAccountant === "function") getListAccountant();
    });

    // ---------- Bảng chọn xử lý ----------
    const pop = $('<div class="acc-qtypop" hidden></div>').appendTo("body");
    let current = null;
    $(document).on("click", ".acc-qtyflag", function (e) {
        e.preventDefault();
        e.stopPropagation();
        const btn = $(this);
        const row = btn.closest("tr");
        current = { row, btn };
        const from = btn.data("from"), to = btn.data("to");
        const orderId = row.find(".order_id").text().trim();
        pop.html(
            '<div class="acc-qtypop__title"><i class="fa-solid fa-triangle-exclamation"></i> Số ca đổi sau khi cập nhật doanh thu</div>' +
            '<div class="acc-qtypop__text">Đơn #' + orderId + ": kế toán đã tính tiền theo <b>" + from + " ca</b>, bộ phận Kết quả vừa cập nhật số ca thực tế <b>" + to + " ca</b>.</div>" +
            '<div class="acc-qtypop__actions">' +
            '<button type="button" class="acc-qtypop__btn is-primary" data-act="apply">Tính lại tiền theo ' + to + " ca</button>" +
            '<button type="button" class="acc-qtypop__btn" data-act="keep">Giữ tiền cũ</button></div>' +
            '<div class="acc-qtypop__hint">Tính lại: Thành tiền = số ca × đơn giá (đơn trọn gói giữ Thành tiền), rồi tính lại chiết khấu, lợi nhuận, còn nợ. Có thể Ctrl+Z để trả lại.</div>'
        );
        pop.prop("hidden", false);
        const r = this.getBoundingClientRect();
        pop.css({
            top: Math.min(r.bottom + 6, window.innerHeight - pop.outerHeight() - 8),
            left: Math.max(8, Math.min(r.left, window.innerWidth - pop.outerWidth() - 8)),
        });
    });
    $(document).on("mousedown", function (e) {
        if (!pop.prop("hidden") && !$(e.target).closest(".acc-qtypop, .acc-qtyflag").length) pop.prop("hidden", true);
    });
    $(document).on("keydown", function (e) {
        if (e.key === "Escape") pop.prop("hidden", true);
    });

    pop.on("click", "[data-act]", function () {
        if (!current) return;
        const { row, btn } = current;
        const act = $(this).data("act");
        if (window.accRowPending && window.accRowPending(row)) {
            toast("Dòng này còn số đang lưu. Đợi lưu xong rồi bấm lại.", "warn");
            return;
        }
        pop.find("button").prop("disabled", true);
        const before = window.accRowValues ? window.accRowValues(row) : null;
        let filter = [];
        try {
            const obj = JSON.parse(localStorage.getItem("accountant_filter_state") || "{}");
            filter = Object.keys(obj).map((k) => ({ name: k, value: obj[k] }));
        } catch (err) {}
        $.ajax({
            url: url_qty_change,
            method: "PATCH",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), Accept: "application/json" },
            data: { id: row.data("id"), action: act, filter_data: filter },
            success: function (res) {
                pop.prop("hidden", true);
                if (!res || !res.success) {
                    if (res && res.cleared) btn.remove();
                    toast((res && res.message) || "Chưa xử lý được, thử lại.", "bad");
                    return;
                }
                if (act === "apply" && res.values) {
                    window.accEnsureOriginal(row);
                    const original = row.data("originalValues");
                    Object.keys(res.values).forEach((k) => {
                        const input = row.find('[name="' + k + '"]');
                        if (!input.length) return;
                        input.val(res.values[k]);
                        if (original) original[k] = res.values[k];
                    });
                    // Ctrl+Z trả lại số tiền cũ (lưu như sửa tay)
                    if (before && window.accRecordOp) window.accRecordOp("Tính lại tiền theo số ca mới đơn #" + row.find(".order_id").text().trim(), [{ row, before }]);
                    row.data("accUndoBefore", window.accRowValues(row));
                }
                btn.remove();
                if (res.totals && typeof updateTotalsUI === "function") updateTotalsUI(res.totals);
                if (res.qty_changed_count !== undefined) window.accSetQtyCount(res.qty_changed_count);
                $(".dropdown-list-container").removeData("loaded");
                toast(res.message + (act === "apply" ? " · Ctrl+Z để trả lại" : ""), "ok");
            },
            error: function (xhr) {
                pop.prop("hidden", true);
                const code = xhr.status;
                toast("Chưa xử lý được: " + (code === 419 || code === 401 ? "phiên đăng nhập đã hết hạn, đăng nhập lại ở tab khác rồi bấm lại" : code === 0 ? "mất kết nối mạng" : "lỗi máy chủ (" + code + ")") + ".", "bad");
            },
        });
    });
})(jQuery);


/* ==========================================================================
 * Mở từ chuông "Việc cần làm" (?todo=wait_revenue | overdue): lọc sẵn nhóm việc, hiện nhãn "Đang lọc … · Bỏ lọc"
 * ========================================================================== */
(function ($) {
    const LABELS = {
        wait_revenue: "Đơn đã có số ca thực tế, chờ cập nhật doanh thu",
        overdue: "Công nợ quá hạn 45 ngày chưa thanh toán (1 năm gần đây)",
    };
    let todo = null;
    try {
        todo = new URLSearchParams(location.search).get("todo");
    } catch (e) {}
    if (!todo || !LABELS[todo] || typeof url_update_rows === "undefined") return;
    window.accTodo = todo;
    $(function () {
        const chip = $('<a class="acc-qtychip is-on" href="' + location.pathname + '"></a>')
            .html('<i class="fa-solid fa-filter"></i> Đang lọc: ' + LABELS[todo] + " · <u>Bỏ lọc</u>");
        const bar = $(".acc-colbar");
        if (bar.length) bar.find(".acc-colbar__info").after(chip);
        else chip.insertBefore("#table-scroll");
    });
})(jQuery);

/**
 * Thanh cuộn tự ẩn (giống macOS) cho bảng công nợ: CSS ẩn thanh cuộn, chỉ hiện khi phần tử có .sb-on.
 * - Đang cuộn (bánh xe, kéo, phím) → hiện, ngừng cuộn 1 giây thì ẩn.
 * - Rê chuột tới sát mép phải / mép dưới vùng cuộn (chỗ thanh cuộn) → hiện để kéo được bằng chuột (Windows).
 */
(function () {
    var timers = new WeakMap();
    var EDGE = 14;

    function show(el, ms) {
        el.classList.add("sb-on");
        clearTimeout(timers.get(el));
        timers.set(el, setTimeout(function () {
            if (!el.matches(":active")) el.classList.remove("sb-on");
        }, ms));
    }

    function inTable(el) {
        return el && el.nodeType === 1 && el.closest && el.closest(".table-scroll");
    }

    document.addEventListener("scroll", function (e) {
        if (inTable(e.target)) show(e.target, 1000);
    }, true);

    document.addEventListener("mousemove", function (e) {
        var el = e.target;
        while (el && el.nodeType === 1 && inTable(el)) {
            var r = el.getBoundingClientRect();
            var canX = el.scrollWidth > el.clientWidth, canY = el.scrollHeight > el.clientHeight;
            if ((canY && e.clientX > r.right - EDGE && e.clientX <= r.right) || (canX && e.clientY > r.bottom - EDGE && e.clientY <= r.bottom)) {
                show(el, 1200);
                break;
            }
            if (el.classList.contains("table-scroll")) break;
            el = el.parentElement;
        }
    }, { passive: true });
})();
