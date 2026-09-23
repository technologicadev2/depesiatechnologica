<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket->load(['user.salarie']);
    }

    public function build()
    {
        $mail = $this->subject('Nouveau ticket: ' . $this->ticket->subject)
                     ->view('emails.ticket_created');

        if ($this->ticket->attachments && $this->ticket->attachments->isNotEmpty()) {
            foreach ($this->ticket->attachments as $attachment) {
                if (in_array($attachment->file_type, ['application/pdf'])) {
                    $filePath = storage_path('app/public/' . $attachment->file_path);
                    if (file_exists($filePath)) {
                        $mail->attach($filePath, [
                            'as' => $attachment->file_name,
                            'mime' => $attachment->file_type,
                        ]);
                    }
                }
            }
        }

        return $mail;
    }
}