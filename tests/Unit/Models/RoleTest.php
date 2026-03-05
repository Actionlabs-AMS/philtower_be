<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Role;
use App\Models\Navigation;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->createTestData();
    }

    private function createTestData()
    {
        // Create permissions
        $permissions = [
            ['name' => 'can_view', 'label' => 'View'],
            ['name' => 'can_create', 'label' => 'Create'],
            ['name' => 'can_edit', 'label' => 'Edit'],
            ['name' => 'can_delete', 'label' => 'Delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create navigations
        $navigations = [
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'cil-speedometer',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
            ],
            [
                'name' => 'Users',
                'slug' => 'users',
                'icon' => 'cil-user',
                'parent_id' => null,
                'active' => true,
                'show_in_menu' => true,
            ],
        ];

        foreach ($navigations as $navigation) {
            Navigation::create($navigation);
        }
    }

    public function test_role_can_be_created()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'active' => true,
            'is_super_admin' => false,
        ]);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Test Role', $role->name);
        $this->assertTrue($role->active);
        $this->assertFalse($role->is_super_admin);
    }

    public function test_role_has_role_permissions_relationship()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'active' => true,
            'is_super_admin' => false,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $role->rolePermissions());
    }

    public function test_get_permissions_formatted()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'active' => true,
            'is_super_admin' => false,
        ]);

        $navigation = Navigation::first();
        $permission = Permission::first();

        // Create role permission
        RolePermission::create([
            'role_id' => $role->id,
            'navigation_id' => $navigation->id,
            'permission_id' => $permission->id,
            'allowed' => true,
        ]);

        $permissions = $role->getPermissionsFormatted();
        
        $this->assertIsArray($permissions);
        $this->assertArrayHasKey($navigation->id, $permissions);
    }

    public function test_permissions_attribute()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'active' => true,
            'is_super_admin' => false,
        ]);

        $navigation = Navigation::first();
        $permission = Permission::first();

        // Create role permission
        RolePermission::create([
            'role_id' => $role->id,
            'navigation_id' => $navigation->id,
            'permission_id' => $permission->id,
            'allowed' => true,
        ]);

        $permissions = $role->permissions;
        
        $this->assertIsArray($permissions);
    }

    public function test_role_soft_deletes()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'active' => true,
            'is_super_admin' => false,
        ]);

        $role->delete();

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }

    public function test_super_admin_role()
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'active' => true,
            'is_super_admin' => true,
        ]);

        $this->assertTrue($role->is_super_admin);
    }

    public function test_inactive_role()
    {
        $role = Role::create([
            'name' => 'Inactive Role',
            'active' => false,
            'is_super_admin' => false,
        ]);

        $this->assertFalse($role->active);
    }
}
