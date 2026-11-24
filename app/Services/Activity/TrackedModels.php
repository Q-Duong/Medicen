<?php

namespace App\Services\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Các model được theo dõi thay đổi "cũ → mới" cho nhật ký hoạt động (ModelChangeObserver gắn trong AppServiceProvider).
 * Mỗi model: tên đối tượng (vd "Đơn #2350"), các cột theo dõi kèm nhãn; cột 'sensitive' chỉ ghi "đã đổi" (không lưu giá trị).
 * Model đã có nhật ký riêng (trait RecordsActivity: trang kế toán, hợp đồng) không nằm ở đây.
 */
class TrackedModels
{
    public const STATUS = [0 => 'Đơn hàng mới', 1 => 'Đang xử lý', 2 => 'Đã cập nhật số ca thực tế', 3 => 'Đã xử lý (chốt)', 4 => 'Đã cập nhật doanh thu', 5 => 'Chưa lên lịch'];

    public static function config(): array
    {
        return [
            \App\Models\Order::class => [
                'subject' => fn (Model $m) => 'Đơn #' . $m->getKey(),
                'fields' => [
                    'unit_id' => 'Đơn vị', 'order_quantity' => 'Số ca', 'order_cost' => 'Đơn giá', 'order_price' => 'Thành tiền', 'order_all_in_one' => 'Trọn gói',
                    'order_vat' => 'VAT', 'order_percent_discount' => '% chiết khấu', 'order_discount' => 'Chiết khấu', 'order_surcharge' => 'Đơn phụ thu',
                    'status_id' => 'Trạng thái', 'complete' => 'Hoàn thành thông tin', 'order_group_id' => 'Nhóm đi chung', 'overnight' => 'Ở lại đêm',
                    'order_warning' => 'Cảnh báo đơn',
                ],
                'format' => ['unit_id' => 'unit', 'status_id' => 'status', 'order_all_in_one' => 'bool', 'order_surcharge' => 'bool', 'complete' => 'bool'],
            ],
            \App\Models\OrderDetail::class => [
                'subject' => fn (Model $m) => 'Đơn #' . (DB::table('orders')->where('order_detail_id', $m->getKey())->value('id') ?? ('chi tiết ' . $m->getKey())),
                'fields' => [
                    'ord_start_day' => 'Ngày chụp', 'ord_session' => 'Buổi', 'ord_time' => 'Giờ chụp', 'ord_cty_name' => 'Tên Cty', 'ord_select' => 'Dịch vụ',
                    'ord_doctor_read' => 'Bác sĩ đọc', 'ord_film' => 'In phim', 'ord_form' => 'Mẫu in', 'ord_print' => 'In phiếu', 'ord_note' => 'Ghi chú',
                    'ord_deliver_results' => 'Trả kết quả', 'ord_email' => 'Email nhận kết quả', 'ord_list_file' => 'File danh sách',
                ],
                'sensitive' => ['ord_email'],
            ],
            \App\Models\Customer::class => [
                'subject' => fn (Model $m) => 'Đơn #' . (DB::table('orders')->where('customer_id', $m->getKey())->value('id') ?? '?') . ' (người liên hệ)',
                'fields' => ['customer_name' => 'Người liên hệ', 'customer_phone' => 'SĐT liên hệ', 'customer_address' => 'Địa chỉ chụp', 'customer_location' => 'Tọa độ'],
                'sensitive' => ['customer_phone'],
            ],
            \App\Models\Accountant::class => [
                'subject' => fn (Model $m) => 'Đơn #' . $m->order_id . ' (công nợ)',
                'fields' => [
                    'accountant_number' => 'Số hóa đơn', 'accountant_date' => 'Ngày xuất HĐ', 'accountant_deadline' => 'Hạn thanh toán (ngày)',
                    'accountant_amount_paid' => 'Đã thanh toán', 'accountant_day_payment' => 'Ngày thanh toán', 'accountant_owe' => 'Còn nợ',
                    'accountant_status' => 'Đã thanh toán xong', 'accountant_distance' => 'Số km', 'accountant_method' => 'Hình thức TT', 'accountant_note' => 'Ghi chú công nợ',
                ],
                'format' => ['accountant_status' => 'bool'],
            ],
            \App\Models\CarKTV::class => [
                'subject' => fn (Model $m) => 'Đơn #' . $m->order_id . ' (lịch xe)',
                'fields' => [
                    'car_name' => 'Xe', 'car_driver_name' => 'Tài xế', 'car_ktv_name_1' => 'KTV 1', 'car_ktv_name_2' => 'KTV 2', 'car_active' => 'Lịch đang dùng',
                    'driver_assistance' => 'Tài xế phụ', 'work_over_250' => 'Đi xa trên 250 km',
                ],
                'format' => ['car_active' => 'bool', 'work_over_250' => 'bool'],
            ],
            \App\Models\Unit::class => [
                'subject' => fn (Model $m) => 'Đơn vị ' . ($m->unit_abbreviation ?: $m->unit_name ?: '#' . $m->getKey()),
                'fields' => ['unit_code' => 'Mã', 'unit_abbreviation' => 'Tên viết tắt', 'unit_name' => 'Tên đầy đủ', 'unit_tax_code' => 'MST', 'unit_address' => 'Địa chỉ'],
            ],
            \App\Models\Staff::class => [
                'subject' => fn (Model $m) => 'Nhân sự ' . ($m->staff_name ?: '#' . $m->getKey()),
                'fields' => ['staff_name' => 'Họ tên', 'staff_phone' => 'SĐT', 'staff_role' => 'Vai trò', 'staff_certificate' => 'CCHN', 'staff_birthday' => 'Ngày sinh'],
                'sensitive' => ['staff_phone', 'staff_birthday'],
            ],
            \App\Models\StaffLeave::class => [
                'subject' => fn (Model $m) => 'Ngày nghỉ của ' . (DB::table('staffs')->where('id', $m->staff_id)->value('staff_name') ?? 'nhân sự #' . $m->staff_id),
                'fields' => ['leave_start' => 'Từ', 'leave_end' => 'Đến', 'leave_session' => 'Buổi', 'leave_note' => 'Ghi chú'],
            ],
            \App\Models\CarMaintenance::class => [
                'subject' => fn (Model $m) => 'Bảo trì xe ' . $m->car,
                'fields' => ['car' => 'Xe', 'maintenance_start' => 'Từ', 'maintenance_end' => 'Đến', 'maintenance_session' => 'Buổi', 'maintenance_note' => 'Ghi chú'],
            ],
            \App\Models\ScheduleHold::class => [
                'subject' => fn (Model $m) => 'Giữ chỗ #' . $m->getKey() . ($m->customer ? ' (' . $m->customer . ')' : ''),
                'fields' => ['hold_start' => 'Từ', 'hold_end' => 'Đến', 'hold_session' => 'Buổi', 'car' => 'Xe', 'driver' => 'Tài xế', 'ktv1' => 'KTV 1', 'ktv2' => 'KTV 2',
                    'customer' => 'Khách', 'expected_cas' => 'Số ca dự kiến', 'status' => 'Trạng thái', 'order_id' => 'Đơn'],
            ],
            \App\Models\Task::class => [
                'subject' => fn (Model $m) => 'Nhiệm vụ "' . mb_strimwidth((string) $m->task_name, 0, 50, '…') . '"',
                'fields' => ['task_name' => 'Tên', 'task_progress' => 'Tiến độ', 'task_status' => 'Trạng thái', 'department' => 'Phòng ban', 'user_id' => 'Người làm'],
            ],
            \App\Models\User::class => [
                'subject' => fn (Model $m) => 'Tài khoản ' . ($m->email ?: '#' . $m->getKey()),
                'fields' => ['name' => 'Loại tài khoản', 'email' => 'Email', 'role' => 'Quyền', 'department' => 'Phòng ban', 'password' => 'Mật khẩu'],
                'sensitive' => ['password'],
                // Lưu lần đăng nhập / remember token… không ghi
                'only_fields' => true,
            ],
            \App\Models\ExcelUpload::class => [
                'subject' => fn (Model $m) => 'Danh sách Excel #' . $m->getKey(),
                'fields' => ['trang_thai' => 'Trạng thái', 'ly_do_tu_choi' => 'Lý do từ chối', 'order_id' => 'Đơn', 'ten_file' => 'File'],
            ],
            \App\Models\ProfitReport::class => [
                'subject' => fn (Model $m) => 'Phiếu lợi nhuận #' . $m->getKey(),
                'fields' => ['status' => 'Trạng thái', 'total_revenue' => 'Tổng thu', 'total_cost' => 'Tổng chi', 'net_profit' => 'Lợi nhuận ròng'],
            ],
        ];
    }

    /** Giá trị hiển thị cho nhật ký */
    public static function format(?string $kind, $v)
    {
        if ($v === null || $v === '') {
            return $v;
        }

        return match ($kind) {
            'unit' => DB::table('units')->where('id', $v)->value('unit_abbreviation') ?? $v,
            'status' => self::STATUS[(int) $v] ?? $v,
            'bool' => (int) $v ? 'Có' : 'Không',
            default => match (true) {
                $v instanceof \DateTimeInterface => $v->format('d/m/Y'),
                $v instanceof \UnitEnum => method_exists($v, 'label') ? $v->label() : ($v instanceof \BackedEnum ? $v->value : $v->name),
                is_array($v) => json_encode($v, JSON_UNESCAPED_UNICODE),
                default => $v,
            },
        };
    }
}
