<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/** Email tài chính: cảnh báo sớm hằng tuần (finance:alerts) và báo cáo CFO đầu tháng (finance:cfo-report) */
class FinanceMail extends Mailable
{
    use Queueable;

    /** @param array<int, array{data: string, name: string, mime?: string}> $files tệp đính kèm (vd PDF báo cáo CFO) */
    public function __construct(public string $mailSubject, public string $template, public array $payload, public array $files = [])
    {
    }

    public function build()
    {
        $mail = $this->subject($this->mailSubject)->view($this->template, $this->payload);
        foreach ($this->files as $f) {
            $mail->attachData($f['data'], $f['name'], ['mime' => $f['mime'] ?? 'application/pdf']);
        }

        return $mail;
    }
}
