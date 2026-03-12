<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Mail\TicketCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendTicketCreatedNotification implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        $ticket->load(['assignedTo']);

        if ($ticket->assigned_to && $ticket->assignedTo?->user_email) {
            Mail::to($ticket->assignedTo->user_email)->queue(new TicketCreatedMail($ticket));
        }
    }
}
