<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/** Email tài chính: cảnh báo sớm hằng tuần (finance:alerts) và báo cáo CFO đầu tháng (finance:cfo-report) */
class FinanceMail extends Mailable
{
    use Queueable;

    public function __construct(public string $mailSubject, public string $template, public array $payload)
    {
    }

    public function build()
    {
        return $this->subject($this->mailSubject)->view($this->template, $this->payload);
    }
}
