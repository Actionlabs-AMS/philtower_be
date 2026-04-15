<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'label' => 'Critical', 'level' => 1.0],
            ['id' => 2, 'label' => 'High', 'level' => 2.0],
            ['id' => 3, 'label' => 'Mid', 'level' => 3.0],
            ['id' => 4, 'label' => 'Low', 'level' => 4.0],
        ];

        foreach ($rows as $row) {
            DB::table('ticket_priorities')->updateOrInsert(
                ['id' => $row['id']],
                ['label' => $row['label'], 'level' => $row['level']]
            );
        }

        $this->command->info('TicketPrioritySeeder: Ticket priorities created/updated.');
    }
}
