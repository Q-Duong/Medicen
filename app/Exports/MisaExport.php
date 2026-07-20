<?php

namespace App\Exports;

use App\Models\Accountant;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use DB;

class MisaExport implements WithHeadings, FromCollection, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return [
            ['FILE MẪU ĐƠN ĐẶT HÀNG ĐỂ NHẬP VÀO PHẦN MỀM AMIS ACCOUNTING'], // 1
            ['Hướng dẫn:'], // 2
            ['- Điền dữ liệu vào các cột tương ứng trên file này'], // 3
            ['- Các cột có dấu (*) là những cột bắt buộc'], // 4
            ['- Nếu muốn nhập nhiều thông tin hơn người dùng có thể tải mẫu đầy đủ/hoặc tự thêm cột trên mẫu cơ bản'], // 5
            ['- Các dòng dữ liệu phía dưới chỉ là ví dụ minh họa'], // 6
            ['', '', '', '', '', '', '', 'Chi tiết hàng tiền'], // 7
            [
                'Ngày đơn hàng (*)', // A
                'Số đơn hàng (*)',   // B
                'Tính giá thành',    // C
                'Mã khách hàng',     // D
                'Tên khách hàng',    // E
                'Địa chỉ',           // F
                'Mã số thuế',        // G
                'Mã hàng (*)',       // H
                'Tên hàng',          // I
                'Là dòng ghi chú',   // J
                'ĐVT',               // K
                'Số lượng',          // L
                'Đơn giá',           // M
                'Thành tiền'         // N
            ] // 8
        ];
    }

    public function collection()
    {
        $query = Accountant::getAccountantByFilter($this->filters);

        $accountants = $query->select(
            'accountants.id',
            'accountants.order_id',
            'accountants.accountant_month',
            'accountant_distance',
            'accountant_deadline',
            'accountant_number',
            'accountant_date',
            'accountant_payment',
            'accountant_day_payment',
            'accountant_method',
            'accountant_amount_paid',
            'accountant_owe',
            'accountant_discount_day',
            'accountant_doctor_read',
            'accountant_doctor_date_payment',
            'accountant_35X43',
            'accountant_polime',
            'accountant_8X10',
            'accountant_10X12',
            'accountant_film_bag',
            'accountant_note',
            'accountant_status',
            'ord_type',
            'ord_start_day',
            'ord_form',
            'ord_note',
            'ord_cty_name',
            'order_vat',
            'order_quantity',
            'order_cost',
            'order_price',
            'order_surcharge',
            'order_percent_discount',
            'order_discount',
            'order_profit',
            'orders.status_id',
            'order_all_in_one',
            'car_name',
            'unit_code', 
            'unit_name',
            'unit_tax_code'
        )->orderBy('ord_start_day', 'ASC')->get();

        $exportData = collect();

        if ($accountants->count() > 0) {
            
            $firstItem = $accountants->first();
            
            $ngayExport = date('d/m/Y'); 
            
            $soDonHangChung = $firstItem->order_id;
            
            $tenGhiChuChung = $firstItem->ord_type == 1 
                ? 'Dịch vụ cho thuê thiết bị y tế - Máy chụp Xquang (không kèm theo hoạt động khám chữa bệnh)'
                : 'Dịch vụ cho thuê thiết bị y tế - Máy Siêu Âm (không kèm theo hoạt động khám chữa bệnh)';

            // ==========================================
            // DÒNG GHI CHÚ CHUNG DUY NHẤT (MASTER)
            // ==========================================
            $exportData->push([
                $ngayExport,           // A
                $soDonHangChung,       // B
                'Không',               // C
                $firstItem->unit_code, // D
                '',                    // E
                '',                    // F
                $firstItem->unit_tax_code,// G
                '',                    // H
                $tenGhiChuChung,       // I
                'Có',                  // J
                '',                    // K
                '',                    // L
                '',                    // M
                ''                     // N
            ]);

            // ==========================================
            // CÁC DÒNG CHI TIẾT (DETAIL)
            // ==========================================
            foreach ($accountants as $item) {
                
                $ctyName = $item->ord_cty_name;
                $tenHangFormat = capitalizeWordsExceptAbbreviations($ctyName);

                if (stripos($ctyName, 'in thêm film') !== false) {
                    $maHang = 'ITP';
                } 
                // Ưu tiên 2: Nếu là Phụ thu
                elseif ($item->order_surcharge == 1) {
                    $maHang = 'PHUTHU';
                } 
                // Mặc định: X-Quang hoặc Siêu âm
                else {
                    $maHang = $item->ord_type == 1 ? 'DVXQ' : 'DVSA';
                }
                
                // Xử lý logic Đơn vị tính (ĐVT)
                $donViTinh = $item->order_all_in_one == 0 ? 'Ca' : 'Gói';
                
                if ($item->order_surcharge == 1) {
                    $donViTinhExport = ''; 
                    $soLuong = '';
                    $donGia = '';
                } elseif ($donViTinh == 'Gói') {
                    $donViTinhExport = 'Gói'; 
                    $soLuong = '';
                    $donGia = '';
                } else {
                    $donViTinhExport = 'Ca';
                    $soLuong = $item->order_quantity;
                    $donGia = $item->order_cost;
                }

                $exportData->push([
                    $ngayExport,                 // A
                    $soDonHangChung,             // B
                    'Không',                     // C
                    $item->unit_code,            // D
                    '',                          // E
                    '',                          // F
                    $item->unit_tax_code,        // G
                    $maHang,                     // H: Mã hàng 
                    $tenHangFormat,              // I: Tên hàng
                    'Không',                     // J
                    $donViTinhExport,            // K: ĐVT
                    $soLuong,                    // L: Số lượng
                    $donGia,                     // M: Đơn giá
                    $item->order_price           // N
                ]);
            }
        }

        return $exportData;
    }

    public function styles(Worksheet $sheet)
    {
        // Đổi font chữ mặc định sang Times New Roman và kích thước mặc định là 12
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman');
        $sheet->getParent()->getDefaultStyle()->getFont()->setSize(12);

        // Gộp ô cho chữ "Chi tiết hàng tiền"
        $sheet->mergeCells('H7:N7');
        
        // Gộp ô tiêu đề từ A1 đến D1 (Cho khớp với vùng tô màu)
        $sheet->mergeCells('A1:D1'); 
        
        // Định nghĩa style border chung cho bảng tiêu đề
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'], // Màu đen
                ],
            ],
        ];

        // Mã màu cam đậm
        $mauCamDam = 'FFFFCC99'; 

        return [
            // Dòng 1: Tiêu đề to (Size 16, In đậm)
            'A1:D1' => [
                'font' => ['bold' => true, 'size' => 16], // Đổi từ 14 thành 16
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $mauCamDam] 
                ]
            ],
            
            // Dòng 2: Hướng dẫn (Chữ đỏ)
            'A2:D2' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFF0000']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $mauCamDam]
                ]
            ],

            // Dòng 3,4,5,6: Nội dung hướng dẫn
            'A3:D6' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $mauCamDam]
                ]
            ],
            
            // Dòng 7: Cột H->N (Kẻ viền, Nền Xanh lá nhạt, In đậm, Căn giữa)
            'H7:N7' => array_merge([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFCCFFCC']
                ]
            ], $borderStyle),
            
            // Dòng 8: Tiêu đề A->G (Kẻ viền, Nền Xanh dương)
            'A8:G8' => array_merge([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFCCFFFF'] 
                ]
            ], $borderStyle),

            // Dòng 8: Tiêu đề H->N (Kẻ viền, Nền Xanh lá)
            'H8:N8' => array_merge([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFCCFFCC']
                ]
            ], $borderStyle),
        ];
    }
}