<?php

namespace App\Models\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * SLA runtime / clock for audit-grade tracking (ticket requests).
 */
class SlaClock extends Model
{
    protected $table = 'sla_clocks';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'sla_id',
        'started_at',
        'due_at',
        'response_due_at',
        'paused_at',
        'total_paused_minutes',
        'breached_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'due_at' => 'datetime',
        'response_due_at' => 'datetime',
        'paused_at' => 'datetime',
        'breached_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sla()
    {
        return $this->belongsTo(Sla::class, 'sla_id');
    }

    /**
     * Create a clock for a ticket request (respects SLA pause conditions).
     */
    public static function createForTicketRequest(TicketRequest $ticket, Sla $sla): self
    {
        $now = now();
        $pauseConditions = $sla->pause_conditions ?? [];
        $isPaused = false;
        $ticketStatus = $ticket->ticketStatus;
        if ($ticketStatus && ($pauseConditions['on_onhold'] ?? false) && $ticketStatus->is_on_hold) {
            $isPaused = true;
        }
        if (($pauseConditions['on_fe_visit'] ?? false) && ($ticket->resolved_at ?? null)) {
            $isPaused = true;
        }

        $resolutionDueAt = $sla->resolution_minutes
            ? Carbon::parse($now)->addMinutes($sla->resolution_minutes)
            : null;
        $responseDueAt = $sla->response_minutes
            ? Carbon::parse($now)->addMinutes($sla->response_minutes)
            : null;

        return self::create([
            'entity_type' => 'ticket_request',
            'entity_id' => $ticket->id,
            'sla_id' => $sla->id,
            'started_at' => $now,
            'due_at' => $resolutionDueAt,
            'response_due_at' => $responseDueAt,
            'paused_at' => $isPaused ? $now : null,
            'total_paused_minutes' => 0,
            'status' => $isPaused ? 'paused' : 'running',
        ]);
    }
}
