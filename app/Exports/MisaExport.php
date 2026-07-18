<?php

namespace App\Exports;

use App\Models\Accountant;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}