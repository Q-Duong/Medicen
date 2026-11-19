<?php

namespace App\Services\Claude\Tools;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Tool cho agent phòng Kế toán. Tất cả chỉ ĐỌC.
 *
 * Hai nguồn dữ liệu:
 *  - Sổ sách MISA của cả công ty (XQ + CT) nhập vào acc_* (php artisan accounting:import / trang Dữ liệu kế toán):
 *    nhật ký chung, sổ quỹ / ngân hàng, công nợ phải thu / phải trả theo kỳ, lương thực trả theo tháng.
 *  - Dữ liệu đang chạy trên web Medicen (mảng XQ): công nợ từng đơn (accountants), báo cáo thu chi (reports).
 * Tài liệu (báo cáo tài chính, hợp đồng): acc_documents.
 */
class AccountantTools implements DepartmentTools
{
    /** Tên khoản mục theo tài khoản (Thông tư 200) để trợ lý trình bày dễ hiểu */
    private const ACCOUNT_NAMES = [
        '111' => 'Tiền mặt', '112' => 'Tiền gửi ngân hàng', '131' => 'Phải thu khách hàng', '133' => 'Thuế GTGT được khấu trừ',
        '138' => 'Phải thu khác', '141' => 'Tạm ứng', '142' => 'Chi phí trả trước ngắn hạn', '152' => 'Nguyên vật liệu', '153' => 'Công cụ dụng cụ',
        '154' => 'Chi phí SXKD dở dang', '156' => 'Hàng hóa', '211' => 'TSCĐ hữu hình', '214' => 'Hao mòn TSCĐ', '242' => 'Chi phí trả trước',
        '331' => 'Phải trả người bán', '333' => 'Thuế phải nộp', '334' => 'Phải trả người lao động', '338' => 'Phải trả khác (BHXH, ...)',
        '341' => 'Vay và nợ thuê tài chính', '411' => 'Vốn chủ sở hữu', '421' => 'Lợi nhuận chưa phân phối', '511' => 'Doanh thu',
        '5111' => 'Doanh thu bán hàng', '5113' => 'Doanh thu dịch vụ', '515' => 'Doanh thu tài chính', '632' => 'Giá vốn',
        '635' => 'Chi phí tài chính (lãi vay)', '642' => 'Chi phí quản lý doanh nghiệp', '6421' => 'CP nhân viên quản lý (lương, BH)',
        '6422' => 'CP vật liệu quản lý', '6423' => 'CP đồ dùng văn phòng', '6424' => 'CP khấu hao TSCĐ', '6425' => 'Thuế, phí, lệ phí',
        '6426' => 'CP dự phòng', '6427' => 'CP dịch vụ mua ngoài', '6428' => 'CP bằng tiền khác', '711' => 'Thu nhập khác', '811' => 'Chi phí khác',
        '821' => 'Chi phí thuế TNDN', '911' => 'Xác định kết quả kinh doanh',
    ];

    private const BOOKS = ['TIEN_MAT' => 'Tiền mặt', 'HDB' => 'HDBank', 'VCB' => 'Vietcombank', 'AGRI' => 'Agribank'];

    public function definitions(): array
    {
        $date = fn (string $d) => ['type' => 'string', 'description' => $d . ' (YYYY-MM-DD)'];

        return [
            [
                'name' => 'tong_quan_du_lieu',
                'description' => 'Xem dữ liệu sổ sách đang có: mỗi loại sổ có từ ngày nào đến ngày nào, lần nhập gần nhất, có báo cáo tài chính / hợp đồng nào. '
                    . 'Gọi đầu tiên khi không chắc kỳ hỏi đã có số liệu chưa.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
            ],
            [
                'name' => 'ket_qua_kinh_doanh',
                'description' => 'Kết quả kinh doanh THEO SỔ SÁCH (nhật ký chung) trong khoảng ngày: doanh thu (511), giá vốn (632), chi phí quản lý (642, '
                    . 'tách theo tài khoản con: lương nhân viên 6421, khấu hao 6424, dịch vụ mua ngoài 6427, ...), chi phí / doanh thu tài chính, '
                    . 'thu nhập / chi phí khác, thuế TNDN, lợi nhuận. Có thể chia theo tháng / quý / năm. Không tính bút toán kết chuyển 911. '
                    . 'Lưu ý: báo cáo tài chính đã nộp có thể có điều chỉnh khác sổ — xem tool tai_lieu (loai bctc).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_ngay' => $date('Từ ngày'),
                        'den_ngay' => $date('Đến ngày'),
                        'chia_theo' => ['type' => 'string', 'enum' => ['tong', 'thang', 'quy', 'nam'], 'description' => 'Mặc định: tong'],
                    ],
                    'required' => ['tu_ngay', 'den_ngay'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'phat_sinh_tai_khoan',
                'description' => 'Tổng phát sinh Nợ / Có của một tài khoản (theo đầu số, vd 642, 6427, 131, 331, 341) trong khoảng ngày, chia theo tháng, '
                    . 'kèm các tài khoản đối ứng lớn nhất. Có thể lọc thêm theo từ khóa trong diễn giải (vd "CT", "Lagi", "dầu", "xe 3").',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tai_khoan' => ['type' => 'string', 'description' => 'Đầu số tài khoản, vd 642 hoặc 6427'],
                        'tu_ngay' => $date('Từ ngày'),
                        'den_ngay' => $date('Đến ngày'),
                        'tu_khoa' => ['type' => 'string', 'description' => 'Lọc diễn giải có chứa (không bắt buộc)'],
                    ],
                    'required' => ['tai_khoan', 'tu_ngay', 'den_ngay'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'tim_but_toan',
                'description' => 'Tìm bút toán trong nhật ký chung và sổ quỹ / ngân hàng theo từ khóa diễn giải, số chứng từ, số hóa đơn, tài khoản, '
                    . 'khoảng ngày, khoảng số tiền. Trả tối đa 50 dòng mới nhất và tổng tiền các dòng khớp.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_khoa' => ['type' => 'string', 'description' => 'Từ khóa trong diễn giải / số chứng từ / số hóa đơn'],
                        'tai_khoan' => ['type' => 'string', 'description' => 'Đầu số tài khoản Nợ hoặc Có (không bắt buộc)'],
                        'tu_ngay' => $date('Từ ngày (không bắt buộc)'),
                        'den_ngay' => $date('Đến ngày (không bắt buộc)'),
                        'so_tien_tu' => ['type' => 'number', 'description' => 'Số tiền tối thiểu (không bắt buộc)'],
                        'so_tien_den' => ['type' => 'number', 'description' => 'Số tiền tối đa (không bắt buộc)'],
                        'nguon' => ['type' => 'string', 'enum' => ['nhat_ky', 'quy_ngan_hang', 'tat_ca'], 'description' => 'Mặc định: tat_ca'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'so_quy_ngan_hang',
                'description' => 'Sổ quỹ tiền mặt và tiền gửi ngân hàng (HDB, VCB, AGRI) trong khoảng ngày: số dư đầu, tổng thu, tổng chi, số dư cuối '
                    . '(nếu sổ có cột tồn), theo tháng. Với tiền mặt (2025 trở đi) chia thêm theo khoản mục XQ / CT / VP, theo xe (XE1..XE8) '
                    . 'và theo mục thu chi (phí cầu đường, tạm ứng, ...).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'so' => ['type' => 'string', 'enum' => ['TIEN_MAT', 'HDB', 'VCB', 'AGRI', 'TAT_CA'], 'description' => 'Mặc định TAT_CA'],
                        'tu_ngay' => $date('Từ ngày'),
                        'den_ngay' => $date('Đến ngày'),
                    ],
                    'required' => ['tu_ngay', 'den_ngay'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'cong_no_doi_tac',
                'description' => 'Công nợ THEO SỔ SÁCH MISA: tổng hợp phải thu khách hàng (131) hoặc phải trả nhà cung cấp (331) của một kỳ đã nhập '
                    . '(số dư đầu kỳ, phát sinh, số dư cuối kỳ từng đối tác). Không ghi kỳ thì lấy kỳ mới nhất. Lọc được theo tên / mã đối tác. '
                    . 'Công nợ từng hóa đơn XQ đang theo dõi trên web: dùng cong_no_web.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'loai' => ['type' => 'string', 'enum' => ['phai_thu', 'phai_tra']],
                        'nam' => ['type' => 'integer', 'description' => 'Năm của kỳ (vd 2024). Không ghi = kỳ mới nhất'],
                        'tu_khoa' => ['type' => 'string', 'description' => 'Tên / mã đối tác (không bắt buộc)'],
                    ],
                    'required' => ['loai'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'luong_thuc_tra',
                'description' => 'Lương thực trả theo tháng, tách mảng CT và XQ, kèm thưởng lễ / Tết, trong các năm chỉ định (tổng theo bộ phận, '
                    . 'không có lương từng người).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_nam' => ['type' => 'integer'],
                        'den_nam' => ['type' => 'integer'],
                    ],
                    'required' => ['tu_nam', 'den_nam'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'cong_no_web',
                'description' => 'Công nợ XQ đang theo dõi trên web Medicen (trang Công nợ, cập nhật từng ngày): từng hóa đơn chưa thu đủ của một đơn vị, '
                    . 'hoặc danh sách đơn vị đang nợ (tổng nợ, quá hạn) nếu không ghi tu_khoa. Có thêm số đơn chưa xuất hóa đơn.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tu_khoa' => ['type' => 'string', 'description' => 'Tên viết tắt / tên / mã đơn vị (không bắt buộc)'],
                        'nam' => ['type' => 'integer', 'description' => 'Chỉ tính đơn chụp trong năm này (không bắt buộc)'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'thu_chi_web',
                'description' => 'Báo cáo thu chi theo tháng đang lập trên web (trang Báo cáo thu chi): các khoản thu / chi dự kiến, ngày đến hạn, '
                    . 'số đã thu / chi thực tế. Không ghi tháng thì lấy các khoản chưa thu / chi đến hạn trong 30 ngày tới.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nam' => ['type' => 'integer'],
                        'thang' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 12],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'tai_lieu',
                'description' => 'Tra tài liệu kế toán đã lưu: báo cáo tài chính (số liệu chính của bảng cân đối, kết quả kinh doanh theo từng năm / kỳ) '
                    . 'và hợp đồng (bên ký, ngày ký, thời hạn, giá trị, điều khoản chính). Tìm theo từ khóa trong tên / nội dung.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'loai' => ['type' => 'string', 'enum' => ['bctc', 'hop_dong', 'tat_ca']],
                        'tu_khoa' => ['type' => 'string', 'description' => 'Vd: 2025, Lagi, máy SA, ĐHYD (không bắt buộc)'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'cong_no_ct',
                'description' => 'Công nợ CT (máy CT đặt tại bệnh viện hợp tác: Á Châu, Bảo Lộc, Cà Mau, Gò Công, Lagi-YSG…), kế toán nhập trên web: '
                    . 'theo bệnh viện và tháng gồm số ca, doanh thu, chi phí, lãi lỗ, phần chia bệnh viện / Medicen, Medicen thu về, '
                    . 'các lần thanh toán (ngày, hình thức HDB / VCB chú Lộc), còn phải thu. Kèm công nợ cũ cần ghi nhớ (Thiên Phú…). '
                    . 'Dùng cho câu hỏi về doanh thu / lãi lỗ / công nợ mảng CT theo bệnh viện (chính xác hơn lọc diễn giải sổ MISA).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nam' => ['type' => 'integer', 'description' => 'Năm, mặc định năm hiện tại'],
                        'benh_vien' => ['type' => 'string', 'description' => 'Tên hoặc mã bệnh viện (không bắt buộc)'],
                        'chi_tiet' => ['type' => 'boolean', 'description' => 'true = trả từng tháng và từng lần thanh toán; mặc định chỉ tổng hợp'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'so_vcb_chu_loc',
                'description' => 'Sổ TK VCB cá nhân chú Lộc (tiền công ty đi qua TK chú): số dư chốt, số dư đầu / cuối tháng, các dòng thu (tiền CT bệnh viện '
                    . 'chuyển vào, góp vốn, bác sĩ đọc CT…) và chi (chú chuyển lại HDB công ty, trả gốc lãi căn A44, xe VF7…), số dư cuối các tháng trong năm, ghi nhớ số cũ.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nam' => ['type' => 'integer'],
                        'thang' => ['type' => 'integer', 'description' => '1-12; bỏ trống = chỉ số dư cuối các tháng của năm'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function call(string $name, array $input, ?User $user = null): array
    {
        return match ($name) {
            'tong_quan_du_lieu' => $this->overview(),
            'ket_qua_kinh_doanh' => $this->incomeStatement($this->date($input, 'tu_ngay'), $this->date($input, 'den_ngay'), (string) ($input['chia_theo'] ?? 'tong')),
            'phat_sinh_tai_khoan' => $this->accountActivity($this->account($input), $this->date($input, 'tu_ngay'), $this->date($input, 'den_ngay'), $this->optString($input, 'tu_khoa')),
            'tim_but_toan' => $this->searchEntries($input),
            'so_quy_ngan_hang' => $this->cashBooks(strtoupper((string) ($input['so'] ?? 'TAT_CA')), $this->date($input, 'tu_ngay'), $this->date($input, 'den_ngay')),
            'cong_no_doi_tac' => $this->partyBalances((string) ($input['loai'] ?? ''), isset($input['nam']) ? (int) $input['nam'] : null, $this->optString($input, 'tu_khoa')),
            'luong_thuc_tra' => $this->salaries((int) ($input['tu_nam'] ?? 0), (int) ($input['den_nam'] ?? 0)),
            'cong_no_web' => $this->webDebts($this->optString($input, 'tu_khoa'), isset($input['nam']) ? (int) $input['nam'] : null),
            'thu_chi_web' => $this->webCashPlan(isset($input['nam']) ? (int) $input['nam'] : null, isset($input['thang']) ? (int) $input['thang'] : null),
            'cong_no_ct' => $this->ctBook((int) ($input['nam'] ?? now()->year), $this->optString($input, 'benh_vien'), (bool) ($input['chi_tiet'] ?? false)),
            'so_vcb_chu_loc' => $this->vcbBook((int) ($input['nam'] ?? now()->year), isset($input['thang']) ? (int) $input['thang'] : null),
            'tai_lieu' => $this->documents((string) ($input['loai'] ?? 'tat_ca'), $this->optString($input, 'tu_khoa')),
            default => throw new InvalidArgumentException("Tool không tồn tại: {$name}"),
        };
    }

    // ------------------------------------------------------------------
    // cong_no_ct / so_vcb_chu_loc
    // ------------------------------------------------------------------

    private function ctBook(int $year, ?string $partner, bool $detail): array
    {
        $book = app(\App\Services\Accounting\CtBook::class);
        $p = $partner ? \App\Models\Accounting\CtPartner::where('code', strtoupper($partner))->orWhere('name', 'like', '%' . $partner . '%')->first() : null;
        if ($partner && !$p) {
            return ['loi' => 'Không thấy bệnh viện "' . $partner . '"', 'benh_vien' => \App\Models\Accounting\CtPartner::pluck('name', 'code')];
        }
        $sum = $book->summary($year);
        $out = [
            'nam' => $year,
            'ghi_chu' => 'Medicen thu về = phần doanh thu Medicen được nhận; còn phải thu = Medicen thu về − đã thanh toán. Số tiền đơn vị đồng.',
            'tong_nam' => $sum['total'],
            'theo_benh_vien' => $sum['by_partner']->map(fn ($x) => ['benh_vien' => $x['partner']->name] + collect($x)->except('partner')->all())->values(),
            'con_phai_thu_moi_ky' => $book->totalOutstanding($p?->id),
            'cong_no_cu_ghi_nho' => \App\Models\Accounting\AccMemo::where('scope', 'ct')->get(['title', 'period', 'amount', 'note']),
        ];
        if ($p) {
            $out['theo_benh_vien'] = $out['theo_benh_vien']->where('benh_vien', $p->name)->values();
            $out['tong_nam'] = $out['theo_benh_vien']->first() ?? 'Bệnh viện chưa có công nợ năm ' . $year;
        }
        if ($detail || $p) {
            $out['chi_tiet'] = $book->rows($year, $p?->id)->map(fn ($r) => [
                'thang' => $r->month, 'benh_vien' => $r->partner->name, 'loai' => $r->kind === 'bhyt' ? 'BHYT ' . $r->label : 'ca chụp',
                'so_ca' => $r->quantity, 'doanh_thu' => (int) $r->revenue, 'chi_phi' => (int) $r->cost, 'lai_lo' => $r->profit,
                'bv_chia' => (int) $r->hospital_share, 'medicen_chia' => (int) $r->medicen_share,
                'bv_thu_ve' => (int) $r->hospital_collect, 'medicen_thu_ve' => (int) $r->medicen_collect,
                'thanh_toan' => $r->payments->map(fn ($x) => ['ngay' => $x->paid_on->toDateString(), 'hinh_thuc' => $x->method, 'so_tien' => (int) $x->amount])->all(),
                'con_phai_thu' => $r->outstanding,
            ])->values();
        }

        return $out;
    }

    private function vcbBook(int $year, ?int $month): array
    {
        $book = app(\App\Services\Accounting\VcbBook::class);
        $opening = $book->opening();
        $out = [
            'so_du_chot' => $opening ? ['ngay' => $opening->entry_date->toDateString(), 'so_tien' => (int) $opening->thu] : null,
            'ghi_chu' => $opening ? 'Số dư = số dư chốt + thu − chi các dòng sau ngày chốt; dòng trước ngày chốt chỉ để ghi nhớ.'
                : 'Kế toán chưa chốt số dư đầu: chưa tính được số dư, chỉ có các dòng thu chi.',
            'so_du_cuoi_thang' => $opening ? $book->yearBalances($year)->filter(fn ($v) => $v !== null) : [],
            'ghi_nho_so_cu' => \App\Models\Accounting\AccMemo::where('scope', 'vcb')->get(['title', 'period', 'amount', 'note']),
        ];
        if ($month) {
            $m = $book->month($year, $month);
            $out['thang'] = [
                'thang' => $month . '/' . $year,
                'so_du_dau_thang' => $m['start_balance'],
                'thu' => $m['thu'],
                'chi' => $m['chi'],
                'so_du_cuoi_thang' => $m['end_balance'],
                'cac_dong' => $m['entries']->map(fn ($e) => ['ngay' => $e->entry_date->toDateString(), 'noi_dung' => $e->description,
                    'thu' => (int) $e->thu, 'chi' => (int) $e->chi, 'nguon' => \App\Models\Accounting\VcbEntry::SOURCES[$e->source] ?? $e->source])->values(),
            ];
            if (!$opening) {
                $out['thang']['tong_thu_cac_dong'] = (int) $m['entries']->sum('thu');
                $out['thang']['tong_chi_cac_dong'] = (int) $m['entries']->sum('chi');
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // tong_quan_du_lieu
    // ------------------------------------------------------------------

    private function overview(): array
    {
        $journal = DB::table('acc_journal_entries')->selectRaw('MIN(posted_on) tu, MAX(posted_on) den, COUNT(*) so_dong')->first();
        $cash = DB::table('acc_cash_entries')->whereNotNull('posted_on')->groupBy('book')
            ->selectRaw('book, MIN(posted_on) tu, MAX(posted_on) den, COUNT(*) so_dong')->get()
            ->map(fn ($r) => ['so' => self::BOOKS[$r->book] ?? $r->book, 'tu' => $r->tu, 'den' => $r->den, 'so_dong' => (int) $r->so_dong])->all();
        $parties = DB::table('acc_party_balances')->groupBy('kind', 'period_from', 'period_to')->orderBy('period_to')
            ->selectRaw('kind, period_from, period_to, COUNT(*) so_doi_tac')->get()
            ->map(fn ($r) => ['loai' => $r->kind === 'receivable' ? 'phai_thu' : 'phai_tra', 'ky' => "{$r->period_from} → {$r->period_to}", 'so_doi_tac' => (int) $r->so_doi_tac])->all();
        $salary = DB::table('acc_salaries')->where('kind', 'luong')->selectRaw("MIN(CONCAT(year,'-',LPAD(month,2,'0'))) tu, MAX(CONCAT(year,'-',LPAD(month,2,'0'))) den")->first();
        $last = DB::table('acc_imports')->orderByDesc('id')->first(['file_name', 'created_at']);

        return [
            'nhat_ky_chung' => ['tu' => $journal->tu, 'den' => $journal->den, 'so_dong' => (int) $journal->so_dong],
            'so_quy_ngan_hang' => $cash,
            'cong_no_theo_ky' => $parties,
            'luong_thuc_tra' => ['tu_thang' => $salary->tu, 'den_thang' => $salary->den],
            'tai_lieu' => DB::table('acc_documents')->orderBy('type')->orderBy('title')->get(['type', 'title', 'period'])
                ->map(fn ($d) => ['loai' => $d->type, 'ten' => $d->title, 'ky' => $d->period])->all(),
            'lan_nhap_gan_nhat' => $last ? ['file' => $last->file_name, 'luc' => (string) $last->created_at] : null,
            'web' => 'Công nợ XQ từng đơn và báo cáo thu chi trên web luôn là số mới nhất (cong_no_web, thu_chi_web).',
        ];
    }

    // ------------------------------------------------------------------
    // ket_qua_kinh_doanh
    // ------------------------------------------------------------------

    private function incomeStatement(Carbon $from, Carbon $to, string $by): array
    {
        $this->checkRange($from, $to, 1830);
        $period = match ($by) {
            'thang' => "DATE_FORMAT(posted_on, '%Y-%m')",
            'quy' => "CONCAT(YEAR(posted_on), '-Q', QUARTER(posted_on))",
            'nam' => 'YEAR(posted_on)',
            default => "'tong'",
        };
        // Phát sinh theo tài khoản con (4 số) của các tài khoản kết quả, bỏ bút toán kết chuyển 911
        $rows = DB::select("
            SELECT {$period} AS ky, LEFT(acc, 4) AS tk, SUM(no) AS no, SUM(co) AS co FROM (
                SELECT posted_on, debit_account AS acc, amount AS no, 0 AS co FROM acc_journal_entries
                    WHERE posted_on BETWEEN ? AND ? AND credit_account NOT LIKE '911%' AND debit_account REGEXP '^(5|6|7|8)'
                UNION ALL
                SELECT posted_on, credit_account AS acc, 0 AS no, amount AS co FROM acc_journal_entries
                    WHERE posted_on BETWEEN ? AND ? AND debit_account NOT LIKE '911%' AND credit_account REGEXP '^(5|6|7|8)'
            ) x GROUP BY ky, tk ORDER BY ky, tk", [$from->toDateString(), $to->toDateString(), $from->toDateString(), $to->toDateString()]);

        $periods = [];
        foreach ($rows as $r) {
            $g = substr($r->tk, 0, 3);
            $isIncome = in_array($g, ['511', '515', '711'], true);
            $val = $isIncome ? $r->co - $r->no : $r->no - $r->co;
            $p = &$periods[(string) $r->ky];
            $p[$g] = ($p[$g] ?? 0) + $val;
            if ($g === '642') {
                $p['chi_tiet_642'][$r->tk] = ($p['chi_tiet_642'][$r->tk] ?? 0) + $val;
            }
            unset($p);
        }

        $out = [];
        foreach ($periods as $ky => $p) {
            $dt = round($p['511'] ?? 0);
            $gv = round($p['632'] ?? 0);
            $ql = round($p['642'] ?? 0);
            $dttc = round($p['515'] ?? 0);
            $cptc = round($p['635'] ?? 0);
            $tnk = round($p['711'] ?? 0);
            $cpk = round($p['811'] ?? 0);
            $thue = round($p['821'] ?? 0);
            $truocThue = $dt - $gv - $ql + $dttc - $cptc + $tnk - $cpk;
            $detail = [];
            foreach ($p['chi_tiet_642'] ?? [] as $tk => $v) {
                $detail[] = ['tai_khoan' => $tk, 'ten' => self::ACCOUNT_NAMES[$tk] ?? '', 'so_tien' => round($v)];
            }
            usort($detail, fn ($a, $b) => $b['so_tien'] <=> $a['so_tien']);
            $out[] = [
                'ky' => $ky,
                'doanh_thu_511' => $dt, 'gia_von_632' => $gv, 'loi_nhuan_gop' => $dt - $gv,
                'chi_phi_quan_ly_642' => $ql, 'chi_tiet_chi_phi_quan_ly' => $detail,
                'doanh_thu_tai_chinh_515' => $dttc, 'chi_phi_tai_chinh_635' => $cptc,
                'thu_nhap_khac_711' => $tnk, 'chi_phi_khac_811' => $cpk,
                'loi_nhuan_truoc_thue' => $truocThue, 'thue_tndn_821' => $thue, 'loi_nhuan_sau_thue' => $truocThue - $thue,
            ];
        }

        return [
            'nguon' => 'Sổ nhật ký chung MISA (không tính bút toán kết chuyển 911)',
            'tu_ngay' => $from->toDateString(), 'den_ngay' => $to->toDateString(),
            'du_lieu_so_den_ngay' => DB::table('acc_journal_entries')->max('posted_on'),
            'ket_qua' => $out,
            'luu_y' => 'Đây là số theo sổ sách. Báo cáo tài chính đã nộp có thể khác do bút toán điều chỉnh khi lập báo cáo (vd năm 2025: '
                . 'chi phí quản lý, thu nhập khác, chi phí khác khác sổ). Muốn số chính thức thì xem tai_lieu loai bctc.',
        ];
    }

    // ------------------------------------------------------------------
    // phat_sinh_tai_khoan
    // ------------------------------------------------------------------

    private function accountActivity(string $acc, Carbon $from, Carbon $to, ?string $keyword): array
    {
        $this->checkRange($from, $to, 1830);
        $base = fn () => DB::table('acc_journal_entries')->whereBetween('posted_on', [$from->toDateString(), $to->toDateString()])
            ->when($keyword, fn ($q) => $q->where('description', 'like', $this->like($keyword)));

        $byMonth = DB::query()->fromSub(
            $base()->where('debit_account', 'like', $acc . '%')->selectRaw("DATE_FORMAT(posted_on,'%Y-%m') ky, amount no, 0 co")
                ->unionAll($base()->where('credit_account', 'like', $acc . '%')->selectRaw("DATE_FORMAT(posted_on,'%Y-%m') ky, 0 no, amount co")),
            'x')->groupBy('ky')->orderBy('ky')->selectRaw('ky, ROUND(SUM(no)) no, ROUND(SUM(co)) co')->get();

        $contra = fn (string $side, string $other) => $base()->where($side, 'like', $acc . '%')->where($other, 'not like', '911%')
            ->groupBy($other)->selectRaw("{$other} tk, ROUND(SUM(amount)) tien, COUNT(*) so_dong")->orderByDesc('tien')->limit(8)->get()
            ->map(fn ($r) => ['tai_khoan' => $r->tk, 'ten' => $this->accountName($r->tk), 'so_tien' => (float) $r->tien, 'so_dong' => (int) $r->so_dong])->all();

        return [
            'tai_khoan' => $acc, 'ten' => $this->accountName($acc),
            'tu_ngay' => $from->toDateString(), 'den_ngay' => $to->toDateString(), 'loc_dien_giai' => $keyword,
            'tong_no' => (float) $byMonth->sum('no'), 'tong_co' => (float) $byMonth->sum('co'),
            'theo_thang' => $byMonth->map(fn ($r) => ['thang' => $r->ky, 'no' => (float) $r->no, 'co' => (float) $r->co])->all(),
            'doi_ung_khi_ghi_no' => $contra('debit_account', 'credit_account'),
            'doi_ung_khi_ghi_co' => $contra('credit_account', 'debit_account'),
            'ghi_chu' => 'Gồm cả bút toán kết chuyển cuối kỳ nếu có (đối ứng 911).',
        ];
    }

    // ------------------------------------------------------------------
    // tim_but_toan
    // ------------------------------------------------------------------

    private function searchEntries(array $input): array
    {
        $kw = $this->optString($input, 'tu_khoa');
        $acc = isset($input['tai_khoan']) && $input['tai_khoan'] !== '' ? $this->account($input) : null;
        $from = isset($input['tu_ngay']) ? $this->date($input, 'tu_ngay') : null;
        $to = isset($input['den_ngay']) ? $this->date($input, 'den_ngay') : null;
        $min = isset($input['so_tien_tu']) ? (float) $input['so_tien_tu'] : null;
        $max = isset($input['so_tien_den']) ? (float) $input['so_tien_den'] : null;
        $src = (string) ($input['nguon'] ?? 'tat_ca');
        if (!$kw && !$acc && !$min && !$max) {
            throw new InvalidArgumentException('Cần ít nhất một điều kiện: tu_khoa, tai_khoan hoặc khoảng số tiền.');
        }

        $result = ['dieu_kien' => array_filter(['tu_khoa' => $kw, 'tai_khoan' => $acc, 'tu_ngay' => $from?->toDateString(), 'den_ngay' => $to?->toDateString(),
            'so_tien_tu' => $min, 'so_tien_den' => $max])];

        if ($src !== 'quy_ngan_hang') {
            $q = DB::table('acc_journal_entries')
                ->when($kw, fn ($q) => $q->where(fn ($q) => $q->where('description', 'like', $this->like($kw))->orWhere('doc_no', 'like', $this->like($kw))->orWhere('invoice_no', $kw)))
                ->when($acc, fn ($q) => $q->where(fn ($q) => $q->where('debit_account', 'like', $acc . '%')->orWhere('credit_account', 'like', $acc . '%')))
                ->when($from, fn ($q) => $q->where('posted_on', '>=', $from->toDateString()))
                ->when($to, fn ($q) => $q->where('posted_on', '<=', $to->toDateString()))
                ->when($min, fn ($q) => $q->where('amount', '>=', $min))
                ->when($max, fn ($q) => $q->where('amount', '<=', $max));
            $result['nhat_ky_chung'] = [
                'so_dong_khop' => (clone $q)->count(),
                'tong_tien' => (float) (clone $q)->sum('amount'),
                'dong' => $q->orderByDesc('posted_on')->limit(50)->get(['posted_on', 'doc_no', 'invoice_no', 'description', 'debit_account', 'credit_account', 'amount'])
                    ->map(fn ($r) => ['ngay' => $r->posted_on, 'chung_tu' => $r->doc_no, 'hoa_don' => $r->invoice_no, 'dien_giai' => $r->description,
                        'no' => $r->debit_account, 'co' => $r->credit_account, 'so_tien' => (float) $r->amount])->all(),
            ];
        }
        if ($src !== 'nhat_ky') {
            $q = DB::table('acc_cash_entries')->whereNotNull('posted_on')
                ->when($kw, fn ($q) => $q->where(fn ($q) => $q->where('description', 'like', $this->like($kw))->orWhere('doc_no', 'like', $this->like($kw))
                    ->orWhere('car', $kw)->orWhere('segment', $kw)->orWhere('item_name', 'like', $this->like($kw))))
                ->when($acc, fn ($q) => $q->where('contra_account', 'like', $acc . '%'))
                ->when($from, fn ($q) => $q->where('posted_on', '>=', $from->toDateString()))
                ->when($to, fn ($q) => $q->where('posted_on', '<=', $to->toDateString()))
                ->when($min, fn ($q) => $q->whereRaw('GREATEST(receipt, payment) >= ?', [$min]))
                ->when($max, fn ($q) => $q->whereRaw('GREATEST(receipt, payment) <= ?', [$max]));
            $result['so_quy_ngan_hang'] = [
                'so_dong_khop' => (clone $q)->count(),
                'tong_thu' => (float) (clone $q)->sum('receipt'),
                'tong_chi' => (float) (clone $q)->sum('payment'),
                'dong' => $q->orderByDesc('posted_on')->limit(50)->get(['book', 'posted_on', 'doc_no', 'description', 'contra_account', 'receipt', 'payment', 'segment', 'car'])
                    ->map(fn ($r) => array_filter(['so' => self::BOOKS[$r->book] ?? $r->book, 'ngay' => $r->posted_on, 'chung_tu' => $r->doc_no, 'dien_giai' => $r->description,
                        'tk_doi_ung' => $r->contra_account, 'thu' => (float) $r->receipt, 'chi' => (float) $r->payment, 'khoan_muc' => $r->segment, 'xe' => $r->car],
                        fn ($v) => $v !== null && $v !== 0.0))->all(),
            ];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // so_quy_ngan_hang
    // ------------------------------------------------------------------

    private function cashBooks(string $book, Carbon $from, Carbon $to): array
    {
        $this->checkRange($from, $to, 1830);
        $books = $book === 'TAT_CA' ? array_keys(self::BOOKS) : [$book];
        if (array_diff($books, array_keys(self::BOOKS))) {
            throw new InvalidArgumentException('so phải là TIEN_MAT, HDB, VCB, AGRI hoặc TAT_CA.');
        }
        $f = $from->toDateString();
        $t = $to->toDateString();

        $out = [];
        foreach ($books as $b) {
            $q = fn () => DB::table('acc_cash_entries')->where('book', $b)->whereBetween('posted_on', [$f, $t]);
            $months = $q()->groupByRaw("DATE_FORMAT(posted_on,'%Y-%m')")->orderByRaw('1')
                ->selectRaw("DATE_FORMAT(posted_on,'%Y-%m') thang, ROUND(SUM(receipt)) thu, ROUND(SUM(payment)) chi")->get();
            $item = [
                'so' => self::BOOKS[$b],
                'so_du_dau' => $this->balanceBefore($b, $f),
                'tong_thu' => (float) $months->sum('thu'),
                'tong_chi' => (float) $months->sum('chi'),
                'so_du_cuoi' => $this->balanceAt($b, $t),
                'theo_thang' => $months->map(fn ($r) => ['thang' => $r->thang, 'thu' => (float) $r->thu, 'chi' => (float) $r->chi])->all(),
                'chi_lon_nhat' => $q()->where('payment', '>', 0)->orderByDesc('payment')->limit(5)->get(['posted_on', 'description', 'payment'])
                    ->map(fn ($r) => ['ngay' => $r->posted_on, 'dien_giai' => $r->description, 'chi' => (float) $r->payment])->all(),
            ];
            if ($b === 'TIEN_MAT') {
                $group = fn (string $col) => $q()->whereNotNull($col)->groupBy($col)->selectRaw("{$col} k, ROUND(SUM(receipt)) thu, ROUND(SUM(payment)) chi")
                    ->orderByDesc('chi')->get()->map(fn ($r) => ['ma' => $r->k, 'thu' => (float) $r->thu, 'chi' => (float) $r->chi])->all();
                $item['theo_khoan_muc'] = $group('segment');
                $item['theo_xe'] = $group('car');
                $item['theo_muc_thu_chi'] = $group('item_name');
                $item['ghi_chu'] = 'Khoản mục XQ / CT / VP và mã xe chỉ có ở sổ tiền mặt từ năm 2025 (MISA). Sổ ngân hàng không chia theo khoản mục.';
            }
            if ($item['so_du_cuoi'] === null) {
                $item['ghi_chu_so_du'] = 'Sổ kỳ này không có cột tồn (mẫu 2023), không tính được số dư cuối.';
            }
            $out[] = $item;
        }

        return ['tu_ngay' => $f, 'den_ngay' => $t, 'nguon' => 'Sổ quỹ / sổ tiền gửi MISA', 'so' => $out];
    }

    /** Tồn trước ngày $date: dòng có cột tồn gần nhất trước ngày đó, hoặc dòng số dư đầu kỳ của kỳ bắt đầu từ ngày đó */
    private function balanceBefore(string $book, string $date): ?float
    {
        $last = DB::table('acc_cash_entries')->where('book', $book)->where('posted_on', '<', $date)->whereNotNull('balance')
            ->orderByDesc('posted_on')->orderByDesc('id')->value('balance');
        if ($last !== null) {
            return (float) $last;
        }
        $opening = DB::table('acc_cash_entries')->join('acc_imports', 'acc_imports.id', '=', 'acc_cash_entries.import_id')
            ->where('acc_cash_entries.book', $book)->where('is_opening', true)->where('acc_imports.period_from', '<=', $date)
            ->orderByDesc('acc_imports.period_from')->value('acc_cash_entries.balance');

        return $opening !== null ? (float) $opening : null;
    }

    private function balanceAt(string $book, string $date): ?float
    {
        $v = DB::table('acc_cash_entries')->where('book', $book)->where('posted_on', '<=', $date)->whereNotNull('balance')
            ->orderByDesc('posted_on')->orderByDesc('id')->value('balance');

        return $v !== null ? (float) $v : null;
    }

    // ------------------------------------------------------------------
    // cong_no_doi_tac
    // ------------------------------------------------------------------

    private function partyBalances(string $type, ?int $year, ?string $keyword): array
    {
        $kind = ['phai_thu' => 'receivable', 'phai_tra' => 'payable'][$type] ?? throw new InvalidArgumentException('loai phải là phai_thu hoặc phai_tra.');
        $periodQ = DB::table('acc_party_balances')->where('kind', $kind)
            ->when($year, fn ($q) => $q->whereYear('period_to', '>=', $year)->whereYear('period_from', '<=', $year));
        $period = $periodQ->orderByDesc('period_to')->first(['period_from', 'period_to']);
        if (!$period) {
            return ['ket_qua' => 'Chưa có số liệu công nợ ' . ($year ? "năm {$year}" : '') . ' trong sổ sách đã nhập.'];
        }
        $rows = DB::table('acc_party_balances')->where('kind', $kind)->where('period_from', $period->period_from)->where('period_to', $period->period_to)
            ->when($keyword, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', $this->like($keyword))->orWhere('code', 'like', $this->like($keyword))))
            ->get();
        $closing = fn ($r) => $kind === 'receivable' ? $r->closing_debit - $r->closing_credit : $r->closing_credit - $r->closing_debit;
        $sorted = $rows->sortByDesc($closing)->values();

        return [
            'loai' => $type === 'phai_thu' ? 'Phải thu khách hàng (131)' : 'Phải trả nhà cung cấp (331)',
            'ky' => "{$period->period_from} → {$period->period_to}",
            'nguon' => 'Tổng hợp công nợ MISA',
            'tong_so_du_cuoi' => round($rows->sum($closing)),
            'so_doi_tac' => $rows->count(),
            'doi_tac' => $sorted->take(40)->map(fn ($r) => [
                'ma' => $r->code, 'ten' => $r->name,
                'du_dau' => round($kind === 'receivable' ? $r->opening_debit - $r->opening_credit : $r->opening_credit - $r->opening_debit),
                'phat_sinh_no' => round($r->debit), 'phat_sinh_co' => round($r->credit), 'du_cuoi' => round($closing($r)),
            ])->all(),
            'ghi_chu' => $rows->count() > 40 ? 'Chỉ liệt kê 40 đối tác số dư lớn nhất.' : null,
        ];
    }

    // ------------------------------------------------------------------
    // luong_thuc_tra
    // ------------------------------------------------------------------

    private function salaries(int $fromYear, int $toYear): array
    {
        if ($fromYear < 2000 || $toYear < $fromYear || $toYear - $fromYear > 10) {
            throw new InvalidArgumentException('tu_nam / den_nam không hợp lệ.');
        }
        $rows = DB::table('acc_salaries')->whereBetween('year', [$fromYear, $toYear])->orderBy('year')->orderByRaw('month IS NULL, month')->get();
        $years = $rows->groupBy('year')->map(fn (Collection $g, $y) => [
            'nam' => (int) $y,
            'theo_thang' => $g->where('kind', 'luong')->map(fn ($r) => ['thang' => (int) $r->month, 'ct' => (float) $r->ct, 'xq' => (float) $r->xq, 'cong' => (float) ($r->ct + $r->xq)])->values()->all(),
            'thuong' => $g->where('kind', '!=', 'luong')->map(fn ($r) => ['loai' => $r->kind === 'thuong_le' ? 'Thưởng lễ' : 'Thưởng Tết', 'ct' => (float) $r->ct, 'xq' => (float) $r->xq])->values()->all(),
            'tong_ct' => (float) $g->sum('ct'), 'tong_xq' => (float) $g->sum('xq'), 'tong_cong' => (float) ($g->sum('ct') + $g->sum('xq')),
        ])->values()->all();

        return ['nguon' => 'File lương thực trả của kế toán (tổng theo mảng CT / XQ)', 'nam' => $years];
    }

    // ------------------------------------------------------------------
    // cong_no_web
    // ------------------------------------------------------------------

    private function webDebts(?string $keyword, ?int $year): array
    {
        $today = Carbon::today();
        $base = DB::table('accountants')
            ->join('orders', 'orders.id', '=', 'accountants.order_id')
            ->join('order_details', 'order_details.id', '=', 'orders.order_detail_id')
            ->leftJoin('units', 'units.id', '=', 'orders.unit_id')
            ->where('orders.status_id', '!=', 0)
            ->where('accountants.accountant_owe', '>', 0)
            ->when($year, fn ($q) => $q->whereYear('order_details.ord_start_day', $year))
            ->when($keyword, fn ($q) => $q->where(fn ($q) => $q->where('units.unit_abbreviation', 'like', $this->like($keyword))
                ->orWhere('units.unit_name', 'like', $this->like($keyword))->orWhere('units.unit_code', 'like', $this->like($keyword))));

        $rows = $base->select('orders.id', 'units.unit_abbreviation', 'order_details.ord_start_day', 'order_details.ord_cty_name', 'accountants.accountant_number',
            'accountants.accountant_date', 'accountants.accountant_deadline', 'accountants.accountant_owe', 'orders.order_price')->get()
            ->map(function ($r) use ($today) {
                $issued = $this->parseDate($r->accountant_date);
                $due = $issued && is_numeric($r->accountant_deadline) ? $issued->copy()->addDays((int) $r->accountant_deadline) : null;

                return [
                    'ma_don' => $r->id, 'don_vi' => $r->unit_abbreviation, 'ngay_chup' => $r->ord_start_day, 'cong_ty' => $r->ord_cty_name,
                    'so_hd' => $r->accountant_number, 'ngay_hd' => $issued?->toDateString(), 'han_tt' => $due?->toDateString(),
                    'thanh_tien' => (float) $r->order_price, 'con_no' => (float) $r->accountant_owe,
                    'qua_han_ngay' => $due && $due->lt($today) ? $due->diffInDays($today) : 0,
                    'chua_xuat_hd' => !$issued,
                ];
            });

        $summary = $rows->groupBy('don_vi')->map(fn ($g, $u) => [
            'don_vi' => $u ?: '(chưa có đơn vị)', 'tong_no' => $g->sum('con_no'), 'qua_han' => $g->where('qua_han_ngay', '>', 0)->sum('con_no'),
            'chua_xuat_hd' => $g->where('chua_xuat_hd', true)->sum('con_no'), 'so_don' => $g->count(), 'qua_han_lau_nhat_ngay' => (int) $g->max('qua_han_ngay'),
        ])->sortByDesc('tong_no')->values();

        return [
            'ngay_tinh' => $today->toDateString(),
            'nguon' => 'Trang Công nợ trên web Medicen (mảng XQ, cập nhật liên tục)',
            'tong_con_no' => $rows->sum('con_no'),
            'tong_qua_han' => $rows->where('qua_han_ngay', '>', 0)->sum('con_no'),
            'tong_chua_xuat_hd' => $rows->where('chua_xuat_hd', true)->sum('con_no'),
            'theo_don_vi' => $summary->take(30)->all(),
            'hoa_don' => $keyword ? $rows->sortByDesc('qua_han_ngay')->take(40)->values()->all() : null,
        ];
    }

    // ------------------------------------------------------------------
    // thu_chi_web
    // ------------------------------------------------------------------

    private function webCashPlan(?int $year, ?int $month): array
    {
        $q = DB::table('report_items')->join('reports', 'reports.id', '=', 'report_items.report_id');
        if ($year && $month) {
            $q->where('reports.year', $year)->where('reports.month', $month);
            $scope = sprintf('Tháng %02d/%d', $month, $year);
        } else {
            $q->whereColumn('report_items.actual_amount', '<', 'report_items.estimated_amount')
                ->whereNotNull('report_items.expected_date')->where('report_items.expected_date', '<=', Carbon::today()->addDays(30)->toDateString());
            $scope = 'Các khoản chưa thu / chi đủ, đến hạn trong 30 ngày tới (kể cả đã quá hạn)';
        }
        $items = $q->orderBy('report_items.expected_date')->get(['reports.month', 'reports.year', 'report_items.type', 'report_items.summary_group',
            'report_items.name', 'report_items.expected_date', 'report_items.estimated_amount', 'report_items.actual_amount', 'report_items.actual_date']);

        return [
            'pham_vi' => $scope,
            'nguon' => 'Trang Báo cáo thu chi trên web Medicen',
            'tong_thu_du_kien' => (float) $items->where('type', 'thu')->sum('estimated_amount'),
            'tong_thu_thuc_te' => (float) $items->where('type', 'thu')->sum('actual_amount'),
            'tong_chi_du_kien' => (float) $items->where('type', 'chi')->sum('estimated_amount'),
            'tong_chi_thuc_te' => (float) $items->where('type', 'chi')->sum('actual_amount'),
            'khoan' => $items->take(80)->map(fn ($r) => ['ky' => sprintf('%02d/%d', $r->month, $r->year), 'loai' => $r->type, 'nhom' => $r->summary_group, 'ten' => $r->name,
                'han' => $r->expected_date, 'du_kien' => (float) $r->estimated_amount, 'thuc_te' => (float) $r->actual_amount, 'ngay_thuc_te' => $r->actual_date])->all(),
        ];
    }

    // ------------------------------------------------------------------
    // tai_lieu
    // ------------------------------------------------------------------

    private function documents(string $type, ?string $keyword): array
    {
        $docs = DB::table('acc_documents')
            ->when(in_array($type, ['bctc', 'hop_dong'], true), fn ($q) => $q->where('type', $type))
            ->when($keyword, fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', $this->like($keyword))->orWhere('parties', 'like', $this->like($keyword))
                ->orWhere('period', 'like', $this->like($keyword))->orWhere('summary', 'like', $this->like($keyword))))
            ->orderBy('type')->orderByDesc('signed_on')->orderBy('title')->limit(30)->get();
        // Nội dung BCTC dài (cả bảng cân đối): nhiều tài liệu khớp thì chỉ trả danh sách, để agent hỏi lại cụ thể hơn
        $full = $docs->count() <= 3;

        return [
            'so_tai_lieu' => $docs->count(),
            'huong_dan' => $full ? null : 'Nhiều tài liệu khớp: chỉ liệt kê tên. Gọi lại với tu_khoa cụ thể hơn (vd tên tài liệu, năm, tên đối tác) để xem nội dung.',
            'tai_lieu' => $docs->map(fn ($d) => array_filter(['loai' => $d->type === 'bctc' ? 'Báo cáo tài chính' : 'Hợp đồng', 'ten' => $d->title, 'ky' => $d->period,
                'cac_ben' => $d->parties, 'ngay_ky' => $d->signed_on, 'hieu_luc_tu' => $d->valid_from, 'hieu_luc_den' => $d->valid_to,
                'gia_tri' => $d->value !== null ? (float) $d->value : null, 'noi_dung' => $full ? $d->summary : null], fn ($v) => $v !== null && $v !== ''))->all(),
            'ghi_chu' => $docs->isEmpty() ? 'Chưa có tài liệu khớp.' : null,
        ];
    }

    // ------------------------------------------------------------------
    // Hỗ trợ
    // ------------------------------------------------------------------

    private function accountName(string $acc): string
    {
        for ($n = strlen($acc); $n >= 3; $n--) {
            if (isset(self::ACCOUNT_NAMES[substr($acc, 0, $n)])) {
                return self::ACCOUNT_NAMES[substr($acc, 0, $n)];
            }
        }

        return '';
    }

    private function account(array $input): string
    {
        $acc = trim((string) ($input['tai_khoan'] ?? ''));
        if (!preg_match('/^\d{3,6}$/', $acc)) {
            throw new InvalidArgumentException('tai_khoan phải là đầu số tài khoản 3–6 chữ số, vd 642, 6427, 131.');
        }

        return $acc;
    }

    private function date(array $input, string $key): Carbon
    {
        $v = (string) ($input[$key] ?? '');
        $d = $this->parseDate($v);
        if (!$d) {
            throw new InvalidArgumentException("Tham số {$key} phải có dạng YYYY-MM-DD.");
        }

        return $d;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function checkRange(Carbon $from, Carbon $to, int $maxDays): void
    {
        if ($to->lt($from)) {
            throw new InvalidArgumentException('den_ngay phải sau tu_ngay.');
        }
        if ($from->diffInDays($to) > $maxDays) {
            throw new InvalidArgumentException("Khoảng ngày tối đa {$maxDays} ngày, hãy chia nhỏ.");
        }
    }

    private function optString(array $input, string $key): ?string
    {
        $v = trim((string) ($input[$key] ?? ''));
        if (mb_strlen($v) > 100) {
            throw new InvalidArgumentException("{$key} quá dài.");
        }

        return $v === '' ? null : $v;
    }

    private function like(string $s): string
    {
        return '%' . str_replace(['%', '_'], ['\%', '\_'], $s) . '%';
    }
}
