<?php

namespace App\Models;

use App\Builders\OrderBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $appends = ['edit_url'];

    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'order_detail_id',
        'order_group_id',
        'unit_id',
        'status_id',
        'order_vat',
        'order_quantity',
        'order_quantity_draft',
        'order_note_ktv',
        'order_cost',
        'order_price',
        'order_percent_discount',
        'order_discount',
        'order_profit',
        'schedule_status',
        'order_warning',
        'accountant_updated',
        'order_all_in_one',
        'order_child',
        'order_surcharge',
        'order_updated',
        'order_send_result',
        'overnight',
        'complete'
    ];

    /** Đơn đã hoàn thành (kế toán đã chốt): khóa hết, không sửa đơn / lịch / chấm công / kết quả / công nợ nữa */
    public const STATUS_CLOSED = 3;

    /** Đã có số ca thực tế (2) / đã cập nhật doanh thu (4): form đơn khóa số lượng + hình thức in phim */
    public const QTY_LOCKED_STATUSES = [2, 4];

    /** Các ô (order_details) khóa cùng số lượng khi đã có số ca thực tế (cùng order_warning ở orders) */
    public const PRINT_LOCKED_FIELDS = ['ord_doctor_read', 'ord_film', 'ord_form', 'ord_print', 'ord_form_print', 'ord_print_result', 'ord_film_sheet'];

    public function isClosed(): bool
    {
        return (int) $this->status_id === self::STATUS_CLOSED;
    }

    /** Thông báo khi đơn đã hoàn thành (null nếu đơn còn sửa được) */
    public static function closedMessage($orderId): ?string
    {
        $status = static::whereKey($orderId)->value('status_id');

        return (int) $status === self::STATUS_CLOSED
            ? 'Đơn #' . $orderId . ' đã hoàn thành (kế toán đã chốt), không còn chỉnh sửa được.'
            : null;
    }

    public function getEditUrlAttribute()
    {
        if (empty($this->order_id)) {
            return '#';
        }
    
        return route('order.edit', ['id' => $this->order_id]);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function group()
    {
        return $this->belongsTo(OrderGroup::class, 'order_group_id');
    }

    public function carKTV()
    {
        $this->hasMany(CarKTV::class);
    }

    public function accountant()
    {
        $this->hasMany(Accountant::class);
    }

    public function newEloquentBuilder($query): OrderBuilder
    {
        return new OrderBuilder($query);
    }
}
