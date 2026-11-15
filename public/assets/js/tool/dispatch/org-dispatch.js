// Trang Xếp lịch xe (admin/xep-lich): gợi ý / xếp thủ công / sửa lịch, giữ chỗ, đổi tuần, chấm công, ảnh tuần.
// File nguồn (dễ đọc). Bản nạp lên trang là dispatch.js (rút gọn từ file này).
// Cấu hình (đường dẫn API, tên KTV thuê) đọc từ data-config của <div id="dispatch">.

(function () {
    // Đường dẫn / cấu hình lấy từ data-config trên khối .dp (Blade điền sẵn)
    const root = document.getElementById('dispatch');
    if (!root) return;
    const CFG = JSON.parse(root.dataset.config || '{}');
    const suggestUrl = CFG.suggestUrl;
    const applyUrl = CFG.applyUrl;
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const orderUrl = id => CFG.orderBase + '/' + id;
    const weekUrl = CFG.weekUrl;
    const pageUrl = CFG.pageUrl;
    const weekView = document.getElementById('week-view');

    const btn = document.getElementById('btn-suggest');
    const panel = document.getElementById('sug');
    const list = document.getElementById('sug-list');
    const note = document.getElementById('sug-note');
    const src = document.getElementById('sug-src');
    const applyBtn = document.getElementById('sug-apply');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dm = d => d ? d.slice(8, 10) + '/' + d.slice(5, 7) : '';
    const dow = d => ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][new Date(d + 'T00:00:00').getDay()];
    let items = [];

    async function api(url, body, method) {
        const res = await fetch(url, {
            method: method || 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(body || {}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || ('Lỗi ' + res.status));
        return data;
    }

    const fmt = n => Number(n || 0).toLocaleString('vi-VN');
    // Ô chọn kèm trạng thái: người bận thì khóa, người xa thì ghi chú; hiện số ca trong tháng
    function select(name, list, value, allowEmpty) {
        const opts = (allowEmpty ? ['<option value=""' + (!value ? ' selected' : '') + '>' + esc(allowEmpty) + '</option>'] : [])
            .concat(list.map(o => {
                const selected = String(o.value) === String(value ?? '');
                const parts = [o.label];
                if (o.cases !== undefined && o.cases !== null) parts.push(fmt(o.cases) + ' ca');
                if (o.cchn) parts.push('CCHN');
                if (o.state === 'busy') parts.push('bận');
                if (o.state === 'far') parts.push('⚠ xa' + (o.km ? ' ' + fmt(o.km) + ' km' : ''));
                if (o.state !== 'busy' && o.note && o.note !== 'thuê ngoài' && o.state !== 'far') parts.push(o.note);
                return '<option value="' + esc(o.value) + '"' + (selected ? ' selected' : '') + (o.state === 'busy' && !selected ? ' disabled' : '')
                    + ' title="' + esc(o.note || '') + '">' + esc(parts.join(' · ')) + '</option>';
            }));
        return '<select data-f="' + name + '">' + opts.join('') + '</select>';
    }

    // Khối "Chấm công sau khi chụp" trong Sửa lịch: số ca thực tế, TX phụ KTV, công tác trên 250km, ghi chú KTV
    function actualBlock(it) {
        const a = it.actual;
        const hired = [it.ktv1, it.ktv2].includes(CFG.hiredKtv) || !a.has_ktv;
        const chip = a.order_quantity_draft
            ? '<span class="act-chip">Đã nhập ' + fmt(a.order_quantity_draft) + ' ca</span>'
            : '<span class="act-chip act-chip--miss">' + (a.past ? 'Chưa nhập số ca thực tế' : 'Chưa chụp') + '</span>';
        return '<details class="sug__actual"' + (a.past ? ' open' : '') + '>'
            + '<summary><i class="fas fa-chevron-right"></i><i class="far fa-calendar-check"></i> Chấm công sau khi chụp ' + chip
            + (a.driver_assistance ? '<span class="act-chip">TX phụ KTV</span>' : '') + (a.work_over_250 ? '<span class="act-chip">C.tác &gt; 250km</span>' : '') + '</summary>'
            + '<div class="act-grid">'
            + '<label>Số ca thực tế<input type="number" min="0" inputmode="numeric" data-a="order_quantity_draft" value="' + (a.order_quantity_draft ?? '') + '" placeholder="' + it.quantity + ' (dự kiến)"></label>'
            + '<label class="chk"><input type="checkbox" data-a="driver_assistance"' + (a.driver_assistance ? ' checked' : '') + '> Tài xế phụ KTV <small>(đi cùng KTV thuê / phụ chụp)</small></label>'
            + '<label class="chk"><input type="checkbox" data-a="work_over_250"' + (a.work_over_250 ? ' checked' : '') + '> Công tác trên 250km</label>'
            + '<label class="wide">Ghi chú KTV<input type="text" maxlength="2000" data-a="order_note_ktv" value="' + esc(a.order_note_ktv || '') + '"></label>'
            + '<div class="act-foot"><span class="act-res"><i class="fas fa-info-circle"></i> Được lưu cùng khi bấm "Lưu thay đổi"</span>'
            + (hired && !a.driver_assistance ? '<span class="act-hint"><i class="fas fa-info-circle"></i> Đơn đi với KTV thuê: tích "Tài xế phụ KTV" nếu tài xế phụ chụp để được tính ca.</span>' : '')
            + '</div>'
            + '</div></details>';
    }

    // Giá trị khối chấm công; chỉ gửi kèm khi người dùng có sửa (tránh ghi KPI khi chỉ đổi xe)
    // Làm mới phương án các đơn chưa lưu trong khung, giữ lựa chọn đang chọn (nếu vẫn hợp lệ),
    // trạng thái tích và chấm công đang sửa; số ca tháng, trống / bận lấy theo dữ liệu mới nhất
    const refreshUrl = CFG.refreshUrl;
    async function refreshRemaining() {
        const rest = items.map((it, i) => ({ it, i })).filter(x => !x.it._done && !x.it.hold_id);
        if (!rest.length) return;
        const keep = {};
        rest.forEach(({ it, i }) => {
            const row = list.querySelector('[data-i="' + i + '"]');
            if (!row) return;
            const box = row.querySelector('.sug__actual');
            keep[it.order_id] = {
                checked: row.querySelector('input[type=checkbox]').checked,
                f: Object.fromEntries(['car', 'driver', 'ktv1', 'ktv2'].map(f => [f, row.querySelector('[data-f=' + f + ']')?.value ?? ''])),
                actual: box && box.dataset.dirty ? actualValues(row) : null,
                open: box ? box.open : null,
            };
        });
        const q = new URLSearchParams({
            ids: rest.filter(x => !x.it.edit).map(x => x.it.order_id).join(','),
            edit: rest.filter(x => x.it.edit).map(x => x.it.order_id).join(','),
        });
        list.style.opacity = .6;
        try {
            const res = await fetch(refreshUrl + '?' + q, { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || ('Lỗi ' + res.status));
            const fresh = Object.fromEntries(data.items.map(x => [x.order_id, x]));
            rest.forEach(({ it, i }) => {
                const n = fresh[it.order_id];
                if (!n) { if ((data.gone || []).includes(it.order_id)) items[i]._done = true; return; }
                const k = keep[it.order_id];
                if (k) {
                    const pool = { car: n.options.car, driver: n.options.driver, ktv1: n.options.ktv, ktv2: n.options.ktv };
                    Object.entries(k.f).forEach(([f, val]) => {
                        if (val === '') { if (f !== 'car' && f !== 'driver') n[f] = null; return; }
                        const o = (pool[f] || []).find(o => String(o.value) === String(val));
                        if (o && o.state !== 'busy') n[f] = val;
                    });
                    if (k.actual && n.actual) Object.assign(n.actual, k.actual);
                }
                items[i] = n;
            });
            render();
            rest.forEach(({ it, i }) => {
                const k = keep[it.order_id];
                const row = list.querySelector('[data-i="' + i + '"]');
                if (!k || !row) return;
                // Về trạng thái chờ xếp: tích sẵn để xếp tiếp
                row.querySelector('input[type=checkbox]').checked = true;
                const box = row.querySelector('.sug__actual');
                if (box && k.actual) box.dataset.dirty = '1';
                if (box && k.open !== null) box.open = k.open;
            });
            paintGhosts();
            const left = rest.filter(({ i }) => !items[i]._done).length;
            if (left) toast('Đã cập nhật số ca và trạng thái trống / bận cho ' + left + ' đơn còn lại');
            closeIfEmpty();
        } catch (e) {
            toast('Không làm mới được các đơn còn lại: ' + e.message);
        } finally {
            list.style.opacity = '';
        }
    }

    function actualValues(row) {
        const box = row.querySelector('.sug__actual');
        if (!box) return null;
        const val = k => box.querySelector('[data-a=' + k + ']');
        return {
            order_quantity_draft: val('order_quantity_draft').value === '' ? null : Number(val('order_quantity_draft').value),
            driver_assistance: val('driver_assistance').checked,
            work_over_250: val('work_over_250').checked,
            order_note_ktv: val('order_note_ktv').value.trim() || null,
        };
    }
    list.addEventListener('input', e => { const box = e.target.closest('.sug__actual'); if (box) box.dataset.dirty = '1'; });
    list.addEventListener('change', e => { const box = e.target.closest('.sug__actual'); if (box) box.dataset.dirty = '1'; });

    function render() {
        // Đơn đã lưu (_done) không vẽ lại; giữ nguyên chỉ số để data-i khớp với items
        list.innerHTML = items.map((it, i) => it._done ? '' : '<div class="sug__item' + (it.surcharge ? ' sug__item--sur' : '') + '" data-i="' + i + '">'
            + '<input type="checkbox" checked aria-label="Chọn đơn ' + it.order_id + '" style="margin-top:4px">'
            + '<div><div class="sug__title">' + esc(it.unit) + (it.surcharge ? '<span class="tag tag--sur" title="Đơn phụ thu">Phụ thu</span>' : '') + (it.group ? '<span class="sug__grp"><i class="fas fa-link"></i> Nhóm ' + esc(it.group) + '</span>' : '') + (it.edit ? '<span class="sug__edit">Sửa lịch</span>' : '')
            + (it.company && it.company.toLowerCase() !== String(it.unit).toLowerCase() ? '<span class="sug__co"><i class="far fa-building"></i> ' + esc(it.company) + '</span>' : '')
            + ' <small>' + dm(it.date) + (it.end_date && it.end_date !== it.date ? '–' + dm(it.end_date) : '')
            + ' · ' + esc(it.session) + ' · ' + it.quantity + ' cas · ' + esc(it.service || '') + ' · #' + it.order_id + '</small></div>'
            + '<div class="sug__fields">'
            + '<label>Xe' + select('car', it.options.car, it.car) + '</label>'
            + '<label>Tài xế' + select('driver', it.options.driver, it.driver) + '</label>'
            + '<label>KTV 1' + select('ktv1', it.options.ktv, it.ktv1, '— Không —') + '</label>'
            + '<label>KTV 2' + select('ktv2', it.options.ktv, it.ktv2, '— Không —') + '</label>'
            + '</div>'
            + (it.hold_id ? '<div class="sug__hold"><i class="fas fa-thumbtack"></i> Xếp vào lịch giữ chỗ: ' + esc(it.hold.car_label) + ' · ' + dm(it.hold.start)
                + (it.hold.end !== it.hold.start ? '–' + dm(it.hold.end) : '') + ' · ' + esc(it.hold.session) + ' (' + esc(it.hold.customer) + ')'
                + (it.can_move ? '<label><input type="checkbox" data-move' + (it.move_to_hold ? ' checked' : '') + '> Đổi ngày chụp của đơn từ ' + dm(it.original.date)
                    + (it.original.end && it.original.end !== it.original.date ? '–' + dm(it.original.end) : '') + ' (' + esc(it.original.session) + ') sang '
                    + (it.hold.days.length > 1 ? 'một ngày giữ chỗ' : 'ngày giữ chỗ') + '</label>'
                    + (it.move_to_hold && it.hold.days.length > 1
                        ? '<div class="sug__holdday">Ngày chụp mới <select data-hold-day>' + it.hold.days.map(d => '<option value="' + d + '"' + (d === it.hold_day ? ' selected' : '') + '>' + dow(d) + ' ' + dm(d) + '</option>').join('')
                            + '</select><span>Đơn chỉ dùng 1 ngày này, các ngày giữ chỗ còn lại vẫn được giữ.</span></div>'
                        : '')
                    + (it.move_to_hold ? '' : '<div style="margin-top:4px;font-weight:400">Đang xếp theo ngày của đơn (' + dm(it.original.date) + '); lịch giữ chỗ vẫn giữ nguyên, nếu khách không cần nữa thì xóa ở lịch xe.</div>')
                    : (it.hold.days.length > 1 ? '<div style="margin-top:4px;font-weight:400">Đơn chỉ dùng ngày ' + dm(it.date) + ', các ngày giữ chỗ còn lại vẫn được giữ.</div>' : ''))
                + '</div>' : '')
            + '<div class="sug__travel" data-travel></div>'
            + it.reasons.map(r => '<div class="sug__reason"><i class="fas fa-magic"></i>' + esc(r) + '</div>').join('')
            + it.warnings.map(w => '<div class="sug__warn"><i class="fas fa-exclamation-triangle"></i> ' + esc(w) + '</div>').join('')
            + (it.edit && it.actual ? actualBlock(it) : '')
            + (it.edit ? '<div class="sug__cancel"><button type="button" class="btn-g btn-cancel" data-cancel-order="' + it.order_id + '"><i class="far fa-calendar-times"></i> Hủy lịch đơn này</button>'
                + '<small>Đơn về "Chưa lên lịch" và hiện lại ở Đơn chờ xếp lịch</small></div>' : '')
            + '<div class="sug__result" hidden></div>'
            + '</div></div>').join('');
        list.querySelectorAll('.sug__item').forEach(updateTravel);
        paintGhosts();
    }

    // Dòng "Di chuyển" theo xe đang chọn: hôm trước / hôm sau xe ở đâu, bao xa
    function updateTravel(row) {
        const it = items[row.dataset.i];
        const car = row.querySelector('[data-f=car]').value;
        const opt = (it.options.car || []).find(o => String(o.value) === String(car));
        const box = row.querySelector('[data-travel]');
        const t = opt && opt.travel;
        box.className = 'sug__travel' + (t && t.far ? ' is-far' : '');
        box.innerHTML = '<i class="fas fa-route"></i>' + esc(t ? t.text : (opt && opt.state === 'busy' ? 'Xe này đang bận buổi đó' : 'Xe thuê ngoài: không theo dõi di chuyển'));
    }

    // Hiện gợi ý lên lưới xe (ô trống trong tuần đang xem)
    function paintGhosts() {
        document.querySelectorAll('.slot--ai').forEach(el => { el.className = 'slot slot--empty'; el.innerHTML = ''; });
        items.forEach((it, i) => {
            const row = list.querySelector('[data-i="' + i + '"]');
            if (!row || !row.querySelector('input[type=checkbox]').checked || row.classList.contains('is-done')) return;
            const car = row.querySelector('[data-f=car]').value;
            const halves = it.session === 'Sáng' ? ['S'] : it.session === 'Chiều' ? ['C'] : ['S', 'C'];
            let d = new Date(it.date + 'T00:00:00');
            const end = new Date((it.end_date || it.date) + 'T00:00:00');
            for (let n = 0; d <= end && n < 31; n++, d.setDate(d.getDate() + 1)) {
                const ymd = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                halves.forEach(h => {
                    const slot = document.querySelector('#car-grid td[data-car="' + car + '"][data-key="' + ymd + '|' + h + '"] .slot');
                    if (slot && !slot.innerHTML.trim()) {
                        slot.className = 'slot slot--ai' + (it.surcharge ? ' slot--sur-ai' : '');
                        slot.innerHTML = '<b>' + esc(it.unit) + '</b><span>' + esc(row.querySelector('[data-f=driver]').value) + '</span>';
                    }
                });
            }
        });
    }

    // Hủy lịch một đơn (form Sửa lịch): đơn về "Chưa lên lịch", hiện lại ở Đơn chờ xếp lịch
    const cancelBase = CFG.cancelBase;
    list.addEventListener('click', async e => {
        const btn = e.target.closest('[data-cancel-order]');
        if (!btn) return;
        const row = btn.closest('.sug__item');
        const it = items[row.dataset.i];
        const ok = await uiConfirm('Xe và nhân sự đang xếp cho đơn #' + it.order_id + ' (' + it.unit + ', ' + dm(it.date) + ') sẽ được bỏ.\n'
            + 'Đơn chuyển về "Chưa lên lịch" và hiện lại ở Đơn chờ xếp lịch để xếp lại.',
            { title: 'Hủy lịch đơn này?', ok: 'Hủy lịch', danger: true });
        if (!ok) return;
        btn.disabled = true;
        try {
            await api(cancelBase + '/' + it.order_id, {});
            it._done = true;
            row.classList.add('is-leaving');
            setTimeout(() => { row.remove(); closeIfEmpty(); }, 400);
            toast('Đã hủy lịch đơn #' + it.order_id + ', đơn về Chưa lên lịch');
            await loadWeek(currentMonday() || it.date, true);
            reloadPending();
            loadTimekeeping(document.getElementById('tk-label')?.dataset.monthCurrent || '');
            setTimeout(refreshRemaining, 450);
        } catch (err) {
            btn.disabled = false;
            uiAlert(err.message);
        }
    });

    list.addEventListener('change', async e => {
        if (e.target.matches('[data-move], [data-hold-day]')) {
            // Bỏ / tích "Đổi ngày" hoặc chọn ngày giữ chỗ khác: tính lại lựa chọn xe / người theo đúng ngày đó
            const row = e.target.closest('.sug__item');
            const it = items[row.dataset.i];
            const move = row.querySelector('[data-move]').checked;
            const day = row.querySelector('[data-hold-day]')?.value || it.hold_day;
            row.style.opacity = .6;
            try {
                const res = await fetch(orderUrl(it.order_id) + '?hold=' + it.hold_id + '&move=' + (move ? '1' : '0') + '&day=' + encodeURIComponent(day), { headers: { 'Accept': 'application/json' } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || ('Lỗi ' + res.status));
                items[row.dataset.i] = data.items[0];
                render();
                await loadWeek(data.items[0].date);
            } catch (err) {
                if (e.target.type === 'checkbox') e.target.checked = !e.target.checked;
                uiAlert(err.message);
            } finally {
                row.style.opacity = '';
            }
            return;
        }
        if (e.target.matches('[data-f=car]')) updateTravel(e.target.closest('.sug__item'));
        paintGhosts();
    });

    // ---------- Đổi tuần không tải lại trang ----------
    function mondayOf(ymd) {
        const d = new Date(ymd + 'T00:00:00');
        d.setDate(d.getDate() - ((d.getDay() + 6) % 7));
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function currentMonday() { return document.getElementById('week-label')?.dataset.monday; }

    async function loadWeek(ymd, force) {
        const monday = mondayOf(ymd);
        if (!force && monday === currentMonday()) return;
        weekView.classList.add('is-loading');
        try {
            const res = await fetch(weekUrl + '?tuan=' + monday, { headers: { 'Accept': 'text/html' } });
            if (!res.ok) throw new Error('Lỗi ' + res.status);
            weekView.innerHTML = await res.text();
            try { history.replaceState(null, '', pageUrl + '?tuan=' + monday); } catch (e) {}
            paintGhosts();
        } catch (e) {
            toast('Không tải được lịch tuần: ' + e.message);
        } finally {
            weekView.classList.remove('is-loading');
        }
    }
    // ---------- Chấm công: đổi tháng không tải lại trang ----------
    const tkView = document.getElementById('timekeeping-view');
    const tkUrl = CFG.timekeepingUrl;
    async function loadTimekeeping(month) {
        tkView.classList.add('is-loading');
        try {
            const res = await fetch(tkUrl + '?thang=' + month, { headers: { 'Accept': 'text/html' } });
            if (!res.ok) throw new Error('Lỗi ' + res.status);
            // Giữ trạng thái mở / đóng của khối "chưa nhập số ca" khi tải lại (sau khi lưu chấm công)
            const wasOpen = tkView.querySelector('details.tk-missing')?.open;
            tkView.innerHTML = await res.text();
            const box = tkView.querySelector('details.tk-missing');
            if (box && wasOpen) box.open = true;
        } catch (e) {
            toast('Không tải được chấm công: ' + e.message);
        } finally {
            tkView.classList.remove('is-loading');
        }
    }
    tkView.addEventListener('click', e => {
        const b = e.target.closest('[data-month]');
        if (b) loadTimekeeping(b.dataset.month);
        const more = e.target.closest('[data-tk-more]');
        if (more) {
            const open = more.previousElementSibling.classList.toggle('is-collapsed') === false;
            more.classList.toggle('is-open', open);
            more.querySelector('span').textContent = open ? more.dataset.less : more.dataset.more;
        }
        // Bấm đơn trong cảnh báo "chưa nhập số ca thực tế": mở Sửa lịch để chấm công luôn
        const link = e.target.closest('[data-edit-orders]');
        if (link) openEdit(link);
    });

    // Tải ảnh lịch xe cả tuần: dựng bảng đầy đủ (ẩn ngoài màn hình) rồi chụp thành PNG
    const sheetUrl = CFG.sheetUrl;
    let h2cLoading = null;
    function loadHtml2canvas() {
        if (window.html2canvas) return Promise.resolve();
        return h2cLoading ??= new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            s.onload = resolve;
            s.onerror = () => { h2cLoading = null; reject(new Error('Không tải được thư viện chụp ảnh')); };
            document.head.appendChild(s);
        });
    }
    async function downloadWeekImage(btn) {
        const monday = btn.dataset.weekImage;
        const label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo ảnh…';
        const holder = document.createElement('div');
        holder.style.cssText = 'position:fixed;left:-20000px;top:0;z-index:-1;';
        try {
            const [res] = await Promise.all([fetch(sheetUrl + '?tuan=' + monday, { headers: { 'Accept': 'text/html' } }), loadHtml2canvas()]);
            if (!res.ok) throw new Error('Lỗi ' + res.status);
            holder.innerHTML = await res.text();
            document.body.appendChild(holder);
            const sheet = holder.querySelector('#week-sheet');
            const canvas = await window.html2canvas(sheet, { scale: 2, backgroundColor: '#ffffff', logging: false, useCORS: true });
            const a = document.createElement('a');
            a.download = 'lich-xe-tuan-' + monday + '.png';
            a.href = canvas.toDataURL('image/png');
            document.body.appendChild(a);
            a.click();
            a.remove();
            toast('Đã tạo ảnh lịch xe tuần ' + dm(monday));
        } catch (err) {
            uiAlert(err.message, { title: 'Không tạo được ảnh' });
        } finally {
            holder.remove();
            btn.disabled = false;
            btn.innerHTML = label;
        }
    }

    weekView.addEventListener('click', e => {
        const img = e.target.closest('[data-week-image]');
        if (img) { downloadWeekImage(img); return; }
        const a = e.target.closest('a[data-week]');
        if (!a) return;
        e.preventDefault();
        loadWeek(a.dataset.week, true);
    });

    // Mở form xếp / sửa / giữ chỗ: cuộn hẳn về đầu trang (các khung này nằm ngay dưới thanh tiêu đề).
    // Cuộn cả cửa sổ lẫn khung nội dung nếu layout admin cuộn trong khung riêng.
    // Cuộn mượt về đầu trang bằng requestAnimationFrame (tự đặt vị trí từng khung hình), vì kiểu
    // "smooth" của trình duyệt bị hủy giữa chừng khi nội dung phía trên vừa thay đổi.
    let scrollAnim = 0;
    // Chiều cao thanh tiêu đề cố định của layout admin (nếu có) để khung không bị che
    function headerOffset() {
        const h = document.querySelector('.header');
        return h && getComputedStyle(h).position === 'fixed' ? h.getBoundingClientRect().height + 12 : 12;
    }
    // Cuộn tới khung vừa mở (khung phương án / giữ chỗ nằm gần đầu trang)
    function scrollToPanel(panel) {
        const root = document.scrollingElement || document.documentElement;
        const target = Math.max(0, panel.getBoundingClientRect().top + root.scrollTop - headerOffset());
        animateScroll(root, target);
    }
    function animateScroll(root, target) {
        cancelAnimationFrame(scrollAnim);
        const scrollers = [root];
        for (let el = document.querySelector('.dp')?.parentElement; el && el !== document.body; el = el.parentElement) {
            if (el.scrollHeight > el.clientHeight && /(auto|scroll)/.test(getComputedStyle(el).overflowY)) scrollers.push(el);
        }
        const starts = scrollers.map(el => el.scrollTop);
        const ends = scrollers.map((el, i) => i === 0 ? target : 0);
        if (starts.every((y, i) => Math.abs(y - ends[i]) < 2)) return;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const duration = reduce ? 0 : Math.min(600, 250 + Math.max(...starts.map((y, i) => Math.abs(y - ends[i]))) / 6);
        const t0 = performance.now();
        const ease = t => 1 - Math.pow(1 - t, 3);
        const step = now => {
            const t = duration ? Math.min(1, (now - t0) / duration) : 1;
            scrollers.forEach((el, i) => { el.scrollTop = Math.round(starts[i] + (ends[i] - starts[i]) * ease(t)); });
            if (t < 1) scrollAnim = requestAnimationFrame(step);
        };
        scrollAnim = requestAnimationFrame(step);
    }
    // Người dùng tự cuộn (lăn chuột / vuốt) thì dừng hiệu ứng
    ['wheel', 'touchstart', 'keydown'].forEach(ev => window.addEventListener(ev, () => cancelAnimationFrame(scrollAnim), { passive: true }));

    let toastTimer;
    function toast(text) {
        let el = document.getElementById('dp-toast');
        if (!el) { el = document.createElement('div'); el.id = 'dp-toast'; el.className = 'toast'; el.setAttribute('role', 'status'); document.body.appendChild(el); }
        el.textContent = text;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('show'), 3500);
    }

    function show(data, label) {
        items = data.items || [];
        src.textContent = label;
        src.className = 'src' + (data.source === 'ai' ? '' : ' src--rule');
        document.getElementById('sug-apply').innerHTML = data.source === 'edit'
            ? '<i class="fas fa-save"></i> Lưu thay đổi' : '<i class="fas fa-check"></i> Áp dụng đã chọn';
        note.hidden = !data.note;
        note.textContent = data.note || '';
        panel.hidden = false;
        render();
        scrollToPanel(panel);
    }

    // Bấm vào đơn chờ: mở form xếp thủ công cho riêng đơn đó (điền sẵn phương án theo quy tắc).
    // Dùng ủy quyền sự kiện vì danh sách được tải lại sau khi giữ chỗ / xếp lịch.
    const pendingView = document.getElementById('pending-view');
    async function openPending(card) {
        card.style.opacity = .6;
        document.querySelectorAll('.pend__item.is-active').forEach(c => c.classList.remove('is-active'));
        card.classList.add('is-active');
        try {
            const res = await fetch(orderUrl(card.dataset.order) + (card.dataset.hold ? '?hold=' + card.dataset.hold : ''), { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || ('Lỗi ' + res.status));
            // Chuyển lịch xe + nhân sự tới đúng tuần của phương án (ngày giữ chỗ nếu đổi ngày, không thì ngày đơn)
            await loadWeek(data.items[0]?.date || card.dataset.date);
            show(data, card.dataset.hold ? 'Xếp vào giữ chỗ' : 'Xếp thủ công');
        } catch (e) {
            uiAlert(e.message);
        } finally {
            card.style.opacity = '';
        }
    }
    pendingView.addEventListener('click', e => {
        const card = e.target.closest('.pend__item');
        if (card) openPending(card);
    });
    pendingView.addEventListener('keydown', e => {
        const card = e.target.closest('.pend__item');
        if (card && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openPending(card); }
    });

    // Tải lại danh sách đơn chờ (vd vừa giữ chỗ cho đơn vị đang có đơn chờ: hiện nhãn "Đã giữ chỗ")
    const pendingUrl = CFG.pendingUrl;
    async function reloadPending() {
        try {
            const res = await fetch(pendingUrl, { headers: { 'Accept': 'text/html' } });
            if (!res.ok) return;
            const openGroups = [...pendingView.querySelectorAll('details[open]')].map(d => d.dataset.groupBox);
            pendingView.innerHTML = await res.text();
            openGroups.forEach(g => { const d = pendingView.querySelector('[data-group-box="' + g + '"]'); if (d) d.open = true; });
            const n = pendingView.querySelector('[data-pending-count]')?.dataset.pendingCount ?? '0';
            const count = document.getElementById('pending-count');
            if (count) count.textContent = n;
            if (btn) btn.disabled = n === '0';
        } catch (e) {}
    }

    btn?.addEventListener('click', async () => {
        btn.disabled = true;
        const label = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tính toán…';
        try {
            const data = await api(suggestUrl);
            show(data, data.source === 'ai' ? 'Claude' : 'Theo quy tắc');
        } catch (e) {
            uiAlert(e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = label;
        }
    });

    function closePanel() {
        panel.hidden = true;
        items = [];
        paintGhosts();
        document.querySelectorAll('.pend__item.is-active').forEach(c => c.classList.remove('is-active'));
    }
    document.getElementById('sug-close').addEventListener('click', closePanel);
    function closeIfEmpty() {
        if (!list.querySelector('.sug__item')) closePanel();
    }

    // Bấm vào ô lịch đã xếp trên lưới: mở form sửa xe / nhân sự của (các) đơn trong ô đó
    const editUrl = CFG.editUrl;
    async function openEdit(slot) {
        slot.style.opacity = .6;
        try {
            const res = await fetch(editUrl + '?ids=' + encodeURIComponent(slot.dataset.orders || slot.dataset.editOrders), { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || ('Lỗi ' + res.status));
            if (!data.items.length) { toast(data.note || 'Không tìm thấy lịch'); return; }
            document.querySelectorAll('.pend__item.is-active').forEach(c => c.classList.remove('is-active'));
            await loadWeek(data.items[0].date);
            show(data, 'Sửa lịch');
        } catch (e) {
            uiAlert(e.message);
        } finally {
            slot.style.opacity = '';
        }
    }
    weekView.addEventListener('click', e => {
        const slot = e.target.closest('.slot--click[data-orders]');
        if (slot) openEdit(slot);
    });
    weekView.addEventListener('keydown', e => {
        const slot = e.target.closest('.slot--click[data-orders], .slot[data-hold], .slot--empty');
        if (slot && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); slot.click(); }
    });

    // Đơn đã xếp: bỏ khỏi danh sách chờ, cập nhật số đơn
    function removePending(orderId) {
        const card = document.querySelector('.pend__item[data-order="' + orderId + '"]');
        if (!card) return;
        card.classList.add('is-gone');
        const group = card.dataset.group;
        setTimeout(() => {
            card.remove();
            // Nhóm "đã qua ngày" / "xa hơn": cập nhật số đơn của nhóm, hết đơn thì ẩn nhóm
            if (group !== 'main') {
                const box = document.querySelector('[data-group-box="' + group + '"]');
                const n = box ? box.querySelectorAll('.pend__item').length : 0;
                if (box) { box.querySelector('[data-group-count]').textContent = n; if (!n) box.remove(); }
                return;
            }
            const left = document.querySelectorAll('.pend__item[data-group="main"]').length;
            const count = document.getElementById('pending-count');
            if (count) count.textContent = left;
            if (!left) {
                document.getElementById('pending-empty').hidden = false;
                if (btn) btn.disabled = true;
            }
        }, 350);
    }

    // ---------- Giữ chỗ ----------
    const holdPanel = document.getElementById('hold-panel');
    const holdForm = document.getElementById('hold-form');
    const holdView = document.getElementById('hold-view');
    const holdErr = document.getElementById('hold-err');
    const holdBase = CFG.holdBase;

    let currentHold = null;
    const holdSubmit = document.getElementById('hold-submit');

    function showHoldForm(title, editId) {
        holdForm.dataset.edit = editId || '';
        holdSubmit.innerHTML = editId ? '<i class="fas fa-save"></i> Lưu thay đổi' : '<i class="fas fa-thumbtack"></i> Lưu giữ chỗ';
        document.getElementById('hold-title').textContent = title;
        holdErr.hidden = true;
        holdForm.hidden = false;
        holdView.hidden = true;
        holdPanel.hidden = false;
        scrollToPanel(holdPanel);
        holdForm.customer.focus({ preventScroll: true });
    }

    // Sửa giữ chỗ: điền lại form với thông tin hiện tại
    function editHold(h) {
        holdForm.reset();
        holdForm.customer.value = h.customer;
        holdForm.hold_start.value = h.start;
        holdForm.hold_end.value = h.end;
        holdForm.hold_session.value = h.session;
        holdForm.car.value = h.car;
        holdForm.driver.value = h.driver || '';
        holdForm.ktv1.value = h.ktv1 || '';
        holdForm.ktv2.value = h.ktv2 || '';
        holdForm.expected_cas.value = h.expected_cas || '';
        holdForm.note.value = h.note || '';
        showHoldForm('Sửa giữ chỗ: ' + h.customer, h.id);
    }

    function openHoldForm(car, key) {
        const [date, half] = key.split('|');
        holdForm.reset();
        holdForm.hold_start.value = date;
        holdForm.hold_end.value = date;
        holdForm.hold_session.value = half === 'S' ? 'Sáng' : half === 'C' ? 'Chiều' : 'Cả ngày';
        holdForm.car.value = car;
        showHoldForm('Giữ chỗ ' + (holdForm.car.selectedOptions[0]?.textContent || '') + ' ngày ' + dm(date), null);
    }

    holdForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (!holdForm.customer.value.trim()) { holdForm.customer.reportValidity(); return; }
        const body = Object.fromEntries(new FormData(holdForm).entries());
        Object.keys(body).forEach(k => { if (body[k] === '') body[k] = null; });
        const editId = holdForm.dataset.edit;
        try {
            const data = editId ? await api(holdBase + '/' + editId, body, 'PUT') : await api(holdBase, body);
            holdPanel.hidden = true;
            toast((editId ? 'Đã cập nhật giữ chỗ cho ' : 'Đã giữ chỗ cho ') + data.hold.customer
                + (data.matched_unit ? '' : ' (chưa có đơn vị trong hệ thống: khi có đơn, bấm vào ô giữ chỗ để xếp đơn vào)'));
            await loadWeek(body.hold_start, true);
            reloadPending();
        } catch (err) {
            holdErr.textContent = err.message;
            holdErr.hidden = false;
        }
    });
    holdPanel.addEventListener('click', e => { if (e.target.closest('[data-hold-close]')) holdPanel.hidden = true; });

    async function openHoldView(id) {
        try {
            const res = await fetch(holdBase + '/' + id, { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || ('Lỗi ' + res.status));
            const h = data.hold;
            currentHold = h;
            document.getElementById('hold-title').textContent = 'Lịch giữ chỗ: ' + h.customer;
            holdView.innerHTML = '<div class="hold-info"><b>' + esc(h.customer) + '</b> · ' + esc(h.car_label) + ' · ' + dm(h.start) + (h.end !== h.start ? '–' + dm(h.end) : '')
                + ' · ' + esc(h.session) + (h.expected_cas ? ' · ~' + fmt(h.expected_cas) + ' cas' : '')
                + (h.driver ? '<br>TX: ' + esc(h.driver) : '') + (h.ktv.length ? ' · KTV: ' + esc(h.ktv.join(', ')) : '')
                + (h.note ? '<br>Ghi chú: ' + esc(h.note) : '') + '</div>'
                + '<p class="sec-title" style="margin:14px 0 0;font-size:14px">Xếp đơn vào chỗ này <small>đơn cùng đơn vị lên đầu, rồi đơn cùng ngày, rồi đơn gần ngày</small></p>'
                + (data.candidates.length ? '<div class="cand">' + data.candidates.map(c => '<div class="cand__row' + (c.surcharge ? ' hold-cand--sur' : '') + '"><div class="grow"><b>' + esc(c.unit) + '</b>'
                    + (c.surcharge ? '<span class="tag tag--sur">Phụ thu</span>' : '')
                    + (c.company && c.company.toLowerCase() !== String(c.unit).toLowerCase() ? ' · ' + esc(c.company) : '')
                    + '<br><small>' + dm(c.date) + ' · ' + esc(c.session) + ' · ' + fmt(c.quantity) + ' cas · #' + c.id + '</small></div>'
                    + (c.match === 'unit' ? '<span class="cand__tag">Cùng đơn vị</span>' : c.match === 'date' ? '<span class="cand__tag cand__tag--date">Cùng ngày</span>' : '')
                    + '<button type="button" class="btn-g" data-fill="' + c.id + '">Xếp vào</button></div>').join('') + '</div>'
                    : '<p class="empty-note" style="margin-top:8px">Chưa có đơn chờ nào phù hợp. Khi Sale lên đơn, đơn sẽ hiện ở đây.</p>')
                + '<div class="hold-form" style="margin-top:14px"><div class="actions">'
                + '<button type="button" class="btn-g" data-hold-delete="' + h.id + '" style="color:var(--bad-ink)"><i class="far fa-trash-alt"></i> Xóa giữ chỗ</button>'
                + '<button type="button" class="btn-g" data-hold-edit><i class="fas fa-pen"></i> Sửa giữ chỗ</button>'
                + '<button type="button" class="btn-g" data-hold-close>Đóng</button></div></div>';
            holdView.dataset.hold = h.id;
            holdForm.hidden = true;
            holdView.hidden = false;
            holdPanel.hidden = false;
            scrollToPanel(holdPanel);
        } catch (err) {
            uiAlert(err.message);
        }
    }

    holdView.addEventListener('click', async e => {
        if (e.target.closest('[data-hold-edit]') && currentHold) { editHold(currentHold); return; }
        const fill = e.target.closest('[data-fill]');
        if (fill) {
            const res = await fetch(orderUrl(fill.dataset.fill) + '?hold=' + holdView.dataset.hold, { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) { uiAlert(data.message || ('Lỗi ' + res.status)); return; }
            holdPanel.hidden = true;
            show(data, 'Xếp vào giữ chỗ');
            return;
        }
        const del = e.target.closest('[data-hold-delete]');
        if (del && await uiConfirm('Xe / người đang giữ sẽ được trả lại trạng thái trống.', { title: 'Xóa lịch giữ chỗ này?', ok: 'Xóa giữ chỗ', danger: true })) {
            const res = await fetch(holdBase + '/' + del.dataset.holdDelete, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token } });
            if (!res.ok) { uiAlert('Lỗi ' + res.status); return; }
            holdPanel.hidden = true;
            toast('Đã xóa giữ chỗ');
            loadWeek(currentMonday(), true);
            reloadPending();
        }
    });

    weekView.addEventListener('click', e => {
        // Ô xe bảo trì: chuyển xuống danh sách bảo trì, làm nổi dòng đó
        const maint = e.target.closest('.slot--maint');
        if (maint) {
            const row = document.querySelector('[data-maint-row="' + maint.dataset.maint + '"]');
            scrollToPanel(document.getElementById('bao-tri'));
            if (row) { row.classList.add('is-flash'); setTimeout(() => row.classList.remove('is-flash'), 1800); }
            return;
        }
        const empty = e.target.closest('.slot--empty');
        if (empty) {
            const td = empty.closest('td[data-car]');
            if (td) openHoldForm(td.dataset.car, td.dataset.key);
            return;
        }
        const held = e.target.closest('.slot[data-hold]');
        if (held) openHoldView(held.dataset.hold);
    });

    // Giá trị đang chọn (tên hiển thị gọn, bỏ phần ghi chú sau dấu ·)
    const v = (row, f) => {
        const sel = row.querySelector('[data-f=' + f + ']');
        if (!sel || !sel.value) return '';
        return sel.options[sel.selectedIndex].textContent.split(' · ')[0];
    };

    applyBtn.addEventListener('click', async () => {
        const rows = [...list.querySelectorAll('.sug__item')].filter(r => r.querySelector('input[type=checkbox]').checked && !r.classList.contains('is-done'));
        if (!rows.length) { uiAlert('Hãy tích chọn ít nhất một đơn trong danh sách phương án.', { title: 'Chưa chọn đơn nào', type: 'info' }); return; }
        const payload = rows.map(r => {
            const it = items[r.dataset.i], v = f => r.querySelector('[data-f=' + f + ']').value;
            const move = r.querySelector('[data-move]');
            const box = r.querySelector('.sug__actual');
            return { order_id: it.order_id, car: v('car'), driver: v('driver'), ktv1: v('ktv1'), ktv2: v('ktv2') || null, edit: !!it.edit,
                hold_id: it.hold_id || null, move_to_hold: move ? move.checked : false, hold_day: move && move.checked ? it.hold_day : null,
                actual: box && box.dataset.dirty ? actualValues(r) : null };
        });
        const editing = payload.some(p => p.edit);
        const moving = payload.filter(p => p.move_to_hold).length;
        if (!await uiConfirm(
            (editing ? 'Xe và nhân sự của ' : 'Lịch xe sẽ được lưu cho ') + payload.length + ' đơn.'
                + (moving ? '\n' + moving + ' đơn sẽ được đổi ngày chụp sang ngày giữ chỗ.' : '')
                + (payload.some(p => p.actual) ? '\nChấm công đã sửa cũng được lưu cùng.' : '')
                ,
            { title: editing ? 'Lưu thay đổi lịch?' : 'Áp dụng lịch xe?', ok: editing ? 'Lưu thay đổi' : 'Áp dụng' })) return;
        applyBtn.disabled = true;
        try {
            const data = await api(applyUrl, { items: payload });
            let ok = 0;
            data.results.forEach(r => {
                const row = rows.find(x => items[x.dataset.i].order_id === r.order_id);
                if (!row) return;
                const out = row.querySelector('.sug__result');
                out.hidden = false;
                if (r.ok) {
                    ok++;
                    row.classList.add('is-done');
                    items[row.dataset.i]._done = true;
                    row.querySelector('input[type=checkbox]').checked = false;
                    out.className = 'sug__result ok';
                    out.innerHTML = '<i class="fas fa-check-circle"></i> ' + esc((r.edit ? 'Đã cập nhật lịch: ' : 'Đã lưu lịch: ') + (r.actual_saved ? '(kèm chấm công) ' : '') + v(row, 'car') + ', TX ' + v(row, 'driver')
                        + (v(row, 'ktv1') ? ', KTV ' + [v(row, 'ktv1'), v(row, 'ktv2')].filter(Boolean).join(', ') : ', không KTV')
                        + (r.warnings && r.warnings.length ? '. Lưu ý: ' + r.warnings.join('; ') : ''));
                    removePending(r.order_id);
                    // Báo xong rồi cho phương án biến khỏi khung
                    setTimeout(() => { row.classList.add('is-leaving'); setTimeout(() => { row.remove(); closeIfEmpty(); }, 400); }, 1600);
                } else {
                    out.className = 'sug__result bad';
                    out.textContent = 'Chưa lưu: ' + r.errors.join('; ');
                }
            });
            if (ok) {
                // Các đơn còn lại trong khung (vd đơn thứ 3 của nhóm): tính lại số ca / trống bận theo lịch vừa lưu
                setTimeout(refreshRemaining, 2100);
                toast((payload.some(p => p.edit) ? 'Đã cập nhật lịch ' : 'Đã lưu lịch ') + ok + ' đơn'
                    + (data.results.some(r => r.hold_used) ? ' (đã dùng lịch giữ chỗ)' : ''));
                await loadWeek(currentMonday() || items[0].date, true);
                reloadPending();
                loadTimekeeping(document.getElementById('tk-label')?.dataset.monthCurrent || '');
            }
        } catch (e) {
            uiAlert(e.message);
        } finally {
            applyBtn.disabled = false;
        }
    });
})();
