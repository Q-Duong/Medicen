/* Khung chat Trợ lý AI nổi trên mọi trang admin (layouts/section/admin/assistant_widget.blade.php).
 * Dùng chung API với trang Trợ lý: assistant.ask → assistant.poll (1,5 giây / lần) → lưu vào cùng lịch sử.
 * Gửi kèm trang đang xem (page_url, page_title) để trợ lý hiểu "đơn này", "khách này".
 * Nguồn: org-assistant-widget.js → esbuild --minify --target=es2019 → assistant-widget.js */
(function () {
    const root = document.getElementById('aw');
    if (!root) return;
    let cfg;
    try { cfg = JSON.parse(root.dataset.config); } catch (e) { return; }
    root.hidden = false;

    const $ = (s) => root.querySelector(s);
    const panel = $('.aw-panel'), body = $('[data-aw-body]'), input = $('[data-aw-input]'), form = $('[data-aw-form]');
    const sendBtn = $('[data-aw-send]'), suggestBox = $('[data-aw-suggest]'), dot = $('.aw-dot'), deptSel = $('[data-aw-dept]');
    const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const store = {
        get: (k) => { try { return localStorage.getItem('aw:' + cfg.user + ':' + k); } catch (e) { return null; } },
        set: (k, v) => { try { v === null ? localStorage.removeItem('aw:' + cfg.user + ':' + k) : localStorage.setItem('aw:' + cfg.user + ':' + k, v); } catch (e) { /* bỏ qua */ } },
    };
    let dept = cfg.depts.find((d) => d.key === store.get('dept')) || cfg.depts.find((d) => d.key === cfg.default) || cfg.depts[0];
    let convId = null, busy = false, loaded = false, pollTimer = null, waitTimer = null;

    // ---------- Markdown gọn (tiêu đề, đậm, nghiêng, code, link, danh sách, bảng, trích dẫn) ----------
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const safeUrl = (u) => (/^(https?:\/\/|\/)/i.test(u) ? u : '#');
    function inline(s) {
        return esc(s)
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/__(.+?)__/g, '<b>$1</b>')
            .replace(/(^|[^*\w])\*(?=\S)([^*]+?)\*/g, '$1<i>$2</i>')
            .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (m, t, u) => '<a href="' + safeUrl(u.replace(/&amp;/g, '&')) + '" target="_blank" rel="noopener">' + t + '</a>');
    }
    function md(src) {
        const lines = String(src || '').replace(/\r\n?/g, '\n').split('\n');
        let html = '', i = 0;
        const isTable = (k) => /\|/.test(lines[k] || '') && /^\s*\|?\s*:?-{2,}/.test(lines[k + 1] || '');
        const row = (l) => l.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((c) => c.trim());
        while (i < lines.length) {
            const line = lines[i];
            let m;
            if (!line.trim()) { i++; continue; }
            if ((m = line.match(/^\s*(```|~~~)\s*([\w-]*)/))) {
                const lang = m[2].toLowerCase(), buf = [];
                i++;
                while (i < lines.length && !lines[i].trim().startsWith(m[1])) buf.push(lines[i++]);
                i++;
                html += ['bieu-do', 'bieudo', 'bieu_do', 'chart'].includes(lang)
                    ? '<p class="aw-chart"><i class="fas fa-chart-bar"></i> Có biểu đồ — <a href="#" data-aw-open-full>mở trang Trợ lý để xem</a></p>'
                    : '<pre>' + esc(buf.join('\n')) + '</pre>';
                continue;
            }
            if ((m = line.match(/^(#{1,6})\s+(.*)$/))) { html += '<h4>' + inline(m[2]) + '</h4>'; i++; continue; }
            if (/^\s*(-{3,}|\*{3,})\s*$/.test(line)) { html += '<hr>'; i++; continue; }
            if (isTable(i)) {
                const head = row(line), rows = [];
                i += 2;
                while (i < lines.length && lines[i].includes('|') && lines[i].trim()) rows.push(row(lines[i++]));
                html += '<div class="aw-table"><table><thead><tr>' + head.map((h) => '<th>' + inline(h) + '</th>').join('') + '</tr></thead><tbody>'
                    + rows.map((r) => '<tr>' + head.map((_, c) => '<td>' + inline(r[c] || '') + '</td>').join('') + '</tr>').join('') + '</tbody></table></div>';
                continue;
            }
            if (/^\s*>/.test(line)) {
                const buf = [];
                while (i < lines.length && /^\s*>/.test(lines[i])) buf.push(lines[i++].replace(/^\s*>\s?/, ''));
                html += '<blockquote>' + md(buf.join('\n')) + '</blockquote>';
                continue;
            }
            if ((m = line.match(/^\s*([-*+]|\d+[.)])\s+/))) {
                const ordered = /\d/.test(m[1]);
                html += ordered ? '<ol>' : '<ul>';
                while (i < lines.length && (m = lines[i].match(/^(\s*)([-*+]|\d+[.)])\s+(.*)$/))) {
                    html += '<li' + (m[1].length >= 2 ? ' class="aw-sub"' : '') + '>' + inline(m[3]) + '</li>';
                    i++;
                    while (i < lines.length && !lines[i].trim() && /^\s*([-*+]|\d+[.)])\s+/.test(lines[i + 1] || '')) i++;
                }
                html += ordered ? '</ol>' : '</ul>';
                continue;
            }
            const buf = [];
            while (i < lines.length && lines[i].trim() && !/^\s*(```|~~~|#{1,6}\s|>|[-*+]\s|\d+[.)]\s)/.test(lines[i]) && !isTable(i)) buf.push(lines[i++]);
            if (!buf.length) buf.push(lines[i++]);
            html += '<p>' + buf.map(inline).join('<br>') + '</p>';
        }
        return html;
    }

    // ---------- Hiển thị ----------
    const fullUrl = () => dept.page + (convId ? '?c=' + convId : '');
    function scrollEnd() { body.scrollTop = body.scrollHeight; }
    function bubble(role, content) {
        const el = document.createElement('div');
        el.className = 'aw-msg aw-msg--' + role;
        el.innerHTML = role === 'assistant' ? md(content) : role === 'error' ? '<i class="fas fa-exclamation-circle"></i> ' + esc(content) : esc(content).replace(/\n/g, '<br>');
        body.appendChild(el);
        scrollEnd();
        return el;
    }
    function empty() {
        body.innerHTML = '<div class="aw-empty"><i class="fas fa-robot"></i><b>' + esc(dept.title) + '</b><span>Hỏi bất cứ điều gì về đơn hàng, khách, lịch xe, công nợ… Trợ lý biết bạn đang xem trang nào.</span></div>';
    }
    function suggestions(show) {
        suggestBox.innerHTML = '';
        if (!show) return;
        (dept.suggest || []).forEach((t) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = t;
            b.addEventListener('click', () => { input.value = t; send(); });
            suggestBox.appendChild(b);
        });
    }
    function waiting(on) {
        busy = on;
        sendBtn.disabled = on;
        const old = body.querySelector('.aw-typing');
        if (old) old.remove();
        clearInterval(waitTimer);
        if (!on) return;
        const el = document.createElement('div');
        el.className = 'aw-msg aw-msg--assistant aw-typing';
        const t0 = Date.now();
        const tick = () => {
            const s = Math.round((Date.now() - t0) / 1000);
            el.innerHTML = '<span class="aw-dots"><i></i><i></i><i></i></span> Trợ lý đang trả lời' + (s > 4 ? ' (' + s + ' giây)' : '') + '…';
        };
        tick();
        waitTimer = setInterval(tick, 1000);
        body.appendChild(el);
        scrollEnd();
    }

    // ---------- Gọi server ----------
    async function api(url, method, data) {
        const r = await fetch(url, {
            method, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, ...(data ? { 'Content-Type': 'application/json' } : {}) },
            body: data ? JSON.stringify(data) : undefined,
        });
        let j = {};
        try { j = await r.json(); } catch (e) { /* trang lỗi */ }
        return { ok: r.ok, status: r.status, data: j };
    }
    function setConv(id) {
        convId = id;
        store.set('conv:' + dept.key, id ? String(id) : null);
    }
    async function load() {
        loaded = true;
        clearTimeout(pollTimer);
        body.innerHTML = '';
        const id = parseInt(store.get('conv:' + dept.key), 10) || null;
        convId = id;
        if (!id) { empty(); suggestions(true); return; }
        const r = await api(dept.messages.replace('__ID__', id), 'GET');
        if (!r.ok || r.data.conversation && r.data.conversation.readonly) { setConv(null); empty(); suggestions(true); return; }
        const msgs = (r.data.messages || []).slice(-30);
        if (!msgs.length) empty();
        msgs.forEach((m) => bubble(m.role === 'user' ? 'user' : m.role === 'error' ? 'error' : 'assistant', m.content));
        suggestions(msgs.length === 0);
        if (r.data.conversation && r.data.conversation.pending) { waiting(true); poll(); }
    }
    async function poll() {
        const r = await api(dept.poll.replace('__ID__', convId), 'POST', {});
        if (r.ok && r.data.pending) { pollTimer = setTimeout(poll, 1500); return; }
        waiting(false);
        if (r.ok && r.data.done) {
            if (r.data.reply) {
                bubble('assistant', r.data.reply);
                if (panel.hidden) dot.hidden = false;
            } else {
                await load(); // đã trả lời ở lần poll khác: tải lại tin nhắn
            }
            return;
        }
        if (r.status === 502 || r.status === 500) { bubble('error', r.data.message || 'Trợ lý đang gặp lỗi, thử lại sau.'); return; }
        pollTimer = setTimeout(() => { waiting(true); poll(); }, 3000); // mạng chập chờn: thử lại
    }
    function pageFilters() {
        try {
            const f = typeof window.assistantPageFilters === 'function' ? window.assistantPageFilters() : null;
            return f && typeof f === 'object' ? f : undefined;
        } catch (e) { return undefined; }
    }
    async function send() {
        const text = input.value.trim();
        if (!text || busy) return;
        if (body.querySelector('.aw-empty')) body.innerHTML = '';
        suggestions(false);
        bubble('user', text);
        input.value = '';
        autosize();
        waiting(true);
        const r = await api(dept.ask, 'POST', {
            message: text, mode: 'auto', conversation_id: convId,
            page_url: location.pathname + location.search, page_title: document.title,
            // Trang có bộ lọc chạy bằng JS (Công nợ): gửi bộ lọc đang chọn để trợ lý biết đúng các đơn đang hiện
            page_filters: pageFilters(),
        });
        if (r.status === 202 || (r.ok && r.data.pending)) {
            setConv(r.data.conversation && r.data.conversation.id);
            poll();
            return;
        }
        waiting(false);
        if (r.status === 403 && convId) { setConv(null); bubble('error', 'Cuộc trò chuyện cũ không dùng được nữa, đã mở cuộc mới. Gửi lại câu hỏi nhé.'); return; }
        if (r.status === 409) { bubble('error', r.data.message || 'Trợ lý đang trả lời câu trước, chờ chút.'); setConv(r.data.conversation && r.data.conversation.id); waiting(true); poll(); return; }
        if (r.status === 429) { bubble('error', 'Hỏi nhanh quá, chờ một phút rồi thử lại.'); return; }
        if (r.status === 419) { bubble('error', 'Phiên đăng nhập hết hạn, tải lại trang.'); return; }
        if (r.data.conversation) setConv(r.data.conversation.id);
        bubble('error', r.data.message || 'Không gửi được câu hỏi (lỗi ' + r.status + ').');
    }

    // ---------- Mở / đóng, chuyển trợ lý ----------
    function open(on) {
        panel.hidden = !on;
        root.classList.toggle('is-open', on);
        store.set('open', on ? '1' : null);
        if (on) {
            dot.hidden = true;
            if (!loaded) load();
            setTimeout(() => input.focus(), 50);
            scrollEnd();
        }
    }
    root.querySelectorAll('[data-aw-toggle]').forEach((b) => b.addEventListener('click', () => open(panel.hidden)));
    $('[data-aw-new]').addEventListener('click', () => { if (busy) return; setConv(null); load(); input.focus(); });
    root.addEventListener('click', (e) => {
        const a = e.target.closest('[data-aw-full], [data-aw-open-full]');
        if (a) { e.preventDefault(); location.href = fullUrl(); }
    });
    if (deptSel) {
        deptSel.value = dept.key;
        deptSel.addEventListener('change', () => {
            if (busy) { deptSel.value = dept.key; return; }
            dept = cfg.depts.find((d) => d.key === deptSel.value) || dept;
            store.set('dept', dept.key);
            load();
        });
    }
    function autosize() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; }
    input.addEventListener('input', autosize);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); send(); }
    });
    form.addEventListener('submit', (e) => { e.preventDefault(); send(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) open(false); });

    // Đang mở ở trang trước thì mở tiếp (trên máy tính); có câu đang chờ thì hỏi tiếp
    if (store.get('open') === '1' && window.innerWidth > 640) open(true);
    else if (store.get('conv:' + dept.key)) load();
})();
