<?php

namespace App\Listeners;

use App\Events\TicketClosed;
use App\Mail\TicketCsatMail;
use App\Models\Support\TicketRequest;
use App\Services\OptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
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

        if (! $ticket->csat_token) {
            TicketRequest::where('id', $ticket->id)->update(['csat_token' => (string) Str::uuid()]);
            $ticket->refresh();
        }

        app(OptionService::class)->sendMailable($ticket->user->user_email, new TicketCsatMail($ticket));
    }
}
