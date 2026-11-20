/*
 * Hợp đồng khách hàng (Kế toán): tải nhiều file → AI đọc từng file → form xem lại → Lưu.
 * Bản đọc được; bản chạy là contracts.js (esbuild --minify --target=es2019).
 */
(function () {
    'use strict';

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function money(input) {
        var digits = input.value.replace(/\D/g, '');
        input.value = digits ? Number(digits).toLocaleString('vi-VN').replace(/,/g, '.') : '';
    }

    // ---------- Form xem lại / sửa ----------
    function initForm(form) {
        if (form.dataset.ready) return;
        form.dataset.ready = '1';
        var counter = 1000;
        var typeSel = form.querySelector('[data-hd-type]');
        var modeSel = form.querySelector('[data-hd-mode]');
        var unitSel = form.querySelector('[data-hd-unit]');
        var dateInp = form.querySelector('[data-hd-date]');
        var orderSel = form.querySelector('[data-hd-order]');
        var orderHint = form.querySelector('[data-hd-order-hint]');

        function sync() {
            form.dataset.type = typeSel.value;
            form.dataset.mode = modeSel.value;
        }
        typeSel.addEventListener('change', function () { sync(); loadOrders(); });
        modeSel.addEventListener('change', sync);
        sync();

        form.addEventListener('input', function (e) {
            if (e.target.matches('[data-hd-money]')) money(e.target);
        });
        form.addEventListener('click', function (e) {
            var add = e.target.closest('[data-hd-add]');
            if (add) {
                var tpl = form.querySelector('template[data-hd-tpl="' + add.dataset.hdAdd + '"]');
                var list = add.closest('.hd-rows').querySelector('.hd-rows__list');
                list.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__i__/g, String(counter++)));
                var first = list.lastElementChild && list.lastElementChild.querySelector('input');
                if (first) first.focus();
                return;
            }
            var del = e.target.closest('[data-hd-del]');
            if (del) del.closest('.hd-row').remove();
        });
        // Enter trong ô nhập không gửi form (lưu bằng nút riêng)
        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') e.preventDefault();
        });
        form.addEventListener('submit', function (e) { e.preventDefault(); });

        var timer;
        function loadOrders() {
            if (!orderSel || typeSel.value !== 'thoi_vu') return;
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (!unitSel.value || !dateInp.value) {
                    orderHint.textContent = 'Chọn đơn vị và ngày chụp để xem các đơn.';
                    return;
                }
                var keep = orderSel.value;
                fetch(form.dataset.ordersUrl + '?unit=' + encodeURIComponent(unitSel.value) + '&date=' + encodeURIComponent(dateInp.value), { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        var html = '<option value="">Tự khớp theo đơn vị + ngày chụp</option>';
                        (j.orders || []).forEach(function (o) {
                            html += '<option value="' + o.id + '"' + (String(o.id) === keep ? ' selected' : '') + '>' + esc(o.label) + '</option>';
                        });
                        orderSel.innerHTML = html;
                        orderHint.textContent = (j.orders || []).length ? j.orders.length + ' đơn của đơn vị trong ±7 ngày quanh ngày chụp.' : 'Không có đơn nào của đơn vị trong ±7 ngày quanh ngày chụp — để "Tự khớp", khi Sales lên đơn sẽ tự nhận.';
                    })
                    .catch(function () {});
            }, 250);
        }
        if (unitSel) unitSel.addEventListener('change', loadOrders);
        if (dateInp) dateInp.addEventListener('change', loadOrders);
    }

    function showErrors(form, errors) {
        var box = form.querySelector('.hd-form__errors');
        var list = [];
        Object.keys(errors || {}).forEach(function (k) { list = list.concat(errors[k]); });
        box.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + list.map(esc).join('<br>');
        box.hidden = !list.length;
        if (list.length) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function save(form, btn) {
        var old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu…';
        // fieldset disabled (chỉ xem) thì không gửi
        return fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } })
            .then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, status: r.status, j: j }; });
            })
            .then(function (res) {
                if (res.status === 409 && res.j.confirm_close) {
                    // Đơn vị đã có hợp đồng nguyên tắc còn hiệu lực: hỏi có kết thúc hợp đồng cũ không, rồi gửi lại
                    var yes = confirm('Đơn vị này đang có hợp đồng nguyên tắc còn hiệu lực:\n- ' + res.j.contracts.join('\n- ')
                        + '\n\nOK = kết thúc hợp đồng cũ vào ngày ' + res.j.end_on + ' (hợp đồng mới áp dụng từ ' + res.j.start_on + ').'
                        + '\nHủy = giữ cả hai (đơn chụp từ ' + res.j.start_on + ' vẫn tính theo hợp đồng mới).');
                    var input = form.querySelector('input[name="close_previous"]');
                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'close_previous';
                        form.appendChild(input);
                    }
                    input.value = yes ? '1' : '0';
                    btn.disabled = false;
                    btn.innerHTML = old;
                    return save(form, btn).finally(function () { input.remove(); });
                }
                if (!res.ok) {
                    showErrors(form, res.j.errors || { x: [res.j.message || 'Lưu không được (lỗi ' + res.status + '), thử lại.'] });
                    return null;
                }
                showErrors(form, {});
                return res.j;
            })
            .catch(function () {
                showErrors(form, { x: ['Mất kết nối, thử lại.'] });
                return null;
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = old;
            });
    }

    // Trang sửa
    document.querySelectorAll('[data-hd-card] .hd-form').forEach(initForm);
    document.querySelectorAll('[data-hd-card] [data-hd-save]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('[data-hd-card]').querySelector('.hd-form');
            save(form, btn).then(function (j) { if (j && j.url) location.href = j.url; });
        });
    });

    // ---------- Bảng dài: chỉ hiện N dòng đầu, nút Xem thêm / Thu gọn ----------
    document.querySelectorAll('[data-hd-collapse]').forEach(function (box) {
        var limit = parseInt(box.dataset.hdCollapse, 10) || 10;
        var rows = Array.prototype.slice.call(box.querySelectorAll('tbody > tr'));
        if (rows.length <= limit + 2) return; // chỉ dư 1–2 dòng thì hiện hết luôn
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'hd-more';
        box.insertAdjacentElement('afterend', btn);
        var open = false;
        function render() {
            rows.forEach(function (tr, i) { tr.classList.toggle('hd-row-hidden', !open && i >= limit); });
            btn.innerHTML = open ? '<i class="fas fa-chevron-up"></i> Thu gọn'
                : '<i class="fas fa-chevron-down"></i> Xem thêm ' + (rows.length - limit) + ' dòng (tổng ' + rows.length + ')';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        btn.addEventListener('click', function () {
            open = !open;
            render();
            if (!open) box.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        render();
    });

    // ---------- Trang tải lên: hàng đợi AI đọc ----------
    var drop = document.querySelector('[data-hd-drop]');
    var queue = document.querySelector('[data-hd-queue]');
    if (!drop || !queue) return;
    var input = document.getElementById('hd-files');
    var pending = [];
    var running = 0;
    var MAX_PARALLEL = 2;
    var OK_EXT = ['pdf', 'docx', 'jpg', 'jpeg', 'png'];

    function addFiles(files) {
        Array.prototype.slice.call(files, 0, 20).forEach(function (file) {
            var card = document.createElement('section');
            card.className = 'sf-card hd-card is-busy';
            card.innerHTML = '<header class="hd-card__head"><span class="hd-card__icon"><i class="fas fa-spinner fa-spin"></i></span>' +
                '<div class="hd-card__title"><b>' + esc(file.name) + '</b><small data-hd-msg>Đang chờ…</small></div>' +
                '<button type="button" class="hd-card__x" data-hd-remove title="Bỏ file này" aria-label="Bỏ file này"><i class="fas fa-times"></i></button></header>' +
                '<div class="hd-card__body"></div>';
            queue.appendChild(card);
            var ext = (file.name.split('.').pop() || '').toLowerCase();
            if (OK_EXT.indexOf(ext) < 0) return fail(card, 'Không đọc được loại file này (chỉ PDF, Word .docx, ảnh JPG / PNG).');
            if (file.size > 30 * 1024 * 1024) return fail(card, 'File lớn hơn 30 MB.');
            pending.push({ file: file, card: card });
        });
        pump();
    }

    function setMsg(card, html, cls) {
        var msg = card.querySelector('[data-hd-msg]');
        msg.innerHTML = html;
        msg.className = cls || '';
    }

    function fail(card, text) {
        card.classList.remove('is-busy');
        card.classList.add('is-error');
        card.querySelector('.hd-card__icon').innerHTML = '<i class="fas fa-times-circle"></i>';
        setMsg(card, esc(text), 'is-err');
    }

    function pump() {
        while (running < MAX_PARALLEL && pending.length) {
            var job = pending.shift();
            if (!job.card.isConnected) continue;
            running++;
            read(job).finally(function () { running--; pump(); });
        }
    }

    function read(job) {
        var card = job.card;
        setMsg(card, 'AI đang đọc hợp đồng… (30–90 giây)');
        var fd = new FormData();
        fd.append('file', job.file);
        return fetch(drop.dataset.extractUrl, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, status: r.status, j: j }; }); })
            .then(function (res) {
                var j = res.j;
                if (!j.form) {
                    var err = j.errors ? Object.values(j.errors)[0][0] : (j.message || (res.status === 429 ? 'Đọc quá nhiều file trong 1 phút, chờ một chút rồi thử lại.' : 'Lỗi ' + res.status));
                    return fail(card, err);
                }
                card.classList.remove('is-busy');
                card.querySelector('.hd-card__body').innerHTML = j.form +
                    '<div class="sf-form-actions hd-card__foot"><button type="button" class="sf-btn sf-btn--ghost" data-hd-remove>Bỏ</button>' +
                    '<button type="button" class="sf-btn sf-btn--primary" data-hd-save><i class="fas fa-check"></i> Lưu hợp đồng</button></div>';
                initForm(card.querySelector('.hd-form'));
                var notes = [];
                var cls = 'is-ok';
                if (!res.ok) { notes.push(esc(j.message)); cls = 'is-warn'; }
                else notes.push('AI đã điền sẵn — kiểm tra lại, nhất là <b>đơn vị</b> và <b>bảng giá</b>, rồi bấm Lưu.');
                if (j.readable === false) { notes.push('File mờ, AI đọc không chắc chắn.'); cls = 'is-warn'; }
                if (j.duplicate) { notes.push('Số hợp đồng này đã có trong hệ thống — <a href="' + esc(j.duplicate.url) + '" target="_blank">mở hợp đồng đã lưu</a>.'); cls = 'is-warn'; }
                card.querySelector('.hd-card__icon').innerHTML = cls === 'is-ok' ? '<i class="fas fa-magic"></i>' : '<i class="fas fa-exclamation-triangle"></i>';
                card.classList.add('is-open');
                setMsg(card, notes.join(' '), cls);
            })
            .catch(function () { fail(card, 'Mất kết nối khi gửi file, thử lại.'); });
    }

    queue.addEventListener('click', function (e) {
        var card = e.target.closest('.hd-card');
        if (!card) return;
        if (e.target.closest('[data-hd-remove]')) {
            if (card.classList.contains('is-open') && !card.classList.contains('is-saved') && !confirm('Bỏ hợp đồng này (chưa lưu)?')) return;
            card.remove();
            return;
        }
        var btn = e.target.closest('[data-hd-save]');
        if (btn) {
            save(card.querySelector('.hd-form'), btn).then(function (j) {
                if (!j) return;
                card.classList.remove('is-open');
                card.classList.add('is-saved');
                card.querySelector('.hd-card__body').innerHTML = '';
                card.querySelector('.hd-card__icon').innerHTML = '<i class="fas fa-check-circle"></i>';
                setMsg(card, esc(j.message) + ' <a href="' + esc(j.url) + '">Xem</a>', 'is-ok');
            });
            return;
        }
        if (e.target.closest('.hd-card__head') && card.classList.contains('is-saved') === false && card.querySelector('.hd-form')) {
            if (!e.target.closest('a')) card.classList.toggle('is-collapsed');
        }
    });

    input.addEventListener('change', function () {
        addFiles(input.files);
        input.value = '';
    });
    ['dragenter', 'dragover'].forEach(function (t) {
        drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (t) {
        drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.remove('is-over'); });
    });
    drop.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
    });
    window.addEventListener('beforeunload', function (e) {
        if (queue.querySelector('.hd-card.is-open:not(.is-saved), .hd-card.is-busy')) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
