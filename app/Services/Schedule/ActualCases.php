<?php

namespace App\Services\Schedule;

use App\Models\CarKTV;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PerformanceAnalysis;
use App\Models\User;
use Carbon\Carbon;

/**
 * Ghi số ca thực tế sau khi chụp (chấm công): số ca KTV chụp, tài xế phụ KTV, công tác trên 250km, ghi chú KTV.
 * Dùng chung cho trang lịch KTV (ScheduleTechniciansController::update) và trang Xếp lịch xe,
 * nên cả hai cùng ghi dữ liệu và cùng tính KPI phần 2 như nhau.
 */
class ActualCases
{
    /**
     * @param array{order_quantity_draft: mixed, order_note_ktv: mixed, driver_assistance: mixed, work_over_250: mixed} $data
     */
    public function save(Order $order, array $data, ?User $user): void
    {
        // 1. Cập nhật Order
        $order->order_quantity_draft = $data['order_quantity_draft'];
        $order->order_note_ktv = $data['order_note_ktv'];
        $order->save();

        // 2. Cập nhật CarKTV
        $carKTV = CarKTV::where('order_id', $order->id)->first();
        if ($carKTV) {
            $carKTV->driver_assistance = $data['driver_assistance'];
            $carKTV->work_over_250 = $data['work_over_250'];
            $carKTV->save();
        }

        // 3. KPI phần 2: lần đầu người dùng (không phải Admin) nhập cho đơn này
        if ($user && $user->role != 0) {
            $kpiExists = PerformanceAnalysis::where('order_id', $order->id)
                ->where('part', 2)
                ->where('user_id', $user->id)
                ->exists();

            if (!$kpiExists) {
                $ordStartDay = OrderDetail::where('id', $order->order_detail_id)->value('ord_start_day');

                $now = Carbon::now()->startOfDay();
                $dateEqual = Carbon::parse($ordStartDay)->startOfDay();

                if ($now->lte($dateEqual)) {
                    $performanceScore = 100;
                    $description = 'Completed ahead of schedule';
                    $status = 1;
                } else {
                    $performanceScore = 0;
                    $description = 'Missed deadline';
                    $status = 0;
                }

                $performanceAnalysis = new PerformanceAnalysis();
                $performanceAnalysis->order_id = $order->id;
                $performanceAnalysis->user_id = $user->id;
                $performanceAnalysis->part = 2;
                $performanceAnalysis->performance = $performanceScore;
                $performanceAnalysis->description = $description;
                $performanceAnalysis->first_edit_time = $order->updated_at;
                $performanceAnalysis->status = $status;
                $performanceAnalysis->save();
            }
        }
    }
}
