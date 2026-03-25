<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds ticket_statuses. Closed states: Cancelled, Closed, Rejected.
 */
class TicketStatusesSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'new', 'label' => 'New', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'assigned', 'label' => 'Assigned', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'in_progress', 'label' => 'In Progress', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'pending', 'label' => 'Pending', 'is_closed' => false, 'is_on_hold' => true],
            ['code' => 'cancelled', 'label' => 'Cancelled', 'is_closed' => true, 'is_on_hold' => false],
            ['code' => 'resolved', 'label' => 'Resolved', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'closed', 'label' => 'Closed', 'is_closed' => true, 'is_on_hold' => false],
            ['code' => 'for_approval', 'label' => 'For Approval', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'pending_manual_approval', 'label' => 'Pending Manual Approval', 'is_closed' => false, 'is_on_hold' => true],
            ['code' => 'approved', 'label' => 'Approved', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'rejected', 'label' => 'Rejected', 'is_closed' => true, 'is_on_hold' => false],
        ];

        foreach ($statuses as $row) {
            DB::table('ticket_statuses')->updateOrInsert(
                ['code' => $row['code']],
                $row
            );
        }

        $this->command->info('TicketStatusesSeeder: Ticket statuses created/updated.');
    }
}
