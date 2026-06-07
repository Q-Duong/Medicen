<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TechnicianWorkloadExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    /**
     * Định nghĩa các cột tiêu đề của file Excel
     */
    public function headings(): array
    {
        return [
            'NHÂN VIÊN',
            'TỔNG SỐ CA',
            'SỐ CA KTV',
            'SỐ CA TX PHỤ KTV',
            'Ở LẠI ĐÊM',
            'C.TÁC TRÊN 250KM',
        ];
    }

    public function map($row): array
    {
        return [
            $row['nhan_vien'],
            $row['tong_so_ca'] > 0 ? $row['tong_so_ca'] : '-',
            $row['so_ca_ktv'] > 0 ? $row['so_ca_ktv'] : '-',
            $row['so_ca_tx_phu_ktv'] > 0 ? $row['so_ca_tx_phu_ktv'] : '-',
            $row['o_lai_dem'] > 0 ? $row['o_lai_dem'] : '-',
            $row['cong_tac_tren_250'] > 0 ? $row['cong_tac_tren_250'] : '-',
        ];
    }

    /**
     * Tùy chỉnh Style (In đậm hàng tiêu đề)
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Định dạng hàng 1 (Headers) là in đậm, nền xám nhạt (tùy chọn)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}