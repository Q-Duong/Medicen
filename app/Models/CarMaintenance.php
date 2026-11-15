<?php

namespace App\Models;

use App\Services\Schedule\ScheduleBoard;
use Illuminate\Database\Eloquent\Model;

/** Lịch xe nghỉ để bảo trì / đăng kiểm / sửa chữa: xe không được xếp trong thời gian này */
class CarMaintenance extends Model
{
    protected $fillable = ['car', 'maintenance_start', 'maintenance_end', 'maintenance_session', 'maintenance_note', 'created_by'];

    protected $casts = ['maintenance_start' => 'date', 'maintenance_end' => 'date'];

    /** "Xe 3 bảo trì: thay nhớt" */
    public function label(): string
    {
        return ScheduleBoard::carLabel($this->car) . ' bảo trì' . ($this->maintenance_note ? ": {$this->maintenance_note}" : '');
    }
}
