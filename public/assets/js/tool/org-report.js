/*
 * Báo cáo thu chi (pages/admin/reports/index.blade.php). Bản rút gọn: report.js
 * - Ô tiền tự định dạng 1.234.567, ghi số thô vào ô ẩn ngay sau (est-input / act-input).
 * - Tính lại tổng: theo nhóm (summary-row), nhóm lớn (group-header), tổng thu / chi, cân đối dự kiến.
 * - Kế toán: thêm dòng, lưu 1 dòng (gửi email báo cập nhật), xóa dòng, Lưu tất cả, kéo đổi thứ tự trong nhóm.
 */
document.addEventListener("DOMContentLoaded", function () {
    const formatMoney = (amount) => new Intl.NumberFormat("vi-VN").format(amount);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    // ---------- Thông báo nhỏ ----------
    let toastEl, toastTimer;
    function toast(msg, isErr) {
        if (!toastEl) {
            toastEl = document.createElement("div");
            toastEl.setAttribute("role", "status");
            document.body.appendChild(toastEl);
        }
        toastEl.className = "rp-toast" + (isErr ? " rp-toast--err" : "");
        toastEl.textContent = msg;
        requestAnimationFrame(() => toastEl.classList.add("is-show"));
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove("is-show"), isErr ? 5000 : 2500);
    }
    const confirmBox = (msg, opts) => (window.uiConfirm ? window.uiConfirm(msg, opts) : Promise.resolve(window.confirm(msg)));

    async function send(payload) {
        const res = await fetch(url_reports_update, {
            method: "PUT",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.status !== "success") {
            const firstErr = data.errors ? Object.values(data.errors)[0] : null;
            throw new Error((firstErr && firstErr[0]) || data.message || "Không lưu được, vui lòng thử lại.");
        }
        return data;
    }

    // ---------- Có thay đổi chưa lưu ----------
    // Dòng đã sửa chưa lưu mang class is-dirty; lưu dòng / Lưu tất cả / xóa dòng thì bỏ
    const saveBar = document.getElementById("rp-savebar");
    const dirtyCount = document.getElementById("rp-dirty-count");
    const hasDirty = () => !!document.querySelector(".item-row.is-dirty");
    function refreshDirty() {
        const n = document.querySelectorAll(".item-row.is-dirty").length;
        if (saveBar) saveBar.hidden = n === 0;
        if (dirtyCount) dirtyCount.textContent = n;
    }
    function markDirty(row, on) {
        if (row) row.classList.toggle("is-dirty", on);
        refreshDirty();
    }
    document.addEventListener("change", (e) => { const row = e.target.closest(".item-row"); if (row) markDirty(row, true); });
    window.addEventListener("beforeunload", (e) => {
        if (!hasDirty()) return;
        e.preventDefault();
        e.returnValue = "";
    });

    // ---------- Chọn tháng / năm: tải lại trang ngay ----------
    const selMonth = document.getElementById("selectMonth");
    const selYear = document.getElementById("selectYear");
    [selMonth, selYear].forEach((el) => el && el.addEventListener("change", () => {
        location.href = "?month=" + encodeURIComponent(selMonth.value) + "&year=" + encodeURIComponent(selYear.value);
    }));

    // ---------- Ô tiền ----------
    document.querySelectorAll(".currency-input").forEach((input) => {
        const val = input.value.replace(/\D/g, "");
        input.value = val ? formatMoney(val) : "0";
    });

    document.addEventListener("input", function (e) {
        if (!e.target.classList.contains("currency-input")) return;
        const input = e.target;
        let rawVal = input.value.replace(/\D/g, "");
        if (rawVal === "") rawVal = "0";
        const cleanVal = parseInt(rawVal, 10).toString();
        input.value = formatMoney(cleanVal);
        input.nextElementSibling.value = cleanVal;
        markDirty(input.closest(".item-row"), true);

        // Nhập số đã thu / chi mà chưa có ngày thực tế: điền giờ hiện tại
        if (input.nextElementSibling.classList.contains("act-input")) {
            const actualDateInput = input.closest("tr").querySelector(".actual-date");
            if (parseInt(cleanVal, 10) > 0 && actualDateInput && !actualDateInput.value) {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                actualDateInput.value = now.toISOString().slice(0, 16);
            }
        }
        calculateFormulas();
    });

    document.addEventListener("focusin", function (e) {
        if (e.target.classList.contains("currency-input") && !e.target.readOnly) e.target.select();
    });

    const rowData = (tr) => ({
        id: tr.querySelector(".row-id").value,
        type: tr.dataset.type,
        summary_group: tr.dataset.group,
        name: tr.querySelector(".item-name").value.trim(),
        expected_date: tr.querySelector(".expected-date").value,
        actual_date: tr.querySelector(".actual-date").value,
        estimated_amount: tr.querySelector(".est-input").value,
        actual_amount: tr.querySelector(".act-input").value,
        _delete: tr.querySelector(".delete-flag") ? tr.querySelector(".delete-flag").value : 0,
    });

    // ---------- Lưu tất cả ----------
    const form = document.getElementById("reportForm");
    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btns = [...document.querySelectorAll(".rp-save-all")];
            if (!btns.length) return;
            const rows = [...document.querySelectorAll(".item-row")];
            const unnamed = rows.find((tr) => !tr.querySelector(".row-id").value && !tr.querySelector(".item-name").value.trim());
            if (unnamed) {
                unnamed.querySelector(".item-name").focus();
                toast("Dòng mới chưa có tên hạng mục.", true);
                return;
            }
            const setBtns = (text, disabled) => btns.forEach((b) => { b.querySelector("span").textContent = text; b.disabled = disabled; });
            setBtns("Đang lưu…", true);
            try {
                await send({ items: rows.map(rowData) });
                rows.forEach((tr) => tr.classList.remove("is-dirty"));
                refreshDirty();
                setBtns("Đã lưu", true);
                setTimeout(() => location.reload(), 500); // tải lại để có id dòng mới và trạng thái nhắc hạn
            } catch (err) {
                toast(err.message, true);
                setBtns("Lưu tất cả", false);
            }
        });
    }

    document.addEventListener("click", async function (e) {
        // ---------- Thêm dòng ----------
        const addBtn = e.target.closest(".add-row-btn");
        if (addBtn) {
            e.preventDefault();
            const tr = addBtn.closest("tr");
            const tempIndex = "new_" + Date.now();
            const newRow = document.createElement("tr");
            newRow.className = "item-row is-new" + (addBtn.dataset.sub === "1" ? " is-sub" : "");
            newRow.dataset.group = addBtn.dataset.group;
            newRow.dataset.type = addBtn.dataset.type;
            newRow.dataset.mainGroup = addBtn.dataset.main;
            newRow.innerHTML = `
                <td class="rp-col-name">
                    <input type="hidden" class="row-id" name="items[${tempIndex}][id]" value="">
                    <div class="rp-name"><input type="text" class="item-name" name="items[${tempIndex}][name]" placeholder="Tên hạng mục…" aria-label="Hạng mục"></div>
                </td>
                <td class="rp-col-date"><input type="date" class="date-input expected-date" name="items[${tempIndex}][expected_date]" aria-label="Ngày dự kiến"></td>
                <td class="rp-col-money">
                    <input type="text" class="currency-input" inputmode="numeric" value="0" aria-label="Số tiền dự kiến">
                    <input type="hidden" class="est-input" name="items[${tempIndex}][estimated_amount]" value="0">
                </td>
                <td class="rp-col-money">
                    <input type="text" class="currency-input" inputmode="numeric" value="0" aria-label="Số tiền thực tế">
                    <input type="hidden" class="act-input" name="items[${tempIndex}][actual_amount]" value="0">
                </td>
                <td class="rp-col-date"><input type="datetime-local" class="date-input actual-date" name="items[${tempIndex}][actual_date]" aria-label="Ngày thực tế"></td>
                <td class="rp-col-act">
                    <button type="button" class="rp-icon btn-save-row" title="Lưu dòng này (gửi email báo cập nhật)" aria-label="Lưu dòng"><i class="fas fa-check"></i></button>
                    <button type="button" class="rp-icon rp-icon--danger btn-delete-row" title="Xóa dòng này" aria-label="Xóa dòng"><i class="fas fa-trash-alt"></i></button>
                    <input type="hidden" name="items[${tempIndex}][_delete]" class="delete-flag" value="0">
                </td>`;
            tr.insertAdjacentElement("afterend", newRow);
            markDirty(newRow, true);
            newRow.querySelector(".item-name").focus();
            return;
        }

        // ---------- Lưu 1 dòng (gửi email báo cập nhật) ----------
        const saveBtn = e.target.closest(".btn-save-row");
        if (saveBtn) {
            e.preventDefault();
            const tr = saveBtn.closest("tr");
            const item = rowData(tr);
            if (!item.name) {
                tr.querySelector(".item-name").focus();
                toast("Nhập tên hạng mục trước khi lưu.", true);
                return;
            }
            item._delete = 0;
            saveBtn.classList.add("is-busy");
            saveBtn.disabled = true;
            try {
                const data = await send({ send_mail: true, items: [item] });
                if (!item.id && data.items) {
                    // Dòng mới: lấy id vừa tạo (dòng mới nhất cùng tên, cùng nhóm)
                    const created = data.items.filter((i) => i.name === item.name && i.summary_group === item.summary_group)
                        .sort((a, b) => b.id - a.id)[0];
                    if (created) {
                        tr.querySelector(".row-id").value = created.id;
                        tr.dataset.id = created.id;
                        tr.classList.remove("is-new");
                    }
                }
                markDirty(tr, false);
                saveBtn.classList.add("is-done");
                toast("Đã lưu \"" + item.name + "\".");
                setTimeout(() => saveBtn.classList.remove("is-done"), 1500);
            } catch (err) {
                toast(err.message, true);
            } finally {
                saveBtn.classList.remove("is-busy");
                saveBtn.disabled = false;
            }
            return;
        }

        // ---------- Xóa 1 dòng ----------
        const delBtn = e.target.closest(".btn-delete-row");
        if (delBtn) {
            e.preventDefault();
            const tr = delBtn.closest("tr");
            const id = tr.querySelector(".row-id").value;
            const name = tr.querySelector(".item-name").value.trim();
            if (!id) {
                tr.remove();
                refreshDirty();
                calculateFormulas();
                return;
            }
            const ok = await confirmBox("Xóa hạng mục \"" + (name || "chưa đặt tên") + "\" khỏi báo cáo tháng này?", { title: "Xóa dòng", ok: "Xóa", danger: true });
            if (!ok) return;
            delBtn.classList.add("is-busy");
            delBtn.disabled = true;
            try {
                await send({ items: [{ id: id, _delete: 1 }] });
                tr.remove();
                refreshDirty();
                calculateFormulas();
                toast("Đã xóa \"" + name + "\".");
            } catch (err) {
                toast(err.message, true);
                delBtn.classList.remove("is-busy");
                delBtn.disabled = false;
            }
        }
    });

    // ---------- Tính tổng ----------
    function calculateFormulas() {
        const groups = {};
        const mainGroups = {};
        const totals = { thu: { est: 0, act: 0 }, chi: { est: 0, act: 0 } };

        document.querySelectorAll(".item-row").forEach((row) => {
            const deleteFlag = row.querySelector(".delete-flag");
            if (deleteFlag && deleteFlag.value === "1") return;
            const est = parseFloat(row.querySelector(".est-input").value) || 0;
            const act = parseFloat(row.querySelector(".act-input").value) || 0;
            const g = (groups[row.dataset.group] = groups[row.dataset.group] || { est: 0, act: 0 });
            g.est += est;
            g.act += act;
            const m = (mainGroups[row.dataset.mainGroup] = mainGroups[row.dataset.mainGroup] || { est: 0, act: 0 });
            m.est += est;
            m.act += act;
            if (totals[row.dataset.type]) {
                totals[row.dataset.type].est += est;
                totals[row.dataset.type].act += act;
            }
        });

        document.querySelectorAll(".summary-row").forEach((row) => {
            const g = groups[row.dataset.group] || { est: 0, act: 0 };
            row.querySelector(".sum-est").textContent = formatMoney(g.est);
            row.querySelector(".sum-act").textContent = formatMoney(g.act);
            row.querySelector(".sum-diff").textContent = formatMoney(g.est - g.act);
        });

        document.querySelectorAll(".group-header").forEach((headerRow) => {
            const m = mainGroups[headerRow.dataset.mainHeader] || { est: 0, act: 0 };
            headerRow.querySelector(".header-sum-est").textContent = formatMoney(m.est);
            headerRow.querySelector(".header-sum-act").textContent = formatMoney(m.act);
        });

        ["thu", "chi"].forEach((type) => {
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = formatMoney(v); };
            set(`tong-${type}-est`, totals[type].est);
            set(`tong-${type}-act`, totals[type].act);
            set(`tong-${type}-diff`, totals[type].est - totals[type].act);
            set(`detail-tong-${type}-est`, totals[type].est);
            set(`detail-tong-${type}-act`, totals[type].act);
        });

        const balanceEst = totals.thu.est - totals.chi.est;
        const elBalance = document.getElementById("balance-est");
        if (elBalance) {
            elBalance.textContent = formatMoney(balanceEst);
            elBalance.classList.toggle("is-pos", balanceEst >= 0);
            elBalance.classList.toggle("is-neg", balanceEst < 0);
            // Thẻ cân đối: xanh khi dư, đỏ khi thiếu
            const card = document.getElementById("balance-card");
            if (card) {
                card.classList.toggle("rp-card--thu", balanceEst >= 0);
                card.classList.toggle("rp-card--neg", balanceEst < 0);
            }
        }
    }

    calculateFormulas();

    // ---------- Kéo đổi thứ tự (chỉ trong cùng nhóm) ----------
    const tbody = document.getElementById("sortable-table-body");
    if (tbody && window.Sortable && tbody.querySelector(".drag-handle")) {
        new Sortable(tbody, {
            handle: ".drag-handle",
            animation: 150,
            onMove: function (evt) {
                const a = evt.dragged;
                const b = evt.related;
                if (!b.classList.contains("item-row")) return false;
                if (a.dataset.group !== b.dataset.group || a.dataset.mainGroup !== b.dataset.mainGroup || a.dataset.type !== b.dataset.type) return false;
            },
            onEnd: function () {
                const orderedIds = [...tbody.querySelectorAll(".item-row")].map((row) => row.dataset.id).filter(Boolean);
                fetch(url_reports_update_order, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
                    body: JSON.stringify({ orders: orderedIds }),
                })
                    .then((res) => { if (!res.ok) throw new Error(); toast("Đã lưu thứ tự."); })
                    .catch(() => toast("Không lưu được thứ tự, tải lại trang rồi thử lại.", true));
            },
        });
    }
});
