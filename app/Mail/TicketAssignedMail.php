<?php

namespace App\Mail;

use App\Models\Support\TicketRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TicketRequest $ticket
    ) {
        $this->ticket->load(['assignedTo', 'user', 'ticketStatus', 'serviceType']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Assigned to You — #' . ($this->ticket->request_number ?? $this->ticket->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-assigned',
        );
    }
}
