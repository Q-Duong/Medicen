<?php

namespace App\Services\Activity;

use Illuminate\Database\Eloquent\Model;

/** Đẩy thay đổi của các model trong TrackedModels vào ActivityRecorder (chỉ khi đang xử lý request có người đăng nhập) */
class ModelChangeObserver
{
    public function created(Model $m): void
    {
        $this->push($m, 'them');
    }

    public function updated(Model $m): void
    {
        $this->push($m, 'sua');
    }

    public function deleted(Model $m): void
    {
        $this->push($m, 'xoa');
    }

    private function push(Model $m, string $action): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }
        app(ActivityRecorder::class)->add($m, $action);
    }
}
