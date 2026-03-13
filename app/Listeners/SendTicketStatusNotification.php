<?php

namespace App\Listeners;

use App\Events\TicketStatusChanged;
use App\Mail\TicketForApprovalMail;
use App\Mail\TicketResolvedMail;
use App\Services\OptionService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketStatusNotification implements ShouldQueue
{
    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;
        $newCode = $ticket->ticketStatus?->code ?? null;
        $optionService = app(OptionService::class);

        if ($newCode === 'for_approval') {
            $ticket->load(['assignedTo']);
            $recipient = $ticket->assigned_to && $ticket->assignedTo?->user_email
                ? $ticket->assignedTo->user_email
                : null;
            if ($recipient) {
                $optionService->sendMailable($recipient, new TicketForApprovalMail($ticket));
            }
            return;
        }

        if ($newCode === 'resolved') {
            $ticket->load(['user']);
            if ($ticket->user_id && $ticket->user?->user_email) {
                $optionService->sendMailable($ticket->user->user_email, new TicketResolvedMail($ticket));
            }
        }
    }
}
