<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\UserMeta;
use App\Helpers\PasswordHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test role
        Role::create([
            'name' => 'Test Role',
            'active' => true,
            'is_super_admin' => false,
        ]);
    }

    public function test_user_can_be_created()
    {
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'hashedpassword',
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->user_login);
        $this->assertEquals('test@example.com', $user->user_email);
    }

    public function test_user_has_encrypted_attributes()
    {
        $user = new User();
        $encryptedAttributes = $user->getEncryptedAttributes();
        
        $this->assertContains('user_activation_key', $encryptedAttributes);
    }

    public function test_user_can_save_meta_data()
    {
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'hashedpassword',
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);

        $metaData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890'
        ];

        $user->saveUserMeta($metaData);

        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'first_name',
            'meta_value' => 'John'
        ]);
    }

    public function test_user_can_get_meta_data()
    {
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'hashedpassword',
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);

        $user->saveUserMeta(['first_name' => 'John']);

        $firstName = $user->getMeta('first_name');
        $this->assertEquals('John', $firstName);
    }

    public function test_user_details_attribute()
    {
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'hashedpassword',
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);

        $user->saveUserMeta([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $details = $user->user_details;
        $this->assertArrayHasKey('first_name', $details);
        $this->assertEquals('John', $details['first_name']);
    }

    public function test_user_role_attribute()
    {
        $role = Role::first();
        
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'hashedpassword',
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);

        $user->saveUserMeta([
            'user_role' => json_encode([
                'id' => $role->id,
                'name' => $role->name
            ])
        ]);

        $userRole = $user->user_role;
        $this->assertInstanceOf(Role::class, $userRole);
        $this->assertEquals($role->id, $userRole->id);
    }

    public function test_user_soft_deletes()
    {
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'hashedpassword',
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_user_has_user_metas_relationship()
    {
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'hashedpassword',
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);

        $user->saveUserMeta(['test_key' => 'test_value']);

        $this->assertInstanceOf(UserMeta::class, $user->getUserMetas()->first());
    }
}
