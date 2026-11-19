// Bảng lịch nội bộ (KTV / Sales / Tài xế / Kết quả):
// - gắn thứ trong tuần, tô cuối tuần + hôm nay, nút "Hôm nay";
// - khung bảng cao vừa màn hình (--sch-fill) để không phải cuộn lồng trang + khung;
// - khối Tổng hợp (.accountant) chuyển vào ngăn trượt, mở bằng nút trên thanh lọc;
// - số ngày có lịch dưới tên từng xe.
// Lịch đổi tháng bằng ajax (thay HTML) nên theo dõi DOM để gắn lại.
(function () {
    'use strict';
    var WEEKDAYS = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    var MONTHS = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
    // Ô tháng lưu tên tiếng Anh (November); chấp nhận cả số
    var monthNum = function (v) {
        var n = parseInt(v, 10);
        if (!isNaN(n)) return n;
        var i = MONTHS.indexOf(String(v || '').trim().toLowerCase());
        return i < 0 ? NaN : i + 1;
    };
    // Dấu tích: lịch KTV / tài xế là đã chấm công, lịch Sales / Kết quả là đã cập nhật số ca
    var CHECK_LABEL = /\/(sales|results)(\/|\.|$)/.test(location.pathname) ? 'Đã cập nhật số ca' : 'Đã chấm công';
    var isDesktop = function () { return window.matchMedia('(min-width: 800px)').matches; };

    function selected() {
        var y = document.querySelector('.select-year');
        var m = document.querySelector('.select-month');
        var year = y ? parseInt(y.value, 10) : NaN;
        var month = m ? monthNum(m.value) : NaN;
        return isNaN(year) || isNaN(month) ? null : { year: year, month: month };
    }

    function isCurrentMonth(sel) {
        var now = new Date();
        return !!sel && sel.year === now.getFullYear() && sel.month === now.getMonth() + 1;
    }

    function decorateDays(sel) {
        var today = new Date();
        document.querySelectorAll('.cd-schedule .timeline li').forEach(function (li) {
            var span = li.querySelector('.timeline-day span');
            if (!span) return;
            // Không đổi chữ trong ô: JS lịch gốc đọc text ngày để xếp thẻ; thứ hiện qua CSS ::after (data-wd)
            var day = parseInt(span.textContent, 10);
            if (isNaN(day)) return;
            var wd = new Date(sel.year, sel.month - 1, day).getDay();
            if (span.getAttribute('data-wd') !== WEEKDAYS[wd]) span.setAttribute('data-wd', WEEKDAYS[wd]);
            li.classList.toggle('is-weekend', wd === 6);
            li.classList.toggle('is-sunday', wd === 0);
            li.classList.toggle('is-today', isCurrentMonth(sel) && day === today.getDate());
        });
    }

    function decorateCars() {
        document.querySelectorAll('.cd-schedule .events .events-group').forEach(function (group) {
            var name = group.querySelector('.top-info > span');
            if (!name) return;
            var days = {};
            group.querySelectorAll('.single-event[data-start]').forEach(function (ev) { days[ev.getAttribute('data-start')] = 1; });
            var n = Object.keys(days).length;
            var text = n ? n + ' ngày có lịch' : 'Chưa có lịch';
            if (!name.querySelector('.sch-car-dot')) name.insertAdjacentHTML('afterbegin', '<i class="sch-car-dot"></i>');
            var small = name.querySelector('.sch-car-count');
            if (!small) { small = document.createElement('small'); small.className = 'sch-car-count'; name.appendChild(small); }
            if (small.textContent !== text) small.textContent = text;
        });
    }

    // ---------- Chiều cao khung bảng: vừa phần còn lại của màn hình ----------
    function fit() {
        var box = document.querySelector('.cd-schedule');
        if (!box || !isDesktop()) return;
        var top = box.getBoundingClientRect().top + window.scrollY;
        var h = Math.max(420, Math.round(window.innerHeight - top - 16));
        document.documentElement.style.setProperty('--sch-fill', h + 'px');
    }

    // Điện thoại: mỗi xe là một hàng thẻ cuộn ngang; cuộn từng hàng tới thẻ hôm nay (hoặc lịch sắp tới gần nhất)
    function parseDmy(v) {
        var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})/.exec(v || '');
        return m ? new Date(+m[3], +m[2] - 1, +m[1]) : null;
    }
    function scrollMobileToday(smooth) {
        if (isDesktop() || !isCurrentMonth(selected())) return false;
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
        document.querySelectorAll('.cd-schedule .events .events-group > ul').forEach(function (ul) {
            var target = null;
            ul.querySelectorAll('.single-event[data-start]').forEach(function (ev) {
                var d = parseDmy(ev.getAttribute('data-start'));
                var t = d ? d.getTime() : NaN;
                ev.classList.toggle('is-today-card', t === today);
                if (!target && t >= today) target = ev;
            });
            if (!target) target = ul.querySelector('.single-event:last-of-type'); // hết lịch trong tháng: về thẻ cuối
            if (!target) return;
            var left = ul.scrollLeft + target.getBoundingClientRect().left - ul.getBoundingClientRect().left - 16;
            ul.scrollTo({ left: Math.max(0, left), behavior: smooth ? 'smooth' : 'instant' });
        });
        return true;
    }

    function scrollToToday(smooth) {
        if (!isDesktop()) return scrollMobileToday(smooth);
        var box = document.querySelector('.cd-schedule');
        var li = document.querySelector('.cd-schedule .timeline li.is-today');
        if (!box || !li) return false;
        var header = box.querySelector('.events .top-info');
        var offset = li.getBoundingClientRect().top - box.getBoundingClientRect().top + box.scrollTop;
        var top = Math.max(0, offset - (header ? header.offsetHeight : 0) - 8);
        box.scrollTo({ top: top, behavior: smooth ? 'smooth' : 'instant' });
        return true;
    }

    // ---------- Thanh nút cuối hàng lọc ----------
    function ensureTools() {
        var row = document.querySelector('.cd-intro .schedule-support .row') || document.querySelector('.cd-intro');
        if (!row) return null;
        var bar = row.querySelector('.sch-tools');
        if (bar) return bar;
        bar = document.createElement('div');
        bar.className = 'sch-tools';
        bar.innerHTML =
            '<div class="sch-tools__legend">' +
            '<span><i class="sch-badge sch-badge--ok fas fa-check"></i> ' + CHECK_LABEL + '</span>' +
            '<span><b class="sch-tools__count">2</b> Nhiều đơn / ngày</span>' +
            '<span><i class="sch-badge sch-badge--warn">!</i> Cần kiểm tra</span>' +
            '</div>' +
            '<div class="sch-tools__btns">' +
            '<button type="button" class="sch-tools__btn sch-tools__btn--summary" hidden><i class="fas fa-chart-bar"></i> Tổng hợp</button>' +
            '<button type="button" class="sch-tools__btn sch-tools__btn--today"><i class="fa-regular fa-calendar-check"></i> Hôm nay</button>' +
            '</div>';
        row.appendChild(bar);
        bar.querySelector('.sch-tools__btn--today').addEventListener('click', goToday);
        bar.querySelector('.sch-tools__btn--summary').addEventListener('click', openDrawer);
        return bar;
    }

    function goToday() {
        if (!isCurrentMonth(selected())) {
            // Đang xem tháng khác: đặt cả năm và tháng rồi chỉ phát 1 lần change (handler đọc cả 2 ô) để không gọi ajax 2 lần
            var now = new Date();
            pendingToday = true;
            var changed = [setSelect(document.querySelector('.select-year'), now.getFullYear()),
                setSelect(document.querySelector('.select-month'), now.getMonth() + 1)].filter(Boolean);
            if (changed.length) fire(changed[changed.length - 1]);
            return;
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
        scrollToToday(true);
    }

    function setSelect(el, value) {
        if (!el) return null;
        var opt = Array.prototype.find.call(el.options, function (o) { return !o.disabled && monthNum(o.value) === value; });
        if (!opt || opt.selected) return null;
        el.value = opt.value;
        return el;
    }

    function fire(el) {
        if (window.jQuery) window.jQuery(el).trigger('change'); else el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // ---------- Ngăn trượt Tổng hợp ----------
    var drawer = null;
    function getDrawer() {
        if (drawer) return drawer;
        drawer = document.createElement('div');
        drawer.className = 'sch-drawer';
        drawer.setAttribute('aria-hidden', 'true');
        drawer.innerHTML =
            '<div class="sch-drawer__backdrop" data-close></div>' +
            '<aside class="sch-drawer__panel" role="dialog" aria-label="Tổng hợp tháng">' +
            '<div class="sch-drawer__head"><div><h3>Tổng hợp tháng</h3><p class="sch-drawer__sub"></p></div>' +
            '<button type="button" class="sch-drawer__close" data-close aria-label="Đóng"><i class="fas fa-times"></i></button></div>' +
            '<div class="sch-drawer__body"></div>' +
            '</aside>';
        document.body.appendChild(drawer);
        drawer.addEventListener('click', function (e) { if (e.target.closest('[data-close]')) closeDrawer(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
        return drawer;
    }
    function openDrawer() { var d = getDrawer(); d.classList.add('is-open'); d.setAttribute('aria-hidden', 'false'); }
    function closeDrawer() { if (drawer) { drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true'); } }

    function moveSummary(bar, sel) {
        var acc = document.querySelector('.schedule .accountant, .schedule-render .accountant');
        var btn = bar && bar.querySelector('.sch-tools__btn--summary');
        if (!acc) return;
        var d = getDrawer();
        var body = d.querySelector('.sch-drawer__body');
        body.innerHTML = '';
        body.appendChild(acc);
        var total = '';
        acc.querySelectorAll('.account-content').forEach(function (card) {
            var title = card.querySelector('.account-title');
            var val = card.querySelector('.account-content-child');
            if (title) title.textContent = title.textContent.replace(/:\s*$/, '').trim();
            var label = title ? title.textContent.toLowerCase() : '';
            var num = val ? parseInt(String(val.textContent).replace(/\D/g, ''), 10) || 0 : 0;
            card.classList.toggle('is-zero', num === 0);
            card.classList.toggle('is-total', label.indexOf('tổng số hình') === 0 || label.indexOf('tổng số cas chụp') === 0);
            if (label.indexOf('tổng số cas chụp') === 0) total = num.toLocaleString('vi-VN');
        });
        d.querySelector('.sch-drawer__sub').textContent = sel ? 'Tháng ' + sel.month + '/' + sel.year : '';
        if (btn) {
            btn.hidden = false;
            btn.innerHTML = '<i class="fas fa-chart-bar"></i> Tổng hợp' + (total ? ' <b>' + total + ' cas</b>' : '');
        }
    }

    // ---------- Popup chi tiết lịch: chia các dòng thành nhóm có tiêu đề, nhãn nhỏ trên / giá trị dưới ----------
    // Chỉ sắp xếp lại DOM (giữ nguyên class) nên JS gốc vẫn điền dữ liệu và gửi form như cũ.
    var SECTION_BY_FIRST = [
        ['mã đơn hàng', 'Thông tin đơn'],
        ['bác sĩ đọc phim', 'Phim & phiếu'],
        ['ghi chú', 'Ghi chú & giao kết quả'],
        ['số cas ktv chụp', 'KTV cập nhật'],
        ['trạng thái đơn', 'Kết quả']
    ];
    var WIDE = ['tên cty', 'địa chỉ', 'danh sách', 'ghi chú', 'cảnh báo', 'file', 'thông tin người liên hệ'];

    function sectionTitle(first) {
        for (var i = 0; i < SECTION_BY_FIRST.length; i++) if (first.indexOf(SECTION_BY_FIRST[i][0]) === 0) return SECTION_BY_FIRST[i][1];
        return '';
    }

    function restructureModal(info) {
        if (!info || info.getAttribute('data-sch') === '1') return;
        info.setAttribute('data-sch', '1');
        var groups = [[]];
        Array.prototype.slice.call(info.children).forEach(function (el) {
            if (el.classList.contains('schedule-line')) { el.remove(); groups.push([]); return; }
            if (el.classList.contains('rs-overlay-change')) return; // nút Cập nhật để cuối
            groups[groups.length - 1].push(el);
        });
        var tail = info.querySelector(':scope > .rs-overlay-change');
        groups.forEach(function (els) {
            if (!els.length) return;
            var items = els.filter(function (el) { return el.classList.contains('event-item'); });
            var form = els.filter(function (el) { return el.tagName === 'FORM'; })[0];
            var first = items[0] ? (items[0].querySelector('.item-title') || {}).textContent || '' : '';
            var title = form && !items.length ? 'Cập nhật' : sectionTitle(first.trim().toLowerCase());
            var sec = document.createElement('section');
            sec.className = 'sch-sec' + (form ? ' sch-sec--form' : '');
            if (title) sec.innerHTML = '<h5 class="sch-sec__title">' + title + '</h5>';
            var grid = document.createElement('div');
            grid.className = 'sch-sec__grid';
            sec.appendChild(grid);
            els.forEach(function (el) {
                if (el.classList.contains('event-item')) {
                    var t = el.querySelector('.item-title');
                    if (t) {
                        t.textContent = t.textContent.replace(/:\s*$/, '').trim();
                        var low = t.textContent.toLowerCase();
                        if (WIDE.some(function (w) { return low.indexOf(w) === 0; })) el.classList.add('is-wide');
                        if (low.indexOf('cảnh báo') === 0) el.classList.add('is-warning');
                    }
                }
                grid.appendChild(el);
            });
            if (form) tidyForm(form);
            info.insertBefore(sec, tail);
        });
    }

    // Trong form: các ô nhập ngắn lên trước cho đều 2 cột; khối có tiêu đề (hình thức trả, file, ghi chú) xuống cuối, ghi chú sau cùng
    function tidyForm(form) {
        var end = form.querySelector(':scope > .rs-overlay-change');
        var blocks = [];
        Array.prototype.slice.call(form.querySelectorAll(':scope > legend')).forEach(function (lg) {
            var next = lg.nextElementSibling;
            var pair = [lg];
            if (next && next.tagName !== 'LEGEND' && !next.classList.contains('rs-overlay-change')) pair.push(next);
            var isNote = pair.some(function (el) { return el.querySelector && el.querySelector('textarea'); });
            blocks.push({ els: pair, note: isNote });
        });
        blocks.filter(function (b) { return !b.note; }).concat(blocks.filter(function (b) { return b.note; })).forEach(function (b) {
            var wrap = document.createElement('div');
            wrap.className = 'sch-field-block' + (b.els.some(function (el) { return el.classList.contains('block-order-send-result'); }) ? ' is-half' : '');
            b.els.forEach(function (el) { wrap.appendChild(el); });
            form.insertBefore(wrap, end);
        });
    }

    function decorateModals() {
        document.querySelectorAll('.event-modal .event-info').forEach(restructureModal);
    }

    function decorate() {
        var sel = selected();
        var bar = ensureTools();
        moveSummary(bar, sel);
        if (sel) decorateDays(sel);
        decorateCars();
        decorateModals();
        fit();
    }

    // ---------- Tìm theo tên công ty (Lịch kết quả): sau khi lịch lọc hiện ra, cuộn tới lịch tìm được ----------
    // JS gốc thay .schedule bằng lịch chỉ còn đơn của công ty đó; ở đây cuộn tới lịch sớm nhất, tô sáng,
    // và hiện thanh "Lịch 1/3 ‹ ›" để nhảy qua các lịch còn lại.
    var pendingFind = null;
    var found = [];
    var foundIdx = 0;
    var findOldBox = null;

    function eventTime(ev) {
        var d = parseDmy(ev.getAttribute('data-start'));
        return d ? d.getTime() : 0;
    }

    function collectFound() {
        found = Array.prototype.slice.call(document.querySelectorAll('.cd-schedule .events .single-event'))
            .filter(function (ev) { return ev.offsetParent !== null; })
            .sort(function (a, b) { return eventTime(a) - eventTime(b) || a.getBoundingClientRect().left - b.getBoundingClientRect().left; });
        return found.length;
    }

    function focusFound(i, smooth) {
        if (!found.length) return;
        foundIdx = (i + found.length) % found.length;
        var ev = found[foundIdx];
        var behavior = smooth ? 'smooth' : 'instant';
        document.querySelectorAll('.single-event.is-found').forEach(function (el) { el.classList.remove('is-found'); });
        ev.classList.add('is-found');
        var box = document.querySelector('.cd-schedule');
        if (isDesktop() && box) {
            var header = box.querySelector('.events .top-info');
            var br = box.getBoundingClientRect();
            var er = ev.getBoundingClientRect();
            box.scrollTo({
                top: Math.max(0, box.scrollTop + er.top - br.top - (header ? header.offsetHeight : 0) - 24),
                left: Math.max(0, box.scrollLeft + er.left - br.left - 64 - 24),
                behavior: behavior
            });
        } else {
            var ul = ev.closest('ul');
            if (ul) ul.scrollTo({ left: Math.max(0, ul.scrollLeft + ev.getBoundingClientRect().left - ul.getBoundingClientRect().left - 16), behavior: behavior });
            var group = ev.closest('.events-group') || ev;
            window.scrollTo({ top: Math.max(0, group.getBoundingClientRect().top + window.scrollY - 80), behavior: behavior });
        }
        renderFindBar();
    }

    function renderFindBar() {
        var bar = ensureTools();
        if (!bar) return;
        var el = bar.querySelector('.sch-find');
        if (!found.length || pendingFind === null && !el) { if (el) el.remove(); return; }
        if (!el) {
            el = document.createElement('div');
            el.className = 'sch-find';
            el.innerHTML = '<span class="sch-find__text"></span>' +
                '<button type="button" class="sch-find__nav" data-dir="-1" aria-label="Lịch trước"><i class="fas fa-chevron-left"></i></button>' +
                '<button type="button" class="sch-find__nav" data-dir="1" aria-label="Lịch sau"><i class="fas fa-chevron-right"></i></button>';
            el.addEventListener('click', function (e) {
                var b = e.target.closest('.sch-find__nav');
                if (b) focusFound(foundIdx + parseInt(b.getAttribute('data-dir'), 10), true);
            });
            bar.insertBefore(el, bar.firstChild);
        }
        var ev = found[foundIdx];
        el.querySelector('.sch-find__text').textContent = 'Lịch ' + (foundIdx + 1) + '/' + found.length + (ev && ev.getAttribute('data-start') ? ' · ' + ev.getAttribute('data-start') : '');
        el.querySelectorAll('.sch-find__nav').forEach(function (b) { b.disabled = found.length < 2; });
    }

    function clearFind() {
        pendingFind = null;
        found = [];
        var el = document.querySelector('.sch-find');
        if (el) el.remove();
    }

    function runFind() {
        if (pendingFind === null) return;
        // JS gốc còn xếp vị trí thẻ sau khi chèn HTML: thử vài lần cho tới khi có thẻ
        var tries = [120, 450, 1000, 1800];
        var go = function () {
            if (pendingFind === null) return;
            // Chưa có lịch lọc (ajax chưa trả về, khung lịch vẫn là cái cũ): đợi lần thay DOM sau
            if (document.querySelector('.cd-schedule') === findOldBox) return;
            if (collectFound()) { focusFound(0, false); pendingFind = ''; return; }
            if (tries.length) setTimeout(go, tries.shift()); else renderFindBar();
        };
        setTimeout(go, tries.shift());
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.li_search')) { found = []; findOldBox = document.querySelector('.cd-schedule'); pendingFind = e.target.closest('.li_search').textContent.trim(); }
        else if (e.target.closest('.btn-close-search')) clearFind();
    }, true);

    var pendingToday = false;
    var timer = null;
    function refresh() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            observer.disconnect();
            decorate();
            if (pendingToday && scrollToToday(false)) pendingToday = false;
            if (pendingFind) runFind();
            else if (found.length && !document.contains(found[0])) clearFind(); // đã về lịch đầy đủ / đổi tháng
            observe();
        }, 60);
    }

    var observer = new MutationObserver(refresh);
    function observe() {
        var root = document.querySelector('.schedule-render') || document.querySelector('.schedule') || document.querySelector('main') || document.body;
        observer.observe(root, { childList: true, subtree: true });
    }

    function init() {
        decorate();
        // Mở trang ở tháng hiện tại thì cuộn sẵn tới hôm nay (thử lại vài lần vì JS lịch gốc còn đặt lại chiều cao bảng sau khi tải)
        if (isCurrentMonth(selected())) [300, 900, 1800].forEach(function (ms) {
            setTimeout(function () { var box = document.querySelector('.cd-schedule'); if (box && box.scrollTop < 5) scrollToToday(false); }, ms);
        });
        else pendingToday = true; // trang Kết quả tải lịch bằng ajax sau
        observe();
        window.addEventListener('resize', fit);
        window.addEventListener('load', fit);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
