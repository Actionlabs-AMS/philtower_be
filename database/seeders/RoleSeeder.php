<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates roles: Developer Account, Web Admin, Senior Team Lead, Team Lead, Service Desk, Employee.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Developer Account',
                'active' => true,
                'is_super_admin' => true,
            ],
            [
                'name' => 'Web Admin',
                'active' => true,
                'is_super_admin' => true,
            ],
            [
                'name' => 'Approver',
                'active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Agent',
                'active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Requestor',
                'active' => true,
                'is_super_admin' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('✓ Roles created/updated');
    }
}
