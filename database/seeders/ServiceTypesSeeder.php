<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds service_types (Service Catalog). No child-ticket-statuses or SLA.
     */
    public function run(): void
    {
        DB::table('service_types')->updateOrInsert(
            ['code' => 'INQUIRY'],
            [
                'name' => 'Inquiry',
                'code' => 'INQUIRY',
                'description' => 'General inquiries and information requests',
                'parent_id' => null,
                'active' => true,
                'approval' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('service_types')->updateOrInsert(
            ['code' => 'INCIDENT_REQUEST'],
            [
                'name' => 'Incident Request',
                'code' => 'INCIDENT_REQUEST',
                'description' => 'Service requests for incidents and issues',
                'parent_id' => null,
                'active' => true,
                'approval' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $incidentRequestId = DB::table('service_types')->where('code', 'INCIDENT_REQUEST')->value('id');

        DB::table('service_types')->updateOrInsert(
            ['code' => 'SERVICE_REQUEST'],
            [
                'name' => 'Service Request',
                'code' => 'SERVICE_REQUEST',
                'description' => 'Service requests for planned services',
                'parent_id' => null,
                'active' => true,
                'approval' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $serviceRequestId = DB::table('service_types')->where('code', 'SERVICE_REQUEST')->value('id');

        $incidentTypes = [
            ['name' => 'Adhoc', 'code' => 'ADHOC', 'description' => 'On-demand or unscheduled service request', 'parent_id' => $incidentRequestId],
            ['name' => 'Breakfix', 'code' => 'BREAKFIX', 'description' => 'Corrective repair for unexpected issues', 'parent_id' => $incidentRequestId],
            ['name' => 'Follow-up', 'code' => 'FOLLOW_UP', 'description' => 'Follow-up service for previous incidents', 'parent_id' => $incidentRequestId],
        ];

        $serviceTypes = [
            ['name' => 'Preventive Maintenance', 'code' => 'PREVENTIVE_MAINTENANCE', 'description' => 'Scheduled preventive maintenance service', 'parent_id' => $serviceRequestId],
            ['name' => 'Training / Shadowing', 'code' => 'TRAINING_SHADOWING', 'description' => 'Training and shadowing services', 'parent_id' => $serviceRequestId],
            ['name' => 'Installation', 'code' => 'INSTALLATION', 'description' => 'Installation service', 'parent_id' => $serviceRequestId],
            ['name' => 'Adhoc Reliever', 'code' => 'ADHOC_RELIEVER', 'description' => 'Adhoc service with reliever support', 'parent_id' => $serviceRequestId],
            ['name' => 'Asset Tagging', 'code' => 'ASSET_TAGGING', 'description' => 'Asset identification and tagging service', 'parent_id' => $serviceRequestId],
        ];

        foreach (array_merge($incidentTypes, $serviceTypes) as $type) {
            DB::table('service_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'code' => $type['code'],
                    'description' => $type['description'],
                    'parent_id' => $type['parent_id'],
                    'active' => true,
                    'approval' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('ServiceTypesSeeder: Service types created/updated.');
    }
}
