<?php

namespace App\Listeners;

use App\Events\TicketStatusChanged;
use App\Mail\TicketForApprovalMail;
use App\Mail\TicketResolvedMail;
use App\Mail\TicketApprovedMail;
use App\Mail\TicketRejectedMail;
use App\Mail\TicketClosedMail;
use App\Models\User;
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
            // Notify all users with the Approver role
            $approverEmails = User::whereHas('role', function ($query) {
                $query->where('name', 'Approver')->where('active', true);
            })
                ->whereNotNull('user_email')
                ->pluck('user_email')
                ->filter()
                ->values()
                ->all();

            if (!empty($approverEmails)) {
                $ticket->loadMissing(['assignedTo', 'user', 'ticketStatus', 'serviceType']);
                $optionService->sendMailable($approverEmails, new TicketForApprovalMail($ticket));
            }
            return;
        }

        if ($newCode === 'pending_manual_approval') {
            // Notify only the explicitly selected approvers from manual_approval_data.
            $manual = $ticket->manual_approval_data ?? [];
            $approverIds = is_array($manual) ? ($manual['approver_ids'] ?? []) : [];
            $approverIds = is_array($approverIds) ? array_values(array_unique(array_map('intval', $approverIds))) : [];

            if (!empty($approverIds)) {
                $approverEmails = User::query()
                    ->whereIn('id', $approverIds)
                    ->whereHas('role', function ($query) {
                        $query->where('name', 'Approver')->where('active', true);
                    })
                    ->whereNotNull('user_email')
                    ->pluck('user_email')
                    ->filter()
                    ->values()
                    ->all();

                if (!empty($approverEmails)) {
                    $ticket->loadMissing(['assignedTo', 'user', 'ticketStatus', 'serviceType']);
                    $optionService->sendMailable($approverEmails, new TicketForApprovalMail($ticket));
                }
            }

            return;
        }

        if ($newCode === 'approved') {
            // Notify requester that the ticket has been approved
            $ticket->loadMissing(['user', 'assignedTo', 'ticketStatus', 'serviceType']);
            if ($ticket->user_id && $ticket->user?->user_email) {
                $optionService->sendMailable($ticket->user->user_email, new TicketApprovedMail($ticket));
            }
            return;
        }

        if ($newCode === 'rejected') {
            // Notify requester that the ticket has been rejected
            $ticket->loadMissing(['user', 'assignedTo', 'ticketStatus', 'serviceType']);
            if ($ticket->user_id && $ticket->user?->user_email) {
                $optionService->sendMailable($ticket->user->user_email, new TicketRejectedMail($ticket));
            }
            return;
        }

        if ($newCode === 'resolved') {
            $ticket->loadMissing(['user']);
            if ($ticket->user_id && $ticket->user?->user_email) {
                $optionService->sendMailable($ticket->user->user_email, new TicketResolvedMail($ticket));
            }
            return;
        }

        if ($newCode === 'closed') {
            $ticket->loadMissing(['user', 'assignedTo', 'ticketStatus', 'serviceType']);
            if ($ticket->user_id && $ticket->user?->user_email) {
                $optionService->sendMailable($ticket->user->user_email, new TicketClosedMail($ticket));
            }
        }
    }
}
