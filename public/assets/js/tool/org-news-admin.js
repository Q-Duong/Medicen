/* Soạn tin tức (Website → Tin tức): CKEditor 5 (super-build có sẵn ở backend/js/ckeditor), ảnh trong bài tải lên news_admin.upload,
 * xem trước ảnh đại diện, gợi ý đường dẫn theo tiêu đề, đếm ký tự. Nguồn: org-news-admin.js → esbuild --minify → news-admin.js */
(function () {
    const cfg = window.NEWS_EDITOR || {};
    let dirty = false;
    const form = document.getElementById('nw-form');
    if (!form) return;

    // ---- Trình soạn thảo ----
    const area = document.getElementById('nw-editor');

    // Bộ tải ảnh trong bài lên server (bản CKEditor có sẵn không kèm SimpleUploadAdapter; Base64 bị tắt để bài không nặng)
    function UploadAdapter(loader) { this.loader = loader; this.ctrl = new AbortController(); }
    UploadAdapter.prototype.upload = function () {
        const self = this;
        return this.loader.file.then(function (file) {
            const fd = new FormData();
            fd.append('upload', file);
            return fetch(cfg.uploadUrl, { method: 'POST', body: fd, signal: self.ctrl.signal, credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': cfg.csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (j) {
                        if (!r.ok || !j.url) {
                            const msg = (j.error && j.error.message) || (r.status === 419 ? 'Phiên làm việc hết hạn, tải lại trang rồi thử lại.' : 'Không tải được ảnh (lỗi ' + r.status + ').');
                            alert(msg);
                            throw new Error(msg);
                        }
                        return { default: j.url };
                    });
                });
        });
    };
    UploadAdapter.prototype.abort = function () { this.ctrl.abort(); };
    function uploadPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = function (loader) { return new UploadAdapter(loader); };
    }

    if (area && window.CKEDITOR && window.CKEDITOR.ClassicEditor) {
        window.CKEDITOR.ClassicEditor.create(area, {
            language: 'vi',
            placeholder: 'Viết nội dung bài ở đây. Kéo thả hoặc dán ảnh trực tiếp vào bài.',
            toolbar: {
                items: ['heading', '|', 'bold', 'italic', 'underline', 'link', '|', 'bulletedList', 'numberedList', 'alignment', '|',
                    'uploadImage', 'insertTable', 'blockQuote', 'horizontalLine', '|', 'fontColor', 'highlight', 'removeFormat', '|', 'undo', 'redo', 'sourceEditing'],
                shouldNotGroupWhenFull: false,
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Đoạn văn', class: 'ck-heading_paragraph' },
                    { model: 'heading2', view: 'h2', title: 'Tiêu đề mục', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Tiêu đề nhỏ', class: 'ck-heading_heading3' },
                ],
            },
            image: {
                toolbar: ['imageTextAlternative', 'toggleImageCaption', '|', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 'resizeImage'],
            },
            table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
            mediaEmbed: {
                previewsInData: true,
                // Chỉ video tải lên web (nút "Chèn video"); không nhúng YouTube / Facebook
                removeProviders: ['dailymotion', 'spotify', 'youtube', 'vimeo', 'instagram', 'twitter', 'googleMaps', 'flickr', 'facebook'],
                extraProviders: [{
                    name: 'medicenVideo',
                    url: /\/news\/video\/(\d{6}-[a-z0-9]{16}\.(?:mp4|webm|mov|m4v))$/,
                    html: function (m) {
                        return '<video controls preload="metadata" playsinline src="/news/video/' + m[1] + '" style="display:block;width:100%;max-height:520px;background:#000;border-radius:8px"></video>';
                    },
                }],
            },
            link: { addTargetToExternalLinks: true, defaultProtocol: 'https://' },
            extraPlugins: [uploadPlugin],
            removePlugins: ['AIAssistant', 'CKBox', 'CKFinder', 'EasyImage', 'RealTimeCollaborativeComments', 'RealTimeCollaborativeTrackChanges',
                'RealTimeCollaborativeRevisionHistory', 'PresenceList', 'Comments', 'TrackChanges', 'TrackChangesData', 'RevisionHistory', 'Pagination',
                'WProofreader', 'MathType', 'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter', 'TableOfContents', 'PasteFromOfficeEnhanced',
                'Base64UploadAdapter', 'ExportPdf', 'ExportWord', 'ImportWord', 'MultiLevelList', 'CaseChange', 'Mention', 'MergeFields'],
        }).then(function (editor) {
            window.newsEditor = editor;
            form.addEventListener('submit', function (e) {
                const plain = editor.getData().replace(/<(?!img|iframe)[^>]*>/gi, '').replace(/&nbsp;/g, ' ').trim();
                if (!plain) {
                    e.preventDefault();
                    alert('Nhập nội dung bài viết.');
                    editor.editing.view.focus();
                }
            });
        }).catch(function (err) {
            // Không tải được trình soạn thảo: vẫn dùng ô nhập thường (HTML)
            area.hidden = false;
            area.rows = 18;
            console.error(err);
        });
    }

    // ---- Chèn video: tải file lên theo từng phần (NewsVideos::CHUNK), xong thì chèn vào chỗ con trỏ ----
    const vBtn = form.querySelector('[data-nw-video]');
    const vFile = form.querySelector('[data-nw-video-file]');
    const vProg = form.querySelector('[data-nw-video-progress]');
    let uploading = false;
    const setProgress = function (pct, text) {
        vProg.hidden = false;
        vProg.querySelector('i').style.width = pct + '%';
        vProg.querySelector('span').textContent = text;
    };
    const sendChunk = function (fd, tries) {
        return fetch(cfg.videoUrl, { method: 'POST', body: fd, credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': cfg.csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (j) {
                    if (r.ok) return j;
                    const err = new Error((j.error && j.error.message) || (j.message) || (r.status === 419 ? 'Phiên làm việc hết hạn, tải lại trang rồi thử lại.' : 'Lỗi ' + r.status));
                    err.fatal = r.status === 422 || r.status === 419 || r.status === 403;
                    throw err;
                });
            })
            .catch(function (e) {
                if (!e.fatal && tries > 1) return new Promise(function (ok) { setTimeout(ok, 1500); }).then(function () { return sendChunk(fd, tries - 1); });
                throw e;
            });
    };
    if (vBtn && vFile && vProg) {
        vBtn.addEventListener('click', function () { if (!uploading) vFile.click(); });
        vFile.addEventListener('change', function () {
            const f = vFile.files && vFile.files[0];
            vFile.value = '';
            if (!f) return;
            if (!/\.(mp4|webm|mov|m4v)$/i.test(f.name)) { alert('Chỉ nhận video MP4, WEBM, MOV.'); return; }
            if (f.size > cfg.videoMaxMb * 1048576) { alert('Video tối đa ' + cfg.videoMaxMb + ' MB. Hãy nén hoặc cắt ngắn video rồi thử lại.'); return; }
            if (!window.newsEditor) { alert('Trình soạn thảo chưa sẵn sàng.'); return; }
            const total = Math.max(1, Math.ceil(f.size / cfg.videoChunk));
            const id = (Date.now().toString(36) + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2)).replace(/[^a-z0-9]/g, '').slice(0, 32);
            const mb = (f.size / 1048576).toFixed(1);
            uploading = true;
            vBtn.disabled = true;
            form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
            let i = 0;
            const next = function () {
                setProgress(Math.round(i / total * 100), 'Đang tải video ' + f.name + ' (' + mb + ' MB): ' + Math.round(i / total * 100) + '%');
                const fd = new FormData();
                fd.append('upload_id', id);
                fd.append('index', i);
                fd.append('total', total);
                fd.append('chunk', f.slice(i * cfg.videoChunk, (i + 1) * cfg.videoChunk), 'part');
                return sendChunk(fd, 3).then(function (j) {
                    i++;
                    if (i < total) return next();
                    if (!j.done || !j.url) throw new Error('Máy chủ chưa ghép xong video, hãy thử lại.');
                    return j.url;
                });
            };
            next().then(function (url) {
                const ed = window.newsEditor;
                ed.model.change(function (w) {
                    const el = ed.model.document.selection.getSelectedElement();
                    if (el) w.setSelection(w.createPositionAfter(el));
                });
                ed.execute('mediaEmbed', url);
                setProgress(100, 'Đã chèn video ' + f.name + '. Nhớ bấm Lưu.');
                dirty = true;
            }).catch(function (e) {
                setProgress(0, 'Không tải được video: ' + e.message);
                alert('Không tải được video: ' + e.message);
            }).finally(function () {
                uploading = false;
                vBtn.disabled = false;
                form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = false; });
            });
        });
    }

    // ---- Gợi ý đường dẫn theo tiêu đề ----
    const slugify = function (s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 200);
    };
    const title = form.querySelector('[data-nw-title]');
    const slug = form.querySelector('[data-nw-slug]');
    if (title && slug) {
        const sync = function () { slug.placeholder = slugify(title.value) || 'tu-dong-theo-tieu-de'; };
        title.addEventListener('input', sync);
        sync();
        slug.addEventListener('blur', function () { if (slug.value) slug.value = slugify(slug.value); });
    }

    // ---- Đếm ký tự ----
    form.querySelectorAll('[data-nw-count]').forEach(function (el) {
        const out = el.parentElement.querySelector('.nw-count');
        const ideal = parseInt(el.dataset.nwCount, 10) || 0;
        if (!out) return;
        const upd = function () {
            const n = el.value.length;
            out.textContent = n + ' ký tự' + (ideal ? (n > ideal ? ' · Google thường cắt sau khoảng ' + ideal : ' · nên ≤ ' + ideal) : '');
            out.classList.toggle('is-over', ideal > 0 && n > ideal);
        };
        el.addEventListener('input', upd);
        upd();
    });

    // ---- Xem trước ảnh đại diện ----
    const file = form.querySelector('[data-nw-file]');
    const preview = form.querySelector('[data-nw-preview]');
    const empty = form.querySelector('.nw-image__empty');
    const remove = form.querySelector('[data-nw-remove]');
    if (file && preview) {
        file.addEventListener('change', function () {
            const f = file.files && file.files[0];
            if (!f) return;
            if (f.size > 8 * 1024 * 1024) {
                alert('Ảnh tối đa 8 MB.');
                file.value = '';
                return;
            }
            preview.src = URL.createObjectURL(f);
            preview.hidden = false;
            if (empty) empty.hidden = true;
            if (remove) remove.checked = false;
        });
    }
    if (remove && preview) {
        remove.addEventListener('change', function () { preview.style.opacity = remove.checked ? .3 : 1; });
    }

    // ---- Nhắc khi rời trang chưa lưu ----
    form.addEventListener('input', function () { dirty = true; });
    setTimeout(function () { if (window.newsEditor) window.newsEditor.model.document.on('change:data', function () { dirty = true; }); }, 1500);
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty || uploading) { e.preventDefault(); e.returnValue = ''; } });
})();
