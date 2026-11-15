<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/** Email nhắc xếp lịch (lệnh schedule:remind) */
class ScheduleReminderMail extends Mailable
{
    use Queueable;

    public function __construct(public string $mailSubject, public array $payload)
    {
    }

    public function build()
    {
        return $this->subject($this->mailSubject)->view('emails.schedule_reminder', $this->payload);
    }
}
