<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TechnicianWorkloadExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
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

    public function headings(): array
    {
        return [
            'Tên Nhân Viên / Kỹ Thuật Viên',
            'Tổng Số Lượng',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Làm đậm hàng đầu tiên (Header)
            1    => ['font' => ['bold' => true, 'size' => 12]],
            // Kẻ bảng cho toàn bộ dữ liệu
            'A1:B' . ($this->data->count() + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}