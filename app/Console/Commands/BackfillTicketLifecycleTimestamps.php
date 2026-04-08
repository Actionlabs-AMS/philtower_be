<?php

namespace App\Console\Commands;

use App\Models\Support\TicketRequest;
use App\Models\Support\TicketStatus;
use App\Models\Support\TicketUpdate;
use Illuminate\Console\Command;

class BackfillTicketLifecycleTimestamps extends Command
{
    protected $signature = 'tickets:backfill-lifecycle-timestamps {--dry-run : Show counts without writing}';
    protected $description = 'Backfill missing resolved_at/closed_at using ticket_updates status-change history first.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $resolvedStatusId = TicketStatus::query()->where('code', 'resolved')->value('id');
        $closedStatusId = TicketStatus::query()->where('code', 'closed')->value('id');

        if (!$resolvedStatusId && !$closedStatusId) {
            $this->warn('No resolved/closed status codes found. Nothing to backfill.');
            return self::SUCCESS;
        }

        $resolvedMissing = 0;
        $closedMissing = 0;
        $resolvedFromHistory = 0;
        $closedFromHistory = 0;
        $resolvedFromFallback = 0;
        $closedFromFallback = 0;
        $updated = 0;

        TicketRequest::withTrashed()
            ->when($resolvedStatusId || $closedStatusId, function ($q) use ($resolvedStatusId, $closedStatusId) {
                $ids = array_values(array_filter([(int) $resolvedStatusId, (int) $closedStatusId]));
                $q->whereIn('ticket_status_id', $ids);
            })
            ->orderBy('id')
            ->chunkById(200, function ($tickets) use ($dryRun, $resolvedStatusId, $closedStatusId, &$resolvedMissing, &$closedMissing, &$resolvedFromHistory, &$closedFromHistory, &$resolvedFromFallback, &$closedFromFallback, &$updated) {
                foreach ($tickets as $ticket) {
                    $dirty = false;
                    $historyResolvedAt = $this->getStatusTransitionTimestamp($ticket->id, 'resolved');
                    $historyClosedAt = $this->getStatusTransitionTimestamp($ticket->id, 'closed');

                    if ($resolvedStatusId && (int) $ticket->ticket_status_id === (int) $resolvedStatusId && $ticket->resolved_at === null) {
                        $resolvedMissing++;
                        if (!$dryRun) {
                            if ($historyResolvedAt !== null) {
                                $ticket->resolved_at = $historyResolvedAt;
                                $resolvedFromHistory++;
                            } else {
                                $ticket->resolved_at = $ticket->updated_at ?? $ticket->created_at ?? now();
                                $resolvedFromFallback++;
                            }
                            $dirty = true;
                        }
                    }

                    if ($closedStatusId && (int) $ticket->ticket_status_id === (int) $closedStatusId) {
                        if ($ticket->closed_at === null) {
                            $closedMissing++;
                            if (!$dryRun) {
                                if ($historyClosedAt !== null) {
                                    $ticket->closed_at = $historyClosedAt;
                                    $closedFromHistory++;
                                } else {
                                    $ticket->closed_at = $ticket->updated_at ?? $ticket->created_at ?? now();
                                    $closedFromFallback++;
                                }
                                $dirty = true;
                            }
                        }

                        if ($ticket->resolved_at === null && !$dryRun) {
                            if ($historyResolvedAt !== null) {
                                $ticket->resolved_at = $historyResolvedAt;
                                $resolvedFromHistory++;
                            } else {
                                $ticket->resolved_at = $ticket->closed_at ?? $ticket->updated_at ?? $ticket->created_at ?? now();
                                $resolvedFromFallback++;
                            }
                            $dirty = true;
                        }
                    }

                    if ($dirty) {
                        $ticket->save();
                        $updated++;
                    }
                }
            });

        $this->info('Backfill scan complete.');
        $this->line("Resolved status missing resolved_at: {$resolvedMissing}");
        $this->line("Closed status missing closed_at: {$closedMissing}");
        if ($dryRun) {
            $this->line('Dry run only. No rows updated.');
        } else {
            $this->line("resolved_at from history: {$resolvedFromHistory}");
            $this->line("resolved_at from fallback: {$resolvedFromFallback}");
            $this->line("closed_at from history: {$closedFromHistory}");
            $this->line("closed_at from fallback: {$closedFromFallback}");
            $this->line("Rows updated: {$updated}");
        }

        return self::SUCCESS;
    }

    private function getStatusTransitionTimestamp(int $ticketId, string $targetStatusCode): ?\Illuminate\Support\Carbon
    {
        $target = strtolower($targetStatusCode);

        // Content pattern currently written by service:
        // "Status changed from <Old Label> to <New Label>"
        return TicketUpdate::withTrashed()
            ->where('ticket_request_id', $ticketId)
            ->where('type', TicketUpdate::TYPE_STATUS_CHANGE)
            ->whereRaw('LOWER(content) LIKE ?', ['% to ' . $target . '%'])
            ->orderBy('created_at', 'asc')
            ->value('created_at');
    }
}

