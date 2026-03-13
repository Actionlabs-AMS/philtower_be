<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Mail\TicketCreatedMail;
use App\Services\OptionService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketCreatedNotification implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        $ticket->load(['user']);

        if ($ticket->user_id && $ticket->user?->user_email) {
            app(OptionService::class)->sendMailable($ticket->user->user_email, new TicketCreatedMail($ticket));
        }
    }
}
