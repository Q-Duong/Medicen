// Hàm cập nhật tên file khi chọn
function updateName(id, displayId) {
    const f = document.getElementById(id).files[0];
    if(f) {
        document.getElementById(displayId).innerText = f.name;
        document.getElementById('status').innerText = ""; // Xóa thông báo cũ
        document.getElementById('btnProcess').disabled = false; // Bật nút xử lý
        document.getElementById('btnReset').style.display = 'none';
    }
}

// Hàm load logo
function loadLogoImage(input) {
    return new Promise(resolve => {
        if (!input.files || !input.files[0]) return resolve(null);
        const r = new FileReader();
        r.onload = e => { const i = new Image(); i.onload = () => resolve(i); i.src = e.target.result; };
        r.readAsDataURL(input.files[0]);
    });
}

// Hàm Reset trạng thái về ban đầu
function resetAll() {
    document.getElementById('excelFile').value = "";
    document.getElementById('logoFile').value = "";
    document.getElementById('excelName').innerText = "Chưa chọn file...";
    document.getElementById('logoName').innerText = "Không có logo";
    document.getElementById('status').innerText = "";
    document.getElementById('btnProcess').disabled = false;
    document.getElementById('btnProcess').innerText = "TẢI XUỐNG ZIP";
    document.getElementById('btnReset').style.display = 'none';
}

// XỬ LÝ CHÍNH
async function processExcel() {
    const fileInput = document.getElementById('excelFile');
    const logoInput = document.getElementById('logoFile');
    const status = document.getElementById('status');
    const btn = document.getElementById('btnProcess');

    if (!fileInput.files.length) { alert("Chưa chọn file Excel!"); return; }
    
    // Khóa nút
    btn.disabled = true; 
    status.innerText = "⏳ Đang đọc dữ liệu...";

    const logoImg = await loadLogoImage(logoInput);
    const reader = new FileReader();

    reader.onload = async function(e) {
        const wb = XLSX.read(e.target.result, { type: 'array' });
        const json = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]]);
        
        // Tìm cột
        let cName=null, cLink=null;
        if(json.length) Object.keys(json[0]).forEach(k => {
            if(k.toLowerCase().includes('tên') || k.toLowerCase().includes('name')) cName=k;
            if(k.toLowerCase().includes('link') || k.toLowerCase().includes('share')) cLink=k;
        });

        if(!cName || !cLink) { 
            alert("Không tìm thấy cột Tên hoặc Link!"); 
            btn.disabled=false; 
            status.innerText = "❌ Lỗi file Excel";
            return; 
        }

        const zip = new JSZip();
        const div = document.getElementById('hidden-area');
        let count = 0;

        for (let i = 0; i < json.length; i++) {
            const row = json[i];
            const name = String(row[cName]||'').trim();
            const link = String(row[cLink] || '').trim();

            if (link && link !== 'nan') {
                status.innerText = `⚙️ Đang xử lý: ${i+1}/${json.length}`;
                const blob = await createQrV6(div, link, name, logoImg);
                const safeName = name.replace(/[^a-zA-Z0-9vnVN\s_-]/g, '').replace(/\s+/g, '_');
                zip.file(`${safeName}.png`, blob);
                count++;
            }
        }

        status.innerText = "📦 Đang nén ZIP...";
        zip.generateAsync({ type: "blob" }).then(content => {
            // Tự động thêm thời gian vào tên file zip để không bị trùng
            const timeStr = new Date().getTime(); 
            saveAs(content, `QR_Medicen_${timeStr}.zip`);
            
            status.innerText = `✅ Hoàn tất! Đã tải ${count} ảnh.`;
            
            // MỞ KHÓA ĐỂ LÀM TIẾP
            btn.innerText = "ĐÃ TẢI XONG";
            // Hiện nút làm mới
            document.getElementById('btnReset').style.display = 'block';
        });
    };
    reader.readAsArrayBuffer(fileInput.files[0]);
}

function createQrV6(container, text, label, logoImg) {
    return new Promise(resolve => {
        const d = document.createElement('div');
        new QRCode(d, { text: text, width: 250, height: 250, correctLevel: QRCode.CorrectLevel.H });
        container.appendChild(d);

        setTimeout(() => {
            const src = d.querySelector('canvas') || d.querySelector('img');
            if(!src) { resolve(null); return; }

            const canvasW = 320; 
            const canvasH = 380;
            const cvs = document.createElement('canvas');
            cvs.width = canvasW; cvs.height = canvasH;
            const ctx = cvs.getContext('2d');

            const draw = () => {
                ctx.fillStyle = "#FFFFFF"; ctx.fillRect(0, 0, canvasW, canvasH);

                const qrX = 35; const qrY = 30;
                ctx.drawImage(src, qrX, qrY);

                // Vẽ viền rộng
                ctx.lineWidth = 5; 
                ctx.strokeStyle = "#3498db"; 
                roundRect(ctx, 15, 10, 290, 290, 25); 
                ctx.stroke();

                if(logoImg) {
                    const s = 50;
                    const lx = qrX + 125 - 25; 
                    const ly = qrY + 125 - 25;
                    ctx.fillStyle="#FFFFFF"; ctx.fillRect(lx-3,ly-3,s+6,s+6);
                    ctx.drawImage(logoImg, lx, ly, s, s);
                }

                ctx.fillStyle = "#000"; ctx.font = "bold 14px Arial"; ctx.textAlign = "center";
                let n = label.length > 30 ? label.substring(0,27)+"..." : label;
                ctx.fillText(n, 160, 360);

                cvs.toBlob(b => { container.innerHTML=''; resolve(b); });
            };

            if(src.tagName==='IMG' && !src.complete) src.onload = draw; else draw();
        }, 50);
    });
}

function roundRect(ctx, x, y, w, h, r) {
    if (w < 2 * r) r = w / 2;
    if (h < 2 * r) r = h / 2;
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}