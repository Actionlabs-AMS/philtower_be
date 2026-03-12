<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Mail\TicketAssignedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendTicketAssignedNotification implements ShouldQueue
{
    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $ticket->load(['assignedTo']);

        if ($ticket->assigned_to && $ticket->assignedTo?->user_email) {
            Mail::to($ticket->assignedTo->user_email)->queue(new TicketAssignedMail($ticket));
        }
    }
}
