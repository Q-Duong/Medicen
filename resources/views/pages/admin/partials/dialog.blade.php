{{--
    Hộp thoại xác nhận / thông báo thay cho confirm() / alert() của trình duyệt.
    JS: await uiConfirm('Nội dung', { title, ok, cancel, danger })  -> true / false
        await uiAlert('Nội dung', { title, type: 'error' | 'info' })
    Form: <form data-confirm="Nội dung" data-confirm-title="Xóa lịch nghỉ?" data-confirm-ok="Xóa" data-confirm-danger> tự hỏi trước khi gửi.
--}}
<style>
    .uidlg { position: fixed; inset: 0; z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px;
        background: rgba(31, 30, 29, .38); opacity: 0; transition: opacity .16s ease; }
    .uidlg.is-open { opacity: 1; }
    .uidlg__box { width: 100%; max-width: 420px; background: #fff; border-radius: 18px; box-shadow: 0 18px 50px rgba(0, 0, 0, .22);
        padding: 22px 22px 18px; transform: translateY(8px) scale(.98); transition: transform .16s ease; font-family: inherit; }
    .uidlg.is-open .uidlg__box { transform: none; }
    .uidlg__head { display: flex; align-items: flex-start; gap: 12px; }
    .uidlg__icon { flex: 0 0 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px;
        background: #f3efe6; color: #6b5a3a; }
    .uidlg--danger .uidlg__icon, .uidlg--error .uidlg__icon { background: #fcebeb; color: #a32d2d; }
    .uidlg--info .uidlg__icon { background: #e9f1fb; color: #2b5d9b; }
    .uidlg__title { margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #1f1e1d; }
    .uidlg__msg { margin: 0; font-size: 14px; line-height: 1.55; color: #4a4843; white-space: pre-line; overflow-wrap: anywhere; }
    .uidlg__actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
    .uidlg__btn { height: 38px; padding: 0 16px; border-radius: 11px; border: 1px solid #e7e4dc; background: #fff; color: #1f1e1d;
        font-size: 14px; font-weight: 500; cursor: pointer; transition: background .12s, box-shadow .12s; }
    .uidlg__btn:hover { background: #f6f4ee; }
    .uidlg__btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(217, 119, 87, .3); }
    .uidlg__btn--ok { border-color: #1f1e1d; background: #1f1e1d; color: #fff; }
    .uidlg__btn--ok:hover { background: #3a3835; }
    .uidlg--danger .uidlg__btn--ok { border-color: #b3261e; background: #b3261e; }
    .uidlg--danger .uidlg__btn--ok:hover { background: #8f1f18; }
    @media (max-width: 480px) {
        .uidlg { align-items: flex-end; padding: 12px; }
        .uidlg__box { border-radius: 18px; padding: 20px 18px 16px; }
        .uidlg__actions .uidlg__btn { flex: 1; }
    }
</style>
<script>
    (function () {
        if (window.uiConfirm) return;
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function open({ title, message, ok, cancel, variant, icon }) {
            return new Promise(resolve => {
                const prev = document.activeElement;
                const el = document.createElement('div');
                el.className = 'uidlg uidlg--' + variant;
                el.setAttribute('role', cancel ? 'alertdialog' : 'dialog');
                el.setAttribute('aria-modal', 'true');
                el.innerHTML = '<div class="uidlg__box"><div class="uidlg__head"><div class="uidlg__icon"><i class="' + icon + '"></i></div><div>'
                    + (title ? '<p class="uidlg__title">' + esc(title) + '</p>' : '')
                    + '<p class="uidlg__msg">' + esc(message) + '</p></div></div><div class="uidlg__actions">'
                    + (cancel ? '<button type="button" class="uidlg__btn" data-r="0">' + esc(cancel) + '</button>' : '')
                    + '<button type="button" class="uidlg__btn uidlg__btn--ok" data-r="1">' + esc(ok) + '</button></div></div>';
                document.body.appendChild(el);
                requestAnimationFrame(() => el.classList.add('is-open'));
                const okBtn = el.querySelector('[data-r="1"]');
                okBtn.focus();
                const close = result => {
                    document.removeEventListener('keydown', onKey, true);
                    el.classList.remove('is-open');
                    setTimeout(() => el.remove(), 160);
                    prev && prev.focus && prev.focus({ preventScroll: true });
                    resolve(result);
                };
                const onKey = e => {
                    if (e.key === 'Escape') { e.preventDefault(); close(false); }
                    if (e.key === 'Tab') {
                        // Giữ focus trong hộp thoại
                        const btns = [...el.querySelectorAll('button')];
                        const i = btns.indexOf(document.activeElement);
                        e.preventDefault();
                        btns[(i + (e.shiftKey ? -1 : 1) + btns.length) % btns.length].focus();
                    }
                };
                document.addEventListener('keydown', onKey, true);
                el.addEventListener('click', e => {
                    const b = e.target.closest('[data-r]');
                    if (b) close(b.dataset.r === '1');
                    else if (e.target === el) close(false);
                });
            });
        }

        window.uiConfirm = (message, o = {}) => open({
            title: o.title || 'Xác nhận', message, ok: o.ok || 'Đồng ý', cancel: o.cancel || 'Hủy',
            variant: o.danger ? 'danger' : 'confirm', icon: o.danger ? 'far fa-trash-alt' : 'far fa-question-circle',
        });
        window.uiAlert = (message, o = {}) => open({
            title: o.title || (o.type === 'info' ? 'Thông báo' : 'Có lỗi'), message, ok: o.ok || 'Đã hiểu', cancel: null,
            variant: o.type === 'info' ? 'info' : 'error', icon: o.type === 'info' ? 'fas fa-info-circle' : 'fas fa-exclamation-circle',
        });

        // <form data-confirm="..."> : hỏi trước khi gửi
        document.addEventListener('submit', async e => {
            const form = e.target;
            if (!form.matches || !form.matches('form[data-confirm]') || form.dataset.confirmed) return;
            e.preventDefault();
            const submitter = e.submitter;
            const yes = await uiConfirm(form.dataset.confirm, { title: form.dataset.confirmTitle, ok: form.dataset.confirmOk, danger: form.hasAttribute('data-confirm-danger') });
            if (!yes) return;
            form.dataset.confirmed = '1';
            // Gửi lại đúng nút đã bấm (giữ name / value của nút, vd complete=1)
            if (form.requestSubmit) form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
            else form.submit();
        }, true);
    })();
</script>
