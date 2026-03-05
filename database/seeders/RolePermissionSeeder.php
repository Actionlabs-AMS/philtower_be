<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Navigation;
use App\Models\Permission;
use App\Models\RolePermission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $superAdminRole = Role::where('name', 'Developer Account')->first();
        $adminRole = Role::where('name', 'Admin')->first();
        $editorRole = Role::where('name', 'Editor')->first();
        $viewerRole = Role::where('name', 'Viewer')->first();

        // Get permissions
        $permissions = Permission::all()->keyBy('name');

        // Get all navigations
        $navigations = Navigation::all();

        // Super Admin - Full access to everything
        if ($superAdminRole) {
            foreach ($navigations as $navigation) {
                foreach ($permissions as $permission) {
                    RolePermission::updateOrCreate(
                        [
                            'role_id' => $superAdminRole->id,
                            'navigation_id' => $navigation->id,
                            'permission_id' => $permission->id,
                        ],
                        ['allowed' => true]
                    );
                }
            }
        }

        // Admin - Full access except super admin features
        if ($adminRole) {
            foreach ($navigations as $navigation) {
                foreach ($permissions as $permission) {
                    // Exclude security dashboard for admin
                    if ($navigation->slug === 'security') {
                        continue;
                    }
                    
                    RolePermission::updateOrCreate(
                        [
                            'role_id' => $adminRole->id,
                            'navigation_id' => $navigation->id,
                            'permission_id' => $permission->id,
                        ],
                        ['allowed' => true]
                    );
                }
            }
        }

        // Editor - Create, Edit, View access
        if ($editorRole) {
            $editorPermissions = ['can_view', 'can_create', 'can_edit'];
            
            foreach ($navigations as $navigation) {
                foreach ($editorPermissions as $permName) {
                    if (isset($permissions[$permName])) {
                        RolePermission::updateOrCreate(
                            [
                                'role_id' => $editorRole->id,
                                'navigation_id' => $navigation->id,
                                'permission_id' => $permissions[$permName]->id,
                            ],
                            ['allowed' => true]
                        );
                    }
                }
            }
        }

        // Viewer - Only view access
        if ($viewerRole) {
            $viewPermission = $permissions['can_view'] ?? null;
            
            if ($viewPermission) {
                foreach ($navigations as $navigation) {
                    RolePermission::updateOrCreate(
                        [
                            'role_id' => $viewerRole->id,
                            'navigation_id' => $navigation->id,
                            'permission_id' => $viewPermission->id,
                        ],
                        ['allowed' => true]
                    );
                }
            }
        }
    }
}
