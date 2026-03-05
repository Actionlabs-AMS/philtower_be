<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Parent SLA only (no child SLA). Severity 1–5; response + resolution minutes.
 */
class SlasSeeder extends Seeder
{
    private const SEVERITY_LEVELS = ['1', '2', '3', '4', '5'];

    private const PARENT_SLA_MINUTES = [
        '1' => ['response' => 5, 'resolution' => 240],
        '2' => ['response' => 15, 'resolution' => 480],
        '3' => ['response' => 30, 'resolution' => 1440],
        '4' => ['response' => 60, 'resolution' => 2880],
        '5' => ['response' => 120, 'resolution' => 4320],
    ];

    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (self::SEVERITY_LEVELS as $severity) {
            $mins = self::PARENT_SLA_MINUTES[$severity];
            $rows[] = [
                'severity' => $severity,
                'response_minutes' => $mins['response'],
                'resolution_minutes' => $mins['resolution'],
                'pause_conditions' => json_encode([
                    'on_onhold' => true,
                    'on_fe_visit' => true,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($rows as $row) {
            DB::table('slas')->updateOrInsert(
                ['severity' => $row['severity']],
                $row
            );
        }
    }
}
