<?php

namespace App\Exports;

use App\Models\Accountant;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;

class ExcelExport implements WithHeadings, FromQuery, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return [
            'Mã đơn hàng',
            'Tháng',
            'Ngày chụp',
            'Loại',
            'Xe',
            'Km',
            'Mã đơn vị',
            'Đơn vị hợp tác',
            'Tên Cty',
            'THCN',
            'Số HH',
            'Ngày HĐ',
            'VAT',
            'Số lượng',
            'Đơn giá',
            'Phụ thu',
            'Thành tiền',
            'Thời hạn thanh toán',
            'Số ngày',
            'Ngày TT',
            'Hình thức',
            'Số tiền đã thanh toán',
            'Còn nợ',
            '%CK',
            'Thành tiền CK',
            'Ngày trích CK',
            'Lợi nhuận',
            'BS đọc kq',
            'NTT',
            'HT in Phim',
            '35 X 43',
            'Polime',
            '8 X 10',
            '10 X 12',
            'Bao phim',
            'Ghi chú',
            'Trạng thái',
        ];
    }

    public function query()
    {
		$query = Accountant::getAccountantByFilter($this->filters);

        return $query->select(
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
            'order_percent_discount',
            'order_discount',
            'order_profit',
            'orders.status_id',
            'car_name',
            'unit_name'
        )->orderBy('ord_start_day', 'ASC');
    }

    public function map($accountant): array
    {
        return [
            $accountant->order_id,
            $accountant->accountant_month,
            $accountant->ord_start_day != null ? date('d/m/Y', strtotime($accountant->ord_start_day)) : '',
            $accountant->ord_type == 1 ? "X-Quang" : "Siêu Âm",
            carRenameFunction($accountant->car_name),
            $accountant->accountant_distance,
            $accountant->unit_code,
            $accountant->unit_name,
            capitalizeWordsExceptAbbreviations($accountant->ord_cty_name),
            $accountant->accountant_deadline,
            $accountant->accountant_number,
            $accountant->accountant_date != null ? date('d/m/Y', strtotime($accountant->accountant_date)) : '',
            $accountant->order_vat,
            $accountant->order_quantity,
            $accountant->order_cost,
            $accountant->order_surcharge,
            $accountant->order_price,
            $accountant->accountant_payment != null ? date('d/m/Y', strtotime($accountant->accountant_payment)) : '',
            $accountant->accountant_day,
            $accountant->accountant_day_payment != null ? date('d/m/Y', strtotime($accountant->accountant_day_payment)) : '',
            $accountant->accountant_method,
            $accountant->accountant_amount_paid,
            $accountant->accountant_owe,
            $accountant->order_percent_discount,
            $accountant->order_discount,
            $accountant->accountant_discount_day != null ? date('d/m/Y', strtotime($accountant->accountant_discount_day)) : '',
            $accountant->order_profit,
            $accountant->accountant_doctor_read,
            $accountant->accountant_doctor_date_payment != null ? date('d/m/Y', strtotime($accountant->accountant_doctor_date_payment)) : '',
            $accountant->ord_form == 'PhimLon' ? 'IN35X43' : $accountant->ord_form,
            $accountant->accountant_35X43,
            $accountant->accountant_polime,
            $accountant->accountant_8X10,
            $accountant->accountant_10X12,
            $accountant->accountant_film_bag,
            $accountant->accountant_note,
            $accountant->status_name,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
