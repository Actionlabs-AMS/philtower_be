<?php

namespace App\Services\Support;

use App\Models\Support\TicketRequest;
use App\Models\Support\TicketStatus;
use App\Models\Support\SlaClock;
use App\Models\Support\ServiceType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard statistics for support/ticket requests (helpdesk-style).
 * Aligns with actionlabs-support-admin dashboard shape for philtower_fe.
 */
class DashboardService
{
    /**
     * @param  User|null  $user  Current user; when null, uses auth()->user(). If user cannot view all tickets, stats are scoped to assigned_to.
     * @param  string|null  $startDate  Y-m-d optional filter
     * @param  string|null  $endDate  Y-m-d optional filter
     * @param  int|null  $serviceTypeId  Optional filter by service type
     * @param  int|null  $ticketStatusId  Optional filter by ticket status
     */
    public function getTicketStats(
        ?User $user = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $serviceTypeId = null,
        ?int $ticketStatusId = null
    ): array {
        $user = $user ?? auth()->user();
        $now = Carbon::now();
        $startOfToday = $now->copy()->startOfDay();
        $endOfToday = $now->copy()->endOfDay();
        $startOfYesterday = $now->copy()->subDay()->startOfDay();
        $endOfYesterday = $now->copy()->subDay()->endOfDay();

        $statusIdsByCode = TicketStatus::pluck('id', 'code')->toArray();
        $closedStatusIds = TicketStatus::where('is_closed', true)->pluck('id')->toArray();

        $base = TicketRequest::query();
        if ($user && ! $user->canViewAllTickets()) {
            $base->where('assigned_to', $user->id);
        }
        if ($startDate && trim((string) $startDate) !== '') {
            try {
                $base->whereDate('ticket_requests.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            } catch (\Exception $e) {
                // ignore invalid start_date
            }
        }
        if ($endDate && trim((string) $endDate) !== '') {
            try {
                $base->whereDate('ticket_requests.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            } catch (\Exception $e) {
                // ignore invalid end_date
            }
        }
        if ($serviceTypeId !== null && $serviceTypeId > 0) {
            $base->where('service_type_id', $serviceTypeId);
        }
        if ($ticketStatusId !== null && $ticketStatusId > 0) {
            $base->where('ticket_status_id', $ticketStatusId);
        }

        // Unresolved: not closed (status not in closed list)
        $unresolved = (clone $base)
            ->whereNotIn('ticket_status_id', $closedStatusIds)
            ->count();

        // Overdue: has SLA clock with due_at in the past and status running/breached
        $overdueTicketIds = SlaClock::query()
            ->where('entity_type', 'ticket_request')
            ->whereIn('status', ['running', 'breached'])
            ->where('due_at', '<', $startOfToday)
            ->pluck('entity_id')
            ->unique()
            ->values()
            ->all();
        $overdue = $overdueTicketIds
            ? (clone $base)->whereIn('id', $overdueTicketIds)->whereNotIn('ticket_status_id', $closedStatusIds)->count()
            : 0;

        // Due today: SLA clock due_at within today, still running
        $dueTodayTicketIds = SlaClock::query()
            ->where('entity_type', 'ticket_request')
            ->where('status', 'running')
            ->whereBetween('due_at', [$startOfToday, $endOfToday])
            ->pluck('entity_id')
            ->unique()
            ->values()
            ->all();
        $dueToday = $dueTodayTicketIds
            ? (clone $base)->whereIn('id', $dueTodayTicketIds)->whereNotIn('ticket_status_id', $closedStatusIds)->count()
            : 0;

        // Open: new + assigned (treat as "open" for KPI)
        $openStatusIds = array_filter([
            $statusIdsByCode['new'] ?? null,
            $statusIdsByCode['assigned'] ?? null,
        ]);
        $open = $openStatusIds ? (clone $base)->whereIn('ticket_status_id', $openStatusIds)->count() : 0;

        // On hold: status with is_on_hold
        $onHoldStatusIds = TicketStatus::where('is_on_hold', true)->pluck('id')->toArray();
        $onHold = $onHoldStatusIds ? (clone $base)->whereIn('ticket_status_id', $onHoldStatusIds)->count() : 0;

        // Unassigned: no assignee and not closed
        $unassigned = (clone $base)
            ->whereNull('assigned_to')
            ->whereNotIn('ticket_status_id', $closedStatusIds)
            ->count();

        // Resolved: has resolved_at set
        $resolved = (clone $base)->whereNotNull('resolved_at')->count();

        // Received: total
        $received = (clone $base)->count();

        // Average first response: philtower has no first_response_at; use 0 or derive from first staff update later
        $averageFirstResponseSeconds = 0;

        // Resolution within SLA: resolved tickets that have an sla_clock with due_at; % where resolved_at <= due_at
        $resolvedWithSla = (clone $base)
            ->whereNotNull('resolved_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('sla_clocks')
                    ->whereColumn('sla_clocks.entity_id', 'ticket_requests.id')
                    ->where('sla_clocks.entity_type', 'ticket_request')
                    ->whereNotNull('sla_clocks.due_at');
            })
            ->count();
        $withinSla = (clone $base)
            ->whereNotNull('resolved_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('sla_clocks')
                    ->whereColumn('sla_clocks.entity_id', 'ticket_requests.id')
                    ->where('sla_clocks.entity_type', 'ticket_request')
                    ->whereNotNull('sla_clocks.due_at')
                    ->whereColumn('ticket_requests.resolved_at', '<=', 'sla_clocks.due_at');
            })
            ->count();
        $resolutionWithinSlaPercent = $resolvedWithSla > 0
            ? round((float) ($withinSla / $resolvedWithSla) * 100, 2)
            : ($received > 0 ? 0.0 : 0.0);

        // Trends by hour (0–23) for today and yesterday (created_at)
        $trendsToday = [];
        $trendsYesterday = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStart = $startOfToday->copy()->addHours($hour);
            $hourEnd = $hourStart->copy()->addHour()->subSecond();
            $trendsToday[$hour] = (clone $base)->whereBetween('created_at', [$hourStart, $hourEnd])->count();
            $yesterdayHourStart = $startOfYesterday->copy()->addHours($hour);
            $yesterdayHourEnd = $yesterdayHourStart->copy()->addHour()->subSecond();
            $trendsYesterday[$hour] = (clone $base)->whereBetween('created_at', [$yesterdayHourStart, $yesterdayHourEnd])->count();
        }

        // KPI by status
        $kpiOpen = $open;
        $kpiInProgress = isset($statusIdsByCode['in_progress'])
            ? (clone $base)->where('ticket_status_id', $statusIdsByCode['in_progress'])->count()
            : 0;
        $kpiPending = isset($statusIdsByCode['pending'])
            ? (clone $base)->where('ticket_status_id', $statusIdsByCode['pending'])->count()
            : 0;
        $kpiOnHold = $onHold;
        $kpiClosed = isset($statusIdsByCode['closed'])
            ? (clone $base)->where('ticket_status_id', $statusIdsByCode['closed'])->count()
            : 0;
        $kpiCancelled = isset($statusIdsByCode['cancelled'])
            ? (clone $base)->where('ticket_status_id', $statusIdsByCode['cancelled'])->count()
            : 0;

        // Ticket source: last 7 days, active (not closed/cancelled) by service_type
        $sevenDaysAgo = $now->copy()->subDays(7)->startOfDay();
        $sourceBase = (clone $base)
            ->where('ticket_requests.created_at', '>=', $sevenDaysAgo)
            ->whereNotIn('ticket_requests.ticket_status_id', array_filter([
                $statusIdsByCode['closed'] ?? null,
                $statusIdsByCode['cancelled'] ?? null,
            ]));
        $totalActive = (clone $sourceBase)->count();
        $sourceRows = (clone $sourceBase)
            ->join('service_types', 'ticket_requests.service_type_id', '=', 'service_types.id')
            ->selectRaw('service_types.name as src_name, service_types.id as src_id, COUNT(*) as c')
            ->groupBy('service_types.id', 'service_types.name')
            ->get();
        $colors = ['#3182CE', '#38A169', '#DD6B20', '#805AD5', '#E53E3E', '#D69E2E'];
        $ticketSource = [
            'period' => 'last_7_days',
            'total_active' => $totalActive,
            'sources' => $sourceRows->map(function ($row, $i) use ($colors) {
                return [
                    'name' => $row->src_name ?? 'Other',
                    'value' => (int) $row->c,
                    'color' => $colors[$i % count($colors)],
                ];
            })->toArray(),
        ];
        if (empty($ticketSource['sources']) && $totalActive > 0) {
            $ticketSource['sources'] = [['name' => 'Portal', 'value' => $totalActive, 'color' => $colors[0]]];
        }

        // SLA breach: count tickets with sla_clock status = breached (same base filters)
        $breachedTicketIds = SlaClock::query()
            ->where('entity_type', 'ticket_request')
            ->where('status', 'breached')
            ->pluck('entity_id')
            ->unique()
            ->values()
            ->all();
        $slaBreachTotal = $breachedTicketIds
            ? (clone $base)->whereIn('id', $breachedTicketIds)->count()
            : 0;
        // SLA breach breakdown by service type (for pie chart)
        $slaBreachBySource = [];
        if ($breachedTicketIds) {
            $breachSourceRows = (clone $base)
                ->whereIn('ticket_requests.id', $breachedTicketIds)
                ->join('service_types', 'ticket_requests.service_type_id', '=', 'service_types.id')
                ->selectRaw('service_types.name as src_name, service_types.id as src_id, COUNT(*) as c')
                ->groupBy('service_types.id', 'service_types.name')
                ->get();
            $slaBreachBySource = $breachSourceRows->map(function ($row, $i) use ($colors) {
                return [
                    'name' => $row->src_name ?? 'Other',
                    'value' => (int) $row->c,
                    'color' => $colors[$i % count($colors)],
                ];
            })->values()->all();
        }
        if (empty($slaBreachBySource) && $slaBreachTotal > 0) {
            $slaBreachBySource = [['name' => 'Breached', 'value' => $slaBreachTotal, 'color' => $colors[0]]];
        }

        // Agent performance: group by assigned_to
        $agentRows = (clone $base)
            ->whereNotNull('assigned_to')
            ->whereNotIn('ticket_status_id', $closedStatusIds)
            ->selectRaw('assigned_to as user_id, COUNT(*) as open_ticket')
            ->groupBy('assigned_to')
            ->orderByDesc('open_ticket')
            ->limit(20)
            ->get();
        $userIds = $agentRows->pluck('user_id')->unique()->filter()->values()->all();
        $users = $userIds ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $agentPerformance = [];
        foreach ($agentRows as $row) {
            $userId = (int) $row->user_id;
            $openTicket = (int) $row->open_ticket;
            $user = $users->get($userId);
            $name = $user ? ($user->user_login ?? 'Agent #' . $userId) : 'Agent #' . $userId;
            $initials = $this->initialsFromName($name, $userId);
            $loadPercent = min(100, (int) round(($openTicket / 20) * 100));
            $agentPerformance[] = [
                'agent_id' => $userId,
                'name' => $name,
                'initials' => $initials,
                'team' => 'Support',
                'open_ticket' => $openTicket,
                'status' => $openTicket > 8 ? 'At Risk' : ($openTicket < 5 ? 'Top Performer' : 'On Track'),
                'avg_frt_seconds' => 0,
                'csat' => null,
                'load_percent' => $loadPercent,
            ];
        }

        return [
            'unresolved' => $unresolved,
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'open' => $open,
            'on_hold' => $onHold,
            'unassigned' => $unassigned,
            'resolved' => $resolved,
            'received' => $received,
            'average_first_response_seconds' => $averageFirstResponseSeconds,
            'resolution_within_sla_percent' => $resolutionWithinSlaPercent,
            'trends_today' => array_values($trendsToday),
            'trends_yesterday' => array_values($trendsYesterday),
            'kpi_open' => $kpiOpen,
            'kpi_in_progress' => $kpiInProgress,
            'kpi_pending' => $kpiPending,
            'kpi_on_hold' => $kpiOnHold,
            'kpi_closed' => $kpiClosed,
            'kpi_cancelled' => $kpiCancelled,
            'ticket_source' => $ticketSource,
            'agent_performance' => $agentPerformance,
            'sla_breach_total' => $slaBreachTotal,
            'sla_breach_by_source' => $slaBreachBySource,
        ];
    }

    private function initialsFromName(string $name, int $fallbackId): string
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }
        if (strlen($name) >= 2) {
            return strtoupper(mb_substr($name, 0, 2));
        }
        return (string) $fallbackId;
    }
}
