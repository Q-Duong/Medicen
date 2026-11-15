// Phần Đơn hàng: tính tiền trên form, danh sách đơn, ô chọn người liên hệ cũ, nhóm đi chung, xác nhận đổi ngày.
// File nguồn (dễ đọc). Bản nạp lên trang là order.js (rút gọn từ file này).


$(".order_cost").on({
    keyup: function () {
        formatCurrency($(this));
    },
    blur: function () {
        formatCurrency($(this), "blur");
    },
    // Thành tiền = số lượng × đơn giá: xem mục "Số lượng × đơn giá" bên dưới (tính cả khi gõ số lượng)
});

$(".order_price").on({
    keyup: function () {
        formatCurrency($(this));
    },
    blur: function () {
        formatCurrency($(this), "blur");
    },
    input: function () {
        var order_price = $(this).val();
        var order_price_format = order_price
            .replace(/\D/g, "")
            .replace(/\B(?=(\d{3})+(?!\d))/g, "");
    },
});

$(".order_all_in_one").change(function () {
    var value = $(this).val();
    if (value == 0) {
        $(".block-order-cost").removeClass("hidden");
    } else {
        $(".block-order-cost").addClass("hidden");
    }
});

$(document).on("change", ".unit-id", function () {
    var value = $(this).val();
    if (value == 5) {
        $(".suggest").removeClass("hidden");
    } else{
        $(".suggest").addClass("hidden");
    }
});

// ==========================================================================
// Danh sách đơn hàng: bấm thẻ thống kê để lọc theo trạng thái
// ==========================================================================

function filterByCardStatus(status) {
    document.getElementById('statusInput').value = status;
    document.getElementById('filterForm').submit();
}

// Ô lọc liên kết: đổi một ô (đơn vị, nhóm, ngày, từ khóa) thì các ô chọn còn lại chỉ gợi ý giá trị có đơn
// theo những ô đã chọn (kèm số đơn). Giá trị đang chọn mà không còn đơn thì vẫn giữ, ghi "(0)".
(function () {
    const form = document.getElementById('filterForm');
    if (!form || !form.dataset.optionsUrl) return;
    const selects = {
        units: form.querySelector('[data-filter-opt="units"]'),
        groups: form.querySelector('[data-filter-opt="groups"]'),
    };
    const FIXED = { units: 1, groups: 3 }; // số lựa chọn cố định ở đầu (Tất cả / Chỉ đơn đi chung / Không đi chung)
    let timer = null, seq = 0;

    function rebuild(key, list) {
        const sel = selects[key];
        if (!sel) return;
        const current = sel.value;
        while (sel.options.length > FIXED[key]) sel.remove(FIXED[key]);
        let seen = Array.from(sel.options).some((o) => o.value === current);
        list.forEach((it) => {
            const o = document.createElement('option');
            o.value = it.value;
            o.textContent = it.value + ' (' + it.count + (key === 'groups' ? ' đơn' : '') + ')';
            if (it.value === current) { o.selected = true; seen = true; }
            sel.appendChild(o);
        });
        if (!seen && current) {
            const o = document.createElement('option');
            o.value = current;
            o.textContent = current + ' (0)';
            o.selected = true;
            sel.appendChild(o);
        }
    }

    async function refresh() {
        const my = ++seq;
        const params = new URLSearchParams(new FormData(form));
        form.classList.add('is-loading-options');
        try {
            const res = await fetch(form.dataset.optionsUrl + '?' + params.toString(), { headers: { Accept: 'application/json' } });
            if (!res.ok || my !== seq) return;
            const data = await res.json();
            rebuild('units', data.units || []);
            rebuild('groups', data.groups || []);
            // Số đơn chưa hoàn thành / đã đủ thông tin theo các ô lọc khác
            const cs = form.querySelector('[data-filter-opt="complete"]');
            if (cs && data.complete) {
                cs.querySelector('option[value="0"]').textContent = 'Chưa hoàn thành (' + data.complete['0'] + ')';
                cs.querySelector('option[value="1"]').textContent = 'Đã đủ thông tin (' + data.complete['1'] + ')';
            }
        } catch (e) {
            // mất mạng: giữ nguyên gợi ý cũ
        } finally {
            if (my === seq) form.classList.remove('is-loading-options');
        }
    }
    const later = (ms) => { clearTimeout(timer); timer = setTimeout(refresh, ms); };

    form.addEventListener('change', (e) => {
        if (e.target.matches('select, input[type=date]')) later(50);
    });
    const kw = form.querySelector('[name=keyword]');
    if (kw) kw.addEventListener('input', () => later(400));
})();

// ==========================================================================
// Form tạo / sửa / copy đơn: chọn người liên hệ cũ (Họ tên + SĐT) theo đơn vị
// Chọn đơn vị xong, bấm vào ô họ tên hoặc SĐT sẽ xổ danh sách người liên hệ ở các đơn cũ của đơn vị đó
// (mới nhất trước); chọn một dòng thì điền cả họ tên lẫn SĐT. Vẫn gõ tay được người mới.
// ==========================================================================

(function () {
    const init = () => {
        const name = document.querySelector('input[name=customer_name]');
        const phone = document.querySelector('input[name=customer_phone]');
        const unit = document.querySelector('[name=unit_id]');
        if (!name || !phone || !unit) return;
        const form = name.closest('form');
        // Đường dẫn API lấy từ form: data-customer-url="{{ route('order.customer_suggestions') }}"
        const url = form && form.dataset.customerUrl;
        if (!url) return;
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const norm = s => String(s ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
        const dm = d => d ? d.slice(8, 10) + '/' + d.slice(5, 7) + '/' + d.slice(0, 4) : '';
        let list = [], loadedFor = null, active = -1, owner = null;

        // Menu + gợi ý "N người liên hệ cũ" gắn dưới ô họ tên
        const menu = document.createElement('div');
        menu.className = 'cp-menu';
        menu.hidden = true;
        const hint = document.createElement('button');
        hint.type = 'button';
        hint.className = 'cp-hint';
        hint.hidden = true;
        // Bọc ô nhập để danh sách xổ ngay dưới ô (không bị lệch bởi dòng báo lỗi / gợi ý)
        [name, phone].forEach(input => {
            // Tắt gợi ý tự điền của trình duyệt (Chrome bỏ qua "off" với ô tên / SĐT, nên dùng giá trị lạ)
            input.setAttribute('autocomplete', 'medicen-' + input.name);
            input.setAttribute('autocorrect', 'off');
            input.setAttribute('spellcheck', 'false');
            const wrap = document.createElement('span');
            wrap.className = 'cp-wrap';
            wrap.style.display = 'block';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
        });
        name.parentElement.appendChild(menu);
        name.parentElement.parentElement.insertBefore(hint, name.parentElement.nextSibling);

        async function load() {
            if (!unit.value || loadedFor === unit.value) return;
            loadedFor = unit.value;
            try {
                const res = await fetch(url + '?unit_id=' + encodeURIComponent(unit.value), { headers: { 'Accept': 'application/json' } });
                list = res.ok ? await res.json() : [];
            } catch (e) { list = []; }
            hint.hidden = !list.length || (name.value && phone.value);
            hint.innerHTML = '<i class="fas fa-address-book"></i> ' + list.length + ' người liên hệ cũ của đơn vị này';
        }

        function render(input) {
            owner = input;
            const q = norm(input.value);
            const rows = list.filter(c => !q || norm(c.name).includes(q) || String(c.phone || '').includes(input.value.trim()));
            active = -1;
            menu.innerHTML = '<div class="cp-head"><span>Liên hệ ở các đơn cũ</span><span>' + rows.length + '/' + list.length + '</span></div>'
                + (rows.length ? rows.map((c, i) => '<button type="button" class="cp-item" data-i="' + list.indexOf(c) + '">'
                    + '<span class="cp-avatar">' + esc((c.name || '?').trim().split(/\s+/).pop().charAt(0).toUpperCase()) + '</span>'
                    + '<span class="cp-main"><b>' + esc(c.name) + '</b><small>' + esc(c.phone || 'Không có SĐT') + '</small></span>'
                    + '<span class="cp-meta">' + c.orders + ' đơn<br>' + dm(c.last_day) + '</span></button>').join('')
                    : '<div class="cp-empty">Không có liên hệ khớp, cứ gõ để nhập người mới.</div>');
            // Menu đặt dưới ô đang gõ
            input.parentElement.appendChild(menu);
            menu.hidden = !list.length;
        }

        function pick(c) {
            name.value = c.name || '';
            phone.value = c.phone || '';
            [name, phone].forEach(i => i.dispatchEvent(new Event('input', { bubbles: true })));
            menu.hidden = true;
            hint.hidden = true;
        }

        [name, phone].forEach(input => {
            // Bấm vào ô (kể cả khi ô đang được chọn sẵn) hoặc focus bằng Tab đều mở danh sách
            input.addEventListener('focus', async () => { await load(); render(input); });
            input.addEventListener('click', async () => { if (menu.hidden || owner !== input) { await load(); render(input); } });
            input.addEventListener('input', () => { if (list.length) render(input); });
            input.addEventListener('keydown', e => {
                if (menu.hidden) return;
                const items = [...menu.querySelectorAll('.cp-item')];
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                    items.forEach((x, i) => x.classList.toggle('is-active', i === active));
                    items[active]?.scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter' && active >= 0) {
                    e.preventDefault();
                    pick(list[items[active].dataset.i]);
                } else if (e.key === 'Escape') {
                    menu.hidden = true;
                }
            });
        });
        menu.addEventListener('mousedown', e => {
            const b = e.target.closest('.cp-item');
            if (b) { e.preventDefault(); pick(list[b.dataset.i]); }
        });
        hint.addEventListener('click', () => { name.focus(); });
        document.addEventListener('mousedown', e => { if (!menu.contains(e.target) && e.target !== name && e.target !== phone) menu.hidden = true; });

        // Đổi đơn vị: xóa họ tên + SĐT cũ để chọn lại người liên hệ của đơn vị mới
        // (chỉ khi đơn vị thật sự đổi; sự kiện change lặp từ select2 / lúc tải trang thì bỏ qua)
        let lastUnit = unit.value;
        const onUnit = () => {
            if (unit.value === lastUnit) return;
            lastUnit = unit.value;
            name.value = '';
            phone.value = '';
            [name, phone].forEach(i => i.dispatchEvent(new Event('input', { bubbles: true })));
            menu.hidden = true;
            loadedFor = null;
            load();
        };
        unit.addEventListener('change', onUnit);
        if (window.jQuery) window.jQuery(unit).on('change', onUnit);
        load();
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();

// ==========================================================================
// Form tạo / sửa / copy đơn: khung "Đi chung với đơn khác" (nhóm đơn đi chung)
// Gợi ý các đơn cùng đơn vị, cùng ngày; Sale tích đơn nào đi chung thì vào chung nhóm (có mã nhóm).
// ==========================================================================

(function () {
    const box = document.getElementById('order-group');
    if (!box) return;
    const form = box.closest('form');
    const list = document.getElementById('og-list');
    const sub = document.getElementById('og-sub');
    const unit = form.querySelector('[name=unit_id]');
    const date = form.querySelector('[name=ord_start_day]');
    // Các đơn đang chung nhóm (khi sửa đơn), lấy từ data-preset="[1,2]"
    let preset = new Set(JSON.parse(box.dataset.preset || '[]'));
    let rows = [];
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dm = d => d ? d.slice(8, 10) + '/' + d.slice(5, 7) : '';

    function paint() {
        const checked = rows.filter(r => preset.has(r.id)).length;
        box.classList.toggle('is-suggest', rows.length > 0 && checked === 0);
        sub.textContent = !rows.length ? '' : checked
            ? 'Đi chung với ' + checked + ' đơn khác của đơn vị này ngày ' + dm(date.value) + '.'
            : 'Đơn vị này đã có ' + rows.length + ' đơn khác ngày ' + dm(date.value) + '. Nếu đi chung chuyến, hãy tích chọn bên dưới.';
    }

    async function load() {
        if (!unit.value || !date.value) { box.hidden = true; return; }
        try {
            const q = new URLSearchParams({ unit_id: unit.value, date: date.value });
            if (box.dataset.exclude) q.set('exclude', box.dataset.exclude);
            const res = await fetch(box.dataset.url + '?' + q, { headers: { 'Accept': 'application/json' } });
            rows = res.ok ? await res.json() : [];
        } catch (e) { rows = []; }
        // Đổi đơn vị / ngày: bỏ các đơn đã chọn không còn trong danh sách
        preset = new Set([...preset].filter(id => rows.some(r => r.id === id)));
        box.hidden = !rows.length;
        const code = document.getElementById('og-code');
        if (code) code.hidden = !rows.some(r => preset.has(r.id));
        list.innerHTML = rows.map(r => '<label class="og__row"><input type="checkbox" name="group_with[]" value="' + r.id + '"'
            + (preset.has(r.id) ? ' checked' : '') + (r.group_id ? ' data-group="' + r.group_id + '"' : '') + '>'
            + '<span class="grow"><b>#' + r.id + ' · ' + esc(r.company || '') + '</b>'
            + '<small>' + esc(r.session) + ' · ' + r.quantity + ' cas · ' + esc(r.service || '') + '</small></span>'
            + (r.surcharge ? '<span class="og__tag og__tag--sur">Phụ thu</span>' : '')
            + (r.group_code ? '<span class="og__tag og__tag--grp">Nhóm ' + esc(r.group_code) + '</span>' : '')
            + (r.car ? '<span class="og__tag og__tag--car">' + esc(r.car) + '</span>' : '')
            + '</label>').join('');
        paint();
    }

    // Tích một đơn đang có nhóm: tích luôn cả nhóm đó (đi chung với cả nhóm)
    list.addEventListener('change', e => {
        const cb = e.target;
        if (!cb.matches('input[type=checkbox]')) return;
        const id = Number(cb.value);
        cb.checked ? preset.add(id) : preset.delete(id);
        if (cb.checked && cb.dataset.group) {
            list.querySelectorAll('input[data-group="' + cb.dataset.group + '"]').forEach(x => { x.checked = true; preset.add(Number(x.value)); });
        }
        paint();
    });
    date.addEventListener('change', load);
    unit.addEventListener('change', load);
    // select2 phát sự kiện qua jQuery (jQuery có thể nạp sau khung này)
    const start = () => { if (window.jQuery) window.jQuery(unit).on('change', load); load(); };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
})();

// ==========================================================================
// Form sửa đơn: đơn đã xếp lịch xe mà đổi ngày / buổi chụp thì hỏi xác nhận (lịch xe sẽ bị hủy)
// ==========================================================================

// Đơn đã xếp lịch: đổi ngày / buổi chụp thì hỏi xác nhận vì lịch xe sẽ bị hủy
(function () {
    const form = document.getElementById('order-edit-form');
    if (!form || !form.dataset.scheduled) return;
    const note = document.getElementById('order-sched-note');
    const date = form.querySelector('[name=ord_start_day]');
    const session = form.querySelector('[name=ord_session]');
    const changed = () => date.value !== form.dataset.oldDate || (session.value || 'Cả ngày') !== form.dataset.oldSession;
    const mark = () => note && note.classList.toggle('is-changed', changed());
    date.addEventListener('change', mark);
    session.addEventListener('change', mark);
    const dm = d => d ? d.slice(8, 10) + '/' + d.slice(5, 7) + '/' + d.slice(0, 4) : '';
    form.addEventListener('submit', async e => {
        if (!changed() || form.dataset.confirmedReschedule) return;
        // Nhớ nút đã bấm (name="complete" = 0 / 1) để gửi lại đúng nút sau khi xác nhận
        const submitter = e.submitter;
        e.preventDefault();
        e.stopImmediatePropagation();
        const ok = await uiConfirm(
            'Ngày chụp: ' + dm(form.dataset.oldDate) + ' (' + form.dataset.oldSession + ') → ' + dm(date.value) + ' (' + (session.value || 'Cả ngày') + ').\n'
            + 'Lịch xe hiện tại (' + form.dataset.scheduleText + ') sẽ bị hủy, đơn chuyển về "Chưa lên lịch" để Kỹ thuật xếp lại.',
            { title: 'Đổi ngày sẽ hủy lịch xe', ok: 'Đổi ngày và hủy lịch', danger: true });
        if (!ok) return;
        form.dataset.confirmedReschedule = '1';
        if (submitter && form.requestSubmit) {
            form.requestSubmit(submitter);
        } else {
            // Trình duyệt cũ: thêm giá trị của nút vào form trước khi gửi
            if (submitter && submitter.name) {
                const h = document.createElement('input');
                h.type = 'hidden'; h.name = submitter.name; h.value = submitter.value;
                form.appendChild(h);
            }
            form.submit();
        }
    }, true);
})();

// ==========================================================================
// Form tạo / sửa / copy đơn: tiền theo đúng quy tắc trang Công nợ
// - "Thành tiền chiết khấu" và "Lợi nhuận" tự tính ngay khi nhập (máy chủ tính lại khi lưu, cùng công thức
//   với RowCalculator.php và calculateRow ở org-accountant.js).
// - Đơn đã cập nhật doanh thu / đã chốt: khóa các ô ảnh hưởng đến tiền.
// ==========================================================================

(function () {
    const form = document.querySelector('form.order-form');
    if (!form) return;
    const field = (name) => form.querySelector('[name="' + name + '"]');
    const discountOut = form.querySelector('.om-discount');
    const profitOut = form.querySelector('.om-profit');
    const fmt = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(n || 0));
    const num = (v) => parseFloat(String(v || '').replace(/\./g, '').replace(/,/g, '')) || 0;

    // Chiết khấu từ ô "% chiết khấu": "5%" (chia 1,1 trừ khi VAT "không"), "10.000/ca" (× số lượng), "500.000" (số tiền)
    function discountFrom(raw, price, qty, vat) {
        raw = String(raw || '');
        const lower = raw.toLowerCase();
        const clean = raw.replace(/\./g, '').replace(/,/g, '.');
        const vatClean = String(vat || '').toLowerCase().trim();
        let amount = 0;
        if (lower.includes('%')) {
            const rate = parseFloat(clean.replace('%', '')) || 0;
            const divisor = vatClean.includes('không') || vatClean.includes('khong') ? 1 : 1.1;
            amount = (price / divisor) * (rate / 100);
        } else if (lower.includes('/ca')) {
            amount = (parseFloat(clean.toLowerCase().replace('/ca', '')) || 0) * qty;
        } else if (!isNaN(parseFloat(clean)) && clean.trim() !== '') {
            amount = parseFloat(clean);
        }
        return Math.round(amount);
    }

    const locked = !!form.querySelector('[data-money-locked]');
    function recalc() {
        // Đơn đã khóa tiền: giữ nguyên số đang lưu (có thể kế toán nhập tay), không tính lại
        if (!discountOut || locked) return;
        const price = num(field('order_price') && field('order_price').value);
        const qty = num(field('order_quantity') && field('order_quantity').value);
        const discount = discountFrom(field('order_percent_discount') && field('order_percent_discount').value, price, qty, field('order_vat') && field('order_vat').value);
        discountOut.value = fmt(discount);
        if (profitOut) profitOut.value = fmt(price - discount);
    }
    ['order_quantity', 'order_cost', 'order_price', 'order_percent_discount', 'order_vat', 'order_all_in_one'].forEach((n) => {
        const el = field(n);
        if (el) {
            el.addEventListener('input', () => setTimeout(recalc, 0));
            el.addEventListener('change', () => setTimeout(recalc, 0));
        }
    });
    // Tổng tiền được order.js tính bằng jQuery (SL × ĐG): nghe thêm sự kiện jQuery để cập nhật theo
    if (window.jQuery) window.jQuery(form).on('keyup input change', '.order_cost, .order_quantity, .order_price', () => setTimeout(recalc, 0));
    // Form sửa đơn đã có số đang lưu (máy chủ điền sẵn): chỉ tính lại khi người dùng sửa ô tiền
    if (discountOut && discountOut.value === '') recalc();

    // Khóa ô tiền khi đơn đã cập nhật doanh thu / đã chốt
    if (locked) {
        ['order_quantity', 'order_cost', 'order_price', 'order_percent_discount', 'order_vat'].forEach((n) => {
            const el = field(n);
            if (el) { el.readOnly = true; el.classList.add('is-money-locked'); el.title = 'Đơn đã cập nhật doanh thu: không sửa được'; }
        });
        const aio = field('order_all_in_one');
        if (aio) {
            // Ô chọn bị khóa thì không gửi lên được: thêm ô ẩn giữ nguyên giá trị (máy chủ cũng giữ số cũ)
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'order_all_in_one'; hidden.value = aio.value;
            aio.after(hidden);
            aio.disabled = true;
            aio.removeAttribute('name');
            aio.classList.add('is-money-locked');
        }
    }
})();


// ==========================================================================
// Số lượng × đơn giá (form tạo / sửa / copy đơn)
// - Gõ SỐ LƯỢNG hay ĐƠN GIÁ đều tự tính Thành tiền ngay (trước đây chỉ khi gõ đơn giá) rồi tính lại chiết khấu,
//   lợi nhuận; số lượng tự thêm dấu chấm như ô tiền (1.200). Đơn trọn gói thì không tự tính Thành tiền.
// - Ô "% chiết khấu": gõ số tiền (không có %) từ 4 chữ số trở lên thì tự thêm dấu chấm (5000 -> 5.000,
//   20000/ca -> 20.000/ca); gõ phần trăm (5%, 2,5%) giữ nguyên.
// ==========================================================================
(function () {
    const form = document.querySelector('form.order-form');
    if (!form) return;
    const el = (name) => form.querySelector('[name="' + name + '"]');
    const digits = (v) => String(v || '').replace(/\D/g, '');
    const fmt = (d) => (d === '' ? '' : Number(d).toLocaleString('vi-VN'));

    // Giữ con trỏ đúng chỗ sau khi thêm / bớt dấu chấm (đếm số chữ số đứng trước con trỏ)
    function reformat(input, formatted) {
        if (input.value === formatted) return;
        const pos = input.selectionStart == null ? input.value.length : input.selectionStart;
        const before = digits(input.value.slice(0, pos)).length;
        input.value = formatted;
        let i = 0, seen = 0;
        while (i < formatted.length && seen < before) {
            if (/\d/.test(formatted[i])) seen++;
            i++;
        }
        try { input.setSelectionRange(i, i); } catch (e) {}
    }

    function allInOne() {
        const a = el('order_all_in_one');
        return a && String(a.value) === '1';
    }

    function computePrice() {
        const qty = el('order_quantity'), cost = el('order_cost'), price = el('order_price');
        if (!qty || !cost || !price || allInOne() || price.readOnly) return;
        const q = digits(qty.value), c = digits(cost.value);
        price.value = q !== '' && c !== '' ? fmt(String(Number(q) * Number(c))) : '';
        // Báo cho phần tính chiết khấu / lợi nhuận
        price.dispatchEvent(new Event('input', { bubbles: true }));
    }

    const qty = el('order_quantity');
    if (qty) {
        if (!qty.readOnly && qty.value) qty.value = fmt(digits(qty.value));
        qty.addEventListener('input', () => {
            if (qty.readOnly) return;
            reformat(qty, fmt(digits(qty.value)));
            computePrice();
        });
    }
    const cost = el('order_cost');
    if (cost) cost.addEventListener('input', () => setTimeout(computePrice, 0));

    const pct = el('order_percent_discount');
    if (pct) {
        pct.addEventListener('input', () => {
            if (pct.readOnly) return;
            const v = pct.value;
            if (v.includes('%')) return;
            const m = v.match(/^\s*([\d.]+)\s*(\/\s*ca)?\s*$/i);
            if (!m) return;
            const d = digits(m[1]);
            const out = d.length >= 4 ? fmt(d) + (m[2] ? '/ca' : '') : (m[2] ? d + m[2].replace(/\s+/g, '') : v);
            if (out !== v) reformat(pct, out);
        });
    }
})();
