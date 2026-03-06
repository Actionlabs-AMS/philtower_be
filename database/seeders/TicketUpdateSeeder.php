<?php

namespace Database\Seeders;

use App\Models\Support\TicketRequest;
use App\Models\Support\TicketUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds ticket_updates (comments and status changes) for existing ticket_requests.
 * Depends on: TicketRequestSeeder, UserSeeder, TicketStatusesSeeder.
 */
class TicketUpdateSeeder extends Seeder
{
    private static array $COMMENTS = [
        'Acknowledged. We will look into this shortly.',
        'Assigned to the team. Expected update within 24 hours.',
        'Waiting for parts. ETA 3–5 business days.',
        'Issue reproduced. Working on a fix.',
        'Resolution applied. Please confirm if the issue is resolved.',
        'Follow-up: Can you provide more details on when this occurs?',
        'Ticket escalated to L2 for further investigation.',
        'User confirmed resolution. Closing ticket.',
        'Vendor has been contacted. Awaiting replacement unit.',
        'Patch has been deployed. Please restart your machine.',
        'Access rights updated. You should see the folder within 5 minutes.',
        'Temporary workaround: use the link sent to your email until we fix the root cause.',
        'Duplicate of TR-20250301-ABC123. Merging updates there.',
        'Scheduled maintenance window: Saturday 2–4 AM. No action needed.',
        'Please try again now. Cache was cleared on our side.',
        'Referred to Facilities for physical inspection.',
        'License request approved. Installation instructions sent.',
        'Backup restore in progress. ETA 1 hour.',
        'Root cause identified. Implementing fix in next release.',
        'Closing as duplicate. Refer to parent ticket for history.',
    ];

    private static array $NOTES = [
        'Customer requested callback after 3 PM.',
        'Priority raised by department head.',
        'Repeated issue – same user, third time this month.',
        'Documented in knowledge base. KB-4421.',
    ];

    public function run(): void
    {
        $tickets = TicketRequest::with(['user', 'assignedTo', 'ticketStatus'])->get();
        if ($tickets->isEmpty()) {
            $this->command->warn('TicketUpdateSeeder: No ticket requests found. Run TicketRequestSeeder first.');
            return;
        }

        $userIds = User::pluck('id')->toArray();
        $fallbackUserId = $userIds[0] ?? null;

        $created = 0;
        foreach ($tickets as $ticket) {
            $authorId = $ticket->assigned_to ?? $ticket->user_id ?? $fallbackUserId;
            $submittedAt = $ticket->submitted_at ?? $ticket->created_at;
            $baseTime = $submittedAt ? Carbon::parse($submittedAt) : now();

            // 2–5 comments per ticket, with staggered created_at
            $numComments = rand(2, min(5, count(self::$COMMENTS)));
            $indices = array_rand(self::$COMMENTS, $numComments);
            $indices = is_array($indices) ? $indices : [$indices];
            foreach (array_values($indices) as $i => $idx) {
                $createdAt = $baseTime->copy()->addHours($i + 1)->addMinutes(rand(5, 45));
                TicketUpdate::create([
                    'ticket_request_id' => $ticket->id,
                    'user_id' => $authorId,
                    'created_by' => $authorId,
                    'content' => self::$COMMENTS[$idx],
                    'type' => TicketUpdate::TYPE_COMMENT,
                    'is_internal' => (bool) rand(0, 1),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $created++;
            }

            // 0–1 internal note per ticket
            if (rand(0, 1)) {
                $noteIdx = array_rand(self::$NOTES);
                $noteAt = $baseTime->copy()->addHours(count($indices) + 2)->addMinutes(rand(10, 30));
                TicketUpdate::create([
                    'ticket_request_id' => $ticket->id,
                    'user_id' => $authorId,
                    'created_by' => $authorId,
                    'content' => self::$NOTES[$noteIdx],
                    'type' => TicketUpdate::TYPE_NOTE,
                    'is_internal' => true,
                    'created_at' => $noteAt,
                    'updated_at' => $noteAt,
                ]);
                $created++;
            }

            // One status-change entry per ticket
            $statusLabel = $ticket->ticketStatus?->label ?? 'Unknown';
            $statusChangeContent = 'Status: ' . $statusLabel . '.';
            $statusChangeAt = $baseTime->copy()->addMinutes(rand(15, 90));
            TicketUpdate::create([
                'ticket_request_id' => $ticket->id,
                'user_id' => $authorId,
                'created_by' => $authorId,
                'content' => $statusChangeContent,
                'type' => TicketUpdate::TYPE_STATUS_CHANGE,
                'is_internal' => true,
                'created_at' => $statusChangeAt,
                'updated_at' => $statusChangeAt,
            ]);
            $created++;
        }

        $this->command->info('TicketUpdateSeeder: ' . $created . ' ticket update(s) created.');
    }
}
