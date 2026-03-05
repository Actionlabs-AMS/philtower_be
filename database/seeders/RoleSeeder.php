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
                'name' => 'Senior Team Lead',
                'active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Team Lead',
                'active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Service Desk',
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
