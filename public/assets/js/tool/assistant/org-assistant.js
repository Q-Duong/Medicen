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
        tra_cuu_don_vi: 'tra cứu đơn vị', xem_lich_xe: 'xem lịch xe', xem_nguoi_di_don: 'xem người đi đơn', cong_no_ct: 'xem công nợ CT', so_vcb_chu_loc: 'xem sổ VCB chú Lộc', xem_nhan_su: 'xem nhân sự', xem_cong_no: 'xem công nợ',
        tinh_loi_nhuan: 'tính lợi nhuận', xem_don_gia_mac_dinh: 'xem bảng giá', cap_nhat_don_gia_mac_dinh: 'CẬP NHẬT giá mặc định',
        // Trợ lý Kế toán
        tong_quan_du_lieu: 'xem dữ liệu sổ sách', ket_qua_kinh_doanh: 'tính kết quả kinh doanh', phat_sinh_tai_khoan: 'xem phát sinh tài khoản',
        tim_but_toan: 'tìm bút toán', so_quy_ngan_hang: 'xem sổ quỹ / ngân hàng', cong_no_doi_tac: 'xem công nợ sổ sách',
        luong_thuc_tra: 'xem lương', cong_no_web: 'xem công nợ web', thu_chi_web: 'xem kế hoạch thu chi', tai_lieu: 'tra tài liệu',
        phan_tich_van_hanh: 'phân tích vận hành', hoat_dong_nhan_vien: 'xem hoạt động nhân viên',
        tuoi_no_tien_ket: 'xem tuổi nợ, tiền kẹt', du_bao_dong_tien: 'dự báo dòng tiền', bao_cao_quan_tri: 'lập báo cáo quản trị', ghi_nho: 'xem ghi nhớ CFO', gia_dinh_neu: 'tính giả định', nhat_ky_sales: 'xem nhật ký trao đổi khách', xem_don_hang: 'xem đơn hàng', kiem_tra_gia_hop_dong: 'đối chiếu giá hợp đồng', sua_gia_don: 'sửa giá đơn', ke_hoach_nam: 'xem kế hoạch năm', theo_doi_thu_no: 'xem thu nợ', duyet_gia: 'xem đơn chờ duyệt giá', hop_dong_khach_hang: 'xem hợp đồng khách hàng', gia_hop_dong: 'xem giá theo hợp đồng', soan_hop_dong: 'soạn hợp đồng Word'
    };
    const ICON_COPY = '<svg class="i-copy" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>';
    const ICON_CHECK = '<svg class="i-check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>';

    let current = 'auto';
    try { const saved = localStorage.getItem(storageKey); if (saved && modes[saved]) current = saved; } catch (e) {}

    // ---------- Markdown → HTML (tự viết, escape mọi thứ trước khi chèn) ----------
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const MSG_LANGS = ['tin-nhan', 'tinnhan', 'tin_nhan', 'khach', 'message'];

    // ---------- Biểu đồ: trợ lý trả khối ```bieu-do {JSON}```, trang vẽ bằng Chart.js (tải khi cần) ----------
    // JSON: { "loai": "cot|duong|tron|cot-chong|ket-hop", "tieu_de": "...", "nhan": ["T1", ...], "don_vi": "đ|%|ca|...",
    //         "chuoi": [{ "ten": "Doanh thu", "so": [..], "loai": "cot|duong", "truc": "phai" }] }
    // (nhận cả khóa tiếng Anh: type, title, labels, unit, datasets[{label, data, type}]).
    const CHART_LANGS = ['bieu-do', 'bieudo', 'bieu_do', 'chart'];
    const CHART_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
    const PALETTE = ['#c96442', '#2f6fd1', '#0f9d6b', '#d49b00', '#9b4dca', '#0f9b9b', '#7b8794', '#e0703a', '#5b4bc4', '#b42318'];
    let chartLib = null;

    function normalizeChart(raw) {
        let j;
        try { j = JSON.parse(raw); } catch (e) { return null; }
        if (!j || typeof j !== 'object') return null;
        const series = (j.chuoi || j.datasets || []).map(d => ({
            name: String(d.ten ?? d.label ?? ''),
            data: (d.so ?? d.data ?? []).map(v => (v === null || v === '' ? null : Number(String(v).replace(/[^\d.-]/g, '')))),
            type: d.loai ?? d.type ?? null,
            right: (d.truc ?? d.axis) === 'phai' || (d.truc ?? d.axis) === 'right',
            unit: d.don_vi ?? d.unit ?? null,
        })).filter(d => d.data.length);
        const labels = (j.nhan || j.labels || []).map(String);
        if (!series.length || !labels.length) return null;
        return { type: String(j.loai || j.type || 'cot').toLowerCase(), title: String(j.tieu_de || j.title || ''), labels, unit: j.don_vi || j.unit || '', note: j.ghi_chu || j.note || '', series };
    }

    function chartFigure(raw) {
        const spec = normalizeChart(raw);
        if (!spec) return copyBlock('code', 'Biểu đồ (dữ liệu lỗi)', raw);
        return '<figure class="md-chart" data-spec="' + esc(JSON.stringify(spec)) + '">'
            + (spec.title ? '<figcaption class="md-chart__title">' + esc(spec.title) + '</figcaption>' : '')
            + '<div class="md-chart__box"><canvas role="img" aria-label="' + esc(spec.title || 'Biểu đồ') + '"></canvas></div>'
            + (spec.note ? '<div class="md-chart__note">' + esc(spec.note) + '</div>' : '')
            + '</figure>';
    }

    function loadChartLib() {
        if (window.Chart) return Promise.resolve(window.Chart);
        if (chartLib) return chartLib;
        chartLib = new Promise((resolve, reject) => {
            const sc = document.createElement('script');
            sc.src = CHART_SRC;
            sc.onload = () => resolve(window.Chart);
            sc.onerror = () => { chartLib = null; reject(new Error('Không tải được thư viện biểu đồ')); };
            document.head.appendChild(sc);
        });
        return chartLib;
    }

    // Số tiền trên trục: 1,2 tỷ / 350 tr; trong chú thích: đầy đủ 1.234.567 đ
    const fmtFull = (v, unit) => v === null || v === undefined || isNaN(v) ? '—'
        : new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(v) + (unit ? (unit === '%' ? '%' : ' ' + unit) : '');
    function fmtShort(v, unit) {
        if (unit === 'đ' || unit === 'VNĐ' || unit === 'vnđ') {
            const a = Math.abs(v);
            if (a >= 1e9) return (v / 1e9).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' tỷ';
            if (a >= 1e6) return (v / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + ' tr';
            if (a >= 1e3) return (v / 1e3).toLocaleString('vi-VN', { maximumFractionDigits: 0 }) + ' k';
        }
        return v.toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + (unit === '%' ? '%' : '');
    }

    async function hydrateCharts(scope) {
        const figs = [...scope.querySelectorAll('.md-chart:not(.is-ready)')];
        if (!figs.length) return;
        let Chart;
        try { Chart = await loadChartLib(); } catch (e) {
            figs.forEach(f => { f.querySelector('.md-chart__box').innerHTML = '<div class="md-chart__err">Không tải được biểu đồ (kiểm tra kết nối mạng). Số liệu vẫn có trong bảng.</div>'; });
            return;
        }
        figs.forEach(fig => {
            fig.classList.add('is-ready');
            let spec;
            try { spec = JSON.parse(fig.dataset.spec); } catch (e) { return; }
            const pie = spec.type === 'tron' || spec.type === 'pie' || spec.type === 'doughnut';
            const stacked = spec.type === 'cot-chong' || spec.type === 'stacked';
            const baseType = spec.type === 'duong' || spec.type === 'line' ? 'line' : 'bar';
            const rightUnit = (spec.series.find(s => s.right) || {}).unit || (spec.series.some(s => s.right) ? '%' : null);
            const datasets = pie
                ? [{ label: spec.series[0].name, data: spec.series[0].data, backgroundColor: spec.labels.map((_, i) => PALETTE[i % PALETTE.length]), borderColor: '#fff', borderWidth: 2 }]
                : spec.series.map((s, i) => {
                    const t = s.type === 'duong' || s.type === 'line' ? 'line' : s.type === 'cot' || s.type === 'bar' ? 'bar' : baseType;
                    const c = PALETTE[i % PALETTE.length];
                    return {
                        type: t, label: s.name, data: s.data, yAxisID: s.right ? 'y1' : 'y', _unit: s.unit || (s.right ? rightUnit : spec.unit),
                        backgroundColor: t === 'line' ? c : c + 'cc', borderColor: c, borderWidth: t === 'line' ? 2.5 : 0,
                        borderRadius: t === 'bar' ? 5 : 0, pointRadius: t === 'line' ? 3 : 0, tension: .25, order: t === 'line' ? 0 : 1,
                    };
                });
            const scales = pie ? {} : {
                x: { stacked, grid: { display: false }, ticks: { color: '#73716c', font: { size: 11 } } },
                y: { stacked, beginAtZero: true, grid: { color: '#eeebe3' }, ticks: { color: '#73716c', font: { size: 11 }, callback: v => fmtShort(v, spec.unit) } },
            };
            if (!pie && spec.series.some(s => s.right)) {
                scales.y1 = { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { color: '#73716c', font: { size: 11 }, callback: v => fmtShort(v, rightUnit) } };
            }
            const total = pie ? spec.series[0].data.reduce((a, b) => a + (b || 0), 0) : 0;
            const config = {
                type: pie ? 'doughnut' : baseType,
                data: { labels: spec.labels, datasets },
                options: {
                    responsive: true, maintainAspectRatio: false, animation: { duration: 300 },
                    interaction: { mode: pie ? 'nearest' : 'index', intersect: pie },
                    plugins: {
                        legend: { display: pie || datasets.length > 1, position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, color: '#1f1e1d', font: { size: 12 } } },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const v = ctx.parsed && typeof ctx.parsed === 'object' ? ctx.parsed.y : ctx.parsed;
                                    const unit = pie ? spec.unit : (ctx.dataset._unit ?? spec.unit);
                                    return ' ' + (pie ? ctx.label : ctx.dataset.label) + ': ' + fmtFull(v, unit)
                                        + (pie && total ? ' (' + (v / total * 100).toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + '%)' : '');
                                },
                            },
                        },
                    },
                    scales,
                    cutout: pie ? '55%' : undefined,
                },
            };
            fig._config = config;
            fig._chart = new Chart(fig.querySelector('canvas'), config);
        });
    }

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
                if (CHART_LANGS.includes(lang)) {
                    html += chartFigure(raw);
                    continue;
                }
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
            // Khối biểu đồ (JSON) không chép ra chữ
            .replace(/^\s*(```|~~~)\s*(bieu-do|bieudo|bieu_do|chart)\s*\n[\s\S]*?^\s*\1\s*$/gim, '[Biểu đồ]')
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
        const pdf = e.target.closest('[data-pdf]');
        if (pdf) exportPdf(pdf.closest('.msg'));
    });

    // Vẽ lại biểu đồ ra ảnh khổ cố định cho bản in (không phụ thuộc độ rộng khung chat, không chạy hiệu ứng)
    function chartImage(config) {
        if (!window.Chart) return null;
        const pie = config.type === 'doughnut';
        const c = document.createElement('canvas');
        // Gần bằng bề rộng trang A4 để chữ trên biểu đồ không bị thu quá nhỏ khi in
        c.width = pie ? 640 : 760;
        c.height = pie ? 320 : 320;
        c.style.cssText = 'position:fixed;left:-9999px;top:0;width:' + c.width + 'px;height:' + c.height + 'px';
        document.body.appendChild(c);
        let chart;
        try {
            chart = new window.Chart(c, Object.assign({}, config, {
                options: Object.assign({}, config.options, { responsive: false, animation: false, devicePixelRatio: 2 }),
                data: { labels: config.data.labels, datasets: config.data.datasets.map(d => Object.assign({}, d)) },
            }));
            return c.toDataURL('image/png');
        } catch (e) {
            return null;
        } finally {
            if (chart) chart.destroy();
            c.remove();
        }
    }

    // ---------- Xuất PDF: in câu trả lời (kèm câu hỏi, biểu đồ thành ảnh) qua hộp thoại In → "Lưu thành PDF" ----------
    function exportPdf(msg) {
        if (!msg) return;
        let q = msg.previousElementSibling;
        while (q && !q.classList.contains('msg--user')) q = q.previousElementSibling;
        const question = q ? [...q.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent).join(' ').trim() : '';
        const mode = q && q.querySelector('.msg__mode') ? q.querySelector('.msg__mode').textContent : '';
        const body = msg.querySelector('.md').cloneNode(true);
        // Biểu đồ: canvas → ảnh (canvas không in sang cửa sổ khác được)
        const srcFigs = msg.querySelectorAll('.md-chart');
        body.querySelectorAll('.md-chart').forEach((fig, i) => {
            const box = fig.querySelector('.md-chart__box');
            const src = srcFigs[i];
            if (!box) return;
            const url = src && src._config ? chartImage(src._config) : null;
            if (url) box.innerHTML = '<img alt="" src="' + url + '">';
            else box.remove();
        });
        body.querySelectorAll('.copy-btn').forEach(b => b.remove());
        const now = new Date();
        const stamp = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        const title = (convHeading.textContent || 'Báo cáo trợ lý').split(' · ')[0];
        const fileTitle = (title + ' ' + now.toLocaleDateString('vi-VN')).replace(/[\\/:*?"<>|]+/g, '-');

        const html = '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>' + esc(fileTitle) + '</title><style>'
            + '@page{size:A4;margin:16mm 14mm 18mm}*{box-sizing:border-box}html{color-scheme:light;background:#fff}'
            + 'body{margin:0;background:#fff;font:13px/1.55 -apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#1f1e1d;-webkit-print-color-adjust:exact;print-color-adjust:exact}'
            + '.hd{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;padding-bottom:10px;margin-bottom:14px;border-bottom:2px solid #c96442}'
            + '.hd b{display:block;font-size:18px}.hd span{color:#73716c;font-size:12px}.hd .r{text-align:right}'
            + '.q{margin:0 0 16px;padding:10px 14px;border-left:3px solid #c96442;background:#faf9f5;border-radius:6px}.q small{display:block;color:#73716c;font-size:11px;margin-bottom:2px}'
            + 'h3,h4,h5,h6{margin:18px 0 6px;break-after:avoid}h3{font-size:16px}h4{font-size:14.5px}p{margin:0 0 8px}ul,ol{margin:0 0 8px;padding-left:20px}'
            + 'table{width:100%;border-collapse:collapse;margin:6px 0 12px;font-size:12px;break-inside:avoid}th,td{padding:5px 8px;border:1px solid #e2ded3;text-align:left;vertical-align:top}'
            + 'th{background:#f4f2eb;font-weight:600}td.num,th.num{text-align:right;white-space:nowrap}'
            + '.md-chart{margin:10px 0 16px;padding:10px 12px;border:1px solid #e2ded3;border-radius:8px;break-inside:avoid}.md-chart__title{font-weight:600;margin-bottom:6px}'
            + '.md-chart img{display:block;max-width:100%;height:auto;margin:0 auto;background:#fff}.md-chart__note{color:#73716c;font-size:11px;margin-top:4px}'
            + '.md-block{margin:8px 0;border:1px solid #e2ded3;border-radius:8px;break-inside:avoid}.md-block__head{padding:6px 10px;background:#f4f2eb;font-size:12px;font-weight:600}'
            + '.md-block pre{margin:0;padding:8px 10px;white-space:pre-wrap;font:12px/1.5 inherit}blockquote{margin:8px 0;padding:6px 12px;border-left:3px solid #e2ded3;color:#4a4843}'
            + '.ft{margin-top:22px;padding-top:8px;border-top:1px solid #e2ded3;color:#73716c;font-size:11px}'
            + '</style></head><body>'
            + '<div class="hd"><div><b>' + esc(title) + '</b><span>' + esc((CFG.assistantTitle || 'Trợ lý AI') + (mode ? ' · ' + mode : '')) + '</span></div>'
            + '<div class="r"><span>Medicen · Nam Khánh Linh</span><br><span>Xuất lúc ' + esc(stamp) + (CFG.userName ? ' · ' + esc(CFG.userName) : '') + '</span></div></div>'
            + (question ? '<div class="q"><small>Câu hỏi</small>' + esc(question) + '</div>' : '')
            + body.innerHTML
            + '<div class="ft">Nội dung do trợ lý AI tổng hợp từ dữ liệu nội bộ Medicen; số liệu nêu nguồn và kỳ trong từng phần. Tài liệu nội bộ, không gửi ra ngoài.</div>'
            + '</body></html>';

        const frame = document.createElement('iframe');
        frame.setAttribute('aria-hidden', 'true');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
        document.body.appendChild(frame);
        const doc = frame.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        const go = () => {
            // Tên file gợi ý khi lưu PDF lấy theo tiêu đề trang
            const oldTitle = document.title;
            document.title = fileTitle;
            try { frame.contentWindow.focus(); frame.contentWindow.print(); } finally {
                document.title = oldTitle;
                setTimeout(() => frame.remove(), 60000);
            }
        };
        const imgs = [...doc.images];
        Promise.all(imgs.map(img => img.complete ? null : new Promise(r => { img.onload = img.onerror = r; }))).then(() => setTimeout(go, 150));
    }

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
        const addChip = (text, key) => {
            const b = document.createElement('button');
            b.type = 'button';
            if (key) {
                // Gợi ý của chức năng khác: ghi tên chức năng, bấm là chuyển sang chức năng đó rồi hỏi luôn
                b.className = 'chip--mode';
                b.innerHTML = '<i class="' + esc(modes[key].icon) + '"></i><small>' + esc(modes[key].label) + '</small>' + esc(text);
            } else {
                b.textContent = text;
            }
            b.addEventListener('click', () => {
                if (send.disabled) return;
                if (key) {
                    setMode(key);
                    if (activeForm()) setFormOpen(false);
                }
                ask(text);
            });
            chips.appendChild(b);
        };
        if (current === 'auto') {
            const shown = (m.suggestions || []).slice(0, 2);
            shown.forEach(text => addChip(text));
            // Mỗi chức năng một câu, bỏ câu đã hiện ở trên
            Object.keys(modes).filter(k => k !== 'auto').forEach(k => {
                const text = (modes[k].suggestions || []).find(t => !shown.includes(t));
                if (text) { shown.push(text); addChip(text, k); }
            });
        } else {
            (m.suggestions || []).forEach(text => addChip(text));
        }
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
        contract(v) {
            const seasonal = v.loai === 'thoi_vu';
            const isNew = v.khach_moi === '1';
            const who = isNew ? v.ten_ben_a : v.don_vi;
            let head = `Soạn hợp đồng ${seasonal ? 'thời vụ' : 'nguyên tắc'} cho ${who}`;
            if (v.dua_theo && v.dua_theo !== v.don_vi) head += `, làm giống hợp đồng của ${v.dua_theo}`;
            const lines = [head + '.'];
            if (isNew) {
                lines.push(`- Khách mới (chưa có trên web): ${v.ten_ben_a}` + (v.mst ? `, MST ${v.mst}` : '') + (v.dia_chi ? `, địa chỉ ${v.dia_chi}` : '')
                    + (v.ma ? `, mã trong số HĐ: ${v.ma}` : ''));
            }
            const rep = [v.dai_dien && `đại diện ${v.dai_dien}`, v.chuc_vu && `chức vụ ${v.chuc_vu}`, v.dien_thoai && `điện thoại ${v.dien_thoai}`].filter(Boolean);
            if (rep.length) lines.push('- Bên A: ' + rep.join(', '));
            if (v.ngay_ky) lines.push(`- Ngày ký: ${dmy(v.ngay_ky)}`);
            if (seasonal) {
                lines.push(`- Ngày chụp ${dmy(v.ngay_chup)}, trọn gói ${fmt(v.gia_tron_goi)}đ (đã gồm VAT)`);
                if (v.cong_ty) lines.push(`- Chụp cho: ${v.cong_ty}`);
                if (v.dia_diem_chup) lines.push(`- Địa điểm: ${v.dia_diem_chup}`);
            } else {
                lines.push(v.doi_gia ? `- Đổi giá: ${v.doi_gia}` : '- Giữ nguyên bảng giá của hợp đồng mẫu');
                if (v.thanh_toan) lines.push(`- Thanh toán trong ${v.thanh_toan} ngày`);
                if (v.thoi_han === '0') lines.push('- Thời hạn: đến khi một bên thanh lý');
                else if (v.thoi_han === '12') lines.push('- Thời hạn: 12 tháng, tự động gia hạn');
                else if (v.thoi_han === '12-khong') lines.push('- Thời hạn: 12 tháng, không tự gia hạn');
            }
            return lines.join('\n');
        },
    };

    // Form hợp đồng: đổi loại thì hiện ô của loại đó (ô ẩn không bắt buộc)
    function syncFormSwitch(f) {
        if (!f || !f.querySelector('[data-af-switch]')) return;
        const type = f.querySelector('select[data-af-switch]');
        const isNew = f.querySelector('input[type=checkbox][data-af-switch]');
        f.querySelectorAll('[data-af-only], [data-af-new]').forEach((el) => {
            el.hidden = (el.dataset.afOnly && type && el.dataset.afOnly !== type.value)
                || (el.dataset.afNew && isNew && el.dataset.afNew !== (isNew.checked ? '1' : '0'));
            el.querySelectorAll('[data-af-required]').forEach((inp) => { inp.required = !el.hidden; });
        });
    }
    document.querySelectorAll('[data-af-switch]').forEach((sw) => sw.addEventListener('change', () => syncFormSwitch(sw.closest('.af'))));
    document.querySelectorAll('.af').forEach(syncFormSwitch);

    function composeFromForm() {
        const f = activeForm();
        // Form đang thu gọn = người dùng chỉ gõ câu hỏi tự do
        if (!f || f.classList.contains('is-collapsed')) return { ok: true, text: '' };
        const fields = [...f.querySelectorAll('input, select, textarea')].filter(el => !el.closest('[hidden]'));
        fields.forEach(el => el.classList.add('touched'));
        const invalid = fields.find(el => !el.checkValidity());
        if (invalid) { invalid.focus(); invalid.reportValidity(); return { ok: false }; }
        if (f.dataset.form === 'profit' && !f.querySelector('[name=so_cas_xq]').value && !f.querySelector('[name=so_cas_cstl]').value) {
            const el = f.querySelector('[name=so_cas_xq]');
            el.setCustomValidity('Nhập số cas XQ phổi hoặc CSTL'); el.reportValidity(); el.setCustomValidity('');
            return { ok: false };
        }
        const v = Object.fromEntries(fields.map(el => [el.name, el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value.trim()]));
        return { ok: true, text: composers[f.dataset.form](v) };
    }

    function resetForm() {
        const f = activeForm();
        if (!f) return;
        f.querySelectorAll('input').forEach(el => { if (el.type === 'checkbox') el.checked = false; else el.value = el.defaultValue; el.classList.remove('touched'); });
        f.querySelectorAll('textarea').forEach(el => { el.value = ''; el.classList.remove('touched'); });
        f.querySelectorAll('select').forEach(el => { el.selectedIndex = 0; el.classList.remove('touched'); });
        syncFormSwitch(f);
    }

    // ---------- Hội thoại ----------
    function scrollDown() { log.scrollTop = log.scrollHeight; }

    // Chức năng của câu hỏi gần nhất: câu trả lời theo sau dùng để biết có hiện nút PDF không
    let lastMode = 'auto';

    function addUser(text, modeLabel, modeKey) {
        lastMode = modeKey || 'auto';
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

    function addBot(markdown, tools, modeKey) {
        const pdfOk = !!(modes[modeKey || lastMode] || {}).pdf;
        const el = document.createElement('div');
        el.className = 'msg msg--bot';
        el._raw = markdown;
        el.innerHTML = '<div class="md">' + renderMarkdown(markdown) + '</div>'
            + '<div class="msg__actions"><button type="button" class="icon-btn" data-copy-all title="Sao chép câu trả lời" aria-label="Sao chép câu trả lời">'
            + ICON_COPY + ICON_CHECK + '</button>'
            + (pdfOk ? '<button type="button" class="icon-btn" data-pdf title="Xuất PDF câu trả lời này" aria-label="Xuất PDF"><i class="far fa-file-pdf" style="font-size:14px"></i></button>' : '')
            + (tools && tools.length ? '<span>Đã ' + esc(tools.map(n => toolNames[n] || n).join(', ')) + '</span>' : '')
            + '</div>';
        thread.appendChild(el);
        hydrateCharts(el);
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

    const sleep = ms => new Promise(r => setTimeout(r, ms));

    /*
     * Chờ câu trả lời của lượt đang hỏi: gọi poll mỗi ~1,5 giây. Mỗi lần poll server còn chạy các tool trợ lý cần
     * (tra cứu DB), nên phải poll tiếp kể cả khi người dùng đã chuyển sang cuộc trò chuyện khác.
     * Lỗi mạng chốc lát thì thử lại; lỗi do server trả về (có message) thì dừng và báo.
     */
    async function waitReply(id) {
        const started = Date.now();
        let fails = 0;
        for (;;) {
            await sleep(1500);
            let data;
            try {
                data = await api('POST', convUrl(id) + '/poll');
                fails = 0;
            } catch (e) {
                if ((e.data && e.data.message) || ++fails >= 5) throw e;
                continue;
            }
            if (data.done) return data;
            if (Date.now() - started > 330000) throw new Error('Trợ lý trả lời quá lâu. Vui lòng tải lại trang để xem lại.');
        }
    }

    // Mở lại cuộc trò chuyện mà câu cuối còn đang chờ trả lời (vd. tải lại trang giữa chừng)
    async function resumePending(id) {
        const typing = addTyping();
        const seq = viewSeq;
        setBusy(true);
        try {
            const data = await waitReply(id);
            typing.remove();
            if (seq !== viewSeq) return;
            if (data.reply == null) { openConversation(id); return; }
            addBot(data.reply, data.tools);
            loadList();
        } catch (e) {
            typing.remove();
            if (seq === viewSeq) addNote(e.message, 'msg--error');
        } finally {
            setBusy(false);
            updateSend();
        }
    }

    function setBusy(busy) {
        send.disabled = busy;
        send.classList.toggle('is-loading', busy);
    }

    async function ask(text) {
        const mode = current;
        addUser(text, mode !== 'auto' ? modes[mode].label : null, mode);
        const typing = addTyping();
        const seq = viewSeq;
        // Người dùng đã chuyển sang cuộc trò chuyện khác trong lúc chờ: chỉ cập nhật danh sách
        const stillHere = () => seq === viewSeq;
        setBusy(true);
        try {
            let data = await api('POST', askUrl, { message: text, mode, conversation_id: conversationId });
            if (data.pending) {
                // Server chỉ nhận câu hỏi rồi trả về ngay; hỏi lại tới khi trợ lý trả lời xong
                if (stillHere()) setConversation(data.conversation);
                data = await waitReply(data.conversation.id);
            }
            typing.remove();
            if (!stillHere()) { loadList(); return true; }
            if (data.reply == null) { openConversation(data.conversation.id); return true; }
            addBot(data.reply, data.tools, mode);
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

    // Tab "Tất cả nhân viên" (Admin): chia nhóm theo từng người, bấm tên để mở / gập; nhóm đang mở được nhớ trong phiên
    const openGroups = new Set();
    let deleteMode = 'soft';

    function convItem(c) {
        const li = document.createElement('li');
        li.className = 'conv' + (c.id === conversationId ? ' is-active' : '') + (c.deleted_at ? ' is-deleted' : '');
        li.dataset.id = c.id;
        const meta = timeLabel(c.last_message_at) + (c.deleted_at ? ' · Đã xóa' + (c.deleted_by ? ' bởi ' + c.deleted_by : '') : '');
        li.innerHTML = '<button type="button" class="conv__open"><span class="conv__title"></span><span class="conv__meta"></span></button>'
            + '<span class="conv__actions">'
            + (c.is_mine && !c.deleted_at ? '<button type="button" class="icon-btn" data-act="rename" title="Đổi tên" aria-label="Đổi tên"><i class="fas fa-pen" style="font-size:11px"></i></button>' : '')
            + '<button type="button" class="icon-btn" data-act="delete" title="' + (deleteMode === 'hard' ? 'Xóa hẳn' : 'Xóa') + '" aria-label="Xóa"><i class="far fa-trash-alt" style="font-size:12px"></i></button>'
            + '</span>';
        li.querySelector('.conv__title').textContent = c.title;
        li.querySelector('.conv__meta').textContent = meta;
        return li;
    }

    function renderList() {
        convList.innerHTML = '';
        if (!items.length) {
            convList.innerHTML = '<li class="side__empty">' + (convSearch.value.trim() ? 'Không tìm thấy cuộc trò chuyện nào.' : 'Chưa có cuộc trò chuyện nào.') + '</li>';
            return;
        }
        if (scope !== 'all') {
            items.forEach(c => convList.appendChild(convItem(c)));
            return;
        }
        // Gom theo người (giữ thứ tự: người có tin nhắn mới nhất lên trước)
        const groups = new Map();
        items.forEach(c => {
            const key = String(c.user_id ?? c.user_name);
            if (!groups.has(key)) groups.set(key, { name: c.user_name, mine: c.is_mine, list: [] });
            groups.get(key).list.push(c);
        });
        const searching = convSearch.value.trim() !== '';
        groups.forEach((g, key) => {
            const hasActive = g.list.some(c => c.id === conversationId);
            const open = searching || hasActive || openGroups.has(key);
            const deleted = g.list.filter(c => c.deleted_at).length;
            const head = document.createElement('li');
            head.className = 'conv-group' + (open ? ' is-open' : '');
            head.innerHTML = '<button type="button" class="conv-group__btn" aria-expanded="' + open + '">'
                + '<i class="fas fa-chevron-right conv-group__chev"></i><span class="conv-group__name"></span>'
                + '<span class="conv-group__count"></span></button>';
            head.querySelector('.conv-group__name').textContent = g.name + (g.mine ? ' (tôi)' : '');
            head.querySelector('.conv-group__count').textContent = g.list.length + (deleted ? ' · ' + deleted + ' đã xóa' : '');
            head.querySelector('button').addEventListener('click', () => {
                openGroups.has(key) ? openGroups.delete(key) : openGroups.add(key);
                renderList();
            });
            convList.appendChild(head);
            if (open) g.list.forEach(c => convList.appendChild(convItem(c)));
        });
    }

    let searchTimer;
    async function loadList() {
        const q = convSearch.value.trim();
        try {
            const data = await api('GET', listUrl + '?scope=' + scope + (q ? '&q=' + encodeURIComponent(q) : ''));
            items = data.items || [];
            if (data.delete_mode) deleteMode = data.delete_mode;
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
                if (m.role === 'user') addUser(m.content, m.mode_label, m.mode);
                else if (m.role === 'assistant') addBot(m.content, m.tools);
                else addNote(m.content, 'msg--error');
            });
            setReadonly(data.conversation);
            setUrl(conversationId);
            renderList();
            log.style.scrollBehavior = 'auto';
            scrollDown();
            log.style.scrollBehavior = '';
            if (data.conversation.pending && !data.conversation.readonly && !send.disabled) resumePending(conversationId);
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
            const msg = deleteMode === 'hard'
                ? 'Cuộc trò chuyện sẽ bị xóa hẳn, không khôi phục lại được.'
                : 'Cuộc trò chuyện sẽ biến mất khỏi danh sách của bạn.';
            if (!await uiConfirm(msg, { title: 'Xóa cuộc trò chuyện này?', ok: 'Xóa', danger: true })) return;
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
