// Trang Trợ lý AI (chat) theo phòng ban: gửi câu hỏi, hiển thị trả lời (markdown), lịch sử trò chuyện, chế độ, nút copy.
// File nguồn (dễ đọc). Bản nạp lên trang là assistant.js (rút gọn từ file này).
// Cấu hình (đường dẫn API, chế độ, cuộc trò chuyện mở sẵn) đọc từ data-config của <div id="assistant">.

(function () {
    // Đường dẫn / dữ liệu ban đầu lấy từ data-config trên <div id="assistant"> (Blade điền sẵn)
    const root = document.getElementById('assistant');
    if (!root) return;
    const CFG = JSON.parse(root.dataset.config || '{}');
    const askUrl = CFG.askUrl;
    const listUrl = CFG.listUrl;
    const convUrl = id => listUrl + '/' + id;
    const pageUrl = CFG.pageUrl;
    const initialConversation = CFG.initialConversation;
    const modes = CFG.modes;
    const storageKey = 'assistant.' + CFG.department + '.mode';
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const log = document.getElementById('assistant-log');
    const thread = document.getElementById('thread');
    const emptyState = document.getElementById('empty');
    const form = document.getElementById('assistant-form');
    const input = document.getElementById('assistant-input');
    const send = document.getElementById('assistant-send');
    const chips = document.getElementById('chips');
    const modeBtn = document.getElementById('mode-btn');
    const modeMenu = document.getElementById('mode-menu');
    const formToggle = document.getElementById('form-toggle');
    const convList = document.getElementById('conv-list');
    const convSearch = document.getElementById('conv-search');
    const convHeading = document.getElementById('conv-heading');
    const composerWrap = document.getElementById('composer-wrap');
    const readonlyBar = document.getElementById('readonly-bar');
    const toolNames = {
        tra_cuu_don_vi: 'tra cứu đơn vị', xem_lich_xe: 'xem lịch xe', xem_cong_no: 'xem công nợ',
        tinh_loi_nhuan: 'tính lợi nhuận', xem_don_gia_mac_dinh: 'xem bảng giá', cap_nhat_don_gia_mac_dinh: 'CẬP NHẬT giá mặc định'
    };
    const ICON_COPY = '<svg class="i-copy" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>';
    const ICON_CHECK = '<svg class="i-check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>';

    let current = 'auto';
    try { const saved = localStorage.getItem(storageKey); if (saved && modes[saved]) current = saved; } catch (e) {}

    // ---------- Markdown → HTML (tự viết, escape mọi thứ trước khi chèn) ----------
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const MSG_LANGS = ['tin-nhan', 'tinnhan', 'tin_nhan', 'khach', 'message'];

    function inline(s) {
        const codes = [];
        s = s.replace(/`([^`]+)`/g, (_, c) => { codes.push(c); return '\u0000' + (codes.length - 1) + '\u0000'; });
        s = esc(s)
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
            .replace(/\*\*(?=\S)(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/__(?=\S)(.+?)__/g, '<strong>$1</strong>')
            .replace(/(^|[^*\w])\*(?=\S)([^*]+?)\*(?!\*)/g, '$1<em>$2</em>')
            .replace(/~~(.+?)~~/g, '<del>$1</del>');
        return s.replace(/\u0000(\d+)\u0000/g, (_, i) => '<code>' + esc(codes[i]) + '</code>');
    }

    const RE = {
        fence: /^\s*(```|~~~)\s*([\w-]*)\s*$/,
        heading: /^(#{1,6})\s+(.*?)\s*#*\s*$/,
        hr: /^\s*([-*_])(\s*\1){2,}\s*$/,
        item: /^(\s*)([-*+•]|\d+[.)])\s+(.*)$/,
        quote: /^\s*>/,
        tableSep: /^\s*\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?\s*$/,
    };
    const isTableAt = (lines, i) => lines[i].includes('|') && i + 1 < lines.length && RE.tableSep.test(lines[i + 1]);
    const isBlockStart = (lines, i) => RE.fence.test(lines[i]) || RE.heading.test(lines[i]) || RE.hr.test(lines[i])
        || RE.item.test(lines[i]) || RE.quote.test(lines[i]) || isTableAt(lines, i);
    const splitRow = l => l.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map(c => c.trim());
    const isNum = c => /^[-+]?[\d.,]+\s*(đ|%|VNĐ|vnđ|km|cas)?$/.test(c);

    function copyBlock(kind, label, raw) {
        return '<div class="md-block md-block--' + kind + '"><div class="md-block__head"><span>' + label + '</span>'
            + '<button type="button" class="copy-btn" data-copy-block>' + ICON_COPY + ICON_CHECK + '<em style="font-style:normal">Sao chép</em></button></div>'
            + '<pre>' + esc(raw) + '</pre></div>';
    }

    function parseList(lines, i) {
        const first = lines[i].match(RE.item);
        const indent = first[1].length;
        const ordered = /\d/.test(first[2]);
        const start = ordered ? parseInt(first[2], 10) : 1;
        let html = ordered ? '<ol' + (start !== 1 ? ' start="' + start + '"' : '') + '>' : '<ul>';
        while (i < lines.length) {
            const m = lines[i].match(RE.item);
            if (!m || m[1].length < indent) break;
            if (m[1].length > indent) {
                const sub = parseList(lines, i);
                html = html.replace(/<\/li>$/, sub.html + '</li>');
                i = sub.next;
                continue;
            }
            if (/\d/.test(m[2]) !== ordered) break;
            let content = inline(m[3]);
            i++;
            while (i < lines.length && lines[i].trim() && /^\s+/.test(lines[i]) && !RE.item.test(lines[i])) {
                content += '<br>' + inline(lines[i++].trim());
            }
            html += '<li>' + content + '</li>';
            // Bỏ qua dòng trống giữa các mục cùng danh sách
            if (i + 1 < lines.length && !lines[i].trim()) {
                const n = lines[i + 1].match(RE.item);
                if (n && n[1].length >= indent) i++;
            }
        }
        return { html: html + (ordered ? '</ol>' : '</ul>'), next: i };
    }

    function renderMarkdown(src) {
        const lines = String(src || '').replace(/\r\n?/g, '\n').split('\n');
        let html = '', i = 0, m;
        while (i < lines.length) {
            const line = lines[i];
            if (!line.trim()) { i++; continue; }
            if ((m = line.match(RE.fence))) {
                const lang = m[2].toLowerCase(), buf = [];
                i++;
                while (i < lines.length && !lines[i].trim().startsWith(m[1])) buf.push(lines[i++]);
                i++;
                const raw = buf.join('\n').replace(/^\n+|\n+$/g, '');
                html += MSG_LANGS.includes(lang)
                    ? copyBlock('msg', '<i class="far fa-comment-dots"></i> Tin nhắn gửi khách', raw)
                    : copyBlock('code', esc(lang || 'Nội dung'), raw);
                continue;
            }
            if ((m = line.match(RE.heading))) {
                const lvl = Math.min(m[1].length + 2, 6);
                html += '<h' + lvl + '>' + inline(m[2]) + '</h' + lvl + '>';
                i++;
                continue;
            }
            if (RE.hr.test(line)) { html += '<hr>'; i++; continue; }
            if (isTableAt(lines, i)) {
                const head = splitRow(line), rows = [];
                i += 2;
                while (i < lines.length && lines[i].includes('|') && lines[i].trim()) rows.push(splitRow(lines[i++]));
                const numCol = head.map((_, c) => rows.length > 0 && rows.every(r => !r[c] || isNum(r[c].replace(/\*/g, ''))));
                html += '<div class="table-wrap"><table><thead><tr>'
                    + head.map((h, c) => '<th' + (numCol[c] ? ' class="num"' : '') + '>' + inline(h) + '</th>').join('')
                    + '</tr></thead><tbody>'
                    + rows.map(r => '<tr>' + head.map((_, c) => '<td' + (numCol[c] ? ' class="num"' : '') + '>' + inline(r[c] || '') + '</td>').join('') + '</tr>').join('')
                    + '</tbody></table></div>';
                continue;
            }
            if (RE.quote.test(line)) {
                const buf = [];
                while (i < lines.length && RE.quote.test(lines[i])) buf.push(lines[i++].replace(/^\s*>\s?/, ''));
                html += '<blockquote>' + renderMarkdown(buf.join('\n')) + '</blockquote>';
                continue;
            }
            if (RE.item.test(line)) {
                const list = parseList(lines, i);
                html += list.html;
                i = list.next;
                continue;
            }
            const buf = [];
            while (i < lines.length && lines[i].trim() && (buf.length === 0 || !isBlockStart(lines, i))) buf.push(lines[i++]);
            html += '<p>' + buf.map(inline).join('<br>') + '</p>';
        }
        return html;
    }

    // Bản chữ thuần (bỏ ký hiệu Markdown) để sao chép cả câu trả lời
    function plainText(md) {
        return String(md || '')
            .replace(/^\s*(```|~~~).*$/gm, '')
            .replace(/^#{1,6}\s+/gm, '')
            .replace(/^\s*>\s?/gm, '')
            .replace(/^\s*\|?\s*:?-{2,}[-|:\s]*(\n|$)/gm, '')
            .replace(/^\s*\|(.*)\|\s*$/gm, (_, row) => {
                const c = row.split('|').map(x => x.trim());
                return c.length === 2 ? c[0] + ': ' + c[1] : c.join(' | ');
            })
            .replace(/\*\*(.+?)\*\*/g, '$1').replace(/__(.+?)__/g, '$1')
            .replace(/(^|[^*\w])\*(?=\S)([^*]+?)\*/g, '$1$2')
            .replace(/`([^`]+)`/g, '$1')
            .replace(/\[([^\]]+)\]\((https?:[^)]+)\)/g, '$1 ($2)')
            .replace(/\n{3,}/g, '\n\n').trim();
    }

    async function copyText(text, btn) {
        try {
            await navigator.clipboard.writeText(text);
        } catch (e) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
        }
        const label = btn.querySelector('em');
        btn.classList.add('is-done');
        if (label) label.textContent = 'Đã chép';
        clearTimeout(btn._t);
        btn._t = setTimeout(() => { btn.classList.remove('is-done'); if (label) label.textContent = 'Sao chép'; }, 1600);
    }

    thread.addEventListener('click', e => {
        const block = e.target.closest('[data-copy-block]');
        if (block) return copyText(block.closest('.md-block').querySelector('pre').textContent, block);
        const all = e.target.closest('[data-copy-all]');
        if (all) copyText(plainText(all.closest('.msg')._raw), all);
    });

    // ---------- Chọn chức năng ----------
    function setPlaceholder() {
        const m = modes[current], f = activeForm();
        input.placeholder = f && f.classList.contains('is-collapsed')
            ? 'Hỏi tiếp, hoặc mở form để nhập đơn mới…'
            : (m.placeholder || 'Hỏi trợ lý Sales…');
    }

    function setMode(key) {
        current = modes[key] ? key : 'auto';
        const m = modes[current];
        document.getElementById('mode-icon').className = m.icon;
        document.getElementById('mode-label').textContent = m.label;
        modeMenu.querySelectorAll('.mode__item').forEach(b => b.setAttribute('aria-checked', b.dataset.mode === current));
        document.querySelectorAll('.af').forEach(f => { f.hidden = f.dataset.form !== m.form; });
        setFormOpen(true);
        chips.innerHTML = '';
        chips.classList.remove('is-hidden');
        (m.suggestions || []).forEach(text => {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = text;
            b.addEventListener('click', () => { if (!send.disabled) ask(text); });
            chips.appendChild(b);
        });
        try { localStorage.setItem(storageKey, current); } catch (e) {}
    }

    function toggleMenu(open) {
        modeMenu.hidden = !open;
        modeBtn.setAttribute('aria-expanded', open);
        if (open) (modeMenu.querySelector('[aria-checked="true"]') || modeMenu.querySelector('button')).focus();
    }

    modeBtn.addEventListener('click', () => toggleMenu(modeMenu.hidden));
    modeMenu.addEventListener('click', e => {
        const item = e.target.closest('.mode__item');
        if (!item) return;
        setMode(item.dataset.mode);
        toggleMenu(false);
        (activeForm()?.querySelector('input,select') || input).focus();
    });
    modeMenu.addEventListener('keydown', e => {
        const items = [...modeMenu.querySelectorAll('.mode__item')];
        const i = items.indexOf(document.activeElement);
        if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
        if (e.key === 'Escape') { toggleMenu(false); modeBtn.focus(); }
    });
    document.addEventListener('click', e => { if (!document.getElementById('mode').contains(e.target)) toggleMenu(false); });

    // ---------- Form → câu hỏi ----------
    function activeForm() { return document.querySelector('.af:not([hidden])'); }

    // Thu gọn form sau khi gửi để khung hội thoại đủ chỗ đọc câu trả lời
    function setFormOpen(open) {
        const f = activeForm();
        formToggle.hidden = !f;
        if (f) {
            f.classList.toggle('is-collapsed', !open);
            formToggle.setAttribute('aria-expanded', open);
            formToggle.innerHTML = open
                ? '<i class="fas fa-chevron-down"></i> Thu gọn form'
                : '<i class="fas fa-list-ul"></i> Mở form nhập';
        }
        setPlaceholder();
        updateSend();
    }
    formToggle.addEventListener('click', () => setFormOpen(activeForm().classList.contains('is-collapsed')));
    const fmt = n => Number(n).toLocaleString('vi-VN');
    const dmy = d => d ? d.split('-').reverse().join('/') : '';

    const composers = {
        quote(v) {
            const parts = [`Báo giá cho ${v.don_vi}: ${v.dich_vu}, ${fmt(v.so_cas)} cas`];
            if (v.so_ngay) parts.push(`chụp ${v.so_ngay} ngày`);
            if (v.ngay_chup) parts.push(`ngày chụp ${dmy(v.ngay_chup)}`);
            parts.push(v.dia_diem ? `địa điểm ${v.dia_diem}` : 'chưa xác nhận địa điểm');
            return parts.join(', ') + '.';
        },
        profit(v) {
            const lines = ['Tính lợi nhuận' + (v.don_vi ? ` đơn ${v.don_vi}` : '') + ':'];
            if (v.so_cas_xq) lines.push(`- XQ phổi: ${fmt(v.so_cas_xq)} cas` + (v.gia_xq ? `, đơn giá ${fmt(v.gia_xq)}đ` : ''));
            if (v.so_cas_cstl) lines.push(`- CSTL: ${fmt(v.so_cas_cstl)} cas` + (v.gia_cstl ? `, đơn giá ${fmt(v.gia_cstl)}đ` : ''));
            lines.push(`- ${v.so_ngay} ngày công tác, ${v.so_dem || 0} đêm lưu trú, tổng ${fmt(v.tong_km)} km đi về, ${v.so_nguoi} nhân sự đi`);
            if (v.phu_thu) lines.push(`- Phụ thu ${fmt(v.phu_thu)}đ`);
            if (v.chiet_khau) lines.push(`- Chiết khấu ${fmt(v.chiet_khau)}đ`);
            return lines.join('\n');
        },
    };

    function composeFromForm() {
        const f = activeForm();
        // Form đang thu gọn = người dùng chỉ gõ câu hỏi tự do
        if (!f || f.classList.contains('is-collapsed')) return { ok: true, text: '' };
        const fields = [...f.querySelectorAll('input, select')];
        fields.forEach(el => el.classList.add('touched'));
        const invalid = fields.find(el => !el.checkValidity());
        if (invalid) { invalid.focus(); invalid.reportValidity(); return { ok: false }; }
        if (f.dataset.form === 'profit' && !f.querySelector('[name=so_cas_xq]').value && !f.querySelector('[name=so_cas_cstl]').value) {
            const el = f.querySelector('[name=so_cas_xq]');
            el.setCustomValidity('Nhập số cas XQ phổi hoặc CSTL'); el.reportValidity(); el.setCustomValidity('');
            return { ok: false };
        }
        const v = Object.fromEntries(fields.map(el => [el.name, el.value.trim()]));
        return { ok: true, text: composers[f.dataset.form](v) };
    }

    function resetForm() {
        const f = activeForm();
        if (!f) return;
        f.querySelectorAll('input').forEach(el => { el.value = el.defaultValue; el.classList.remove('touched'); });
        f.querySelectorAll('select').forEach(el => { el.selectedIndex = 0; el.classList.remove('touched'); });
    }

    // ---------- Hội thoại ----------
    function scrollDown() { log.scrollTop = log.scrollHeight; }

    function addUser(text, modeLabel) {
        emptyState.hidden = true;
        // Gợi ý chỉ hiện khi bắt đầu hoặc vừa đổi chức năng, để dành chỗ cho hội thoại
        chips.classList.add('is-hidden');
        const el = document.createElement('div');
        el.className = 'msg msg--user';
        if (modeLabel) {
            const tag = document.createElement('span');
            tag.className = 'msg__mode';
            tag.textContent = modeLabel;
            el.appendChild(tag);
        }
        el.appendChild(document.createTextNode(text));
        thread.appendChild(el);
        scrollDown();
    }

    function addBot(markdown, tools) {
        const el = document.createElement('div');
        el.className = 'msg msg--bot';
        el._raw = markdown;
        el.innerHTML = '<div class="md">' + renderMarkdown(markdown) + '</div>'
            + '<div class="msg__actions"><button type="button" class="icon-btn" data-copy-all title="Sao chép câu trả lời" aria-label="Sao chép câu trả lời">'
            + ICON_COPY + ICON_CHECK + '</button>'
            + (tools && tools.length ? '<span>Đã ' + esc(tools.map(n => toolNames[n] || n).join(', ')) + '</span>' : '')
            + '</div>';
        thread.appendChild(el);
        scrollDown();
    }

    function addNote(text, cls) {
        const el = document.createElement('div');
        el.className = 'msg ' + cls;
        el.textContent = text;
        thread.appendChild(el);
        scrollDown();
        return el;
    }

    function addTyping() {
        const el = document.createElement('div');
        el.className = 'msg msg--typing';
        el.innerHTML = '<span class="dots"><i></i><i></i><i></i></span><span>Đang tra cứu và soạn câu trả lời…</span>';
        thread.appendChild(el);
        scrollDown();
        return el;
    }

    async function api(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: method === 'GET' ? undefined : JSON.stringify(body || {}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const err = new Error(data.message || ('Lỗi ' + res.status));
            err.data = data;
            throw err;
        }
        return data;
    }

    function setBusy(busy) {
        send.disabled = busy;
        send.classList.toggle('is-loading', busy);
    }

    async function ask(text) {
        const mode = current;
        addUser(text, mode !== 'auto' ? modes[mode].label : null);
        const typing = addTyping();
        const seq = viewSeq;
        // Người dùng đã chuyển sang cuộc trò chuyện khác trong lúc chờ: chỉ cập nhật danh sách
        const stillHere = () => seq === viewSeq;
        setBusy(true);
        try {
            const data = await api('POST', askUrl, { message: text, mode, conversation_id: conversationId });
            typing.remove();
            if (!stillHere()) { loadList(); return true; }
            addBot(data.reply, data.tools);
            setConversation(data.conversation);
            return true;
        } catch (e) {
            typing.remove();
            if (!stillHere()) { loadList(); return false; }
            addNote(e.message, 'msg--error');
            if (e.data && e.data.conversation) setConversation(e.data.conversation);
            return false;
        } finally {
            setBusy(false);
            updateSend();
            input.focus();
        }
    }

    // ---------- Ô nhập ----------
    function autoGrow() {
        input.style.height = 'auto';
        // Ô trống: giữ 1 dòng (Chrome tính cả placeholder vào scrollHeight)
        if (!input.value) return;
        input.style.height = Math.min(input.scrollHeight, 220) + 'px';
        input.style.overflowY = input.scrollHeight > 220 ? 'auto' : 'hidden';
    }
    function updateSend() {
        const f = activeForm();
        const hasForm = f && !f.classList.contains('is-collapsed');
        send.classList.toggle('is-empty', !input.value.trim() && !hasForm);
    }
    input.addEventListener('input', () => { autoGrow(); updateSend(); });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (send.disabled) return;
        const composed = composeFromForm();
        if (!composed.ok) return;
        const note = input.value.trim();
        const text = [composed.text, note && composed.text ? 'Ghi chú: ' + note : note].filter(Boolean).join('\n');
        if (!text) { input.focus(); return; }
        input.value = '';
        autoGrow();
        if (await ask(text) && composed.text) { resetForm(); setFormOpen(false); }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    // Enter trong ô của form chức năng cũng gửi luôn
    document.querySelectorAll('.af input').forEach(el => el.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.isComposing) { e.preventDefault(); form.requestSubmit(); }
    }));

    // ---------- Lịch sử trò chuyện ----------
    let conversationId = null;
    let viewSeq = 0;
    let scope = 'mine';
    let items = [];
    const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

    function timeLabel(iso) {
        if (!iso) return '';
        const d = new Date(iso), now = new Date();
        const hm = d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        const day = x => new Date(x.getFullYear(), x.getMonth(), x.getDate()).getTime();
        const diff = Math.round((day(now) - day(d)) / 86400000);
        if (diff === 0) return 'Hôm nay ' + hm;
        if (diff === 1) return 'Hôm qua ' + hm;
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: d.getFullYear() === now.getFullYear() ? undefined : 'numeric' });
    }

    function renderList() {
        convList.innerHTML = '';
        if (!items.length) {
            convList.innerHTML = '<li class="side__empty">' + (convSearch.value.trim() ? 'Không tìm thấy cuộc trò chuyện nào.' : 'Chưa có cuộc trò chuyện nào.') + '</li>';
            return;
        }
        items.forEach(c => {
            const li = document.createElement('li');
            li.className = 'conv' + (c.id === conversationId ? ' is-active' : '');
            li.dataset.id = c.id;
            const meta = (scope === 'all' ? c.user_name + ' · ' : '') + timeLabel(c.last_message_at);
            li.innerHTML = '<button type="button" class="conv__open"><span class="conv__title"></span><span class="conv__meta"></span></button>'
                + '<span class="conv__actions">'
                + (c.is_mine ? '<button type="button" class="icon-btn" data-act="rename" title="Đổi tên" aria-label="Đổi tên"><i class="fas fa-pen" style="font-size:11px"></i></button>' : '')
                + '<button type="button" class="icon-btn" data-act="delete" title="Xóa" aria-label="Xóa"><i class="far fa-trash-alt" style="font-size:12px"></i></button>'
                + '</span>';
            li.querySelector('.conv__title').textContent = c.title;
            li.querySelector('.conv__meta').textContent = meta;
            convList.appendChild(li);
        });
    }

    let searchTimer;
    async function loadList() {
        const q = convSearch.value.trim();
        try {
            const data = await api('GET', listUrl + '?scope=' + scope + (q ? '&q=' + encodeURIComponent(q) : ''));
            items = data.items || [];
        } catch (e) {
            items = [];
        }
        renderList();
    }
    convSearch.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadList, 250); });
    document.querySelectorAll('.side__tabs button').forEach(b => b.addEventListener('click', () => {
        scope = b.dataset.scope;
        document.querySelectorAll('.side__tabs button').forEach(x => x.setAttribute('aria-pressed', x === b));
        loadList();
    }));

    function setUrl(id) {
        try { history.replaceState(null, '', id ? pageUrl + '?c=' + id : pageUrl); } catch (e) {}
    }

    // Sau khi gửi: ghi nhận cuộc trò chuyện (mới tạo hoặc đang tiếp tục) và đưa lên đầu danh sách
    function setConversation(c) {
        if (!c) return;
        const isNew = conversationId !== c.id;
        conversationId = c.id;
        convHeading.textContent = c.title;
        if (isNew) setUrl(c.id);
        loadList();
    }

    function clearThread() {
        viewSeq++;
        thread.querySelectorAll('.msg').forEach(el => el.remove());
    }

    function setReadonly(conv) {
        const ro = !!(conv && conv.readonly);
        composerWrap.hidden = ro;
        readonlyBar.hidden = !ro;
        if (ro) document.getElementById('readonly-text').textContent = 'Đang xem cuộc trò chuyện của ' + conv.user_name + ' (chỉ xem, không hỏi tiếp được).';
    }

    function closeSideOnMobile() { if (isMobile()) root.classList.remove('side-open'); }

    function newConversation() {
        conversationId = null;
        clearThread();
        emptyState.hidden = false;
        chips.classList.remove('is-hidden');
        convHeading.textContent = '';
        setReadonly(null);
        setUrl(null);
        renderList();
        closeSideOnMobile();
        input.focus();
    }

    async function openConversation(id) {
        closeSideOnMobile();
        try {
            const data = await api('GET', convUrl(id));
            conversationId = data.conversation.id;
            clearThread();
            emptyState.hidden = true;
            chips.classList.add('is-hidden');
            convHeading.textContent = data.conversation.title + (data.conversation.readonly ? ' · ' + data.conversation.user_name : '');
            data.messages.forEach(m => {
                if (m.role === 'user') addUser(m.content, m.mode_label);
                else if (m.role === 'assistant') addBot(m.content, m.tools);
                else addNote(m.content, 'msg--error');
            });
            setReadonly(data.conversation);
            setUrl(conversationId);
            renderList();
            log.style.scrollBehavior = 'auto';
            scrollDown();
            log.style.scrollBehavior = '';
        } catch (e) {
            newConversation();
            addNote(e.message.includes('404') || e.message.includes('No query') ? 'Không tìm thấy cuộc trò chuyện này (có thể đã bị xóa).' : e.message, 'msg--error');
        }
    }

    convList.addEventListener('click', async e => {
        const li = e.target.closest('.conv');
        if (!li) return;
        const id = Number(li.dataset.id);
        const act = e.target.closest('[data-act]')?.dataset.act;
        if (act === 'delete') {
            if (!await uiConfirm('Cuộc trò chuyện sẽ bị xóa hẳn, không khôi phục lại được.', { title: 'Xóa cuộc trò chuyện này?', ok: 'Xóa', danger: true })) return;
            try {
                await api('DELETE', convUrl(id));
                if (id === conversationId) newConversation();
                loadList();
            } catch (err) { uiAlert(err.message); }
            return;
        }
        if (act === 'rename') return startRename(li, id);
        if (e.target.closest('.conv__open')) openConversation(id);
    });

    function startRename(li, id) {
        const item = items.find(c => c.id === id);
        const field = document.createElement('input');
        field.className = 'conv__rename';
        field.value = item ? item.title : '';
        field.maxLength = 200;
        li.replaceChildren(field);
        field.focus();
        field.select();
        let done = false;
        const finish = async save => {
            if (done) return;
            done = true;
            const title = field.value.trim();
            if (save && title && item && title !== item.title) {
                try {
                    await api('PATCH', convUrl(id), { title });
                    if (id === conversationId) convHeading.textContent = title;
                } catch (err) { uiAlert(err.message); }
            }
            loadList();
        };
        field.addEventListener('keydown', ev => {
            if (ev.key === 'Enter' && !ev.isComposing) { ev.preventDefault(); finish(true); }
            if (ev.key === 'Escape') finish(false);
        });
        field.addEventListener('blur', () => finish(true));
    }

    document.getElementById('new-chat').addEventListener('click', newConversation);
    document.getElementById('assistant-reset').addEventListener('click', newConversation);
    document.getElementById('side-backdrop').addEventListener('click', () => root.classList.remove('side-open'));
    document.getElementById('side-toggle').addEventListener('click', () => {
        if (isMobile()) { root.classList.toggle('side-open'); return; }
        root.classList.toggle('side-closed');
        try { localStorage.setItem('assistant.sideClosed', root.classList.contains('side-closed') ? '1' : ''); } catch (e) {}
    });
    try { if (localStorage.getItem('assistant.sideClosed')) root.classList.add('side-closed'); } catch (e) {}

    document.getElementById('dept-switch')?.addEventListener('change', e => { window.location = e.target.value; });

    setMode(current);
    autoGrow();
    loadList();
    if (initialConversation) openConversation(initialConversation);
})();
