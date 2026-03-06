<?php

namespace App\Services\Support;

use App\Models\Support\TicketRequest;
use App\Models\Support\TicketStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ticket-based analytics for Overview: filters (service type, date range, statistics type),
 * line chart (tickets over time), table (by date or by agent), CSV-ready rows.
 */
class TicketAnalyticsService
{
    /**
     * Get tickets overview: chart (daily counts) + table (by date or by agent).
     *
     * @param int|null $serviceTypeId
     * @param string|null $dateFrom Y-m-d
     * @param string|null $dateTo   Y-m-d
     * @param string $statisticsType 'tickets' | 'agents'
     * @return array{chart: array{labels: string[], data: int[]}, table: array, statistics_type: string}
     */
    public function getTicketsOverview(
        ?int $serviceTypeId,
        ?string $dateFrom,
        ?string $dateTo,
        string $statisticsType = 'tickets'
    ): array {
        $now = Carbon::now();
        $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : $now->copy()->subDays(30)->startOfDay();
        $end = $dateTo ? Carbon::parse($dateTo)->endOfDay() : $now->copy()->endOfDay();

        $base = TicketRequest::query();
        if ($serviceTypeId) {
            $base->where('service_type_id', $serviceTypeId);
        }
        $base->whereBetween('created_at', [$start, $end]);

        $closedStatusIds = TicketStatus::where('is_closed', true)->pluck('id')->toArray();

        // Chart: daily ticket counts (labels = dates, data = counts)
        $dailyCounts = (clone $base)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->toArray();

        $chartLabels = [];
        $chartData = [];
        $current = $start->copy();
        while ($current <= $end) {
            $d = $current->format('Y-m-d');
            $chartLabels[] = $d;
            $chartData[] = (int) ($dailyCounts[$d] ?? 0);
            $current->addDay();
        }

        $chart = [
            'labels' => $chartLabels,
            'data' => $chartData,
        ];

        $table = [];
        if ($statisticsType === 'agents') {
            $table = $this->buildAgentsTable($base, $closedStatusIds);
        } else {
            $table = $this->buildTicketDetailsTable($base);
        }

        return [
            'chart' => $chart,
            'table' => $table,
            'statistics_type' => $statisticsType,
        ];
    }

    /**
     * Detail rows for "Tickets": ticket request + status + first SLA clock (for table and CSV).
     */
    private function buildTicketDetailsTable($baseQuery): array
    {
        $tickets = (clone $baseQuery)
            ->with(['ticketStatus', 'serviceType', 'slaClocks' => fn ($q) => $q->orderBy('id')->limit(1)])
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [];
        foreach ($tickets as $t) {
            $clock = $t->slaClocks->first();
            $rows[] = [
                'id' => $t->id,
                'request_number' => $t->request_number,
                'user_id' => $t->user_id,
                'service_type_id' => $t->service_type_id,
                'service_type_name' => $t->serviceType?->name,
                'description' => $t->description,
                'contact_name' => $t->contact_name,
                'contact_email' => $t->contact_email,
                'contact_number' => $t->contact_number,
                'ticket_status_id' => $t->ticket_status_id,
                'status_code' => $t->ticketStatus?->code,
                'status_label' => $t->ticketStatus?->label,
                'assigned_to' => $t->assigned_to,
                'submitted_at' => $t->submitted_at?->toIso8601String(),
                'resolved_at' => $t->resolved_at?->toIso8601String(),
                'closed_at' => $t->closed_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
                'updated_at' => $t->updated_at?->toIso8601String(),
                'sla_clock_id' => $clock?->id,
                'sla_clock_started_at' => $clock?->started_at?->toIso8601String(),
                'sla_clock_due_at' => $clock?->due_at?->toIso8601String(),
                'sla_clock_response_due_at' => $clock?->response_due_at?->toIso8601String(),
                'sla_clock_status' => $clock?->status,
                'sla_clock_breached_at' => $clock?->breached_at?->toIso8601String(),
                'sla_clock_completed_at' => $clock?->completed_at?->toIso8601String(),
            ];
        }
        return $rows;
    }

    /**
     * Table rows for "Agents": agent_id, agent_name, open_count, resolved_count, total_count (in range).
     */
    private function buildAgentsTable($baseQuery, array $closedStatusIds): array
    {
        $agentCounts = (clone $baseQuery)
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to as user_id, COUNT(*) as total')
            ->groupBy('assigned_to')
            ->get();

        $resolvedCounts = (clone $baseQuery)
            ->whereNotNull('assigned_to')
            ->whereNotNull('resolved_at')
            ->selectRaw('assigned_to as user_id, COUNT(*) as resolved')
            ->groupBy('assigned_to')
            ->pluck('resolved', 'user_id')
            ->toArray();

        $openCounts = (clone $baseQuery)
            ->whereNotNull('assigned_to')
            ->whereNotIn('ticket_status_id', $closedStatusIds)
            ->selectRaw('assigned_to as user_id, COUNT(*) as open_c')
            ->groupBy('assigned_to')
            ->pluck('open_c', 'user_id')
            ->toArray();

        $userIds = $agentCounts->pluck('user_id')->unique()->filter()->values()->all();
        $users = $userIds ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

        $rows = [];
        foreach ($agentCounts as $row) {
            $userId = (int) $row->user_id;
            $user = $users->get($userId);
            $name = $user ? ($user->user_login ?? 'Agent #' . $userId) : 'Agent #' . $userId;
            $rows[] = [
                'agent_id' => $userId,
                'agent_name' => $name,
                'open_count' => (int) ($openCounts[$userId] ?? 0),
                'resolved_count' => (int) ($resolvedCounts[$userId] ?? 0),
                'total_count' => (int) $row->total,
            ];
        }

        usort($rows, fn ($a, $b) => $b['total_count'] <=> $a['total_count']);
        return $rows;
    }
}
