<?php

namespace App\Repositories;

use App\Models\Accountant;
use App\Models\Report;
use App\Models\ReportItem;
use Illuminate\Support\Facades\DB;

class ReportRepository implements ReportRepositoryInterface
{
    public function getReportByPeriod(int $month, int $year)
    {
        $report = Report::firstOrCreate(
            ['month' => $month, 'year' => $year]
        );
        // if ($report->wasRecentlyCreated || $report->items()->count() === 0) {
        //     $this->seedDefaultItems($report);
        // }

        return $report->load(['items' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }]);
    }

    public function updateReportItems(int $reportId, array $itemsData)
    {
        DB::transaction(function () use ($reportId, $itemsData) {
            foreach ($itemsData as $item) {
                
                // 1. NẾU CÓ LỆNH XÓA TỪ GIAO DIỆN
                if (isset($item['_delete']) && $item['_delete'] == '1') {
                    if (!empty($item['id'])) {
                        ReportItem::where('id', $item['id'])->where('report_id', $reportId)->delete();
                    }
                    continue; // Xóa xong thì bỏ qua, không chạy code Cập nhật bên dưới nữa
                }

                // 2. NẾU LÀ CẬP NHẬT DÒNG CŨ
                if (!empty($item['id'])) {
                    ReportItem::where('id', $item['id'])
                              ->where('report_id', $reportId)
                              ->update([
                                  'name'             => $item['name'] ?? DB::raw('name'),
                                  'estimated_amount' => $item['estimated_amount'] ?? 0,
                                  'actual_amount'    => $item['actual_amount'] ?? 0,
                                  'expected_date'    => $item['expected_date'] ?? null,
                                  'actual_date'      => $item['actual_date'] ?? null,
                              ]);
                } 
                // 3. NẾU LÀ THÊM DÒNG MỚI
                else {
                    if (!empty($item['name'])) { 
                        ReportItem::create([
                            'report_id'        => $reportId,
                            'type'             => $item['type'],
                            'summary_group'    => $item['summary_group'],
                            'name'             => $item['name'],
                            'estimated_amount' => $item['estimated_amount'] ?? 0,
                            'actual_amount'    => $item['actual_amount'] ?? 0,
                            'expected_date'    => $item['expected_date'] ?? null,
                            'actual_date'      => $item['actual_date'] ?? null,
                        ]);
                    }
                }
            }
        });
        
        return Report::with('items')->find($reportId);
    }

    // Tự động tạo khung các mục và gán Ngày Dự Kiến + Số Tiền cố định
    public function seedDefaultItems(Report $report)
    {
        $year = $report->year;
        $month = $report->month;

        // Hàm hỗ trợ tự động tạo ngày (VD: nhập 30 vào tháng 2 nó sẽ tự lấy ngày 28/29)
        $getDate = function ($day) use ($year, $month) {
            $date = \Carbon\Carbon::create($year, $month, 1);
            $maxDay = $date->daysInMonth;
            return $date->day(min($day, $maxDay))->format('Y-m-d');
        };

        $defaults = [
            // --- NHÓM THU ---
            ['type' => 'thu', 'summary_group' => 'Tiền hiện có (SDĐK)', 'name' => '- Tiền hiện có (VCB chú Lộc)', 'estimated_amount' => 617000000, 'expected_date' => null],
            ['type' => 'thu', 'summary_group' => 'Tiền hiện có (SDĐK)', 'name' => '- Tiền hiện có (Bank Cty)', 'estimated_amount' => 46000000, 'expected_date' => null],

            ['type' => 'thu', 'summary_group' => 'CT', 'name' => '- CT Á Châu', 'estimated_amount' => 15000000, 'expected_date' => $getDate(10)],
            ['type' => 'thu', 'summary_group' => 'CT', 'name' => '- CT Á Châu (bhyt)', 'estimated_amount' => 50000000, 'expected_date' => $getDate(10)],
            ['type' => 'thu', 'summary_group' => 'CT', 'name' => '- CT Bảo Lộc', 'estimated_amount' => 66000000, 'expected_date' => $getDate(12)],
            ['type' => 'thu', 'summary_group' => 'CT', 'name' => '- CT Cà Mau', 'estimated_amount' => 29000000, 'expected_date' => $getDate(12)],
            ['type' => 'thu', 'summary_group' => 'CT', 'name' => '- CT Gò Công', 'estimated_amount' => 117000000, 'expected_date' => $getDate(12)],
            ['type' => 'thu', 'summary_group' => 'CT', 'name' => '- CT Lagi', 'estimated_amount' => 78000000, 'expected_date' => $getDate(12)],

            ['type' => 'thu', 'summary_group' => 'XQ (số dự thu trong tháng)', 'name' => '- XQ YD1+2', 'estimated_amount' => 156000000, 'expected_date' => $getDate(30)],
            ['type' => 'thu', 'summary_group' => 'XQ (số dự thu trong tháng)', 'name' => '- XQ KH lẻ (ước tính)', 'estimated_amount' => 200000000, 'expected_date' => $getDate(30)],

            ['type' => 'thu', 'summary_group' => 'Thu chuyển nhượng', 'name' => '- Thu ban xe VFe34', 'estimated_amount' => 0, 'expected_date' => null],
            ['type' => 'thu', 'summary_group' => 'Thu chuyển nhượng', 'name' => '- Thu góp vốn Cà Mau', 'estimated_amount' => 0, 'expected_date' => null],
            ['type' => 'thu', 'summary_group' => 'Thu chuyển nhượng', 'name' => '- Thu bán máy Tín Nghĩa', 'estimated_amount' => 160000000, 'expected_date' => null],

            // --- NHÓM CHI ---
            ['type' => 'chi', 'summary_group' => 'Chi tiền vay Bank', 'name' => 'HDB (12 tây)', 'estimated_amount' => 260000000, 'expected_date' => $getDate(12)],
            ['type' => 'chi', 'summary_group' => 'Chi tiền vay Bank', 'name' => 'Chailease+HDB (20 tây)', 'estimated_amount' => 140000000, 'expected_date' => $getDate(20)],
            ['type' => 'chi', 'summary_group' => 'Chi tiền vay Bank', 'name' => 'Đáo hạn HDB (vay lại lương) 05/07', 'estimated_amount' => 340000000, 'expected_date' => $getDate(5)],
            ['type' => 'chi', 'summary_group' => 'Chi tiền vay Bank', 'name' => 'Đáo hạn HDB (20/7)', 'estimated_amount' => 280000000, 'expected_date' => $getDate(20)],
            ['type' => 'chi', 'summary_group' => 'Chi tiền vay Bank', 'name' => 'VCB chú Lộc', 'estimated_amount' => 35000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi tiền vay Bank', 'name' => 'Vietbank cô Hồng', 'estimated_amount' => 10000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi tiền vay Bank', 'name' => 'Vay Shinhan mua VF7', 'estimated_amount' => 14000000, 'expected_date' => null],

            ['type' => 'chi', 'summary_group' => 'Chi DV', 'name' => '- Đóng tiền điện+nước', 'estimated_amount' => 6000000, 'expected_date' => null],

            ['type' => 'chi', 'summary_group' => 'Chi cố định', 'name' => '- Tiền thuê nhà', 'estimated_amount' => 15000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi cố định', 'name' => '- Chi lương NV + Bs đọc CT tháng 5', 'estimated_amount' => 158000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi cố định', 'name' => '- Chi lương NV + Bs đọc CT tháng 6', 'estimated_amount' => 300000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi cố định', 'name' => '- Nộp BHXH', 'estimated_amount' => 30720000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi cố định', 'name' => '- Phí bảo trì may CT', 'estimated_amount' => 8000000, 'expected_date' => null],

            ['type' => 'chi', 'summary_group' => 'Chi nộp thuế', 'name' => 'Nợ thuế Q4/2025 (TC : 260 triệu)', 'estimated_amount' => 260000000, 'expected_date' => null],

            ['type' => 'chi', 'summary_group' => 'Chi trả NCC', 'name' => '- Trả nợ film XQ', 'estimated_amount' => 120000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi trả NCC', 'name' => '- Phí PM pacs đợt cuối 2025', 'estimated_amount' => 120000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi trả NCC', 'name' => '- Phí PM pacs 2026', 'estimated_amount' => 12000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi trả NCC', 'name' => '- Phí thuê máy SA', 'estimated_amount' => 0, 'expected_date' => null],

            ['type' => 'chi', 'summary_group' => 'Chi mượn cá nhân', 'name' => '- Tra no cu co Hồng', 'estimated_amount' => 250000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi mượn cá nhân', 'name' => '- Tra no cu co Linh', 'estimated_amount' => 121000000, 'expected_date' => null],

            ['type' => 'chi', 'summary_group' => 'Chi khác', 'name' => '- LN co dong CT', 'estimated_amount' => 85000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi khác', 'name' => '- Chi phí thuê TX+KTV', 'estimated_amount' => 10000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi khác', 'name' => '- Ứng chi TX', 'estimated_amount' => 23000000, 'expected_date' => null],
            ['type' => 'chi', 'summary_group' => 'Chi khác', 'name' => '- Chi HHXQ', 'estimated_amount' => 0, 'expected_date' => null],
        ];

        $insertData = array_map(function ($item) use ($report) {
            $item['report_id'] = $report->id;
            $item['actual_amount'] = 0;
            $item['created_at'] = now();
            $item['updated_at'] = now();
            return $item;
        }, $defaults);

        ReportItem::insert($insertData);
    }

    /**
     * Tự động lấy dữ liệu từ bảng Công nợ đắp vào Báo cáo Thu Chi
     */
    public function getAccountantStats($month, int $year)
    {
        $params = [
            'year'  => $year,
            'type'  => 'all'
        ];
        
        $baseQuery = Accountant::getAccountantByFilter($params);

        // if ($month !== 'all') {
        //     $baseQuery->where('accountants.accountant_month', $month);
        // }

        return $baseQuery->selectRaw('
            SUM(accountants.accountant_owe) as total_price,
            
            SUM(CASE 
                WHEN accountants.accountant_number IS NOT NULL AND accountants.accountant_number != "" 
                 AND accountants.accountant_date IS NOT NULL AND accountants.accountant_date != "" 
                THEN accountants.accountant_owe 
                ELSE 0 
            END) as total_da_xuat_hd,
            
            SUM(CASE 
                WHEN accountants.accountant_number IS NULL OR accountants.accountant_number = "" 
                  OR accountants.accountant_date IS NULL OR accountants.accountant_date = "" 
                THEN accountants.accountant_owe 
                ELSE 0 
            END) as total_chua_xuat_hd
        ')->first();
    }
}
