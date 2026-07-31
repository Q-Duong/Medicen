<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Report;

class ReportUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $report;
    public $item;

    public function __construct(Report $report, array $item)
    {
        $this->report = $report;
        $this->item = $item;
    }

    public function build()
    {
        $action = isset($this->item['_delete']) && $this->item['_delete'] == 1 ? 'Xóa' : 'Cập nhật';
        
        return $this->subject("[Thông báo] {$action} hạng mục Báo cáo Thu Chi T{$this->report->month}/{$this->report->year}")
                    ->view('emails.report_updated');
    }
}