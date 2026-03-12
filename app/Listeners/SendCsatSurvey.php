<?php

namespace App\Listeners;

use App\Events\TicketClosed;
use App\Mail\TicketCsatMail;
use App\Models\Support\TicketRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendCsatSurvey implements ShouldQueue
{
    public function handle(TicketClosed $event): void
    {
        $ticket = $event->ticket;
        $ticket->load(['user']);

        if (! $ticket->user_id || ! $ticket->user?->user_email) {
            return;
        }

        // Generate a unique CSAT token if not already set
        if (! $ticket->csat_token) {
            TicketRequest::where('id', $ticket->id)->update(['csat_token' => (string) Str::uuid()]);
            $ticket->refresh();
        }

        Mail::to($ticket->user->user_email)->queue(new TicketCsatMail($ticket));
    }
}
