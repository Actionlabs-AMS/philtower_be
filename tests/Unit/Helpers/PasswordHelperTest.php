<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\PasswordHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class PasswordHelperTest extends TestCase
{
    public function test_generate_salt()
    {
        $salt = PasswordHelper::generateSalt();
        
        $this->assertIsString($salt);
        $this->assertEquals(64, strlen($salt)); // 32 bytes = 64 hex characters
        $this->assertTrue(ctype_xdigit($salt));
    }

    public function test_generate_salt_is_unique()
    {
        $salt1 = PasswordHelper::generateSalt();
        $salt2 = PasswordHelper::generateSalt();
        
        $this->assertNotEquals($salt1, $salt2);
    }

    public function test_generate_password()
    {
        $salt = 'testsalt123';
        $password = 'testpassword';
        
        $hashedPassword = PasswordHelper::generatePassword($salt, $password);
        
        $this->assertIsString($hashedPassword);
        $this->assertNotEquals($password, $hashedPassword);
        $this->assertTrue(strlen($hashedPassword) > 0);
    }

    public function test_generate_password_with_different_salts()
    {
        $password = 'testpassword';
        $salt1 = 'salt1';
        $salt2 = 'salt2';
        
        $hashed1 = PasswordHelper::generatePassword($salt1, $password);
        $hashed2 = PasswordHelper::generatePassword($salt2, $password);
        
        $this->assertNotEquals($hashed1, $hashed2);
    }

    public function test_verify_password()
    {
        $salt = 'testsalt123';
        $password = 'testpassword';
        $hashedPassword = PasswordHelper::generatePassword($salt, $password);
        
        $this->assertTrue(PasswordHelper::verifyPassword($salt, $password, $hashedPassword));
        $this->assertFalse(PasswordHelper::verifyPassword($salt, 'wrongpassword', $hashedPassword));
        $this->assertFalse(PasswordHelper::verifyPassword('wrongsalt', $password, $hashedPassword));
    }

    public function test_generate_reset_token()
    {
        $token = PasswordHelper::generateResetToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
        $this->assertTrue(ctype_xdigit($token));
    }

    public function test_generate_reset_token_is_unique()
    {
        $token1 = PasswordHelper::generateResetToken();
        $token2 = PasswordHelper::generateResetToken();
        
        $this->assertNotEquals($token1, $token2);
    }

    public function test_hash_reset_token()
    {
        $token = 'testtoken123';
        $hashedToken = PasswordHelper::hashResetToken($token);
        
        $this->assertIsString($hashedToken);
        $this->assertNotEquals($token, $hashedToken);
    }

    public function test_verify_reset_token()
    {
        $token = 'testtoken123';
        $hashedToken = PasswordHelper::hashResetToken($token);
        
        $this->assertTrue(PasswordHelper::verifyResetToken($token, $hashedToken));
        $this->assertFalse(PasswordHelper::verifyResetToken('wrongtoken', $hashedToken));
    }

    public function test_generate_temporary_password()
    {
        $tempPassword = PasswordHelper::generateTemporaryPassword();
        
        $this->assertIsString($tempPassword);
        $this->assertEquals(12, strlen($tempPassword));
        $this->assertTrue(ctype_alnum($tempPassword));
    }

    public function test_generate_temporary_password_is_unique()
    {
        $temp1 = PasswordHelper::generateTemporaryPassword();
        $temp2 = PasswordHelper::generateTemporaryPassword();
        
        $this->assertNotEquals($temp1, $temp2);
    }

    public function test_check_password_strength_weak_password()
    {
        $result = PasswordHelper::checkPasswordStrength('123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('feedback', $result);
        $this->assertArrayHasKey('is_strong', $result);
        $this->assertFalse($result['is_strong']);
        $this->assertLessThan(80, $result['score']);
    }

    public function test_check_password_strength_strong_password()
    {
        $result = PasswordHelper::checkPasswordStrength('StrongP@ssw0rd123!');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('feedback', $result);
        $this->assertArrayHasKey('is_strong', $result);
        $this->assertTrue($result['is_strong']);
        $this->assertGreaterThanOrEqual(80, $result['score']);
    }

    public function test_check_password_strength_medium_password()
    {
        $result = PasswordHelper::checkPasswordStrength('MediumPass123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('feedback', $result);
        $this->assertArrayHasKey('is_strong', $result);
        $this->assertIsBool($result['is_strong']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_check_password_strength_empty_password()
    {
        $result = PasswordHelper::checkPasswordStrength('');
        
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['score']);
        $this->assertFalse($result['is_strong']);
        $this->assertIsArray($result['feedback']);
    }

    public function test_password_strength_feedback_structure()
    {
        $result = PasswordHelper::checkPasswordStrength('TestPassword123!');
        
        $this->assertArrayHasKey('length', $result['feedback']);
        $this->assertArrayHasKey('uppercase', $result['feedback']);
        $this->assertArrayHasKey('lowercase', $result['feedback']);
        $this->assertArrayHasKey('numbers', $result['feedback']);
        $this->assertArrayHasKey('symbols', $result['feedback']);
        $this->assertArrayHasKey('common_patterns', $result['feedback']);
    }
}
