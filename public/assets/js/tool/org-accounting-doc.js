/*
 * Form "Thêm / sửa tài liệu kế toán": nút "Đọc bằng AI" gửi file lên accounting.documents.extract,
 * AI trả về loại, tên, các bên, kỳ, ngày, giá trị, tóm tắt → điền vào form (chưa lưu). Bản rút gọn: accounting-doc.js
 */
(function () {
    'use strict';

    // Popup hướng dẫn (dialog #ad-help)
    var help = document.getElementById('ad-help');
    if (help && help.showModal) {
        document.querySelectorAll('[data-ad-help]').forEach(function (b) {
            b.addEventListener('click', function () { help.showModal(); });
        });
        help.querySelectorAll('[data-ad-help-close]').forEach(function (b) {
            b.addEventListener('click', function () { help.close(); });
        });
        // Bấm ra ngoài khung thì đóng
        help.addEventListener('click', function (e) {
            if (e.target === help) help.close();
        });
    }

    var fileInput = document.getElementById('ad-file');
    var btn = document.getElementById('ad-ai-btn');
    var status = document.getElementById('ad-ai-status');
    if (!fileInput || !btn) return;
    var form = fileInput.form;
    var btnLabel = btn.innerHTML;
    var readable = ['pdf', 'xlsx', 'xls', 'docx', 'jpg', 'jpeg', 'png'];

    function setStatus(html, cls) {
        status.className = 'ad-ai__hint' + (cls ? ' ' + cls : '');
        status.innerHTML = html;
    }

    function ext(name) {
        var i = name.lastIndexOf('.');
        return i < 0 ? '' : name.slice(i + 1).toLowerCase();
    }

    function money(n) {
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function field(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function fill(name, value) {
        var el = field(name);
        if (!el || value === null || value === undefined || value === '') return false;
        el.value = value;
        el.classList.add('ad-filled');
        setTimeout(function () { el.classList.remove('ad-filled'); }, 2500);
        return true;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    fileInput.addEventListener('change', function () {
        var f = fileInput.files[0];
        if (!f) {
            btn.disabled = true;
            return;
        }
        if (readable.indexOf(ext(f.name)) < 0) {
            btn.disabled = true;
            setStatus('File .' + escapeHtml(ext(f.name)) + ' AI không đọc được (chỉ PDF, ảnh JPG / PNG, Excel, Word .docx). Vẫn lưu file được, nhưng hãy nhập tay phần thông tin.', 'is-warn');
            return;
        }
        var mb = f.size / 1048576;
        var isImg = ['jpg', 'jpeg', 'png'].indexOf(ext(f.name)) >= 0;
        var limit = isImg ? 5 : (ext(f.name) === 'pdf' ? 20 : 30);
        if (mb > limit) {
            btn.disabled = true;
            setStatus('File ' + mb.toFixed(1) + ' MB, AI chỉ đọc ' + (isImg ? 'ảnh' : 'file .' + escapeHtml(ext(f.name))) + ' tối đa ' + limit + ' MB. Xem <b>Hướng dẫn</b> để tách nhỏ file; vẫn lưu file được (≤ 30 MB) nếu nhập tay.', 'is-warn');
            return;
        }
        btn.disabled = false;
        setStatus('Đã chọn <b>' + escapeHtml(f.name) + '</b>. Bấm <b>Đọc bằng AI</b> để tự điền thông tin.');
    });

    btn.addEventListener('click', function () {
        var f = fileInput.files[0];
        if (!f) return;
        var hasSummary = (field('summary').value || '').trim() !== '';
        if (hasSummary && !window.confirm('AI sẽ ghi đè các ô đang có (tên, tóm tắt…). Tiếp tục?')) return;

        var data = new FormData();
        data.append('file', f);
        btn.disabled = true;
        fileInput.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Đang đọc…</span>';
        setStatus('AI đang đọc <b>' + escapeHtml(f.name) + '</b>, thường mất 20 giây – 2 phút với file dài. Đừng đóng trang.', 'is-busy');

        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: data
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                if (!res.ok) {
                    var msg = body.message || (res.status === 429 ? 'Bấm quá nhiều lần, đợi một phút rồi thử lại.' : 'AI chưa đọc được file, thử lại.');
                    if (body.errors && body.errors.file) msg = body.errors.file[0];
                    throw new Error(msg);
                }
                return body.data;
            });
        }).then(function (d) {
            fill('type', d.type);
            fill('title', d.title);
            fill('parties', d.parties);
            fill('period', d.period);
            fill('signed_on', d.signed_on);
            fill('valid_from', d.valid_from);
            fill('valid_to', d.valid_to);
            fill('value', d.value ? money(d.value) : null);
            fill('summary', d.summary);
            if (d.readable === false) {
                setStatus('<i class="fas fa-exclamation-triangle"></i> File mờ / khó đọc: AI chỉ điền được một phần, xem ghi chú trong tóm tắt và bổ sung tay trước khi lưu.', 'is-warn');
            } else {
                setStatus('<i class="fas fa-check-circle"></i> AI đã điền xong (ô viền cam). Kiểm tra lại số liệu, sửa nếu cần rồi bấm <b>Lưu</b>.', 'is-ok');
            }
        }).catch(function (e) {
            setStatus('<i class="fas fa-exclamation-circle"></i> ' + escapeHtml(e.message || 'Lỗi kết nối, thử lại.'), 'is-err');
        }).then(function () {
            btn.disabled = false;
            fileInput.disabled = false;
            btn.innerHTML = btnLabel;
        });
    });
})();
