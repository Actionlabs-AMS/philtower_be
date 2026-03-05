<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds ticket_statuses (Ticket Statuses, renamed from Parent Ticket Statuses).
 */
class TicketStatusesSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'open', 'label' => 'Open', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'on_hold_parts', 'label' => 'On Hold (Parts)', 'is_closed' => false, 'is_on_hold' => true],
            ['code' => 'on_hold_customer', 'label' => 'On Hold (Customer)', 'is_closed' => false, 'is_on_hold' => true],
            ['code' => 'resolved_incomplete', 'label' => 'Resolved Incomplete', 'is_closed' => false, 'is_on_hold' => false],
            ['code' => 'closed', 'label' => 'Closed', 'is_closed' => true, 'is_on_hold' => false],
            ['code' => 'cancelled', 'label' => 'Cancelled', 'is_closed' => true, 'is_on_hold' => false],
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
