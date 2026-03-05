<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\User;
use App\Helpers\PasswordHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

class EncryptsAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function test_encrypted_attributes_are_encrypted_on_save()
    {
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => 'test-activation-key',
        ]);

        $user->save();

        // Check that the activation key is encrypted in the database
        $this->assertDatabaseHas('users', [
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
        ]);

        // The activation key should be encrypted (not the original value)
        $userFromDb = User::find($user->id);
        $this->assertNotEquals('test-activation-key', $userFromDb->getAttributes()['user_activation_key']);
    }

    public function test_encrypted_attributes_are_decrypted_on_retrieve()
    {
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => 'test-activation-key',
        ]);

        $user->save();

        // Retrieve the user and check that the activation key is decrypted
        $retrievedUser = User::find($user->id);
        $this->assertEquals('test-activation-key', $retrievedUser->user_activation_key);
    }

    public function test_encrypted_attributes_are_hidden_from_serialization()
    {
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => 'test-activation-key',
        ]);

        $user->save();

        // Check that hidden attributes are not in the array representation
        $userArray = $user->toArray();
        $this->assertArrayNotHasKey('user_pass', $userArray);
        $this->assertArrayNotHasKey('user_salt', $userArray);
        $this->assertArrayNotHasKey('user_activation_key', $userArray);
    }

    public function test_encrypted_attributes_are_hidden_from_json()
    {
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => 'test-activation-key',
        ]);

        $user->save();

        // Check that hidden attributes are not in the JSON representation
        $userJson = $user->toJson();
        $this->assertStringNotContainsString('user_pass', $userJson);
        $this->assertStringNotContainsString('user_salt', $userJson);
        $this->assertStringNotContainsString('user_activation_key', $userJson);
    }

    public function test_encryption_works_with_special_characters()
    {
        $specialKey = 'test-key-with-special-chars!@#$%^&*()_+{}|:"<>?[]\\;\',./';
        
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => $specialKey,
        ]);

        $user->save();

        // Retrieve and verify the special characters are preserved
        $retrievedUser = User::find($user->id);
        $this->assertEquals($specialKey, $retrievedUser->user_activation_key);
    }

    public function test_encryption_works_with_unicode_characters()
    {
        $unicodeKey = 'test-key-with-unicode-测试-🚀-ñáéíóú';
        
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => $unicodeKey,
        ]);

        $user->save();

        // Retrieve and verify the unicode characters are preserved
        $retrievedUser = User::find($user->id);
        $this->assertEquals($unicodeKey, $retrievedUser->user_activation_key);
    }

    public function test_encryption_works_with_empty_string()
    {
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => '',
        ]);

        $user->save();

        // Retrieve and verify empty string is preserved
        $retrievedUser = User::find($user->id);
        $this->assertEquals('', $retrievedUser->user_activation_key);
    }

    public function test_encryption_works_with_null_value()
    {
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => null,
        ]);

        $user->save();

        // Retrieve and verify null is preserved
        $retrievedUser = User::find($user->id);
        $this->assertNull($retrievedUser->user_activation_key);
    }

    public function test_encrypted_attributes_list()
    {
        $user = new User();
        $encryptedAttributes = $user->getEncryptedAttributes();
        
        $this->assertIsArray($encryptedAttributes);
        $this->assertContains('user_activation_key', $encryptedAttributes);
    }

    public function test_encryption_handles_very_long_strings()
    {
        $longKey = str_repeat('a', 1000); // 1000 character string
        
        $user = new User([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
            'user_activation_key' => $longKey,
        ]);

        $user->save();

        // Retrieve and verify the long string is preserved
        $retrievedUser = User::find($user->id);
        $this->assertEquals($longKey, $retrievedUser->user_activation_key);
    }
}
