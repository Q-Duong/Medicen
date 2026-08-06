document.addEventListener("DOMContentLoaded", function () {
    const formatMoney = (amount) =>
        new Intl.NumberFormat("vi-VN").format(amount);

    // Định dạng tiền
    document.querySelectorAll(".currency-input").forEach((input) => {
        let val = input.value.replace(/\D/g, "");
        input.value = val ? formatMoney(val) : "0";
    });

    document.addEventListener("input", function (e) {
        if (e.target.classList.contains("currency-input")) {
            let input = e.target;
            let rawVal = input.value.replace(/\D/g, "");
            if (rawVal === "") rawVal = "0";

            let cleanVal = parseInt(rawVal, 10).toString();
            input.value = formatMoney(cleanVal);
            input.nextElementSibling.value = cleanVal;

            if (input.nextElementSibling.classList.contains('act-input')) {
                let tr = input.closest('tr');
                let actualDateInput = tr.querySelector('.actual-date');
                
                if (parseInt(cleanVal) > 0 && actualDateInput && !actualDateInput.value) {
                    let now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    
                    actualDateInput.value = now.toISOString().slice(0, 16);
                }
            }

            calculateFormulas();
        }
    });

    document.addEventListener("focusin", function (e) {
        if (e.target.classList.contains("currency-input")) {
            e.target.select();
        }
    });

    // ==========================================
    // XỬ LÝ LƯU TẤT CẢ
    // ==========================================
    document
        .getElementById("reportForm")
        .addEventListener("submit", function (e) {
            e.preventDefault();
            const form = this;
            const btn = document.getElementById("btn-save-all");
            const originalText = btn.innerText;
            btn.innerText = "Đang lưu...";
            btn.disabled = true;

            const payload = { items: [] };
            document.querySelectorAll(".item-row").forEach((tr) => {
                payload.items.push({
                    id: tr.querySelector(".row-id").value,
                    type: tr.dataset.type,
                    summary_group: tr.dataset.group,
                    name: tr.querySelector(".item-name").value,
                    expected_date: tr.querySelector(".expected-date").value,
                    actual_date: tr.querySelector(".actual-date").value,
                    estimated_amount: tr.querySelector(".est-input").value,
                    actual_amount: tr.querySelector(".act-input").value,
                    _delete: tr.querySelector(".delete-flag")
                        ? tr.querySelector(".delete-flag").value
                        : 0,
                });
            });

            fetch(form.action, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content",
                    ),
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.status === "success") {
                        btn.innerText = "✓ Đã lưu tất cả";
                        setTimeout(() => {
                            window.location.reload();
                        }, 500); // Reload để reset lại ID và trạng thái chuẩn
                    }
                })
                .catch((err) => {
                    alert("Có lỗi xảy ra, vui lòng thử lại!");
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
        });

    // ==========================================
    // XỬ LÝ NÚT THÊM DÒNG
    // ==========================================
    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("add-row-btn")) {
            e.preventDefault();
            const btn = e.target;
            const tr = btn.closest("tr");
            const type = btn.dataset.type;
            const group = btn.dataset.group;
            const main = btn.dataset.main;
            const isSub = btn.dataset.sub === "1";

            const tempIndex = "new_" + Date.now();

            const newRow = document.createElement("tr");
            newRow.className = "item-row";
            newRow.dataset.group = group;
            newRow.dataset.type = type;
            newRow.dataset.mainGroup = main;
            newRow.style.backgroundColor = "#e8f6f3";

            newRow.innerHTML = `
                <input type="hidden" class="row-id" name="items[${tempIndex}][id]" value="">
                
                <td><input type="date" class="date-input expected-date" name="items[${tempIndex}][expected_date]"></td>
                
                <td style="padding-left: ${isSub ? "35px" : "15px"}; text-align: left;">
                    <input type="text" class="item-name" name="items[${tempIndex}][name]" placeholder="Nhập tên mục..." style="width: 100%; border: 1px dashed #bdc3c7; background: #fff; padding: 4px; outline: none; border-radius: 3px;" required>
                </td>
                
                <td>
                    <input type="text" class="currency-input" value="0">
                    <input type="hidden" class="est-input" name="items[${tempIndex}][estimated_amount]" value="0">
                </td>
                
                <td>
                    <input type="text" class="currency-input" value="0">
                    <input type="hidden" class="act-input" name="items[${tempIndex}][actual_amount]" value="0">
                </td>
                
                <td><input type="datetime-local" class="date-input actual-date" name="items[${tempIndex}][actual_date]"></td>
                
                <td style="text-align: center;">
                    <button type="button" class="action-btn btn-save-row" title="Lưu dòng này">Lưu</button>
                    <button type="button" class="action-btn btn-delete-row" title="Xóa dòng này">✕</button>
                    <input type="hidden" name="items[${tempIndex}][_delete]" class="delete-flag" value="0">
                </td>
            `;
            tr.insertAdjacentElement("afterend", newRow);
        }

        // ==========================================
        // XỬ LÝ LƯU (1 DÒNG - AJAX)
        // ==========================================
        if (e.target.classList.contains("btn-save-row")) {
            e.preventDefault();
            const btn = e.target;
            const tr = btn.closest("tr");

            const id = tr.querySelector(".row-id").value;
            const type = tr.dataset.type;
            const summary_group = tr.dataset.group;

            const payload = {
                send_mail: true,
                items: [
                    {
                        id: id,
                        type: type,
                        summary_group: summary_group,
                        name: tr.querySelector(".item-name").value,
                        expected_date: tr.querySelector(".expected-date").value,
                        actual_date: tr.querySelector(".actual-date").value,
                        estimated_amount: tr.querySelector(".est-input").value,
                        actual_amount: tr.querySelector(".act-input").value,
                        _delete: 0,
                    },
                ],
            };

            btn.innerText = "...";
            btn.disabled = true;

            fetch(url_reports_update, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content",
                    ),
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.status === "success") {
                        btn.innerText = "✓";
                        btn.style.backgroundColor = "#27ae60";
                        setTimeout(() => {
                            btn.innerText = "Lưu";
                            btn.style.backgroundColor = "";
                            btn.disabled = false;
                        }, 1500);

                        if (!id && data.items) {
                            const rowName =
                                tr.querySelector(".item-name").value;
                            const newItem = data.items
                                .reverse()
                                .find((item) => item.name === rowName);
                            if (newItem) {
                                tr.querySelector(".row-id").value = newItem.id;
                            }
                        }
                    }
                })
                .catch((err) => {
                    alert("Có lỗi xảy ra, vui lòng thử lại!");
                    btn.innerText = "Lưu";
                    btn.disabled = false;
                });
        }

        // ==========================================
        // XỬ LÝ XÓA (1 DÒNG - AJAX)
        // ==========================================
        if (e.target.classList.contains("btn-delete-row")) {
            e.preventDefault();
            if (
                confirm(
                    "Bạn có chắc muốn xóa dòng này? Dữ liệu sẽ bị xóa khỏi cơ sở dữ liệu.",
                )
            ) {
                const btn = e.target;
                const tr = btn.closest("tr");
                const id = tr.querySelector(".row-id").value;

                if (!id) {
                    tr.remove();
                    calculateFormulas();
                    return;
                }

                btn.innerText = "...";

                fetch(url_reports_update, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                            "content",
                        ),
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        items: [{ id: id, _delete: 1 }],
                    }),
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status === "success") {
                            tr.remove();
                            calculateFormulas();
                        }
                    });
            }
        }
    });

    // ==========================================
    // TÍNH TOÁN CÔNG THỨC
    // ==========================================
    function calculateFormulas() {
        const groups = {};
        const mainGroups = {};
        let totals = { thu: { est: 0, act: 0 }, chi: { est: 0, act: 0 } };

        document.querySelectorAll(".item-row").forEach((row) => {
            const deleteFlag = row.querySelector(".delete-flag");
            if (deleteFlag && deleteFlag.value === "1") return;

            const groupName = row.dataset.group;
            const mainGroupName = row.dataset.mainGroup;
            const type = row.dataset.type;
            const est = parseFloat(row.querySelector(".est-input").value) || 0;
            const act = parseFloat(row.querySelector(".act-input").value) || 0;

            if (!groups[groupName]) groups[groupName] = { est: 0, act: 0 };
            groups[groupName].est += est;
            groups[groupName].act += act;

            if (!mainGroups[mainGroupName])
                mainGroups[mainGroupName] = { est: 0, act: 0 };
            mainGroups[mainGroupName].est += est;
            mainGroups[mainGroupName].act += act;

            totals[type].est += est;
            totals[type].act += act;
        });

        document.querySelectorAll(".summary-row").forEach((row) => {
            const grp = row.dataset.group;
            if (groups[grp]) {
                row.querySelector(".sum-est").innerText = formatMoney(
                    groups[grp].est,
                );
                row.querySelector(".sum-act").innerText = formatMoney(
                    groups[grp].act,
                );
                row.querySelector(".sum-diff").innerText = formatMoney(
                    groups[grp].est - groups[grp].act,
                );
            }
        });

        document.querySelectorAll(".group-header").forEach((headerRow) => {
            const mGrp = headerRow.dataset.mainHeader;
            if (mainGroups[mGrp]) {
                headerRow.querySelector(".header-sum-est").innerText =
                    formatMoney(mainGroups[mGrp].est);
                headerRow.querySelector(".header-sum-act").innerText =
                    formatMoney(mainGroups[mGrp].act);
            }
        });

        ["thu", "chi"].forEach((type) => {
            document.getElementById(`tong-${type}-est`).innerText = formatMoney(
                totals[type].est,
            );
            document.getElementById(`tong-${type}-act`).innerText = formatMoney(
                totals[type].act,
            );
            document.getElementById(`tong-${type}-diff`).innerText =
                formatMoney(totals[type].est - totals[type].act);

            const detailEst = document.getElementById(
                `detail-tong-${type}-est`,
            );
            const detailAct = document.getElementById(
                `detail-tong-${type}-act`,
            );
            if (detailEst) detailEst.innerText = formatMoney(totals[type].est);
            if (detailAct) detailAct.innerText = formatMoney(totals[type].act);
        });

        const balanceEst = totals["thu"].est - totals["chi"].est;
        const elBalanceEst = document.getElementById("balance-est");

        if (elBalanceEst) {
            elBalanceEst.innerText = formatMoney(balanceEst);
            elBalanceEst.style.color = balanceEst >= 0 ? "#27ae60" : "#e74c3c";
        }
    }

    calculateFormulas();

    var tbody = document.getElementById('sortable-table-body');
    
    if(tbody) {
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 150,

            onMove: function (evt) {
                var draggedRow = evt.dragged;
                var targetRow = evt.related;

                if (!targetRow.classList.contains('item-row')) {
                    return false; 
                }

                var draggedGroup = draggedRow.getAttribute('data-group');
                var targetGroup = targetRow.getAttribute('data-group');
                
                var draggedMain = draggedRow.getAttribute('data-main-group');
                var targetMain = targetRow.getAttribute('data-main-group');
                
                var draggedType = draggedRow.getAttribute('data-type');
                var targetType = targetRow.getAttribute('data-type');

                if (draggedGroup !== targetGroup || draggedMain !== targetMain || draggedType !== targetType) {
                    return false; 
                }
            },

            onEnd: function (evt) {
                let orderedIds = [];
                tbody.querySelectorAll('.item-row').forEach(function(row) {
                    orderedIds.push(row.getAttribute('data-id'));
                });

                fetch(url_reports_update_order, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            "content",
                        ),
                    },
                    body: JSON.stringify({ orders: orderedIds })
                })
                .then(response => response.json())
                .then(data => {
                    
                      
                });
            }
        });
    }
});
