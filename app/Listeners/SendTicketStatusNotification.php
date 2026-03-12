<?php

namespace App\Listeners;

use App\Events\TicketStatusChanged;
use App\Mail\TicketForApprovalMail;
use App\Mail\TicketResolvedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendTicketStatusNotification implements ShouldQueue
{
    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;
        $newCode = $ticket->ticketStatus?->code ?? null;

        if ($newCode === 'for_approval') {
            $ticket->load(['assignedTo']);
            $recipient = $ticket->assigned_to && $ticket->assignedTo?->user_email
                ? $ticket->assignedTo->user_email
                : null;
            if ($recipient) {
                Mail::to($recipient)->queue(new TicketForApprovalMail($ticket));
            }
            return;
        }

        if ($newCode === 'resolved') {
            $ticket->load(['user']);
            if ($ticket->user_id && $ticket->user?->user_email) {
                Mail::to($ticket->user->user_email)->queue(new TicketResolvedMail($ticket));
            }
        }
    }
}
