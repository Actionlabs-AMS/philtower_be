<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
                'is_super_admin' => false,
            ],
            [
                'name' => 'Editor',
                'active' => true,
                'is_super_admin' => false,
            ],
            [
                'name' => 'Viewer',
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
    }
}
