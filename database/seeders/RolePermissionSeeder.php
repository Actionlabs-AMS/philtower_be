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
     * Roles: Developer Account, Web Admin, Approver, Agent, Requestor.
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

        // Index navigations by slug for targeted permission assignment
        $navsBySlug = $navigations->keyBy('slug');

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

        // Approver: can_view, can_create, can_edit on ticket management and service catalog
        $approverRole = $roles->get('Approver');
        if ($approverRole) {
            $approverPerms = ['can_view', 'can_create', 'can_edit'];
            $approverNavSlugs = [
                'dashboard',
                'profile',
                'ticket-management',
                'all-tickets',
                'tickets-for-approval',
                'service-catalog',
                'service-types',
                'ticket-statuses',
                'sla-timing',
                'analytics',
                'analytics-dashboard',
            ];
            foreach ($approverNavSlugs as $slug) {
                $nav = $navsBySlug->get($slug);
                if (!$nav) {
                    continue;
                }
                foreach ($approverPerms as $permName) {
                    $permId = $permissionIds[$permName] ?? null;
                    if ($permId) {
                        RolePermission::updateOrCreate(
                            [
                                'role_id' => $approverRole->id,
                                'navigation_id' => $nav->id,
                                'permission_id' => $permId,
                            ],
                            ['allowed' => true]
                        );
                    }
                }
            }
            $this->command->info('✓ Approver permissions assigned (view, create, edit on ticket management & service catalog)');
        }

        // Agent: can_view, can_create, can_edit on ticket management, analytics, and dashboard
        $agentRole = $roles->get('Agent');
        if ($agentRole) {
            $agentPerms = ['can_view', 'can_create', 'can_edit'];
            $agentNavSlugs = [
                'dashboard',
                'profile',
                'ticket-management',
                'all-tickets',
                'tickets-for-approval',
                'analytics',
                'analytics-dashboard',
                'activity-logs',
            ];
            foreach ($agentNavSlugs as $slug) {
                $nav = $navsBySlug->get($slug);
                if (!$nav) {
                    continue;
                }
                foreach ($agentPerms as $permName) {
                    $permId = $permissionIds[$permName] ?? null;
                    if ($permId) {
                        RolePermission::updateOrCreate(
                            [
                                'role_id' => $agentRole->id,
                                'navigation_id' => $nav->id,
                                'permission_id' => $permId,
                            ],
                            ['allowed' => true]
                        );
                    }
                }
            }
            $this->command->info('✓ Agent permissions assigned (view, create, edit on ticket management & analytics)');
        }

        // Requestor: can_view, can_create on my-request and help-center only
        $requestorRole = $roles->get('Requestor');
        if ($requestorRole) {
            $requestorPerms = ['can_view', 'can_create'];
            $requestorNavSlugs = [
                'dashboard',
                'profile',
                'my-request',
                'help-center',
            ];
            foreach ($requestorNavSlugs as $slug) {
                $nav = $navsBySlug->get($slug);
                if (!$nav) {
                    continue;
                }
                foreach ($requestorPerms as $permName) {
                    $permId = $permissionIds[$permName] ?? null;
                    if ($permId) {
                        RolePermission::updateOrCreate(
                            [
                                'role_id' => $requestorRole->id,
                                'navigation_id' => $nav->id,
                                'permission_id' => $permId,
                            ],
                            ['allowed' => true]
                        );
                    }
                }
            }
            $this->command->info('✓ Requestor permissions assigned (view, create on my-request & help-center)');
        }

        $this->command->info('✓ Role permissions created/updated');
    }
}
