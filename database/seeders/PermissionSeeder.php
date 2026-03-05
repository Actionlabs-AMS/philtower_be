<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the standard CRUD + export/import permissions used by role_permissions.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'can_view', 'label' => 'can_view'],
            ['name' => 'can_create', 'label' => 'can_create'],
            ['name' => 'can_edit', 'label' => 'can_edit'],
            ['name' => 'can_delete', 'label' => 'can_delete'],
            ['name' => 'can_export', 'label' => 'can_export'],
            ['name' => 'can_import', 'label' => 'can_import'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✓ Permissions created/updated');
    }
}
