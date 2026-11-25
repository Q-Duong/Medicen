<?php

/*
|--------------------------------------------------------------------------
| Trợ lý AI theo phòng ban (Claude Managed Agents)
|--------------------------------------------------------------------------
|
| Mỗi phòng ban là một Agent lưu trên Claude (system prompt + skill + tool).
| Dựng / cập nhật: php artisan claude:setup <phong_ban>
| ID của agent, skill, environment được ghi vào storage/app/claude/agents.json.
|
*/

return [
    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('CLAUDE_MODEL', 'claude-opus-5-5'),

    // File lưu ID các tài nguyên đã tạo trên Claude
    'registry_path' => storage_path('app/claude/agents.json'),

    // Thời gian tối đa (giây) chờ agent trả lời một câu hỏi
    'reply_timeout' => (int) env('CLAUDE_REPLY_TIMEOUT', 240),

    // Số ngày giữ lịch sử chat với trợ lý (lệnh assistant:prune chạy mỗi ngày lúc 02:00)
    'history_days' => (int) env('CLAUDE_HISTORY_DAYS', 90),

    // SubAdmin loại Admin (role 0/1, tên 'SubAdmin') chỉ dùng các trợ lý này, không xem lịch sử chat của người khác
    'subadmin_assistants' => ['ke_toan'],

    'departments' => [
        'sales' => [
            'name' => 'NKL Sales',
            'title' => 'Trợ lý Sales',
            // Phòng ban (users.department) được dùng trợ lý này. Admin (role 0, 1) dùng được tất cả.
            'user_departments' => ['sales'],
            'description' => 'Trợ lý nội bộ phòng Sale NKL: báo giá, chốt lịch, công nợ, soạn tin nhắn khách.',
            'system_prompt' => resource_path('claude/agents/sales.md'),
            // Thư mục skill (chứa SKILL.md) sẽ được upload lên Claude
            'skills' => [
                resource_path('claude/skills/nkl-sale'),
            ],
            'tools' => \App\Services\Claude\Tools\SalesTools::class,

            /*
             * Chức năng chọn trong khung chat. Không giới hạn tool: chỉ gợi ý cho người
             * dùng và báo trợ lý biết đang làm việc gì ('instruction' gửi kèm câu hỏi).
             * 'form': hiện form nhập (xem resources/views/pages/admin/assistant/forms).
             * 'pdf' => true: hiện nút xuất PDF dưới câu trả lời (hiện chỉ Phân tích CFO của Kế toán).
             * Ở "Tự động", ngoài gợi ý riêng còn hiện câu gợi ý đầu tiên của từng chức năng khác (bấm là chuyển sang chức năng đó và hỏi luôn).
             */
            'modes' => [
                'auto' => [
                    'label' => 'Tự động',
                    'icon' => 'fas fa-magic',
                    'placeholder' => 'Hỏi gì cũng được: báo giá, lịch xe, công nợ, lợi nhuận…',
                    'instruction' => null,
                    'suggestions' => [
                        'Tuần sau xe nào còn trống?',
                        'Báo giá 300 cas XQ phổi tại Biên Hòa cho khách mới',
                        'Kiểm tra công nợ PK Thiên Hà và soạn tin nhắc nợ',
                    ],
                ],
                'bao_gia' => [
                    'label' => 'Báo giá',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'placeholder' => 'Ghi chú thêm cho báo giá (không bắt buộc)…',
                    'form' => 'quote',
                    'instruction' => 'Báo giá. Kiểm tra đủ thông tin bắt buộc theo skill (thiếu thì hỏi lại). Tra đơn vị (khách cũ/mới, giá cũ), '
                        . 'công nợ, lịch xe ngày chụp nếu có ngày, và tính lợi nhuận. Trả lời gồm: đơn giá và thành tiền, phần đã bao gồm, '
                        . 'kết luận lãi/hòa/lỗ, việc cần xin ý kiến KTT (nếu có), và tin nhắn báo giá soạn sẵn để gửi khách.',
                    'suggestions' => [
                        'Khách cũ BV Bình Chánh hỏi giá 250 cas phổi, giá lần trước bao nhiêu?',
                        'Khách ép giá XQ phổi xuống 20.000/cas cho 300 cas, còn lãi không?',
                    ],
                ],
                'loi_nhuan' => [
                    'label' => 'Tính lợi nhuận',
                    'icon' => 'fas fa-calculator',
                    'placeholder' => 'Giá khác mặc định nếu có, vd: lương TX đợt này 400.000/ngày…',
                    'form' => 'profit',
                    'instruction' => 'Tính lợi nhuận bằng tool tinh_loi_nhuan. Trình bày gọn: tổng thu, các khoản chi lớn, tổng chi, lợi nhuận ròng, '
                        . '% lãi, kết luận theo quy trình và các giả định. Giá nào người dùng báo khác mặc định thì dùng don_gia_khac.',
                    'suggestions' => [
                        'Đang áp dụng đơn giá lương TX, KTV, dầu bao nhiêu?',
                        'Đơn 2 ngày ở Cần Thơ, 340km đi về, 400 cas phổi, ngủ lại 1 đêm, 3 người: lãi bao nhiêu?',
                    ],
                ],
                'hop_dong' => [
                    'label' => 'Soạn hợp đồng',
                    'icon' => 'fas fa-file-signature',
                    'placeholder' => 'Ghi thêm yêu cầu (không bắt buộc), vd: bỏ phần phụ thu, thanh toán 30 ngày…',
                    'form' => 'contract',
                    'instruction' => 'Soạn hợp đồng ra file Word bằng tool soan_hop_dong (theo mẫu công ty, không tự viết lại điều khoản). Đơn vị mẫu chưa rõ bảng giá thì gọi '
                        . 'gia_hop_dong trước. Đổi giá vài bậc thì dùng doi_gia_bac. Trả lời: link [Tải hợp đồng Word](...), số hợp đồng, hợp đồng làm mẫu, các giá đã đổi, '
                        . 'tóm tắt bảng giá, và nêu rõ MỌI cảnh báo (bậc giá dưới giá sàn, thông tin Bên A còn trống). Nhắc kiểm tra trong Word trước khi gửi khách.',
                    'suggestions' => [
                        'Làm lại hợp đồng nguyên tắc cho PK 115 Y DƯỢC, bậc 100–149 ca giảm còn 21.000/ca',
                        'Làm hợp đồng cho khách mới giống hợp đồng của BV SANTE',
                        'Làm hợp đồng thời vụ cho BV TÂM TRÍ NHA TRANG ngày 15/12, trọn gói 14.000.000',
                    ],
                ],
                'lich_xe' => [
                    'label' => 'Lịch xe / chốt lịch',
                    'icon' => 'fas fa-shuttle-van',
                    'placeholder' => 'Ví dụ: ngày 20/11 còn xe nào trống?',
                    'instruction' => 'Lịch xe. Dùng xem_lich_xe để trả lời xe nào trống, đơn nào chưa xếp xe; xem_nguoi_di_don để biết ai đi đơn nào và SĐT. Khi chốt lịch, nhắc các bước '
                        . 'theo skill (lịch nháp, chuyển lịch chính thức trước 1 tuần, thông báo TX/KTV).',
                    'suggestions' => [
                        'Tuần sau xe nào còn trống?',
                        'Liệt kê các đơn tháng này chưa xếp xe',
                        'Ngày mai ai đi đơn nào, SĐT liên hệ?',
                    ],
                ],
                'cong_no' => [
                    'label' => 'Công nợ',
                    'icon' => 'fas fa-receipt',
                    'placeholder' => 'Tên đơn vị cần kiểm tra công nợ…',
                    'instruction' => 'Công nợ. Dùng xem_cong_no. Nêu tổng nợ, phần quá hạn, hóa đơn quá hạn lâu nhất, và bước xử lý theo skill '
                        . '(nhắn tin, gọi điện, báo KTT, có được nhận đơn mới không). Nếu cần, soạn sẵn tin nhắc nợ.',
                    'suggestions' => [
                        'Kiểm tra công nợ PK Thiên Hà',
                        'Soạn tin nhắc nợ cho BV SG Bình Dương',
                    ],
                ],
                'tin_nhan' => [
                    'label' => 'Soạn tin nhắn khách',
                    'icon' => 'far fa-comment-dots',
                    'placeholder' => 'Ví dụ: tin confirm lịch cho PK Thiên Hà ngày 13/11…',
                    'instruction' => 'Soạn tin nhắn gửi khách theo mẫu trong skill, lịch sự, đặt trong khối ```tin-nhan để người dùng bấm sao chép. Nếu tin cần số liệu (ngày, số tiền, '
                        . 'địa điểm) thì tra bằng tool; thiếu thông tin thì để dấu [..] cho người dùng điền.',
                    'suggestions' => [
                        'Soạn tin confirm lịch chụp cho khách',
                        'Soạn tin từ chối lịch khéo léo',
                        'Soạn tin xin lỗi vì xe đến trễ 30 phút do kẹt xe',
                    ],
                ],
            ],
        ],

        // Trợ lý phòng Kế toán: sổ sách MISA cả công ty (XQ + CT) nhập vào acc_* (php artisan accounting:import),
        // công nợ / thu chi trên web, báo cáo tài chính + hợp đồng (acc_documents). Chỉ đọc.
        'ke_toan' => [
            'name' => 'NKL Kế toán',
            'title' => 'Trợ lý Kế toán',
            'user_departments' => ['ke_toan'],
            'description' => 'Trợ lý nội bộ phòng Kế toán NKL kiêm CFO: doanh thu, chi phí, lợi nhuận, dòng tiền, công nợ, lương, báo cáo tài chính, hợp đồng; phân tích chuyên sâu và hướng kinh doanh.',
            'system_prompt' => resource_path('claude/agents/ke_toan.md'),
            'skills' => [
                resource_path('claude/skills/nkl-ke-toan'),
            ],
            'tools' => \App\Services\Claude\Tools\AccountantTools::class,
            'modes' => [
                'auto' => [
                    'label' => 'Tự động',
                    'icon' => 'fas fa-magic',
                    'placeholder' => 'Hỏi về doanh thu, chi phí, dòng tiền, công nợ, lương, hợp đồng…',
                    'instruction' => null,
                    'suggestions' => [
                        'Tóm tắt kết quả kinh doanh 6 tháng đầu năm 2026 theo tháng',
                        'Tồn quỹ tiền mặt và các ngân hàng cuối tháng 6/2026?',
                        'Khách nào đang nợ quá hạn nhiều nhất?',
                    ],
                ],
                'cfo' => [
                    'label' => 'Phân tích CFO',
                    'icon' => 'fas fa-user-tie',
                    // Chỉ chức năng có 'pdf' => true mới hiện nút xuất PDF dưới câu trả lời (báo cáo đủ bảng, biểu đồ)
                    'pdf' => true,
                    'placeholder' => 'Kỳ cần phân tích hoặc vấn đề, vd: tháng 10/2026, 6 tháng gần nhất, vì sao lợi nhuận giảm…',
                    'instruction' => 'Vai CFO (mục 7 skill nkl-ke-toan). Lấy đủ 3 lớp: phan_tich_van_hanh (vận hành XQ, lãi ước tính, so kỳ trước), ket_qua_kinh_doanh (lãi lỗ, chi phí), '
                        . 'tiền và nợ (du_bao_dong_tien, tuoi_no_tien_ket, so_quy_ngan_hang). Trình bày: tóm tắt điều hành, bảng chỉ số kỳ này / kỳ trước, '
                        . 'phân tích nguyên nhân bằng số cụ thể, rủi ro theo mức độ, khuyến nghị hành động có người làm, thời hạn và tác động bằng tiền. '
                        . 'Kèm 1–3 biểu đồ ```bieu-do (doanh thu & lợi nhuận theo tháng, khách hàng / xe, cơ cấu chi phí). '
                        . 'Kết thúc bằng mục "⚠️ Dữ liệu còn thiếu" (gom thieu_du_lieu từ các tool: thiếu gì, ảnh hưởng, ai bổ sung ở đâu).',
                    'suggestions' => [
                        'Báo cáo CFO tháng vừa rồi: tình hình, rủi ro và việc cần làm',
                        'Phân tích 6 tháng gần nhất: doanh thu đến từ đâu, khách nào đang giảm?',
                        'Xe nào chạy hiệu quả nhất, xe nào đang lãng phí?',
                        'Dòng tiền 13 tuần tới có tuần nào thiếu tiền không?',
                        'Tiền đang kẹt ở đâu, nên thu nợ ai trước?',
                    ],
                ],
                'chien_luoc' => [
                    'label' => 'Hướng kinh doanh',
                    'pdf' => true,
                    'icon' => 'fas fa-compass',
                    'placeholder' => 'Vd: năm 2027 nên tập trung vào đâu? Có nên mua thêm xe? Tăng giá được không?',
                    'instruction' => 'Vạch hướng kinh doanh ở vai CFO (mục 7 skill nkl-ke-toan): dựa trên số liệu 12 tháng gần nhất (phan_tich_van_hanh, ket_qua_kinh_doanh, '
                        . 'cong_no_ct, dòng tiền), xác định thế mạnh / điểm yếu, các lựa chọn và kịch bản cơ sở – tốt – xấu với giả định ghi rõ, '
                        . 'tác động lên lợi nhuận và tiền; kết thúc bằng 3–5 ưu tiên cụ thể. Số liệu ngoài web (giá đối thủ, giá máy mới) thì hỏi lại người dùng, không bịa. '
                        . 'Vẽ biểu đồ ```bieu-do so sánh các kịch bản và xu hướng 12 tháng. Kết thúc bằng mục "⚠️ Dữ liệu còn thiếu" để người dùng bổ sung.',
                    'suggestions' => [
                        'Năm 2027 công ty nên tập trung vào đâu để tăng lợi nhuận?',
                        'Có nên mua thêm 1 xe X-quang không? Cần bao nhiêu ca / tháng để hòa vốn?',
                        'Nếu tăng giá 5% thì lợi nhuận và rủi ro mất khách thế nào?',
                    ],
                ],
                'bao_cao' => [
                    'label' => 'Kết quả kinh doanh',
                    'pdf' => true,
                    'icon' => 'fas fa-chart-line',
                    'placeholder' => 'Kỳ cần xem, vd: quý 2/2026, năm 2025 so với 2024…',
                    'instruction' => 'Kết quả kinh doanh. Dùng ket_qua_kinh_doanh (chia theo kỳ phù hợp), so sánh kỳ trước nếu hợp lý, chỉ ra khoản chi phí tăng / giảm mạnh. '
                        . 'Nếu là năm đã có báo cáo tài chính thì đối chiếu và nêu chênh lệch.',
                    'suggestions' => [
                        'So sánh kết quả kinh doanh 2025 với 2024',
                        'Chi phí quản lý 6 tháng đầu 2026 theo từng khoản',
                    ],
                ],
                'dong_tien' => [
                    'label' => 'Dòng tiền',
                    'pdf' => true,
                    'icon' => 'fas fa-coins',
                    'placeholder' => 'Vd: tiền mặt tháng 5/2026 chi những gì…',
                    'instruction' => 'Dòng tiền. Dùng so_quy_ngan_hang và thu_chi_web: số dư đầu / cuối, thu chi theo tháng, khoản chi lớn, các khoản sắp đến hạn; cảnh báo nếu thiếu tiền.',
                    'suggestions' => [
                        'Chi tiền mặt năm 2025 theo XQ / CT / VP và theo xe',
                        'Các khoản phải chi trong 30 ngày tới, có đủ tiền không?',
                    ],
                ],
                'cong_no' => [
                    'label' => 'Công nợ',
                    'pdf' => true,
                    'icon' => 'fas fa-receipt',
                    'placeholder' => 'Tên khách / nhà cung cấp…',
                    'instruction' => 'Công nợ. Khách XQ: cong_no_web (hóa đơn, quá hạn); đối chiếu cong_no_doi_tac (sổ MISA) nếu cần. Nhà cung cấp / vay: cong_no_doi_tac phai_tra, phat_sinh_tai_khoan 341. '
                        . 'Soạn sẵn tin nhắc nợ / thư đối chiếu khi được yêu cầu.',
                    'suggestions' => [
                        'Danh sách khách nợ quá hạn và số ngày quá hạn',
                        'Công ty còn nợ nhà cung cấp nào bao nhiêu?',
                    ],
                ],
                'tai_lieu' => [
                    'label' => 'BCTC / Hợp đồng',
                    'pdf' => true,
                    'icon' => 'far fa-file-alt',
                    'placeholder' => 'Vd: hợp đồng máy CT Lagi hết hạn khi nào?',
                    'instruction' => 'Tra tài liệu bằng tool tai_lieu (báo cáo tài chính, hợp đồng). Trả lời kèm tên tài liệu nguồn.',
                    'suggestions' => [
                        'Tổng tài sản và nợ phải trả cuối năm 2025 theo BCTC',
                        'Các hợp đồng thuê / mua máy siêu âm đang có',
                    ],
                ],
            ],
        ],

        // 'technicians' => [...], 'results' => [...]
    ],
];
