<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Mail\TicketAssignedMail;
use App\Services\OptionService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketAssignedNotification implements ShouldQueue
{
    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $ticket->load(['assignedTo']);

        if ($ticket->assigned_to && $ticket->assignedTo?->user_email) {
            app(OptionService::class)->sendMailable($ticket->assignedTo->user_email, new TicketAssignedMail($ticket));
        }
    }
}
