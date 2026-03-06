<?php

namespace App\Helpers;

use App\Models\Support\Sla;
use App\Models\Support\SlaClock;
use App\Models\Support\TicketRequest;
use App\Models\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * SLA clock management for ticket requests (philtower).
 * Creates/updates SlaClock when ticket status or SLA-relevant data changes.
 */
class SlaHelper
{
    public static function manageTicketRequestSla(TicketRequest $ticket, bool $isNew = false): ?SlaClock
    {
        $sla = $ticket->slas_id ? Sla::find($ticket->slas_id) : null;
        if (!$sla) {
            return null;
        }

        $clock = SlaClock::where('entity_type', 'ticket_request')
            ->where('entity_id', $ticket->id)
            ->first();

        if ($isNew && !$clock) {
            return SlaClock::createForTicketRequest($ticket, $sla);
        }
        if (!$isNew && $clock) {
            return self::updateSlaClock($clock, $ticket, $sla);
        }
        if (!$isNew && !$clock) {
            Log::info('SlaHelper: Creating retroactive SLA clock for ticket request', ['ticket_request_id' => $ticket->id]);
            return SlaClock::createForTicketRequest($ticket, $sla);
        }

        return $clock;
    }

    protected static function updateSlaClock(SlaClock $clock, TicketRequest $ticket, Sla $sla): SlaClock
    {
        $now = Carbon::now();
        $ticket->loadMissing('ticketStatus');
        $ticketStatus = $ticket->ticketStatus;
        $shouldPause = self::shouldPauseClock($ticket, $sla);
        $isCompleted = self::isTicketRequestCompleted($ticket, $ticketStatus);

        if ($isCompleted && !$clock->completed_at) {
            $clock->update([
                'completed_at' => $now,
                'status' => 'completed',
                'paused_at' => null,
            ]);
            return $clock->fresh();
        }

        if ($shouldPause) {
            if (!$clock->paused_at) {
                $clock->update([
                    'paused_at' => $now,
                    'status' => 'paused',
                ]);
            }
            return $clock->fresh();
        }

        if (!$shouldPause && !$isCompleted) {
            $pauseMinutes = 0;
            if ($clock->paused_at) {
                $pauseMinutes = (int) $now->diffInMinutes(Carbon::parse($clock->paused_at));
            }
            $clock->update([
                'paused_at' => null,
                'total_paused_minutes' => ($clock->total_paused_minutes ?? 0) + $pauseMinutes,
                'status' => 'running',
            ]);
        }

        if ($clock->status === 'running' && !$clock->breached_at && $clock->due_at) {
            if ($now->greaterThan($clock->due_at)) {
                $clock->update([
                    'breached_at' => $now,
                    'status' => 'breached',
                ]);
            }
        }

        return $clock->fresh();
    }

    protected static function shouldPauseClock(TicketRequest $ticket, Sla $sla): bool
    {
        $pauseConditions = $sla->pause_conditions ?? [];
        $ticket->loadMissing('ticketStatus');
        $ticketStatus = $ticket->ticketStatus;

        if (($pauseConditions['on_onhold'] ?? false) && $ticketStatus && $ticketStatus->is_on_hold) {
            return true;
        }
        if (($pauseConditions['on_fe_visit'] ?? false) && $ticket->resolved_at) {
            $resolvedAt = Carbon::parse($ticket->resolved_at);
            if ($resolvedAt->isFuture()) {
                return true;
            }
        }
        return false;
    }

    protected static function isTicketRequestCompleted(TicketRequest $ticket, ?TicketStatus $ticketStatus): bool
    {
        if ($ticketStatus && $ticketStatus->is_closed) {
            return true;
        }
        return $ticket->closed_at !== null;
    }
}
