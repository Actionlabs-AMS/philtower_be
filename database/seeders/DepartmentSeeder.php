<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'CEO/CFRSO',
                'code' => 'CEO',
                'descriptions' => 'CEO/CFRSO',
                'active' => true,
            ],
            [
                'name' => 'Commercial',
                'code' => 'COMMERCIAL',
                'descriptions' => 'Commercial',
                'active' => true,
            ],
            [
                'name' => 'CORPORATE FINANCE & RISK',
                'code' => 'CORPORATE FINANCE & RISK',
                'descriptions' => 'CORPORATE FINANCE & RISK',
                'active' => true,
            ],
            [
                'name' => 'GOVERNMENT RELATIONS AND EXTERNAL AFFAIRS',
                'code' => 'EXTERNAL AFFAIRS',
                'descriptions' => 'GOVERNMENT RELATIONS AND EXTERNAL AFFAIRS',
                'active' => true,
            ],
            [
                'name' => 'HUMAN RESOURCES AND ADMINISTRATION',
                'code' => 'HRAD',
                'descriptions' => 'HUMAN RESOURCES AND ADMINISTRATION',
                'active' => true,
            ],
            [
                'name' => 'IT',
                'code' => 'IT',
                'descriptions' => 'IT',
                'active' => true,
            ],
            [
                'name' => 'Legal',
                'code' => 'LEGAL',
                'descriptions' => 'Legal',
                'active' => true,
            ],
            [
                'name' => 'LESSOR ASSET MANAGEMENT',
                'code' => 'LESSOR ASSET MANAGEMENT',
                'descriptions' => 'LESSOR ASSET MANAGEMENT',
                'active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPERATIONS',
                'descriptions' => 'Operations',
                'active' => true,
            ],
            [
                'name' => 'Roll-Out',
                'code' => 'ROLL-OUT',
                'descriptions' => 'Roll-Out',
                'active' => true,
            ],
            [
                'name' => 'Supply Chain Management',
                'code' => 'SCM',
                'descriptions' => 'Supply Chain Management',
                'active' => true,
            ],
            [
                'name' => 'SECURITY',
                'code' => 'SECURITY',
                'descriptions' => 'SECURITY',
                'active' => true,
            ],
            [
                'name' => 'WORKPLACE HEALTH, SAFETY AND ENVIRONMENT',
                'code' => 'WHSE',
                'descriptions' => 'WORKPLACE HEALTH, SAFETY AND ENVIRONMENT',
                'active' => true,
            ],
            [
                'name' => 'UTILITY AND BILLING',
                'code' => 'UTILITY AND BILLING',
                'descriptions' => 'UTILITY AND BILLING',
                'active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']], // unique key
                $dept
            );
        }
    }
}