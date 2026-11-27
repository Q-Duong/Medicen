/* Nhật ký Sales: dán / kéo thả ảnh chụp màn hình chat vào khung, xem trước, báo "AI đang đọc" khi lưu.
 * Nguồn: org-sales-log.js → esbuild --minify --target=es2019 → sales-log.js */
(function () {
    const form = document.getElementById('sl-add');
    if (form) {
        const input = form.querySelector('[data-sl-file]');
        const drop = form.querySelector('[data-sl-drop]');
        const text = form.querySelector('[data-sl-text]');
        const thumbs = form.querySelector('[data-sl-thumbs]');
        const MAX = 6;
        let files = [];

        const sync = function () {
            const dt = new DataTransfer();
            files.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
            thumbs.innerHTML = '';
            files.forEach(function (f, i) {
                const box = document.createElement('div');
                box.className = 'sl-thumb';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                img.alt = f.name;
                const del = document.createElement('button');
                del.type = 'button';
                del.title = 'Bỏ ảnh';
                del.innerHTML = '&times;';
                del.addEventListener('click', function () { files.splice(i, 1); sync(); });
                box.appendChild(img);
                box.appendChild(del);
                thumbs.appendChild(box);
            });
            drop.classList.toggle('has-files', files.length > 0);
        };
        const add = function (list) {
            Array.from(list || []).forEach(function (f) {
                if (!/^image\/(png|jpe?g|webp)$/.test(f.type)) return;
                if (f.size > 8 * 1024 * 1024) { alert('Ảnh ' + (f.name || '') + ' lớn hơn 8 MB.'); return; }
                if (files.length >= MAX) { alert('Tối đa ' + MAX + ' ảnh mỗi lần.'); return; }
                files.push(new File([f], f.name && f.name !== 'image.png' ? f.name : 'chup-man-hinh-' + Date.now() + '.png', { type: f.type }));
            });
            sync();
        };
        input.addEventListener('change', function () { const l = Array.from(input.files); files = []; add(l); });
        text.addEventListener('paste', function (e) {
            const items = Array.from((e.clipboardData && e.clipboardData.files) || []);
            if (items.length) { e.preventDefault(); add(items); }
        });
        ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
        drop.addEventListener('drop', function (e) { e.preventDefault(); add(e.dataTransfer.files); });

        form.addEventListener('submit', function (e) {
            if (!text.value.trim() && !files.length) {
                e.preventDefault();
                alert('Dán đoạn chat, ghi chú cuộc gọi hoặc ảnh chụp màn hình.');
                return;
            }
            const btn = form.querySelector('[data-sl-submit]');
            btn.disabled = true;
            btn.querySelector('span').textContent = 'AI đang đọc… (5–15 giây)';
            btn.querySelector('i').className = 'fas fa-spinner fa-spin';
        });
    }

    // Nút gọi AI khác (tóm tắt ngày): khóa nút, báo đang chạy
    document.querySelectorAll('form[data-sl-busy]').forEach(function (f) {
        f.addEventListener('submit', function () {
            const b = f.querySelector('button[type=submit]');
            if (b) { b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + f.dataset.slBusy; }
        });
    });

    // Đóng menu sửa khi bấm ra ngoài
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.sl details[open]').forEach(function (d) {
            if (!d.contains(e.target) && (d.classList.contains('sl-menu') || d.classList.contains('sl-todo__edit'))) d.open = false;
        });
    });
})();
