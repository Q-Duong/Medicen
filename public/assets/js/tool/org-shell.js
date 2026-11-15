// Khung trang quản trị: chuông "Việc cần làm" trên header.
// Lấy số từ notifications.todo (App\Services\Notifications\TodoList): lúc mở trang, mỗi phút khi tab đang mở,
// và khi bấm chuông / nút cập nhật. Số đếm trực tiếp từ dữ liệu: làm xong việc thì tự giảm, không có "đã đọc".
// File nguồn (dễ đọc). Bản nạp lên trang là shell.js (rút gọn từ file này).
(function () {
    const bell = document.getElementById('app-bell');
    if (!bell || !bell.dataset.url) return;
    const badge = bell.querySelector('.app-bell__badge');
    const body = bell.querySelector('.app-bell__body');
    const foot = bell.querySelector('.app-bell__foot');
    const refreshBtn = bell.querySelector('.app-bell__refresh');
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const num = (n) => new Intl.NumberFormat('vi-VN').format(n || 0);
    let busy = false;

    function render(data) {
        const total = data.total || 0;
        badge.hidden = total === 0;
        badge.textContent = total > 99 ? '99+' : String(total);
        badge.classList.toggle('is-urgent', (data.urgent || 0) > 0);
        bell.classList.toggle('has-todo', total > 0);
        bell.querySelector('.app-bell__btn').title = total ? 'Việc cần làm: ' + num(total) : 'Không có việc cần làm';

        if (!data.groups || !data.groups.length) {
            body.innerHTML = '<div class="app-bell__empty"><i class="far fa-check-circle"></i> Không có việc nào cần làm. Tốt lắm!</div>';
        } else {
            body.innerHTML = data.groups.map((g) =>
                '<div class="app-bell__group"><div class="app-bell__glabel"><i class="' + esc(g.icon) + '"></i> ' + esc(g.label) + '</div>' +
                g.items.map((i) =>
                    '<a class="app-bell__item' + (i.level === 'urgent' ? ' is-urgent' : '') + '" href="' + esc(i.url) + '">' +
                    '<span class="app-bell__count">' + (i.count > 999 ? '999+' : num(i.count)) + '</span>' +
                    '<span class="app-bell__text"><b>' + esc(i.title) + '</b>' + (i.hint ? '<small>' + esc(i.hint) + '</small>' : '') + '</span>' +
                    '<i class="fas fa-chevron-right app-bell__go"></i></a>'
                ).join('') + '</div>'
            ).join('');
        }
        foot.textContent = data.checked_at ? 'Cập nhật lúc ' + data.checked_at + ' · tự cập nhật mỗi phút' : '';
    }

    async function load(fresh) {
        if (busy) return;
        busy = true;
        bell.classList.add('is-loading');
        try {
            const res = await fetch(bell.dataset.url + (fresh ? '?fresh=1' : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (res.ok) render(await res.json());
        } catch (e) {
            // mất mạng: giữ số cũ
        } finally {
            busy = false;
            bell.classList.remove('is-loading');
        }
    }

    refreshBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        load(true);
    });
    // Bấm vào menu (trừ link) không đóng menu
    bell.querySelector('.app-bell__menu').addEventListener('click', (e) => {
        if (!e.target.closest('a')) e.stopPropagation();
    });
    bell.querySelector('.app-bell__btn').addEventListener('click', () => load(false));

    setTimeout(() => load(false), 600);
    setInterval(() => {
        if (document.visibilityState === 'visible') load(false);
    }, 60000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') load(false);
    });
})();
