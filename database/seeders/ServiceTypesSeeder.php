<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Categories: Incident, Problem, Request, Inquiry.
     * Subcategories: Incident (Network, Laptop, Printer); Request (Access, Installation, NEO).
     */
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Incident', 'code' => 'INCIDENT', 'description' => 'Incident'],
            ['name' => 'Problem', 'code' => 'PROBLEM', 'description' => 'Problem'],
            ['name' => 'Request', 'code' => 'REQUEST', 'description' => 'Request'],
            ['name' => 'Inquiry', 'code' => 'INQUIRY', 'description' => 'Inquiry'],
        ];

        foreach ($categories as $cat) {
            DB::table('service_types')->updateOrInsert(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'code' => $cat['code'],
                    'description' => $cat['description'],
                    'parent_id' => null,
                    'active' => true,
                    'approval' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $incidentId = DB::table('service_types')->where('code', 'INCIDENT')->value('id');
        $requestId = DB::table('service_types')->where('code', 'REQUEST')->value('id');

        $subcategories = [
            ['name' => 'Network', 'code' => 'INCIDENT_NETWORK', 'parent_id' => $incidentId],
            ['name' => 'Laptop', 'code' => 'INCIDENT_LAPTOP', 'parent_id' => $incidentId],
            ['name' => 'Printer', 'code' => 'INCIDENT_PRINTER', 'parent_id' => $incidentId],
            ['name' => 'Access', 'code' => 'REQUEST_ACCESS', 'parent_id' => $requestId],
            ['name' => 'Installation', 'code' => 'REQUEST_INSTALLATION', 'parent_id' => $requestId],
            ['name' => 'NEO', 'code' => 'REQUEST_NEO', 'parent_id' => $requestId],
        ];

        foreach ($subcategories as $sub) {
            DB::table('service_types')->updateOrInsert(
                ['code' => $sub['code']],
                [
                    'name' => $sub['name'],
                    'code' => $sub['code'],
                    'description' => $sub['name'],
                    'parent_id' => $sub['parent_id'],
                    'active' => true,
                    'approval' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('ServiceTypesSeeder: Service types (categories and subcategories) created/updated.');
    }
}
