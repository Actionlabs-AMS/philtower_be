<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'can_view', 'label' => 'View'],
            ['name' => 'can_create', 'label' => 'Create'],
            ['name' => 'can_edit', 'label' => 'Edit'],
            ['name' => 'can_delete', 'label' => 'Delete'],
            ['name' => 'can_export', 'label' => 'Export'],
            ['name' => 'can_import', 'label' => 'Import'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
