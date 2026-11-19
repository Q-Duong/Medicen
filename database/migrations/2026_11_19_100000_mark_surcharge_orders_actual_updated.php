<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Đơn phụ thu không qua bộ phận Kết quả nên trước đây kẹt ở "Đang xử lý" / "Chưa lên lịch".
 * Từ nay lên đơn phụ thu là "Đã cập nhật số ca thực tế" (Order::booted); migration này chuyển các đơn cũ.
 * down(): không hoàn tác (không biết trạng thái cũ của từng đơn).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('order_surcharge', 1)
            ->whereIn('status_id', [0, 1, 5])
            ->update(['status_id' => 2, 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
