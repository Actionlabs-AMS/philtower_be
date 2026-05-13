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
                'name' => 'IT Department',
                'code' => 'IT',
                'descriptions' => 'Handles technical infrastructure and support',
                'active' => true,
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'descriptions' => 'Manages employee relations and hiring',
                'active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'descriptions' => 'Handles budgeting and financial planning',
                'active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'descriptions' => 'Oversees daily business operations',
                'active' => true,
            ],
            [
                'name' => 'Customer Support',
                'code' => 'CS',
                'descriptions' => 'Handles customer concerns and tickets',
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