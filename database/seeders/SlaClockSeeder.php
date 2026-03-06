<?php

namespace Database\Seeders;

use App\Models\Support\Sla;
use App\Models\Support\SlaClock;
use App\Models\Support\TicketRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds sla_clocks for existing ticket_requests. Depends on: TicketRequestSeeder, SlasSeeder.
 */
class SlaClockSeeder extends Seeder
{
    public function run(): void
    {
        $tickets = TicketRequest::with(['sla', 'ticketStatus'])->get();
        if ($tickets->isEmpty()) {
            $this->command->warn('SlaClockSeeder: No ticket requests found. Run TicketRequestSeeder first.');
            return;
        }

        $created = 0;
        foreach ($tickets as $ticket) {
            if (!$ticket->slas_id || !$ticket->sla) {
                continue;
            }

            $exists = SlaClock::where('entity_type', 'ticket_request')
                ->where('entity_id', $ticket->id)
                ->exists();
            if ($exists) {
                continue;
            }

            $startedAt = $ticket->submitted_at ?? $ticket->created_at ?? now();
            $startedAt = Carbon::parse($startedAt);

            $sla = $ticket->sla;
            $dueAt = $sla->resolution_minutes
                ? $startedAt->copy()->addMinutes($sla->resolution_minutes)
                : null;
            $responseDueAt = $sla->response_minutes
                ? $startedAt->copy()->addMinutes($sla->response_minutes)
                : null;

            $pauseConditions = $sla->pause_conditions ?? [];
            $isPaused = false;
            if ($ticket->ticketStatus && ($pauseConditions['on_onhold'] ?? false) && $ticket->ticketStatus->is_on_hold) {
                $isPaused = true;
            }
            if (($pauseConditions['on_fe_visit'] ?? false) && $ticket->resolved_at) {
                $isPaused = true;
            }

            $status = 'running';
            $completedAt = null;
            $breachedAt = null;
            if ($ticket->closed_at || ($ticket->ticketStatus && $ticket->ticketStatus->is_closed)) {
                $status = 'completed';
                $completedAt = $ticket->closed_at ?? $ticket->updated_at ?? now();
            } elseif ($dueAt && now()->greaterThan($dueAt) && !$isPaused) {
                $status = 'breached';
                $breachedAt = $dueAt;
            }

            SlaClock::create([
                'entity_type' => 'ticket_request',
                'entity_id' => $ticket->id,
                'sla_id' => $sla->id,
                'started_at' => $startedAt,
                'due_at' => $dueAt,
                'response_due_at' => $responseDueAt,
                'paused_at' => $isPaused ? $startedAt : null,
                'total_paused_minutes' => 0,
                'breached_at' => $breachedAt,
                'completed_at' => $completedAt,
                'status' => $status,
            ]);
            $created++;
        }

        $this->command->info('SlaClockSeeder: ' . $created . ' SLA clock(s) created.');
    }
}
