<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Helpers\MicrosoftGraphHelper;
use App\Mail\TicketCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketCreatedNotification implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        $ticket->load(['assignedTo']);

        if ($ticket->assigned_to && $ticket->assignedTo?->user_email) {
            MicrosoftGraphHelper::sendMailable($ticket->assignedTo->user_email, new TicketCreatedMail($ticket));
        }
    }
}
