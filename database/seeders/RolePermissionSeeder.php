<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Navigation;
use App\Models\RolePermission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Assigns permissions to roles per navigation (run after PermissionSeeder, RoleSeeder, NavigationSeeder).
     * Roles: Developer Account, Web Admin, Senior Team Lead, Team Lead, Service Desk, Employee.
     */
    public function run(): void
    {
        $permissions = Permission::all();
        $permissionIds = $permissions->keyBy('name')->map(fn ($p) => $p->id)->all();

        if (empty($permissionIds)) {
            $this->command->warn('⚠ No permissions found. Please run PermissionSeeder first.');
            return;
        }

        $navigations = Navigation::all();
        if ($navigations->isEmpty()) {
            $this->command->warn('⚠ No navigations found. Please run NavigationSeeder first.');
            return;
        }

        $roles = Role::all()->keyBy('name');
        if ($roles->isEmpty()) {
            $this->command->warn('⚠ No roles found. Please run RoleSeeder first.');
            return;
        }

        $this->command->info('✓ Permissions and navigations loaded');

        // Super Admin roles: all permissions on all navigations
        $superAdminRoleNames = ['Developer Account', 'Web Admin'];
        foreach ($superAdminRoleNames as $roleName) {
            $role = $roles->get($roleName);
            if (!$role) {
                continue;
            }
            foreach ($navigations as $nav) {
                foreach ($permissionIds as $permName => $permId) {
                    RolePermission::updateOrCreate(
                        [
                            'role_id' => $role->id,
                            'navigation_id' => $nav->id,
                            'permission_id' => $permId,
                        ],
                        ['allowed' => true]
                    );
                }
            }
            $this->command->info("✓ {$roleName} permissions assigned (all permissions on all navigations)");
        }

        // Senior Team Lead & Team Lead: all permissions on all navigations
        $teamLeadRoleNames = ['Senior Team Lead', 'Team Lead'];
        foreach ($teamLeadRoleNames as $roleName) {
            $role = $roles->get($roleName);
            if (!$role) {
                continue;
            }
            foreach ($navigations as $nav) {
                foreach ($permissionIds as $permName => $permId) {
                    RolePermission::updateOrCreate(
                        [
                            'role_id' => $role->id,
                            'navigation_id' => $nav->id,
                            'permission_id' => $permId,
                        ],
                        ['allowed' => true]
                    );
                }
            }
            $this->command->info("✓ {$roleName} permissions assigned (all permissions on all navigations)");
        }

        // Service Desk: can_view, can_create, can_edit on all navigations
        $serviceDeskRole = $roles->get('Service Desk');
        if ($serviceDeskRole) {
            $serviceDeskPerms = ['can_view', 'can_create', 'can_edit'];
            foreach ($navigations as $nav) {
                foreach ($serviceDeskPerms as $permName) {
                    $permId = $permissionIds[$permName] ?? null;
                    if ($permId) {
                        RolePermission::updateOrCreate(
                            [
                                'role_id' => $serviceDeskRole->id,
                                'navigation_id' => $nav->id,
                                'permission_id' => $permId,
                            ],
                            ['allowed' => true]
                        );
                    }
                }
            }
            $this->command->info('✓ Service Desk permissions assigned (view, create, edit on all navigations)');
        }

        // Employee: can_view only on all navigations
        $employeeRole = $roles->get('Employee');
        if ($employeeRole) {
            $viewPermId = $permissionIds['can_view'] ?? null;
            if ($viewPermId) {
                foreach ($navigations as $nav) {
                    RolePermission::updateOrCreate(
                        [
                            'role_id' => $employeeRole->id,
                            'navigation_id' => $nav->id,
                            'permission_id' => $viewPermId,
                        ],
                        ['allowed' => true]
                    );
                }
                $this->command->info('✓ Employee permissions assigned (view only on all navigations)');
            }
        }

        $this->command->info('✓ Role permissions created/updated');
    }
}
